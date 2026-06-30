<?php

/**
 * PaymentPoint Webhook Handler
 * Xử lý callback từ PaymentPoint khi có giao dịch
 */

define("IN_SITE", true);
require_once(__DIR__ . "/../libs/db.php");
require_once(__DIR__ . "/../config.php");
require_once(__DIR__ . "/../libs/lang.php");
require_once(__DIR__ . "/../libs/helper.php");
require_once(__DIR__ . "/../libs/database/users.php");
$CMSNT = new DB();

// Ghi log webhook nhận được (để debug)
function logWebhook($message, $data = null)
{
    global $CMSNT;
    $logData = date('Y-m-d H:i:s') . ' - ' . $message;
    if ($data) {
        $logData .= ' - Data: ' . json_encode($data);
    }
    $logData = check_string($logData);
    $CMSNT->insert('logs', [
        'user_id'    => 0,
        'ip'         => myip(),
        'device'     => getUserAgent(),
        'createdate' => gettime(),
        'action'     => '[PaymentPoint Webhook] ' . substr($logData, 0, 500)
    ]);
    return true;
}

// Kiểm tra xem cổng thanh toán PaymentPoint có được kích hoạt không
if ($CMSNT->site('paymentpoint_status') != 1) {
    http_response_code(400);
    die('Payment gateway is not activated');
}

// Step 1: Read the raw POST data from the request body
$inputData = file_get_contents('php://input');

// Step 2: Get the signature from the headers
$signatureHeader = isset($_SERVER['HTTP_PAYMENTPOINT_SIGNATURE']) ? $_SERVER['HTTP_PAYMENTPOINT_SIGNATURE'] : '';

// Step 3: Get secret key from settings
$secretKey = $CMSNT->site('paymentpoint_api_secret');

if (empty($secretKey)) {
    logWebhook('Secret key not configured');
    http_response_code(400);
    die('Secret key not configured');
}

// Step 4: Calculate the expected signature using HMAC-SHA256
$calculatedSignature = hash_hmac('sha256', $inputData, $secretKey);

// Step 5: Verify if the calculated signature matches the signature from the header
if (!hash_equals($calculatedSignature, $signatureHeader)) {
    // If signatures don't match, return an error response (possible tampering)
    logWebhook('Invalid signature', [
        'received' => $signatureHeader,
        'calculated' => $calculatedSignature
    ]);
    http_response_code(400);
    die('Invalid signature');
}

// Step 6: Decode the JSON payload
$webhookData = json_decode($inputData, true);

// Step 7: Ensure the data was successfully decoded
if ($webhookData === null) {
    logWebhook('Invalid JSON data received');
    http_response_code(400);
    die('Invalid JSON data received');
}

// Log webhook data
logWebhook('Webhook received', $webhookData);

// Step 8: Extract relevant data from the decoded webhook
$notificationStatus = isset($webhookData['notification_status']) ? check_string($webhookData['notification_status']) : null;
$transactionId = isset($webhookData['transaction_id']) ? check_string($webhookData['transaction_id']) : null;
$amountPaid = isset($webhookData['amount_paid']) ? floatval($webhookData['amount_paid']) : null;
$settlementAmount = isset($webhookData['settlement_amount']) ? floatval($webhookData['settlement_amount']) : null;
$settlementFee = isset($webhookData['settlement_fee']) ? floatval($webhookData['settlement_fee']) : 0;
$transactionStatus = isset($webhookData['transaction_status']) ? check_string($webhookData['transaction_status']) : null;
$timestamp = isset($webhookData['timestamp']) ? check_string($webhookData['timestamp']) : null;

// Receiver info
$receiverAccountNumber = isset($webhookData['receiver']['account_number']) ? check_string($webhookData['receiver']['account_number']) : null;
$receiverBank = isset($webhookData['receiver']['bank']) ? check_string($webhookData['receiver']['bank']) : null;
$receiverName = isset($webhookData['receiver']['name']) ? check_string($webhookData['receiver']['name']) : null;

// Customer info
$customerId = isset($webhookData['customer']['customer_id']) ? check_string($webhookData['customer']['customer_id']) : null;
$customerEmail = isset($webhookData['customer']['email']) ? check_string($webhookData['customer']['email']) : null;
$customerName = isset($webhookData['customer']['name']) ? check_string($webhookData['customer']['name']) : null;

// Check if required data is present
if (!$transactionId || !$amountPaid || !$transactionStatus) {
    logWebhook('Missing required data', [
        'transaction_id' => $transactionId,
        'amount_paid' => $amountPaid,
        'transaction_status' => $transactionStatus
    ]);
    http_response_code(400);
    die('Missing required data');
}

// Step 9: Process the payment if successful
if ($notificationStatus === 'payment_successful' && $transactionStatus === 'success') {

    // Tìm giao dịch dựa trên account_number của receiver
    $paymentRecord = null;

    // Tìm theo account_number
    if ($receiverAccountNumber) {
        $paymentRecord = $CMSNT->get_row_safe(
            "SELECT * FROM `payment_paymentpoint` WHERE `account_number` = ? AND `status` = 0 ORDER BY `id` DESC LIMIT 1",
            [$receiverAccountNumber]
        );
    }

    // Nếu không tìm thấy, thử tìm theo customer_id
    if (!$paymentRecord && $customerId) {
        $paymentRecord = $CMSNT->get_row_safe(
            "SELECT * FROM `payment_paymentpoint` WHERE `customer_id` = ? AND `status` = 0 ORDER BY `id` DESC LIMIT 1",
            [$customerId]
        );
    }

    if ($paymentRecord) {
        // Kiểm tra xem giao dịch này đã được xử lý chưa (tránh xử lý trùng)
        $existingTransaction = $CMSNT->get_row_safe(
            "SELECT * FROM `payment_paymentpoint` WHERE `webhook_transaction_id` = ? AND `status` = 1",
            [$transactionId]
        );

        if ($existingTransaction) {
            logWebhook('Transaction already processed', ['transaction_id' => $transactionId]);
            http_response_code(200);
            die('Transaction already processed');
        }

        // Tính toán số tiền thực nhận theo tỷ giá
        $rate = floatval($CMSNT->site('paymentpoint_rate'));
        if ($rate <= 0) {
            $rate = 1;
        }
        $receivedAmount = $amountPaid * $rate;

        // Cộng tiền cho user
        $user = new users();
        $isCong = $user->AddCredits(
            $paymentRecord['user_id'],
            $receivedAmount,
            __('Recharge PaymentPoint') . ' #' . $transactionId,
            'TOPUP_paymentpoint_' . $transactionId
        );

        if ($isCong) {
            // Cập nhật trạng thái giao dịch
            $CMSNT->update('payment_paymentpoint', [
                'status' => 1,
                'amount' => $amountPaid,
                'price' => $receivedAmount,
                'webhook_transaction_id' => $transactionId,
                'updated_at' => gettime()
            ], " `id` = ? ", [$paymentRecord['id']]);

            // Tạo log giao dịch gần đây
            $CMSNT->insert('deposit_log', [
                'user_id' => $paymentRecord['user_id'],
                'method' => 'PaymentPoint',
                'amount' => $amountPaid,
                'received' => $receivedAmount,
                'create_time' => time(),
                'is_virtual' => 0
            ]);

            // Gửi thông báo cho admin
            $my_text = $CMSNT->site('noti_recharge');
            $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
            $my_text = str_replace('{username}', getRowRealtime('users', $paymentRecord['user_id'], 'username'), $my_text);
            $my_text = str_replace('{method}', 'PaymentPoint (' . $receiverBank . ')', $my_text);
            $my_text = str_replace('{amount}', number_format($amountPaid, 2) . ' NGN', $my_text);
            $my_text = str_replace('{price}', format_currency($receivedAmount), $my_text);
            $my_text = str_replace('{time}', gettime(), $my_text);
            sendMessAdmin($my_text);

            logWebhook('Payment processed successfully', [
                'user_id' => $paymentRecord['user_id'],
                'amount' => $amountPaid,
                'received' => $receivedAmount,
                'transaction_id' => $transactionId
            ]);

            http_response_code(200);
            die('Payment processed successfully');
        } else {
            logWebhook('Failed to add credits', [
                'user_id' => $paymentRecord['user_id'],
                'amount' => $receivedAmount
            ]);
            http_response_code(500);
            die('Failed to process payment');
        }
    } else {
        logWebhook('Payment record not found', [
            'account_number' => $receiverAccountNumber,
            'customer_id' => $customerId
        ]);
        http_response_code(404);
        die('Payment record not found');
    }
} else {
    // Giao dịch không thành công hoặc trạng thái khác
    logWebhook('Payment not successful', [
        'notification_status' => $notificationStatus,
        'transaction_status' => $transactionStatus
    ]);
    http_response_code(200);
    die('Webhook received - Payment not successful');
}
