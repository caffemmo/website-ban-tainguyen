<?php

define("IN_SITE", true);
require_once(__DIR__."/../libs/db.php");
require_once(__DIR__."/../config.php");
require_once(__DIR__."/../libs/lang.php");
require_once(__DIR__."/../libs/helper.php");
require_once(__DIR__."/../libs/database/users.php");
$User = new users();
$CMSNT = new DB();
 

if ($CMSNT->site('status') != 1 && isSecureCookie('admin_login') != true) {
    die('status_website_off');
}
if ($CMSNT->site('card_status') != 1) {
    die('status_card_off');
}
/** CALLBACK */
if(isset($_GET['request_id']) && isset($_GET['callback_sign'])){
    // Validate đầu vào (không dùng trực tiếp vào SQL nếu chưa validate)
    $status = validate_string($_GET['status'] ?? '', 20);
    $message = validate_string($_GET['message'] ?? '', 255);
    $request_id = validate_alphanumeric($_GET['request_id'] ?? '', 100); // request id
    $declared_value = validate_float($_GET['declared_value'] ?? null, 0.0);
    $value = validate_float($_GET['value'] ?? null, 0.0); // Giá trị thực của thẻ
    $amount = validate_float($_GET['amount'] ?? null, 0.0); // Số tiền nhận được
    $code = validate_alphanumeric($_GET['code'] ?? '', 100);
    $serial = validate_alphanumeric($_GET['serial'] ?? '', 100);
    $telco = validate_string($_GET['telco'] ?? '', 50);
    $trans_id = validate_alphanumeric($_GET['trans_id'] ?? '', 255); // Mã giao dịch bên chúng tôi
    $callback_sign = validate_alphanumeric($_GET['callback_sign'] ?? '', 2000);

    if($status === false || $request_id === false || $code === false || $serial === false || $callback_sign === false){
        die('invalid_params');
    }

    if($callback_sign != md5($CMSNT->site('card_partner_key').$code.$serial)){
        die('callback_sign_error');
    }
    if (!$row = $CMSNT->get_row_safe(" SELECT * FROM `cards` WHERE `trans_id` = ? AND `status` = 'pending' ", [$request_id])) {
        die('request_id_error');
    }
    if (!$getUser = $CMSNT->get_row_safe(" SELECT * FROM `users` WHERE `id` = ? AND `banned` = 0 ", [$row['user_id']])) {
        die('user không hợp lệ');
    }
    if($status == 1){
        if($CMSNT->site('card_ck') == 0){
            $price = $amount;
        }else{
            $price = $value - $value * $CMSNT->site('card_ck') / 100;
        }
        $CMSNT->update("cards", array(
            'status'        => 'completed',
            'price'         => $price,
            'update_date'    => gettime()
        ), " `id` = ? ", [$row['id']]);
        $isCong = $User->AddCredits($row['user_id'], $price, "Nạp thẻ cào Seri ".$row['serial']." - Pin ".$row['pin'], 'TOPUP_CARD_'.$row['pin']);
        if($isCong){
          

            /** SEND NOTI CHO ADMIN */
            $my_text = $CMSNT->site('noti_recharge');
            $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
            $my_text = str_replace('{username}', $getUser['username'], $my_text);
            $my_text = str_replace('{method}', $telco, $my_text);
            $my_text = str_replace('{amount}', format_currency($amount), $my_text);
            $my_text = str_replace('{price}', format_currency($price), $my_text);
            $my_text = str_replace('{time}', gettime(), $my_text);
            sendMessAdmin($my_text);

            // TẠO LOG GIAO DỊCH GẦN ĐÂY
            $CMSNT->insert('deposit_log',[
                'user_id'       => $getUser['id'],
                'method'        => 'Thẻ cào',
                'amount'        => $value,
                'received'      => $price,
                'create_time'   => time(),
                'is_virtual'    => 0
            ]);
            die('payment.success');
        }else{
            die('thẻ này đã được cộng tiền rồi');
        }
    }
    else{
        $CMSNT->update("cards", array(
            'status'        => 'error',
            'price'         => 0,
            'update_date'   => gettime(),
            'reason'        => 'Thẻ cào không hợp lệ hoặc đã được sử dụng'
        ), " `id` = ? ", [$row['id']]);
        exit('payment.error');
    }
}