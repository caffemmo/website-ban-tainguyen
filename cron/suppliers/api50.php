<?php

define("IN_SITE", true);
require_once(__DIR__ . '/../../libs/db.php');
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../libs/lang.php');
require_once(__DIR__ . '/../../libs/helper.php');
require_once(__DIR__ . '/../../libs/suppliers.php');
$CMSNT = new DB();

// Kiểm tra key cron nếu được cấu hình
if (!empty($CMSNT->site('key_cron_job'))) {
    if (empty($_GET['key']) || $_GET['key'] != $CMSNT->site('key_cron_job')) {
        die(__('Key không hợp lệ'));
    }
}

/* START CHỐNG SPAM - Ngăn gọi cron quá nhanh (< 5 giây) */
$elapsed = time() - (int)$CMSNT->site('time_cron_suppliers_api50');
if ($elapsed >= 0 && $elapsed < 5) {
    die('Thao tác quá nhanh, vui lòng thử lại sau!');
}
$CMSNT->update("settings", [
    'value' => time()
], " `name` = 'time_cron_suppliers_api50' ");

// Lặp qua tất cả nhà cung cấp API_50 đang hoạt động
foreach ($CMSNT->get_list_safe(" SELECT * FROM `suppliers` WHERE `status` = ? AND `type` = ? ", [1, 'API_50']) as $supplier) {

    // =============================================
    // HEALTH CHECK - Lấy số dư tài khoản
    // Endpoint: GET /api/telegram-buyer/balance?key=xxx
    // =============================================
    if (!empty($supplier['token'])) {
        $result_raw = balance_API_50($supplier['domain'], $supplier['token'], $supplier['proxy']);
        $result = json_decode($result_raw, true);

        // Kiểm tra response hợp lệ
        if (isset($result['success']) && $result['success'] == true && isset($result['balance'])) {
            // Ưu tiên dùng balanceText (có sẵn đơn vị VND/USD) nếu API trả về
            $balance_display = isset($result['balanceText'])
                ? check_string($result['balanceText'])
                : check_string($result['balance']) . ' ' . (isset($result['walletCurrency']) ? $result['walletCurrency'] : '');
            $CMSNT->update('suppliers', [
                'price'          => $balance_display,
                'update_gettime' => gettime()
            ], " `id` = ? ", [$supplier['id']]);
        } else {
            // Ghi nhận lỗi vào cột price để admin biết
            $CMSNT->update('suppliers', [
                'price'          => 'Kết nối đến API không thành công!',
                'update_gettime' => gettime()
            ], " `id` = ? ", [$supplier['id']]);
        }
    }

    // =============================================
    // ĐỒNG BỘ SẢN PHẨM
    // Endpoint: GET /api/telegram-buyer/products
    // Response: {success, products: [{_id, product_name, pricing, walletPricing, walletCurrency, stats: {available}}]}
    // _id là MongoDB ObjectId string — dùng làm api_id khi mua hàng
    // pricing / walletPricing là giá theo walletCurrency (VND hoặc USD tùy bot)
    // =============================================
    $hasData = false; // Cờ Anti-Nuke: chỉ xóa sản phẩm cũ khi đã lấy được data mới hợp lệ
    $result_raw = listProduct_API_50($supplier['domain'], $supplier['token'], $supplier['proxy']);
    $data = json_decode($result_raw, true);

    // Anti-Nuke Guard: chỉ xử lý khi API trả về thành công với danh sách hợp lệ
    if (isset($data['success']) && $data['success'] == true && isset($data['products']) && is_array($data['products']) && !empty($data['products'])) {
        $hasData = true;

        foreach ($data['products'] as $product_api) {
            // _id là MongoDB ObjectId string — dùng làm api_id để tra cứu & mua hàng
            $api_id   = check_string(trim((string)$product_api['_id']));
            $api_name = isset($product_api['product_name']) ? trim($product_api['product_name']) : '';
            $api_name = $supplier['check_string_api'] == 'OFF' ? $api_name : check_string($api_name);

            // Mô tả sản phẩm (nếu có)
            $api_desc = isset($product_api['description']) ? trim($product_api['description']) : '';
            $api_desc = $supplier['check_string_api'] == 'OFF' ? $api_desc : check_string($api_desc);

            // Số lượng còn hàng: stats.available (nullable, có thể là null nếu không giới hạn)
            $api_stock = 0;
            if (isset($product_api['stats']['available']) && $product_api['stats']['available'] !== null) {
                $api_stock = intval($product_api['stats']['available']);
            } elseif (isset($product_api['stats']['total']) && $product_api['stats']['total'] !== null) {
                // Fallback: total - sold nếu available không có
                $sold = isset($product_api['stats']['sold']) ? intval($product_api['stats']['sold']) : 0;
                $api_stock = intval($product_api['stats']['total']) - $sold;
            }

            // Giá: dùng walletPricing (giá theo currency của bot) nếu có, fallback về pricing
            // walletCurrency xác định đây là VND hay USD
            $api_price = 0;
            if (isset($product_api['walletPricing'])) {
                $api_price = floatval($product_api['walletPricing']);
            } elseif (isset($product_api['pricing'])) {
                $api_price = floatval($product_api['pricing']);
            }

            // Áp dụng tỷ giá nếu cần (VD: rate = 25000 khi giá là USD mà hệ thống dùng VND)
            if (!empty($supplier['rate']) && $supplier['rate'] != 1) {
                $api_price = $api_price * floatval($supplier['rate']);
            }

            // Tính giá bán = giá gốc + % markup
            $ck    = $api_price * $supplier['discount'] / 100;
            $price = $api_price;
            if ($supplier['update_price'] == 'ON') {
                if ($supplier['roundMoney'] == 'ON') {
                    $price = roundMoney($api_price + $ck);
                } else {
                    $price = $api_price + $ck;
                }
            }

            // Kiểm tra sản phẩm đã tồn tại chưa (tra cứu bằng api_id + supplier_id)
            if (!$product = $CMSNT->get_row_safe(" SELECT * FROM `products` WHERE `api_id` = ? AND `supplier_id` = ? ", [$api_id, $supplier['id']])) {
                // THÊM SẢN PHẨM MỚI
                $product_status = (isset($supplier['isAutoShow']) && $supplier['isAutoShow'] == 1) ? 1 : 0;
                $CMSNT->insert('products', [
                    'user_id'         => $supplier['user_id'],
                    'category_id'     => 0,
                    'supplier_id'     => $supplier['id'],
                    'name'            => $api_name,
                    'slug'            => create_slug($api_name . $api_id),
                    'short_desc'      => $api_desc,
                    'price'           => $price,
                    'status'          => $product_status,
                    'cost'            => $api_price,
                    'api_id'          => $api_id,
                    'api_name'        => $api_name,
                    'api_stock'       => $api_stock,
                    'api_time_update' => time(),
                    'create_gettime'  => gettime(),
                    'update_gettime'  => gettime()
                ]);
                if ($CMSNT->site('debug_api_suppliers') == 1) {
                    echo '<b style="color:red;">CREATE</b> - [API_50] Tạo sản phẩm ' . $api_name . ' (ID: ' . $api_id . ') thành công!<br>';
                }
            } else {
                // CẬP NHẬT SẢN PHẨM ĐÃ TỒN TẠI
                $price = $product['price'];
                if ($supplier['update_price'] == 'ON') {
                    if ($supplier['roundMoney'] == 'ON') {
                        $price = roundMoney($api_price + $ck);
                    } else {
                        $price = $api_price + $ck;
                    }
                }
                $product_name = $api_name;
                $product_desc = $api_desc;
                $product_slug = create_slug($product_name . $api_id);
                if ($supplier['update_name'] == 'OFF') {
                    // Giữ nguyên tên/mô tả do admin đặt
                    $product_name = $product['name'];
                    $product_desc = $product['short_desc'];
                    $product_slug = $product['slug'];
                }
                $CMSNT->update('products', [
                    'price'           => $price,
                    'name'            => $product_name,
                    'slug'            => $product_slug,
                    'short_desc'      => $product_desc,
                    'cost'            => $api_price,
                    'api_name'        => $api_name,
                    'api_time_update' => time(),
                    'api_stock'       => $api_stock
                ], " `id` = ? ", [$product['id']]);
                if ($CMSNT->site('debug_api_suppliers') == 1) {
                    echo '<b style="color:green;">UPDATE</b> - [API_50] Cập nhật sản phẩm ' . $api_name . ' (ID: ' . $api_id . ') thành công!<br>';
                }
            }
        } // end foreach products
    }

    // =============================================
    // XÓA SẢN PHẨM CŨ KHÔNG CÒN TRÊN API (Anti-Nuke Pattern)
    // Chỉ chạy khi đã lấy được data mới hợp lệ (tránh xóa toàn bộ khi API lỗi tạm thời)
    // =============================================
    if ($hasData) {
        $CMSNT->remove('products', " `supplier_id` = ? AND " . time() . " - `api_time_update` >= 3600 ", [$supplier['id']]);
    }
}
