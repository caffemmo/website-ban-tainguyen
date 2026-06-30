<?php

define("IN_SITE", true);
require_once(__DIR__."/../libs/db.php");
require_once(__DIR__."/../config.php");
require_once(__DIR__."/../libs/lang.php");
require_once(__DIR__."/../libs/helper.php");
require_once(__DIR__."/../libs/database/users.php");
$CMSNT = new DB();
 

// Cổng thanh toán phải đang bật mới xử lý callback
if($CMSNT->site('flutterwave_status') != 1){
    http_response_code(403);
    die('Cổng thanh toán này chưa được kích hoạt');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!empty($data)) {
        $idData = validate_alphanumeric($data['id'] ?? '', 255);
        if ($idData === false) {
            http_response_code(400);
            exit;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.flutterwave.com/v3/transactions/$idData/verify");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer ".$CMSNT->site('flutterwave_secretKey')
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($response, true);
        $id = validate_alphanumeric($response['data']['id'] ?? '', 255);
        $txRef = validate_alphanumeric($response['data']['tx_ref'] ?? '', 255);
        $currency = validate_string($response['data']['currency'] ?? '', 10);
        $amount = validate_float($response['data']['amount'] ?? null, 0.0);
        $price = $amount * $CMSNT->site('flutterwave_rate');
        if (($response['data']['status'] ?? '') == 'successful') {
            if($row = $CMSNT->get_row_safe(" SELECT * FROM `payment_flutterwave` WHERE `tx_ref` = ? AND `currency` = ? AND `status` = 'pending'  ", [$txRef, $currency])){
                $user = new users;
                $isCong = $user->AddCredits($row['user_id'], $price, __('Recharge Flutterwave').' #'.$id, 'TOPUP_Flutterwave_'.$txRef);
                if($isCong){
                    $CMSNT->update('payment_flutterwave', [
                        'status'   => 'success',
                        'price'     => $price,
                        'update_gettime'    => gettime(),
                        'amount'    => $amount
                    ], " `id` = ? AND `status` = 'pending' ", [$row['id']]);

                    // TẠO LOG GIAO DỊCH GẦN ĐÂY
                    $CMSNT->insert('deposit_log',[
                        'user_id'       => $row['user_id'],
                        'method'        => 'Flutterwave',
                        'amount'        => $price,
                        'received'      => $price,
                        'create_time'   => time(),
                        'is_virtual'    => 0
                    ]);
                    
                    /** SEND NOTI CHO ADMIN */
                    $my_text = $CMSNT->site('noti_recharge');
                    $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
                    $my_text = str_replace('{username}', getRowRealtime('users', $row['user_id'], 'username'), $my_text);
                    $my_text = str_replace('{method}', 'Flutterwave', $my_text);
                    $my_text = str_replace('{amount}', $price, $my_text);
                    $my_text = str_replace('{price}', $price, $my_text);
                    $my_text = str_replace('{time}', gettime(), $my_text);
                    sendMessAdmin($my_text);


                }
            }
        }
    }
}

