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

/* START CHỐNG SPAM */
$elapsed = time() - (int)$CMSNT->site('time_cron_suppliers_api38');
    if ($elapsed >= 0 && $elapsed < 5) {
        die('Thao tác quá nhanh, vui lòng thử lại sau!');
    }
$CMSNT->update("settings", [
    'value' => time()
], " `name` = 'time_cron_suppliers_api38' ");

// DEBUG flag
$debug = $CMSNT->site('debug_api_suppliers') == 1;

$suppliers = $CMSNT->get_list_safe(" SELECT * FROM `suppliers` WHERE `status` = ? AND `type` = ? ", [1, 'API_38']);

if ($debug) {
    echo "<b>Tìm thấy " . count($suppliers) . " API_38 supplier(s) có status = 1</b><br><br>";
}

if (empty($suppliers)) {
    if ($debug) {
        echo "<span style='color:orange;'>⚠️ Không có supplier nào được tìm thấy. Vui lòng kiểm tra:</span><br>";
        echo "1. Supplier đã được thêm chưa?<br>";
        echo "2. Supplier có status = 1 (Hoạt động) không?<br>";
        echo "3. Supplier có type = 'API_38' không?<br>";
    }
    die();
}

foreach ($suppliers as $supplier) {

    if ($debug) {
        echo "<hr><b>Đang xử lý Supplier:</b> " . htmlspecialchars($supplier['name'] ?? 'N/A') . " (ID: {$supplier['id']})<br>";
        echo "Domain: " . htmlspecialchars($supplier['domain']) . "<br>";
    }

    // API_38 sử dụng api_key làm app_id và token làm app_key
    $app_id = $supplier['api_key'];
    $app_key = $supplier['token'];

    if ($debug) {
        echo "App ID (api_key): " . (!empty($app_id) ? htmlspecialchars($app_id) : '<span style="color:red">TRỐNG!</span>') . "<br>";
        echo "App Key (token): " . (!empty($app_key) ? '***' . substr($app_key, -4) : '<span style="color:red">TRỐNG!</span>') . "<br><br>";
    }

    // 1. CẬP NHẬT SỐ DƯ API
    if (!empty($app_id) && !empty($app_key)) {
        if ($debug) echo "<b>1. Gọi API Balance...</b><br>";
        $result1 = balance_API_38($supplier['domain'], $app_id, $app_key, $supplier['proxy']);
        if ($debug) echo "Response: <pre>" . htmlspecialchars(substr($result1, 0, 500)) . "</pre>";

        $result = json_decode($result1, true);
        if (isset($result['code']) && $result['code'] == 200) {
            $balance = isset($result['data']['balance']) ? $result['data']['balance'] : 0;
            if ($debug) echo "<span style='color:green;'>✓ Balance: " . format_currency(check_string($balance)) . "</span><br>";
            $CMSNT->update('suppliers', [
                'price' => format_currency(check_string($balance)),
                'update_gettime' => gettime()
            ], " `id` = ? ", [$supplier['id']]);
        } else {
            $errorMsg = isset($result['msg']) ? $result['msg'] : 'Kết nối đến API không thành công!';
            if ($debug) echo "<span style='color:red;'>✗ Error: " . htmlspecialchars($errorMsg) . "</span><br>";
            $CMSNT->update('suppliers', [
                'price' => check_string($errorMsg),
                'update_gettime' => gettime()
            ], " `id` = ? ", [$supplier['id']]);
        }
    } else {
        if ($debug) echo "<span style='color:red;'>⚠️ Thiếu app_id hoặc app_key, bỏ qua kiểm tra balance</span><br>";
    }

    // 2. CURL LẤY SẢN PHẨM - /shared/commodity/items
    if ($debug) echo "<br><b>2. Gọi API List Products...</b><br>";
    $result = listProduct_API_38($supplier['domain'], $app_id, $app_key, $supplier['proxy']);
    if ($debug) echo "Response (500 ký tự đầu): <pre>" . htmlspecialchars(substr($result, 0, 500)) . "</pre>";

    $result = json_decode($result, true);

    // Kiểm tra response hợp lệ
    if (isset($result['code']) && $result['code'] == 200 && isset($result['data'])) {
        if ($debug) echo "<span style='color:green;'>✓ API trả về thành công, có " . count($result['data']) . " category(s)</span><br>";

        // Duyệt qua từng category
        foreach ($result['data'] as $category) {
            $category_id = 0; // Mặc định không có category

            // Nếu bật đồng bộ category
            if ($supplier['sync_category'] == 'ON' && isset($category['name'])) {
                $cat_name = $supplier['check_string_api'] == 'OFF' ? $category['name'] : check_string($category['name']);
                $cat_id_api = isset($category['id']) ? check_string($category['id']) : '';

                // Kiểm tra category đã tồn tại chưa
                $existing_cat = $CMSNT->get_row_safe("SELECT * FROM `categories` WHERE `id_api` = ? AND `supplier_id` = ?", [$cat_id_api, $supplier['id']]);

                if (!$existing_cat) {
                    // Thêm category mới
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
                    // Cập nhật thời gian
                    $CMSNT->update('categories', [
                        'api_time_update' => time()
                    ], " `id` = ? ", [$category_id]);
                }
            }

            // Duyệt qua sản phẩm trong category (children)
            $products = isset($category['children']) ? $category['children'] : [];

            foreach ($products as $api) {
                // Lấy thông tin sản phẩm từ API
                // API Shared sử dụng 'code' làm ID sản phẩm (sharedCode)
                $api_id = isset($api['code']) ? check_string($api['code']) : '';
                if (empty($api_id)) continue;

                $api_name = isset($api['name']) ? $api['name'] : '';
                $api_name = $supplier['check_string_api'] == 'OFF' ? $api_name : check_string($api_name);

                $api_desc = isset($api['description']) ? check_string($api['description']) : '';

                // Gọi API inventory để lấy số lượng tồn kho chính xác
                $api_stock = 0;
                $inventory_result = inventory_API_38($supplier['domain'], $app_id, $app_key, $api_id, '', $supplier['proxy']);
                $inventory_data = json_decode($inventory_result, true);
                if (isset($inventory_data['code']) && $inventory_data['code'] == 200 && isset($inventory_data['data']['count'])) {
                    $api_stock = intval($inventory_data['data']['count']);
                }
                if ($debug) echo "  → Tồn kho [{$api_id}]: {$api_stock}<br>";

                // Giá có thể là price hoặc user_price (giá đại lý)
                $api_price = isset($api['user_price']) ? floatval($api['user_price']) : (isset($api['price']) ? floatval($api['price']) : 0);

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
                    // CẬP NHẬT SẢN PHẨM
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

                    // Cập nhật category nếu bật sync
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
        }
    }

    // 3. XÓA SẢN PHẨM KHÔNG CÒN TỒN TẠI SAU 1 GIỜ
    $CMSNT->remove('products', " `supplier_id` = '" . $supplier['id'] . "' AND " . time() . " - `api_time_update` >= 3600 ");

    // Xóa category không còn tồn tại sau 1 giờ (nếu bật sync)
    if ($supplier['sync_category'] == 'ON') {
        $CMSNT->remove('categories', " `supplier_id` = '" . $supplier['id'] . "' AND " . time() . " - `api_time_update` >= 3600 ");
    }
}
