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

if ($CMSNT->site('status') != 1) {
    http_response_code(503);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Website is offline']);
    exit;
}

// Lấy Secret Key từ cấu hình (dùng làm Bearer Token xác thực)
$expectedToken = $CMSNT->site('token_webhook_web2m');
if (empty($expectedToken)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Secret Key not configured']);
    exit;
}

// Lấy token từ header Authorization
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? '');

if (empty($authHeader) || strpos($authHeader, 'Bearer ') !== 0) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authorization header not found or invalid']);
    exit;
}

$receivedToken = substr($authHeader, 7);

// So sánh token nhận được với token hợp lệ (constant-time chống timing attack)
if (!hash_equals((string)$expectedToken, (string)$receivedToken)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}

// Nhận dữ liệu từ webhook
$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

if (json_last_error() !== JSON_ERROR_NONE || !isset($data['transactions']) || !is_array($data['transactions'])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid payload, transactions not found']);
    exit;
}

// Cập nhật thời gian cron bank để không báo lỗi cron
$CMSNT->update("settings", ['value' => time()], " `name` = 'check_time_cron_bank' ");

foreach ($data['transactions'] as $transaction) {
    /**
     * Cấu trúc dữ liệu pay2s.vn:
     * - id: ID giao dịch
     * - gateway: Tên ngân hàng (ACB, VCB, MB...)
     * - transactionDate: Thời gian giao dịch
     * - transactionNumber: Mã giao dịch
     * - accountNumber: Số tài khoản
     * - content: Nội dung chuyển khoản
     * - transferType: Loại giao dịch (IN/OUT)
     * - transferAmount: Số tiền
     * - checksum: Mã checksum
     */
    $tid            = validate_string($transaction['transactionNumber'] ?? ($transaction['id'] ?? ''), 100);
    $description    = validate_string($transaction['content'] ?? '', 255);
    $amount         = validate_float($transaction['transferAmount'] ?? null, 0.0);
    $method         = validate_string($transaction['gateway'] ?? '', 50);
    $transferType   = $transaction['transferType'] ?? '';

    if ($tid === false || empty($tid)) {
        continue;
    }
    if ($amount === false || $amount < $CMSNT->site('bank_min') || $amount > $CMSNT->site('bank_max')) {
        continue;
    }

    // Chỉ xử lý giao dịch tiền VÀO
    if ($transferType == 'IN') {
        if ($getUser = findUserByDescription($description !== false ? $description : '', $bankRechargeType)) {
            if ($CMSNT->num_rows_safe(" SELECT `id` FROM `payment_bank` WHERE `tid` = ? AND `description` = ? ", [$tid, ($description !== false ? $description : '')]) == 0) {
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
                    $isCong = $user->AddCredits($getUser['id'], $received, "Nạp tiền tự động qua $method (#$tid - " . ($description !== false ? $description : '') . " - $amount)", 'TOPUP_' . ($transaction['accountNumber'] ?? 'PAY2S') . '_' . $tid);
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

    // Ghi log tất cả giao dịch (IN + OUT)
    $countRow = $CMSNT->get_row_safe(" SELECT COUNT(id) AS c FROM `log_bank_auto` WHERE `tid` = ? AND `description` = ? ", [$tid, ($description !== false ? $description : '')]);
    if (($countRow['c'] ?? 0) == 0) {
        $CMSNT->insert("log_bank_auto", array(
            'tid'               => $tid,
            'method'            => $method,
            'description'       => ($description !== false ? $description : ''),
            'type'              => $transferType,
            'amount'            => $amount,
            'create_gettime'    => gettime()
        ));
    }
}

// Trả kết quả webhook theo chuẩn pay2s.vn
http_response_code(200);
header('Content-Type: application/json');
echo json_encode(['success' => true]);
