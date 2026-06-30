<?php

/**
 * Handler: Đơn hàng
 * Prefix: "order"
 * 
 * Callback data:
 * - order:list                  → Danh sách đơn hàng của user (phân trang, trang 1)
 * - order:list:{page}           → Phân trang danh sách đơn hàng
 * - order:detail:{trans_id}     → Chi tiết 1 đơn hàng + danh sách account đã mua
 * - order:download:{trans_id}   → Tải file .txt danh sách account của đơn hàng
 * 
 * Dữ liệu lấy từ:
 * - `product_order`: header đơn hàng (trans_id, product_name, amount, pay, time, ...)
 * - `product_sold`: dòng chi tiết account đã mua (trans_id, account)
 */
function tgshop_handle_order($bot, $params)
{
    $action = isset($params[0]) ? $params[0] : 'list';

    switch ($action) {
        // Danh sách đơn hàng (phân trang)
        case 'list':
            $page = isset($params[1]) ? intval($params[1]) : 1;
            tgshop_order_list($bot, $page);
            break;

        // Chi tiết 1 đơn hàng cụ thể
        // trans_id có thể chứa ký tự alpha-num nhưng không có ':' → lấy params[1] là đủ
        case 'detail':
            $trans_id = isset($params[1]) ? $params[1] : '';
            tgshop_order_detail($bot, $trans_id);
            break;

        // Tải file .txt danh sách account của đơn hàng
        case 'download':
            $trans_id = isset($params[1]) ? $params[1] : '';
            tgshop_order_download($bot, $trans_id);
            break;

        default:
            tgshop_order_list($bot, 1);
            break;
    }
}

/**
 * Hiển thị danh sách đơn hàng của user hiện tại, sắp xếp mới nhất trước
 * 
 * Chỉ lấy đơn chưa bị user xoá mềm (trash = 0) — giống logic trang web.
 * 
 * @param int $page Số trang hiện tại (bắt đầu từ 1)
 */
function tgshop_order_list($bot, $page)
{
    $limit = 5; // 5 đơn/trang — đơn hàng có nhiều thông tin cần show nên giữ ít hơn product list
    if ($page < 1) $page = 1;

    $user_id = $bot->user['id'];

    // Đếm tổng đơn hàng (tôn trọng trash = 0 giống web)
    $total = $bot->CMSNT->num_rows_safe(
        "SELECT * FROM `product_order` WHERE `buyer` = ? AND `trash` = 0",
        [$user_id]
    );

    $total_pages = max(1, (int) ceil($total / $limit));
    if ($page > $total_pages) $page = $total_pages;
    $offset = ($page - 1) * $limit;

    // Lấy các đơn trong trang hiện tại
    $orders = $bot->CMSNT->get_list_safe(
        "SELECT `id`, `trans_id`, `product_id`, `product_name`, `amount`, `money`, `pay`, `create_gettime`
         FROM `product_order`
         WHERE `buyer` = ? AND `trash` = 0
         ORDER BY `id` DESC
         LIMIT ?, ?",
        [$user_id, $offset, $limit]
    );

    // Header + thống kê
    $text  = __('📦 <b>Đơn hàng của bạn</b>') . "\n";
    $text .= sprintf(__('📄 Trang %s/%s (%s đơn)'), $page, $total_pages, $total) . "\n";
    $text .= "━━━━━━━━━━━━━━━━━━\n\n";

    if (empty($orders)) {
        $text .= __('<i>Bạn chưa có đơn hàng nào.</i>') . "\n\n";
        $text .= __('Nhấn nút bên dưới để bắt đầu mua sắm 🛒');

        $keyboard = TelegramShopBot::inlineKeyboard([
            [
                ['text' => __('🛒 Mua sắm ngay'), 'data' => 'shop:categories']
            ],
            [
                TelegramShopBot::backButton('menu:main', __('🏠 Menu chính'))
            ]
        ]);
        $bot->reply($text, $keyboard);
        return;
    }

    // Render từng đơn hàng (text mô tả ngắn gọn)
    $index = $offset;
    foreach ($orders as $order) {
        $index++;
        $safe_product = htmlspecialchars($order['product_name'], ENT_QUOTES, 'UTF-8');
        $text .= sprintf(__('🧾 <b>#%s</b>'), $order['trans_id']) . "\n";
        $text .= "   🛍 <b>" . $safe_product . "</b>\n";
        $text .= sprintf(__('   🔢 SL: %s'), format_cash($order['amount']));
        $text .= ' | ' . sprintf(__('💰 %s'), format_currency($order['pay'])) . "\n";
        $text .= sprintf(__('   🕒 %s'), $order['create_gettime']) . "\n\n";
    }

    // Nút chi tiết cho từng đơn (label gọn: #trans_id + SL)
    $order_buttons = [];
    foreach ($orders as $order) {
        // Giới hạn label ~30 ký tự để không bị tràn trên màn hình nhỏ
        $label = '👁 #' . $order['trans_id'] . ' (' . format_cash($order['amount']) . ')';
        if (mb_strlen($label, 'UTF-8') > 35) {
            $label = mb_substr($label, 0, 32, 'UTF-8') . '...';
        }
        $order_buttons[] = [
            ['text' => $label, 'data' => 'order:detail:' . $order['trans_id']]
        ];
    }

    // Nút phân trang
    $nav_buttons = [];
    if ($page > 1) {
        $nav_buttons[] = ['text' => __('◀️ Trước'), 'data' => 'order:list:' . ($page - 1)];
    }
    $nav_buttons[] = ['text' => sprintf(__('📄 %s/%s'), $page, $total_pages), 'data' => 'order:list:' . $page];
    if ($page < $total_pages) {
        $nav_buttons[] = ['text' => __('Sau ▶️'), 'data' => 'order:list:' . ($page + 1)];
    }

    // Gộp tất cả nút
    $all_buttons = array_merge(
        $order_buttons,
        [$nav_buttons],
        [
            [
                ['text' => __('🛒 Mua tiếp'), 'data' => 'shop:categories'],
                TelegramShopBot::backButton('menu:main', __('🏠 Menu chính'))
            ]
        ]
    );

    $keyboard = TelegramShopBot::inlineKeyboard($all_buttons);
    $bot->reply($text, $keyboard);
}

/**
 * Hiển thị chi tiết 1 đơn hàng + toàn bộ danh sách account đã mua
 * 
 * Bảo mật: chỉ cho xem đơn thuộc về user đang request (buyer = user.id).
 * Account list: <=3500 ký tự hiển thị inline <pre>, dài hơn → gửi file .txt đính kèm.
 * 
 * @param string $trans_id Mã đơn hàng
 */
function tgshop_order_detail($bot, $trans_id)
{
    // Validate trans_id: chỉ cho alpha-num + một số ký tự cơ bản sinh bởi uniqid()/random()
    // Làm để tránh SQL/callback injection — mặc dù prepared statement đã an toàn.
    if (!preg_match('/^[A-Za-z0-9]{1,60}$/', (string) $trans_id)) {
        $bot->reply(__('❌ Mã đơn không hợp lệ.'));
        return;
    }

    // Lấy đơn, ràng buộc buyer = user hiện tại để chặn view đơn người khác
    $order = $bot->CMSNT->get_row_safe(
        "SELECT * FROM `product_order` WHERE `trans_id` = ? AND `buyer` = ? AND `trash` = 0",
        [$trans_id, $bot->user['id']]
    );

    if (!$order) {
        $bot->reply(__('❌ Không tìm thấy đơn hàng này hoặc bạn không có quyền xem.'));
        return;
    }

    // Lấy danh sách account đã mua trong đơn này
    $sold_rows = $bot->CMSNT->get_list_safe(
        "SELECT `account` FROM `product_sold` WHERE `trans_id` = ?",
        [$trans_id]
    );

    // Build text info
    $safe_product = htmlspecialchars($order['product_name'], ENT_QUOTES, 'UTF-8');
    $text  = __('🧾 <b>Chi tiết đơn hàng</b>') . "\n";
    $text .= "━━━━━━━━━━━━━━━━━━\n";
    $text .= sprintf(__('🆔 <b>Mã đơn:</b> <code>%s</code>'), $order['trans_id']) . "\n";
    $text .= sprintf(__('🛍 <b>Sản phẩm:</b> %s'), $safe_product) . "\n";
    $text .= sprintf(__('🔢 <b>Số lượng:</b> %s'), format_cash($order['amount'])) . "\n";
    $text .= sprintf(__('🧮 <b>Tạm tính:</b> %s'), format_currency($order['money'])) . "\n";
    $text .= sprintf(__('💳 <b>Đã thanh toán:</b> %s'), format_currency($order['pay'])) . "\n";
    $text .= sprintf(__('🕒 <b>Thời gian:</b> %s'), $order['create_gettime']) . "\n";

    // Keyboard: tải file + mua lại + điều hướng
    // Nút tải file chỉ hiện khi có account thật (sold_rows không rỗng) — xử lý bên dưới
    $has_accounts = !empty($sold_rows);
    $keyboard_rows = [];
    if ($has_accounts) {
        $keyboard_rows[] = [
            ['text' => __('📥 Tải file .txt'), 'data' => 'order:download:' . $order['trans_id']]
        ];
    }
    $keyboard_rows[] = [
        ['text' => __('🛒 Mua lại'), 'data' => 'shop:detail:' . $order['product_id']]
    ];
    $keyboard_rows[] = [
        TelegramShopBot::backButton('order:list', __('⬅️ Danh sách đơn')),
        TelegramShopBot::backButton('menu:main', __('🏠 Menu chính'))
    ];
    $keyboard = TelegramShopBot::inlineKeyboard($keyboard_rows);

    if (!$has_accounts) {
        $text .= "\n" . __('<i>⚠️ Chưa có dữ liệu sản phẩm — có thể đơn đang xử lý.</i>');
        $bot->reply($text, $keyboard);
        return;
    }

    // Gộp accounts thành text (mỗi dòng 1 account, decode nếu đã escape)
    $accounts_text = '';
    foreach ($sold_rows as $row) {
        $line = preg_replace("/\r/", '', htmlspecialchars_decode((string) $row['account']));
        $accounts_text .= $line . "\n";
    }
    $accounts_text = rtrim($accounts_text, "\n");

    // Ngưỡng hiển thị inline:
    // - < 10 account: đủ gọn để user copy trực tiếp trong tin nhắn
    // - AND tổng text <= 3500 ký tự: chừa chỗ cho header + thẻ HTML (Telegram giới hạn 4096)
    // Nếu vượt một trong 2 điều kiện → auto gửi file .txt (vì 1 account có thể >255 chars)
    if (count($sold_rows) < 10 && mb_strlen($accounts_text, 'UTF-8') <= 3500) {
        $text .= "\n" . __('📄 <b>Sản phẩm đã mua:</b>') . "\n";
        $text .= "<pre>" . htmlspecialchars($accounts_text, ENT_QUOTES, 'UTF-8') . "</pre>";
        $bot->reply($text, $keyboard);
        return;
    }

    // Danh sách dài → send 2 tin nhắn: header text (có keyboard) + file .txt kèm
    $notice = $text . "\n" . sprintf(__('📎 Danh sách %s sản phẩm được gửi trong file đính kèm.'), format_cash(count($sold_rows)));
    $bot->reply($notice, $keyboard);

    // Ghi file tạm và gửi qua sendDocument
    $tmp_dir = sys_get_temp_dir();
    $filename = 'order_' . $trans_id . '.txt';
    $filepath = $tmp_dir . DIRECTORY_SEPARATOR . $filename;

    $file_header  = "===============================\n";
    $file_header .= "Order: #{$trans_id}\n";
    $file_header .= "Product: {$order['product_name']}\n";
    $file_header .= "Amount: " . format_cash($order['amount']) . "\n";
    $file_header .= "Time: " . $order['create_gettime'] . "\n";
    $file_header .= "===============================\n\n";

    file_put_contents($filepath, $file_header . $accounts_text);

    $caption = sprintf(__('📄 Đơn hàng #%s'), $trans_id);
    $bot->sendDocument($filepath, $filename, 'text/plain', $caption);

    // Xoá file tạm để tránh rác trên server
    @unlink($filepath);
}

/**
 * Tải file .txt danh sách account của đơn hàng
 * 
 * Dùng chung cho cả 2 luồng: nút sau khi mua xong & nút trong chi tiết đơn.
 * Chỉ gửi file qua sendDocument, KHÔNG edit/reply message gốc để giữ UI hiện tại.
 * 
 * @param string $trans_id Mã đơn hàng
 */
function tgshop_order_download($bot, $trans_id)
{
    // Validate trans_id — regex giống tgshop_order_detail để thống nhất
    if (!preg_match('/^[A-Za-z0-9]{1,60}$/', (string) $trans_id)) {
        $bot->sendMessage(__('❌ Mã đơn không hợp lệ.'));
        return;
    }

    // Bảo mật: đơn phải thuộc user hiện tại, ngay cả khi biết trans_id người khác
    $order = $bot->CMSNT->get_row_safe(
        "SELECT `trans_id`, `product_name`, `amount`, `create_gettime`
         FROM `product_order`
         WHERE `trans_id` = ? AND `buyer` = ? AND `trash` = 0",
        [$trans_id, $bot->user['id']]
    );

    if (!$order) {
        $bot->sendMessage(__('❌ Không tìm thấy đơn hàng này hoặc bạn không có quyền xem.'));
        return;
    }

    // Lấy danh sách account
    $sold_rows = $bot->CMSNT->get_list_safe(
        "SELECT `account` FROM `product_sold` WHERE `trans_id` = ?",
        [$trans_id]
    );

    if (empty($sold_rows)) {
        $bot->sendMessage(__('⚠️ Đơn hàng này chưa có dữ liệu sản phẩm để tải.'));
        return;
    }

    // Gộp accounts (decode nếu đã escape, chuẩn hoá newline)
    $accounts_text = '';
    foreach ($sold_rows as $row) {
        $line = preg_replace("/\r/", '', htmlspecialchars_decode((string) $row['account']));
        $accounts_text .= $line . "\n";
    }
    $accounts_text = rtrim($accounts_text, "\n");

    // Ghi file tạm kèm header thông tin đơn — dễ tra cứu về sau
    $tmp_dir = sys_get_temp_dir();
    $filename = 'order_' . $trans_id . '.txt';
    $filepath = $tmp_dir . DIRECTORY_SEPARATOR . $filename;

    $file_header  = "===============================\n";
    $file_header .= "Order: #{$trans_id}\n";
    $file_header .= "Product: {$order['product_name']}\n";
    $file_header .= "Amount: " . format_cash($order['amount']) . "\n";
    $file_header .= "Time: " . $order['create_gettime'] . "\n";
    $file_header .= "===============================\n\n";

    file_put_contents($filepath, $file_header . $accounts_text);

    $caption = sprintf(__('📄 Đơn hàng #%s — %s sản phẩm'), $trans_id, format_cash(count($sold_rows)));
    $bot->sendDocument($filepath, $filename, 'text/plain', $caption);

    @unlink($filepath);
}
