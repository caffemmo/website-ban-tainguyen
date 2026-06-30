<?php

define("IN_SITE", true);
require_once(__DIR__."/../../libs/db.php");
require_once(__DIR__."/../../config.php");
require_once(__DIR__."/../../libs/lang.php");
require_once(__DIR__."/../../libs/helper.php");
$CMSNT = new DB();

if (empty($_GET['api_key'])) {
    die(json_encode(['status' => 'error', 'msg' => __('Thiếu api_key')]));
}
if (empty($_GET['product'])) {
    die(json_encode(['status' => 'error', 'msg' => __('Thiếu product')]));
}

// Validate inputs
$api_key = validate_alphanumeric($_GET['api_key'], 255);
if ($api_key === false) {
    // Rate limit
    checkBlockIP('API', 15);
    die(json_encode(['status' => 'error', 'msg' => __('API Key không hợp lệ')]));
}

$product_id = validate_int($_GET['product'], 1);
if ($product_id === false) {
    die(json_encode(['status' => 'error', 'msg' => __('ID sản phẩm không hợp lệ')]));
}

// Auth with prepared statements
if (!$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `api_key` = ? AND `banned` = 0", [$api_key])) {
    // Rate limit
    checkBlockIP('API', 15);
    die(json_encode(['status' => 'error', 'msg' => __('API Key không hợp lệ')]));
}
 
$stock = 0;
foreach ($CMSNT->get_list_safe("SELECT * FROM `products` WHERE `id` = ? AND `status` = 1 AND `allow_api` = 1", [$product_id]) as $product) {
    $stock = ($product['supplier_id'] != 0) ? $product['api_stock'] : getStock($product['code']);
}


die(json_encode([
    'sum'       => $stock
], JSON_PRETTY_PRINT));