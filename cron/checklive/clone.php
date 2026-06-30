<?php

    define("IN_SITE", true);
    require_once(__DIR__.'/../../libs/db.php');
    require_once(__DIR__.'/../../config.php');
    require_once(__DIR__.'/../../libs/lang.php');
    require_once(__DIR__.'/../../libs/helper.php');
    $CMSNT = new DB();

    // Nếu có đặt key cron job thì kiểm tra key hợp lệ
    if(!empty($CMSNT->site('key_cron_job'))){
        if(empty($_GET['key']) || $_GET['key'] != $CMSNT->site('key_cron_job')){
            die(__('Key không hợp lệ'));
        }
    }

    /* START CHỐNG SPAM */
    $elapsed = time() - (int)$CMSNT->site('time_cron_checklive_clone');
    if ($elapsed >= 0 && $elapsed < 3) {
        die('Thao tác quá nhanh, vui lòng thử lại sau!');
    }
    $CMSNT->update("settings", [
        'value' => time()
    ], " `name` = 'time_cron_checklive_clone' ");

    
    // Khởi tạo mảng UIDs
    $uids = [];

    // Khởi tạo mảng chứa các thông tin về sản phẩm
    $products_info = [];

    // Khởi tạo chuỗi điều kiện
    $where_is_checklive = "";

    // Lấy danh sách các sản phẩm có `check_live` là 'Clone'
    $products_list = $CMSNT->get_list_safe("SELECT * FROM `products` WHERE `check_live` = ?", ['Clone']);

    if (!empty($products_list)) {
        $product_codes = array_map(function($product) {
            return $product['code'];
        }, $products_list);

        // Chuyển đổi các mã sản phẩm thành chuỗi để sử dụng trong mệnh đề IN
        $product_codes_str = implode("','", array_map('addslashes', $product_codes));
        $where_is_checklive = " AND `product_code` IN ('$product_codes_str')";
    }else{
        die('Không có sản phẩm nào bật check live');
    }

    // TỐI ƯU 1: Tăng chunk size lên 300 để giảm overhead
    $limit = intval($CMSNT->site('limit_check_live_clone')) ?? 500;

    // Lấy danh sách sản phẩm từ CSDL
    $products = $CMSNT->get_list_safe("SELECT * FROM `product_stock` WHERE `id` > ? $where_is_checklive ORDER BY `time_check_live` ASC LIMIT ? ", [0, $limit]);

    // Lặp qua danh sách sản phẩm để tạo danh sách UIDs
    foreach ($products as $product) {
        // Kiểm tra xem UID đã tồn tại trong mảng UIDs chưa, nếu chưa thì thêm vào
        if (!in_array(check_string($product['uid']), $uids)) {
            $uids[] = check_string($product['uid']);
        }
        // Lưu thông tin sản phẩm vào mảng để sử dụng sau này
        $products_info[check_string($product['uid'])] = $product;
    }

    // TỐI ƯU 2: Tăng chunk size từ 100 lên 300
    $chunk_size = 300;
    $uid_chunks = array_chunk($uids, $chunk_size);
    $total_chunks = count($uid_chunks);

    echo "Bắt đầu kiểm tra " . count($uids) . " UIDs chia thành " . $total_chunks . " nhóm (mỗi nhóm " . $chunk_size . " UIDs).<br>";

    // Xử lý từng nhóm UID
    foreach ($uid_chunks as $chunk_index => $chunk) {
        echo "--- Đang xử lý nhóm " . ($chunk_index + 1) . "/" . $total_chunks . " (" . count($chunk) . " UIDs) ---<br>";
        
        // Khởi tạo multi-curl handler cho nhóm hiện tại
        $mh = curl_multi_init();
        $curl_handles = [];
        
        // TỐI ƯU 3: Thêm setting tối ưu cho curl_multi
        curl_multi_setopt($mh, CURLMOPT_MAX_TOTAL_CONNECTIONS, 50);
        curl_multi_setopt($mh, CURLMOPT_MAX_HOST_CONNECTIONS, 10);
        
        // Lặp qua danh sách UIDs trong nhóm hiện tại để tạo các xử lý cURL
        foreach ($chunk as $uid) {
            $ch = curl_init();
            $url = "https://graph2.facebook.com/v3.3/{$uid}/picture?redirect=0";
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,  // Timeout 10 giây
                CURLOPT_CONNECTTIMEOUT => 5,  // Connection timeout 5 giây
                CURLOPT_SSL_VERIFYPEER => false,  // Tăng tốc bỏ qua verify SSL
            ]);
            curl_multi_add_handle($mh, $ch);
            $curl_handles[$uid] = $ch;
        }
        
        // TỐI ƯU 4: Thêm curl_multi_select để tránh CPU spinning
        $running = null;
        do {
            $status = curl_multi_exec($mh, $running);
            if ($running) {
                // Chờ activity trên bất kỳ curl_multi kết nối nào
                curl_multi_select($mh, 0.1);
            }
        } while ($running > 0);
        
        // TỐI ƯU 5: Batch update thay vì update từng record
        $live_ids = [];
        $die_records = [];
        $error_ids = [];
        
        // Lặp qua các xử lý cURL để lấy kết quả và phân loại
        foreach ($curl_handles as $uid => $ch) {
            $result = curl_multi_getcontent($ch);
            $info = curl_getinfo($ch);
            
            if ($info['http_code'] == 200) {
                $result_array = json_decode($result, true);
                if (isset($result_array['data']) && (!empty($result_array['data']['height']) || !empty($result_array['data']['width']))) {
                    // UID live, lưu lại để batch update
                    $live_ids[] = $products_info[$uid]['id'];
                    echo "UID: ".substr($uid, 0, 8)."*******, Result: LIVE <br>";
                } else {
                    // UID die, lưu lại để batch insert
                    $die_records[] = [
                        'uid' => $uid,
                        'info' => $products_info[$uid]
                    ];
                    echo "UID: ".substr($uid, 0, 8)."*******, Result: DIE<br>";
                }
            } else {
                // Lưu lại các ID có lỗi để batch update
                $error_ids[] = $products_info[$uid]['id'];
                $error_message = "UID: ".substr($uid, 0, 8)."*******, Result: ERROR, HTTP Code: " . $info['http_code'];
                echo $error_message . "<br>";
            }
            
            // Đóng xử lý cURL
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        
        // Đóng multi-curl handler cho nhóm hiện tại
        curl_multi_close($mh);
        
        // TỐI ƯU 6: Batch update tất cả LIVE UIDs cùng lúc
        if (!empty($live_ids)) {
            $live_ids_str = implode(',', array_map('intval', $live_ids));
            $CMSNT->query("UPDATE `product_stock` SET `time_check_live` = ".time()." WHERE `id` IN ($live_ids_str)");
            echo "✓ Đã cập nhật " . count($live_ids) . " UIDs LIVE<br>";
        }
        
        // TỐI ƯU 7: Batch update tất cả ERROR UIDs cùng lúc
        if (!empty($error_ids)) {
            $error_ids_str = implode(',', array_map('intval', $error_ids));
            $CMSNT->query("UPDATE `product_stock` SET `time_check_live` = ".time()." WHERE `id` IN ($error_ids_str)");
            echo "✓ Đã cập nhật " . count($error_ids) . " UIDs ERROR<br>";
        }
        
        // TỐI ƯU 8: Sử dụng 1 transaction cho tất cả DIE records
        if (!empty($die_records)) {
            $CMSNT->query("START TRANSACTION");
            
            try {
                $success_count = 0;
                $failed_count = 0;
                
                foreach ($die_records as $record) {
                    $uid = $record['uid'];
                    $info = $record['info'];
                    
                    // Insert vào product_die
                    $isInsert = $CMSNT->insert('product_die', [
                        'product_code'      => $info['product_code'],
                        'seller'            => $info['seller'],
                        'uid'               => $info['uid'],
                        'account'           => $info['account'],
                        'create_gettime'    => $info['create_gettime'],
                        'type'              => $info['type']
                    ]);
                    
                    if($isInsert){
                        // Xóa khỏi bảng product_stock
                        $CMSNT->remove('product_stock', " `id` = '".$info['id']."' ");
                        $success_count++;
                    } else {
                        $failed_count++;
                    }
                }
                
                // Commit toàn bộ transaction
                $CMSNT->query("COMMIT");
                echo "✓ Đã xử lý " . $success_count . " UIDs DIE thành công";
                if($failed_count > 0) {
                    echo " (Lỗi: $failed_count)";
                }
                echo "<br>";
                
            } catch (Exception $e) {
                // Rollback nếu có lỗi
                $CMSNT->query("ROLLBACK");
                echo "✗ Lỗi khi xử lý DIE records: " . $e->getMessage() . "<br>";
                
                // Update time_check_live cho các record lỗi để tránh vòng lặp
                $die_ids = array_map(function($r) { return $r['info']['id']; }, $die_records);
                $die_ids_str = implode(',', array_map('intval', $die_ids));
                $CMSNT->query("UPDATE `product_stock` SET `time_check_live` = ".time()." WHERE `id` IN ($die_ids_str)");
            }
        }
        
        // TỐI ƯU 9: Giảm delay từ 0.5s xuống 0.1s (hoặc bỏ hẳn nếu không cần)
        if ($chunk_index < $total_chunks - 1) {
            echo "--- Hoàn thành nhóm " . ($chunk_index + 1) . "/" . $total_chunks . ". Tạm dừng 0.1 giây... ---<br>";
            usleep(100000); // 0.1 giây = 100,000 micro giây (giảm từ 500,000)
        }
    }

    echo "Quá trình kiểm tra hoàn tất.<br>";

