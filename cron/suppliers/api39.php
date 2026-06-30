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
$elapsed = time() - (int)$CMSNT->site('time_cron_suppliers_api39');
    if ($elapsed >= 0 && $elapsed < 5) {
        die('Thao tác quá nhanh, vui lòng thử lại sau!');
    }
$CMSNT->update("settings", [
    'value' => time()
], " `name` = 'time_cron_suppliers_api39' ");

// DEBUG flag
$debug = $CMSNT->site('debug_api_suppliers') == 1;

$suppliers = $CMSNT->get_list_safe(" SELECT * FROM `suppliers` WHERE `status` = ? AND `type` = ? ", [1, 'API_39']);

if ($debug) {
    echo "<b>Tìm thấy " . count($suppliers) . " API_39 supplier(s) có status = 1</b><br><br>";
}

if (empty($suppliers)) {
    if ($debug) {
        echo "<span style='color:orange;'>⚠️ Không có supplier nào được tìm thấy. Vui lòng kiểm tra:</span><br>";
        echo "1. Supplier đã được thêm chưa?<br>";
        echo "2. Supplier có status = 1 (Hoạt động) không?<br>";
        echo "3. Supplier có type = 'API_39' không?<br>";
    }
    die();
}

foreach ($suppliers as $supplier) {

    if ($debug) {
        echo "<hr><b>Đang xử lý Supplier:</b> " . htmlspecialchars($supplier['name'] ?? 'N/A') . " (ID: {$supplier['id']})<br>";
        echo "Domain: " . htmlspecialchars($supplier['domain']) . "<br>";
    }

    // 1. CẬP NHẬT SỐ DƯ API
    if (!empty($supplier['token'])) {
        if ($debug) echo "<b>1. Gọi API Balance...</b><br>";
        $result1 = balance_API_39($supplier['domain'], $supplier['token'], $supplier['proxy']);
        if ($debug) echo "Response: <pre>" . htmlspecialchars(substr($result1, 0, 500)) . "</pre>";

        $result = json_decode($result1, true);
        if (isset($result['success']) && $result['success'] == true) {
            $balance = isset($result['data']['walletBalance']) ? $result['data']['walletBalance'] : 0;
            if ($debug) echo "<span style='color:green;'>✓ Balance: " . format_currency(check_string($balance)) . "</span><br>";
            $CMSNT->update('suppliers', [
                'price' => format_currency(check_string($balance)),
                'update_gettime' => gettime()
            ], " `id` = ? ", [$supplier['id']]);
        } else {
            $errorMsg = isset($result['message']) ? $result['message'] : 'Kết nối đến API không thành công!';
            if ($debug) echo "<span style='color:red;'>✗ Error: " . htmlspecialchars($errorMsg) . "</span><br>";
            $CMSNT->update('suppliers', [
                'price' => check_string($errorMsg),
                'update_gettime' => gettime()
            ], " `id` = ? ", [$supplier['id']]);
        }
    } else {
        if ($debug) echo "<span style='color:red;'>⚠️ Thiếu token, bỏ qua kiểm tra balance</span><br>";
    }

    // 2. Chuyển đổi list_api_id (textarea) từ DB thành mảng ID
    $raw_list = $supplier['list_api_id'];
    $api_ids = [];
    if (!empty($raw_list)) {
        $lines = explode("\n", str_replace("\r", "", $raw_list));
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $api_ids[] = $line;
            }
        }
    }
    $api_ids = array_unique($api_ids);

    if ($debug) echo "<br><b>2. Bắt đầu đồng bộ " . count($api_ids) . " ID từ textarea...</b><br>";

    // Xóa các sản phẩm cục bộ không có trong danh sách ID này (chỉ tác động đến các sản phẩm thuộc supplier hiện tại)
    $existingProducts = $CMSNT->get_list_safe(" SELECT * FROM `products` WHERE `supplier_id` = ? ", [$supplier['id']]);
    foreach ($existingProducts as $p) {
        if (!empty($p['api_id']) && !in_array($p['api_id'], $api_ids)) {
            $CMSNT->remove("products", " `id` = ? ", [$p['id']]);
            if ($debug) echo "<b style='color:red;'>DELETE</b> - Đã xoá sản phẩm " . htmlspecialchars($p['name']) . " vì ID [{$p['api_id']}] đã bị gỡ khỏi cấu hình.<br>";
        }
    }

    // Lặp qua từng chuỗi ID được cấu hình trong textarea (format: productId|variantId) để cập nhật hoặc tạo mới
    foreach ($api_ids as $api_id) {
        $api_id_parts = explode('|', $api_id);
        $datammo_product_id = trim($api_id_parts[0]);
        $datammo_variant_id = isset($api_id_parts[1]) ? trim($api_id_parts[1]) : '';

        $productData = null;
        $productResult = '';

        // Ưu tiên gọi API lấy thông tin phân loại nếu có variantId
        if (!empty($datammo_variant_id)) {
            $productResult = variantInfo_API_39($supplier['domain'], $supplier['token'], $datammo_variant_id, $supplier['proxy']);
            $productData = json_decode($productResult, true);
        }

        // Nếu không có variantId hoặc gọi API variant lỗi, fallback về API lấy thông tin product
        if (!$productData || !isset($productData['success']) || $productData['success'] != true) {
            $productResult = productInfo_API_39($supplier['domain'], $supplier['token'], $datammo_product_id, $supplier['proxy']);
            $productData = json_decode($productResult, true);
        }

        if (isset($productData['success']) && $productData['success'] == true && isset($productData['data'])) {
            $data = $productData['data'];
            $api_stock = isset($data['stock']) ? intval($data['stock']) : 0;
            $api_price = isset($data['price']) ? floatval($data['price']) : 0;
            $product_name = isset($data['name']) ? $data['name'] : (isset($data['productTitle']) ? $data['productTitle'] : $api_id);

            // Áp dụng tỷ giá nếu có
            if (!empty($supplier['rate']) && $supplier['rate'] != 1) {
                $api_price = $api_price * $supplier['rate'];
            }

            $ck = $api_price * $supplier['discount'] / 100;
            $calculated_price = ($supplier['roundMoney'] == 'ON') ? roundMoney($api_price + $ck) : ($api_price + $ck);

            // Kiểm tra xem ID này đã tồn tại trên web chưa
            $isExist = $CMSNT->get_row_safe(" SELECT * FROM `products` WHERE `api_id` = ? AND `supplier_id` = ? ", [$api_id, $supplier['id']]);

            if ($isExist) {
                // UPDATE sản phẩm đã có
                $price = $isExist['price'];
                if ($supplier['update_price'] == 'ON') {
                    $price = $calculated_price;
                }

                $update_data = [
                    'price'           => $price,
                    'cost'            => $api_price,
                    'api_stock'       => $api_stock,
                    'api_time_update' => time()
                ];

                if ($supplier['update_name'] == 'ON') {
                    $update_data['name'] = $product_name;
                }

                $CMSNT->update('products', $update_data, " `id` = ? ", [$isExist['id']]);
                if ($debug) echo "<b style='color:green;'>UPDATE</b> - Sản phẩm " . htmlspecialchars($product_name) . " | Tồn kho: {$api_stock}<br>";
            } else {
                // CREATE sản phẩm mới
                $product_status = (isset($supplier['isAutoShow']) && $supplier['isAutoShow'] == 1) ? 1 : 0;
                $insert_data = [
                    'category_id'     => 0, // Không đồng bộ chuyên mục như yêu cầu
                    'user_id'         => $supplier['user_id'],
                    'supplier_id'     => $supplier['id'],
                    'api_id'          => $api_id,
                    'api_name'        => $product_name,
                    'name'            => check_string($product_name),
                    'slug'            => create_slug($product_name . '-' . $api_id),
                    'price'           => $calculated_price,
                    'cost'            => $api_price,
                    'api_stock'       => $api_stock,
                    'api_time_update' => time(),
                    'status'          => $product_status,
                    'create_gettime'  => gettime(),
                    'update_gettime'  => gettime()
                ];

                $CMSNT->insert('products', $insert_data);
                if ($debug) echo "<b style='color:blue;'>CREATE</b> - Đã thêm " . htmlspecialchars($product_name) . " | Tồn kho: {$api_stock}<br>";
            }
        } else {
            if ($debug) echo "<b style='color:orange;'>SKIP</b> - Không lấy được thông tin ID " . htmlspecialchars($api_id) . "<br>";
        }
    }
}
