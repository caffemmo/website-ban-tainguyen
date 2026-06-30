<?php

/**
 * Handler: Nạp tiền
 * Prefix: "recharge"
 * 
 * Callback data:
 * - recharge:methods                        → Danh sách cổng nạp tiền đang bật
 * - recharge:bank                           → Chọn ngân hàng (nếu nhiều) hoặc hiển thị trực tiếp
 * - recharge:bank_detail:{bank_id}          → Hiển thị thông tin chuyển khoản 1 ngân hàng
 * - recharge:crypto                         → Chọn mệnh giá USDT
 * - recharge:crypto_confirm:{amount}        → Xác nhận + tạo hóa đơn crypto
 * - recharge:tripay                         → Chọn kênh thanh toán Tripay
 * - recharge:tripay_channel:{channel}       → Chọn mệnh giá cho kênh Tripay
 * - recharge:tripay_confirm:{channel}:{amount} → Xác nhận + tạo hóa đơn Tripay
 */
function tgshop_handle_recharge($bot, $params)
{
    $action = isset($params[0]) ? $params[0] : 'methods';

    switch ($action) {
        case 'methods':
            tgshop_recharge_methods($bot);
            break;

        case 'bank':
            tgshop_recharge_bank($bot);
            break;

        case 'bank_detail':
            $bank_id = isset($params[1]) ? intval($params[1]) : 0;
            tgshop_recharge_bank_detail($bot, $bank_id);
            break;

        // User gõ text họ tên → processMessage route đến đây
        case 'process_fullname':
            $fullname_text = isset($params[1]) ? $params[1] : '';
            tgshop_recharge_process_fullname($bot, $fullname_text);
            break;

        case 'crypto':
            tgshop_recharge_crypto($bot);
            break;

        case 'crypto_confirm':
            $amount = isset($params[1]) ? floatval($params[1]) : 0;
            tgshop_recharge_crypto_confirm($bot, $amount);
            break;

        // Hiện prompt nhập số tiền USDT tùy ý
        case 'crypto_input':
            tgshop_recharge_crypto_input($bot);
            break;

        // User gõ text số tiền → processMessage route đến đây qua state
        case 'crypto_custom':
            $amount_text = isset($params[1]) ? $params[1] : '';
            tgshop_recharge_crypto_custom($bot, $amount_text);
            break;

        case 'tripay':
            tgshop_recharge_tripay($bot);
            break;

        case 'tripay_channel':
            $channel = isset($params[1]) ? $params[1] : '';
            tgshop_recharge_tripay_channel($bot, $channel);
            break;

        case 'tripay_confirm':
            $channel = isset($params[1]) ? $params[1] : '';
            $amount = isset($params[2]) ? intval($params[2]) : 0;
            tgshop_recharge_tripay_confirm($bot, $channel, $amount);
            break;

        default:
            tgshop_recharge_methods($bot);
            break;
    }
}

// =========================================================================
// DANH SÁCH CỔNG NẠP TIỀN
// =========================================================================

/**
 * Hiển thị danh sách cổng nạp tiền đang bật
 * Kiểm tra status từng cổng trong settings, chỉ hiện cổng nào đang active
 */
function tgshop_recharge_methods($bot)
{
    $balance = $bot->getFormattedBalance();

    $text  = __('💰 <b>Nạp tiền</b>') . "\n\n";
    $text .= sprintf(__('💳 <b>Số dư hiện tại:</b> %s'), $balance) . "\n\n";
    $text .= __('Chọn phương thức nạp tiền:');

    $buttons = [];
    $row = [];

    // Chỉ hiển thị cổng đang bật trong settings
    if ($bot->CMSNT->site('bank_status') == 1) {
        $row[] = ['text' => __('🏦 Ngân hàng'), 'data' => 'recharge:bank'];
    }
    if ($bot->CMSNT->site('crypto_status') == 1) {
        $row[] = ['text' => __('💎 Crypto'), 'data' => 'recharge:crypto'];
    }
    if (!empty($row)) {
        $buttons[] = $row;
        $row = [];
    }

    if ($bot->CMSNT->site('tripay_status') == 1) {
        $tripay_name = $bot->CMSNT->site('tripay_name');
        if (empty($tripay_name)) {
            $tripay_name = 'TriPay';
        }
        $row[] = ['text' => '💳 ' . $tripay_name, 'data' => 'recharge:tripay'];
    }
    if (!empty($row)) {
        $buttons[] = $row;
    }

    // Nếu không có cổng nào được bật
    if (empty($buttons)) {
        $text .= "\n\n" . __('⚠️ Hiện chưa có phương thức nạp tiền nào được bật.');
    }

    $buttons[] = [
        TelegramShopBot::backButton('menu:main', __('🏠 Menu chính'))
    ];

    $keyboard = TelegramShopBot::inlineKeyboard($buttons);
    $bot->reply($text, $keyboard);
}

// =========================================================================
// NẠP QUA NGÂN HÀNG
// =========================================================================

/**
 * Hiển thị danh sách ngân hàng hoặc trực tiếp thông tin nếu chỉ có 1 bank
 * Bank không tạo đơn — chỉ hiển thị STK + nội dung CK + QR, hệ thống tự cộng tiền
 * 
 * Nếu cấu hình fullname_transfer mà user chưa có prefix_fullname:
 * → Tự động tạo từ tên Telegram (bỏ dấu, ghi hoa)
 * → Nếu tên Telegram không hợp lệ → yêu cầu user lên web nhập
 */
function tgshop_recharge_bank($bot)
{
    // Kiểm tra và xử lý fullname_transfer trước khi hiển thị bank
    if (!tgshop_ensure_prefix_fullname($bot)) {
        return;
    }

    // Lấy danh sách ngân hàng đang bật
    $banks = $bot->CMSNT->get_list_safe("SELECT * FROM `banks` WHERE `status` = 1", []);

    if (empty($banks)) {
        $bot->reply(__('⚠️ Chưa có ngân hàng nào được cấu hình.'));
        return;
    }

    // Nếu chỉ có 1 bank → hiển thị trực tiếp thông tin
    if (count($banks) == 1) {
        tgshop_recharge_bank_detail($bot, $banks[0]['id']);
        return;
    }

    // Nhiều bank → hiển thị danh sách nút chọn
    $text  = __('🏦 <b>Nạp tiền qua Ngân hàng</b>') . "\n\n";
    $text .= __('Chọn ngân hàng bạn muốn chuyển khoản:');

    $buttons = [];
    foreach ($banks as $bank) {
        $buttons[] = [
            ['text' => '🏛 ' . $bank['short_name'] . ' - ' . $bank['accountName'], 'data' => 'recharge:bank_detail:' . $bank['id']]
        ];
    }

    $buttons[] = [
        TelegramShopBot::backButton('recharge:methods', __('⬅️ Chọn cổng khác'))
    ];

    $keyboard = TelegramShopBot::inlineKeyboard($buttons);
    $bot->reply($text, $keyboard);
}

/**
 * Kiểm tra user đã có prefix_fullname chưa (khi bank_recharge_type == fullname_transfer)
 * 
 * Trên web: popup modal bắt buộc nhập họ tên trước khi xem STK
 * Trên Telegram: gửi prompt yêu cầu user gõ họ tên trực tiếp trong chat
 * 
 * Không cần cột state riêng — dựa vào prefix_fullname == NULL để biết đang chờ nhập.
 * processMessage() trong TelegramShopBot sẽ check isWaitingFullname() và route text
 * đến recharge:process_fullname thay vì về menu.
 * 
 * @return bool true nếu OK (đã có fullname hoặc không yêu cầu), false nếu đang chờ nhập
 */
function tgshop_ensure_prefix_fullname($bot)
{
    $bankRechargeType = $bot->CMSNT->site('bank_recharge_type');

    // Không phải kiểu fullname → luôn OK
    if ($bankRechargeType != 'fullname_transfer') {
        return true;
    }

    // Đã có prefix_fullname → OK
    if (!empty($bot->user['prefix_fullname'])) {
        return true;
    }

    // Chưa có → gửi prompt bắt user gõ họ tên
    $text  = __('✏️ <b>Nhập Họ và Tên</b>') . "\n\n";
    $text .= __('Để nạp tiền qua ngân hàng, bạn cần nhập họ và tên thật dùng làm nội dung chuyển khoản.') . "\n\n";
    $text .= __('📌 <b>Yêu cầu:</b>') . "\n";
    $text .= __('• Nhập đầy đủ họ và tên (ít nhất 2 từ)') . "\n";
    $text .= __('• Chỉ chữ cái, không số hay ký tự đặc biệt') . "\n";
    $text .= __('• Ví dụ: <code>Nguyen Van A</code>') . "\n\n";
    $text .= __('👇 Hãy gõ họ và tên của bạn:');

    $keyboard = TelegramShopBot::inlineKeyboard([
        [
            TelegramShopBot::backButton('recharge:methods', __('⬅️ Quay lại'))
        ]
    ]);

    $bot->sendMessage($text, $keyboard);
    return false;
}

/**
 * Xử lý text input khi user gõ họ tên
 * Validate theo cùng logic với ajaxs/client/auth.php SavePrefixFullname:
 * - Bỏ dấu tiếng Việt, ghi hoa
 * - Chỉ chấp nhận A-Z + khoảng trắng, 2-5 từ
 * 
 * Không cần state riêng — processMessage check isWaitingFullname() (prefix_fullname == NULL)
 * nên miễn là chưa lưu thành công, mọi text user gõ đều vào đây
 * 
 * @param TelegramShopBot $bot Bot instance
 * @param string $text Họ tên user gõ vào
 */
function tgshop_recharge_process_fullname($bot, $text)
{
    // Chuẩn hóa: bỏ dấu + ghi hoa — cùng logic SavePrefixFullname trên web
    $fullname = removeVietnameseAccents($text);
    $fullname = mb_strtoupper($fullname, 'UTF-8');
    $fullname = preg_replace('/\s+/', ' ', trim($fullname));

    // Validate: chữ A-Z + khoảng trắng, ít nhất 2 từ, tối đa 5 từ
    // Cho phép số ở cuối từ cuối cùng (VD: NGUYEN VAN A1) để xử lý trùng tên
    if (!preg_match('/^[A-Z]+(\s[A-Z]+){1,4}\d*$/', $fullname)) {
        // Tên không hợp lệ → thông báo lỗi, user gõ lại sẽ tự động vào đây
        // (vì prefix_fullname vẫn NULL → isWaitingFullname() vẫn true)
        $text  = __('❌ <b>Họ tên không hợp lệ</b>') . "\n\n";
        $text .= sprintf(__('Bạn nhập: <code>%s</code>'), htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8')) . "\n\n";
        $text .= __('📌 <b>Yêu cầu:</b>') . "\n";
        $text .= __('• Ít nhất 2 từ, tối đa 5 từ') . "\n";
        $text .= __('• Chỉ chữ cái (A-Z), không số hay ký tự đặc biệt') . "\n";
        $text .= __('• Ví dụ: <code>Nguyen Van A</code>') . "\n\n";
        $text .= __('👇 Vui lòng nhập lại:');

        $keyboard = TelegramShopBot::inlineKeyboard([
            [
                TelegramShopBot::backButton('recharge:methods', __('⬅️ Quay lại'))
            ]
        ]);

        $bot->sendMessage($text, $keyboard);
        return;
    }

    // Kiểm tra trùng họ tên với user khác trong hệ thống
    $duplicate = $bot->CMSNT->get_row_safe(
        "SELECT `id` FROM `users` WHERE `prefix_fullname` = ? AND `id` != ?",
        [$fullname, $bot->user['id']]
    );
    if ($duplicate) {
        $text  = __('❌ <b>Họ tên đã được sử dụng</b>') . "\n\n";
        $text .= sprintf(__('Tên <code>%s</code> đã có người dùng khác đăng ký.'), $fullname) . "\n\n";
        $text .= __('Vui lòng nhập lại bằng cách thêm số vào sau tên.') . "\n";
        $text .= sprintf(__('Ví dụ: <code>%s1</code> hoặc <code>%s2</code>'), $fullname, $fullname) . "\n\n";
        $text .= __('👇 Nhập lại họ và tên:');

        $keyboard = TelegramShopBot::inlineKeyboard([
            [
                TelegramShopBot::backButton('recharge:methods', __('⬅️ Quay lại'))
            ]
        ]);

        $bot->sendMessage($text, $keyboard);
        return;
    }

    // Hợp lệ + không trùng → lưu vào DB
    $bot->CMSNT->update("users", [
        'prefix_fullname' => $fullname
    ], " `id` = ? ", [$bot->user['id']]);

    // Cập nhật user object trong bộ nhớ
    $bot->user['prefix_fullname'] = $fullname;

    // Ghi log
    $bot->CMSNT->insert("logs", [
        'user_id'    => $bot->user['id'],
        'ip'         => '0.0.0.0',
        'device'     => 'Telegram Bot',
        'createdate' => gettime(),
        'action'     => sprintf(__('Cập nhật họ tên chuyển khoản qua Telegram: %s'), $fullname)
    ]);

    // Thông báo thành công + tự động chuyển sang danh sách bank
    $bot->sendMessage(sprintf(__('✅ Đã lưu họ tên: <b>%s</b>'), $fullname));

    // Tiếp tục flow nạp ngân hàng
    tgshop_recharge_bank($bot);
}

/**
 * Hiển thị chi tiết thông tin chuyển khoản cho 1 ngân hàng cụ thể
 * Bao gồm: STK, chủ TK, nội dung CK, và QR code
 * 
 * Gửi QR trước (nếu có) rồi gửi text + buttons riêng.
 * Tách riêng để đảm bảo nút bấm luôn hoạt động ngay cả khi ảnh QR lỗi
 * (Telegram server fetch ảnh từ VietQR có thể timeout/block).
 */
function tgshop_recharge_bank_detail($bot, $bank_id)
{
    $bank = $bot->CMSNT->get_row_safe("SELECT * FROM `banks` WHERE `id` = ? AND `status` = 1", [$bank_id]);

    if (!$bank) {
        $bot->reply(__('❌ Ngân hàng không tồn tại.'));
        return;
    }

    // Tính nội dung chuyển khoản — cùng logic với views/client/recharge-bank.php
    $transferContent = tgshop_generate_transfer_content($bot);

    // Gửi ảnh QR trước (nếu lỗi thì bỏ qua, không ảnh hưởng flow)
    $qr_url = tgshop_generate_bank_qr($bank, $transferContent);
    if ($qr_url) {
        $bot->sendPhoto($qr_url);
    }

    // Gửi text thông tin + buttons riêng — luôn hoạt động dù QR lỗi
    $text  = __('🏦 <b>Thông tin chuyển khoản</b>') . "\n\n";
    $text .= sprintf(__('🏛 <b>Ngân hàng:</b> %s'), $bank['short_name']) . "\n";
    $text .= sprintf(__('👤 <b>Chủ TK:</b> %s'), $bank['accountName']) . "\n";
    $text .= sprintf(__('💳 <b>Số TK:</b> <code>%s</code>'), $bank['accountNumber']) . "\n\n";
    $text .= sprintf(__('📝 <b>Nội dung CK:</b> <code>%s</code>'), $transferContent) . "\n\n";

    // Hiển thị giới hạn nạp để user không nạp nhầm ngoài khoảng cho phép
    $bank_min = $bot->CMSNT->site('bank_min');
    $bank_max = $bot->CMSNT->site('bank_max');
    if ($bank_min > 0 || $bank_max > 0) {
        $text .= sprintf(__('📏 <b>Giới hạn nạp:</b> %s - %s'), format_currency($bank_min), format_currency($bank_max)) . "\n\n";
    }

    $text .= __('⚠️ Vui lòng chuyển khoản đúng nội dung để hệ thống tự động cộng tiền.');

    $buttons = [];

    // Nếu có nhiều bank → thêm nút chọn bank khác
    $bank_count = $bot->CMSNT->num_rows_safe("SELECT * FROM `banks` WHERE `status` = 1", []);
    if ($bank_count > 1) {
        $buttons[] = [
            TelegramShopBot::backButton('recharge:bank', __('⬅️ Chọn ngân hàng khác'))
        ];
    }

    $buttons[] = [
        TelegramShopBot::backButton('recharge:methods', __('⬅️ Chọn cổng khác')),
        TelegramShopBot::backButton('menu:main', __('🏠 Menu chính'))
    ];

    $keyboard = TelegramShopBot::inlineKeyboard($buttons);
    $bot->sendMessage($text, $keyboard);
}

/**
 * Sinh nội dung chuyển khoản theo cấu hình hệ thống
 * Đồng bộ logic với views/client/recharge-bank.php
 * 
 * @return string Nội dung CK user cần điền khi chuyển khoản
 */
function tgshop_generate_transfer_content($bot)
{
    $bankRechargeType = $bot->CMSNT->site('bank_recharge_type');
    $prefix = $bot->CMSNT->site('prefix_autobank');

    // Kiểu họ tên: prefix_fullname + prefix_autobank
    if ($bankRechargeType == 'fullname_transfer' && !empty($bot->user['prefix_fullname'])) {
        return $bot->user['prefix_fullname'] . ' ' . $prefix;
    }

    // Kiểu mặc định: prefix_autobank + user_id
    return $prefix . $bot->user['id'];
}

/**
 * Tạo URL ảnh QR cho ngân hàng (VietQR hoặc Web2M cho MOMO)
 * 
 * @return string|null URL ảnh QR, null nếu không tạo được
 */
function tgshop_generate_bank_qr($bank, $transferContent)
{
    $encodedContent = rawurlencode($transferContent);

    if ($bank['short_name'] == 'MOMO') {
        return "https://api.web2m.com/api/qrmomo.php?amount=0&phone=" . $bank['accountNumber'] . "&noidung=" . $encodedContent . "&size=300";
    }

    // Dùng img.vietqr.io (endpoint ổn định hơn api.vietqr.io cho server-side fetch)
    // Format query-param thay vì path-based để tránh lỗi encoding
    return "https://img.vietqr.io/image/" . $bank['short_name'] . "-" . $bank['accountNumber'] . "-compact2.jpg?amount=0&addInfo=" . $encodedContent . "&accountName=" . rawurlencode($bank['accountName']);
}

// =========================================================================
// NẠP QUA CRYPTO (USDT - FPAYMENT)
// =========================================================================

/**
 * Hiển thị form chọn mệnh giá USDT
 * Tỷ giá lấy từ crypto_rate, giới hạn từ crypto_min/crypto_max
 */
function tgshop_recharge_crypto($bot)
{
    $crypto_rate = $bot->CMSNT->site('crypto_rate');
    $crypto_min = $bot->CMSNT->site('crypto_min');
    $crypto_max = $bot->CMSNT->site('crypto_max');

    $text  = __('💎 <b>Nạp tiền Crypto (USDT TRC20)</b>') . "\n\n";
    $text .= sprintf(__('💱 <b>Tỷ giá:</b> 1 USDT = %s'), format_currency($crypto_rate)) . "\n";
    $text .= sprintf(__('📏 <b>Giới hạn:</b> %s - %s USDT'), $crypto_min, $crypto_max) . "\n\n";

    // Hiển thị bảng khuyến mãi nếu có
    $promoConfig = $bot->CMSNT->site('crypto_promotions');
    $promotions = parseCryptoPromotionsConfig($promoConfig);
    if (!empty($promotions)) {
        $text .= __('🎁 <b>Khuyến mãi:</b>') . "\n";
        foreach ($promotions as $promo) {
            $text .= sprintf(__('   • Nạp từ %s USDT: +%s%%'), $promo['min'], $promo['discount']) . "\n";
        }
        $text .= "\n";
    }

    $text .= __('Chọn số USDT muốn nạp:');

    // Mệnh giá preset dựa trên min/max
    $presets = [5, 10, 20, 50, 100, 200];
    $valid_presets = array_filter($presets, function ($p) use ($crypto_min, $crypto_max) {
        return $p >= $crypto_min && $p <= $crypto_max;
    });

    $buttons = [];
    $row = [];
    $count = 0;
    foreach ($valid_presets as $amount) {
        $row[] = ['text' => $amount . ' USDT', 'data' => 'recharge:crypto_confirm:' . $amount];
        $count++;
        if ($count % 3 == 0) {
            $buttons[] = $row;
            $row = [];
        }
    }
    if (!empty($row)) {
        $buttons[] = $row;
    }

    // Nút nhập số tiền tùy ý
    $buttons[] = [
        ['text' => __('✏️ Nhập số khác'), 'data' => 'recharge:crypto_input']
    ];

    $buttons[] = [
        TelegramShopBot::backButton('recharge:methods', __('⬅️ Chọn cổng khác')),
        TelegramShopBot::backButton('menu:main', __('🏠 Menu chính'))
    ];

    $keyboard = TelegramShopBot::inlineKeyboard($buttons);
    $bot->reply($text, $keyboard);
}

/**
 * Hiện prompt nhập số tiền USDT tùy ý
 * Set state để processMessage route text số tiền vào crypto_custom
 */
function tgshop_recharge_crypto_input($bot)
{
    $crypto_min = $bot->CMSNT->site('crypto_min');
    $crypto_max = $bot->CMSNT->site('crypto_max');

    // Set state: khi user gõ text tiếp theo → route đến recharge:crypto_custom:{text}
    $bot->setState('recharge:crypto_custom');

    $text  = __('✏️ <b>Nhập số USDT</b>') . "\n\n";
    $text .= sprintf(__('📏 <b>Giới hạn:</b> %s - %s USDT'), $crypto_min, $crypto_max) . "\n\n";
    $text .= __('👇 Hãy gõ số USDT muốn nạp (VD: <code>15</code> hoặc <code>75.5</code>):');

    $keyboard = TelegramShopBot::inlineKeyboard([
        [
            TelegramShopBot::backButton('recharge:crypto', __('⬅️ Quay lại'))
        ]
    ]);

    $bot->reply($text, $keyboard);
}

/**
 * Xử lý text input khi user gõ số tiền USDT tùy ý
 * Validate rồi chuyển sang crypto_confirm
 * 
 * @param TelegramShopBot $bot Bot instance
 * @param string $amount_text Số tiền user gõ vào
 */
function tgshop_recharge_crypto_custom($bot, $amount_text)
{
    // Cho phép cả dấu . và , làm phân cách thập phân
    $amount_text = str_replace(',', '.', trim($amount_text));
    $amount = floatval($amount_text);

    if ($amount <= 0) {
        $bot->sendMessage(__('❌ Vui lòng nhập một số hợp lệ (VD: <code>15</code> hoặc <code>75.5</code>).'));
        // Set lại state để user nhập lại
        $bot->setState('recharge:crypto_custom');
        return;
    }

    // Chuyển sang confirm (hàm này tự validate min/max)
    tgshop_recharge_crypto_confirm($bot, $amount);
}

/**
 * Xác nhận và tạo hóa đơn nạp crypto qua FPAYMENT
 * Gọi cùng API và logic như ajaxs/client/create.php (RechargeCryptoNew)
 */
function tgshop_recharge_crypto_confirm($bot, $amount)
{
    // Validate cơ bản
    if ($amount <= 0) {
        $bot->reply(__('❌ Số tiền không hợp lệ.'));
        return;
    }

    $crypto_min = $bot->CMSNT->site('crypto_min');
    $crypto_max = $bot->CMSNT->site('crypto_max');

    if ($amount < $crypto_min || $amount > $crypto_max) {
        $text = sprintf(__('❌ Số tiền phải từ %s đến %s USDT. Bạn nhập: %s USDT.'), $crypto_min, $crypto_max, $amount);
        $keyboard = TelegramShopBot::inlineKeyboard([
            [
                TelegramShopBot::backButton('recharge:crypto_input', __('✏️ Nhập lại')),
                TelegramShopBot::backButton('recharge:crypto', __('⬅️ Chọn mệnh giá'))
            ]
        ]);
        $bot->sendMessage($text, $keyboard);
        return;
    }

    // Kiểm tra cấu hình FPAYMENT
    $merchant_id = $bot->CMSNT->site('crypto_merchant_id');
    $api_key = $bot->CMSNT->site('crypto_api_key');
    if (empty($merchant_id) || empty($api_key)) {
        $bot->reply(__('❌ Chức năng này chưa được cấu hình. Vui lòng liên hệ Admin.'));
        return;
    }

    // Chống spam: tối đa 3 đơn waiting cùng mệnh giá
    $pending_count = $bot->CMSNT->num_rows_safe(
        "SELECT * FROM `payment_crypto` WHERE `user_id` = ? AND `status` = 'waiting' AND ROUND(`amount`) = ?",
        [$bot->user['id'], $amount]
    );
    if ($pending_count >= 3) {
        $bot->reply(__('⚠️ Bạn đã có quá nhiều hóa đơn đang chờ. Vui lòng thanh toán hoặc chờ hết hạn.'));
        return;
    }

    // Gọi API FPAYMENT tạo hóa đơn
    $request_id = md5(time() . random('qwertyuiopasdfghjklzxcvbnm0123456789', 5));

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL            => "https://app.fpayment.net/api/AddInvoice",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => [
            'merchant_id'  => $merchant_id,
            'api_key'      => $api_key,
            'name'         => 'Recharge via Telegram',
            'description'  => 'Recharge invoice to ' . $bot->user['username'],
            'amount'       => $amount,
            'request_id'   => $request_id,
            'callback_url' => base_url('api/callback_crypto_new.php'),
            'success_url'  => !empty($bot->getBotUsername()) ? 'https://t.me/' . $bot->getBotUsername() : base_url('client/recharge-crypto'),
            'cancel_url'   => !empty($bot->getBotUsername()) ? 'https://t.me/' . $bot->getBotUsername() : base_url('client/recharge-crypto')
        ],
    ]);
    $response = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);

    if ($error) {
        $bot->logDebug('CRYPTO_CURL_ERROR', $error);
        $bot->reply(__('❌ Có lỗi kết nối đến cổng thanh toán. Vui lòng thử lại sau.'));
        return;
    }

    $result = json_decode($response, true);
    if (!isset($result['status']) || $result['status'] != 'success') {
        $error_msg = isset($result['msg']) ? $result['msg'] : 'Unknown error';
        $bot->logDebug('CRYPTO_API_ERROR', $error_msg);
        $bot->reply(__('❌ Tạo hóa đơn thất bại. Vui lòng thử lại sau.'));
        return;
    }

    // Lưu thông tin hóa đơn vào DB
    $trans_id    = $result['data']['trans_id'];
    $real_amount = floatval($result['data']['amount']);
    $status      = $result['data']['status'];
    $url_payment = $result['data']['url_payment'];

    // Tính số tiền thực nhận (đã tính khuyến mãi + tỷ giá)
    $received = calculateCryptoReceivedAmount($real_amount, $bot->CMSNT->site('crypto_promotions'));
    $received = $received * $bot->CMSNT->site('crypto_rate');

    $bot->CMSNT->insert('payment_crypto', [
        'trans_id'       => $trans_id,
        'user_id'        => $bot->user['id'],
        'request_id'     => $request_id,
        'amount'         => $real_amount,
        'received'       => $received,
        'exchange_rate'  => $bot->CMSNT->site('crypto_rate'), // Lưu tỷ giá USDT tại thời điểm tạo hóa đơn
        'create_gettime' => gettime(),
        'update_gettime' => gettime(),
        'status'         => $status,
        'url_payment'    => $url_payment,
        'msg'            => null
    ]);

    // Ghi log
    $bot->CMSNT->insert("logs", [
        'user_id'    => $bot->user['id'],
        'ip'         => '0.0.0.0',
        'device'     => 'Telegram Bot',
        'createdate' => gettime(),
        'action'     => sprintf(__('Tạo hóa đơn nạp Crypto qua Telegram #%s'), $trans_id)
    ]);

    // Hiển thị kết quả cho user
    $text  = __('✅ <b>Đã tạo hóa đơn nạp Crypto</b>') . "\n\n";
    $text .= sprintf(__('💎 <b>Số lượng:</b> %s USDT'), $real_amount) . "\n";
    $text .= sprintf(__('💰 <b>Thực nhận:</b> %s'), format_currency($received)) . "\n";
    $text .= sprintf(__('🔗 <b>Mã GD:</b> <code>%s</code>'), $trans_id) . "\n\n";
    $text .= __('Nhấn nút bên dưới để thanh toán:');

    $keyboard = TelegramShopBot::inlineKeyboard([
        [
            ['text' => __('💳 Thanh toán ngay'), 'web_app' => $url_payment]
        ],
        [
            TelegramShopBot::backButton('recharge:crypto', __('⬅️ Quay lại')),
            TelegramShopBot::backButton('menu:main', __('🏠 Menu chính'))
        ]
    ]);

    $bot->sendMessage($text, $keyboard);
}

// =========================================================================
// NẠP QUA TRIPAY
// =========================================================================

/**
 * Hiển thị danh sách kênh thanh toán Tripay
 * Các kênh phổ biến: QRIS, DANA, OVO, BRIVA, BNIVA, ALFAMART, INDOMARET
 */
function tgshop_recharge_tripay($bot)
{
    $tripay_name = $bot->CMSNT->site('tripay_name');
    if (empty($tripay_name)) {
        $tripay_name = 'TriPay';
    }
    $tripay_rate = $bot->CMSNT->site('tripay_rate');
    $tripay_min = $bot->CMSNT->site('tripay_min');
    $tripay_max = $bot->CMSNT->site('tripay_max');

    $text  = sprintf(__('💳 <b>Nạp tiền qua %s</b>'), $tripay_name) . "\n\n";
    $text .= sprintf(__('💱 <b>Tỷ giá:</b> %s / IDR'), format_currency($tripay_rate)) . "\n";
    $text .= sprintf(__('📏 <b>Giới hạn:</b> %s - %s IDR'), number_format($tripay_min), number_format($tripay_max)) . "\n\n";
    $text .= __('Chọn kênh thanh toán:');

    // Các kênh phổ biến nhất — nhóm theo loại
    $keyboard = TelegramShopBot::inlineKeyboard([
        // E-Wallet
        [
            ['text' => 'QRIS', 'data' => 'recharge:tripay_channel:QRIS'],
            ['text' => 'DANA', 'data' => 'recharge:tripay_channel:DANA'],
            ['text' => 'OVO', 'data' => 'recharge:tripay_channel:OVO']
        ],
        // Virtual Account
        [
            ['text' => 'BRI VA', 'data' => 'recharge:tripay_channel:BRIVA'],
            ['text' => 'BNI VA', 'data' => 'recharge:tripay_channel:BNIVA']
        ],
        // Retail
        [
            ['text' => 'Alfamart', 'data' => 'recharge:tripay_channel:ALFAMART'],
            ['text' => 'Indomaret', 'data' => 'recharge:tripay_channel:INDOMARET']
        ],
        [
            TelegramShopBot::backButton('recharge:methods', __('⬅️ Chọn cổng khác')),
            TelegramShopBot::backButton('menu:main', __('🏠 Menu chính'))
        ]
    ]);

    $bot->reply($text, $keyboard);
}

/**
 * Chọn mệnh giá cho kênh Tripay cụ thể
 * Hiển thị preset số tiền IDR phổ biến
 */
function tgshop_recharge_tripay_channel($bot, $channel)
{
    if (empty($channel)) {
        $bot->reply(__('❌ Kênh thanh toán không hợp lệ.'));
        return;
    }

    // Sử dụng thư viện tripay để lấy tên hiển thị
    require_once(__DIR__ . '/../../../libs/tripay.php');
    $channel_name = tripayGetMethodName($channel);

    $tripay_min = $bot->CMSNT->site('tripay_min');
    $tripay_max = $bot->CMSNT->site('tripay_max');
    $tripay_rate = $bot->CMSNT->site('tripay_rate');

    $text  = sprintf(__('💳 <b>Kênh: %s</b>'), $channel_name) . "\n\n";
    $text .= sprintf(__('📏 <b>Giới hạn:</b> %s - %s IDR'), number_format($tripay_min), number_format($tripay_max)) . "\n";
    $text .= sprintf(__('💱 <b>Tỷ giá:</b> %s / IDR'), format_currency($tripay_rate)) . "\n\n";
    $text .= __('Chọn số tiền nạp (IDR):');

    // Mệnh giá preset IDR phổ biến
    $presets = [50000, 100000, 200000, 500000, 1000000];
    $valid_presets = array_filter($presets, function ($p) use ($tripay_min, $tripay_max) {
        return $p >= $tripay_min && $p <= $tripay_max;
    });

    $buttons = [];
    $row = [];
    $count = 0;
    foreach ($valid_presets as $amt) {
        // Hiển thị đẹp: 50K, 100K, 200K, 500K, 1M
        $label = $amt >= 1000000 ? ($amt / 1000000) . 'M' : ($amt / 1000) . 'K';
        $label .= ' IDR';

        $row[] = ['text' => $label, 'data' => 'recharge:tripay_confirm:' . $channel . ':' . $amt];
        $count++;
        if ($count % 3 == 0) {
            $buttons[] = $row;
            $row = [];
        }
    }
    if (!empty($row)) {
        $buttons[] = $row;
    }

    $buttons[] = [
        TelegramShopBot::backButton('recharge:tripay', __('⬅️ Chọn kênh khác')),
        TelegramShopBot::backButton('menu:main', __('🏠 Menu chính'))
    ];

    $keyboard = TelegramShopBot::inlineKeyboard($buttons);
    $bot->reply($text, $keyboard);
}

/**
 * Xác nhận và tạo hóa đơn Tripay
 * Gọi cùng API và logic như ajaxs/client/create.php (RechargeTripay)
 */
function tgshop_recharge_tripay_confirm($bot, $channel, $amount)
{
    // Validate
    if (empty($channel) || $amount <= 0) {
        $bot->reply(__('❌ Thông tin không hợp lệ.'));
        return;
    }

    $tripay_min = $bot->CMSNT->site('tripay_min');
    $tripay_max = $bot->CMSNT->site('tripay_max');

    if ($amount < $tripay_min) {
        $bot->reply(sprintf(__('❌ Số tiền tối thiểu là %s IDR.'), number_format($tripay_min)));
        return;
    }
    if ($amount > $tripay_max) {
        $bot->reply(sprintf(__('❌ Số tiền tối đa là %s IDR.'), number_format($tripay_max)));
        return;
    }

    // Kiểm tra cấu hình Tripay
    $api_key = $bot->CMSNT->site('tripay_api_key');
    $private_key = $bot->CMSNT->site('tripay_private_key');
    $merchant_code = $bot->CMSNT->site('tripay_merchant_code');

    if (empty($api_key) || empty($private_key) || empty($merchant_code)) {
        $bot->reply(__('❌ Chức năng này chưa được cấu hình. Vui lòng liên hệ Admin.'));
        return;
    }

    require_once(__DIR__ . '/../../../libs/tripay.php');

    $trans_id = random('QWERTYUIOPASDFGHJKLZXCVBNM', 3) . time();
    $price = $amount * $bot->CMSNT->site('tripay_rate');

    // Xử lý email cho Tripay API (bắt buộc có email hợp lệ)
    $email = $bot->user['email'];
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strpos($email, '@telegram.local') !== false) {
        $email = $bot->user['username'] . '@example.com';
    }

    $tripayConfig = [
        'api_key'       => $api_key,
        'private_key'   => $private_key,
        'merchant_code' => $merchant_code,
        'sandbox'       => $bot->CMSNT->site('tripay_sandbox') == 1
    ];

    $params = [
        'method'         => $channel,
        'merchant_ref'   => $trans_id,
        'amount'         => (int)$amount,
        'customer_name'  => $bot->user['username'],
        'customer_email' => $email,
        'order_items'    => [
            [
                'sku'      => 'TOPUP',
                'name'     => 'Deposit ' . $bot->user['username'],
                'price'    => (int)$amount,
                'quantity' => 1,
                'subtotal' => (int)$amount
            ]
        ],
        'callback_url'   => base_url('api/callback_tripay.php'),
        'return_url'     => !empty($bot->getBotUsername()) ? 'https://t.me/' . $bot->getBotUsername() : base_url('?action=recharge-tripay'),
        'expired_time'   => time() + (24 * 60 * 60)
    ];

    $response = tripayCreateTransaction($tripayConfig, $params);

    if (!$response || !isset($response['success']) || $response['success'] !== true) {
        $error_msg = isset($response['message']) ? $response['message'] : 'Unknown error';
        $bot->logDebug('TRIPAY_API_ERROR', $error_msg);
        $bot->reply(__('❌ Tạo hóa đơn thất bại. Vui lòng thử lại sau.'));
        return;
    }

    $checkout_url = isset($response['data']['checkout_url']) ? $response['data']['checkout_url'] : '';
    $reference = isset($response['data']['reference']) ? $response['data']['reference'] : '';

    // Lưu vào DB
    $bot->CMSNT->insert('payment_tripay', [
        'user_id'      => $bot->user['id'],
        'trans_id'     => $trans_id,
        'reference'    => $reference,
        'method'       => $channel,
        'price'        => $price,
        'amount'       => $amount,
        'status'       => 0,
        'created_at'   => gettime(),
        'updated_at'   => gettime(),
        'checkout_url' => $checkout_url,
        'notication'   => 0
    ]);

    // Rate limit
    $bot->CMSNT->update("users", [
        'time_request' => time()
    ], " `id` = ?", [$bot->user['id']]);

    // Ghi log
    $channel_name = tripayGetMethodName($channel);
    $bot->CMSNT->insert("logs", [
        'user_id'    => $bot->user['id'],
        'ip'         => '0.0.0.0',
        'device'     => 'Telegram Bot',
        'createdate' => gettime(),
        'action'     => sprintf(__('Tạo hóa đơn nạp %s qua Telegram #%s'), $channel_name, $trans_id)
    ]);

    // Hiển thị kết quả
    $text  = __('✅ <b>Đã tạo hóa đơn nạp tiền</b>') . "\n\n";
    $text .= sprintf(__('💳 <b>Kênh:</b> %s'), $channel_name) . "\n";
    $text .= sprintf(__('💵 <b>Số tiền:</b> %s IDR'), number_format($amount)) . "\n";
    $text .= sprintf(__('💰 <b>Thực nhận:</b> %s'), format_currency($price)) . "\n";
    $text .= sprintf(__('🔗 <b>Mã GD:</b> <code>%s</code>'), $trans_id) . "\n\n";
    $text .= __('Nhấn nút bên dưới để thanh toán:');

    $keyboard = TelegramShopBot::inlineKeyboard([
        [
            ['text' => __('💳 Thanh toán ngay'), 'web_app' => $checkout_url]
        ],
        [
            TelegramShopBot::backButton('recharge:tripay', __('⬅️ Quay lại')),
            TelegramShopBot::backButton('menu:main', __('🏠 Menu chính'))
        ]
    ]);

    $bot->sendMessage($text, $keyboard);
}
