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
if(empty($_GET['product'])){
    http_response_code(400);
    die(json_encode(['status' => 'error', 'msg' => __('Thiếu product')]));
}

$client_domain = isset($_SERVER['HTTP_X_CLIENT_DOMAIN']) ? $_SERVER['HTTP_X_CLIENT_DOMAIN'] : null;


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
 
// Validate product ID
$product_id = validate_int($_GET['product'], 1);
if ($product_id === false) {
    http_response_code(400);
    die(json_encode(['status' => 'error', 'msg' => __('ID sản phẩm không hợp lệ')]));
}

$data_product = [];
$pending_condition = column_exists('products', 'pending') ? " AND `pending` = 0 " : "";
$products = $CMSNT->get_list_safe("SELECT * FROM `products` WHERE `id` = ? AND `status` = 1 AND `allow_api` = 1 AND `hide_in_shop` = 0 $pending_condition", [$product_id]);
foreach($products as $product){
    $stock = $product['supplier_id'] != 0 ? $product['api_stock'] : getStock($product['code']);
    $data_product[] = [
        'id'            => $product['id'],
        'name'          => $product['name'],
        'price'         => $product['price'],
        'amount'        => intval($stock),
        'description'   => $product['short_desc'],
        'flag'          => $product['flag'],
        'min'           => intval($product['min']),
        'max'           => intval($product['max'])
    ];
}


http_response_code(200);
die(json_encode([
    'status'    => 'success',
    'msg'       => __('Lấy dữ liệu thành công!'),
    'product'   => $data_product
], JSON_PRETTY_PRINT));