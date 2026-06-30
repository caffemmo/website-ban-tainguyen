<?php

define("IN_SITE", true);
require_once(__DIR__ . '/../../libs/db.php');
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../libs/lang.php');
require_once(__DIR__ . '/../../libs/helper.php');
require_once(__DIR__ . '/../../libs/suppliers.php');
$CMSNT = new DB();

// Số trang xử lý tối đa mỗi lần chạy cron (tránh timeout)
$PAGES_PER_RUN = 5;

// Nếu có đặt key cron job thì kiểm tra key hợp lệ
if (!empty($CMSNT->site('key_cron_job'))) {
    if (empty($_GET['key']) || $_GET['key'] != $CMSNT->site('key_cron_job')) {
        die(__('Key không hợp lệ'));
    }
}

/* START CHỐNG SPAM */
$elapsed = time() - (int)$CMSNT->site('time_cron_suppliers_api36');
    if ($elapsed >= 0 && $elapsed < 5) {
        die('Thao tác quá nhanh, vui lòng thử lại sau!');
    }
$CMSNT->update("settings", [
    'value' => time()
], " `name` = 'time_cron_suppliers_api36' ");



foreach ($CMSNT->get_list_safe(" SELECT * FROM `suppliers` WHERE `status` = ? AND `type` = ? ", [1, 'API_36']) as $supplier) {
    // CẬP NHẬT SỐ DƯ API
    if (!empty($supplier['token'])) {
        $result1 = balance_API_36($supplier['domain'], $supplier['token'], $supplier['proxy']);
        $result = json_decode($result1, true);
        if (isset($result['code']) && $result['code'] == 1) {
            $CMSNT->update('suppliers', [
                'price' => '$' . check_string($result['data']['balance']),
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


    // CURL LẤY SẢN PHẨM - HỖ TRỢ PHÂN TRANG INCREMENTAL
    // Lấy trang cuối đã xử lý từ database (lưu trong trường api_last_page hoặc dùng json trong notes)
    $sync_data = !empty($supplier['notes']) ? json_decode($supplier['notes'], true) : [];
    $current_page = isset($sync_data['last_synced_page']) ? intval($sync_data['last_synced_page']) + 1 : 1;
    $last_page = isset($sync_data['total_pages']) ? intval($sync_data['total_pages']) : 1;

    // Nếu đã sync hết, bắt đầu lại từ trang 1
    if ($current_page > $last_page) {
        $current_page = 1;
    }

    $pages_processed = 0;
    $start_page = $current_page;

    while ($pages_processed < $PAGES_PER_RUN) {
        $result = listProduct_API_36($supplier['domain'], $supplier['token'], $supplier['proxy'], $current_page);
        $result = json_decode($result, true);

        if (isset($result) && isset($result['code']) && $result['code'] == 1) {
            // Lấy thông tin phân trang từ meta
            if (isset($result['meta']) && isset($result['meta']['last_page'])) {
                $last_page = intval($result['meta']['last_page']);
            }

            if ($CMSNT->site('debug_api_suppliers') == 1) {
                echo '<b style="color:blue;">PAGINATION</b> - Đang xử lý trang ' . $current_page . '/' . $last_page . ' (batch ' . ($pages_processed + 1) . '/' . $PAGES_PER_RUN . ')<br>';
            }

            $category_id = 0; // Mặc định ID chuyên mục sẽ không có
            foreach ($result['data'] as $api) {
                $api_id = check_string($api['id']);

                $api_name = $api['name'];
                $api_name = $supplier['check_string_api'] == 'OFF' ? $api_name : check_string($api_name);

                // Tạo mô tả từ dữ liệu API
                $api_desc = isset($api['description']) && !empty($api['description']) ? $api['description'] : '';
                $api_desc = $supplier['check_string_api'] == 'OFF' ? $api_desc : check_string($api_desc);

                $api_stock = isset($api['in_stock']) ? intval($api['in_stock']) : 0;
                $api_price = check_string($api['price']); // Giá gốc (USD)

                // Áp dụng tỷ giá nếu có
                if (!empty($supplier['rate']) && $supplier['rate'] != 1) {
                    $api_price = $api_price * $supplier['rate'];
                }

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

                // Lấy min/max từ API
                $min_qty = isset($api['min_qty']) ? intval($api['min_qty']) : 1;
                $max_qty = isset($api['max_qty']) ? intval($api['max_qty']) : 999999;

                if (!$product = $CMSNT->get_row_safe(" SELECT * FROM `products` WHERE `api_id` = ? AND `supplier_id` = ? ", [$api_id, $supplier['id']])) {
                    // THÊM SẢN PHẨM
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
                        'min'               => $min_qty,
                        'max'               => $max_qty,
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
                    // CẬP NHẬT SẢN PHẨM
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
                        'min'           => $min_qty,
                        'max'           => $max_qty,
                        'api_name'      => $api_name,
                        'api_time_update'    => time(),
                        'api_stock'     => $api_stock
                    ], " `id` = '" . $product['id'] . "' ");
                    if ($CMSNT->site('debug_api_suppliers') == 1) {
                        echo '<b style="color:green;">UPDATE</b> - sản phẩm ' . $api_name . ' thành công !<br>';
                    }
                }
            }

            $pages_processed++;
            $current_page++;

            // Nếu đã xử lý hết tất cả trang
            if ($current_page > $last_page) {
                break;
            }
        } else {
            // Lỗi API, dừng lại
            if ($CMSNT->site('debug_api_suppliers') == 1) {
                echo '<b style="color:orange;">ERROR</b> - Không thể lấy dữ liệu trang ' . $current_page . '<br>';
            }
            break;
        }
    }

    // Lưu tiến trình đồng bộ vào database
    $sync_data['last_synced_page'] = $current_page - 1;
    $sync_data['total_pages'] = $last_page;
    $sync_data['last_sync_time'] = time();

    $CMSNT->update('suppliers', [
        'notes' => json_encode($sync_data)
    ], " `id` = ? ", [$supplier['id']]);

    if ($CMSNT->site('debug_api_suppliers') == 1) {
        $end_page = $current_page - 1;
        echo '<b style="color:purple;">PROGRESS</b> - Đã đồng bộ trang ' . $start_page . '-' . $end_page . '/' . $last_page . '. ';
        if ($end_page >= $last_page) {
            echo 'Hoàn thành chu kỳ đồng bộ!<br>';
        } else {
            echo 'Lần chạy tiếp theo sẽ tiếp tục từ trang ' . ($end_page + 1) . '<br>';
        }
    }

    // Chỉ xóa sản phẩm không còn tồn tại khi đã hoàn thành một chu kỳ đồng bộ đầy đủ
    // (tức là đã duyệt qua tất cả các trang)
    if ($current_page > $last_page) {
        // Xóa sản phẩm không còn tồn tại trên API sau 2 giờ (tăng lên vì sync incremental)
        $CMSNT->remove('products', " `supplier_id` = '" . $supplier['id'] . "' AND " . time() . " - `api_time_update` >= 7200 ");

        if ($CMSNT->site('debug_api_suppliers') == 1) {
            echo '<b style="color:gray;">CLEANUP</b> - Đã xóa các sản phẩm không còn tồn tại trên API (>2 giờ không cập nhật)<br>';
        }
    }
}
