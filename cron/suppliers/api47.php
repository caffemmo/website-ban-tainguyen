<?php

define("IN_SITE", true);
// Gọi các file cấu hình và thư viện
require_once(__DIR__ . '/../../libs/db.php');
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../libs/lang.php');
require_once(__DIR__ . '/../../libs/helper.php');
require_once(__DIR__ . '/../../libs/suppliers.php');
$CMSNT = new DB();

// Kiểm tra key cron job
if (!empty($CMSNT->site('key_cron_job'))) {
    if (empty($_GET['key']) || $_GET['key'] != $CMSNT->site('key_cron_job')) {
        die(__('Key không hợp lệ'));
    }
}

/* CHỐNG SPAM CRON */
$elapsed = time() - (int)$CMSNT->site('time_cron_suppliers_api47');
if ($elapsed >= 0 && $elapsed < 5) {
    die('Thao tác quá nhanh, vui lòng thử lại sau!');
}
$CMSNT->update("settings", [
    'value' => time()
], " `name` = 'time_cron_suppliers_api47' ");

// DEBUG flag
$debug = $CMSNT->site('debug_api_suppliers') == 1;

$suppliers = $CMSNT->get_list_safe(" SELECT * FROM `suppliers` WHERE `status` = ? AND `type` = ? ", [1, 'API_47']);

if ($debug) {
    echo "<b>Tìm thấy " . count($suppliers) . " API_47 supplier(s) có status = 1</b><br><br>";
}

if (empty($suppliers)) {
    if ($debug) {
        echo "<span style='color:orange;'>⚠️ Không có supplier nào được tìm thấy. Vui lòng kiểm tra:</span><br>";
        echo "1. Supplier đã được thêm chưa?<br>";
        echo "2. Supplier có status = 1 (Hoạt động) không?<br>";
        echo "3. Supplier có type = 'API_47' không?<br>";
    }
    die();
}

foreach ($suppliers as $supplier) {

    if ($debug) {
        echo "<hr><b>Đang xử lý Supplier:</b> " . htmlspecialchars($supplier['name'] ?? 'N/A') . " (ID: {$supplier['id']})<br>";
        echo "Domain: " . htmlspecialchars($supplier['domain']) . "<br>";
    }

    // API_47 sử dụng token làm API Key
    $token = $supplier['token'];

    if ($debug) {
        echo "API Key (token): " . (!empty($token) ? '***' . substr($token, -4) : '<span style="color:red">TRỐNG!</span>') . "<br><br>";
    }

    if (empty($token)) {
        if ($debug) echo "<span style='color:red;'>⚠️ Thiếu token (API Key), bỏ qua supplier này</span><br>";
        continue;
    }

    // 1. CẬP NHẬT SỐ DƯ API VÀ LẤY THÔNG TIN KHO HÀNG
    if ($debug) echo "<b>1. Gọi API Get Stock & Balance...</b><br>";
    $response = balance_API_47($supplier['domain'], $token, $supplier['proxy']);
    if ($debug) echo "Response: <pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";

    $result = json_decode($response, true);
    if (isset($result['success']) && $result['success'] == true) {
        $balance = isset($result['balance']) ? $result['balance'] : 0;
        if ($debug) echo "<span style='color:green;'>✓ Balance: " . format_currency(check_string($balance)) . "</span><br>";
        
        // Cập nhật số dư trong DB admin
        $CMSNT->update('suppliers', [
            'price' => format_currency(check_string($balance)),
            'update_gettime' => gettime()
        ], " `id` = ? ", [$supplier['id']]);
        
        // 2. ĐỒNG BỘ DANH MỤC (Tạo danh mục mặc định của API_47 nếu sync_category = ON)
        $category_id = 0;
        if ($supplier['sync_category'] == 'ON') {
            $cat_name = "API_47";
            $cat_id_api = "api47_default";
            
            $existing_cat = $CMSNT->get_row_safe("SELECT * FROM `categories` WHERE `id_api` = ? AND `supplier_id` = ?", [$cat_id_api, $supplier['id']]);
            if (!$existing_cat) {
                $category_id = $CMSNT->insert('categories', [
                    'name' => $cat_name,
                    'slug' => create_slug($cat_name . $cat_id_api),
                    'id_api' => $cat_id_api,
                    'supplier_id' => $supplier['id'],
                    'create_date' => gettime(),
                    'api_time_update' => time()
                ]);
            } else {
                $category_id = $existing_cat['id'];
                $CMSNT->update('categories', [
                    'api_time_update' => time()
                ], " `id` = ? ", [$category_id]);
            }
        }

        // 3. ĐỒNG BỘ SẢN PHẨM
        $products = isset($result['products']) && is_array($result['products']) ? $result['products'] : [];
        if ($debug) echo "Tìm thấy " . count($products) . " sản phẩm trên hệ thống đối tác.<br>";

        foreach ($products as $api_id => $api) {
            $api_id = check_string($api_id);
            if (empty($api_id)) continue;

            $api_name = isset($api['name']) ? $api['name'] : '';
            $api_name = $supplier['check_string_api'] == 'OFF' ? $api_name : check_string($api_name);
            
            $api_desc = isset($api['type']) ? "Loại: " . check_string($api['type']) : "";
            $api_stock = isset($api['stock']) ? intval($api['stock']) : 0;
            $api_price = isset($api['price']) ? floatval($api['price']) : 0;

            if ($debug) echo "  → Đồng bộ [{$api_id}]: {$api_name} | Giá: {$api_price} | Tồn kho: {$api_stock}<br>";

            // Áp dụng tỷ giá nếu có
            if (!empty($supplier['rate']) && $supplier['rate'] != 1) {
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

            // Kiểm tra sản phẩm đã tồn tại chưa
            if (!$product = $CMSNT->get_row_safe(" SELECT * FROM `products` WHERE `api_id` = ? AND `supplier_id` = ? ", [$api_id, $supplier['id']])) {
                // THÊM SẢN PHẨM MỚI
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
                // CẬP NHẬT SẢN PHẨM TỒN TẠI
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
                    $product_name = $product['name'];
                    $product_desc = $product['short_desc'];
                    $product_slug = $product['slug'];
                }

                // Cập nhật dữ liệu sản phẩm
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
                if ($supplier['sync_category'] == 'ON' && $category_id > 0) {
                    $update_data['category_id'] = $category_id;
                }

                $CMSNT->update('products', $update_data, " `id` = '" . $product['id'] . "' ");
                if ($CMSNT->site('debug_api_suppliers') == 1) {
                    echo '<b style="color:green;">UPDATE</b> - sản phẩm ' . $api_name . ' thành công !<br>';
                }
            }
        }
    } else {
        $errorMsg = isset($result['error']) ? $result['error'] : 'Kết nối đến API không thành công!';
        if ($debug) echo "<span style='color:red;'>✗ Error: " . htmlspecialchars($errorMsg) . "</span><br>";
        
        $CMSNT->update('suppliers', [
            'price' => check_string($errorMsg),
            'update_gettime' => gettime()
        ], " `id` = ? ", [$supplier['id']]);
    }

    // 4. XÓA SẢN PHẨM KHÔNG CÒN TỒN TẠI SAU 1 GIỜ
    $CMSNT->remove('products', " `supplier_id` = '" . $supplier['id'] . "' AND " . time() . " - `api_time_update` >= 3600 ");

    // Xóa category không còn tồn tại sau 1 giờ (nếu bật sync)
    if ($supplier['sync_category'] == 'ON') {
        $CMSNT->remove('categories', " `supplier_id` = '" . $supplier['id'] . "' AND " . time() . " - `api_time_update` >= 3600 ");
    }
}
