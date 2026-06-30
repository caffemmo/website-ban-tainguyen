<?php

define("IN_SITE", true);
require_once(__DIR__."/../libs/db.php");
require_once(__DIR__."/../config.php");
require_once(__DIR__."/../libs/lang.php");
require_once(__DIR__."/../libs/helper.php");
require_once(__DIR__."/../libs/database/users.php");
$CMSNT = new DB();
 

// Kiểm tra xem cổng thanh toán OpenPix có được kích hoạt không
if($CMSNT->site('openpix_status') != 1){
    die('Cổng thanh toán này chưa được kích hoạt');
}

// Lấy headers và nội dung của request
$body = file_get_contents('php://input');
$signature = validate_string($_SERVER['HTTP_X_OPENPIX_SIGNATURE'] ?? '', 5000);


// Giải mã dữ liệu JSON từ body trước để xác định loại sự kiện
$data = json_decode($body, true);

// Kiểm tra xem có phải JSON hợp lệ không
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    die('Invalid JSON data');
}

// Xác định khóa HMAC dựa trên loại sự kiện
$secretKeyOnOpenpixPlatform = $CMSNT->site('openpix_HMAC_key_completed');
if (isset($data['event'])) {
    if ($data['event'] == 'OPENPIX:CHARGE_COMPLETED') {
        // Sử dụng khóa cho giao dịch thành công
        $secretKeyOnOpenpixPlatform = $CMSNT->site('openpix_HMAC_key_completed');
    } else if ($data['event'] == 'OPENPIX:CHARGE_EXPIRED') {
        // Sử dụng khóa cho giao dịch hết hạn
        $secretKeyOnOpenpixPlatform = $CMSNT->site('openpix_HMAC_key');
    } else {
        // Sự kiện không được hỗ trợ
        http_response_code(400);
        die('Unsupported event type');
    }
} else {
    // Không tìm thấy trường event
    http_response_code(400);
    die('Event field not found');
}

// Bắt buộc phải có khóa HMAC hợp lệ. Nếu admin chưa cấu hình (rỗng/false) thì
// hash_hmac với key rỗng sẽ tạo ra chữ ký mà ai cũng tính được => phải fail-closed.
if(!is_string($secretKeyOnOpenpixPlatform) || strlen($secretKeyOnOpenpixPlatform) < 16){
    http_response_code(503);
    die('Webhook chưa được cấu hình');
}

// Thuật toán hash
$algorithm = 'sha1'; // algoritmo de hash

// Tạo chữ ký HMAC
$hmac = base64_encode(hash_hmac($algorithm, $body, $secretKeyOnOpenpixPlatform, true));


// Kiểm tra chữ ký HMAC (so sánh constant-time chống timing attack)
if(hash_equals($hmac, (string)$signature)) {
    // Xử lý sự kiện khi giao dịch hoàn tất
    if ($data['event'] == 'OPENPIX:CHARGE_COMPLETED') {
        $transactionID = validate_alphanumeric($data['charge']['transactionID'] ?? '', 255);
        $status = validate_string($data['charge']['status'] ?? '', 50);
        $amount = validate_float($data['charge']['value'] ?? null, 0.0);
        $userCorrelationID = validate_alphanumeric($data['charge']['correlationID'] ?? '', 255);

        // Kiểm tra trạng thái giao dịch
        if ($status == 'COMPLETED') {
            if ($row = $CMSNT->get_row_safe("SELECT * FROM `payment_openpix` WHERE `trans_id` = ? AND `status` = 0", [$userCorrelationID])) {
                $user = new users;
                $isCong = $user->AddCredits($row['user_id'], $row['price'], __('Recharge OpenPix').' #'.$userCorrelationID, 'TOPUP_openpix_'.$userCorrelationID);
                if ($isCong) {
                    $CMSNT->update('payment_openpix', [
                        'status' => 1,
                        'updated_at' => gettime()
                    ], " `id` = ? ", [$row['id']]);

                    // Tạo log giao dịch gần đây
                    $CMSNT->insert('deposit_log', [
                        'user_id' => $row['user_id'],
                        'method' => 'OpenPix',
                        'amount' => $amount,
                        'received' => $row['price'],
                        'create_time' => time(),
                        'is_virtual' => 0
                    ]);

                    // Gửi thông báo cho admin
                    $my_text = $CMSNT->site('noti_recharge');
                    $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
                    $my_text = str_replace('{username}', getRowRealtime('users', $row['user_id'], 'username'), $my_text);
                    $my_text = str_replace('{method}', 'OpenPix', $my_text);
                    $my_text = str_replace('{amount}', $amount, $my_text);
                    $my_text = str_replace('{price}', format_currency($row['price']), $my_text);
                    $my_text = str_replace('{time}', gettime(), $my_text);
                    sendMessAdmin($my_text);
                }
            }
        }
    } else if ($data['event'] == 'OPENPIX:CHARGE_EXPIRED') {
        $transactionID = validate_alphanumeric($data['charge']['transactionID'] ?? '', 255);
        $status = validate_string($data['charge']['status'] ?? '', 50);
        $amount = validate_float($data['charge']['value'] ?? null, 0.0);
        $userCorrelationID = validate_alphanumeric($data['charge']['correlationID'] ?? '', 255);

        // Xử lý sự kiện khi giao dịch hết hạn
        if ($status == 'EXPIRED') {
            if ($row = $CMSNT->get_row_safe("SELECT * FROM `payment_openpix` WHERE `trans_id` = ? AND `status` = 0", [$userCorrelationID])) {
                $CMSNT->update('payment_openpix', [
                    'status' => 2, // 2 = expired
                    'updated_at' => gettime()
                ], " `id` = ? ", [$row['id']]);
            }
        }
    }
    // Trả về mã trạng thái 200
    http_response_code(200);
} else {
    // Thông báo chữ ký HMAC không hợp lệ
    echo 'Invalid HMAC';
    
    // Ghi log lỗi
    $CMSNT->insert('logs', [
        'user_id'       => 0,
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'   => gettime(),
        'action'        => 'HMAC Error: chữ ký không hợp lệ (event = '.($data['event'] ?? 'unknown').')'
    ]);
    
    // Trả về mã trạng thái 401
    http_response_code(401);
}

