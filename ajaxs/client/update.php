<?php

use GuzzleHttp\Promise\Is;

define("IN_SITE", true);
require_once(__DIR__."/../../config.php");
require_once(__DIR__."/../../libs/db.php");
require_once(__DIR__."/../../libs/lang.php");
require_once(__DIR__."/../../libs/helper.php");
require_once(__DIR__.'/../../libs/database/users.php');

if(!isset($_POST['action'])){
    $data = json_encode([
        'status'    => 'error',
        'msg'       => __('The Request Not Found')
    ]);
    die($data);   
}

if($_POST['action'] == 'updateIPWhitelist'){
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    
    // Validate token với prepared statement
    $token = validate_alphanumeric($_POST['token'], 255);
    if ($token === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    
    if (!$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    
    $ip_whitelist = isset($_POST['ip_whitelist']) ? trim($_POST['ip_whitelist']) : '';
    
    // Validate IP format nếu có dữ liệu
    if (!empty($ip_whitelist)) {
        $ips = array_filter(explode("\n", $ip_whitelist), function($ip) {
            return trim($ip) !== '';
        });
        
        foreach ($ips as $ip) {
            $ip = trim($ip);
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                die(json_encode([
                    'status' => 'error', 
                    'msg' => __('IP không hợp lệ: ') . $ip . '. ' . __('Vui lòng kiểm tra lại định dạng IP.')
                ]));
            }
        }
        
        // Chuyển đổi về format chuẩn (mỗi IP trên một dòng)
        $ip_whitelist = implode("\n", array_map('trim', $ips));
    }
    
    // Validate string length
    $ip_whitelist = validate_string($ip_whitelist, 2000);
    if ($ip_whitelist === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Danh sách IP quá dài')]));
    }
    
    $isUpdate = $CMSNT->update('users', [
        'ip_whitelist_api' => $ip_whitelist
    ], " `id` = ?", [$getUser['id']]);
    
    if($isUpdate){
        // Chuyển đổi IP từ xuống dòng thành dấu phẩy cho log
        $ip_log = empty($ip_whitelist) ? 'Trống' : str_replace("\n", ", ", $ip_whitelist);
        
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Update IP Whitelist').' ('.$ip_log.')'
        ]);
        die(json_encode([
            'status' => 'success',
            'msg' => __('Cập nhật danh sách IP Whitelist thành công!')
        ]));
    } else {
        die(json_encode([
            'status' => 'error',
            'msg' => __('Cập nhật danh sách IP Whitelist thất bại!')
        ]));
    }
}

if($_POST['action'] == 'saveNoteOrder'){
    if (empty($_POST['id'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Đơn hàng không tồn tại trong hệ thống')]));
    }
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    
    // Validate token với prepared statement
    $token = validate_alphanumeric($_POST['token'], 255);
    if ($token === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    
    if (!$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    
    // Validate order ID
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Đơn hàng không hợp lệ')]));
    }
    
    if (!$order = $CMSNT->get_row_safe("SELECT * FROM `product_order` WHERE `id` = ? AND `buyer` = ?", [$id, $getUser['id']])) {
        die(json_encode(['status' => 'error', 'msg' => __('Đơn hàng không tồn tại trong hệ thống')]));
    }
    
    // Validate note
    $note = !empty($_POST['note']) ? validate_string($_POST['note'], 1000) : NULL;
    if ($note === false && !empty($_POST['note'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Ghi chú quá dài')]));
    }
    
    $isUpdate = $CMSNT->update('product_order', [
        'note'  => $note
    ], " `id` = ?", [$order['id']]);
    
    if($isUpdate){
        die(json_encode([
            'status'    => 'success',
            'msg'       => __('Cập nhật ghi chú thành công!')
        ]));
    }else{
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Cập nhật ghi chú thất bại!')
        ]));
    }
}

if($_POST['action'] == 'toggleFavorite'){
    if (empty($_POST['id'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Sản phẩm không hợp lệ')]));
    }
    
    // Validate product ID
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Sản phẩm không hợp lệ')]));
    }
    
    if (!$product = $CMSNT->get_row_safe("SELECT * FROM `products` WHERE `id` = ? AND `status` = 1", [$id])) {
        die(json_encode(['status' => 'error', 'msg' => __('Sản phẩm không tồn tại trong hệ thống')]));
    }
    
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    
    // Validate token với prepared statement
    $token = validate_alphanumeric($_POST['token']);
    if ($token === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    
    if (!$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    
    if($isFavorite = $CMSNT->get_row_safe("SELECT * FROM `favorites` WHERE `user_id` = ? AND `product_id` = ?", [$getUser['id'], $product['id']])){
        $CMSNT->remove('favorites', " `id` = ?", [$isFavorite['id']]);
        die(json_encode([
            'status'    => 'success',
            'button'    => false,
            'msg'       => __('Đã xóa sản phẩm khỏi danh sách yêu thích')
        ]));
    }else{
        $CMSNT->insert('favorites', [
            'product_id'        => $product['id'],
            'user_id'           => $getUser['id'],
            'create_gettime'    => gettime()
        ]);
        die(json_encode([
            'status'    => 'success',
            'button'    => true,
            'msg'       => __('Đã thêm sản phẩm vào danh sách yêu thích')
        ]));
    }
}

if($_POST['action'] == 'changeLanguage'){
    if (empty($_POST['id'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Data does not exist')]));
    }
    
    // Validate language ID
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Data does not exist')]));
    }
    
    $row = $CMSNT->get_row_safe("SELECT * FROM `languages` WHERE `id` = ?", [$id]);
    if (!$row) {
        die(json_encode(['status' => 'error', 'msg' => __('Data does not exist')]));
    }
    
    $isUpdate = setLanguage($id);
    if ($isUpdate) {
        $data = json_encode([
            'status'    => 'success',
            'msg'       => __('Change language successfully')
        ]);
        die($data);
    }
}

if($_POST['action'] == 'changeCurrency'){
    if (empty($_POST['id'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Data does not exist')]));
    }
    
    // Validate currency ID
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Data does not exist')]));
    }
    
    $row = $CMSNT->get_row_safe("SELECT * FROM `currencies` WHERE `id` = ?", [$id]);
    if (!$row) {
        die(json_encode(['status' => 'error', 'msg' => __('Data does not exist')]));
    }
    
    $isUpdate = setCurrency($id);
    if ($isUpdate) {
        $data = json_encode([
            'status'    => 'success',
            'msg'       => __('Successful currency change')
        ]);
        die($data);
    }
}


if ($CMSNT->site('status_demo') != 0) {
    $data = json_encode([
        'status'    => 'error',
        'msg'       => __('This function cannot be used because this is a demo site')
    ]);
    die($data);
}
// CHỨC NĂNG KHÔNG DÙNG ĐƯỢC TẠI TRANG WEB DEMO

 

 

 

use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Orders\OrdersGetRequest;
use PayPalHttp\HttpException;

if($_POST['action'] == 'confirmPaypal' && isset($_POST['order']) ){

    if ($CMSNT->site('paypal_status') != 1) {
        die(json_encode(['status' => 'error', 'msg' => __('Chức năng này đang được bảo trì')]));
    }
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập')]));
    }
    
    // Validate token với prepared statement
    $token = validate_alphanumeric($_POST['token']);
    if ($token === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập')]));
    }
    
    if (!$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập')]));
    }
    $clientId = $CMSNT->site('paypal_clientId');
    $clientSecret = $CMSNT->site('paypal_clientSecret');
    $environment = new ProductionEnvironment($clientId, $clientSecret);
    //$environment = new SandboxEnvironment($clientId, $clientSecret);
    $client = new PayPalHttpClient($environment);
    $orderData = $_POST['order'];
    $request = new OrdersGetRequest($orderData['id']);
    try {
        $response = $client->execute($request);
        if ($response->statusCode != 200) {
            die(json_encode(['status' => 'error', 'msg' => __('Đã xảy ra lỗi!')]));
        }
        $order = $response->result;
        if ($order->status != 'COMPLETED') {
            die(json_encode(['status' => 'error', 'msg' => __('Đơn hàng không hợp lệ hoặc chưa thanh toán')]));
        }
        $orderDetail = $order->purchase_units[0];
        if ($CMSNT->get_row_safe("SELECT * FROM `payment_paypal` WHERE `trans_id` = ?", [$order->id])) {
            die(json_encode(['status' => 'error', 'msg' => __('Giao dịch này đã được xử lý')]));
        }
        $price = $CMSNT->site('paypal_rate') * $orderDetail->amount->value;
        $isInsert = $CMSNT->insert("payment_paypal", [
            'user_id'       => $getUser['id'],
            'trans_id'      => $order->id,
            'amount'        => $orderDetail->amount->value,
            'price'         => $price,
            'create_date'   => gettime(),
            'create_time'   => time()
        ]);
        if ($isInsert) {
            $user = new users();
            $isCong = $user->AddCredits($getUser['id'], $price, __('Nạp tiền tự động qua PayPal')." - $order->id", 'TOPUP_PAYPAL_'.$order->id);
            if($isCong){
                /** SEND NOTI CHO ADMIN */
                $my_text = $CMSNT->site('noti_recharge');
                $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
                $my_text = str_replace('{username}', $getUser['username'], $my_text);
                $my_text = str_replace('{method}', 'PayPal', $my_text);
                $my_text = str_replace('{amount}', $orderDetail->amount->value, $my_text);
                $my_text = str_replace('{price}', $price, $my_text);
                $my_text = str_replace('{time}', gettime(), $my_text);
                sendMessAdmin($my_text);
                die(json_encode(['status' => 'success', 'msg' => __('Nạp tiền thành công')]));
            }else{
                die(json_encode(['status' => 'error', 'msg' => __('Hóa đơn này đã được cộng tiền rồi')]));
            }
        }
    } catch (HttpException $e) {
        die(json_encode(['status' => 'error', 'msg' => $e->getMessage()]));
    } catch (Exception $e) {
        die(json_encode(['status' => 'error', 'msg' => $e->getMessage()]));
    }
}


die(json_encode([
    'status'    => 'error',
    'msg'       => __('Invalid data')
]));