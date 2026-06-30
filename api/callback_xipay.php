<?php

define("IN_SITE", true);
require_once(__DIR__."/../libs/db.php");
require_once(__DIR__."/../config.php");
require_once(__DIR__."/../libs/lang.php");
require_once(__DIR__."/../libs/helper.php");
require_once(__DIR__."/../libs/xipay.php");
require_once(__DIR__."/../libs/database/users.php");
$CMSNT = new DB();
 


if($CMSNT->site('gateway_xipay_status') != 1){
    die('Cổng thanh toán này chưa được kích hoạt');
}

// 1. Chỉ cho phép phương thức GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header("HTTP/1.1 405 Method Not Allowed");
    exit("Method Not Allowed");
}

// out_trade_no: Mã đơn hàng của merchant
// trade_no: Mã giao dịch của 彩虹易支付
// trade_status: Trạng thái giao dịch
// type: Phương thức thanh toán
// money: Số tiền giao dịch
$out_trade_no = isset($_GET['out_trade_no']) ? validate_alphanumeric($_GET['out_trade_no'], 255) : '';
$trade_no     = isset($_GET['trade_no']) ? validate_alphanumeric($_GET['trade_no'], 255) : '';
$trade_status = isset($_GET['trade_status']) ? validate_string($_GET['trade_status'], 50) : '';
$type         = isset($_GET['type']) ? validate_string($_GET['type'], 50) : '';
$money        = isset($_GET['money']) ? validate_float($_GET['money'], 0.0) : 0;
$display_type = $type == 'wxpay' ? 'WeChat Pay' : 'Alipay';
// 3. Kiểm tra nếu thiếu các tham số bắt buộc
if ($out_trade_no === false || $trade_no === false || $trade_status === false || $money === false || empty($out_trade_no) || empty($trade_no) || empty($trade_status) || $money === 0) {
    exit("fail");
}

$epay_config['apiurl'] = 'https://pay.xipay.cc/';
$epay_config['pid'] = $CMSNT->site('gateway_xipay_pid');
$epay_config['key'] = $CMSNT->site('gateway_xipay_md5key');

$epay = new EpayCore($epay_config);

// 6. Xác thực chữ ký callback thông qua hàm verifyNotify()
$verify_result = $epay->verifyNotify();

if ($verify_result) { // Nếu xác thực thành công
    // Nếu trạng thái giao dịch là TRADE_SUCCESS, thực hiện cập nhật đơn hàng, cấp phát dịch vụ, v.v.
    if ($trade_status == 'TRADE_SUCCESS') {
        if($row = $CMSNT->get_row_safe(" SELECT * FROM `payment_xipay` WHERE `out_trade_no` = ? AND `transaction_id` IS NULL AND `status` =  0 ", [$out_trade_no])){
            $user = new users;
            $isCong = $user->AddCredits($row['user_id'], $row['price'], __('Recharge').' '.$display_type.' #'.$out_trade_no, 'TOPUP_xipay_'.$out_trade_no);
            if($isCong){
                
                $CMSNT->update('payment_xipay', [
                    'status'            => 1,
                    'type'              => $type,
                    'transaction_id'    => $trade_no
                ], " `id` = ?  ", [$row['id']]);

                // TẠO LOG GIAO DỊCH GẦN ĐÂY
                $CMSNT->insert('deposit_log',[
                    'user_id'       => $row['user_id'],
                    'method'        => $display_type,
                    'amount'        => $money,
                    'received'      => $row['price'],
                    'create_time'   => time(),
                    'is_virtual'    => 0
                ]);
                /** SEND NOTI CHO ADMIN */
                $my_text = $CMSNT->site('noti_recharge');
                $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
                $my_text = str_replace('{username}', getRowRealtime('users', $row['user_id'], 'username'), $my_text);
                $my_text = str_replace('{method}', $display_type, $my_text);
                $my_text = str_replace('{amount}', $money, $my_text);
                $my_text = str_replace('{price}', format_currency($row['price']), $my_text);
                $my_text = str_replace('{time}', gettime(), $my_text);
                sendMessAdmin($my_text);
            }
        }
    }
    else{
        if($row = $CMSNT->get_row_safe(" SELECT * FROM `payment_xipay` WHERE `out_trade_no` = ? AND `status` = 0 ", [$out_trade_no])){
            $CMSNT->update('payment_xipay', [
                'status'    => 2
            ], " `id` = ? AND `status` = 0 ", [$row['id']]);
        }
    }

    echo "success";
} 
else {
    echo "fail";
}