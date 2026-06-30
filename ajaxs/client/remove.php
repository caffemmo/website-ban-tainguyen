<?php

define("IN_SITE", true);
require_once(__DIR__."/../../config.php");
require_once(__DIR__."/../../libs/db.php");
require_once(__DIR__."/../../libs/lang.php");
require_once(__DIR__."/../../libs/helper.php");
if ($CMSNT->site('status_demo') != 0) {
    $data = json_encode([
        'status'    => 'error',
        'msg'       => __('This function cannot be used because this is a demo site')
    ]);
    die($data);
}
if(!isset($_POST['action'])){
    $data = json_encode([
        'status'    => 'error',
        'msg'       => 'The Request Not Found'
    ]);
    die($data);   
}
if(!isset($_POST['id'])){
    $data = json_encode([
        'status'    => 'error',
        'msg'       => __('The ID to delete does not exist')
    ]);
    die($data);   
}
if ($CMSNT->site('status_demo') != 0) {
    die(json_encode(['status' => 'error', 'msg' => __('Chức năng này không thể sử dụng trên website demo')]));
}
if($_POST['action'] == 'removeOrder'){
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Please login')]));
    }
    
    // Validate token với prepared statement
    $token = validate_alphanumeric($_POST['token'], 255);
    if ($token === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Please login')]));
    }
    
    if (!$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Please login')]));
    }
    
    // Validate order ID
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Đơn hàng không hợp lệ')]));
    }
    
    if (!$row = $CMSNT->get_row_safe("SELECT * FROM `product_order` WHERE `id` = ? AND `buyer` = ?", [$id, $getUser['id']])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Đơn hàng không tồn tại trong hệ thống')
        ]));
    }
    
    $isRemove = $CMSNT->update("product_order", [
        'trash' => 1
    ], " `id` = '".$row['id']."' ");
    if ($isRemove) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Delete order').' ('.$row['trans_id'].')'
        ]);
        die(json_encode([
            'status'    => 'success',
            'msg'       => __('Xóa đơn hàng thành công!')
        ]));
    }
}

if($_POST['action'] == 'removeFavorite'){
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Please login')]));
    }
    
    // Validate token với prepared statement
    $token = validate_alphanumeric($_POST['token'], 255);
    if ($token === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Please login')]));
    }
    
    if (!$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Please login')]));
    }
    
    // Validate favorite ID
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    
    if (!$row = $CMSNT->get_row_safe("SELECT * FROM `favorites` WHERE `id` = ? AND `user_id` = ?", [$id, $getUser['id']])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Xóa dữ liệu thất bại')
        ]));
    }
    $isRemove = $CMSNT->remove("favorites", " `id` = '".$row['id']."' ");
    if ($isRemove) {
        die(json_encode([
            'status'    => 'success',
            'msg'       => __('Xóa dữ liệu thành công')
        ]));
    }
}
 


die(json_encode([
    'status'    => 'error',
    'msg'       => __('Invalid data')
]));

