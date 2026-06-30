<?php

define("IN_SITE", true);
require_once(__DIR__."/../libs/db.php");
require_once(__DIR__."/../config.php");
require_once(__DIR__."/../libs/lang.php");
require_once(__DIR__."/../libs/helper.php");
require_once(__DIR__."/../libs/korapay.php");
require_once(__DIR__."/../libs/database/users.php");
$CMSNT = new DB();
 


if($CMSNT->site('korapay_status') != 1){
    die('Cổng thanh toán này chưa được kích hoạt');
}

 

// Đặt header trả về kiểu JSON
header("Content-Type: application/json");

// Đọc dữ liệu POST thô từ Korapay
$input = file_get_contents('php://input');

// Giải mã JSON thành mảng
$data = json_decode($input, true);


// Kiểm tra dữ liệu có hợp lệ không
if (empty($data)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid payload"]);
    exit;
}

// Kiểm tra các trường cần thiết: event và data
if (!isset($data['event']) || !isset($data['data'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required fields"]);
    exit;
}

// Lấy thông tin event và dữ liệu giao dịch
$event = $data['event'];
$payloadData = $data['data'];

if ($event === 'charge.success') {
    // Giao dịch thành công, xử lý thông tin
    $reference = isset($payloadData['reference']) ? validate_alphanumeric($payloadData['reference'], 255) : null;
    $currency = isset($payloadData['currency']) ? validate_string($payloadData['currency'], 10) : null;
    $amount = isset($payloadData['amount']) ? validate_float($payloadData['amount'], 0.0) : null;
    $fee = isset($payloadData['fee']) ? validate_float($payloadData['fee'], 0.0) : null;
    $status = isset($payloadData['status']) ? validate_string($payloadData['status'], 50) : null;
    $paymentMethod = isset($payloadData['payment_method']) ? validate_string($payloadData['payment_method'], 50) : null;
    $paymentReference = isset($payloadData['payment_reference']) ? validate_alphanumeric($payloadData['payment_reference'], 255) : null;

    // 1. Kiểm tra lại giao dịch (gọi verify API của Korapay)
    $secretKey = $CMSNT->site('korapay_secretKey');
    $verification = korapayVerifyCharge($secretKey, $reference);
    
    // Kiểm tra kết quả verify
    if (!$verification || !isset($verification['status']) || $verification['status'] !== true) {
        http_response_code(400);
        echo json_encode(["error" => "Transaction verification failed"]);
        exit;
    }else{

        if($verification['data']['status'] == 'success'){
            if($row = $CMSNT->get_row_safe(" SELECT * FROM `payment_korapay` WHERE `trans_id` = ? AND `status` =  0 ", [$reference])){
                $user = new users;
                $isCong = $user->AddCredits($row['user_id'], $row['price'], __('Recharge Korapay').' #'.$reference, 'TOPUP_korapay_'.$reference);
                if($isCong){
                    
                    $CMSNT->update('payment_korapay', [
                        'status'            => 1,
                        'updated_at'        => gettime()
                    ], " `id` = ?  ", [$row['id']]);
    
                    // TẠO LOG GIAO DỊCH GẦN ĐÂY
                    $CMSNT->insert('deposit_log',[
                        'user_id'       => $row['user_id'],
                        'method'        => __('Korapay Africa'),
                        'amount'        => $amount,
                        'received'      => $row['price'],
                        'create_time'   => time(),
                        'is_virtual'    => 0
                    ]);
                    /** SEND NOTI CHO ADMIN */
                    $my_text = $CMSNT->site('noti_recharge');
                    $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
                    $my_text = str_replace('{username}', getRowRealtime('users', $row['user_id'], 'username'), $my_text);
                    $my_text = str_replace('{method}', __('Recharge Korapay'), $my_text);
                    $my_text = str_replace('{amount}', $amount, $my_text);
                    $my_text = str_replace('{price}', format_currency($row['price']), $my_text);
                    $my_text = str_replace('{time}', gettime(), $my_text);
                    sendMessAdmin($my_text);
                }
            }

        }
        if($verification['data']['status'] == 'failed' || $verification['data']['status'] == 'expired'){
            if($row = $CMSNT->get_row_safe(" SELECT * FROM `payment_korapay` WHERE `trans_id` = ? AND `status` =  0 ", [$reference])){
                $CMSNT->update('payment_korapay', [
                    'status'            => 2,
                    'updated_at'        => gettime()
                ], " `id` = ?  ", [$row['id']]);
            }
        }

        // Trả về phản hồi thành công cho Korapay
        http_response_code(200);
        echo json_encode(["received" => true]);
        exit;
    }
} else {
    // Nếu event không phải là charge.success, bạn có thể xử lý theo nhu cầu khác.
    http_response_code(200);
    echo json_encode(["received" => true, "message" => "Event not processed"]);
    exit;
}