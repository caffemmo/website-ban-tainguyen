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
    $elapsed = time() - (int)$CMSNT->site('time_cron_suppliers_api1');
    if ($elapsed >= 0 && $elapsed < 5) {
        die('Thao tác quá nhanh, vui lòng thử lại sau!');
    }
    $CMSNT->update("settings", [
        'value' => time()
    ], " `name` = 'time_cron_suppliers_api1' ");



    foreach($CMSNT->get_list_safe(" SELECT * FROM `suppliers` WHERE `status` = ? AND `type` = ? ", [1, 'API_1']) as $supplier){
        // CẬP NHẬT SỐ DƯ API
        $response = balance_API_1($supplier['domain'], $supplier['api_key']);
        $result = json_decode($response, true);
        if($result['status'] == true){
            $CMSNT->update('suppliers', [
                'price' => format_currency($result['balance']),
                'update_gettime'    => gettime()
            ], " `id` = ? ", [$supplier['id']]);
        }else{
            $CMSNT->update('suppliers', [
                'price' => check_string($response),
                'update_gettime'    => gettime()
            ], " `id` = ? ", [$supplier['id']]);
        }

        // CURL LẤY SẢN PHẨM
        $result = listProduct_API_1($supplier['domain']);
        $result = json_decode($result, true);
        if(isset($result['data'])){
            foreach($result['data'] as $category){
                foreach($category['list_product'] as $api){

                    $api_id = validate_alphanumeric($api['id_product']);
                    $api_name = $supplier['check_string_api'] == 'OFF' ? $api['name'] : check_string($api['name']);
                    $api_desc = $supplier['check_string_api'] == 'OFF' ? $api['desc'] : check_string($api['desc']);
                    $api_stock = intval(check_string($api['quantity']));
                    $api_price = intval(check_string($api['price']));//
                    $ck = $api_price * $supplier['discount'] / 100;
                    $price = $api['price'];
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
                        $product_status = (isset($supplier['isAutoShow']) && $supplier['isAutoShow'] == 1) ? 1 : 0;
                        $CMSNT->insert('products', [
                            'user_id'           => $supplier['user_id'],
                            'category_id'       => 0,
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
                        $api_name = $supplier['check_string_api'] == 'OFF' ? $api['name'] : check_string($api['name']);
                        $api_desc = $supplier['check_string_api'] == 'OFF' ? $api['desc'] : check_string($api['desc']);
                        $api_stock = intval(check_string($api['quantity']));//
                        $api_price = intval(check_string($api['price']));//
                        $ck = $api_price * $supplier['discount'] / 100;

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
            $CMSNT->remove('products', " `supplier_id` = '".$supplier['id']."' AND ".time()." - `api_time_update` >= 3600 ");
        }

    }
