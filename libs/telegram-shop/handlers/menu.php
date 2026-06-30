<?php

/**
 * Handler: Menu chính
 * Prefix: "menu"
 * 
 * Callback data:
 * - menu:main → Hiển thị menu chính (trang chủ Bot)
 * 
 * Đây là handler mặc định, mọi tương tác đều quay về đây
 */
function tgshop_handle_menu($bot, $params)
{
    $action = isset($params[0]) ? $params[0] : 'main';

    switch ($action) {
        case 'main':
            tgshop_menu_main($bot);
            break;

        default:
            tgshop_menu_main($bot);
            break;
    }
}

/**
 * Hiển thị menu chính với các nút chức năng
 * Đây là trang chủ của Bot - nơi user tiếp cận mọi chức năng
 */
function tgshop_menu_main($bot)
{
    $site_title = $bot->CMSNT->site('title');
    $balance = $bot->getFormattedBalance();

    // Dùng chuỗi dịch cố định + placeholder để hệ thống đa ngôn ngữ map đúng key.
    $text  = sprintf(__('🏪 <b>%s</b>'), $site_title) . "\n\n";
    $text .= sprintf(__('👤 <b>Tài khoản:</b> <code>%s</code>'), $bot->user['username']) . "\n";
    $text .= sprintf(__('💰 <b>Số dư:</b> <code>%s</code>'), $balance) . "\n\n";
    $text .= __('Chọn chức năng bên dưới:');

    // Layout nút bấm - dễ dàng thêm/bớt nút khi mở rộng chức năng
    $keyboard = TelegramShopBot::inlineKeyboard([
        // Hàng 1: Mua sắm - 2 lối vào chính
        [
            ['text' => __('📂 Danh mục sản phẩm'), 'data' => 'shop:categories'],
            ['text' => __('🛒 Tất cả sản phẩm'), 'data' => 'shop:products:0:1']
        ],
        // Hàng 2: Tìm kiếm sản phẩm nhanh theo tên
        [
            ['text' => __('🔍 Tìm kiếm sản phẩm'), 'data' => 'shop:search']
        ],
        // Hàng 3: Tài khoản & Tài chính
        [
            ['text' => __('👤 Tài khoản'), 'data' => 'account:info'],
            ['text' => __('💰 Nạp tiền'), 'data' => 'recharge:methods']
        ],
        // Hàng 4: Đơn hàng & Lịch sử
        [
            ['text' => __('📦 Đơn hàng'), 'data' => 'order:list'],
            ['text' => __('📋 Lịch sử giao dịch'), 'data' => 'account:history']
        ],
        // Hàng 5: Cài đặt ngôn ngữ & tiền tệ
        [
            ['text' => __('⚙️ Cài đặt'), 'data' => 'settings:menu']
        ]
    ]);

    $bot->reply($text, $keyboard);
}
