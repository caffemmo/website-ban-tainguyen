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
    $elapsed = time() - (int)$CMSNT->site('time_cron_checklive_gmail');
    if ($elapsed >= 0 && $elapsed < 1) {
        die('Thao tác quá nhanh, vui lòng thử lại sau!');
    }
    $CMSNT->update("settings", [
        'value' => time()
    ], " `name` = 'time_cron_checklive_gmail' ");

    if($CMSNT->site('api_check_live_gmail') == ''){
        die('Bạn chưa cấu hình API, cấu hình tại Cài đặt -> Kết nối');
    }


 // Khởi tạo mảng UIDs và email
$uids = [];
$emails = [];
// Khởi tạo mảng chứa các thông tin về sản phẩm
$products_info = [];

$where_is_checklive = '';
// Lấy danh sách các sản phẩm có `check_live` là 'Gmail'
$products_list = $CMSNT->get_list_safe("SELECT * FROM `products` WHERE `check_live` = ?", ['Gmail']);

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
$thirty_minutes_ago = time() - $CMSNT->site('time_limit_check_live_gmail'); // 1800 giây = 30 phút mới check tiếp gmail đó
$products = $CMSNT->get_list_safe("SELECT * FROM `product_stock` WHERE `time_check_live` < ? $where_is_checklive ORDER BY `time_check_live` ASC LIMIT ?", [$thirty_minutes_ago, $limit]);

// Lặp qua danh sách sản phẩm để tạo danh sách UIDs và emails
foreach ($products as $product) {
    $email = $product['uid'];
    // Kiểm tra xem UID đã tồn tại trong mảng UIDs chưa, nếu chưa thì thêm vào
    if (!in_array($email, $uids)) {
        $uids[] = $email;
        $emails[] = ["email" => $email];
    }
    // Lưu thông tin sản phẩm vào mảng để sử dụng sau này
    $products_info[$email] = $product;
}

// Khởi tạo cURL
$ch = curl_init();
$url = $CMSNT->site('api_check_live_gmail');
$api_key = $CMSNT->site('api_key_check_live_gmail');

// Thiết lập các tùy chọn cURL
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode([
        "api_key" => $api_key,
        "emails" => $emails
    ]),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json'
    ],
]);

$result = curl_exec($ch);
//echo $result;
//die($result);
$info = curl_getinfo($ch);

if ($info['http_code'] == 200) {
    $response_data = json_decode($result, true);
    foreach ($response_data['data'] as $email_data) {
        $email = $email_data['email'];
        $status = $email_data['status'];
        // if ($status == 'live' || $status == 'Verify') {
        if ($status == 'live') {
            // UID live, cập nhật thời gian kiểm tra live
            $CMSNT->update("product_stock", ['time_check_live' => time()], " `id` = '".$products_info[$email]['id']."' ");
            echo "GMAIL: $email, Result: LIVE | ".$status."<br>";
        } else {
            // UID die, di chuyển sang bảng product_die
            // Sử dụng giao dịch để đảm bảo tính nhất quán dữ liệu
            $CMSNT->query("START TRANSACTION");
            try {
                // Chèn vào bảng product_die
                $isInsert = $CMSNT->insert('product_die', [
                    'product_code' => $products_info[$email]['product_code'],
                    'seller' => $products_info[$email]['seller'],
                    'uid' => $products_info[$email]['uid'],
                    'account' => $products_info[$email]['account'],
                    'create_gettime' => $products_info[$email]['create_gettime'],
                    'type' => $products_info[$email]['type']
                ]);
                
                if($isInsert){
                    // Xóa khỏi bảng product_stock
                    $CMSNT->remove('product_stock', " `id` = '".$products_info[$email]['id']."' ");
                    echo "GMAIL: $email, Result: DIE | ".$status." <br>";
                    $CMSNT->query("COMMIT");
                } else {
                    $CMSNT->query("ROLLBACK");
                    echo "GMAIL: $email, Result: DIE | ".$status." | Lỗi: Không thể chèn dữ liệu <br>";
                }
            } catch (Exception $e) {
                $CMSNT->query("ROLLBACK");
                echo "GMAIL: $email, Result: DIE | ".$status." | Lỗi: ".$e->getMessage()." <br>";
            }
        }
    }
} else {
    foreach ($uids as $email) {
        $CMSNT->update("product_stock", ['time_check_live' => time()], " `id` = '".$products_info[$email]['id']."' ");
        $error_message = "GMAIL: ".substr($email, 0, 6)."*******, Result: ERROR";
        echo $error_message . "<br>";
    }
}

// Đóng xử lý cURL
curl_close($ch);
