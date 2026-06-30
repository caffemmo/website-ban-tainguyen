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
$elapsed = time() - (int)$CMSNT->site('time_cron_suppliers_api41');
if ($elapsed >= 0 && $elapsed < 5) {
    die('Thao tác quá nhanh, vui lòng thử lại sau!');
}
$CMSNT->update("settings", [
    'value' => time()
], " `name` = 'time_cron_suppliers_api41' ");



foreach ($CMSNT->get_list_safe(" SELECT * FROM `suppliers` WHERE `status` = ? AND `type` = ? ", [1, 'API_41']) as $supplier) {
    // CẬP NHẬT SỐ DƯ API — GET /v1/account/balance
    if (!empty($supplier['api_key'])) {
        $result1 = balance_API_41($supplier['domain'], $supplier['api_key'], $supplier['proxy']);
        $result = json_decode($result1, true);
        if (isset($result['success']) && $result['success'] == true) {
            // Số dư nằm trong $result['data']['balance']
            $balance = isset($result['data']['balance']) ? $result['data']['balance'] : 0;
            $CMSNT->update('suppliers', [
                'price' => format_currency(check_string($balance)),
                'update_gettime'    => gettime()
            ], " `id` = ? ", [$supplier['id']]);
        } else {
            $errorMsg = isset($result['error']) ? $result['error'] : (isset($result['message']) ? $result['message'] : 'Kết nối đến API không thành công!');
            $CMSNT->update('suppliers', [
                'price' => check_string($errorMsg),
                'update_gettime'    => gettime()
            ], " `id` = ? ", [$supplier['id']]);
        }
    }


    // ĐỒNG BỘ SẢN PHẨM — GET /v1/products (hỗ trợ pagination)
    // Response: {"success":true,"data":{"items":[...],"total":N,"page":N,"totalPages":N}}
    $page = 1;
    $hasData = false; // Cờ đánh dấu đã nhận được dữ liệu thành công (Anti-Nuke)

    // Duyệt qua từng trang sản phẩm — tối đa 100 sản phẩm/trang
    do {
        $hasMore = false;
        $result = listProduct_API_41($supplier['domain'], $supplier['api_key'], $supplier['proxy'], $page, 100);
        $data = json_decode($result, true);

        // Anti-Nuke: Chỉ xử lý khi API trả về thành công VÀ có dữ liệu
        // Sản phẩm nằm trong data.items[] (không phải data[] trực tiếp)
        if (isset($data['success']) && $data['success'] == true && isset($data['data']['items']) && !empty($data['data']['items'])) {
            $hasData = true;
            $products = $data['data']['items'];

            foreach ($products as $product_api) {
                // Lấy tên danh mục làm mô tả ngắn (short_desc)
                $api_desc = '';
                if (isset($product_api['category']) && isset($product_api['category']['name'])) {
                    $api_desc = $product_api['category']['name'];
                }

                $base_product_id = check_string(trim($product_api['id']));
                $base_product_name = trim($product_api['name']);
                $base_product_name = $supplier['check_string_api'] == 'OFF' ? $base_product_name : check_string($base_product_name);

                // Xây dựng danh sách items cần đồng bộ
                // Nếu sản phẩm có variants → mỗi variant thành 1 sản phẩm riêng
                // api_id = "productId|variantId" — khi mua sẽ tách ra gửi cả 2 field
                // Nếu không có variants → giữ nguyên api_id = productId
                $items_to_sync = [];
                if (isset($product_api['variants']) && is_array($product_api['variants']) && !empty($product_api['variants'])) {
                    // Sản phẩm có variants — tạo 1 row per variant
                    foreach ($product_api['variants'] as $variant) {
                        $variant_id = check_string(trim($variant['id']));
                        $variant_name = isset($variant['name']) ? trim($variant['name']) : '';
                        $items_to_sync[] = [
                            'api_id'    => $base_product_id . '|' . $variant_id,
                            'api_name'  => $base_product_name . ($variant_name ? ' - ' . ($supplier['check_string_api'] == 'OFF' ? $variant_name : check_string($variant_name)) : ''),
                            'api_stock' => isset($variant['stockCount']) ? intval($variant['stockCount']) : 0,
                            'api_price' => isset($variant['price']) ? floatval($variant['price']) : 0,
                        ];
                    }
                } else {
                    // Sản phẩm không có variants — đồng bộ bình thường
                    $items_to_sync[] = [
                        'api_id'    => $base_product_id,
                        'api_name'  => $base_product_name,
                        'api_stock' => isset($product_api['stockCount']) ? intval($product_api['stockCount']) : 0,
                        'api_price' => isset($product_api['price']) ? floatval($product_api['price']) : 0,
                    ];
                }

                // Duyệt từng item (sản phẩm hoặc variant) để đồng bộ vào database
                foreach ($items_to_sync as $item) {
                    $api_id = $item['api_id'];
                    $api_name = $item['api_name'];
                    $api_stock = $item['api_stock'];
                    $api_price = $item['api_price'];

                    // Áp dụng tỷ giá nếu có (dùng cho chuyển đổi tiền tệ quốc tế)
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
                            echo '<b style="color:red;">CREATE</b> - [API_41] Tạo sản phẩm ' . $api_name . ' thành công !<br>';
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
                            'price'         => $price,
                            'name'          => $product_name,
                            'slug'          => $product_slug,
                            'short_desc'    => $product_desc,
                            'cost'          => $api_price,
                            'api_name'      => $api_name,
                            'api_time_update'    => time(),
                            'api_stock'     => $api_stock
                        ], " `id` = '" . $product['id'] . "' ");
                        if ($CMSNT->site('debug_api_suppliers') == 1) {
                            echo '<b style="color:green;">UPDATE</b> - [API_41] sản phẩm ' . $api_name . ' thành công !<br>';
                        }
                    }
                } // end foreach items_to_sync
            } // end foreach products

            // Kiểm tra có trang tiếp theo không — dùng totalPages từ API
            $totalPages = isset($data['data']['totalPages']) ? intval($data['data']['totalPages']) : 1;
            if ($page < $totalPages) {
                $hasMore = true;
                $page++;
            }
        }
    } while ($hasMore);

    // Xóa sản phẩm không còn tồn tại trên API sau 1 giờ
    // Chỉ xóa KHI đã lấy được dữ liệu thành công (Anti-Nuke Pattern)
    if ($hasData) {
        $CMSNT->remove('products', " `supplier_id` = '" . $supplier['id'] . "' AND " . time() . " - `api_time_update` >= 3600 ");
    }
}
