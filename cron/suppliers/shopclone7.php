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
$elapsed = time() - (int)$CMSNT->site('time_cron_suppliers_shopclone7');
    if ($elapsed >= 0 && $elapsed < 5) {
        die('Thao tác quá nhanh, vui lòng thử lại sau!');
    }
$CMSNT->update("settings", [
    'value' => time()
], " `name` = 'time_cron_suppliers_shopclone7' ");



foreach ($CMSNT->get_list_safe(" SELECT * FROM `suppliers` WHERE `status` = ? AND `type` = ? ", [1, 'SHOPCLONE7']) as $supplier) {
    // CẬP NHẬT SỐ DƯ API
    $result1 = balance_API_SHOPCLONE7($supplier['domain'], $supplier['api_key'], $supplier['proxy']);
    $result = json_decode($result1, true);
    if (isset($result['status']) && $result['status'] == 'success') {
        $CMSNT->update('suppliers', [
            'price' => check_string(format_currency($result['data']['money'])),
            'update_gettime'    => gettime()
        ], " `id` = ? ", [$supplier['id']]);
    } else {
        $CMSNT->update('suppliers', [
            'price' => check_string($result1),
            'update_gettime'    => gettime()
        ], " `id` = ? ", [$supplier['id']]);
    }


    // CURL LẤY SẢN PHẨM
    // Kiểm tra nếu supplier có cấu trúc chuyên mục cha -> con
    $use_child_categories = isset($supplier['child']) && $supplier['child'] == 1;
    $result = listProduct_API_SHOPCLONE7($supplier['domain'], $supplier['api_key'], $supplier['proxy'], $use_child_categories);
    $result = json_decode($result, true);
    if (isset($result['status']) && $result['status'] == 'success') {

        // Xử lý theo cấu trúc chuyên mục cha -> con -> sản phẩm
        if ($use_child_categories) {
            foreach ($result['categories'] as $parent_category) {
                // XỬ LÝ CHUYÊN MỤC CHA
                $parent_category_id = 0;
                if ($supplier['sync_category'] == 'ON') {
                    $parent_category_name = validate_string($parent_category['name'], 255, 1);
                    if ($parent_category_name === false) continue;

                    $parent_api_id = validate_alphanumeric($parent_category['id'], 50);
                    if ($parent_api_id === false) continue;

                    // Tìm hoặc tạo chuyên mục cha theo name
                    if (!$parent_cat_api = $CMSNT->get_row_safe(" SELECT * FROM `categories` WHERE `name` = ? AND `parent_id` = 0 ", [$parent_category_name])) {
                        // Tạo mới chuyên mục cha
                        $rand = '_' . random('QWERTTYUIOPASDFGHJKLZXCVBNM123456789', 6);
                        $uploads_dir = '../../assets/storage/images/category' . $rand . '.png';
                        $url_image = $CMSNT->site('favicon');
                        if (isset($supplier['sync_category_image']) && $supplier['sync_category_image'] == 'ON') {
                            $image = @imagecreatefrompng($parent_category['icon']);
                            if ($image) {
                                if (imagepng($image, $uploads_dir)) {
                                    $url_image = 'assets/storage/images/category' . $rand . '.png';
                                }
                                imagedestroy($image);
                            }
                        }
                        $isInsert = $CMSNT->insert('categories', [
                            'parent_id'         => 0,
                            'id_api'            => $parent_api_id,
                            'supplier_id'       => $supplier['id'],
                            'status'            => 1,
                            'name'              => $parent_category_name,
                            'slug'              => create_slug($parent_category_name . $parent_api_id),
                            'title'             => isset($parent_category['title']) ? validate_string($parent_category['title'], 255) : '',
                            'description'       => isset($parent_category['description']) ? validate_string($parent_category['description'], 500) : '',
                            'keywords'          => isset($parent_category['keywords']) ? validate_string($parent_category['keywords'], 500) : '',
                            'icon'              => $url_image,
                            'stt'               => isset($parent_category['stt']) ? intval($parent_category['stt']) : 0,
                            'create_date'       => gettime(),
                            'api_time_update'   => time()
                        ]);
                        if ($isInsert) {
                            $parent_category_id = $isInsert;
                            echo '<b style="color:red;">CREATE</b> - Tạo chuyên mục cha ' . $parent_category_name . ' thành công !<br>';
                        }
                    } else {
                        $parent_category_id = $parent_cat_api['id'];
                        $parent_stt = isset($parent_category['stt']) ? intval($parent_category['stt']) : 0;
                        //
                        $CMSNT->update('categories', [
                            'name'          => $parent_category_name,
                            'slug'          => create_slug($parent_category_name . $parent_api_id),
                            'title'         => isset($parent_category['title']) ? validate_string($parent_category['title'], 255) : '',
                            'description'   => isset($parent_category['description']) ? validate_string($parent_category['description'], 500) : '',
                            'keywords'      => isset($parent_category['keywords']) ? validate_string($parent_category['keywords'], 500) : '',
                            'stt'           => $parent_stt,
                            'api_time_update'   => time()
                        ], " `id` = ? ", [$parent_category_id]);
                        echo '<b style="color:blue;">UPDATE</b> - Cập nhật chuyên mục cha "' . $parent_category_name . '" !<br>';
                    }
                }

                // XỬ LÝ CHUYÊN MỤC CON
                foreach ($parent_category['child_categories'] as $child_category) {
                    $category_id = 0;
                    if ($supplier['sync_category'] == 'ON') {
                        $category_name = validate_string($child_category['name'], 255, 1);
                        if ($category_name === false) continue;

                        $child_api_id = validate_alphanumeric($child_category['id'], 50);
                        if ($child_api_id === false) continue;

                        // Tìm chuyên mục con theo name
                        if (!$category_api = $CMSNT->get_row_safe(" SELECT * FROM `categories` WHERE `name` = ? ", [$category_name])) {
                            // Tạo mới chuyên mục con
                            $rand = '_' . random('QWERTTYUIOPASDFGHJKLZXCVBNM123456789', 6);
                            $uploads_dir = '../../assets/storage/images/category' . $rand . '.png';
                            $url_image = $CMSNT->site('favicon');
                            if (isset($supplier['sync_category_image']) && $supplier['sync_category_image'] == 'ON') {
                                $image = @imagecreatefrompng($child_category['icon']);
                                if ($image) {
                                    if (imagepng($image, $uploads_dir)) {
                                        $url_image = 'assets/storage/images/category' . $rand . '.png';
                                    }
                                    imagedestroy($image);
                                }
                            }
                            $isInsert = $CMSNT->insert('categories', [
                                'parent_id'         => $parent_category_id,
                                'id_api'            => $child_api_id,
                                'supplier_id'       => $supplier['id'],
                                'status'            => 1,
                                'name'              => $category_name,
                                'slug'              => create_slug($category_name),
                                'title'             => isset($child_category['title']) ? validate_string($child_category['title'], 255) : '',
                                'description'       => isset($child_category['description']) ? validate_string($child_category['description'], 500) : '',
                                'keywords'          => isset($child_category['keywords']) ? validate_string($child_category['keywords'], 500) : '',
                                'icon'              => $url_image,
                                'stt'               => isset($child_category['stt']) ? intval($child_category['stt']) : 0,
                                'create_date'       => gettime(),
                                'api_time_update'   => time()
                            ]);
                            if ($isInsert) {
                                // Lấy id chuyên mục vừa thêm
                                $category_id = $isInsert;
                                echo '<b style="color:red;">CREATE</b> - Tạo chuyên mục con ' . $category_name . ' thành công !<br>';
                            }
                        } else {
                            $category_id = $category_api['id'];

                            // Kiểm tra nếu tên hoặc stt thay đổi → cập nhật
                            $child_stt = isset($child_category['stt']) ? intval($child_category['stt']) : 0;
                            $CMSNT->update('categories', [
                                'parent_id'     => $parent_category_id,
                                'name'          => $category_name,
                                'slug'          => create_slug($category_name),
                                'title'         => isset($child_category['title']) ? validate_string($child_category['title'], 255) : '',
                                'description'   => isset($child_category['description']) ? validate_string($child_category['description'], 500) : '',
                                'keywords'      => isset($child_category['keywords']) ? validate_string($child_category['keywords'], 500) : '',
                                'stt'           => $child_stt,
                                'api_time_update'   => time()
                            ], " `id` = ? ", [$category_id]);
                            echo '<b style="color:blue;">UPDATE</b> - Cập nhật chuyên mục con "' . $category_name . '" !<br>';
                        }
                    }

                    // XỬ LÝ SẢN PHẨM CỦA CHUYÊN MỤC CON
                    foreach ($child_category['products'] as $api) {
                        $api_id = validate_alphanumeric($api['id'], 100);
                        if ($api_id === false) continue;

                        $api_name = $supplier['check_string_api'] == 'OFF' ? $api['name'] : validate_string($api['name'], 500, 1);
                        $api_short_desc = $supplier['check_string_api'] == 'OFF' ? (isset($api['short_desc']) ? $api['short_desc'] : '') : (isset($api['short_desc']) ? validate_string($api['short_desc'], 1000) : '');
                        $api_description = $supplier['check_string_api'] == 'OFF' ? (isset($api['description']) ? $api['description'] : '') : (isset($api['description']) ? validate_string($api['description'], 10000) : '');
                        $api_stock = validate_int($api['amount'], 0);
                        $api_price = validate_float($api['price'], 0);

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
                        if (!$product = $CMSNT->get_row_safe(" SELECT * FROM `products` WHERE `api_id` = ? AND `supplier_id` = ? ", [$api_id, $supplier['id']])) {
                            // THÊM SẢN PHẨM
                            $product_status = (isset($supplier['isAutoShow']) && $supplier['isAutoShow'] == 1) ? 1 : 0;
                            $CMSNT->insert('products', [
                                'user_id'           => $supplier['user_id'],
                                'category_id'       => $category_id,
                                'supplier_id'       => $supplier['id'],
                                'name'              => $api_name,
                                'slug'              => create_slug($api_name . $api_id),
                                'short_desc'        => $api_short_desc,
                                'description'       => $api_description,
                                'price'             => $price,
                                'status'            => $product_status,
                                'cost'              => $api_price,
                                'api_id'            => $api_id,
                                'api_name'          => $api_name,
                                'api_stock'         => $api_stock,
                                'stt'               => isset($api['stt']) ? intval($api['stt']) : 0,
                                'api_time_update'   => time(),
                                'create_gettime'    => gettime(),
                                'update_gettime'   => gettime()
                            ]);
                            if ($CMSNT->site('debug_api_suppliers') == 1) {
                                echo '<b style="color:red;">CREATE</b> - Tạo sản phẩm ' . $api_name . ' thành công !<br>';
                            }
                        } else {
                            // CẬP NHẬT SẢN PHẨM
                            $api_name = $supplier['check_string_api'] == 'OFF' ? $api['name'] : validate_string($api['name'], 500, 1);
                            $api_short_desc = $supplier['check_string_api'] == 'OFF' ? (isset($api['short_desc']) ? $api['short_desc'] : '') : (isset($api['short_desc']) ? validate_string($api['short_desc'], 1000) : '');
                            $api_description = $supplier['check_string_api'] == 'OFF' ? (isset($api['description']) ? $api['description'] : '') : (isset($api['description']) ? validate_string($api['description'], 10000) : '');
                            $api_stock = validate_int($api['amount'], 0);
                            $api_price = validate_float($api['price'], 0);

                            if ($api_name === false || $api_stock === false || $api_price === false) continue;

                            // Quy đổi rate tiền tệ nếu có
                            if (isset($supplier['rate']) && $supplier['rate'] != 1 && $supplier['rate'] > 0) {
                                $api_price = $api_price * $supplier['rate'];
                            }
                            $ck = $api_price * $supplier['discount'] / 100;

                            $price = $product['price'];
                            if ($supplier['update_price'] == 'ON') {
                                if ($supplier['roundMoney'] == 'ON') {
                                    $price = roundMoney($api_price + $ck);
                                } else {
                                    $price = $api_price + $ck;
                                }
                            }
                            $product_name = $api_name;
                            $product_short_desc = $api_short_desc;
                            $product_description = $api_description;
                            $product_slug = create_slug($product_name . $api_id);
                            if ($supplier['update_name'] == 'OFF') {
                                $product_name = $product['name'];
                                $product_short_desc = $product['short_desc'];
                                $product_description = $product['description'];
                                $product_slug = $product['slug'];
                            }

                            // Cập nhật sản phẩm, bao gồm category_id nếu thay đổi
                            $update_data = [
                                'category_id'   => $category_id,
                                'price'         => $price,
                                'name'          => $product_name,
                                'slug'          => $product_slug,
                                'short_desc'    => $product_short_desc,
                                'description'   => $product_description,
                                'cost'          => $api_price,
                                'api_name'      => $api_name,
                                'stt'           => isset($api['stt']) ? intval($api['stt']) : 0,
                                'api_time_update'    => time(),
                                'api_stock'     => $api_stock
                            ];
                            $CMSNT->update('products', $update_data, " `id` = ? ", [$product['id']]);

                            if ($CMSNT->site('debug_api_suppliers') == 1) {
                                $msg = '<b style="color:green;">UPDATE</b> - sản phẩm ' . $api_name . ' thành công !';
                                if ($product['category_id'] != $category_id) {
                                    $msg .= ' (Đã chuyển chuyên mục)';
                                }
                                echo $msg . '<br>';
                            }
                        }
                    }
                }
            }
        } else {
            // Xử lý theo cấu trúc cũ: chuyên mục con -> sản phẩm
            foreach ($result['categories'] as $category) {

                $category_id = 0; // Mặc định ID chuyên mục sẽ không có
                if ($supplier['sync_category'] == 'ON') {
                    $category_name = validate_string($category['name'], 255, 1);
                    if ($category_name === false) continue;

                    $category_api_id = validate_alphanumeric($category['id'], 50);
                    if ($category_api_id === false) continue;

                    // TÌM CHUYÊN MỤC THEO NAME
                    if (!$category_api = $CMSNT->get_row_safe(" SELECT * FROM `categories` WHERE `name` = ? ", [$category_name])) {
                        // Tạo mới chuyên mục
                        $rand = '_' . random('QWERTTYUIOPASDFGHJKLZXCVBNM123456789', 6);
                        $uploads_dir = '../../assets/storage/images/category' . $rand . '.png';
                        $url_image = $CMSNT->site('favicon'); // Fallback URL
                        // Attempt to create image from category image URL
                        if (isset($supplier['sync_category_image']) && $supplier['sync_category_image'] == 'ON') {
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
                            'title'             => isset($category['title']) ? validate_string($category['title'], 255) : '',
                            'description'       => isset($category['description']) ? validate_string($category['description'], 500) : '',
                            'keywords'          => isset($category['keywords']) ? validate_string($category['keywords'], 500) : '',
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
                            'title'         => isset($category['title']) ? validate_string($category['title'], 255) : '',
                            'description'   => isset($category['description']) ? validate_string($category['description'], 500) : '',
                            'keywords'      => isset($category['keywords']) ? validate_string($category['keywords'], 500) : '',
                            'api_time_update'   => time()
                        ], " `id` = ? ", [$category_id]);
                        echo '<b style="color:blue;">UPDATE</b> - Cập nhật chuyên mục "' . $category_name . '" !<br>';
                    }
                }

                foreach ($category['products'] as $api) {

                    $api_id = validate_alphanumeric($api['id'], 100);
                    if ($api_id === false) continue;

                    $api_name = $supplier['check_string_api'] == 'OFF' ? $api['name'] : validate_string($api['name'], 500, 1);
                    $api_desc = $supplier['check_string_api'] == 'OFF' ? $api['description'] : validate_string($api['description'], 5000);
                    $api_stock = validate_int($api['amount'], 0);
                    $api_price = validate_float($api['price'], 0);

                    if ($api_name === false || $api_stock === false || $api_price === false) continue;

                    // Quy đổi rate tiền tệ nếu có
                    if (isset($supplier['rate']) && $supplier['rate'] != 1 && $supplier['rate'] > 0) {
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
                    if (!$product = $CMSNT->get_row_safe(" SELECT * FROM `products` WHERE `api_id` = ? AND `supplier_id` = ? ", [$api_id, $supplier['id']])) {
                        // THÊM SẢN PHẨM
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
                        $api_name = $supplier['check_string_api'] == 'OFF' ? $api['name'] : validate_string($api['name'], 500, 1);
                        $api_desc = $supplier['check_string_api'] == 'OFF' ? $api['description'] : validate_string($api['description'], 5000);
                        $api_stock = validate_int($api['amount'], 0);
                        $api_price = validate_float($api['price'], 0);

                        if ($api_name === false || $api_stock === false || $api_price === false) continue;

                        // Quy đổi rate tiền tệ nếu có
                        if (isset($supplier['rate']) && $supplier['rate'] != 1 && $supplier['rate'] > 0) {
                            $api_price = $api_price * $supplier['rate'];
                        }
                        $ck = $api_price * $supplier['discount'] / 100;

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
                            'api_name'      => $api_name,
                            'api_time_update'    => time(),
                            'api_stock'     => $api_stock
                        ], " `id` = ? ", [$product['id']]);
                        if ($CMSNT->site('debug_api_suppliers') == 1) {
                            echo '<b style="color:green;">UPDATE</b> - sản phẩm ' . $api_name . ' thành công !<br>';
                        }
                    }
                }
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
}
