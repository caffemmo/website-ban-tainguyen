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
    $elapsed = time() - (int)$CMSNT->site('time_cron_suppliers_api33');
    if ($elapsed >= 0 && $elapsed < 5) {
        die('Thao tác quá nhanh, vui lòng thử lại sau!');
    }
    $CMSNT->update("settings", [
        'value' => time()
    ], " `name` = 'time_cron_suppliers_api33' ");



    foreach($CMSNT->get_list_safe(" SELECT * FROM `suppliers` WHERE `status` = ? AND `type` = ? ", [1, 'API_33']) as $supplier){
        // CẬP NHẬT SỐ DƯ API
        if(!empty($supplier['username']) && !empty($supplier['password'])){
            $result1 = getToken_API_33($supplier['domain'], $supplier['username'], $supplier['password'], $supplier['proxy']);
            $result = json_decode($result1, true);
            if(isset($result['code']) && $result['code'] == '200000'){
                $CMSNT->update('suppliers', [
                    'price' => format_currency(check_string($result['balance'])),
                    'token' => check_string($result['data']),
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
        $result = listProduct_API_33($supplier['domain'], $supplier['proxy']);
        $result = json_decode($result, true);
        if(isset($result) && $result['code'] == '200000'){
            $category_id = 0; // Mặc định ID chuyên mục sẽ không có
            foreach($result['data'] as $api){
                if(!isset($api['prices'])){
                    continue;
                }
                foreach($api['prices'] as $api1){
                    $api_id = check_string($api1['id']);

                    $api_name = $api['name'].' '.$api1['min_day'].' ngày';
                    $api_name = $supplier['check_string_api'] == 'OFF' ? $api_name : check_string($api_name);

                    // Tạo mô tả đẹp từ dữ liệu API
                    $desc_parts = array();
                    if(!empty($api['brief'])) {
                        $desc_parts[] = $api['brief'];
                    }
                    if(!empty($api['name'])) {
                        $chip_info = $api['name'];
                        if(!empty($api['short_name'])) {
                            $chip_info .= ' ('.$api['short_name'].')';
                        }
                        $desc_parts[] = 'Chip: '.$chip_info;
                    } else if(!empty($api['chip'])) {
                        $desc_parts[] = 'Chip: '.$api['chip'];
                    }
                    if(!empty($api['ram'])) {
                        $desc_parts[] = 'RAM: '.$api['ram'].'GB';
                    }
                    if(!empty($api['memory'])) {
                        $desc_parts[] = 'Bộ nhớ: '.$api['memory'].'GB';
                    }
                    $api_desc = implode(' | ', $desc_parts);
                    $api_desc = $supplier['check_string_api'] == 'OFF' ? $api_desc : check_string($api_desc);

                    $api_stock = intval(999);
                    $api_price = check_string($api1['amount'] * $api1['min_day']); // Giá gốc * min
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
                        $api_name = $api['name'].' '.$api1['min_day'].' ngày';
                        $api_name = $supplier['check_string_api'] == 'OFF' ? $api_name : check_string($api_name);

                        // Tạo mô tả đẹp từ dữ liệu API
                        $desc_parts = array();
                        if(!empty($api['brief'])) {
                            $desc_parts[] = $api['brief'];
                        }
                        if(!empty($api['name'])) {
                            $chip_info = $api['name'];
                            if(!empty($api['short_name'])) {
                                $chip_info .= ' ('.$api['short_name'].')';
                            }
                            $desc_parts[] = 'Chip: '.$chip_info;
                        } else if(!empty($api['chip'])) {
                            $desc_parts[] = 'Chip: '.$api['chip'];
                        }
                        if(!empty($api['ram'])) {
                            $desc_parts[] = 'RAM: '.$api['ram'].'GB';
                        }
                        if(!empty($api['memory'])) {
                            $desc_parts[] = 'Bộ nhớ: '.$api['memory'].'GB';
                        }
                        $api_desc = implode(' | ', $desc_parts);
                        $api_desc = $supplier['check_string_api'] == 'OFF' ? $api_desc : check_string($api_desc);

                        $api_stock = intval(999);
                        $api_price = check_string($api1['amount'] * $api1['min_day']); // Giá gốc * min
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
        }
        $CMSNT->remove('products', " `supplier_id` = '".$supplier['id']."' AND ".time()." - `api_time_update` >= 3600 ");

    }