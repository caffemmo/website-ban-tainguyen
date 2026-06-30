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

$user = [];
$data = [];

$user = [
    'username'      => $getUser['username'],
    'money'         => $getUser['money']
];
$data = [
    'status'    => 'success',
    'msg'       => 'Lấy dữ liệu thành công!',
    'data'      => $user
];
http_response_code(200);
echo json_encode($data, JSON_PRETTY_PRINT);