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
$elapsed = time() - (int)$CMSNT->site('time_cron_suppliers_api53');
if ($elapsed >= 0 && $elapsed < 5) {
    die('Thao tác quá nhanh, vui lòng thử lại sau!');
}
$CMSNT->update("settings", [
    'value' => time()
], " `name` = 'time_cron_suppliers_api53' ");

// Số sản phẩm lấy về mỗi trang (API giới hạn tối đa 100)
define('API_53_PAGE_SIZE', 100);
// Số trang tối đa được duyệt, tránh vòng lặp vô hạn nếu API trả meta sai
define('API_53_MAX_PAGE', 50);
// Thời gian nghỉ giữa mỗi request chi tiết sản phẩm (micro giây) - tránh dính rate limit 120 req
define('API_53_SLEEP_DETAIL', 120000);

/**
 * Kiểm tra response có phải lỗi rate limit / lỗi hệ thống của API_53 không
 *
 * @param array|null $data Mảng đã json_decode từ response
 * @return bool
 */
function is_error_API_53($data)
{
    return !is_array($data) || isset($data['error']);
}

// Lặp qua tất cả nhà cung cấp API_53 đang hoạt động
foreach ($CMSNT->get_list_safe(" SELECT * FROM `suppliers` WHERE `status` = ? AND `type` = ? ", [1, 'API_53']) as $supplier) {

    // =============================================
    // HEALTH CHECK - Lấy số dư ví
    // Endpoint: GET /api/v2/wallet/balance
    // Response: {"data":{"vnd":500000,"usd":25}}
    // =============================================
    if (!empty($supplier['api_key'])) {
        $result_raw = balance_API_53($supplier['domain'], $supplier['api_key'], $supplier['proxy']);
        $result = json_decode($result_raw, true);

        // Số dư VNĐ là số dư dùng để thanh toán đơn hàng
        $balance = null;
        if (isset($result['data']['vnd'])) {
            $balance = $result['data']['vnd'];
        } else if (isset($result['data']['VND'])) {
            $balance = $result['data']['VND'];
        }

        if (!is_error_API_53($result) && $balance !== null) {
            $CMSNT->update('suppliers', [
                'price'          => format_currency(check_string($balance)),
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
    // Bước 1: GET /api/v2/products   → danh sách id + title (có phân trang)
    // Bước 2: GET /api/v2/products/{id} → chi tiết kèm variants (giá + tồn kho)
    //
    // Mỗi VARIANT được lưu thành 1 sản phẩm riêng trong hệ thống,
    // cột api_id lưu variant_id vì đây mới là đơn vị đặt hàng được.
    // =============================================
    $hasData = false; // Cờ Anti-Nuke: chỉ xóa sản phẩm cũ khi đã đồng bộ trọn vẹn
    $syncFailed = false; // Đánh dấu có bất kỳ request nào thất bại giữa chừng

    // ---- Bước 1: Lấy toàn bộ danh sách sản phẩm qua phân trang ----
    $api_products = [];
    $offset = 0;
    for ($page = 0; $page < API_53_MAX_PAGE; $page++) {
        $list_raw = listProduct_API_53($supplier['domain'], $supplier['api_key'], $supplier['proxy'], API_53_PAGE_SIZE, $offset);
        $list = json_decode($list_raw, true);

        if (is_error_API_53($list) || !isset($list['data']) || !is_array($list['data'])) {
            $syncFailed = true;
            if ($CMSNT->site('debug_api_suppliers') == 1) {
                echo '<b style="color:red;">ERROR</b> - [API_53] Không lấy được danh sách sản phẩm (offset ' . $offset . '): ' . htmlspecialchars(substr($list_raw, 0, 300), ENT_QUOTES, 'UTF-8') . '<br>';
            }
            break;
        }

        $api_products = array_merge($api_products, $list['data']);

        // Dừng khi đã lấy đủ theo meta.count hoặc trang trả về rỗng
        $total = isset($list['meta']['count']) ? intval($list['meta']['count']) : count($api_products);
        if (empty($list['data']) || count($api_products) >= $total) {
            break;
        }
        $offset += API_53_PAGE_SIZE;
    }

    // ---- Bước 2: Lấy chi tiết từng sản phẩm và đồng bộ từng variant ----
    if (!$syncFailed && !empty($api_products)) {
        foreach ($api_products as $product_api) {
            if (empty($product_api['id'])) {
                continue;
            }

            $detail_raw = detailProduct_API_53($supplier['domain'], $supplier['api_key'], $product_api['id'], $supplier['proxy']);
            $detail = json_decode($detail_raw, true);
            usleep(API_53_SLEEP_DETAIL);

            // Không lấy được chi tiết → bỏ qua sản phẩm này và KHÔNG cho phép xóa dọn dẹp
            if (is_error_API_53($detail) || !isset($detail['data']['variants']) || !is_array($detail['data']['variants'])) {
                $syncFailed = true;
                if ($CMSNT->site('debug_api_suppliers') == 1) {
                    echo '<b style="color:red;">ERROR</b> - [API_53] Không lấy được chi tiết sản phẩm ' . htmlspecialchars($product_api['id'], ENT_QUOTES, 'UTF-8') . '<br>';
                }
                continue;
            }

            $data = $detail['data'];

            // Tên và mô tả gốc của sản phẩm cha
            $parent_name = isset($data['title']) ? trim($data['title']) : '';
            $parent_desc = isset($data['description']) && $data['description'] !== null ? trim($data['description']) : '';

            foreach ($data['variants'] as $variant) {
                // ID variant chính là mã dùng để đặt hàng
                if (empty($variant['id'])) {
                    continue;
                }
                $api_id = check_string(trim((string)$variant['id']));
                if (empty($api_id)) {
                    continue;
                }

                // Ghép tên: "Tên sản phẩm - Tên variant" (bỏ qua nếu variant trùng tên cha)
                $variant_name = isset($variant['title']) ? trim($variant['title']) : '';
                $api_name = $parent_name;
                if (!empty($variant_name) && $variant_name !== $parent_name) {
                    $api_name = $parent_name . ' - ' . $variant_name;
                }
                $api_name = $supplier['check_string_api'] == 'OFF' ? $api_name : check_string($api_name);

                $api_desc = $supplier['check_string_api'] == 'OFF' ? $parent_desc : check_string($parent_desc);

                // Tồn kho: hết hàng thì ép về 0 dù available_quantity trả về số dương
                $api_stock = 0;
                if (isset($variant['in_stock']) && $variant['in_stock'] === true) {
                    $api_stock = isset($variant['available_quantity']) ? intval($variant['available_quantity']) : 0;
                }

                // Giá: ưu tiên VNĐ, không có thì lấy USD (admin dùng cột Tỷ giá để quy đổi)
                $api_price = 0;
                if (isset($variant['prices']['vnd'])) {
                    $api_price = floatval($variant['prices']['vnd']);
                } else if (isset($variant['prices']['VND'])) {
                    $api_price = floatval($variant['prices']['VND']);
                } else if (isset($variant['prices']['usd'])) {
                    $api_price = floatval($variant['prices']['usd']);
                }

                // Áp dụng tỷ giá nếu cần
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
                        echo '<b style="color:red;">CREATE</b> - [API_53] Tạo sản phẩm ' . htmlspecialchars($api_name, ENT_QUOTES, 'UTF-8') . ' (ID: ' . htmlspecialchars($api_id, ENT_QUOTES, 'UTF-8') . ') thành công!<br>';
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
                        echo '<b style="color:green;">UPDATE</b> - [API_53] Cập nhật sản phẩm ' . htmlspecialchars($api_name, ENT_QUOTES, 'UTF-8') . ' (ID: ' . htmlspecialchars($api_id, ENT_QUOTES, 'UTF-8') . ') thành công!<br>';
                    }
                }
            } // end foreach variants
        } // end foreach products

        // Chỉ coi là đồng bộ thành công khi không có request nào lỗi giữa chừng
        $hasData = !$syncFailed;
    }

    // =============================================
    // XÓA SẢN PHẨM CŨ KHÔNG CÒN TRÊN API (Anti-Nuke Pattern)
    // Chỉ chạy khi đã lấy được data mới hợp lệ và trọn vẹn
    // =============================================
    if ($hasData) {
        $CMSNT->remove('products', " `supplier_id` = ? AND " . time() . " - `api_time_update` >= 3600 ", [$supplier['id']]);
    }
}
