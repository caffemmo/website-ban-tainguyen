<?php

/**
 * Handler: Mua sắm sản phẩm
 * Prefix: "shop"
 * 
 * Callback data:
 * - shop:categories                    → Danh sách chuyên mục cha (parent)
 * - shop:cat:{parent_id}               → Danh sách chuyên mục con trong parent
 * - shop:products:{cat_id}:{page}      → Danh sách sản phẩm trong chuyên mục (phân trang)
 * - shop:detail:{product_id}           → Chi tiết sản phẩm
 * - shop:buy:{product_id}              → Chọn số lượng mua
 * - shop:buy_input:{product_id}        → Prompt cho user nhập số lượng tùy ý
 * - shop:buy_custom:{product_id}       → User gõ text số lượng → route qua state
 * - shop:buy_confirm:{product_id}:{amount} → Màn xác nhận đơn trước khi trừ tiền
 * - shop:buy_do:{product_id}:{amount}  → Thực thi mua (gọi API buyProduct nội bộ)
 * - shop:search                        → Prompt nhập từ khoá tìm kiếm
 * - shop:search_do                     → Nhận text user gõ qua state, lưu keyword + hiển thị trang 1
 * - shop:search_page:{page}            → Phân trang kết quả tìm kiếm (keyword đọc từ users.telegram_search)
 */
function tgshop_handle_shop($bot, $params)
{
    $action = isset($params[0]) ? $params[0] : 'categories';

    switch ($action) {
        // Hiển thị danh sách chuyên mục cha
        case 'categories':
            tgshop_shop_categories($bot);
            break;

        // Hiển thị chuyên mục con trong 1 chuyên mục cha
        case 'cat':
            $parent_id = isset($params[1]) ? intval($params[1]) : 0;
            tgshop_shop_sub_categories($bot, $parent_id);
            break;

        // Hiển thị danh sách sản phẩm trong 1 chuyên mục
        case 'products':
            $cat_id = isset($params[1]) ? intval($params[1]) : 0;
            $page = isset($params[2]) ? intval($params[2]) : 1;
            tgshop_shop_products($bot, $cat_id, $page);
            break;

        // Chi tiết 1 sản phẩm cụ thể
        case 'detail':
            $product_id = isset($params[1]) ? intval($params[1]) : 0;
            tgshop_shop_product_detail($bot, $product_id);
            break;

        // Màn chọn số lượng mua
        case 'buy':
            $product_id = isset($params[1]) ? intval($params[1]) : 0;
            tgshop_shop_buy_quantity($bot, $product_id);
            break;

        // Prompt nhập số lượng tùy ý (set state chờ text)
        case 'buy_input':
            $product_id = isset($params[1]) ? intval($params[1]) : 0;
            tgshop_shop_buy_input($bot, $product_id);
            break;

        // User gõ text số lượng → processMessage route đến đây qua state
        // State: "shop:buy_custom:{product_id}" → user gõ "5" → params = [buy_custom, {product_id}, 5]
        case 'buy_custom':
            $product_id = isset($params[1]) ? intval($params[1]) : 0;
            $amount_text = isset($params[2]) ? $params[2] : '';
            tgshop_shop_buy_custom($bot, $product_id, $amount_text);
            break;

        // Màn xác nhận đơn trước khi trừ tiền
        case 'buy_confirm':
            $product_id = isset($params[1]) ? intval($params[1]) : 0;
            $amount = isset($params[2]) ? intval($params[2]) : 0;
            tgshop_shop_buy_confirm($bot, $product_id, $amount);
            break;

        // Thực thi mua
        case 'buy_do':
            $product_id = isset($params[1]) ? intval($params[1]) : 0;
            $amount = isset($params[2]) ? intval($params[2]) : 0;
            tgshop_shop_buy_do($bot, $product_id, $amount);
            break;

        // Prompt nhập từ khoá tìm kiếm (set state = "shop:search_do")
        case 'search':
            tgshop_shop_search_prompt($bot);
            break;

        // User gõ từ khoá → processMessage route đến đây qua state.
        // Keyword có thể chứa dấu ':' nên phải join lại toàn bộ params còn lại.
        case 'search_do':
            $keyword = isset($params[1]) ? implode(':', array_slice($params, 1)) : '';
            tgshop_shop_search_do($bot, $keyword);
            break;

        // Phân trang kết quả search (keyword đọc từ users.telegram_search)
        case 'search_page':
            $page = isset($params[1]) ? intval($params[1]) : 1;
            tgshop_shop_search_page($bot, $page);
            break;

        default:
            tgshop_shop_categories($bot);
            break;
    }
}

/**
 * Hiển thị danh sách chuyên mục cha (parent_id = 0)
 * Mỗi chuyên mục cha là 1 nút bấm → nhấn vào sẽ hiển thị chuyên mục con hoặc sản phẩm
 */
function tgshop_shop_categories($bot)
{
    // Lấy tất cả categories đang bật
    $all_categories = get_categories_cached();

    // Lọc ra categories cha (parent_id = 0)
    $parent_categories = array_filter($all_categories, function ($cat) {
        return $cat['parent_id'] == 0;
    });

    // Kiểm tra xem có chuyên mục cha nào không
    // Nếu không có parent → tất cả categories đều là leaf → hiển thị trực tiếp
    if (empty($parent_categories)) {
        tgshop_shop_all_leaf_categories($bot);
        return;
    }

    $text  = __('📂 <b>Danh sách chuyên mục</b>') . "\n\n";
    $text .= __('Chọn chuyên mục bạn muốn xem:');

    // Xây dựng mảng nút, mỗi hàng 2 nút cho gọn gàng
    $buttons = [];
    $row = [];
    $count = 0;

    foreach ($parent_categories as $cat) {
        // Đếm số chuyên mục con bên trong
        $children = array_filter($all_categories, function ($c) use ($cat) {
            return $c['parent_id'] == $cat['id'];
        });
        $child_count = count($children);

        // Nếu có chuyên mục con → dẫn tới trang sub-categories
        // Nếu không có chuyên mục con → dẫn thẳng tới sản phẩm
        if ($child_count > 0) {
            $label = $cat['name'] . " ({$child_count})";
            $callback = 'shop:cat:' . $cat['id'];
        } else {
            // Đếm sản phẩm trực tiếp trong category này
            $count_sql = "SELECT * FROM `products` WHERE `status` = 1 AND `category_id` = ? AND `hide_in_shop` = 0" . (column_exists('products', 'pending') ? " AND `pending` = 0" : "");
            if ($bot->CMSNT->site('product_hide_outstock') == 1) {
                $count_sql .= " AND (
                    (supplier_id != 0 AND api_stock > 0)
                    OR
                    (supplier_id = 0 AND (SELECT COUNT(id) FROM `product_stock` WHERE `product_code` = products.code) > 0)
                )";
            }
            $product_count = $bot->CMSNT->num_rows_safe($count_sql, [$cat['id']]);
            $label = $cat['name'] . " ({$product_count})";
            $callback = 'shop:products:' . $cat['id'] . ':1';
        }

        $row[] = ['text' => $label, 'data' => $callback];
        $count++;

        // Mỗi hàng 2 nút (phù hợp màn hình di động)
        if ($count % 2 == 0) {
            $buttons[] = $row;
            $row = [];
        }
    }

    // Nếu còn nút lẻ chưa đủ hàng → thêm vào
    if (!empty($row)) {
        $buttons[] = $row;
    }

    // Thêm nút "Xem tất cả sản phẩm" và nút "Quay lại"
    $buttons[] = [
        ['text' => __('📋 Xem tất cả sản phẩm'), 'data' => 'shop:products:0:1']
    ];
    $buttons[] = [
        TelegramShopBot::backButton('menu:main', __('🏠 Menu chính'))
    ];

    $keyboard = TelegramShopBot::inlineKeyboard($buttons);
    $bot->reply($text, $keyboard);
}

/**
 * Hiển thị tất cả leaf categories (khi không có cấu trúc parent)
 * Trường hợp đặc biệt: shop không dùng phân cấp parent/child
 */
function tgshop_shop_all_leaf_categories($bot)
{
    $categories = get_categories_cached();

    $text  = __('📂 <b>Danh sách chuyên mục</b>') . "\n\n";
    $text .= __('Chọn chuyên mục bạn muốn xem:');

    $buttons = [];
    $row = [];
    $count = 0;

    foreach ($categories as $cat) {
        $count_sql = "SELECT * FROM `products` WHERE `status` = 1 AND `category_id` = ? AND `hide_in_shop` = 0" . (column_exists('products', 'pending') ? " AND `pending` = 0" : "");
        if ($bot->CMSNT->site('product_hide_outstock') == 1) {
            $count_sql .= " AND (
                (supplier_id != 0 AND api_stock > 0)
                OR
                (supplier_id = 0 AND (SELECT COUNT(id) FROM `product_stock` WHERE `product_code` = products.code) > 0)
            )";
        }
        $product_count = $bot->CMSNT->num_rows_safe($count_sql, [$cat['id']]);

        $row[] = ['text' => $cat['name'] . " ({$product_count})", 'data' => 'shop:products:' . $cat['id'] . ':1'];
        $count++;

        if ($count % 2 == 0) {
            $buttons[] = $row;
            $row = [];
        }
    }

    if (!empty($row)) {
        $buttons[] = $row;
    }

    $buttons[] = [
        ['text' => __('📋 Xem tất cả sản phẩm'), 'data' => 'shop:products:0:1']
    ];
    $buttons[] = [
        TelegramShopBot::backButton('menu:main', __('🏠 Menu chính'))
    ];

    $keyboard = TelegramShopBot::inlineKeyboard($buttons);
    $bot->reply($text, $keyboard);
}

/**
 * Hiển thị chuyên mục con (sub-categories) trong 1 chuyên mục cha
 * Mỗi chuyên mục con là 1 nút → nhấn vào sẽ hiển thị sản phẩm
 */
function tgshop_shop_sub_categories($bot, $parent_id)
{
    // Lấy thông tin chuyên mục cha
    $parent = $bot->CMSNT->get_row_safe("SELECT * FROM `categories` WHERE `id` = ? AND `status` = 1", [$parent_id]);
    if (!$parent) {
        $bot->reply(__('❌ Chuyên mục không tồn tại.'));
        return;
    }

    // Lấy danh sách chuyên mục con
    $children = get_categories_by_parent_cached($parent_id);

    if (empty($children)) {
        // Không có con → hiển thị sản phẩm trực tiếp
        tgshop_shop_products($bot, $parent_id, 1);
        return;
    }

    $text  = sprintf(__('📂 <b>%s</b>'), $parent['name']) . "\n\n";
    $text .= __('Chọn chuyên mục con:');

    $buttons = [];
    $row = [];
    $count = 0;

    foreach ($children as $cat) {
        // Đếm sản phẩm trong mỗi chuyên mục con
        $count_sql = "SELECT * FROM `products` WHERE `status` = 1 AND `category_id` = ? AND `hide_in_shop` = 0" . (column_exists('products', 'pending') ? " AND `pending` = 0" : "");
        if ($bot->CMSNT->site('product_hide_outstock') == 1) {
            $count_sql .= " AND (
                (supplier_id != 0 AND api_stock > 0)
                OR
                (supplier_id = 0 AND (SELECT COUNT(id) FROM `product_stock` WHERE `product_code` = products.code) > 0)
            )";
        }
        $product_count = $bot->CMSNT->num_rows_safe($count_sql, [$cat['id']]);

        $row[] = ['text' => $cat['name'] . " ({$product_count})", 'data' => 'shop:products:' . $cat['id'] . ':1'];
        $count++;

        if ($count % 2 == 0) {
            $buttons[] = $row;
            $row = [];
        }
    }

    if (!empty($row)) {
        $buttons[] = $row;
    }

    // Nút quay lại danh sách chuyên mục
    $buttons[] = [
        TelegramShopBot::backButton('shop:categories', __('⬅️ Tất cả chuyên mục')),
        TelegramShopBot::backButton('menu:main', __('🏠 Menu chính'))
    ];

    $keyboard = TelegramShopBot::inlineKeyboard($buttons);
    $bot->reply($text, $keyboard);
}

/**
 * Hiển thị danh sách sản phẩm trong 1 chuyên mục (có phân trang)
 * 
 * @param TelegramShopBot $bot Instance bot
 * @param int $cat_id ID chuyên mục (0 = tất cả sản phẩm)
 * @param int $page Số trang hiện tại
 */
function tgshop_shop_products($bot, $cat_id, $page)
{
    $limit = 10; // Số sản phẩm mỗi trang (10 vừa đủ thoáng cho Telegram)
    if ($page < 1) $page = 1;
    $offset = ($page - 1) * $limit;

    // Xây dựng query theo chuyên mục
    $where = "`status` = 1 AND `hide_in_shop` = 0";
    $params = [];
    $category_name = __('Tất cả sản phẩm');

    // Kiểm tra cột pending tồn tại không
    $pending_condition = column_exists('products', 'pending') ? " AND `pending` = 0" : "";
    $where .= $pending_condition;

    // Ẩn sản phẩm hết hàng nếu cấu hình product_hide_outstock bật
    if ($bot->CMSNT->site('product_hide_outstock') == 1) {
        $where .= " AND (
            (supplier_id != 0 AND api_stock > 0)
            OR
            (supplier_id = 0 AND (SELECT COUNT(id) FROM `product_stock` WHERE `product_code` = products.code) > 0)
        )";
    }

    if ($cat_id > 0) {
        $where .= " AND `category_id` = ?";
        $params[] = $cat_id;

        // Lấy tên chuyên mục để hiển thị
        $category = $bot->CMSNT->get_row_safe("SELECT `name`, `parent_id` FROM `categories` WHERE `id` = ?", [$cat_id]);
        if ($category) {
            $category_name = $category['name'];
        }
    }

    // Đếm tổng sản phẩm
    $total = $bot->CMSNT->num_rows_safe("SELECT * FROM `products` WHERE {$where}", $params);
    $total_pages = max(1, ceil($total / $limit));
    if ($page > $total_pages) $page = $total_pages;

    // Lấy danh sách sản phẩm (chỉ select fields cần thiết)
    $products = $bot->CMSNT->get_list_safe(
        "SELECT `id`, `name`, `price`, `discount`, `short_desc`, `sold`, `code`, `supplier_id`, `api_stock`
         FROM `products` WHERE {$where} ORDER BY `stt` DESC LIMIT ?, ?",
        array_merge($params, [$offset, $limit])
    );

    $text  = sprintf(__('🛒 <b>%s</b>'), $category_name) . "\n";
    $text .= sprintf(__('📄 Trang %s/%s (%s sản phẩm)'), $page, $total_pages, $total) . "\n";
    $text .= "━━━━━━━━━━━━━━━━━━\n\n";

    if (empty($products)) {
        $text .= __('<i>Không có sản phẩm nào trong chuyên mục này.</i>');
    } else {
        $index = $offset;
        foreach ($products as $product) {
            $index++;
            // Tính tồn kho
            $stock = $product['supplier_id'] != 0 ? $product['api_stock'] : getStock($product['code']);

            // Tính giá sau giảm
            $original_price = $product['price'];
            $final_price = $original_price - $original_price * $product['discount'] / 100;

            // Hiển thị emoji trạng thái tồn kho
            $stock_icon = $stock > 0 ? '🟢' : '🔴';
            $stock_label = $stock > 0 ? format_cash($stock) : __('Hết hàng');

            $text .= "<b>V{$product['id']} | {$product['name']}</b>\n";
            $text .= "   💰 " . format_currency($final_price);
            if ($product['discount'] > 0) {
                $text .= " <s>" . format_currency($original_price) . "</s> (-{$product['discount']}%)";
            }
            $text .= "\n";
            $text .= sprintf(__('   %s Kho: %s'), $stock_icon, $stock_label);
            // Hiển thị số đã bán phụ thuộc vào cài đặt product_sold_display trong admin
            if ($bot->CMSNT->site('product_sold_display') == 1) {
                $text .= ' | ' . sprintf(__('📦 Đã bán: %s'), format_cash($product['sold']));
            }
            $text .= "\n\n";
        }
    }

    // Xây dựng nút cho từng sản phẩm (dạng chi tiết)
    $product_buttons = [];
    foreach ($products as $product) {
        $stock = $product['supplier_id'] != 0 ? $product['api_stock'] : getStock($product['code']);
        $prefix = ($stock > 0 ? '🛒 ' : '🚫 ') . 'V' . $product['id'] . ' | ';
        $max_name_len = 30 - mb_strlen('V' . $product['id'] . ' | ', 'UTF-8');
        $label = $prefix . mb_substr($product['name'], 0, $max_name_len, 'UTF-8');
        if (mb_strlen($product['name'], 'UTF-8') > $max_name_len) {
            $label .= '...';
        }
        $product_buttons[] = [
            ['text' => $label, 'data' => 'shop:detail:' . $product['id']]
        ];
    }

    // Nút phân trang
    $nav_buttons = [];
    if ($page > 1) {
        $nav_buttons[] = ['text' => __('◀️ Trước'), 'data' => 'shop:products:' . $cat_id . ':' . ($page - 1)];
    }
    $nav_buttons[] = ['text' => sprintf(__('📄 %s/%s'), $page, $total_pages), 'data' => 'shop:products:' . $cat_id . ':' . $page];
    if ($page < $total_pages) {
        $nav_buttons[] = ['text' => __('Sau ▶️'), 'data' => 'shop:products:' . $cat_id . ':' . ($page + 1)];
    }

    // Nút quay lại → xác định nút back phù hợp dựa trên context
    $back_data = 'shop:categories'; // Mặc định quay về danh sách chuyên mục
    $back_label = __('⬅️ Chuyên mục');

    // Nếu đang xem chuyên mục con → quay về chuyên mục cha
    if ($cat_id > 0 && isset($category) && $category['parent_id'] > 0) {
        $back_data = 'shop:cat:' . $category['parent_id'];
        $back_label = __('⬅️ Quay lại');
    }

    // Ghép tất cả nút
    $all_buttons = array_merge(
        $product_buttons,
        [$nav_buttons],
        [
            [
                TelegramShopBot::backButton($back_data, $back_label),
                TelegramShopBot::backButton('menu:main', __('🏠 Menu chính'))
            ]
        ]
    );

    $keyboard = TelegramShopBot::inlineKeyboard($all_buttons);
    $bot->reply($text, $keyboard);
}

/**
 * Hiển thị chi tiết 1 sản phẩm
 * Bao gồm: tên, mô tả, giá, tồn kho, nút mua (link web)
 */
function tgshop_shop_product_detail($bot, $product_id)
{
    // Lấy thông tin sản phẩm
    $product = $bot->CMSNT->get_row_safe(
        "SELECT `id`, `name`, `slug`, `price`, `discount`, `short_desc`, `description`, `sold`, `code`, `supplier_id`, `api_stock`, `category_id`, `min`, `max`
         FROM `products` WHERE `id` = ? AND `status` = 1 AND `hide_in_shop` = 0",
        [$product_id]
    );

    if (!$product) {
        $bot->reply(__('❌ Sản phẩm không tồn tại hoặc đã bị ẩn.'));
        return;
    }

    // Ẩn sản phẩm hết hàng nếu cấu hình product_hide_outstock bật
    if ($bot->CMSNT->site('product_hide_outstock') == 1) {
        $stock = $product['supplier_id'] != 0 ? $product['api_stock'] : getStock($product['code']);
        if ($stock <= 0) {
            $bot->reply(__('❌ Sản phẩm không tồn tại hoặc đã bị ẩn.'));
            return;
        }
    }

    // Lấy tên chuyên mục
    $category = $bot->CMSNT->get_row_safe("SELECT `name`, `parent_id` FROM `categories` WHERE `id` = ?", [$product['category_id']]);
    $category_name = $category ? $category['name'] : __('Không phân loại');

    // Tính tồn kho, giá
    $stock = $product['supplier_id'] != 0 ? $product['api_stock'] : getStock($product['code']);
    $original_price = $product['price'];
    $final_price = $original_price - $original_price * $product['discount'] / 100;
    $stock_icon = $stock > 0 ? '🟢' : '🔴';
    $stock_label = $stock > 0 ? format_cash($stock) : __('Hết hàng');

    // Xây dựng tin nhắn chi tiết
    $text  = sprintf(__('🏷 <b>%s</b>'), $product['name']) . "\n";
    $text .= "━━━━━━━━━━━━━━━━━━\n\n";

    // Mô tả ngắn
    if (!empty($product['short_desc'])) {
        // Hiển thị mô tả ngắn dạng bullet point (mỗi dòng 1 bullet)
        $desc_lines = array_filter(explode("\n", $product['short_desc']));
        foreach ($desc_lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $text .= "  ✔️ " . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . "\n";
            }
        }
        $text .= "\n";
    }

    // Thông tin giá và tồn kho
    $text .= sprintf(__('📂 <b>Chuyên mục:</b> %s'), $category_name) . "\n";
    $text .= __('💰 <b>Giá:</b> ') . format_currency($final_price);
    if ($product['discount'] > 0) {
        $text .= " <s>" . format_currency($original_price) . "</s> <b>(-{$product['discount']}%)</b>";
    }
    $text .= "\n";
    $text .= sprintf(__('%s <b>Kho hàng:</b> %s'), $stock_icon, $stock_label) . "\n";
    // Hiển thị số đã bán phụ thuộc vào cài đặt product_sold_display trong admin
    if ($bot->CMSNT->site('product_sold_display') == 1) {
        $text .= sprintf(__('📦 <b>Đã bán:</b> %s'), format_cash($product['sold'])) . "\n";
    }

    // Giới hạn mua
    if ($product['min'] > 0 || $product['max'] > 0) {
        $text .= __('📏 <b>Giới hạn:</b> ');
        if ($product['min'] > 0) $text .= sprintf(__('Tối thiểu %s'), $product['min']);
        if ($product['min'] > 0 && $product['max'] > 0) $text .= " - ";
        if ($product['max'] > 0) $text .= sprintf(__('Tối đa %s'), $product['max']);
        $text .= "\n";
    }

    // Nút bấm
    $buttons = [];

    // Nút mua ngay trực tiếp trong Telegram (callback nội bộ)
    // User còn money trừ khi checkout. Stock = 0 thì chặn ngay tại đây để tiết kiệm API call.
    if ($stock > 0) {
        $buttons[] = [
            ['text' => __('🛒 Mua ngay'), 'data' => 'shop:buy:' . $product_id]
        ];
    } else {
        $buttons[] = [
            ['text' => __('🚫 Hết hàng'), 'data' => 'shop:detail:' . $product_id]
        ];
    }

    // Nút quay lại danh sách sản phẩm cùng chuyên mục
    $buttons[] = [
        TelegramShopBot::backButton('shop:products:' . $product['category_id'] . ':1', __('⬅️ Quay lại')),
        TelegramShopBot::backButton('menu:main', __('🏠 Menu chính'))
    ];

    $keyboard = TelegramShopBot::inlineKeyboard($buttons);
    $bot->reply($text, $keyboard);
}

// =========================================================================
// LUỒNG MUA HÀNG TRỰC TIẾP TẠI TELEGRAM
// =========================================================================

/**
 * Helper lấy sản phẩm kèm stock thực tế (dùng chung cho các bước mua)
 * Trả về null nếu sản phẩm không tồn tại/ẩn/ngừng bán
 * 
 * @return array|null ['product' => array, 'stock' => int, 'final_price' => float]
 */
function tgshop_shop_load_product_for_buy($bot, $product_id)
{
    // Điều kiện giống buyProduct: phải status=1. Thêm hide_in_shop=0 để chặn ẩn.
    $product = $bot->CMSNT->get_row_safe(
        "SELECT `id`, `name`, `slug`, `price`, `discount`, `code`, `supplier_id`, `api_stock`,
                `category_id`, `min`, `max`, `status`, `hide_in_shop`
         FROM `products`
         WHERE `id` = ? AND `status` = 1 AND `hide_in_shop` = 0",
        [$product_id]
    );

    if (!$product) {
        return null;
    }

    // Stock: với supplier dùng api_stock, với kho nội bộ query bảng product_stock
    $stock = $product['supplier_id'] != 0 ? intval($product['api_stock']) : getStock($product['code']);

    // Giá sau chiết khấu cơ bản (chưa tính chiết khấu theo số lượng / user discount / VAT)
    $final_price = $product['discount'] == 0
        ? $product['price']
        : $product['price'] - $product['price'] * $product['discount'] / 100;

    return [
        'product'     => $product,
        'stock'       => $stock,
        'final_price' => $final_price,
    ];
}

/**
 * Bước 1: Hiển thị màn chọn số lượng mua
 * - Hiển thị các preset nằm trong khoảng [min, max] và <= stock
 * - Cho nút "Nhập số khác" nếu khoảng còn mở rộng được
 */
function tgshop_shop_buy_quantity($bot, $product_id)
{
    $info = tgshop_shop_load_product_for_buy($bot, $product_id);
    if (!$info) {
        $bot->reply(__('❌ Sản phẩm không tồn tại hoặc đã bị ẩn.'));
        return;
    }
    $product     = $info['product'];
    $stock       = $info['stock'];
    $final_price = $info['final_price'];

    if ($stock <= 0) {
        $bot->reply(__('🚫 Sản phẩm đã hết hàng, vui lòng chọn sản phẩm khác.'));
        return;
    }

    // Ràng buộc min/max của sản phẩm (fallback 1 nếu không cấu hình)
    $min = max(1, intval($product['min']));
    $max = intval($product['max']);
    if ($max <= 0) {
        $max = $stock; // Không cấu hình max → lấy theo tồn kho
    }
    // Chặn vượt tồn kho — không thể mua nhiều hơn kho hiện có
    $max = min($max, $stock);

    // Preset số lượng quen thuộc; lọc theo [min, max]
    $presets = [1, 2, 5, 10, 20, 50, 100];
    $available = [];
    foreach ($presets as $q) {
        if ($q >= $min && $q <= $max) {
            $available[] = $q;
        }
    }
    // Đảm bảo luôn có ít nhất 1 lựa chọn "mua min" nếu preset không phủ tới min
    if (empty($available) || $available[0] > $min) {
        array_unshift($available, $min);
    }
    // Bỏ trùng và sắp xếp
    $available = array_values(array_unique($available));
    sort($available);

    // Xây tin nhắn
    $balance = $bot->getFormattedBalance();
    $text  = sprintf(__('🛒 <b>Mua: %s</b>'), $product['name']) . "\n";
    $text .= "━━━━━━━━━━━━━━━━━━\n";
    $text .= sprintf(__('💰 <b>Đơn giá:</b> %s'), format_currency($final_price)) . "\n";
    $text .= sprintf(__('📦 <b>Tồn kho:</b> %s'), format_cash($stock)) . "\n";
    $text .= sprintf(__('🏦 <b>Số dư:</b> %s'), $balance) . "\n\n";
    $text .= sprintf(__('📏 <b>Giới hạn:</b> %s - %s'), format_cash($min), format_cash($max)) . "\n\n";
    $text .= __('Chọn số lượng muốn mua:');

    // Xây nút: mỗi hàng 3 nút cho gọn, kèm tổng tiền ước tính
    $buttons = [];
    $row = [];
    $count = 0;
    foreach ($available as $q) {
        $total = $final_price * $q;
        $row[] = [
            'text' => sprintf(__('%s SP (%s)'), format_cash($q), format_currency($total)),
            'data' => 'shop:buy_confirm:' . $product_id . ':' . $q
        ];
        $count++;
        if ($count % 2 == 0) {
            $buttons[] = $row;
            $row = [];
        }
    }
    if (!empty($row)) {
        $buttons[] = $row;
    }

    // Nút nhập số lượng tùy ý (chỉ hiện khi max > min, tránh khi cố định 1)
    if ($max > $min) {
        $buttons[] = [
            ['text' => __('✏️ Nhập số lượng khác'), 'data' => 'shop:buy_input:' . $product_id]
        ];
    }

    // Nút điều hướng
    $buttons[] = [
        TelegramShopBot::backButton('shop:detail:' . $product_id, __('⬅️ Quay lại')),
        TelegramShopBot::backButton('menu:main', __('🏠 Menu chính'))
    ];

    $keyboard = TelegramShopBot::inlineKeyboard($buttons);
    $bot->reply($text, $keyboard);
}

/**
 * Bước 2a: Prompt user nhập số lượng tùy ý
 * Set state "shop:buy_custom:{product_id}" — khi user gõ số, route về buy_custom
 */
function tgshop_shop_buy_input($bot, $product_id)
{
    $info = tgshop_shop_load_product_for_buy($bot, $product_id);
    if (!$info) {
        $bot->reply(__('❌ Sản phẩm không tồn tại hoặc đã bị ẩn.'));
        return;
    }
    $product = $info['product'];
    $stock   = $info['stock'];

    $min = max(1, intval($product['min']));
    $max = intval($product['max']) > 0 ? intval($product['max']) : $stock;
    $max = min($max, $stock);

    // Set state kèm product_id để khi text đến còn biết mua sản phẩm nào
    $bot->setState('shop:buy_custom:' . $product_id);

    $text  = sprintf(__('✏️ <b>Nhập số lượng cần mua</b>') . "\n\n");
    $text .= sprintf(__('🛍 <b>Sản phẩm:</b> %s'), $product['name']) . "\n";
    $text .= sprintf(__('📏 <b>Giới hạn:</b> %s - %s'), format_cash($min), format_cash($max)) . "\n\n";
    $text .= __('👇 Nhập số lượng (chỉ số nguyên):');

    $keyboard = TelegramShopBot::inlineKeyboard([
        [
            TelegramShopBot::backButton('shop:buy:' . $product_id, __('⬅️ Chọn preset')),
            TelegramShopBot::backButton('shop:detail:' . $product_id, __('🔙 Chi tiết SP'))
        ]
    ]);

    $bot->reply($text, $keyboard);
}

/**
 * Bước 2b: Xử lý số user gõ khi ở trạng thái buy_custom
 * Validate + chuyển sang màn xác nhận
 */
function tgshop_shop_buy_custom($bot, $product_id, $amount_text)
{
    // Cho phép user gõ "5 sp", "5.0", "5,0" → chuẩn hoá về số nguyên
    $cleaned = preg_replace('/[^0-9]/', '', $amount_text);
    $amount  = $cleaned !== '' ? intval($cleaned) : 0;

    if ($amount <= 0) {
        // Set lại state để user có thể nhập lại ngay
        $bot->setState('shop:buy_custom:' . $product_id);
        $keyboard = TelegramShopBot::inlineKeyboard([
            [
                TelegramShopBot::backButton('shop:buy:' . $product_id, __('⬅️ Chọn preset')),
                TelegramShopBot::backButton('shop:detail:' . $product_id, __('🔙 Chi tiết SP'))
            ]
        ]);
        $bot->sendMessage(__('❌ Số lượng không hợp lệ. Vui lòng nhập 1 số nguyên dương (VD: <code>5</code>).'), $keyboard);
        return;
    }

    // Chuyển sang màn xác nhận — các check min/max/stock xử lý bên trong buy_confirm
    tgshop_shop_buy_confirm($bot, $product_id, $amount);
}

/**
 * Bước 3: Màn xác nhận trước khi trừ tiền
 * Hiển thị chi tiết + số dư hiện tại + cảnh báo nếu không đủ
 */
function tgshop_shop_buy_confirm($bot, $product_id, $amount)
{
    $info = tgshop_shop_load_product_for_buy($bot, $product_id);
    if (!$info) {
        $bot->reply(__('❌ Sản phẩm không tồn tại hoặc đã bị ẩn.'));
        return;
    }
    $product     = $info['product'];
    $stock       = $info['stock'];
    $final_price = $info['final_price'];

    $min = max(1, intval($product['min']));
    $max = intval($product['max']) > 0 ? intval($product['max']) : $stock;
    $max = min($max, $stock);

    // Validate số lượng trước khi cho xác nhận — tránh tốn call API nội bộ nếu sai
    if ($amount < $min) {
        $bot->reply(sprintf(__('❌ Số lượng tối thiểu là <b>%s</b>. Vui lòng chọn lại.'), format_cash($min)));
        tgshop_shop_buy_quantity($bot, $product_id);
        return;
    }
    if ($amount > $max) {
        $bot->reply(sprintf(__('❌ Số lượng tối đa có thể mua là <b>%s</b> (giới hạn theo kho/cấu hình).'), format_cash($max)));
        tgshop_shop_buy_quantity($bot, $product_id);
        return;
    }

    // Tính toán sơ bộ giống logic trong buyProduct (không áp coupon vì UX Telegram chưa hỗ trợ)
    $money = $final_price * $amount;

    // Chiết khấu: nếu user có user.discount thì dùng, ngược lại dùng getDiscount(amount, product_id)
    $user_discount_percent = floatval($bot->user['discount']);
    if ($user_discount_percent > 0) {
        $discount = $money * $user_discount_percent / 100;
    } else {
        $discount = $money * getDiscount($amount, $product['id']) / 100;
    }
    $sub_total = $money - $discount;

    // VAT (nếu site bật)
    $tax_vat_percent = floatval($bot->CMSNT->site('tax_vat'));
    $vat = $tax_vat_percent > 0 ? $sub_total * $tax_vat_percent / 100 : 0;
    $pay = $sub_total + $vat;

    // Số dư
    $balance = floatval($bot->user['money']);
    $enough  = $balance >= $pay;

    // Xây tin nhắn
    $text  = __('📝 <b>Xác nhận đơn hàng</b>') . "\n";
    $text .= "━━━━━━━━━━━━━━━━━━\n";
    $text .= sprintf(__('🛍 <b>Sản phẩm:</b> %s'), $product['name']) . "\n";
    $text .= sprintf(__('🔢 <b>Số lượng:</b> %s'), format_cash($amount)) . "\n";
    $text .= sprintf(__('💰 <b>Đơn giá:</b> %s'), format_currency($final_price)) . "\n";
    $text .= sprintf(__('🧾 <b>Tạm tính:</b> %s'), format_currency($money)) . "\n";
    if ($discount > 0) {
        $text .= sprintf(__('🎁 <b>Chiết khấu:</b> -%s'), format_currency($discount)) . "\n";
    }
    if ($vat > 0) {
        $text .= sprintf(__('🏛 <b>VAT (%s%%):</b> +%s'), $tax_vat_percent, format_currency($vat)) . "\n";
    }
    $text .= "━━━━━━━━━━━━━━━━━━\n";
    $text .= sprintf(__('💳 <b>Tổng thanh toán:</b> %s'), format_currency($pay)) . "\n";
    $text .= sprintf(__('🏦 <b>Số dư hiện tại:</b> %s'), format_currency($balance)) . "\n";

    // Cảnh báo số dư không đủ + gợi ý nạp tiền
    if (!$enough) {
        $need_more = $pay - $balance;
        $text .= "\n" . sprintf(__('⚠️ <b>Số dư không đủ!</b> Thiếu: <b>%s</b>'), format_currency($need_more)) . "\n";
        $text .= __('Vui lòng nạp thêm để tiếp tục thanh toán.');

        $keyboard = TelegramShopBot::inlineKeyboard([
            [
                ['text' => __('💰 Nạp tiền ngay'), 'data' => 'recharge:methods']
            ],
            [
                TelegramShopBot::backButton('shop:buy:' . $product_id, __('⬅️ Đổi số lượng')),
                TelegramShopBot::backButton('shop:detail:' . $product_id, __('🔙 Chi tiết SP'))
            ]
        ]);
        $bot->reply($text, $keyboard);
        return;
    }

    $text .= "\n" . __('Bấm <b>Xác nhận mua</b> để trừ tiền và lấy sản phẩm ngay.');

    $keyboard = TelegramShopBot::inlineKeyboard([
        [
            ['text' => __('✅ Xác nhận mua'), 'data' => 'shop:buy_do:' . $product_id . ':' . $amount]
        ],
        [
            TelegramShopBot::backButton('shop:buy:' . $product_id, __('⬅️ Đổi số lượng')),
            TelegramShopBot::backButton('shop:detail:' . $product_id, __('❌ Hủy'))
        ]
    ]);

    $bot->reply($text, $keyboard);
}

/**
 * Bước 4: Thực thi mua — gọi endpoint buyProduct nội bộ rồi render kết quả
 * Tái sử dụng toàn bộ logic (nhiều loại supplier, VAT, hoa hồng, mail, noti...)
 * thay vì duplicate trong bot.
 */
function tgshop_shop_buy_do($bot, $product_id, $amount)
{
    $info = tgshop_shop_load_product_for_buy($bot, $product_id);
    if (!$info) {
        $bot->reply(__('❌ Sản phẩm không tồn tại hoặc đã bị ẩn.'));
        return;
    }
    $product = $info['product'];

    // Báo cho user biết đang xử lý (đơn hàng với supplier ngoài có thể mất vài giây)
    $bot->reply(__('⏳ <b>Đang xử lý đơn hàng...</b>') . "\n\n" . __('Vui lòng không bấm lại nút trong lúc chờ.'));

    // Gọi buyProduct qua cURL nội bộ dùng token của user (đã set khi đăng ký)
    $response = tgshop_shop_call_buy_api($bot, $product_id, $amount);

    // Response lỗi kết nối/không parse được JSON
    if (!is_array($response) || !isset($response['status'])) {
        $keyboard = TelegramShopBot::inlineKeyboard([
            [
                ['text' => __('🔄 Thử lại'), 'data' => 'shop:buy_confirm:' . $product_id . ':' . $amount]
            ],
            [
                TelegramShopBot::backButton('shop:detail:' . $product_id, __('⬅️ Chi tiết SP')),
                TelegramShopBot::backButton('menu:main', __('🏠 Menu chính'))
            ]
        ]);
        $bot->sendMessage(
            __('❌ <b>Lỗi xử lý đơn hàng</b>') . "\n\n" .
                __('Không nhận được phản hồi từ hệ thống. Tiền (nếu đã trừ) sẽ được hoàn tự động.') . "\n" .
                __('Vui lòng thử lại sau ít phút.'),
            $keyboard
        );
        return;
    }

    // Lỗi nghiệp vụ (stock không đủ, số dư không đủ, rate limit, ...)
    if ($response['status'] !== 'success') {
        $msg = isset($response['msg']) ? $response['msg'] : __('Có lỗi xảy ra khi mua hàng.');
        $keyboard = TelegramShopBot::inlineKeyboard([
            [
                ['text' => __('🔄 Thử lại'), 'data' => 'shop:buy_confirm:' . $product_id . ':' . $amount]
            ],
            [
                TelegramShopBot::backButton('shop:detail:' . $product_id, __('⬅️ Chi tiết SP')),
                TelegramShopBot::backButton('menu:main', __('🏠 Menu chính'))
            ]
        ]);
        $bot->sendMessage(
            __('❌ <b>Không thể mua hàng</b>') . "\n\n" . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'),
            $keyboard
        );
        return;
    }

    // Mua thành công — refresh user để lấy số dư mới
    $bot->user = $bot->CMSNT->get_row_safe("SELECT * FROM `users` WHERE `id` = ?", [$bot->user['id']]);

    $trans_id = isset($response['trans_id']) ? $response['trans_id'] : '';
    $accounts = isset($response['data']) && is_array($response['data']) ? $response['data'] : [];

    tgshop_shop_send_purchase_result($bot, $product, $amount, $trans_id, $accounts);
}

/**
 * Helper: gọi endpoint buyProduct nội bộ
 * Dùng token path (không cần IP whitelist — khác với api_key).
 * 
 * @return array|null JSON decode response, null nếu cURL lỗi
 */
function tgshop_shop_call_buy_api($bot, $product_id, $amount)
{
    $url = base_url('/ajaxs/client/product.php');

    $post = http_build_query([
        'action' => 'buyProduct',
        'id'     => $product_id,
        'amount' => $amount,
        'token'  => $bot->user['token'],
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    // Timeout cao vì đơn hàng với supplier ngoài có thể mất 10-30s (API_6 có poll)
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    $body = curl_exec($ch);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err) {
        $bot->logDebug('BUY_API_CURL', $err);
        return null;
    }

    $data = json_decode($body, true);
    if (!is_array($data)) {
        // Log raw response để debug khi API trả HTML lỗi, 500, v.v.
        $bot->logDebug('BUY_API_PARSE', mb_substr((string) $body, 0, 500, 'UTF-8'));
        return null;
    }

    return $data;
}

/**
 * Render kết quả mua thành công:
 * - Danh sách ngắn: inline <pre>...</pre> cho dễ copy
 * - Danh sách dài (>3500 chars): gửi file .txt đính kèm
 */
function tgshop_shop_send_purchase_result($bot, $product, $amount, $trans_id, $accounts)
{
    $balance_new = format_currency($bot->user['money']);

    // Header chung cho cả 2 nhánh (inline / file)
    $header  = __('✅ <b>Mua hàng thành công!</b>') . "\n";
    $header .= "━━━━━━━━━━━━━━━━━━\n";
    $header .= sprintf(__('🛍 <b>Sản phẩm:</b> %s'), $product['name']) . "\n";
    $header .= sprintf(__('🔢 <b>Số lượng:</b> %s'), format_cash($amount)) . "\n";
    $header .= sprintf(__('🧾 <b>Mã đơn:</b> <code>%s</code>'), $trans_id) . "\n";
    $header .= sprintf(__('🏦 <b>Số dư mới:</b> %s'), $balance_new) . "\n";

    // Layout nút: hàng đầu là tải file để user thấy ngay, sau mới đến điều hướng
    $keyboard = TelegramShopBot::inlineKeyboard([
        [
            ['text' => __('📥 Tải file .txt'), 'data' => 'order:download:' . $trans_id]
        ],
        [
            ['text' => __('🛒 Mua tiếp'), 'data' => 'shop:detail:' . $product['id']],
            ['text' => __('📦 Đơn hàng'), 'data' => 'order:list']
        ],
        [
            TelegramShopBot::backButton('menu:main', __('🏠 Menu chính'))
        ]
    ]);

    // Xây nội dung account (mỗi dòng 1 account)
    $accounts_text = '';
    foreach ($accounts as $acc) {
        // Chuẩn hoá: xoá \r thừa, decode nếu lưu dạng htmlspecialchars
        $line = preg_replace("/\r/", '', htmlspecialchars_decode((string) $acc));
        $accounts_text .= $line . "\n";
    }
    $accounts_text = rtrim($accounts_text, "\n");

    // Ngưỡng hiển thị inline:
    // - < 10 account: đủ gọn để user copy trực tiếp trong tin nhắn
    // - AND tổng text <= 3500 ký tự: chừa chỗ cho header + thẻ HTML (Telegram giới hạn 4096)
    // Nếu vượt một trong 2 điều kiện → auto gửi file .txt (vì 1 account có thể >255 chars)
    if (count($accounts) < 10 && mb_strlen($accounts_text, 'UTF-8') <= 3500) {
        $text  = $header . "\n";
        $text .= __('📄 <b>Sản phẩm đã mua:</b>') . "\n";
        $text .= "<pre>" . htmlspecialchars($accounts_text, ENT_QUOTES, 'UTF-8') . "</pre>";
        $bot->sendMessage($text, $keyboard);
        return;
    }

    // Danh sách quá dài → gửi kèm file .txt
    $tmp_dir = sys_get_temp_dir();
    $filename = 'order_' . $trans_id . '.txt';
    $filepath = $tmp_dir . DIRECTORY_SEPARATOR . $filename;

    // Nội dung file: có header ghi rõ đơn hàng, dễ tra cứu sau này
    $file_header  = "===============================\n";
    $file_header .= "Order: #{$trans_id}\n";
    $file_header .= "Product: {$product['name']}\n";
    $file_header .= "Amount: " . format_cash($amount) . "\n";
    $file_header .= "Time: " . gettime() . "\n";
    $file_header .= "===============================\n\n";

    file_put_contents($filepath, $file_header . $accounts_text);

    // Gửi tin nhắn header trước (có keyboard), sau đó gửi file riêng
    $notice = $header . "\n" . sprintf(__('📎 Danh sách %s sản phẩm được gửi trong file đính kèm.'), format_cash(count($accounts)));
    $bot->sendMessage($notice, $keyboard);

    $caption = sprintf(__('📄 Đơn hàng #%s'), $trans_id);
    $bot->sendDocument($filepath, $filename, 'text/plain', $caption);

    // Dọn file tạm để tránh rác trên server
    @unlink($filepath);
}

// =========================================================================
// TÌM KIẾM SẢN PHẨM
// =========================================================================

/**
 * Bước 1: Prompt user nhập từ khoá tìm kiếm
 * Set state "shop:search_do" — khi user gõ text tiếp theo, processMessage sẽ route về search_do
 */
function tgshop_shop_search_prompt($bot)
{
    // Khi user bắt đầu search mới → xoá keyword cũ để tránh nhầm lẫn
    $bot->CMSNT->update('users', ['telegram_search' => null], ' `id` = ? ', [$bot->user['id']]);

    // Set state chờ text user gõ vào
    $bot->setState('shop:search_do');

    $text  = __('🔍 <b>Tìm kiếm sản phẩm</b>') . "\n\n";
    $text .= __('Nhập tên sản phẩm bạn muốn tìm (tối thiểu 2 ký tự).') . "\n";
    $text .= __('Gõ vài ký tự đặc trưng để kết quả chính xác nhất.') . "\n\n";
    $text .= __('👇 Nhập từ khoá:');

    $keyboard = TelegramShopBot::inlineKeyboard([
        [
            TelegramShopBot::backButton('shop:categories', __('⬅️ Danh mục')),
            TelegramShopBot::backButton('menu:main', __('🏠 Menu chính'))
        ]
    ]);

    $bot->reply($text, $keyboard);
}

/**
 * Bước 2: Nhận từ khoá user vừa gõ → validate + lưu vào DB + hiển thị trang 1
 * 
 * Lưu vào users.telegram_search thay vì nhét keyword vào callback_data vì:
 * - callback_data giới hạn 64 bytes → keyword tiếng Việt có dấu dễ vượt quá
 * - User có thể cần phân trang, lưu DB giúp state bền vững qua nhiều request
 */
function tgshop_shop_search_do($bot, $keyword)
{
    // Trim khoảng trắng 2 đầu + bỏ khoảng trắng liên tiếp
    $keyword = trim(preg_replace('/\s+/', ' ', (string) $keyword));

    if (mb_strlen($keyword, 'UTF-8') < 2) {
        // Keyword quá ngắn → set lại state để user nhập lại
        $bot->setState('shop:search_do');
        $keyboard = TelegramShopBot::inlineKeyboard([
            [
                TelegramShopBot::backButton('shop:categories', __('⬅️ Danh mục')),
                TelegramShopBot::backButton('menu:main', __('🏠 Menu chính'))
            ]
        ]);
        $bot->sendMessage(__('❌ Từ khoá quá ngắn. Vui lòng nhập tối thiểu 2 ký tự.'), $keyboard);
        return;
    }

    // Giới hạn độ dài tối đa theo VARCHAR(100) để không bị lỗi khi INSERT
    $keyword_stored = mb_substr($keyword, 0, 100, 'UTF-8');

    // Lưu keyword để phân trang sau này đọc lại
    $bot->CMSNT->update('users', ['telegram_search' => $keyword_stored], ' `id` = ? ', [$bot->user['id']]);

    // Hiển thị trang đầu tiên
    tgshop_shop_render_search_results($bot, $keyword_stored, 1);
}

/**
 * Phân trang kết quả search — keyword đọc lại từ users.telegram_search
 */
function tgshop_shop_search_page($bot, $page)
{
    // Refresh user để lấy keyword mới nhất (có thể user vừa search xong ở request khác)
    $fresh_user = $bot->CMSNT->get_row_safe(
        "SELECT `telegram_search` FROM `users` WHERE `id` = ?",
        [$bot->user['id']]
    );
    $keyword = $fresh_user && !empty($fresh_user['telegram_search']) ? $fresh_user['telegram_search'] : '';

    if ($keyword === '') {
        // Không có keyword → quay về prompt search
        tgshop_shop_search_prompt($bot);
        return;
    }

    tgshop_shop_render_search_results($bot, $keyword, $page);
}

/**
 * Render kết quả tìm kiếm — cấu trúc tương tự tgshop_shop_products nhưng:
 * - Filter theo LIKE trên name thay vì category_id
 * - Nút phân trang dẫn về shop:search_page:{page} (không cần embed keyword)
 * - Có nút "🔁 Tìm từ khoá khác"
 */
function tgshop_shop_render_search_results($bot, $keyword, $page)
{
    $limit = 10; // Đồng bộ với danh sách sản phẩm thông thường
    if ($page < 1) $page = 1;
    $offset = ($page - 1) * $limit;

    // Build WHERE: status=1 + không ẩn + (không pending nếu cột tồn tại) + name LIKE
    $where = "`status` = 1 AND `hide_in_shop` = 0";
    if (column_exists('products', 'pending')) {
        $where .= " AND `pending` = 0";
    }
    // Ẩn sản phẩm hết hàng nếu cấu hình product_hide_outstock bật
    if ($bot->CMSNT->site('product_hide_outstock') == 1) {
        $where .= " AND (
            (supplier_id != 0 AND api_stock > 0)
            OR
            (supplier_id = 0 AND (SELECT COUNT(id) FROM `product_stock` WHERE `product_code` = products.code) > 0)
        )";
    }
    $where .= " AND `name` LIKE ?";
    // Escape wildcard trong keyword để user gõ "_" hoặc "%" không bị dùng như LIKE pattern
    $safe_keyword = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $keyword);
    $like_param = '%' . $safe_keyword . '%';

    // Đếm tổng kết quả
    $total = $bot->CMSNT->num_rows_safe("SELECT * FROM `products` WHERE {$where}", [$like_param]);
    $total_pages = max(1, (int) ceil($total / $limit));
    if ($page > $total_pages) $page = $total_pages;
    $offset = ($page - 1) * $limit;

    // Lấy danh sách sản phẩm (chỉ select fields cần thiết, giống tgshop_shop_products)
    $products = $bot->CMSNT->get_list_safe(
        "SELECT `id`, `name`, `price`, `discount`, `short_desc`, `sold`, `code`, `supplier_id`, `api_stock`
         FROM `products` WHERE {$where} ORDER BY `stt` DESC LIMIT ?, ?",
        [$like_param, $offset, $limit]
    );

    // Header
    $safe_keyword_display = htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8');
    $text  = sprintf(__('🔍 <b>Kết quả tìm: %s</b>'), $safe_keyword_display) . "\n";
    $text .= sprintf(__('📄 Trang %s/%s (%s sản phẩm)'), $page, $total_pages, $total) . "\n";
    $text .= "━━━━━━━━━━━━━━━━━━\n\n";

    if (empty($products)) {
        $text .= __('<i>Không tìm thấy sản phẩm nào khớp với từ khoá này.</i>') . "\n";
        $text .= __('<i>Thử dùng từ khoá khác hoặc xem danh mục sản phẩm.</i>');

        $keyboard = TelegramShopBot::inlineKeyboard([
            [
                ['text' => __('🔁 Tìm từ khoá khác'), 'data' => 'shop:search']
            ],
            [
                TelegramShopBot::backButton('shop:categories', __('📂 Danh mục')),
                TelegramShopBot::backButton('menu:main', __('🏠 Menu chính'))
            ]
        ]);
        $bot->reply($text, $keyboard);
        return;
    }

    // Render từng sản phẩm — logic giống tgshop_shop_products để đồng nhất UX
    foreach ($products as $product) {
        $stock = $product['supplier_id'] != 0 ? $product['api_stock'] : getStock($product['code']);

        $original_price = $product['price'];
        $final_price = $original_price - $original_price * $product['discount'] / 100;

        $stock_icon = $stock > 0 ? '🟢' : '🔴';
        $stock_label = $stock > 0 ? format_cash($stock) : __('Hết hàng');

        $text .= "<b>V{$product['id']} | {$product['name']}</b>\n";
        $text .= "   💰 " . format_currency($final_price);
        if ($product['discount'] > 0) {
            $text .= " <s>" . format_currency($original_price) . "</s> (-{$product['discount']}%)";
        }
        $text .= "\n";
        $text .= sprintf(__('   %s Kho: %s'), $stock_icon, $stock_label);
        // Hiển thị số đã bán phụ thuộc vào cài đặt product_sold_display trong admin
        if ($bot->CMSNT->site('product_sold_display') == 1) {
            $text .= ' | ' . sprintf(__('📦 Đã bán: %s'), format_cash($product['sold']));
        }
        $text .= "\n\n";
    }

    // Nút từng sản phẩm (dùng lại format giống tgshop_shop_products)
    $product_buttons = [];
    foreach ($products as $product) {
        $stock = $product['supplier_id'] != 0 ? $product['api_stock'] : getStock($product['code']);
        $prefix = ($stock > 0 ? '🛒 ' : '🚫 ') . 'V' . $product['id'] . ' | ';
        $max_name_len = 30 - mb_strlen('V' . $product['id'] . ' | ', 'UTF-8');
        $label = $prefix . mb_substr($product['name'], 0, $max_name_len, 'UTF-8');
        if (mb_strlen($product['name'], 'UTF-8') > $max_name_len) {
            $label .= '...';
        }
        $product_buttons[] = [
            ['text' => $label, 'data' => 'shop:detail:' . $product['id']]
        ];
    }

    // Nút phân trang dẫn về shop:search_page (keyword đọc từ DB nên không cần nhét vào callback)
    $nav_buttons = [];
    if ($page > 1) {
        $nav_buttons[] = ['text' => __('◀️ Trước'), 'data' => 'shop:search_page:' . ($page - 1)];
    }
    $nav_buttons[] = ['text' => sprintf(__('📄 %s/%s'), $page, $total_pages), 'data' => 'shop:search_page:' . $page];
    if ($page < $total_pages) {
        $nav_buttons[] = ['text' => __('Sau ▶️'), 'data' => 'shop:search_page:' . ($page + 1)];
    }

    // Gộp nút: sản phẩm → pagination → tìm lại + menu
    $all_buttons = array_merge(
        $product_buttons,
        [$nav_buttons],
        [
            [
                ['text' => __('🔁 Tìm từ khoá khác'), 'data' => 'shop:search']
            ],
            [
                TelegramShopBot::backButton('shop:categories', __('📂 Danh mục')),
                TelegramShopBot::backButton('menu:main', __('🏠 Menu chính'))
            ]
        ]
    );

    $keyboard = TelegramShopBot::inlineKeyboard($all_buttons);
    $bot->reply($text, $keyboard);
}
