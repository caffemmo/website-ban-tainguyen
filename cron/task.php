<?php

    define("IN_SITE", true);

    require_once(__DIR__.'/../libs/db.php');
    require_once(__DIR__.'/../config.php');
    require_once(__DIR__.'/../libs/lang.php');
    require_once(__DIR__.'/../libs/helper.php');
    $CMSNT = new DB();
 

    // Nếu có đặt key cron job thì kiểm tra key hợp lệ
    if(!empty($CMSNT->site('key_cron_job'))){
        if(empty($_GET['key']) || $_GET['key'] != $CMSNT->site('key_cron_job')){
            die(__('Key không hợp lệ'));
        }
    }
    
    /* START CHỐNG SPAM */
    $elapsed = time() - (int)$CMSNT->site('check_time_cron_task');
    if ($elapsed >= 0 && $elapsed < 3) {
        die('Thao tác quá nhanh, vui lòng thử lại sau!');
    }
    $CMSNT->update("settings", [
        'value' => time()
    ], " `name` = 'check_time_cron_task' ");


    foreach($CMSNT->get_list_safe(" SELECT * FROM `automations` ", []) as $task){

        // XÓA LỊCH SỬ NẠP TIỀN
        if($task['type'] == 'delete_history_topup'){
            $CMSNT->remove('payment_momo', " ".time()." - UNIX_TIMESTAMP(create_gettime) >= ".$task['schedule']." ");
            $CMSNT->remove('payment_bank', " ".time()." - UNIX_TIMESTAMP(create_gettime) >= ".$task['schedule']." ");
            $CMSNT->remove('payment_crypto', " ".time()." - UNIX_TIMESTAMP(create_gettime) >= ".$task['schedule']." ");
            $CMSNT->remove('payment_thesieure', " ".time()." - UNIX_TIMESTAMP(create_gettime) >= ".$task['schedule']." ");
            $CMSNT->insert('logs', [
                'user_id' => 0,
                'ip' => myip(),
                'device' => 'cron_task_' . $task['id'],
                'createdate' => gettime(),
                'action' => __('[Cron] Xóa lịch sử nạp tiền cũ')
            ]);
        }
        
        // XÓA BIẾN ĐỘNG SỐ DƯ
        if($task['type'] == 'delete_history_dongtien'){
            $CMSNT->remove('dongtien', " ".time()." - UNIX_TIMESTAMP(thoigian) >= ".$task['schedule']." ");
            $CMSNT->insert('logs', [
                'user_id' => 0,
                'ip' => myip(),
                'device' => 'cron_task_' . $task['id'],
                'createdate' => gettime(),
                'action' => __('[Cron] Xóa biến động số dư cũ')
            ]);
        }
        
        // CHUYỂN KHO HÀNG
        if($task['type'] == 'change_warehouse'){
            if($task['product_id'] == ''){
                foreach($CMSNT->get_list_safe(" SELECT * FROM `product_stock` WHERE ? - UNIX_TIMESTAMP(create_gettime) >= ? ", [time(), $task['schedule']]) as $product_stock){
                    // CHUYỂN KHO HÀNG
                    $isUpdate = $CMSNT->update('product_stock', [
                        'product_code'      => $task['other'],
                        'create_gettime'    => gettime()
                    ], " `id` = '".$product_stock['id']."' ");
                }
            }else{
                foreach(json_decode($task['product_id'], true) as $product){
                    if($product_code = $CMSNT->get_row_safe(" SELECT * FROM `products` WHERE `id` = ? ", [$product])['code']){
                        // CHUYỂN KHO HÀNG
                        $isUpdate = $CMSNT->update('product_stock', [
                            'product_code'      => $task['other'],
                            'create_gettime'    => gettime()
                        ], " `product_code` = '$product_code' AND ".time()." - UNIX_TIMESTAMP(create_gettime) >= ".$task['schedule']." ");
                        
                    }
                }
            }
            $CMSNT->insert('logs', [
                'user_id' => 0,
                'ip' => myip(),
                'device' => 'cron_task_' . $task['id'],
                'createdate' => gettime(),
                'action' => __('[Cron] Chuyển kho hàng tự động')
            ]);
        }

        // XÓA TÀI KHOẢN ĐÃ BÁN
        if($task['type'] == 'delete_order'){
            if($task['product_id'] == ''){
                // ẨN ĐƠN HÀNG ĐỦ THỜI GIAN
                $CMSNT->update('product_order', [
                    'trash'     => 1
                ], " ".time()." - UNIX_TIMESTAMP(create_gettime) >= ".$task['schedule']." ");
                // XÓA TÀI KHOẢN ĐÃ BÀN ĐỦ THỜI GIAN
                $isRemove = $CMSNT->remove('product_sold', " ".time()." - UNIX_TIMESTAMP(create_gettime) >= ".$task['schedule']." ");
            }else{
                foreach(json_decode($task['product_id'], true) as $product){
                    foreach($CMSNT->get_list_safe(" SELECT * FROM `product_order` WHERE `product_id` = ? AND ? - UNIX_TIMESTAMP(create_gettime) >= ? AND `trash` = ? ", [$product, time(), $task['schedule'], 0]) as $product_order){
                        // ẨN ĐƠN HÀNG ĐỦ THỜI GIAN
                        $CMSNT->update('product_order', [
                            'trash'     => 1
                        ], " `trans_id` = '".$product_order['trans_id']."' ");
                        // XÓA TÀI KHOẢN ĐÃ BÀN ĐỦ THỜI GIAN
                       $isRemove = $CMSNT->remove('product_sold', " `trans_id` = '".$product_order['trans_id']."' ");
                    }
                }
            }
            $CMSNT->insert('logs', [
                'user_id' => 0,
                'ip' => myip(),
                'device' => 'cron_task_' . $task['id'],
                'createdate' => gettime(),
                'action' => __('[Cron] Xóa tài khoản đã bán')
            ]);
        }
        // XÓA TÀI KHOẢN ĐÃ BÁN KHÔNG XÓA UID
        if($task['type'] == 'delete_order_not_uid'){
            if($task['product_id'] == ''){
                // ẨN ĐƠN HÀNG ĐỦ THỜI GIAN
                $CMSNT->update('product_order', [
                    'trash'     => 1
                ], " ".time()." - UNIX_TIMESTAMP(create_gettime) >= ".$task['schedule']." ");
                // XÓA TÀI KHOẢN ĐÃ BÀN ĐỦ THỜI GIAN
                $isUpdate = $CMSNT->update('product_sold', [
                    'account'  => __('Tài khoản đã được xóa tự động')
                ], " ".time()." - UNIX_TIMESTAMP(create_gettime) >= ".$task['schedule']." ");
            }else{
                foreach(json_decode($task['product_id'], true) as $product){
                    foreach($CMSNT->get_list_safe(" SELECT * FROM `product_order` WHERE `product_id` = ? AND ? - UNIX_TIMESTAMP(create_gettime) >= ? AND `trash` = ? ", [$product, time(), $task['schedule'], 0]) as $product_order){
                        // ẨN ĐƠN HÀNG ĐỦ THỜI GIAN
                        $CMSNT->update('product_order', [
                            'trash'     => 1
                        ], " `trans_id` = '".$product_order['trans_id']."' ");
                        
                        // XÓA TÀI KHOẢN ĐÃ BÀN ĐỦ THỜI GIAN
                        $isUpdate = $CMSNT->update('product_sold', [
                            'account'  => __('Tài khoản đã được xóa tự động')
                        ]," `trans_id` = '".$product_order['trans_id']."' ");
                    }
                }
            }
            $CMSNT->insert('logs', [
                'user_id' => 0,
                'ip' => myip(),
                'device' => 'cron_task_' . $task['id'],
                'createdate' => gettime(),
                'action' => __('[Cron] Xóa tài khoản đã bán (giữ UID)')
            ]);
        }
        // XÓA ĐƠN HÀNG & TÀI KHOẢN ĐÃ BÁN
        if($task['type'] == 'delete_order_revenue'){
            if($task['product_id'] == ''){
                // XÓA ĐƠN HÀNG ĐỦ THỜI GIAN
                $CMSNT->remove('product_order', " ".time()." - UNIX_TIMESTAMP(create_gettime) >= ".$task['schedule']." ");
                // XÓA TÀI KHOẢN ĐÃ BÀN ĐỦ THỜI GIAN
                $isRemove = $CMSNT->remove('product_sold', " ".time()." - UNIX_TIMESTAMP(create_gettime) >= ".$task['schedule']." ");
            }else{
                foreach(json_decode($task['product_id'], true) as $product){
                    foreach($CMSNT->get_list_safe(" SELECT * FROM `product_order` WHERE `product_id` = ? AND ? - UNIX_TIMESTAMP(create_gettime) >= ? ", [$product, time(), $task['schedule']]) as $product_order){
                        // XÓA ĐƠN HÀNG ĐỦ THỜI GIAN
                        $CMSNT->remove('product_order', " `trans_id` = '".$product_order['trans_id']."' ");
                        // XÓA TÀI KHOẢN ĐÃ BÀN ĐỦ THỜI GIAN
                        $isRemove = $CMSNT->remove('product_sold', " `trans_id` = '".$product_order['trans_id']."' ");
                    }
                }
            }
            $CMSNT->insert('logs', [
                'user_id' => 0,
                'ip' => myip(),
                'device' => 'cron_task_' . $task['id'],
                'createdate' => gettime(),
                'action' => __('[Cron] Xóa đơn hàng & tài khoản đã bán')
            ]);
        }
        
        // XÓA USER KHÔNG NẠP TIỀN
        if($task['type'] == 'delete_user_no_topup'){
            // XÓA USER CÓ total_money = 0 VÀ money = 0 VÀ ĐÃ ĐĂNG KÝ QUÁ THỜI GIAN SCHEDULE VÀ CHƯA CÓ DỮ LIỆU REF
            $CMSNT->remove('users', " `total_money` = 0 AND `money` = 0 AND `admin` = 0 AND `ref_amount` = 0 AND `ref_price` = 0 AND `ref_total_price` = 0 AND ".time()." - UNIX_TIMESTAMP(update_date) >= ".$task['schedule']." ");
            $CMSNT->insert('logs', [
                'user_id' => 0,
                'ip' => myip(),
                'device' => 'cron_task_' . $task['id'],
                'createdate' => gettime(),
                'action' => __('[Cron] Xóa user không nạp tiền')
            ]);
        }

        // DỌN DẸP ẢNH RÁC (orphan images)
        if($task['type'] == 'cleanup_orphan_images'){
            $imageDir = __DIR__ . '/../assets/storage/images/';
            $relativeDir = 'assets/storage/images/';

            if (is_dir($imageDir)) {
                // 1. Thu thập tất cả file ảnh (chỉ file trực tiếp, BỎ QUA thư mục con)
                $allFiles = [];
                $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
                $dirHandle = opendir($imageDir);
                if ($dirHandle) {
                    while (($file = readdir($dirHandle)) !== false) {
                        if ($file === '.' || $file === '..') continue;
                        $fullPath = $imageDir . $file;
                        if (!is_file($fullPath)) continue;
                        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        if (in_array($ext, $allowedExts)) {
                            $allFiles[$relativeDir . $file] = $fullPath;
                        }
                    }
                    closedir($dirHandle);
                }

                if (!empty($allFiles)) {
                    // 2. Thu thập tất cả đường dẫn ảnh đang được sử dụng trong DB
                    $usedImages = [];

                    // categories.icon
                    $catIcons = $CMSNT->get_list("SELECT `icon` FROM `categories` WHERE `icon` IS NOT NULL AND `icon` != ''");
                    foreach ($catIcons as $row) {
                        $usedImages[$row['icon']] = true;
                    }

                    // products.image
                    $prodImages = $CMSNT->get_list("SELECT `image` FROM `products` WHERE `image` IS NOT NULL AND `image` != ''");
                    foreach ($prodImages as $row) {
                        $usedImages[$row['image']] = true;
                    }

                    // settings (logo, favicon, banner, v.v.)
                    $settingImages = $CMSNT->get_list("SELECT `value` FROM `settings` WHERE (`name` LIKE '%image%' OR `name` LIKE '%logo%' OR `name` LIKE '%favicon%' OR `name` LIKE '%banner%' OR `name` LIKE '%icon%' OR `name` LIKE '%avatar%') AND `value` IS NOT NULL AND `value` != ''");
                    foreach ($settingImages as $row) {
                        $usedImages[$row['value']] = true;
                    }

                    // blogs.image
                    $blogImages = $CMSNT->get_list("SELECT `image` FROM `blogs` WHERE `image` IS NOT NULL AND `image` != ''");
                    foreach ($blogImages as $row) {
                        $usedImages[$row['image']] = true;
                    }

                    // blog_categories.icon
                    $blogCatIcons = $CMSNT->get_list("SELECT `icon` FROM `blog_categories` WHERE `icon` IS NOT NULL AND `icon` != ''");
                    foreach ($blogCatIcons as $row) {
                        $usedImages[$row['icon']] = true;
                    }

                    // payment_banks.image
                    $bankImages = $CMSNT->get_list("SELECT `image` FROM `payment_banks` WHERE `image` IS NOT NULL AND `image` != ''");
                    foreach ($bankImages as $row) {
                        $usedImages[$row['image']] = true;
                    }

                    // payment_manual.icon
                    $manualIcons = $CMSNT->get_list("SELECT `icon` FROM `payment_manual` WHERE `icon` IS NOT NULL AND `icon` != ''");
                    foreach ($manualIcons as $row) {
                        $usedImages[$row['icon']] = true;
                    }

                    // 3. Xóa ảnh orphan
                    $deletedCount = 0;
                    $freedBytes = 0;

                    foreach ($allFiles as $relativePath => $fullPath) {
                        if (!isset($usedImages[$relativePath])) {
                            $fileSize = filesize($fullPath);
                            if (@unlink($fullPath)) {
                                $deletedCount++;
                                $freedBytes += $fileSize;
                            }
                        }
                    }

                    // 4. Ghi log nếu có xóa
                    if ($deletedCount > 0) {
                        $freedMB = round($freedBytes / 1024 / 1024, 2);
                        $CMSNT->insert("logs", [
                            'user_id' => 0,
                            'ip' => myip(),
                            'device' => 'cron_task_' . $task['id'],
                            'createdate' => gettime(),
                            'action' => sprintf(__('[Cron] Dọn dẹp %d ảnh rác, giải phóng %s MB dung lượng'), $deletedCount, $freedMB)
                        ]);
                    }
                }
            }
        }
    }

 