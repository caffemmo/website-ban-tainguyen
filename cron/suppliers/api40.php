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
$elapsed = time() - (int)$CMSNT->site('time_cron_suppliers_api40');
    if ($elapsed >= 0 && $elapsed < 5) {
        die('Thao tác quá nhanh, vui lòng thử lại sau!');
    }
$CMSNT->update("settings", [
    'value' => time()
], " `name` = 'time_cron_suppliers_api40' ");



foreach ($CMSNT->get_list_safe(" SELECT * FROM `suppliers` WHERE `status` = ? AND `type` = ? ", [1, 'API_40']) as $supplier) {
    // CẬP NHẬT SỐ DƯ API - Gọi POST /api/balance
    if (!empty($supplier['api_key'])) {
        $result1 = balance_API_40($supplier['domain'], $supplier['api_key'], $supplier['proxy']);
        $result = json_decode($result1, true);
        if (isset($result['success']) && $result['success'] == true) {
            // Số dư nằm trực tiếp trong $result['balance']
            $CMSNT->update('suppliers', [
                'price' => format_currency(check_string($result['balance'])),
                'update_gettime'    => gettime()
            ], " `id` = ? ", [$supplier['id']]);
        } else {
            $errorMsg = isset($result['message']) ? $result['message'] : 'Kết nối đến API không thành công!';
            $CMSNT->update('suppliers', [
                'price' => check_string($errorMsg),
                'update_gettime'    => gettime()
            ], " `id` = ? ", [$supplier['id']]);
        }
    }


    // CURL LẤY SẢN PHẨM - GET /api/services
    // Response có cấu trúc phân cấp: category → position
    $result = listProduct_API_40($supplier['domain'], $supplier['api_key'], $supplier['proxy']);
    $data = json_decode($result, true);

    // Anti-Nuke: Chỉ xử lý khi API trả về thành công VÀ có dữ liệu
    // Tránh xóa toàn bộ sản phẩm khi API gặp sự cố
    if (isset($data['success']) && $data['success'] == true && !empty($data['category'])) {

        // Duyệt từng category trong response
        foreach ($data['category'] as $category) {
            // Lấy tên chuyên mục từ API (dùng làm mô tả ngắn cho sản phẩm)
            $category_name = isset($category['category_name']) ? $category['category_name'] : '';
            $category_id_api = isset($category['category_id']) ? $category['category_id'] : 0;

            // Chưa hỗ trợ đồng bộ chuyên mục tự động — mặc định category_id = 0
            $category_id = 0;

            // Duyệt từng position (sản phẩm) trong category
            if (!empty($category['position'])) {
                foreach ($category['position'] as $position) {
                    // position_id = api_id, position_name = tên sản phẩm
                    $api_id = check_string(trim($position['position_id']));
                    $api_name = trim($position['position_name']);
                    $api_name = $supplier['check_string_api'] == 'OFF' ? $api_name : check_string($api_name);

                    $api_stock = isset($position['stock']) ? intval($position['stock']) : 0;
                    $api_price = isset($position['position_price']) ? floatval($position['position_price']) : 0;

                    // Tạo mô tả ngắn từ tên chuyên mục + loại sản phẩm
                    $api_desc = $category_name;

                    // Áp dụng tỷ giá nếu có (dùng cho chuyển đổi tiền tệ)
                    if (!empty($supplier['rate']) && $supplier['rate'] != 1) {
                        $api_price = $api_price * $supplier['rate'];
                    }

                    // Tính giá bán = giá gốc + % tăng giá
                    $ck = $api_price * $supplier['discount'] / 100;
                    $price = $api_price;
                    if ($supplier['update_price'] == 'ON') {
                        // CẬP NHẬT GIÁ BÁN
                        if ($supplier['roundMoney'] == 'ON') {
                            // LÀM TRÒN GIÁ BÁN
                            $price = roundMoney($api_price + $ck);
                        } else {
                            $price = $api_price + $ck;
                        }
                    }

                    if (!$product = $CMSNT->get_row_safe(" SELECT * FROM `products` WHERE `api_id` = ? AND `supplier_id` = ? ", [$api_id, $supplier['id']])) {
                        // THÊM SẢN PHẨM MỚI
                        // Xác định trạng thái sản phẩm dựa vào isAutoShow
                        $product_status = (isset($supplier['isAutoShow']) && $supplier['isAutoShow'] == 1) ? 1 : 0;
                        $CMSNT->insert('products', [
                            'user_id'           => $supplier['user_id'],
                            'category_id'       => $category_id,
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
                            'update_gettime'   => gettime()
                        ]);
                        if ($CMSNT->site('debug_api_suppliers') == 1) {
                            echo '<b style="color:red;">CREATE</b> - Tạo sản phẩm ' . $api_name . ' thành công !<br>';
                        }
                    } else {
                        // CẬP NHẬT SẢN PHẨM ĐÃ TỒN TẠI
                        $price = $product['price'];
                        if ($supplier['update_price'] == 'ON') {
                            // CẬP NHẬT GIÁ BÁN
                            if ($supplier['roundMoney'] == 'ON') {
                                // LÀM TRÒN GIÁ BÁN
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
                            echo '<b style="color:green;">UPDATE</b> - sản phẩm ' . $api_name . ' thành công !<br>';
                        }
                    }
                }
            }
        }

        // Xóa sản phẩm không còn tồn tại trên API sau 1 giờ
        // Chỉ xóa KHI đã lấy được dữ liệu thành công (Anti-Nuke Pattern)
        $CMSNT->remove('products', " `supplier_id` = '" . $supplier['id'] . "' AND " . time() . " - `api_time_update` >= 3600 ");
    }
}
