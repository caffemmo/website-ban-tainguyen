<?php

define("IN_SITE", true);
require_once(__DIR__."/../libs/db.php");
require_once(__DIR__."/../libs/lang.php");
require_once(__DIR__."/../libs/helper.php");
require_once(__DIR__."/../libs/database/users.php");
require_once(__DIR__."/../libs/telegram-shop/TelegramShopBot.php");
$CMSNT = new DB();

// Cổng thanh toán phải đang bật mới xử lý callback
if($CMSNT->site('crypto_status') != 1){
    http_response_code(403);
    die('Cổng thanh toán này chưa được kích hoạt');
}

// Nhận request từ callback
$request_data = $_GET;

// Kiểm tra các tham số callback nhận được
if (!isset($request_data['request_id'], $request_data['merchant_id'], $request_data['api_key'], $request_data['received'], $request_data['status'])) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Thiếu tham số callback.'
    ]);
    exit;
}

$request_id = validate_alphanumeric($request_data['request_id'], 255);      // Mã giao dịch bí mật của hệ thống
$merchant_id = validate_alphanumeric($request_data['merchant_id'], 255);    // ID cửa hàng tạo hóa đơn
$api_key = validate_string($request_data['api_key'], 2000);                 // API KEY của cửa hàng
$amount = validate_float($request_data['received'], 0.0);                 // Số lượng USDT thực nhận được
$status = validate_string($request_data['status'], 50);                     // Trạng thái: waiting, expired, completed
$from_address = isset($request_data['from_address']) ? validate_string($request_data['from_address'], 2000) : null;        // Địa chỉ USDT gửi tiền
$transaction_id = isset($request_data['transaction_id']) ? validate_string($request_data['transaction_id'], 2000) : null;  // Mã giao dịch blockchain

// Validate bắt buộc
if ($request_id === false || $merchant_id === false || $api_key === false || $amount === false || $status === false) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Dữ liệu không hợp lệ.'
    ]);
    exit;
}

// Xác minh tính hợp lệ của callback tránh giả mạo callback
$expected_merchant_id = trim($CMSNT->site('crypto_merchant_id')); // Thay bằng Merchant ID của bạn
$expected_api_key = trim($CMSNT->site('crypto_api_key')); // Thay bằng API Key của bạn
if ($merchant_id !== $expected_merchant_id || $api_key !== $expected_api_key) {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Merchant ID hoặc API Key không hợp lệ.'
    ]);
    exit;
}
if(!$row = $CMSNT->get_row_safe(" SELECT * FROM `payment_crypto` WHERE `request_id` = ? ", [$request_id])){
    echo json_encode([
        'status' => 'error',
        'message' => 'Hóa đơn không tồn tại'
    ]);
    exit;
}

$received = $row['received']; // Thực nhận

$getUser = $CMSNT->get_row_safe(" SELECT * FROM `users` WHERE `id` = ? ", [$row['user_id']]);
// HOÁ ĐƠN ĐÃ CỘNG TIỀN SẼ KHÔNG THAY ĐỔI TRẠNG THÁI
if($row['status'] == 'completed'){
    echo json_encode([
        'status' => 'error',
        'message' => 'Hoá đơn này đã được xử lý rồi'
    ]);
    exit;
}

// Xử lý trạng thái giao dịch
switch ($status) {
    case 'waiting':
        // Giao dịch đang chờ thực hiện
        // Thêm code để xử lý trạng thái đang chờ
        break;
    case 'expired':
        // Giao dịch đã hết hạn
        $CMSNT->update('payment_crypto', [
            'status'            => 'expired',
            'update_gettime'    => gettime()
        ], " `id` = ? ", [$row['id']]);
        break;
    case 'completed':
        // Giao dịch đã hoàn tất
        $isUpdate = $CMSNT->update('payment_crypto', [
            'status'            => 'completed',
            'update_gettime'    => gettime()
        ], " `id` = ? ", [$row['id']]);
        if($isUpdate){
            $User = new users();
            $isCong = $User->AddCredits($row['user_id'], $received, "Crypto Recharge #".$row['trans_id'], 'TOPUP_CRYPTO_'.$row['trans_id']);
            if($isCong){
                /** SEND NOTI CHO ADMIN */
                $my_text = $CMSNT->site('noti_recharge');
                $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
                $my_text = str_replace('{username}', $getUser['username'], $my_text);
                $my_text = str_replace('{method}', 'Crypto', $my_text);
                $my_text = str_replace('{amount}', '$'.$amount, $my_text);
                $my_text = str_replace('{price}', format_currency($received), $my_text);
                $my_text = str_replace('{time}', gettime(), $my_text);
                sendMessAdmin($my_text);

                // Gửi thông báo Telegram cho user (nếu đăng ký qua Telegram)
                TelegramShopBot::notifyRecharge(
                    $row['user_id'],
                    'Crypto (USDT)',
                    '$' . $amount . ' USDT',
                    $received
                );
   
                // TẠO LOG GIAO DỊCH GẦN ĐÂY
                $CMSNT->insert('deposit_log',[
                    'user_id'       => $getUser['id'],
                    'method'        => 'USDT',
                    'amount'        => $received,
                    'received'      => $received,
                    'create_time'   => time(),
                    'is_virtual'    => 0
                ]);

                // Phản hồi callback thành công
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Callback đã được xử lý thành công.'
                ]);
                exit;
            }else{
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Không thể cộng tiền cho tài khoản user'
                ]);
                exit;
            }
        }else{
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Không thể cập nhật trạng thái completed.'
            ]);
            exit;
        }
        break;
    default:
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Trạng thái giao dịch không hợp lệ.'
        ]);
        exit;
}

// Phản hồi callback thành công
http_response_code(200);
echo json_encode([
    'status' => 'success',
    'message' => 'Callback đã được xử lý thành công.'
]);