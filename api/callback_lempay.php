<?php

/**
 * LemPay Callback Handler
 * Xử lý thông báo kết quả thanh toán từ LemPay
 * 
 * LemPay sẽ gọi đến file này qua phương thức GET khi thanh toán thành công
 */

define("IN_SITE", true);
require_once(__DIR__ . "/../libs/db.php");
require_once(__DIR__ . "/../config.php");
require_once(__DIR__ . "/../libs/lang.php");
require_once(__DIR__ . "/../libs/helper.php");
require_once(__DIR__ . "/../libs/lempay.php");
require_once(__DIR__ . "/../libs/database/users.php");
$CMSNT = new DB();

// Kiểm tra cổng thanh toán đã được kích hoạt chưa
if ($CMSNT->site('lempay_status') != 1) {
    die('Payment gateway is not activated');
}

// LemPay gửi callback qua GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header("HTTP/1.1 405 Method Not Allowed");
    exit("Method Not Allowed");
}

// Lấy các tham số từ callback
// pid, trade_no, out_trade_no, type, name, money, trade_status, sign, sign_type
$pid          = isset($_GET['pid']) ? validate_int($_GET['pid'], 1) : null;
$trade_no     = isset($_GET['trade_no']) ? validate_alphanumeric($_GET['trade_no'], 255) : '';
$out_trade_no = isset($_GET['out_trade_no']) ? validate_alphanumeric($_GET['out_trade_no'], 255) : '';
$type         = isset($_GET['type']) ? validate_string($_GET['type'], 20) : '';
$name         = isset($_GET['name']) ? validate_string($_GET['name'], 255) : '';
$money        = isset($_GET['money']) ? validate_float($_GET['money'], 0.0) : 0;
$trade_status = isset($_GET['trade_status']) ? validate_string($_GET['trade_status'], 50) : '';
$sign         = isset($_GET['sign']) ? $_GET['sign'] : '';

// Kiểm tra các tham số bắt buộc
if (empty($out_trade_no) || empty($trade_no) || empty($trade_status) || $money <= 0) {
    exit("fail");
}

// Xác thực chữ ký
$merchantKey = $CMSNT->site('lempay_key');
if (!lempayVerifyCallback($_GET, $merchantKey)) {
    exit("fail");
}

// Lấy tên hiển thị phương thức thanh toán
$displayType = lempayGetPaymentTypeName($type);

// Xử lý theo trạng thái giao dịch
if ($trade_status === 'TRADE_SUCCESS') {
    // Giao dịch thành công
    $row = $CMSNT->get_row_safe(
        "SELECT * FROM `payment_lempay` WHERE `trans_id` = ? AND `status` = 0",
        [$out_trade_no]
    );

    if ($row) {
        $user = new users();
        $isCong = $user->AddCredits(
            $row['user_id'],
            $row['price'],
            __('Recharge LemPay') . ' ' . $displayType . ' #' . $out_trade_no,
            'TOPUP_lempay_' . $out_trade_no
        );

        if ($isCong) {
            // Cập nhật trạng thái đơn hàng
            $CMSNT->update('payment_lempay', [
                'status'     => 1,
                'trade_no'   => $trade_no,
                'type'       => $type,
                'updated_at' => gettime()
            ], " `id` = ? ", [$row['id']]);

            // Tạo log giao dịch gần đây
            $CMSNT->insert('deposit_log', [
                'user_id'    => $row['user_id'],
                'method'     => 'LemPay ' . $displayType,
                'amount'     => $money,
                'received'   => $row['price'],
                'create_time' => time(),
                'is_virtual' => 0
            ]);

            // Gửi thông báo cho Admin
            $my_text = $CMSNT->site('noti_recharge');
            $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
            $my_text = str_replace('{username}', getRowRealtime('users', $row['user_id'], 'username'), $my_text);
            $my_text = str_replace('{method}', 'LemPay ' . $displayType, $my_text);
            $my_text = str_replace('{amount}', $money . ' CNY', $my_text);
            $my_text = str_replace('{price}', format_currency($row['price']), $my_text);
            $my_text = str_replace('{time}', gettime(), $my_text);
            sendMessAdmin($my_text);
        }
    }

    // Trả về success cho LemPay
    echo "success";
    exit;
} else {
    // Giao dịch thất bại hoặc trạng thái khác
    $row = $CMSNT->get_row_safe(
        "SELECT * FROM `payment_lempay` WHERE `trans_id` = ? AND `status` = 0",
        [$out_trade_no]
    );

    if ($row) {
        $CMSNT->update('payment_lempay', [
            'status'     => 2,
            'trade_no'   => $trade_no,
            'type'       => $type,
            'updated_at' => gettime()
        ], " `id` = ? AND `status` = 0 ", [$row['id']]);
    }

    echo "success";
    exit;
}
