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
$elapsed = time() - (int)$CMSNT->site('time_cron_suppliers_api54');
if ($elapsed >= 0 && $elapsed < 5) {
    die('Thao tác quá nhanh, vui lòng thử lại sau!');
}
$CMSNT->update("settings", [
    'value' => time()
], " `name` = 'time_cron_suppliers_api54' ");

// Lặp qua tất cả nhà cung cấp API_54 đang hoạt động
foreach ($CMSNT->get_list_safe(" SELECT * FROM `suppliers` WHERE `status` = ? AND `type` = ? ", [1, 'API_54']) as $supplier) {

    // =============================================
    // HEALTH CHECK - Lấy số dư ví
    // Endpoint: GET /api/v2/telegram-buyer/balance?key=xxx
    // Response: {"success":true,"walletCurrency":"VND","balance":250000,"balanceText":"250.000 ₫"}
    // =============================================
    if (!empty($supplier['token'])) {
        $result_raw = balance_API_54($supplier['domain'], $supplier['token'], $supplier['proxy']);
        $result = json_decode($result_raw, true);

        if (isset($result['success']) && $result['success'] == true && isset($result['balance'])) {
            // Ưu tiên balanceText vì đã có sẵn đơn vị tiền tệ (VND hoặc USD tùy loại bot)
            $balance_display = isset($result['balanceText'])
                ? check_string($result['balanceText'])
                : check_string($result['balance']) . ' ' . (isset($result['walletCurrency']) ? check_string($result['walletCurrency']) : '');
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
    // Endpoint: GET /api/v2/telegram-buyer/products?key=xxx (trả toàn bộ sản phẩm, không phân trang)
    // Response: {"success":true,"products":[{"_id","product_name","description","pricing","walletPricing",
    //            "isSlotProduct","requiresCustomerEmail","stats":{"total","sold","available"}}]}
    // _id là MongoDB ObjectId string — dùng làm api_id khi mua hàng
    // =============================================
    $hasData = false; // Cờ Anti-Nuke: chỉ xóa sản phẩm cũ khi đã lấy được data mới hợp lệ
    $result_raw = listProduct_API_54($supplier['domain'], $supplier['token'], $supplier['proxy']);
    $data = json_decode($result_raw, true);

    // Anti-Nuke Guard: chỉ xử lý khi API trả về thành công với danh sách hợp lệ
    if (isset($data['success']) && $data['success'] == true && isset($data['products']) && is_array($data['products']) && !empty($data['products'])) {
        $hasData = true;

        foreach ($data['products'] as $product_api) {
            if (empty($product_api['_id'])) {
                continue;
            }

            // Bỏ qua sản phẩm dạng slot: khi mua bắt buộc gửi thêm customer_email / slot_months
            // mà luồng mua hàng của website không có ô nhập các thông tin này
            $is_slot = (isset($product_api['isSlotProduct']) && $product_api['isSlotProduct'] === true)
                || (isset($product_api['requiresCustomerEmail']) && $product_api['requiresCustomerEmail'] === true)
                || (isset($product_api['requiresSlotMonths']) && $product_api['requiresSlotMonths'] === true)
                || (isset($product_api['slotProductType']) && $product_api['slotProductType'] === 'slot');
            if ($is_slot) {
                if ($CMSNT->site('debug_api_suppliers') == 1) {
                    echo '<b style="color:orange;">SKIP</b> - [API_54] Bỏ qua sản phẩm dạng slot: ' . htmlspecialchars((string)$product_api['_id'], ENT_QUOTES, 'UTF-8') . '<br>';
                }
                continue;
            }

            // _id chỉ gồm chữ, số (ObjectId) hoặc dạng slot_xxx — validate trước khi dùng
            $api_id = validate_alphanumeric($product_api['_id'], 50);
            if ($api_id === false) {
                if ($CMSNT->site('debug_api_suppliers') == 1) {
                    echo '<b style="color:red;">ERROR</b> - [API_54] Mã sản phẩm không hợp lệ, đã bỏ qua<br>';
                }
                continue;
            }

            $api_name = isset($product_api['product_name']) ? trim($product_api['product_name']) : '';
            if ($api_name === '') {
                continue;
            }
            $api_name = $supplier['check_string_api'] == 'OFF' ? $api_name : check_string($api_name);

            // Mô tả sản phẩm (nếu có)
            $api_desc = isset($product_api['description']) && $product_api['description'] !== null ? trim($product_api['description']) : '';
            $api_desc = $supplier['check_string_api'] == 'OFF' ? $api_desc : check_string($api_desc);

            // Số lượng còn hàng: stats.available (có thể null nếu nhà cung cấp không giới hạn)
            $api_stock = 0;
            if (isset($product_api['stats']['available']) && $product_api['stats']['available'] !== null) {
                $api_stock = intval($product_api['stats']['available']);
            } elseif (isset($product_api['stats']['total']) && $product_api['stats']['total'] !== null) {
                // Fallback: total - sold nếu available không có
                $sold = isset($product_api['stats']['sold']) ? intval($product_api['stats']['sold']) : 0;
                $api_stock = intval($product_api['stats']['total']) - $sold;
            }
            if ($api_stock < 0) {
                $api_stock = 0;
            }

            // Giá: ưu tiên walletPricing (giá theo loại ví của key), fallback về pricing
            $api_price = 0;
            if (isset($product_api['walletPricing'])) {
                $api_price = floatval($product_api['walletPricing']);
            } elseif (isset($product_api['pricing'])) {
                $api_price = floatval($product_api['pricing']);
            }

            // Áp dụng tỷ giá nếu cần (VD: rate = 25000 khi ví là USD mà website dùng VNĐ)
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
                    // Escape output vì tên sản phẩm là dữ liệu thô từ nhà cung cấp khi tắt check_string_api
                    echo '<b style="color:red;">CREATE</b> - [API_54] Tạo sản phẩm ' . htmlspecialchars($api_name, ENT_QUOTES, 'UTF-8') . ' (ID: ' . htmlspecialchars($api_id, ENT_QUOTES, 'UTF-8') . ') thành công!<br>';
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
                    // Escape output vì tên sản phẩm là dữ liệu thô từ nhà cung cấp khi tắt check_string_api
                    echo '<b style="color:green;">UPDATE</b> - [API_54] Cập nhật sản phẩm ' . htmlspecialchars($api_name, ENT_QUOTES, 'UTF-8') . ' (ID: ' . htmlspecialchars($api_id, ENT_QUOTES, 'UTF-8') . ') thành công!<br>';
                }
            }
        } // end foreach products
    } else if ($CMSNT->site('debug_api_suppliers') == 1) {
        echo '<b style="color:red;">ERROR</b> - [API_54] Không lấy được danh sách sản phẩm: ' . htmlspecialchars(substr((string)$result_raw, 0, 300), ENT_QUOTES, 'UTF-8') . '<br>';
    }

    // =============================================
    // XÓA SẢN PHẨM CŨ KHÔNG CÒN TRÊN API (Anti-Nuke Pattern)
    // Chỉ chạy khi đã lấy được data mới hợp lệ (tránh xóa toàn bộ khi API lỗi tạm thời)
    // =============================================
    if ($hasData) {
        $CMSNT->remove('products', " `supplier_id` = ? AND " . time() . " - `api_time_update` >= 3600 ", [$supplier['id']]);
    }
}
