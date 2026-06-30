<?php

/**
 * Handler: Quản lý tài khoản
 * Prefix: "account"
 * 
 * Callback data:
 * - account:info         → Xem thông tin tài khoản chi tiết
 * - account:balance      → Xem số dư nhanh
 * - account:history      → Lịch sử giao dịch (nạp/trừ tiền)
 * - account:password     → Tạo/đổi mật khẩu đăng nhập website
 * - account:password:set → Xử lý text mật khẩu user nhập vào
 */
function tgshop_handle_account($bot, $params)
{
    $action = isset($params[0]) ? $params[0] : 'info';

    switch ($action) {
        case 'info':
            tgshop_account_info($bot);
            break;

        case 'balance':
            tgshop_account_balance($bot);
            break;

        case 'history':
            tgshop_account_history($bot, $params);
            break;

        case 'password':
            tgshop_account_password($bot, $params);
            break;

        default:
            tgshop_account_info($bot);
            break;
    }
}

/**
 * Hiển thị thông tin tài khoản chi tiết
 */
function tgshop_account_info($bot)
{
    $user = $bot->user;
    $balance = $bot->getFormattedBalance();
    $total_money = format_currency($user['total_money']);

    // Chuỗi có dữ liệu động dùng sprintf để giữ key dịch cố định.
    $text  = __('👤 <b>Thông tin tài khoản</b>') . "\n\n";
    $text .= sprintf(__('🆔 <b>Username:</b> <code>%s</code>'), $user['username']) . "\n";
    $text .= sprintf(__('📧 <b>Email:</b> <code>%s</code>'), $user['email']) . "\n";
    if (!empty($user['fullname'])) {
        $text .= sprintf(__('📝 <b>Họ tên:</b> %s'), $user['fullname']) . "\n";
    }
    $text .= "\n";
    $text .= sprintf(__('💰 <b>Số dư:</b> <code>%s</code>'), $balance) . "\n";
    $text .= sprintf(__('💵 <b>Tổng nạp:</b> <code>%s</code>'), $total_money) . "\n";
    $text .= sprintf(__('📅 <b>Ngày tạo:</b> %s'), $user['create_date']) . "\n";

    $keyboard = TelegramShopBot::inlineKeyboard([
        [
            ['text' => __('💰 Nạp tiền'), 'data' => 'recharge:methods'],
            ['text' => __('📋 Lịch sử'), 'data' => 'account:history']
        ],
        // Nút tạo/đổi mật khẩu để user đăng nhập trực tiếp website
        [
            ['text' => __('🔑 Tạo mật khẩu'), 'data' => 'account:password']
        ],
        [
            TelegramShopBot::backButton('menu:main')
        ]
    ]);

    $bot->reply($text, $keyboard);
}

/**
 * Hiển thị số dư nhanh
 */
function tgshop_account_balance($bot)
{
    $balance = $bot->getFormattedBalance();

    $text = sprintf(__('💰 <b>Số dư hiện tại:</b> <code>%s</code>'), $balance);

    $keyboard = TelegramShopBot::inlineKeyboard([
        [
            ['text' => __('💰 Nạp tiền'), 'data' => 'recharge:methods']
        ],
        [
            TelegramShopBot::backButton('menu:main')
        ]
    ]);

    $bot->reply($text, $keyboard);
}

/**
 * Hiển thị lịch sử giao dịch (nạp/trừ tiền)
 * Phân trang bằng nút Trước/Sau
 */
function tgshop_account_history($bot, $params)
{
    // Phân trang: params[1] là số trang (mặc định trang 1)
    $page = isset($params[1]) ? intval($params[1]) : 1;
    if ($page < 1) $page = 1;
    $limit = 5; // Số dòng mỗi trang (giữ gọn vì giao diện Telegram nhỏ)
    $offset = ($page - 1) * $limit;

    // Đếm tổng số giao dịch
    $total = $bot->CMSNT->num_rows_safe(
        "SELECT * FROM `dongtien` WHERE `user_id` = ?",
        [$bot->user['id']]
    );

    $total_pages = max(1, ceil($total / $limit));
    if ($page > $total_pages) $page = $total_pages;

    // Lấy danh sách giao dịch
    $transactions = $bot->CMSNT->get_list_safe(
        "SELECT * FROM `dongtien` WHERE `user_id` = ? ORDER BY `id` DESC LIMIT ?, ?",
        [$bot->user['id'], $offset, $limit]
    );

    $text = sprintf(__('📋 <b>Lịch sử giao dịch</b> (Trang %s/%s)'), $page, $total_pages) . "\n\n";

    if (empty($transactions)) {
        $text .= __('<i>Chưa có giao dịch nào.</i>');
    } else {
        foreach ($transactions as $tx) {
            // Xác định icon dựa trên loại giao dịch (cộng/trừ)
            $amount_change = floatval($tx['sotienthaydoi']);
            $icon = $amount_change >= 0 ? '🟢' : '🔴';
            $amount_str = number_format($amount_change, 0, ',', '.');

            $text .= "{$icon} <code>{$amount_str}</code>\n";
            $text .= sprintf(__('   📝 %s'), $tx['noidung']) . "\n";
            $text .= sprintf(__('   🕐 %s'), $tx['create_gettime']) . "\n\n";
        }
    }

    // Xây dựng nút phân trang
    $nav_buttons = [];
    if ($page > 1) {
        $nav_buttons[] = ['text' => __('◀️ Trước'), 'data' => 'account:history:' . ($page - 1)];
    }
    $nav_buttons[] = ['text' => sprintf(__('📄 %s/%s'), $page, $total_pages), 'data' => 'account:history:' . $page];
    if ($page < $total_pages) {
        $nav_buttons[] = ['text' => __('Sau ▶️'), 'data' => 'account:history:' . ($page + 1)];
    }

    $keyboard = TelegramShopBot::inlineKeyboard([
        $nav_buttons,
        [
            TelegramShopBot::backButton('account:info', __('⬅️ Tài khoản')),
            TelegramShopBot::backButton('menu:main', __('🏠 Menu chính'))
        ]
    ]);

    $bot->reply($text, $keyboard);
}

// =========================================================================
// TẠO MẬT KHẨU ĐĂNG NHẬP WEBSITE
// =========================================================================

/**
 * Router con cho chức năng tạo/đổi mật khẩu
 * - account:password      → Hiển thị form hướng dẫn nhập mật khẩu
 * - account:password:set  → Xử lý text mật khẩu user nhập (từ state machine)
 */
function tgshop_account_password($bot, $params)
{
    $sub_action = isset($params[1]) ? $params[1] : 'form';

    switch ($sub_action) {
        // User nhấn nút "🔑 Tạo mật khẩu" → hiển thị hướng dẫn
        case 'form':
            tgshop_account_password_form($bot);
            break;

        // State machine route: user gõ text mật khẩu → xử lý
        case 'set':
            $password = isset($params[2]) ? $params[2] : '';
            tgshop_account_password_process($bot, $password);
            break;

        default:
            tgshop_account_password_form($bot);
            break;
    }
}

/**
 * Hiển thị hướng dẫn nhập mật khẩu và bật state chờ input text
 * User sẽ gõ mật khẩu → processMessage route qua state → account:password:set:{text}
 */
function tgshop_account_password_form($bot)
{
    // Hướng dẫn user nhập mật khẩu mới
    $text  = __('🔑 <b>Tạo mật khẩu tài khoản</b>') . "\n\n";
    $text .= __('Sau khi tạo mật khẩu, thông tin tài khoản của bạn sẽ là:') . "\n";
    $text .= sprintf(__('👤 <b>Username:</b> <code>%s</code>'), $bot->user['username']) . "\n\n";
    $text .= __('📝 <b>Vui lòng nhập mật khẩu mới</b> (6-50 ký tự):');

    $keyboard = TelegramShopBot::inlineKeyboard([
        [
            TelegramShopBot::backButton('account:info', __('⬅️ Hủy'))
        ]
    ]);

    $bot->reply($text, $keyboard);

    // Bật state chờ user nhập text mật khẩu
    // Khi user gõ text → processMessage → getState() → route đến 'account:password:set:{text}'
    $bot->setState('account:password:set');
}

/**
 * Xử lý mật khẩu user nhập: validate → hash → lưu DB → thông báo kết quả
 * Bảo mật: xóa tin nhắn chứa mật khẩu ngay sau khi xử lý
 * 
 * @param TelegramShopBot $bot Instance Bot
 * @param string $password Mật khẩu user vừa gõ
 */
function tgshop_account_password_process($bot, $password)
{
    // Xóa tin nhắn chứa mật khẩu ngay lập tức để không bị lộ trong chat history
    if ($bot->message_id) {
        $bot->deleteMessage($bot->message_id);
    }

    // Validate mật khẩu dùng hàm chuẩn hệ thống (tối thiểu 6, tối đa 50 ký tự)
    $result = validate_password($password, 6, 50);
    if (!$result['success']) {
        // Mật khẩu không đạt yêu cầu → thông báo lỗi + giữ state để user thử lại
        $text  = __('❌ <b>Mật khẩu không hợp lệ</b>') . "\n\n";
        $text .= sprintf(__('📋 <b>Lỗi:</b> %s'), $result['message']) . "\n\n";
        $text .= __('📝 Vui lòng nhập lại mật khẩu mới (6-50 ký tự):');

        $keyboard = TelegramShopBot::inlineKeyboard([
            [
                TelegramShopBot::backButton('account:info', __('⬅️ Hủy'))
            ]
        ]);

        $bot->sendMessage($text, $keyboard);

        // Giữ state để user có thể thử nhập lại ngay
        $bot->setState('account:password:set');
        return;
    }

    // Hash mật khẩu theo cấu hình hệ thống (md5/bcrypt/sha1)
    $hashed_password = TypePassword($result['password']);

    // Cập nhật mật khẩu mới vào database
    $bot->CMSNT->update("users", [
        'password' => $hashed_password
    ], " `id` = ? ", [$bot->user['id']]);

    // Ghi log bảo mật cho hành động thay đổi mật khẩu
    $bot->CMSNT->insert("logs", [
        'user_id'    => $bot->user['id'],
        'ip'         => '0.0.0.0',
        'device'     => 'Telegram Bot',
        'createdate' => gettime(),
        'action'     => __('Tạo/đổi mật khẩu đăng nhập website qua Telegram Shopping Bot')
    ]);

    // Thông báo thành công — KHÔNG hiển thị lại mật khẩu vì lý do bảo mật
    $text  = __('✅ <b>Tạo mật khẩu thành công!</b>') . "\n\n";
    $text .= __('Thông tin tài khoản của bạn đã được cập nhật mật khẩu mới thành công.') . "\n";
    $text .= sprintf(__('👤 <b>Username:</b> <code>%s</code>'), $bot->user['username']) . "\n";
    $text .= __('🔑 <b>Mật khẩu:</b> mật khẩu bạn vừa nhập') . "\n";

    $keyboard = TelegramShopBot::inlineKeyboard([
        [
            TelegramShopBot::backButton('account:info', __('⬅️ Tài khoản')),
            TelegramShopBot::backButton('menu:main', __('🏠 Menu chính'))
        ]
    ]);

    $bot->sendMessage($text, $keyboard);
}
