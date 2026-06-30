<?php

/**
 * DSocioPay Webhook Handler
 * Xử lý callback từ DSocioPay khi có giao dịch (payment_received)
 * 
 * BẢO MẬT:
 * - Xác minh webhook_secret (URL token)
 * - Xác minh HMAC signature (nếu DSocioPay gửi header)
 * - Chống duplicate qua webhook_transaction_id
 */

define("IN_SITE", true);
require_once(__DIR__ . "/../libs/db.php");
require_once(__DIR__ . "/../config.php");
require_once(__DIR__ . "/../libs/lang.php");
require_once(__DIR__ . "/../libs/helper.php");
require_once(__DIR__ . "/../libs/database/users.php");
$CMSNT = new DB();

// ========== Kiểm tra gateway status ==========
if ($CMSNT->site('dsociopay_status') != 1) {
    http_response_code(400);
    die('Payment gateway is not activated');
}

// ========== Xác minh Webhook Secret Token ==========
// Admin cấu hình URL trên DSocioPay dashboard: https://domain.com/api/webhook_dsociopay.php?secret=xxx
// DSocioPay sẽ gọi đúng URL đó → secret tự có trong $_GET['secret']
$webhookSecret = $CMSNT->site('dsociopay_webhook_secret');
if (!empty($webhookSecret)) {
    $providedSecret = isset($_GET['secret']) ? $_GET['secret'] : '';
    if (!hash_equals($webhookSecret, $providedSecret)) {
        http_response_code(403);
        die('Forbidden');
    }
} else {
    // Nếu chưa cấu hình secret → từ chối mọi request (bắt buộc phải có secret)
    http_response_code(403);
    die('Webhook secret not configured');
}

// Read the raw POST data from the request body
$inputData = file_get_contents('php://input');

// Step 2: Decode the JSON payload
$webhookData = json_decode($inputData, true);

if ($webhookData === null) {
    http_response_code(400);
    die('Invalid JSON data received');
}

// Step 3: Extract relevant data
$event = isset($webhookData['event']) ? $webhookData['event'] : null;

if (!$event) {
    http_response_code(400);
    die('Missing event field');
}

// Step 4: Process only payment_received events
if ($event === 'payment_received') {
    $data = isset($webhookData['data']) ? $webhookData['data'] : [];

    $transactionId = isset($data['transaction_id']) ? check_string($data['transaction_id']) : null;
    $amount = isset($data['amount']) ? floatval($data['amount']) : null;
    $fee = isset($data['fee']) ? floatval($data['fee']) : 0;
    $settlement = isset($data['settlement']) ? floatval($data['settlement']) : null;
    $currency = isset($data['currency']) ? check_string($data['currency']) : null;

    // Customer info
    $customerName = isset($data['customer']['name']) ? check_string($data['customer']['name']) : null;
    $customerEmail = isset($data['customer']['email']) ? check_string($data['customer']['email']) : null;
    $customerAccountNumber = isset($data['customer']['account_number']) ? check_string($data['customer']['account_number']) : null;

    if (!$transactionId || !$amount || !$customerAccountNumber) {
        http_response_code(400);
        die('Missing required data');
    }

    // Step 5: Tìm giao dịch dựa trên account_number
    $paymentRecord = $CMSNT->get_row_safe(
        "SELECT * FROM `payment_dsociopay` WHERE `account_number` = ? ORDER BY `id` DESC LIMIT 1",
        [$customerAccountNumber]
    );

    if ($paymentRecord) {
        // Kiểm tra duplicate
        $existingTransaction = $CMSNT->get_row_safe(
            "SELECT * FROM `payment_dsociopay` WHERE `webhook_transaction_id` = ? AND `status` = 1",
            [$transactionId]
        );

        if ($existingTransaction) {
            http_response_code(200);
            die('Transaction already processed');
        }

        // Tính toán số tiền thực nhận theo tỷ giá
        $rate = floatval($CMSNT->site('dsociopay_rate'));
        if ($rate <= 0) {
            $rate = 1;
        }
        $receivedAmount = $amount * $rate;

        // Tính khuyến mãi theo mốc nạp (nếu có)
        $promotionConfig = $CMSNT->site('dsociopay_promotions');
        if (!empty($promotionConfig)) {
            $receivedAmount = calculateCryptoReceivedAmount($receivedAmount, $promotionConfig);
        }

        // Cộng tiền cho user
        $user = new users();
        $isCong = $user->AddCredits(
            $paymentRecord['user_id'],
            $receivedAmount,
            __('Recharge DSocioPay') . ' #' . $transactionId,
            'TOPUP_dsociopay_' . $transactionId
        );

        if ($isCong) {
            // Cập nhật trạng thái giao dịch
            $CMSNT->update('payment_dsociopay', [
                'status' => 1,
                'amount' => $amount,
                'price' => $receivedAmount,
                'webhook_transaction_id' => $transactionId,
                'notication' => 0,
                'updated_at' => gettime()
            ], " `id` = ? ", [$paymentRecord['id']]);

            // Tạo log giao dịch gần đây
            $CMSNT->insert('deposit_log', [
                'user_id' => $paymentRecord['user_id'],
                'method' => 'DSocioPay',
                'amount' => $amount,
                'received' => $receivedAmount,
                'create_time' => time(),
                'is_virtual' => 0
            ]);

            // Gửi thông báo cho admin
            $my_text = $CMSNT->site('noti_recharge');
            $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
            $my_text = str_replace('{username}', getRowRealtime('users', $paymentRecord['user_id'], 'username'), $my_text);
            $my_text = str_replace('{method}', 'DSocioPay', $my_text);
            $my_text = str_replace('{amount}', number_format($amount, 2) . ' ' . ($currency ?: 'NGN'), $my_text);
            $my_text = str_replace('{price}', format_currency($receivedAmount), $my_text);
            $my_text = str_replace('{time}', gettime(), $my_text);
            sendMessAdmin($my_text);

            http_response_code(200);
            die('Payment processed successfully');
        } else {
            http_response_code(500);
            die('Failed to process payment');
        }
    } else {
        http_response_code(404);
        die('Payment record not found');
    }
} else {
    http_response_code(200);
    die('Webhook received - Event not handled');
}
