<?php

/**
 * Handler: Cài đặt ngôn ngữ & tiền tệ
 * Prefix: "settings"
 * 
 * Callback data:
 * - settings:menu             → Menu cài đặt chính
 * - settings:lang             → Danh sách ngôn ngữ để chọn
 * - settings:lang_set:{id}    → Lưu ngôn ngữ đã chọn
 * - settings:currency         → Danh sách tiền tệ để chọn
 * - settings:currency_set:{id}→ Lưu tiền tệ đã chọn
 * 
 * Bảo mật:
 * - Validate ID bằng intval + kiểm tra tồn tại trong DB trước khi lưu
 * - Chỉ hiển thị ngôn ngữ/tiền tệ đang bật (status=1 / display=1)
 */
function tgshop_handle_settings($bot, $params)
{
    $action = isset($params[0]) ? $params[0] : 'menu';

    switch ($action) {
        // Menu cài đặt chính
        case 'menu':
            tgshop_settings_menu($bot);
            break;

        // Danh sách ngôn ngữ
        case 'lang':
            tgshop_settings_lang_list($bot);
            break;

        // Lưu ngôn ngữ đã chọn (settings:lang_set:{id})
        case 'lang_set':
            $id = isset($params[1]) ? intval($params[1]) : 0;
            tgshop_settings_lang_set($bot, $id);
            break;

        // Danh sách tiền tệ
        case 'currency':
            tgshop_settings_currency_list($bot);
            break;

        // Lưu tiền tệ đã chọn (settings:currency_set:{id})
        case 'currency_set':
            $id = isset($params[1]) ? intval($params[1]) : 0;
            tgshop_settings_currency_set($bot, $id);
            break;

        default:
            tgshop_settings_menu($bot);
            break;
    }
}

/**
 * Menu cài đặt chính - hiển thị 2 lựa chọn: Ngôn ngữ và Tiền tệ
 */
function tgshop_settings_menu($bot)
{
    $text  = __('⚙️ <b>Cài đặt</b>') . "\n\n";
    $text .= __('Chọn mục cần thay đổi:');

    $keyboard = TelegramShopBot::inlineKeyboard([
        [
            ['text' => __('🌐 Ngôn ngữ'), 'data' => 'settings:lang'],
            ['text' => __('💱 Tiền tệ'), 'data' => 'settings:currency']
        ],
        [
            TelegramShopBot::backButton('menu:main')
        ]
    ]);

    $bot->reply($text, $keyboard);
}

/**
 * Hiển thị danh sách ngôn ngữ đang bật để user chọn
 * Ngôn ngữ hiện tại được đánh dấu ✅ để user biết đang dùng ngôn ngữ nào
 */
function tgshop_settings_lang_list($bot)
{
    // Query qua $bot->CMSNT (cùng DB instance với bot) + params [1] để dùng prepared statement
    // Cột stt có thể chưa tồn tại nếu install.php chưa chạy migration mới nhất
    $order_by = column_exists('languages', 'stt') ? "`stt` ASC, `id` ASC" : "`id` ASC";
    $languages = $bot->CMSNT->get_list_safe(
        "SELECT * FROM `languages` WHERE `status` = ? ORDER BY {$order_by}",
        [1]
    );

    if (empty($languages)) {
        $bot->reply(__('⚠️ Chưa có ngôn ngữ nào được cài đặt.'));
        return;
    }

    // Xác định ngôn ngữ hiện tại (từ DB user hoặc default)
    $current_lang = !empty($bot->user['telegram_lang']) ? $bot->user['telegram_lang'] : null;

    // Nếu user chưa chọn ngôn ngữ → dùng default của hệ thống
    if (empty($current_lang)) {
        $default = $bot->CMSNT->get_row("SELECT `lang` FROM `languages` WHERE `lang_default` = 1");
        $current_lang = $default ? $default['lang'] : '';
    }

    $text  = __('🌐 <b>Chọn ngôn ngữ</b>') . "\n\n";
    $text .= __('Nhấn vào ngôn ngữ bạn muốn sử dụng:');

    // Tạo nút cho từng ngôn ngữ (mỗi ngôn ngữ 1 hàng để tên hiển thị đầy đủ)
    $rows = [];
    foreach ($languages as $lang) {
        // Đánh dấu ✅ ngôn ngữ đang dùng (bảng languages dùng cột `lang` làm tên, VD: vi_VN)
        $label = ($lang['lang'] === $current_lang) ? '✅ ' . $lang['lang'] : $lang['lang'];
        $rows[] = [
            ['text' => $label, 'data' => 'settings:lang_set:' . $lang['id']]
        ];
    }

    // Nút quay lại
    $rows[] = [
        TelegramShopBot::backButton('settings:menu', __('⬅️ Cài đặt'))
    ];

    $keyboard = TelegramShopBot::inlineKeyboard($rows);
    $bot->reply($text, $keyboard);
}

/**
 * Lưu ngôn ngữ user đã chọn vào DB + inject lại $_COOKIE ngay lập tức
 * Bảo mật: validate ID + kiểm tra ngôn ngữ tồn tại và đang bật
 * 
 * @param TelegramShopBot $bot Instance Bot
 * @param int $id ID ngôn ngữ trong bảng languages
 */
function tgshop_settings_lang_set($bot, $id)
{
    // Bảo mật: validate ID phải là số nguyên dương
    if ($id <= 0) {
        $bot->reply(__('⚠️ Dữ liệu không hợp lệ.'));
        return;
    }

    // Kiểm tra ngôn ngữ tồn tại VÀ đang bật (tránh hacker gửi ID tùy ý)
    $lang = $bot->CMSNT->get_row_safe(
        "SELECT `id`, `lang` FROM `languages` WHERE `id` = ? AND `status` = 1",
        [$id]
    );
    if (!$lang) {
        $bot->reply(__('⚠️ Ngôn ngữ không tồn tại hoặc đã bị tắt.'));
        return;
    }

    // Lưu giá trị `lang` (VD: vi_VN, en_US) vào DB user
    $bot->CMSNT->update("users", [
        'telegram_lang' => $lang['lang']
    ], " `id` = ? ", [$bot->user['id']]);

    // Inject ngay vào $_COOKIE để các hàm __() trong response dưới đây đọc đúng ngôn ngữ mới
    $_COOKIE['language'] = $lang['lang'];

    // Cập nhật user cache trong bot
    $bot->user['telegram_lang'] = $lang['lang'];

    // Thông báo thành công bằng ngôn ngữ MỚI
    $text  = __('✅ <b>Đổi ngôn ngữ thành công!</b>') . "\n\n";
    $text .= sprintf(__('🌐 <b>Ngôn ngữ hiện tại:</b> %s'), $lang['lang']);

    $keyboard = TelegramShopBot::inlineKeyboard([
        [
            TelegramShopBot::backButton('settings:menu', __('⬅️ Cài đặt')),
            TelegramShopBot::backButton('menu:main', __('🏠 Menu chính'))
        ]
    ]);

    $bot->reply($text, $keyboard);
}

/**
 * Hiển thị danh sách tiền tệ đang bật để user chọn
 * Tiền tệ hiện tại được đánh dấu ✅
 */
function tgshop_settings_currency_list($bot)
{
    // Query qua $bot->CMSNT (cùng DB instance với bot) + params [1] để dùng prepared statement
    $currencies = $bot->CMSNT->get_list_safe(
        "SELECT * FROM `currencies` WHERE `display` = ? ORDER BY `id` ASC",
        [1]
    );

    if (empty($currencies)) {
        $bot->reply(__('⚠️ Chưa có tiền tệ nào được cài đặt.'));
        return;
    }

    // Xác định tiền tệ hiện tại (từ DB user hoặc default)
    $current_currency_id = !empty($bot->user['telegram_currency']) ? intval($bot->user['telegram_currency']) : 0;

    // Nếu user chưa chọn tiền tệ → dùng default của hệ thống
    if ($current_currency_id <= 0) {
        $default = $bot->CMSNT->get_row_safe("SELECT `id` FROM `currencies` WHERE `default_currency` = 1", []);
        $current_currency_id = $default ? intval($default['id']) : 0;
    }

    $text  = __('💱 <b>Chọn tiền tệ</b>') . "\n\n";
    $text .= __('Nhấn vào tiền tệ bạn muốn sử dụng:');

    // Tạo nút cho từng tiền tệ, hiển thị 2 nút mỗi hàng
    $buttons = [];
    foreach ($currencies as $currency) {
        // Hiển thị: code + tên (VD: "USD - US Dollar") hoặc symbol
        $display_name = $currency['code'];
        if (!empty($currency['name'])) {
            $display_name .= ' - ' . $currency['name'];
        }

        // Đánh dấu ✅ tiền tệ đang dùng
        if (intval($currency['id']) === $current_currency_id) {
            $display_name = '✅ ' . $display_name;
        }

        $buttons[] = ['text' => $display_name, 'data' => 'settings:currency_set:' . $currency['id']];
    }

    // Chia thành 2 nút mỗi hàng cho giao diện gọn
    $rows = [];
    for ($i = 0; $i < count($buttons); $i += 2) {
        $row = [$buttons[$i]];
        if (isset($buttons[$i + 1])) {
            $row[] = $buttons[$i + 1];
        }
        $rows[] = $row;
    }

    // Nút quay lại
    $rows[] = [
        TelegramShopBot::backButton('settings:menu', __('⬅️ Cài đặt'))
    ];

    $keyboard = TelegramShopBot::inlineKeyboard($rows);
    $bot->reply($text, $keyboard);
}

/**
 * Lưu tiền tệ user đã chọn vào DB + inject lại $_COOKIE ngay lập tức
 * Bảo mật: validate ID + kiểm tra tiền tệ tồn tại và đang bật
 * 
 * @param TelegramShopBot $bot Instance Bot
 * @param int $id ID tiền tệ trong bảng currencies
 */
function tgshop_settings_currency_set($bot, $id)
{
    // Bảo mật: validate ID phải là số nguyên dương
    if ($id <= 0) {
        $bot->reply(__('⚠️ Dữ liệu không hợp lệ.'));
        return;
    }

    // Kiểm tra tiền tệ tồn tại VÀ đang bật (tránh hacker gửi ID tùy ý)
    $currency = $bot->CMSNT->get_row_safe(
        "SELECT `id`, `code`, `name` FROM `currencies` WHERE `id` = ? AND `display` = 1",
        [$id]
    );
    if (!$currency) {
        $bot->reply(__('⚠️ Tiền tệ không tồn tại hoặc đã bị tắt.'));
        return;
    }

    // Lưu ID tiền tệ vào DB user
    $bot->CMSNT->update("users", [
        'telegram_currency' => intval($currency['id'])
    ], " `id` = ? ", [$bot->user['id']]);

    // Inject ngay vào $_COOKIE để format_currency() trong response đọc đúng tiền tệ mới
    $_COOKIE['currency'] = intval($currency['id']);

    // Cập nhật user cache trong bot
    $bot->user['telegram_currency'] = intval($currency['id']);

    // Hiển thị số dư theo tiền tệ mới để user thấy ngay sự thay đổi
    $new_balance = format_currency($bot->user['money']);

    // Tên hiển thị tiền tệ
    $display_name = $currency['code'];
    if (!empty($currency['name'])) {
        $display_name .= ' (' . $currency['name'] . ')';
    }

    // Thông báo thành công
    $text  = __('✅ <b>Đổi tiền tệ thành công!</b>') . "\n\n";
    $text .= sprintf(__('💱 <b>Tiền tệ hiện tại:</b> %s'), $display_name) . "\n";
    $text .= sprintf(__('💰 <b>Số dư:</b> %s'), $new_balance);

    $keyboard = TelegramShopBot::inlineKeyboard([
        [
            TelegramShopBot::backButton('settings:menu', __('⬅️ Cài đặt')),
            TelegramShopBot::backButton('menu:main', __('🏠 Menu chính'))
        ]
    ]);

    $bot->reply($text, $keyboard);
}
