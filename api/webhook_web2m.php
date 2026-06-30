<?php

define("IN_SITE", true);
require_once(__DIR__ . "/../libs/db.php");
require_once(__DIR__ . "/../config.php");
require_once(__DIR__ . "/../libs/lang.php");
require_once(__DIR__ . "/../libs/helper.php");
require_once(__DIR__ . "/../libs/database/users.php");
$CMSNT = new DB();

// Kiểu nạp tiền: prefix_id (mặc định) hoặc fullname_transfer
$bankRechargeType = $CMSNT->site('bank_recharge_type') ?: 'prefix_id';


if ($CMSNT->site('status') != 1 && isSecureCookie('admin_login') != true) {
    die('status_website_off');
}
if (empty($CMSNT->site('token_webhook_web2m'))) {
    die('token_webhook_empty');
}
function writeLog($message, $logFile = 'webhook.log')
{
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;

    // Ghi thông điệp log vào tệp log
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}


$accessToken = $CMSNT->site('token_webhook_web2m'); // Thay YOUR_ACCESS_TOKEN bằng Access Token thực tế
$receivedData = file_get_contents('php://input'); // Nhận dữ liệu từ webhook

// Ghi dữ liệu vào log để kiểm tra
// writeLog($receivedData);

// Kiểm tra xem tiêu đề Authorization có tồn tại trong yêu cầu
if (isset($_SERVER['HTTP_AUTHORIZATION']) && strpos($_SERVER['HTTP_AUTHORIZATION'], 'Bearer ') === 0) {
    $bearerToken = substr($_SERVER['HTTP_AUTHORIZATION'], 7); // Lấy chuỗi token sau 'Bearer '

    // Kiểm tra token khớp (so sánh constant-time chống timing attack)
    if (hash_equals((string)$accessToken, (string)$bearerToken)) {
        // Dữ liệu hợp lệ, tiếp tục xử lý
        $result = json_decode($receivedData, true);
        foreach ($result['data'] as $data) {
            $tid            = validate_alphanumeric($data['transactionNum'] ?? '', 100);
            $description    = validate_string($data['description'] ?? '', 255);
            $amount         = validate_float($data['amount'] ?? null, 0.0);
            $method         = validate_string($data['bank'] ?? '', 50);
            if ($tid === false || empty($tid)) {
                continue;
            }
            if ($amount === false || $amount < $CMSNT->site('bank_min') || $amount > $CMSNT->site('bank_max')) {
                continue;
            }

            // Xử lý dữ liệu tại đây
            if ($data['type'] == 'IN') {
                if ($getUser = findUserByDescription($description !== false ? $description : '', $bankRechargeType)) {
                    if ($CMSNT->num_rows_safe(" SELECT `id` FROM `payment_bank` WHERE `tid` = ? AND `description` = ?  ", [$tid, ($description !== false ? $description : '')]) == 0) {
                        $received = checkPromotion($amount);
                        $insertSv2 = $CMSNT->insert("payment_bank", array(
                            'tid'               => $tid,
                            'method'            => $method,
                            'user_id'           => $getUser['id'],
                            'description'       => ($description !== false ? $description : ''),
                            'amount'            => $amount,
                            'received'          => $received,
                            'create_gettime'    => gettime(),
                            'create_time'       => time()
                        ));
                        if ($insertSv2) {
                            $user = new users();
                            $isCong = $user->AddCredits($getUser['id'], $received, "Nạp tiền tự động qua $method (#$tid - " . ($description !== false ? $description : '') . " - $amount)", 'TOPUP_' . ($data['accountNumber'] ?? 'WEB2M') . '_' . $tid);
                            if ($isCong) {

                                // XỬ LÝ TIỀN NỢ NẾU CÓ
                                debit_processing($getUser['id']);
                                // TẠO LOG GIAO DỊCH GẦN ĐÂY
                                $CMSNT->insert('deposit_log', [
                                    'user_id'       => $getUser['id'],
                                    'method'        => $method,
                                    'amount'        => $amount,
                                    'received'      => $received,
                                    'create_time'   => time(),
                                    'is_virtual'    => 0
                                ]);
                                /** SEND NOTI CHO ADMIN */
                                $my_text = $CMSNT->site('noti_recharge');
                                $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
                                $my_text = str_replace('{username}', $getUser['username'], $my_text);
                                $my_text = str_replace('{method}', $method, $my_text);
                                $my_text = str_replace('{amount}', format_currency($amount), $my_text);
                                $my_text = str_replace('{price}', format_currency($received), $my_text);
                                $my_text = str_replace('{time}', gettime(), $my_text);
                                sendMessAdmin($my_text);
                            }
                        }
                    }
                }
            }

            $countRow = $CMSNT->get_row_safe(" SELECT COUNT(id) AS c FROM `log_bank_auto` WHERE `tid` = ? AND `description` = ?  ", [$tid, ($description !== false ? $description : '')]);
            if (($countRow['c'] ?? 0) == 0) {
                $CMSNT->insert("log_bank_auto", array(
                    'tid'               => $tid,
                    'method'            => $method,
                    'description'       => ($description !== false ? $description : ''),
                    'type'              => $data['type'],
                    'amount'            => $amount,
                    'create_gettime'    => gettime()
                ));
            }
        }


        // Trả kết quả webhook
        $response = array(
            "status" => true,
            "msg" => "OK"
        );
        echo json_encode($response);
    } else {
        // Chữ ký không khớp, từ chối yêu cầu
        http_response_code(401); // Unauthorized
        echo 'Chữ ký không hợp lệ.';
    }
} else {
    // Tiêu đề Authorization không tồn tại hoặc không hợp lệ
    http_response_code(401); // Unauthorized
    echo 'Access Token không được cung cấp hoặc không hợp lệ.';
}
