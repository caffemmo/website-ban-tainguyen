<?php

define("IN_SITE", true);
require_once(__DIR__ . '/../../libs/db.php');
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../libs/lang.php');
require_once(__DIR__ . '/../../libs/helper.php');
require_once(__DIR__ . '/../../libs/suppliers.php');
$CMSNT = new DB();

// Nếu có đặt key cron job thì kiểm tra key hợp lệ
if (!empty($CMSNT->site('key_cron_job'))) {
    if (empty($_GET['key']) || $_GET['key'] != $CMSNT->site('key_cron_job')) {
        die(__('Key không hợp lệ'));
    }
}

/* START CHỐNG SPAM - Ngăn gọi cron quá nhanh (< 5 giây) */
$elapsed = time() - (int)$CMSNT->site('time_cron_suppliers_api43');
if ($elapsed >= 0 && $elapsed < 5) {
    die('Thao tác quá nhanh, vui lòng thử lại sau!');
}
$CMSNT->update("settings", [
    'value' => time()
], " `name` = 'time_cron_suppliers_api43' ");

foreach ($CMSNT->get_list_safe(" SELECT * FROM `suppliers` WHERE `status` = ? AND `type` = ? ", [1, 'API_43']) as $supplier) {
    // HEALTH CHECK API 
    if (!empty($supplier['token'])) {
        $result1 = balance_API_43($supplier['domain'], $supplier['token'], $supplier['proxy']);
        $result = json_decode($result1, true);

        if (isset($result['profile']['balance']) || isset($result['wallet']['balance'])) {
            $balance = isset($result['profile']['balance']) ? $result['profile']['balance'] : $result['wallet']['balance'];
            $CMSNT->update('suppliers', [
                'price' => format_currency(check_string($balance)),
                'update_gettime' => gettime()
            ], " `id` = ? ", [$supplier['id']]);
        } else {
            $CMSNT->update('suppliers', [
                'price' => 'Kết nối đến API không thành công!',
                'update_gettime' => gettime()
            ], " `id` = ? ", [$supplier['id']]);
        }
    }


    // ĐỒNG BỘ SẢN PHẨM 
    // Response: {"success":true,"products":[{id,name,category_id,category_name,keyword,subtitle,description,stock,price,...}]}
    $hasData = false; // Cờ đánh dấu đã nhận được dữ liệu thành công (Anti-Nuke)
    $result = listProduct_API_43($supplier['domain'], $supplier['token'], $supplier['proxy']);
    $data = json_decode($result, true);

    // Anti-Nuke: Chỉ xử lý khi API trả về thành công VÀ có dữ liệu
    if (isset($data['success']) && $data['success'] == true && isset($data['products']) && is_array($data['products']) && !empty($data['products'])) {
        $hasData = true;

        foreach ($data['products'] as $product_api) {
            $api_id    = check_string(trim($product_api['id']));
            $api_name  = trim($product_api['name']);
            $api_name  = $supplier['check_string_api'] == 'OFF' ? $api_name : check_string($api_name);

            // Map description
            $api_desc  = isset($product_api['description']) ? trim($product_api['description']) : '';
            if (empty($api_desc) && isset($product_api['subtitle'])) {
                $api_desc = trim($product_api['subtitle']);
            }
            $api_desc = $supplier['check_string_api'] == 'OFF' ? $api_desc : check_string($api_desc);

            $api_stock = isset($product_api['stock']) ? intval($product_api['stock']) : 0;
            $api_price = isset($product_api['price']) ? floatval($product_api['price']) : 0;

            // Áp dụng tỷ giá nếu có (dùng để chuyển USD → VND vì API trả giá USD)
            if (!empty($supplier['rate']) && $supplier['rate'] != 1) {
                $api_price = $api_price * $supplier['rate'];
            }

            // Tính giá bán = giá gốc + % tăng giá
            $ck = $api_price * $supplier['discount'] / 100;
            $price = $api_price;
            if ($supplier['update_price'] == 'ON') {
                if ($supplier['roundMoney'] == 'ON') {
                    $price = roundMoney($api_price + $ck);
                } else {
                    $price = $api_price + $ck;
                }
            }

            if (!$product = $CMSNT->get_row_safe(" SELECT * FROM `products` WHERE `api_id` = ? AND `supplier_id` = ? ", [$api_id, $supplier['id']])) {
                // THÊM SẢN PHẨM MỚI
                $product_status = (isset($supplier['isAutoShow']) && $supplier['isAutoShow'] == 1) ? 1 : 0;
                $CMSNT->insert('products', [
                    'user_id'           => $supplier['user_id'],
                    'category_id'       => 0,
                    'supplier_id'       => $supplier['id'],
                    'name'              => $api_name,
                    'slug'              => create_slug($api_name . $api_id),
                    'short_desc'        => $api_desc,
                    'price'             => $price,
                    'status'            => $product_status,
                    'cost'              => $api_price,
                    'api_id'            => $api_id,
                    'api_name'          => $api_name,
                    'api_stock'         => $api_stock,
                    'api_time_update'   => time(),
                    'create_gettime'    => gettime(),
                    'update_gettime'    => gettime()
                ]);
                if ($CMSNT->site('debug_api_suppliers') == 1) {
                    echo '<b style="color:red;">CREATE</b> - [API_43] Tạo sản phẩm ' . $api_name . ' thành công !<br>';
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
                // Giữ nguyên tên/mô tả nếu cài đặt update_name = OFF
                if ($supplier['update_name'] == 'OFF') {
                    $product_name = $product['name'];
                    $product_desc = $product['short_desc'];
                    $product_slug = $product['slug'];
                }
                $CMSNT->update('products', [
                    'price'             => $price,
                    'name'              => $product_name,
                    'slug'              => $product_slug,
                    'short_desc'        => $product_desc,
                    'cost'              => $api_price,
                    'api_name'          => $api_name,
                    'api_time_update'   => time(),
                    'api_stock'         => $api_stock
                ], " `id` = '" . $product['id'] . "' ");
                if ($CMSNT->site('debug_api_suppliers') == 1) {
                    echo '<b style="color:green;">UPDATE</b> - [API_43] sản phẩm ' . $api_name . ' thành công !<br>';
                }
            }
        } // end foreach data
    }

    // Xóa sản phẩm không còn tồn tại trên API sau 1 giờ
    // Chỉ xóa KHI đã lấy được dữ liệu thành công (Anti-Nuke Pattern)
    if ($hasData) {
        $CMSNT->remove('products', " `supplier_id` = '" . $supplier['id'] . "' AND " . time() . " - `api_time_update` >= 3600 ");
    }
}
