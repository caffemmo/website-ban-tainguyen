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
$elapsed = time() - (int)$CMSNT->site('time_cron_suppliers_shopkey');
    if ($elapsed >= 0 && $elapsed < 5) {
        die('Thao tác quá nhanh, vui lòng thử lại sau!');
    }
$CMSNT->update("settings", [
    'value' => time()
], " `name` = 'time_cron_suppliers_shopkey' ");



foreach ($CMSNT->get_list_safe(" SELECT * FROM `suppliers` WHERE `status` = ? AND `type` = ? ", [1, 'SHOPKEY']) as $supplier) {
    // CẬP NHẬT SỐ DƯ API
    $result1 = balance_API_SHOPKEY($supplier['domain'], $supplier['api_key'], $supplier['token'], $supplier['proxy']);
    $result = json_decode($result1, true);
    if (isset($result['success']) && $result['success'] == true) {
        $CMSNT->update('suppliers', [
            'price' => check_string(format_currency($result['data']['balance']['current'])),
            'update_gettime'    => gettime()
        ], " `id` = ? ", [$supplier['id']]);
    } else {
        $CMSNT->update('suppliers', [
            'price' => check_string($result1),
            'update_gettime'    => gettime()
        ], " `id` = ? ", [$supplier['id']]);
    }


    // CURL LẤY SẢN PHẨM - PAGINATION: lấy tất cả các trang
    $page = 1;
    $per_page = 100; // Lấy 100 sản phẩm mỗi trang để giảm số lần request
    $has_more = true;

    while ($has_more) {
        $result = listProduct_API_SHOPKEY($supplier['domain'], $supplier['api_key'], $supplier['token'], $supplier['proxy'], $page, $per_page);
        $result = json_decode($result, true);

        if (!isset($result['success']) || $result['success'] != true) {
            echo '<b style="color:red;">ERROR</b> - Không thể lấy danh sách sản phẩm trang ' . $page . '<br>';
            break;
        }

        // DEBUG: Hiển thị thông tin pagination
        if ($CMSNT->site('debug_api_suppliers') == 1) {
            $pagination = $result['data']['pagination'] ?? [];
            echo '<b style="color:purple;">DEBUG</b> - Trang ' . $page . '/' . ($pagination['total_pages'] ?? '?') . ' - ' . count($result['data']['products']) . ' products<br>';
        }

        // SHOPKEY API trả về products với các plans bên trong
        // Mỗi plan sẽ được sync thành 1 sản phẩm riêng (tương tự shopclone7)
        foreach ($result['data']['products'] as $api_product) {

            $category_id = 0; // Mặc định ID chuyên mục sẽ không có

            // XỬ LÝ CHUYÊN MỤC
            if ($supplier['sync_category'] == 'ON' && isset($api_product['category'])) {
                $category_data = $api_product['category'];
                $category_name = validate_string($category_data['name'], 255, 1);
                if ($category_name === false) continue;

                $category_api_id = validate_alphanumeric($category_data['id'], 50);
                if ($category_api_id === false) continue;

                // TÌM CHUYÊN MỤC THEO NAME
                if (!$category_api = $CMSNT->get_row_safe(" SELECT * FROM `categories` WHERE `name` = ? ", [$category_name])) {
                    // Tạo mới chuyên mục
                    $rand = '_' . random('QWERTTYUIOPASDFGHJKLZXCVBNM123456789', 6);
                    $uploads_dir = __DIR__ . '/../../assets/storage/images/category' . $rand . '.png';
                    $url_image = $CMSNT->site('favicon'); // Fallback URL

                    // Xử lý ảnh từ API nếu có (hỗ trợ PNG, JPEG, WEBP, GIF)
                    if (!empty($category_data['image']) && isset($supplier['sync_category_image']) && $supplier['sync_category_image'] == 'ON') {
                        $image_content = @file_get_contents($category_data['image']);
                        if ($image_content !== false) {
                            $image = @imagecreatefromstring($image_content);
                            if ($image) {
                                if (imagepng($image, $uploads_dir)) {
                                    $url_image = 'assets/storage/images/category' . $rand . '.png';
                                }
                                imagedestroy($image);
                            }
                        }
                    }

                    $isInsert = $CMSNT->insert('categories', [
                        'parent_id'         => 0,
                        'id_api'            => $category_api_id,
                        'supplier_id'       => $supplier['id'],
                        'status'            => 1,
                        'name'              => $category_name,
                        'slug'              => create_slug($category_name),
                        'title'             => isset($category_data['description']) ? validate_string($category_data['description'], 255) : '',
                        'description'       => isset($category_data['description']) ? validate_string($category_data['description'], 2000) : '',
                        'keywords'          => '',
                        'icon'              => $url_image,
                        'create_date'       => gettime(),
                        'api_time_update'   => time()
                    ]);
                    if ($isInsert) {
                        $category_id = $isInsert;
                        echo '<b style="color:red;">CREATE</b> - Tạo category ' . $category_name . ' thành công !<br>';
                    }
                } else {
                    $category_id = $category_api['id']; // Lấy ID chuyên mục nếu đã tạo sẵn chuyên mục

                    $CMSNT->update('categories', [
                        'name'          => $category_name,
                        'slug'          => create_slug($category_name),
                        'title'         => isset($category_data['description']) ? validate_string($category_data['description'], 255) : '',
                        'description'   => isset($category_data['description']) ? validate_string($category_data['description'], 500) : '',
                        'api_time_update'   => time()
                    ], " `id` = ? ", [$category_id]);
                    echo '<b style="color:blue;">UPDATE</b> - Cập nhật chuyên mục "' . $category_name . '" !<br>';
                }
            }

            // XỬ LÝ CÁC PLAN CỦA SẢN PHẨM - MỖI PLAN LÀ 1 SẢN PHẨM
            // Chỉ sync các plan có is_instant = true
            if (isset($api_product['plans']) && is_array($api_product['plans'])) {
                foreach ($api_product['plans'] as $plan) {
                    // Chỉ lấy các plan có is_instant = true
                    if (!isset($plan['is_instant']) || $plan['is_instant'] != true) {
                        continue;
                    }

                    // api_id của sản phẩm = plan_id (để khi mua hàng sẽ gọi đúng plan)
                    $api_id = validate_alphanumeric($plan['id'], 100);
                    if ($api_id === false) continue;

                    // Tên sản phẩm = tên product + tên plan
                    $product_name = $api_product['name'];
                    $plan_name = $plan['name'];
                    $api_name = $supplier['check_string_api'] == 'OFF'
                        ? $product_name . ' - ' . $plan_name
                        : validate_string($product_name . ' - ' . $plan_name, 500, 1);

                    // Khi check_string_api OFF: giữ nguyên HTML gốc từ API, chỉ đảm bảo encoding UTF-8 hợp lệ
                    // Khi check_string_api ON: escape HTML qua validate_string() để hiển thị an toàn
                    $api_description = $supplier['check_string_api'] == 'OFF'
                        ? mb_convert_encoding($api_product['description'] ?? '', 'UTF-8', 'UTF-8')
                        : validate_string($api_product['description'] ?? '', 10000);

                    $api_stock = validate_int($plan['stock_count'] ?? 0, 0);
                    $api_price = validate_float($plan['final_price'] ?? $plan['price'], 0);

                    if ($api_name === false || $api_stock === false || $api_price === false) continue;

                    // Quy đổi rate tiền tệ nếu có
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

                    // Kiểm tra sản phẩm đã tồn tại chưa (theo api_id = plan_id)
                    if (!$product = $CMSNT->get_row_safe(" SELECT * FROM `products` WHERE `api_id` = ? AND `supplier_id` = ? ", [$api_id, $supplier['id']])) {
                        // THÊM SẢN PHẨM MỚI
                        $product_status = (isset($supplier['isAutoShow']) && $supplier['isAutoShow'] == 1) ? 1 : 0;

                        // Xử lý ảnh sản phẩm (hỗ trợ PNG, JPEG, WEBP, GIF)
                        $product_image = '';
                        if (!empty($api_product['image'])) {
                            $rand = '_' . random('QWERTTYUIOPASDFGHJKLZXCVBNM123456789', 6);
                            $uploads_dir = __DIR__ . '/../../assets/storage/images/product' . $rand . '.png';

                            // Tải ảnh từ URL (hỗ trợ nhiều định dạng)
                            $image_content = @file_get_contents($api_product['image']);
                            if ($image_content !== false) {
                                $image = @imagecreatefromstring($image_content);
                                if ($image) {
                                    if (imagepng($image, $uploads_dir)) {
                                        $product_image = 'product' . $rand . '.png';
                                    }
                                    imagedestroy($image);
                                }
                            }
                        }

                        $CMSNT->insert('products', [
                            'user_id'           => $supplier['user_id'],
                            'category_id'       => $category_id,
                            'supplier_id'       => $supplier['id'],
                            'name'              => $api_name,
                            'slug'              => create_slug($api_name . $api_id),
                            'short_desc'        => '',
                            'description'       => $api_description,
                            'price'             => $price,
                            'images'            => $product_image,
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
                        $update_price = $product['price'];
                        if ($supplier['update_price'] == 'ON') {
                            if ($supplier['roundMoney'] == 'ON') {
                                $update_price = roundMoney($api_price + $ck);
                            } else {
                                $update_price = $api_price + $ck;
                            }
                        }

                        $product_name_update = $api_name;
                        $product_desc_update = $api_description;
                        $product_slug_update = create_slug($api_name . $api_id);
                        if ($supplier['update_name'] == 'OFF') {
                            $product_name_update = $product['name'];
                            $product_desc_update = $product['description'];
                            $product_slug_update = $product['slug'];
                        }

                        $CMSNT->update('products', [
                            'category_id'       => $category_id > 0 ? $category_id : $product['category_id'],
                            'price'             => $update_price,
                            'name'              => $product_name_update,
                            'slug'              => $product_slug_update,
                            'description'       => $product_desc_update,
                            'cost'              => $api_price,
                            'api_name'          => $api_name,
                            'api_stock'         => $api_stock,
                            'api_time_update'   => time()
                        ], " `id` = ? ", [$product['id']]);

                        if ($CMSNT->site('debug_api_suppliers') == 1) {
                            echo '<b style="color:green;">UPDATE</b> - Cập nhật sản phẩm ' . $api_name . ' !<br>';
                        }
                    }
                }
            }
        }

        // Kiểm tra còn trang tiếp theo không
        $pagination = $result['data']['pagination'] ?? [];
        $has_more = isset($pagination['has_more']) ? $pagination['has_more'] : false;
        $page++;

        // Giới hạn tối đa 50 trang để tránh loop vô hạn
        if ($page > 50) {
            echo '<b style="color:orange;">WARNING</b> - Đã đạt giới hạn 50 trang, dừng sync<br>';
            break;
        }
    }

    $current_time = time();

    // Xóa ảnh của sản phẩm trước khi xóa sản phẩm
    $products_to_delete = $CMSNT->get_list_safe(" SELECT * FROM `products` WHERE `supplier_id` = ? AND ? - `api_time_update` >= 3600 ", [$supplier['id'], $current_time]);
    foreach ($products_to_delete as $product) {
        // Xóa ảnh sản phẩm từ server
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

    // Xóa icon của chuyên mục trước khi xóa chuyên mục
    $categories_to_delete = $CMSNT->get_list_safe(" SELECT * FROM `categories` WHERE `supplier_id` = ? AND ? - `api_time_update` >= 3600 ", [$supplier['id'], $current_time]);
    foreach ($categories_to_delete as $category) {
        if (!empty($category['icon'])) {
            $iconPath = __DIR__ . "/../../" . $category['icon'];
            if (file_exists($iconPath)) {
                unlink($iconPath);
            }
        }
    }

    // Xóa sản phẩm và chuyên mục khỏi database
    $CMSNT->remove('products', " `supplier_id` = ? AND ? - `api_time_update` >= 3600 ", [$supplier['id'], $current_time]);
    $CMSNT->remove('categories', " `supplier_id` = ? AND ? - `api_time_update` >= 3600 ", [$supplier['id'], $current_time]);
}
