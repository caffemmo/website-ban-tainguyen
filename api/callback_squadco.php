<?php

define("IN_SITE", true);
require_once(__DIR__."/../libs/db.php");
require_once(__DIR__."/../config.php");
require_once(__DIR__."/../libs/lang.php");
require_once(__DIR__."/../libs/helper.php");
require_once(__DIR__."/../libs/database/users.php");
$CMSNT = new DB();
 
$headers = getallheaders();

// Cổng thanh toán phải đang bật mới xử lý webhook/callback
if($CMSNT->site('squadco_status') != 1){
    http_response_code(403);
    die('Cổng thanh toán này chưa được kích hoạt');
}

if ((strtoupper($_SERVER['REQUEST_METHOD']) != 'POST' ) || !isset($headers['x-squad-encrypted-body']) ){
    die('Yêu cầu không hợp lệ');
}
$input = @file_get_contents("php://input");
// Bắt buộc phải có Secret Key hợp lệ. Nếu admin chưa cấu hình (rỗng/false) thì
// hash_hmac với key rỗng sẽ tạo ra chữ ký mà ai cũng tính được => phải fail-closed.
$secretKey = $CMSNT->site('squadco_Secret_Key');
if(!is_string($secretKey) || strlen($secretKey) < 16){
    http_response_code(503);
    die('Webhook chưa được cấu hình');
}
$expectedSignature = strtoupper(hash_hmac('sha512', $input, $secretKey));
if(!hash_equals($expectedSignature, (string)$headers['x-squad-encrypted-body'])){
    die('Xác minh webhook thất bại');
}
$body = json_decode($input, true);
$body = $body['Body'];
$amount = floor(check_string($body['amount'])/100);
if($amount <= 0){
    die('Số tiền không hợp lệ');
}
if(($body['currency'] ?? '') != $CMSNT->site('squadco_currency_code')){
    die('Currency không hợp lệ');
}
$transaction_ref = validate_alphanumeric($body['transaction_ref'] ?? '', 255);
$email = validate_email($body['email'] ?? '');
if($transaction_ref === false || $email === false) {
    die('Dữ liệu không hợp lệ');
}
if($CMSNT->get_row_safe(" SELECT * FROM `payment_squadco` WHERE `transaction_ref` = ? AND `amount` = ? ", [$transaction_ref, $amount])){
    die('Đơn này xử lý rồi');
}
if(!$getUser = $CMSNT->get_row_safe(" SELECT * FROM `users` WHERE `email` = ? ", [$email])){
    die('Email user không tồn tại trong hệ thống');
}
$price = $amount * $CMSNT->site('squadco_rate');
$isInsert = $CMSNT->insert('payment_squadco', [
    'user_id'           => $getUser['id'],
    'transaction_ref'   => $transaction_ref,
    'amount'            => $amount,
    'price'             => $price,
    'create_gettime'    => gettime()
]);
if($isInsert){
    $user = new users;
    $user->AddCredits($getUser['id'], $price, __('Recharge Squadco').' #'.$transaction_ref, 'TOPUP_Squadco_'.$transaction_ref);
    // TẠO LOG GIAO DỊCH GẦN ĐÂY
    $CMSNT->insert('deposit_log',[
        'user_id'       => $getUser['id'],
        'method'        => 'Squadco',
        'amount'        => $price,
        'received'      => $price,
        'create_time'   => time(),
        'is_virtual'    => 0
    ]);
    
    /** SEND NOTI CHO ADMIN */
    $my_text = $CMSNT->site('noti_recharge');
    $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
    $my_text = str_replace('{username}', getRowRealtime('users', $getUser['id'], 'username'), $my_text);
    $my_text = str_replace('{method}', 'Squadco', $my_text);
    $my_text = str_replace('{amount}', $price, $my_text);
    $my_text = str_replace('{price}', $price, $my_text);
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);
}