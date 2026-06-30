<?php

    define("IN_SITE", true);
    require_once(__DIR__.'/../../libs/db.php');
    require_once(__DIR__.'/../../config.php');
    require_once(__DIR__.'/../../libs/lang.php');
    require_once(__DIR__.'/../../libs/helper.php');
    require_once(__DIR__.'/../../libs/suppliers.php');
    $CMSNT = new DB();

    // Nếu có đặt key cron job thì kiểm tra key hợp lệ
    if(!empty($CMSNT->site('key_cron_job'))){
        if(empty($_GET['key']) || $_GET['key'] != $CMSNT->site('key_cron_job')){
            die(__('Key không hợp lệ'));
        }
    }

    /* START CHỐNG SPAM */
    $elapsed = time() - (int)$CMSNT->site('time_cron_suppliers_api35');
    if ($elapsed >= 0 && $elapsed < 5) {
        die('Thao tác quá nhanh, vui lòng thử lại sau!');
    }
    $CMSNT->update("settings", [
        'value' => time()
    ], " `name` = 'time_cron_suppliers_api35' ");



    foreach($CMSNT->get_list_safe(" SELECT * FROM `suppliers` WHERE `status` = ? AND `type` = ? ", [1, 'API_35']) as $supplier){
        // CẬP NHẬT SỐ DƯ API - api_key lưu email, token lưu token
        if(!empty($supplier['api_key']) && !empty($supplier['token'])){
            $result1 = balance_API_35($supplier['domain'], $supplier['api_key'], $supplier['token'], $supplier['proxy']);
            $result = json_decode($result1, true);
            if(isset($result['success']) && $result['success'] == true){
                $balance_wmr = isset($result['data']['balance_wmr']) ? $result['data']['balance_wmr'] : 0;
                $balance_usd = isset($result['data']['balance_usd']) ? $result['data']['balance_usd'] : 0;
                $CMSNT->update('suppliers', [
                    'price' => $balance_wmr . ' WMR / $' . $balance_usd . ' USD',
                    'update_gettime'    => gettime()
                ], " `id` = ? ", [$supplier['id']]);
            }else{
                $errorMsg = isset($result['message']) ? $result['message'] : 'Kết nối đến API không thành công!';
                $CMSNT->update('suppliers', [
                    'price' => check_string($errorMsg),
                    'update_gettime'    => gettime()
                ], " `id` = ? ", [$supplier['id']]);
            }
        }


        // CURL LẤY SẢN PHẨM
        $result = listProduct_API_35($supplier['domain'], $supplier['proxy']);
        $result = json_decode($result, true);
        if(isset($result) && isset($result['success']) && $result['success'] == true && isset($result['data']['categories'])){
            
            // Lặp qua từng category
            foreach($result['data']['categories'] as $category){
                $category_id = 0; // Mặc định ID chuyên mục sẽ không có
                $category_name = isset($category['category_name']) ? $category['category_name'] : '';
                
                // Lặp qua từng item trong category
                if(!isset($category['items']) || !is_array($category['items'])) continue;
                
                foreach($category['items'] as $api){
                    $api_id = check_string($api['id']);

                    $api_name = isset($api['name']) ? $api['name'] : '';
                    $api_name = $supplier['check_string_api'] == 'OFF' ? $api_name : check_string($api_name);

                    // Lấy mô tả từ API
                    $api_desc = isset($api['description']) ? $api['description'] : '';
                    // Nếu bật check_string_api thì lọc HTML
                    if($supplier['check_string_api'] == 'ON'){
                        $api_desc = check_string(strip_tags($api_desc));
                    }

                    $api_stock = isset($api['stock']) ? intval($api['stock']) : 0;
                    
                    // Lấy giá USD (price_usd) hoặc WMR (price_wmr)
                    $api_price = isset($api['price_usd']) ? floatval($api['price_usd']) : 0;
                    
                    // Áp dụng tỷ giá nếu có
                    if(!empty($supplier['rate']) && $supplier['rate'] != 1){
                        $api_price = $api_price * $supplier['rate'];
                    }
                    
                    $ck = $api_price * $supplier['discount'] / 100;
                    $price = $api_price;
                    if($supplier['update_price'] == 'ON'){
                        // CẬP NHẬT GIÁ BÁN
                        if($supplier['roundMoney'] == 'ON'){
                            // LÀM TRÒN GIÁ BÁN
                            $price = roundMoney($api_price + $ck);
                        }else{
                            $price = $api_price + $ck;
                        } 
                    } 
                    
                    if(!$product = $CMSNT->get_row_safe(" SELECT * FROM `products` WHERE `api_id` = ? AND `supplier_id` = ? ", [$api_id, $supplier['id']])){
                        // THÊM SẢN PHẨM
                        // Xác định trạng thái sản phẩm dựa vào isAutoShow
                        $product_status = (isset($supplier['isAutoShow']) && $supplier['isAutoShow'] == 1) ? 1 : 0;
                        $CMSNT->insert('products', [
                            'user_id'           => $supplier['user_id'],
                            'category_id'       => $category_id,
                            'supplier_id'       => $supplier['id'],
                            'name'              => $api_name,
                            'slug'              => create_slug($api_name.$api_id),
                            'short_desc'        => $api_desc,
                            'price'             => $price,
                            'status'            => $product_status,
                            'cost'              => $api_price,
                            'api_id'            => $api_id,
                            'api_name'          => $api_name,
                            'api_stock'         => $api_stock,
                            'api_time_update'   => time(),
                            'create_gettime'    => gettime(),
                            'update_gettime'   => gettime()
                        ]);
                        if($CMSNT->site('debug_api_suppliers') == 1){
                            echo '<b style="color:red;">CREATE</b> - Tạo sản phẩm '.$api_name.' thành công !<br>';
                        }
                    }else{
                        // CẬP NHẬT SẢN PHẨM
                        $price = $product['price'];
                        if($supplier['update_price'] == 'ON'){
                            // CẬP NHẬT GIÁ BÁN
                            if($supplier['roundMoney'] == 'ON'){
                                // LÀM TRÒN GIÁ BÁN
                                $price = roundMoney($api_price + $ck);
                            }else{
                                $price = $api_price + $ck;
                            } 
                        } 
                        $product_name = $api_name;
                        $product_desc = $api_desc;
                        $product_slug = create_slug($product_name.$api_id);
                        if($supplier['update_name'] == 'OFF'){
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
                        ], " `id` = '".$product['id']."' ");
                        if($CMSNT->site('debug_api_suppliers') == 1){
                            echo '<b style="color:green;">UPDATE</b> - sản phẩm '.$api_name.' thành công !<br>';
                        }
                    }
                }
            }
        }
        // Xóa sản phẩm không còn tồn tại trên API sau 1 giờ
        $CMSNT->remove('products', " `supplier_id` = '".$supplier['id']."' AND ".time()." - `api_time_update` >= 3600 ");

    }
