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
    $elapsed = time() - (int)$CMSNT->site('time_cron_checklive_tiktok');
    if ($elapsed >= 0 && $elapsed < 1) {
        die('Thao tác quá nhanh, vui lòng thử lại sau!');
    }
    $CMSNT->update("settings", [
        'value' => time()
    ], " `name` = 'time_cron_checklive_tiktok' ");

    if($CMSNT->site('api_check_live_tiktok') == ''){
        die('Bạn chưa cấu hình API, cấu hình tại Cài đặt -> Kết nối');
    }

 // Khởi tạo mảng UIDs và email
$uids = [];
$emails = [];
// Khởi tạo mảng chứa các thông tin về sản phẩm
$products_info = [];

$where_is_checklive = '';
// Lấy danh sách các sản phẩm có `check_live` là 'Tiktok'
$products_list = $CMSNT->get_list_safe("SELECT * FROM `products` WHERE `check_live` = ?", ['Tiktok']);

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
$limit = intval($CMSNT->site('limit_check_live_clone')) ?? 500;
$thirty_minutes_ago = time() - $CMSNT->site('time_limit_check_live_tiktok'); 
$products = $CMSNT->get_list_safe("SELECT * FROM `product_stock` WHERE `time_check_live` < ? $where_is_checklive ORDER BY `time_check_live` ASC LIMIT ?", [$thirty_minutes_ago, $limit]);


// Lặp qua danh sách sản phẩm để tạo danh sách UIDs
foreach ($products as $product) {
    // Kiểm tra xem UID đã tồn tại trong mảng UIDs chưa, nếu chưa thì thêm vào
    if (!in_array(check_string($product['uid']), $uids)) {
        $uids[] = check_string($product['uid']);
    }
    // Lưu thông tin sản phẩm vào mảng để sử dụng sau này
    $products_info[check_string($product['uid'])] = $product;
}

// Khởi tạo multi-curl handler
$mh = curl_multi_init();
$curl_handles = [];

// Lặp qua danh sách UIDs để tạo các xử lý cURL riêng lẻ cho mỗi UID
foreach ($uids as $uid) {
    $ch = curl_init();
    $url = $CMSNT->site('api_check_live_tiktok')."?account=".$uid."&api_key=".$CMSNT->site('api_key_check_live_tiktok');
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true
    ]);
    curl_multi_add_handle($mh, $ch);
    $curl_handles[$uid] = $ch;
}

// Thực thi các yêu cầu đồng thời
$running = null;
do {
    curl_multi_exec($mh, $running);
} while ($running > 0);


// Lặp qua các xử lý cURL để lấy kết quả và xử lý
foreach ($curl_handles as $uid => $ch) {
    $result = curl_multi_getcontent($ch);
    $info = curl_getinfo($ch);
    if ($info['http_code'] == 200) {
        $result_array = json_decode($result, true);
        if (isset($result_array['data']) && $result_array['data']['status'] == 'live') {
            // UID live, cập nhật thời gian kiểm tra live
            $CMSNT->update("product_stock", ['time_check_live' => time()], " `id` = '".$products_info[$uid]['id']."' ");
            echo "UID: ".substr($uid, 0, 8)."*******, Result: LIVE <br>";
        }else if (isset($result_array['data']) && $result_array['data']['status'] == 'die') {
            $isInsert = false;
            // UID die, di chuyển sang bảng product_die
            // Sử dụng giao dịch để đảm bảo tính nhất quán dữ liệu
            $CMSNT->query("START TRANSACTION");
            try {
                // Chèn vào bảng product_die
                $isInsert = $CMSNT->insert('product_die', [
                    'product_code'      => $products_info[$uid]['product_code'],
                    'seller'            => $products_info[$uid]['seller'],
                    'uid'               => $products_info[$uid]['uid'],
                    'account'           => $products_info[$uid]['account'],
                    'create_gettime'    => $products_info[$uid]['create_gettime'],
                    'type'              => $products_info[$uid]['type']
                ]);
                
                if($isInsert){
                    // Xóa khỏi bảng product_stock
                    $CMSNT->remove('product_stock', " `id` = '".$products_info[$uid]['id']."' ");
                    echo "UID: ".substr($uid, 0, 8)."*******, Result: DIE ".$result."<br>";
                    $CMSNT->query("COMMIT");
                } else {
                    $CMSNT->query("ROLLBACK");
                    echo "UID: ".substr($uid, 0, 8)."*******, Result: DIE | Lỗi: Không thể chèn dữ liệu <br>";
                }
            } catch (Exception $e) {
                $CMSNT->query("ROLLBACK");
                echo "UID: ".substr($uid, 0, 8)."*******, Result: DIE | Lỗi: ".$e->getMessage()." <br>";
            }
        }else{
            $error_message = "UID: ".substr($uid, 0, 8)."*******, Result: ERROR, Error: " . $result;
            echo $error_message . "<br>";
        }
    } else {
        //$CMSNT->update("product_stock", ['time_check_live' => time()], " `id` = '".$products_info[$uid]['id']."' ");
        $error_message = "UID: ".substr($uid, 0, 8)."*******, Result: ERROR, Error: " . $result;
        echo $error_message . "<br>";
    }
    // Đóng xử lý cURL
    curl_multi_remove_handle($mh, $ch);
    curl_close($ch);
}

// Đóng multi-curl handler
curl_multi_close($mh);
