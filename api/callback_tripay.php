<?php

/**
 * TriPay Callback Handler
 * Xử lý thông báo kết quả thanh toán từ TriPay
 * 
 * TriPay sẽ gọi đến file này qua phương thức POST khi trạng thái giao dịch thay đổi
 * Header quan trọng: X-Callback-Signature
 */

define("IN_SITE", true);
require_once(__DIR__ . "/../libs/db.php");
require_once(__DIR__ . "/../config.php");
require_once(__DIR__ . "/../libs/lang.php");
require_once(__DIR__ . "/../libs/helper.php");
require_once(__DIR__ . "/../libs/tripay.php");
require_once(__DIR__ . "/../libs/database/users.php");
require_once(__DIR__ . "/../libs/telegram-shop/TelegramShopBot.php");

$CMSNT = new DB();

// Kiểm tra cổng thanh toán đã được kích hoạt chưa
if ($CMSNT->site('tripay_status') != 1) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Payment gateway is not activated']));
}

// TriPay gửi callback qua POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Method Not Allowed']));
}

// Lấy raw JSON body
$jsonBody = file_get_contents('php://input');
if (empty($jsonBody)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Empty request body']));
}

// Lấy signature từ header
$callbackSignature = $_SERVER['HTTP_X_CALLBACK_SIGNATURE'] ?? '';
if (empty($callbackSignature)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Missing callback signature']));
}

// Xác thực signature
$privateKey = $CMSNT->site('tripay_private_key');
if (!tripayVerifyCallback($callbackSignature, $jsonBody, $privateKey)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Invalid signature']));
}

// Parse JSON
$data = json_decode($jsonBody, true);
if ($data === null) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Invalid JSON']));
}

// Lấy thông tin từ callback
$merchantRef = $data['merchant_ref'] ?? '';
$reference   = $data['reference'] ?? '';
$status      = $data['status'] ?? '';
$totalAmount = $data['total_amount'] ?? 0;

// Validate tham số bắt buộc
if (empty($merchantRef) || empty($status)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Missing required parameters']));
}

// Lấy tên phương thức
$methodName = tripayGetMethodName($data['payment_method'] ?? '');

// Xử lý theo trạng thái giao dịch
if ($status === 'PAID') {
    // Giao dịch thành công
    $row = $CMSNT->get_row_safe(
        "SELECT * FROM `payment_tripay` WHERE `trans_id` = ? AND `status` = 0",
        [$merchantRef]
    );

    if ($row) {
        $user = new users();

        // Cộng tiền cho user
        $isCong = $user->AddCredits(
            $row['user_id'],
            $row['price'],
            __('Recharge TriPay') . ' ' . $methodName . ' #' . $merchantRef,
            'TOPUP_tripay_' . $merchantRef
        );

        if ($isCong) {
            // Cập nhật trạng thái đơn hàng
            $CMSNT->update('payment_tripay', [
                'status'     => 1,
                'reference'  => $reference,
                'method'     => $data['payment_method'] ?? '',
                'updated_at' => gettime()
            ], " `id` = ? ", [$row['id']]);

            // Tạo log giao dịch gần đây
            $CMSNT->insert('deposit_log', [
                'user_id'    => $row['user_id'],
                'method'     => 'TriPay ' . $methodName,
                'amount'     => $totalAmount,
                'received'   => $row['price'],
                'create_time' => time(),
                'is_virtual' => 0
            ]);

            // Gửi thông báo cho Admin
            $my_text = $CMSNT->site('noti_recharge');
            $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
            $my_text = str_replace('{username}', getRowRealtime('users', $row['user_id'], 'username'), $my_text);
            $my_text = str_replace('{method}', 'TriPay ' . $methodName, $my_text);
            $my_text = str_replace('{amount}', number_format($totalAmount) . ' IDR', $my_text);
            $my_text = str_replace('{price}', format_currency($row['price']), $my_text);
            $my_text = str_replace('{time}', gettime(), $my_text);
            sendMessAdmin($my_text);

            // Gửi thông báo Telegram cho user (nếu đăng ký qua Telegram)
            TelegramShopBot::notifyRecharge(
                $row['user_id'],
                'TriPay ' . $methodName,
                number_format($totalAmount) . ' IDR',
                $row['price']
            );
        }
    }

    // Trả về success cho TriPay
    echo json_encode(['success' => true]);
    exit;
} elseif ($status === 'EXPIRED' || $status === 'FAILED' || $status === 'REFUND') {
    // Giao dịch thất bại hoặc hết hạn
    $row = $CMSNT->get_row_safe(
        "SELECT * FROM `payment_tripay` WHERE `trans_id` = ? AND `status` = 0",
        [$merchantRef]
    );

    if ($row) {
        $CMSNT->update('payment_tripay', [
            'status'     => 2,
            'reference'  => $reference,
            'method'     => $data['payment_method'] ?? '',
            'updated_at' => gettime()
        ], " `id` = ? AND `status` = 0 ", [$row['id']]);
    }

    echo json_encode(['success' => true]);
    exit;
} else {
    // Trạng thái khác (UNPAID...) - chỉ acknowledge
    echo json_encode(['success' => true]);
    exit;
}
