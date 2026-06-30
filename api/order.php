<?php

define("IN_SITE", true);
require_once(__DIR__."/../libs/db.php");
require_once(__DIR__."/../config.php");
require_once(__DIR__."/../libs/lang.php");
require_once(__DIR__."/../libs/helper.php");
$CMSNT = new DB();


header('Content-Type: application/json; charset=utf-8');



if(empty($_GET['api_key'])){
    http_response_code(400);
    die(json_encode(['status' => 'error', 'msg' => __('Thiếu api_key')]));
}
if(empty($_GET['order'])){
    http_response_code(400);
    die(json_encode(['status' => 'error', 'msg' => __('Thiếu order')]));
}
// Validate API key
$api_key = validate_alphanumeric($_REQUEST['api_key']);
if ($api_key === false) {
    checkBlockIP('API', 15);
    http_response_code(400);
    die(json_encode(['status' => 'error', 'msg' => __('API Key không hợp lệ')]));
}

if (!$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `api_key` = ? AND `banned` = 0", [$api_key])) {
    // Rate limit
    checkBlockIP('API', 15);
    http_response_code(401);
    die(json_encode(['status' => 'error', 'msg' => __('API Key không hợp lệ')]));
}

// Kiểm tra IP Whitelist
$client_ip = myip();
if (!checkIPWhitelist($getUser['ip_whitelist_api'], $client_ip)) {
    // Rate limit cho IP không hợp lệ
    checkBlockIP('IP_NOT_WHITELIST_API', 5);
    http_response_code(403);
    die(json_encode([
        'status' => 'error', 
        'msg' => __('IP của bạn không nằm trong Whitelist API của User này'),
        'client_ip' => $client_ip
    ]));
}

if($getUser['banned'] == 1){
    http_response_code(403);
    die(json_encode(['status' => 'error', 'msg' => __('Tài khoản đã bị khóa')]));
}

// Validate order ID
$order_id = validate_alphanumeric($_GET['order']);
if ($order_id === false) {
    http_response_code(400);
    die(json_encode(['status' => 'error', 'msg' => __('Mã đơn hàng không hợp lệ')]));
}

$user_id = validate_int($getUser['id'], 1);
if ($user_id === false) {
    http_response_code(400);
    die(json_encode(['status' => 'error', 'msg' => __('ID người dùng không hợp lệ')]));
}

$order = $CMSNT->get_row_safe("SELECT * FROM `product_order` WHERE `trans_id` = ? AND `buyer` = ?", [$order_id, $user_id]);
if(!$order){
    http_response_code(404);
    die(json_encode(['status' => 'error', 'msg' => __('Đơn hàng không tồn tại')]));
}
$accounts = [];

$accounts_data = $CMSNT->get_list("SELECT * FROM `product_sold` WHERE `trans_id` = '".$order['trans_id']."' ");
if (empty($accounts_data)) {
    http_response_code(404);
    die(json_encode(['status' => 'error', 'msg' => __('Không có tài khoản nào trong đơn hàng này')]));
}

foreach($accounts_data as $account){
    $accounts[] = $account['account'];
}


http_response_code(200);
die(json_encode([
    'status'    => 'success', 
    'msg'       => __('Lấy đơn hàng thành công!'),
    'trans_id'  => $order['trans_id'],
    'data'      => $accounts
]));

 