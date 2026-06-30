<?php

define("IN_SITE", true);
// Gọi các file cấu hình và thư viện
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

/* CHỐNG SPAM CRON: chỉ chặn nếu lần chạy trước cách đây < 5s (so sánh elapsed không âm) */
$elapsed = time() - (int)$CMSNT->site('time_cron_suppliers_api48');
if ($elapsed >= 0 && $elapsed < 5) {
    die('Thao tác quá nhanh, vui lòng thử lại sau!');
}
$CMSNT->update("settings", [
    'value' => time()
], " `name` = 'time_cron_suppliers_api48' ");


foreach ($CMSNT->get_list_safe(" SELECT * FROM `suppliers` WHERE `status` = ? AND `type` = ? ", [1, 'API_48']) as $supplier) {

    // 1. CẬP NHẬT SỐ DƯ API
    if (!empty($supplier['api_key'])) {
        $result1 = balance_API_48($supplier['domain'], $supplier['api_key'], $supplier['proxy']);
        $result = json_decode($result1, true);
        // APIv7: status=success + data.money chứa số dư
        if (isset($result['status']) && $result['status'] == 'success' && isset($result['data']['money'])) {
            $CMSNT->update('suppliers', [
                'price' => format_currency(check_string($result['data']['money'])),
                'update_gettime' => gettime()
            ], " `id` = ? ", [$supplier['id']]);
        } else {
            $errorMsg = isset($result['msg']) ? $result['msg'] : 'Kết nối đến API không thành công!';
            $CMSNT->update('suppliers', [
                'price' => check_string($errorMsg),
                'update_gettime' => gettime()
            ], " `id` = ? ", [$supplier['id']]);
        }
    }

    // 2. LẤY DANH SÁCH SẢN PHẨM
    $result = listProduct_API_48($supplier['domain'], $supplier['api_key'], $supplier['proxy']);
    $result = json_decode($result, true);

    if (isset($result['status']) && $result['status'] == 'success' && !empty($result['categories'])) {

        // APIv7 trả về cấu trúc flat: categories[] -> products[] (không có chuyên mục cha)
        foreach ($result['categories'] as $category) {

            // 2.1. ĐỒNG BỘ CHUYÊN MỤC NẾU BẬT
            $category_id = 0;
            if ($supplier['sync_category'] == 'ON') {
                $category_name = validate_string($category['name'], 255, 1);
                if ($category_name === false) continue;

                $category_api_id = validate_alphanumeric($category['id'], 50);
                if ($category_api_id === false) continue;

                // Tìm chuyên mục theo name để tránh trùng lặp khi đổi sang supplier khác
                if (!$category_api = $CMSNT->get_row_safe(" SELECT * FROM `categories` WHERE `name` = ? ", [$category_name])) {
                    // Tạo mới chuyên mục: tải icon từ API nếu được bật sync_category_image
                    $rand = '_' . random('QWERTTYUIOPASDFGHJKLZXCVBNM123456789', 6);
                    $uploads_dir = '../../assets/storage/images/category' . $rand . '.png';
                    $url_image = $CMSNT->site('favicon'); // Fallback icon mặc định
                    if (isset($supplier['sync_category_image']) && $supplier['sync_category_image'] == 'ON' && !empty($category['icon'])) {
                        $image = @imagecreatefrompng($category['icon']);
                        if ($image) {
                            if (imagepng($image, $uploads_dir)) {
                                $url_image = 'assets/storage/images/category' . $rand . '.png';
                            }
                            imagedestroy($image);
                        }
                    }
                    $isInsert = $CMSNT->insert('categories', [
                        'parent_id'         => 1,
                        'id_api'            => $category_api_id,
                        'supplier_id'       => $supplier['id'],
                        'status'            => 1,
                        'name'              => $category_name,
                        'slug'              => create_slug($category_name),
                        'icon'              => $url_image,
                        'create_date'       => gettime(),
                        'api_time_update'   => time()
                    ]);
                    if ($isInsert) {
                        $category_id = $isInsert;
                        if ($CMSNT->site('debug_api_suppliers') == 1) {
                            echo '<b style="color:red;">CREATE</b> - Tạo chuyên mục ' . $category_name . ' thành công !<br>';
                        }
                    }
                } else {
                    $category_id = $category_api['id'];
                    // Cập nhật api_time_update để không bị xóa khi dọn dẹp định kỳ
                    $CMSNT->update('categories', [
                        'name'              => $category_name,
                        'slug'              => create_slug($category_name),
                        'api_time_update'   => time()
                    ], " `id` = ? ", [$category_id]);
                    if ($CMSNT->site('debug_api_suppliers') == 1) {
                        echo '<b style="color:blue;">UPDATE</b> - Cập nhật chuyên mục "' . $category_name . '" !<br>';
                    }
                }
            }

            // 2.2. ĐỒNG BỘ SẢN PHẨM CỦA CHUYÊN MỤC
            if (!empty($category['products']) && is_array($category['products'])) {
                foreach ($category['products'] as $api) {

                    $api_id = validate_alphanumeric($api['id'], 100);
                    if ($api_id === false) continue;

                    $api_name = $supplier['check_string_api'] == 'OFF'
                        ? $api['name']
                        : validate_string($api['name'], 500, 1);
                    // APIv7 dùng `description` cho mô tả ngắn (theo tài liệu)
                    $api_desc_raw = isset($api['description']) ? $api['description'] : '';
                    $api_desc = $supplier['check_string_api'] == 'OFF'
                        ? $api_desc_raw
                        : validate_string($api_desc_raw, 5000);
                    // APIv7 dùng `amount` cho tồn kho
                    $api_stock = validate_int($api['amount'], 0);
                    $api_price = validate_float($api['price'], 0);

                    if ($api_name === false || $api_stock === false || $api_price === false) continue;

                    // Quy đổi rate tiền tệ nếu có (vd: USD -> VND)
                    if (isset($supplier['rate']) && $supplier['rate'] != 1 && $supplier['rate'] > 0) {
                        $api_price = $api_price * $supplier['rate'];
                    }
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
                        // THÊM SẢN PHẨM MỚI: status mặc định theo isAutoShow của supplier
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
                            'update_gettime'    => gettime()
                        ]);
                        if ($CMSNT->site('debug_api_suppliers') == 1) {
                            echo '<b style="color:red;">CREATE</b> - Tạo sản phẩm ' . $api_name . ' thành công !<br>';
                        }
                    } else {
                        // CẬP NHẬT SẢN PHẨM: giữ giá hiện tại nếu update_price = OFF
                        $price = $product['price'];
                        if ($supplier['update_price'] == 'ON') {
                            if ($supplier['roundMoney'] == 'ON') {
                                $price = roundMoney($api_price + $ck);
                            } else {
                                $price = $api_price + $ck;
                            }
                        }
                        // Giữ tên/desc/slug cũ nếu admin tắt update_name (đã tự sửa tay)
                        $product_name = $api_name;
                        $product_desc = $api_desc;
                        $product_slug = create_slug($product_name . $api_id);
                        if ($supplier['update_name'] == 'OFF') {
                            $product_name = $product['name'];
                            $product_desc = $product['short_desc'];
                            $product_slug = $product['slug'];
                        }

                        $update_data = [
                            'price'             => $price,
                            'name'              => $product_name,
                            'slug'              => $product_slug,
                            'short_desc'        => $product_desc,
                            'cost'              => $api_price,
                            'api_name'          => $api_name,
                            'api_time_update'   => time(),
                            'api_stock'         => $api_stock
                        ];
                        // Chỉ cập nhật category_id khi đang bật sync_category để tránh ghi đè vị trí admin đã chọn
                        if ($supplier['sync_category'] == 'ON' && $category_id > 0) {
                            $update_data['category_id'] = $category_id;
                        }
                        $CMSNT->update('products', $update_data, " `id` = ? ", [$product['id']]);
                        if ($CMSNT->site('debug_api_suppliers') == 1) {
                            echo '<b style="color:green;">UPDATE</b> - sản phẩm ' . $api_name . ' thành công !<br>';
                        }
                    }
                }
            }
        }

        // 3. DỌN DẸP SẢN PHẨM/CHUYÊN MỤC KHÔNG CÒN TỒN TẠI TRÊN API SAU 1 GIỜ
        $current_time = time();

        // Xóa ảnh sản phẩm trước khi xóa khỏi DB để không để rác trên ổ đĩa
        $products_to_delete = $CMSNT->get_list_safe(" SELECT * FROM `products` WHERE `supplier_id` = ? AND ? - `api_time_update` >= 3600 ", [$supplier['id'], $current_time]);
        foreach ($products_to_delete as $product) {
            if (!empty($product['images'])) {
                $images = explode(PHP_EOL, trim($product['images']));
                foreach ($images as $filename) {
                    $filename = trim($filename);
                    if (!empty($filename)) {
                        $file_path = __DIR__ . "/../../" . dirImageProduct($filename);
                        if (file_exists($file_path)) {
                            unlink($file_path);
                        }
                    }
                }
            }
        }

        // Xóa icon chuyên mục (chỉ áp dụng khi bật sync_category để tránh xóa nhầm)
        if ($supplier['sync_category'] == 'ON') {
            $categories_to_delete = $CMSNT->get_list_safe(" SELECT * FROM `categories` WHERE `supplier_id` = ? AND ? - `api_time_update` >= 3600 ", [$supplier['id'], $current_time]);
            foreach ($categories_to_delete as $category) {
                if (!empty($category['icon'])) {
                    $iconPath = __DIR__ . "/../../" . $category['icon'];
                    if (file_exists($iconPath)) {
                        unlink($iconPath);
                    }
                }
            }
        }

        $CMSNT->remove('products', " `supplier_id` = ? AND ? - `api_time_update` >= 3600 ", [$supplier['id'], $current_time]);
        if ($supplier['sync_category'] == 'ON') {
            $CMSNT->remove('categories', " `supplier_id` = ? AND ? - `api_time_update` >= 3600 ", [$supplier['id'], $current_time]);
        }
    } else {
        // API lỗi: ghi log debug để admin biết cần kiểm tra api_key/domain
        if ($CMSNT->site('debug_api_suppliers') == 1) {
            $errorMsg = isset($result['msg']) ? $result['msg'] : 'Không lấy được danh sách sản phẩm từ API';
            echo '<b style="color:red;">ERROR</b> - Supplier #' . $supplier['id'] . ': ' . htmlspecialchars($errorMsg) . '<br>';
        }
    }
}
