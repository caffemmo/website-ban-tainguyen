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
$elapsed = time() - (int)$CMSNT->site('time_cron_suppliers_api52');
if ($elapsed >= 0 && $elapsed < 5) {
    die('Thao tác quá nhanh, vui lòng thử lại sau!');
}
$CMSNT->update("settings", [
    'value' => time()
], " `name` = 'time_cron_suppliers_api52' ");

// Lặp qua tất cả nhà cung cấp API_52 đang hoạt động
foreach ($CMSNT->get_list_safe(" SELECT * FROM `suppliers` WHERE `status` = ? AND `type` = ? ", [1, 'API_52']) as $supplier) {

    // =============================================
    // HEALTH CHECK - Lấy số dư tài khoản
    // Endpoint: GET /api/partner/v1/me
    // =============================================
    if (!empty($supplier['api_key'])) {
        $result_raw = balance_API_52($supplier['domain'], $supplier['api_key'], $supplier['proxy']);
        $result = json_decode($result_raw, true);

        // Xác định xem API có kết nối thành công không (success hoặc ok là true)
        $is_ok = false;
        if (isset($result['success']) && $result['success'] == true) {
            $is_ok = true;
        } else if (isset($result['ok']) && $result['ok'] == true) {
            $is_ok = true;
        }

        // Lấy số dư linh hoạt
        $balance = null;
        if (isset($result['balance'])) {
            $balance = $result['balance'];
        } else if (isset($result['data']['wallet']['vnd'])) {
            $balance = $result['data']['wallet']['vnd'];
        } else if (isset($result['data']['wallet']['credit'])) {
            $balance = $result['data']['wallet']['credit'];
        }

        // Kiểm tra response hợp lệ
        if ($is_ok && $balance !== null) {
            $balance_display = isset($result['balanceText'])
                ? check_string($result['balanceText'])
                : format_currency(check_string($balance));
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
    // Endpoint: GET /api/partner/v1/products
    // =============================================
    $hasData = false; // Cờ Anti-Nuke: chỉ xóa sản phẩm cũ khi đã lấy được data mới hợp lệ
    $result_raw = listProduct_API_52($supplier['domain'], $supplier['api_key'], $supplier['proxy']);
    $data = json_decode($result_raw, true);

    $products = [];
    if (isset($data['products']) && is_array($data['products'])) {
        $products = $data['products'];
    } else if (isset($data['data']) && is_array($data['data'])) {
        $products = $data['data'];
    } else if (is_array($data) && isset($data[0])) {
        $products = $data;
    }

    // Anti-Nuke Guard: chỉ xử lý khi API trả về thành công với danh sách hợp lệ
    if (!empty($products) && is_array($products)) {
        $hasData = true;

        foreach ($products as $product_api) {
            // ID sản phẩm
            $api_id = '';
            if (isset($product_api['id'])) {
                $api_id = check_string(trim((string)$product_api['id']));
            } else if (isset($product_api['productId'])) {
                $api_id = check_string(trim((string)$product_api['productId']));
            } else if (isset($product_api['code'])) {
                $api_id = check_string(trim((string)$product_api['code']));
            }

            if (empty($api_id)) {
                continue;
            }

            // Tên sản phẩm
            $api_name = '';
            if (isset($product_api['name'])) {
                $api_name = trim($product_api['name']);
            } else if (isset($product_api['title'])) {
                $api_name = trim($product_api['title']);
            }
            $api_name = $supplier['check_string_api'] == 'OFF' ? $api_name : check_string($api_name);

            // Mô tả sản phẩm
            $api_desc = '';
            if (isset($product_api['description'])) {
                $api_desc = trim($product_api['description']);
            } else if (isset($product_api['desc'])) {
                $api_desc = trim($product_api['desc']);
            }
            $api_desc = $supplier['check_string_api'] == 'OFF' ? $api_desc : check_string($api_desc);

            // Số lượng còn hàng
            $api_stock = 0;
            if (isset($product_api['stock']) && $product_api['stock'] !== null) {
                $api_stock = intval($product_api['stock']);
            } else if (isset($product_api['quantity']) && $product_api['quantity'] !== null) {
                $api_stock = intval($product_api['quantity']);
            } else if (isset($product_api['in_stock']) && $product_api['in_stock'] !== null) {
                $api_stock = intval($product_api['in_stock']);
            } else if (isset($product_api['inStock']) && $product_api['inStock'] === true) {
                $api_stock = 999;
            }

            // Giá
            $api_price = 0;
            if (isset($product_api['priceVnd']) && $product_api['priceVnd'] > 0) {
                $api_price = floatval($product_api['priceVnd']);
            } else if (isset($product_api['price'])) {
                $api_price = floatval($product_api['price']);
            } else if (isset($product_api['priceCredit']) && $product_api['priceCredit'] > 0) {
                $api_price = floatval($product_api['priceCredit']);
            } else if (isset($product_api['cost'])) {
                $api_price = floatval($product_api['cost']);
            }

            // Áp dụng tỷ giá nếu cần
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
                    echo '<b style="color:red;">CREATE</b> - [API_52] Tạo sản phẩm ' . $api_name . ' (ID: ' . $api_id . ') thành công!<br>';
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
                    echo '<b style="color:green;">UPDATE</b> - [API_52] Cập nhật sản phẩm ' . $api_name . ' (ID: ' . $api_id . ') thành công!<br>';
                }
            }
        } // end foreach products
    }

    // =============================================
    // XÓA SẢN PHẨM CŨ KHÔNG CÒN TRÊN API (Anti-Nuke Pattern)
    // Chỉ chạy khi đã lấy được data mới hợp lệ
    // =============================================
    if ($hasData) {
        $CMSNT->remove('products', " `supplier_id` = ? AND " . time() . " - `api_time_update` >= 3600 ", [$supplier['id']]);
    }
}
