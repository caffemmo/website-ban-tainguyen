<?php

// Định nghĩa để cho phép truy cập file cấu hình và core
define("IN_SITE", true);
require_once(__DIR__ . '/../../libs/db.php');
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../libs/lang.php');
require_once(__DIR__ . '/../../libs/helper.php');
require_once(__DIR__ . '/../../libs/suppliers.php');

$CMSNT = new DB();

// Kiểm tra mã khóa bảo vệ cron job (nếu có cấu hình trong cài đặt site)
if (!empty($CMSNT->site('key_cron_job'))) {
    if (empty($_GET['key']) || $_GET['key'] != $CMSNT->site('key_cron_job')) {
        die(__('Key không hợp lệ'));
    }
}

/* CHỐNG SPAM CRON - Giới hạn tần suất gọi cron, tối thiểu 5 giây một lần */
$elapsed = time() - (int)$CMSNT->site('time_cron_suppliers_api46');
if ($elapsed >= 0 && $elapsed < 5) {
    die('Thao tác quá nhanh, vui lòng thử lại sau!');
}
$CMSNT->update("settings", [
    'value' => time()
], " `name` = 'time_cron_suppliers_api46' ");

// Duyệt qua tất cả các nhà cung cấp loại API_46 đang hoạt động (status = 1)
foreach ($CMSNT->get_list_safe(" SELECT * FROM `suppliers` WHERE `status` = ? AND `type` = ? ", [1, 'API_46']) as $supplier) {

    // =========================================================================
    // 1. HEALTH CHECK - Lấy số dư tài khoản từ API nhà cung cấp
    // =========================================================================
    if (!empty($supplier['token']) && !empty($supplier['domain'])) {
        $result_raw = balance_API_46($supplier['domain'], $supplier['token'], $supplier['proxy']);
        $result = json_decode($result_raw, true);

        // Kiểm tra response hợp lệ từ API (trường success và balance)
        if (isset($result['success']) && $result['success'] == true && isset($result['balance'])) {
            $balance_display = format_currency($result['balance']);
            // Cập nhật số dư hiển thị và thời gian cập nhật vào database
            $CMSNT->update('suppliers', [
                'price'          => $balance_display,
                'update_gettime' => gettime()
            ], " `id` = ? ", [$supplier['id']]);
        } else {
            // Cập nhật thông báo lỗi vào database để admin biết và kiểm tra
            $CMSNT->update('suppliers', [
                'price'          => 'Kết nối đến API thất bại!',
                'update_gettime' => gettime()
            ], " `id` = ? ", [$supplier['id']]);
        }
    }

    // =========================================================================
    // 2. ĐỒNG BỘ SẢN PHẨM TỪ API
    // =========================================================================
    $hasData = false; // Cờ an toàn (Anti-Nuke): Chỉ xóa sản phẩm cũ khi lấy dữ liệu mới thành công
    $result_raw = listProduct_API_46($supplier['domain'], $supplier['token'], $supplier['proxy']);
    $data = json_decode($result_raw, true);

    // Kiểm tra danh sách sản phẩm trả về từ API có hợp lệ không
    if (isset($data['success']) && $data['success'] == true && isset($data['products']) && is_array($data['products']) && !empty($data['products'])) {
        $hasData = true;

        foreach ($data['products'] as $product_api) {
            // Lấy ID sản phẩm duy nhất của API
            $api_id   = check_string(trim((string)$product_api['id']));
            $api_name = isset($product_api['name']) ? trim($product_api['name']) : '';
            $api_name = $supplier['check_string_api'] == 'OFF' ? $api_name : check_string($api_name);

            // Mô tả sản phẩm
            $api_desc = isset($product_api['description']) ? trim($product_api['description']) : '';
            $api_desc = $supplier['check_string_api'] == 'OFF' ? $api_desc : check_string($api_desc);

            // Số lượng tồn kho từ API
            $api_stock = isset($product_api['stock']) ? intval($product_api['stock']) : 0;

            // Giá sản phẩm từ API
            $api_price = isset($product_api['price']) ? floatval($product_api['price']) : 0;

            // Áp dụng tỷ giá nếu có cấu hình (ví dụ: nhân tỉ giá ngoại tệ nếu cần)
            if (!empty($supplier['rate']) && $supplier['rate'] != 1) {
                $api_price = $api_price * floatval($supplier['rate']);
            }

            // Tính toán giá bán dựa trên chiết khấu / markup
            $ck    = $api_price * $supplier['discount'] / 100;
            $price = $api_price;
            if ($supplier['update_price'] == 'ON') {
                if ($supplier['roundMoney'] == 'ON') {
                    $price = roundMoney($api_price + $ck);
                } else {
                    $price = $api_price + $ck;
                }
            }

            // Kiểm tra xem sản phẩm đã tồn tại trong cơ sở dữ liệu chưa
            $product = $CMSNT->get_row_safe(" SELECT * FROM `products` WHERE `api_id` = ? AND `supplier_id` = ? ", [$api_id, $supplier['id']]);

            if (!$product) {
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
                    echo '<b style="color:red;">CREATE</b> - [API_46] Tạo sản phẩm ' . $api_name . ' (ID: ' . $api_id . ') thành công!<br>';
                }
            } else {
                // CẬP NHẬT SẢN PHẨM ĐÃ CÓ
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
                    // Giữ nguyên tên và mô tả do Admin tự đặt
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
                    echo '<b style="color:green;">UPDATE</b> - [API_46] Cập nhật sản phẩm ' . $api_name . ' (ID: ' . $api_id . ') thành công!<br>';
                }
            }
        }
    }

    // =========================================================================
    // 3. XÓA SẢN PHẨM CŨ (Anti-Nuke Pattern)
    // Chỉ xóa các sản phẩm của nhà cung cấp này nếu quá thời gian cập nhật (ví dụ: không có trong danh sách mới của API)
    // =========================================================================
    if ($hasData) {
        $CMSNT->remove('products', " `supplier_id` = ? AND " . time() . " - `api_time_update` >= 3600 ", [$supplier['id']]);
    }
}
