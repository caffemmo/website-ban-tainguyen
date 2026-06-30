<?php

/**
 * ZiniPay Callback / Webhook Handler
 * Xử lý thông báo kết quả thanh toán từ ZiniPay.
 *
 * ZiniPay gọi webhook với invoice_id + status (GET hoặc POST/JSON).
 * Webhook KHÔNG đủ tin cậy để cộng tiền => luôn gọi lại endpoint
 * verify để xác minh trạng thái thực tế trước khi cộng tiền.
 */

define("IN_SITE", true);
require_once(__DIR__ . "/../libs/db.php");
require_once(__DIR__ . "/../config.php");
require_once(__DIR__ . "/../libs/lang.php");
require_once(__DIR__ . "/../libs/helper.php");
require_once(__DIR__ . "/../libs/zinipay.php");
require_once(__DIR__ . "/../libs/database/users.php");
$CMSNT = new DB();

// Kiểm tra cổng thanh toán đã được kích hoạt chưa
if ($CMSNT->site('zinipay_status') != 1) {
    die('Payment gateway is not activated');
}

// CỔNG CHẶN: yêu cầu mã bí mật trong URL callback (?secret=...).
// Chặn người ngoài tùy tiện gọi endpoint (tránh dò/spam verify).
// So sánh hằng-thời-gian (hash_equals) để chống timing attack.
$expectedSecret = (string) $CMSNT->site('zinipay_callback_secret');
$gotSecret      = (string) ($_GET['secret'] ?? $_POST['secret'] ?? '');
if ($expectedSecret === '' || !hash_equals($expectedSecret, $gotSecret)) {
    header("HTTP/1.1 403 Forbidden");
    exit("forbidden");
}

// ZiniPay có thể gửi webhook qua GET (query) hoặc POST (JSON/form)
$params = $_GET;
if (empty($params['invoice_id'])) {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json)) {
        $params = array_merge($params, $json);
    }
    if (!empty($_POST)) {
        $params = array_merge($params, $_POST);
    }
}

$invoice_id = isset($params['invoice_id']) ? validate_alphanumeric($params['invoice_id'], 255) : '';

if (empty($invoice_id)) {
    exit("fail");
}

// Tìm đơn hàng theo invoice_id (đã lưu vào trade_no khi tạo hóa đơn)
$row = $CMSNT->get_row_safe(
    "SELECT * FROM `payment_zinipay` WHERE `trade_no` = ? AND `status` = 0",
    [$invoice_id]
);

if (!$row) {
    // Không có đơn pending khớp => xác nhận đã nhận để gateway ngừng gửi lại
    echo "success";
    exit;
}

// Xác minh trạng thái thực tế từ backend ZiniPay
$config = [
    'api_key' => $CMSNT->site('zinipay_api_key'),
    'api_url' => $CMSNT->site('zinipay_api_url') ?: 'https://api.zinipay.com'
];

$verify = zinipayVerifyPayment($config, $invoice_id);

$verifyStatus = isset($verify['status']) ? strtoupper($verify['status']) : '';
$paidAmount   = isset($verify['amount']) ? (float) $verify['amount'] : 0;
$tradeNo      = $verify['transaction_id'] ?? $invoice_id;
$payMethod    = $verify['payment_method'] ?? '';

if ($verifyStatus === 'COMPLETED') {
    // Chống cộng thiếu: số tiền verify phải >= số tiền đơn hàng
    if ($paidAmount + 0.01 < (float) $row['amount']) {
        exit("fail");
    }

    // ATOMIC CLAIM chống cộng trùng (race condition / replay):
    // Chuyển status 0 -> 1 ngay TRƯỚC khi cộng tiền. update() chỉ trả về true
    // khi affected_rows > 0 (đơn còn đang = 0). Nhờ row-lock của MySQL, nhiều
    // webhook đồng thời sẽ bị tuần tự hóa: chỉ 1 request "chiếm" được đơn,
    // các request còn lại nhận false và không cộng tiền lần nữa.
    $claimed = $CMSNT->update('payment_zinipay', [
        'status'     => 1,
        'trade_no'   => $invoice_id,
        'type'       => $payMethod,
        'updated_at' => gettime()
    ], " `id` = ? AND `status` = 0 ", [$row['id']]);

    if (!$claimed) {
        // Đơn đã được request khác xử lý => xác nhận đã nhận, không cộng lại
        echo "success";
        exit;
    }

    $user = new users();
    $isCong = $user->AddCredits(
        $row['user_id'],
        $row['price'],
        __('Recharge ZiniPay') . ' #' . $row['trans_id'],
        'TOPUP_zinipay_' . $row['trans_id']
    );

    if ($isCong) {
        $CMSNT->insert('deposit_log', [
            'user_id'     => $row['user_id'],
            'method'      => 'ZiniPay' . ($payMethod ? ' ' . $payMethod : ''),
            'amount'      => $row['price'],
            'received'    => $row['price'],
            'create_time' => time(),
            'is_virtual'  => 0
        ]);

        // Gửi thông báo cho Admin
        $my_text = $CMSNT->site('noti_recharge');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', getRowRealtime('users', $row['user_id'], 'username'), $my_text);
        $my_text = str_replace('{method}', 'ZiniPay' . ($payMethod ? ' ' . $payMethod : ''), $my_text);
        $my_text = str_replace('{amount}', $row['price'] . ' BDT', $my_text);
        $my_text = str_replace('{price}', format_currency($row['price']), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
    } else {
        // Cộng tiền thất bại => trả đơn về pending để webhook sau thử lại
        $CMSNT->update('payment_zinipay', [
            'status'     => 0,
            'updated_at' => gettime()
        ], " `id` = ? AND `status` = 1 ", [$row['id']]);
    }

    echo "success";
    exit;
} elseif ($verifyStatus === 'FAILED') {
    $CMSNT->update('payment_zinipay', [
        'status'     => 2,
        'trade_no'   => $invoice_id,
        'type'       => $payMethod,
        'updated_at' => gettime()
    ], " `id` = ? AND `status` = 0 ", [$row['id']]);

    echo "success";
    exit;
}

// PENDING hoặc trạng thái khác: chưa xử lý, xác nhận đã nhận webhook
echo "success";
exit;
