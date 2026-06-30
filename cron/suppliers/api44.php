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
$elapsed = time() - (int)$CMSNT->site('time_cron_suppliers_api44');
if ($elapsed >= 0 && $elapsed < 5) {
    die('Thao tác quá nhanh, vui lòng thử lại sau!');
}
$CMSNT->update("settings", [
    'value' => time()
], " `name` = 'time_cron_suppliers_api44' ");

// Lặp qua tất cả nhà cung cấp API_44 đang hoạt động
foreach ($CMSNT->get_list_safe(" SELECT * FROM `suppliers` WHERE `status` = ? AND `type` = ? ", [1, 'API_44']) as $supplier) {

    // =============================================
    // HEALTH CHECK - Lấy số dư tài khoản
    // Endpoint: GET /api/buyer/balance
    // Response: {"balance": float, "user_id": int, "name": string}
    // Số dư tính bằng USDT → hiển thị dạng "$XX.XX USDT"
    // =============================================
    if (!empty($supplier['token'])) {
        $result_raw = balance_API_44($supplier['domain'], $supplier['token'], $supplier['proxy']);
        $result = json_decode($result_raw, true);

        // Kiểm tra response hợp lệ: phải có trường "balance"
        if (isset($result['balance'])) {
            // Hiển thị số dư USDT kèm tên tài khoản nếu có
            $balance_display = '$' . check_string($result['balance']) . ' USDT';
            if (!empty($result['name'])) {
                $balance_display .= ' (' . check_string($result['name']) . ')';
            }
            $CMSNT->update('suppliers', [
                'price'          => $balance_display,
                'update_gettime' => gettime()
            ], " `id` = ? ", [$supplier['id']]);
        } else {
            // Ghi nhận lỗi kết nối vào cột price để admin biết
            $CMSNT->update('suppliers', [
                'price'          => 'Kết nối đến API không thành công!',
                'update_gettime' => gettime()
            ], " `id` = ? ", [$supplier['id']]);
        }
    }

    // =============================================
    // ĐỒNG BỘ SẢN PHẨM
    // Endpoint: GET /api/buyer/products
    // Response: {"products": [{"product_id": int, "name": str, "price": float, "stock": int, "description": str}]}
    // Giá tính bằng USDT → nhân với rate để chuyển sang VND
    // =============================================
    $hasData = false; // Cờ Anti-Nuke: chỉ xóa sản phẩm cũ khi đã lấy được data mới thành công
    $result_raw = listProduct_API_44($supplier['domain'], $supplier['token'], $supplier['proxy']);
    $data = json_decode($result_raw, true);

    // Anti-Nuke Guard: Chỉ xử lý khi API trả về danh sách hợp lệ (không rỗng)
    // Tránh trường hợp API lỗi tạm thời → xóa toàn bộ catalog
    if (isset($data['products']) && is_array($data['products']) && !empty($data['products'])) {
        $hasData = true;

        foreach ($data['products'] as $product_api) {
            // Lấy product_id dạng integer (đây là ID dùng khi mua hàng)
            $api_id   = check_string(trim((string)$product_api['product_id']));
            $api_name = isset($product_api['name']) ? trim($product_api['name']) : '';
            $api_name = $supplier['check_string_api'] == 'OFF' ? $api_name : check_string($api_name);

            // Lấy description (có thể không có)
            $api_desc = isset($product_api['description']) ? trim($product_api['description']) : '';
            $api_desc = $supplier['check_string_api'] == 'OFF' ? $api_desc : check_string($api_desc);

            // Stock: số lượng còn hàng (stock > 0 là còn)
            $api_stock = isset($product_api['stock']) ? intval($product_api['stock']) : 0;

            // Giá: tính bằng USDT, áp dụng tỷ giá để chuyển sang VND
            $api_price = isset($product_api['price']) ? floatval($product_api['price']) : 0;

            // Áp dụng tỷ giá (VD: rate = 25000 nếu 1 USDT = 25.000 VND)
            if (!empty($supplier['rate']) && $supplier['rate'] != 1) {
                $api_price = $api_price * floatval($supplier['rate']);
            }

            // Tính giá bán = giá gốc + % markup của supplier
            $ck    = $api_price * $supplier['discount'] / 100;
            $price = $api_price;
            if ($supplier['update_price'] == 'ON') {
                if ($supplier['roundMoney'] == 'ON') {
                    $price = roundMoney($api_price + $ck);
                } else {
                    $price = $api_price + $ck;
                }
            }

            // Kiểm tra sản phẩm đã tồn tại trong hệ thống chưa (dựa trên api_id + supplier_id)
            if (!$product = $CMSNT->get_row_safe(" SELECT * FROM `products` WHERE `api_id` = ? AND `supplier_id` = ? ", [$api_id, $supplier['id']])) {
                // THÊM SẢN PHẨM MỚI vào hệ thống
                // isAutoShow: nếu cài đặt ON thì sản phẩm tự hiện, OFF thì ẩn (cần admin duyệt)
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
                    echo '<b style="color:red;">CREATE</b> - [API_44] Tạo sản phẩm ' . $api_name . ' (ID: ' . $api_id . ') thành công!<br>';
                }
            } else {
                // CẬP NHẬT SẢN PHẨM ĐÃ TỒN TẠI
                // Tính lại giá (có thể API vừa thay đổi giá)
                $price = $product['price'];
                if ($supplier['update_price'] == 'ON') {
                    if ($supplier['roundMoney'] == 'ON') {
                        $price = roundMoney($api_price + $ck);
                    } else {
                        $price = $api_price + $ck;
                    }
                }
                // Quyết định có cập nhật tên/mô tả hay không dựa trên cài đặt update_name
                $product_name = $api_name;
                $product_desc = $api_desc;
                $product_slug = create_slug($product_name . $api_id);
                if ($supplier['update_name'] == 'OFF') {
                    // Giữ nguyên tên/mô tả do admin đặt, không ghi đè từ API
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
                    echo '<b style="color:green;">UPDATE</b> - [API_44] Cập nhật sản phẩm ' . $api_name . ' (ID: ' . $api_id . ') thành công!<br>';
                }
            }
        } // end foreach products
    }

    // =============================================
    // XÓA SẢN PHẨM CŨ KHÔNG CÒN TRÊN API
    // Chỉ chạy khi đã lấy được data mới thành công (Anti-Nuke Pattern)
    // Sản phẩm bị đánh dấu "cũ" khi api_time_update không được cập nhật trong > 1 giờ
    // =============================================
    if ($hasData) {
        $CMSNT->remove('products', " `supplier_id` = ? AND " . time() . " - `api_time_update` >= 3600 ", [$supplier['id']]);
    }
}
