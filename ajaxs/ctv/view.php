<?php
define("IN_SITE", true);
require_once(__DIR__.'/../../config.php');
require_once(__DIR__.'/../../libs/db.php');
require_once(__DIR__."/../../libs/lang.php");
require_once(__DIR__.'/../../libs/helper.php');
require_once(__DIR__.'/../../models/is_ctv.php');

$CMSNT = new DB();

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
        'msg'       => __('The Request Not Found')
    ]);
    die($data);   
}

if($CMSNT->site('ctv_status') != 1){
    die(json_encode(['status' => 'error', 'msg' => __('CTV Panel đang bị tắt trong cài đặt hệ thống.')]));
}
// Handle view product live accounts
if ($_POST['action'] == 'view_product_live') {
    $code = validate_alphanumeric($_POST['code'], 50);
    if ($code === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Mã kho hàng không hợp lệ!')]));
    }
    
    // Check if product belongs to this CTV
    $product = $CMSNT->get_row_safe("SELECT * FROM `products` WHERE `code` = ? AND `user_id` = ?", [$code, $getUser['id']]);
    if (!$product) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền truy cập kho hàng này!')]));
    }
    
    // Get all live accounts for this product
    $accounts = $CMSNT->get_list_safe("SELECT * FROM `product_stock` WHERE `product_code` = ? ORDER BY `id` DESC", [$code]);
    
    $accountList = [];
    foreach ($accounts as $account) {
        $accountList[] = $account['account'];
    }
    
    $accountsString = implode("\n", $accountList);
    
    die(json_encode([
        'status' => 'success',
        'accounts' => $accountsString,
        'count' => count($accountList)
    ]));
}

// Handle view product die accounts
if ($_POST['action'] == 'view_product_die') {
    $code = validate_alphanumeric($_POST['code'], 50);
    if ($code === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Mã kho hàng không hợp lệ!')]));
    }
    
    // Check if product belongs to this CTV
    $product = $CMSNT->get_row_safe("SELECT * FROM `products` WHERE `code` = ? AND `user_id` = ?", [$code, $getUser['id']]);
    if (!$product) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền truy cập kho hàng này!')]));
    }
    
    // Get all die accounts for this product
    $accounts = $CMSNT->get_list_safe("SELECT * FROM `product_die` WHERE `product_code` = ? ORDER BY `id` DESC", [$code]);
    
    $accountList = [];
    foreach ($accounts as $account) {
        $accountList[] = $account['account'];
    }
    
    $accountsString = implode("\n", $accountList);
    
    die(json_encode([
        'status' => 'success',
        'accounts' => $accountsString,
        'count' => count($accountList)
    ]));
}

// Handle view order details
if ($_POST['action'] == 'view_order') {
    $trans_id = validate_alphanumeric($_POST['trans_id'], 32);
    if ($trans_id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Mã đơn hàng không hợp lệ!')]));
    }
    
    // Check if order belongs to this CTV's products
    $order = $CMSNT->get_row_safe("SELECT * FROM `product_order` WHERE `trans_id` = ? AND `seller` = ?", [$trans_id, $getUser['id']]);
    if (!$order) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền xem đơn hàng này!')]));
    }
    
    // Get sold accounts for this order
    $accounts = $CMSNT->get_list_safe("SELECT * FROM `product_sold` WHERE `trans_id` = ? ORDER BY `id` DESC", [$trans_id]);
    
    $accountList = [];
    foreach ($accounts as $account) {
        $accountList[] = $account['account'];
    }
    
    $accountsString = implode("\n", $accountList);
    
    die(json_encode([
        'status' => 'success',
        'accounts' => $accountsString,
        'count' => count($accountList)
    ]));
}

// Handle download order
if ($_POST['action'] == 'download_order') {
    $trans_id = validate_alphanumeric($_POST['trans_id'], 32);
    if ($trans_id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Mã đơn hàng không hợp lệ!')]));
    }
    
    // Check if order belongs to this CTV's products
    $order = $CMSNT->get_row_safe("SELECT * FROM `product_order` WHERE `trans_id` = ? AND `seller` = ?", [$trans_id, $getUser['id']]);
    if (!$order) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền tải đơn hàng này!')]));
    }
    
    // Get sold accounts for this order
    $accounts = $CMSNT->get_list_safe("SELECT * FROM `product_sold` WHERE `trans_id` = ? ORDER BY `id` DESC", [$trans_id]);
    
    $accountList = [];
    foreach ($accounts as $account) {
        $accountList[] = $account['account'];
    }
    
    $accountsString = implode("\n", $accountList);
    $filename = 'order_' . $trans_id . '_' . date('Y-m-d_H-i-s') . '.txt';
    
    die(json_encode([
        'status' => 'success',
        'accounts' => $accountsString,
        'filename' => $filename,
        'count' => count($accountList)
    ]));
}


if($_POST['action'] == 'tinh_tien_refund'){
    if(empty($_POST['id'])){
        die(json_encode(['status' => 'error', 'msg' => 'ID đơn hàng không tồn tại']));
    }
    
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID đơn hàng không hợp lệ')]));
    }
    
    if(!$product_order = $CMSNT->get_row_safe("SELECT * FROM `product_order` WHERE `id` = ? AND `seller` = ?", [$id, $getUser['id']])){
        die(json_encode(['status' => 'error', 'msg' => __('Đơn hàng không tồn tại trong hệ thống')]));
    }
    
    // Lấy kiểu hoàn tiền (full/partial)
    $refundType = validate_string($_POST['refundType'], 20);
    if ($refundType === false || !in_array($refundType, ['full', 'partial'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Kiểu hoàn tiền không hợp lệ')]));
    }
    
    if ($refundType == 'partial') {
        // Lấy số lượng cần hoàn (nếu có)
        $partialQuantity = isset($_POST['partialQuantity']) ? validate_int($_POST['partialQuantity'], 1) : 0;
        if($partialQuantity === false){
            die(json_encode(['status' => 'error', 'msg' => __('Số lượng tài khoản cần hoàn không hợp lệ')]));
        }
        if($partialQuantity > $product_order['amount']){
            die(json_encode(['status' => 'error', 'msg' => __('Số lượng tài khoản cần hoàn vượt quá số lượng tài khoản của đơn hàng này.')]));
        }
        $rate = $product_order['pay'] / $product_order['amount'];
        // Tổng số tiền Refund
        $amountRefund = $partialQuantity * $rate;
        die(json_encode(['status' => 'success', 'totalRefund' => format_currency($amountRefund)]));
    } else {
        die(json_encode(['status' => 'success', 'totalRefund' => format_currency($product_order['pay'])]));
    }
}

// Tính toán phí rút tiền
if($_POST['action'] == 'calculateWithdrawFee'){
    $amount = validate_float($_POST['amount'], 0.01);
    if ($amount === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Số tiền không hợp lệ')]));
    }
    
    // Lấy phí rút tiền từ cấu hình
    $feePercent = $CMSNT->site('ctv_fee_withdraw');
    $fee = ($amount * $feePercent) / 100;
    $receive = $amount - $fee;
    
    die(json_encode([
        'status' => 'success',
        'amount' => format_currency($amount),
        'fee' => $fee,
        'receive' => format_currency($receive),
        'feePercent' => $feePercent
    ]));
}

die(json_encode(['status' => 'error', 'msg' => __('Thao tác không hợp lệ!')]));
 
