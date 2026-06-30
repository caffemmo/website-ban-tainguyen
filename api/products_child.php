<?php

define("IN_SITE", true);
require_once(__DIR__."/../libs/db.php");
require_once(__DIR__."/../config.php");
require_once(__DIR__."/../libs/lang.php");
require_once(__DIR__."/../libs/helper.php");
$CMSNT = new DB();

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
 
$data_category_parent = [];

// Lấy danh sách chuyên mục cha (parent_id = 0)
foreach($CMSNT->get_list_safe(" SELECT * FROM `categories` WHERE `parent_id` = 0 AND `status` = 1 ") as $parent_category){
    $data_category_child = [];
    
    // Lấy danh sách chuyên mục con của chuyên mục cha này
    foreach($CMSNT->get_list_safe(" SELECT * FROM `categories` WHERE `parent_id` = '".$parent_category['id']."' AND `status` = 1 ") as $child_category){
        $data_product = [];
        
        // Lấy danh sách sản phẩm của chuyên mục con
        foreach($CMSNT->get_list_safe(" SELECT * FROM `products` WHERE `category_id` = '".$child_category['id']."' AND `status` = 1 AND `allow_api` = 1 AND `hide_in_shop` = 0 ".(column_exists('products', 'pending') ? " AND `pending` = 0 " : "")." ") as $product){
            $stock = $product['supplier_id'] != 0 ? $product['api_stock'] : getStock($product['code']);
            $data_product[] = [
                'id'            => $product['id'],
                'name'          => $product['name'],
                'slug'          => $product['slug'],
                'price'         => $product['price'],
                'amount'        => intval($stock), 
                'short_desc'    => $product['short_desc'],
                'description'   => $product['description'],
                'status'        => $product['status'],
                'images'        => BASE_URL($product['images']),
                'flag'          => $product['flag'],
                'min'           => $product['min'],
                'max'           => $product['max'],
                'stt'           => isset($product['stt']) ? $product['stt'] : 0
            ];
        }
        
        $data_category_child[] = [
            'id'            => $child_category['id'],
            'name'          => $child_category['name'],
            'title'         => isset($child_category['title']) ? $child_category['title'] : '',
            'description'   => isset($child_category['description']) ? $child_category['description'] : '',
            'keywords'      => isset($child_category['keywords']) ? $child_category['keywords'] : '',
            'icon'          => BASE_URL($child_category['icon']),
            'stt'           => isset($child_category['stt']) ? $child_category['stt'] : 0,
            'products'      => $data_product
        ];
    }
    
    $data_category_parent[] = [
        'id'                => $parent_category['id'],
        'name'              => $parent_category['name'],
        'title'             => isset($parent_category['title']) ? $parent_category['title'] : '',
        'description'       => isset($parent_category['description']) ? $parent_category['description'] : '',
        'keywords'          => isset($parent_category['keywords']) ? $parent_category['keywords'] : '',
        'icon'              => BASE_URL($parent_category['icon']),
        'stt'               => isset($parent_category['stt']) ? $parent_category['stt'] : 0,
        'child_categories'  => $data_category_child
    ];
}



http_response_code(200);
die(json_encode([
    'status'            => 'success',
    'msg'               => __('Lấy dữ liệu thành công!'),
    'categories'        => $data_category_parent
], JSON_PRETTY_PRINT));