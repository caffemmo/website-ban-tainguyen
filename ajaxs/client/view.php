<?php

define("IN_SITE", true);
require_once(__DIR__ . "/../../config.php");
require_once(__DIR__ . "/../../libs/db.php");
require_once(__DIR__ . "/../../libs/lang.php");
require_once(__DIR__ . "/../../libs/helper.php");


if ($CMSNT->site('status_demo') != 0) {
    $data = json_encode([
        'status'    => 'error',
        'msg'       => __('This function cannot be used because this is a demo site')
    ]);
    die($data);
}
if (!isset($_POST['action'])) {
    $data = json_encode([
        'status'    => 'error',
        'msg'       => __('The Request Not Found')
    ]);
    die($data);
}
if ($_POST['action'] == 'download_order') {
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

    if (empty($_POST['trans_id'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Đơn hàng không hợp lệ')]));
    }

    // Validate transaction ID
    $trans_id = validate_alphanumeric($_POST['trans_id'], 32);
    if ($trans_id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Đơn hàng không hợp lệ')]));
    }

    if (!$order = $CMSNT->get_row_safe("SELECT * FROM `product_order` WHERE `trans_id` = ? AND `buyer` = ? AND `trash` = 0", [$trans_id, $getUser['id']])) {
        die(json_encode(['status' => 'error', 'msg' => __('Đơn hàng không tồn tại trong hệ thống')]));
    }
    // check bảo mật ip
    if ($order['status_view_order'] == 1 || $CMSNT->site('isPurchaseIpVerified') == 1) {
        if ($order['ip'] != myip()) {
            die(json_encode(['status' => 'error', 'msg' => __('Địa chỉ IP của bạn không khớp với địa chỉ IP bạn dùng để mua hàng')]));
        }
    }
    // check bảo mật device
    if ($order['status_view_order'] == 1 || $CMSNT->site('isPurchaseDeviceVerified') == 1) {
        if ($order['device'] != getUserAgent()) {
            die(json_encode(['status' => 'error', 'msg' => __('Trình duyệt của bạn không khớp với trình duyệt lúc bạn mua hàng')]));
        }
    }
    // Lấy dữ liệu từ products với xử lý encoding an toàn
    $product_text = getRowRealtime('products', $order['product_id'], 'text_txt');
    if ($product_text) {
        $accounts = fix_order_encoding($product_text) . PHP_EOL;
    } else {
        $accounts = '';
    }

    // Lấy dữ liệu từ product_sold với xử lý encoding an toàn
    foreach ($CMSNT->get_list_safe("SELECT * FROM `product_sold` WHERE `trans_id` = ? AND `buyer` = ? ORDER BY id DESC", [$trans_id, $getUser['id']]) as $account) {
        if (!empty($account['account'])) {
            $account_data = fix_order_encoding($account['account']);
            $accounts .= $account_data . PHP_EOL;
        }
    }
    $file = $trans_id . ".txt";
    $data = json_encode([
        'status'    => 'success',
        'filename'  => $file,
        'accounts'  => $accounts,
        'msg'       => __('Đang tải xuống đơn hàng...')
    ]);
    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => __('Download order') . ' (' . $order['trans_id'] . ')'
    ]);
    die($data);
}

if ($_POST['action'] == 'loadStatusInvoice') {
    if (empty($_POST['trans_id'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Trans ID does not exist in the system')]));
    }

    // Validate transaction ID
    $trans_id = validate_alphanumeric($_POST['trans_id'], 32);
    if ($trans_id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Trans ID does not exist in the system')]));
    }

    if (!$row = $CMSNT->get_row_safe("SELECT * FROM `invoices` WHERE `trans_id` = ?", [$trans_id])) {
        die(json_encode(['status' => 'error', 'msg' => __('Trans ID does not exist in the system')]));
    }

    die(json_encode([
        'data'  => [
            'status'   => $row['status']
        ],
        'status' => 'success',
        'msg' => ''
    ]));
}

// HIỂN THỊ THÔNG BÁO KHI NẠP TIỀN
if ($_POST['action'] == 'notication_topup') {
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

    if (!$row = $CMSNT->get_row_safe("SELECT * FROM `payment_bank` WHERE `notication` = 0 AND `user_id` = ?", [$getUser['id']])) {
        die(json_encode(['status' => 'error', 'msg' => __('Không có lịch sử nạp tiền gần đây')]));
    }

    $CMSNT->update('payment_bank', [
        'notication'    => 1
    ], " `id` = ?", [$row['id']]);

    die(json_encode([
        'status' => 'success',
        'msg' => __('Nạp tiền thành công') . ' ' . format_currency($row['received'])
    ]));
}
if ($_POST['action'] == 'notication_topup_momo') {
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

    if (!$row = $CMSNT->get_row_safe("SELECT * FROM `payment_momo` WHERE `notication` = 0 AND `user_id` = ?", [$getUser['id']])) {
        die(json_encode(['status' => 'error', 'msg' => __('Không có lịch sử nạp tiền gần đây')]));
    }

    $CMSNT->update('payment_momo', [
        'notication'    => 1
    ], " `id` = ?", [$row['id']]);

    die(json_encode([
        'status' => 'success',
        'msg' => __('Nạp tiền thành công') . ' ' . format_currency($row['received'])
    ]));
}
if ($_POST['action'] == 'notication_topup_xipay') {
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

    if (!$row = $CMSNT->get_row_safe("SELECT * FROM `payment_xipay` WHERE `notication` = 0 AND `user_id` = ? AND `status` = 1", [$getUser['id']])) {
        die(json_encode(['status' => 'error', 'msg' => __('Không có lịch sử nạp tiền gần đây')]));
    }

    $CMSNT->update('payment_xipay', [
        'notication'    => 1
    ], " `id` = ?", [$row['id']]);

    die(json_encode([
        'status' => 'success',
        'msg' => __('Deposit successful') . ' ' . format_currency($row['price'])
    ]));
}
if ($_POST['action'] == 'notication_topup_thesieure') {
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

    if (!$row = $CMSNT->get_row_safe("SELECT * FROM `payment_thesieure` WHERE `notication` = 0 AND `user_id` = ?", [$getUser['id']])) {
        die(json_encode(['status' => 'error', 'msg' => __('Không có lịch sử nạp tiền gần đây')]));
    }

    $CMSNT->update('payment_thesieure', [
        'notication'    => 1
    ], " `id` = ?", [$row['id']]);

    die(json_encode([
        'status' => 'success',
        'msg' => __('Nạp tiền thành công') . ' ' . format_currency($row['received'])
    ]));
}


if ($_POST['action'] == 'notication_topup_korapay') {
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
    require_once(__DIR__ . "/../../libs/korapay.php");
    require_once(__DIR__ . "/../../libs/database/users.php");
    $user = new users;

    foreach ($CMSNT->get_list_safe("SELECT * FROM `payment_korapay` WHERE `status` = 0 AND `user_id` = ? ORDER BY `id` DESC LIMIT 3", [$getUser['id']]) as $row) {
        $secretKey = $CMSNT->site('korapay_secretKey');
        $reference = $row['trans_id'];
        $verification = korapayVerifyCharge($secretKey, $reference);
        if ($verification || isset($verification['status']) || $verification['status'] !== false) {
            if ($verification['data']['status'] == 'success') {
                $isCong = $user->AddCredits($row['user_id'], $row['price'], __('Recharge Korapay') . ' #' . $reference, 'TOPUP_korapay_' . $reference);
                if ($isCong) {
                    $CMSNT->update('payment_korapay', [
                        'status'            => 1,
                        'notication'        => 1,
                        'updated_at'        => gettime()
                    ], " `id` = ?", [$row['id']]);
                    // TẠO LOG GIAO DỊCH GẦN ĐÂY
                    $CMSNT->insert('deposit_log', [
                        'user_id'       => $row['user_id'],
                        'method'        => __('Korapay Africa'),
                        'amount'        => $amount,
                        'received'      => $row['price'],
                        'create_time'   => time(),
                        'is_virtual'    => 0
                    ]);
                    /** SEND NOTI CHO ADMIN */
                    $my_text = $CMSNT->site('noti_recharge');
                    $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
                    $my_text = str_replace('{username}', getRowRealtime('users', $row['user_id'], 'username'), $my_text);
                    $my_text = str_replace('{method}', __('Recharge Korapay'), $my_text);
                    $my_text = str_replace('{amount}', $amount, $my_text);
                    $my_text = str_replace('{price}', format_currency($row['price']), $my_text);
                    $my_text = str_replace('{time}', gettime(), $my_text);
                    sendMessAdmin($my_text);
                    die(json_encode([
                        'status' => 'success',
                        'msg' => __('Deposit successful') . ' ' . format_currency($row['price'])
                    ]));
                }
            }
            if ($verification['data']['status'] == 'failed' || $verification['data']['status'] == 'expired') {
                $CMSNT->update('payment_korapay', [
                    'status'            => 2,
                    'updated_at'        => gettime()
                ], " `id` = ?", [$row['id']]);
            }
        }
    }
}

if ($_POST['action'] == 'notication_topup_pocketfi') {
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
    require_once(__DIR__ . "/../../libs/pocketfi.php");
    require_once(__DIR__ . "/../../libs/database/users.php");
    $user = new users;

    foreach ($CMSNT->get_list_safe("SELECT * FROM `payment_pocketfi` WHERE `status` = 0 AND `user_id` = ? ORDER BY `id` DESC LIMIT 3", [$getUser['id']]) as $row) {
        $apiToken = $CMSNT->site('pocketfi_api_token');
        $paymentId = $row['payment_id'];
        $verification = pocketfiVerifyCharge($apiToken, $paymentId);

        if ($verification && isset($verification['status'])) {
            if ($verification['status'] == 'success' || $verification['status'] == 'completed') {
                $isCong = $user->AddCredits($row['user_id'], $row['price'], __('Recharge PocketFi') . ' #' . $row['trans_id'], 'TOPUP_pocketfi_' . $row['trans_id']);
                if ($isCong) {
                    $CMSNT->update('payment_pocketfi', [
                        'status'            => 1,
                        'notication'        => 1,
                        'updated_at'        => gettime()
                    ], " `id` = ?", [$row['id']]);
                    // TẠO LOG GIAO DỊCH GẦN ĐÂY
                    $CMSNT->insert('deposit_log', [
                        'user_id'       => $row['user_id'],
                        'method'        => __('PocketFi'),
                        'amount'        => $row['price'],
                        'received'      => $row['price'],
                        'create_time'   => time(),
                        'is_virtual'    => 0
                    ]);
                    /** SEND NOTI CHO ADMIN */
                    $my_text = $CMSNT->site('noti_recharge');
                    $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
                    $my_text = str_replace('{username}', getRowRealtime('users', $row['user_id'], 'username'), $my_text);
                    $my_text = str_replace('{method}', __('Recharge PocketFi'), $my_text);
                    $my_text = str_replace('{amount}', $row['amount'], $my_text);
                    $my_text = str_replace('{price}', format_currency($row['price']), $my_text);
                    $my_text = str_replace('{time}', gettime(), $my_text);
                    sendMessAdmin($my_text);
                    die(json_encode([
                        'status' => 'success',
                        'msg' => __('Deposit successful') . ' ' . format_currency($row['price'])
                    ]));
                }
            }
            if ($verification['status'] == 'failed' || $verification['status'] == 'expired' || $verification['status'] == 'cancelled') {
                $CMSNT->update('payment_pocketfi', [
                    'status'            => 2,
                    'updated_at'        => gettime()
                ], " `id` = ?", [$row['id']]);
            }
        }
    }
}

if ($_POST['action'] == 'notication_topup_toyyibpay') {
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

    if (!$row = $CMSNT->get_row_safe("SELECT * FROM `payment_toyyibpay` WHERE `notication` = 0 AND `user_id` = ? AND `status` = 1", [$getUser['id']])) {
        die(json_encode(['status' => 'error', 'msg' => __('Không có lịch sử nạp tiền gần đây')]));
    }

    $CMSNT->update('payment_toyyibpay', [
        'notication'    => 1
    ], " `id` = ?", [$row['id']]);

    die(json_encode([
        'status' => 'success',
        'msg' => __('Nạp tiền thành công') . ' RM ' . format_cash($row['amount'])
    ]));
}

if ($_POST['action'] == 'notication_topup_bakong') {
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
    require_once(__DIR__ . "/../../libs/bakong.php");
    require_once(__DIR__ . "/../../libs/database/users.php");
    $user = new users;

    foreach (
        $CMSNT->get_list_safe("
        SELECT * FROM `payment_bakong` 
        WHERE `user_id` = ? 
        AND `status` = 0 
        ORDER BY `id` DESC 
        LIMIT 3
    ", [$getUser['id']]) as $payment_bakong
    ) {
        $response = verifyPaymentBakong($payment_bakong['trans_id'], $payment_bakong['amount']);
        if ($response['status'] == true) {
            // Cộng tiền cho user
            $isCong = $user->AddCredits(
                $payment_bakong['user_id'],
                $payment_bakong['price'],
                __('Recharge Bakong Wallet Cambodia') . ' #' . $payment_bakong['trans_id'],
                'TOPUP_Bakong_' . $payment_bakong['trans_id']
            );

            if ($isCong) {
                // Cập nhật status = 1 (đã thanh toán)
                $CMSNT->update('payment_bakong', [
                    'status'     => 1,
                    'updated_at' => gettime(),
                    'notication' => 1
                ], " `id` = ?", [$payment_bakong['id']]);

                // Tạo log nạp
                $CMSNT->insert('deposit_log', [
                    'user_id'       => $payment_bakong['user_id'],
                    'method'        => __('Bakong Wallet Cambodia'),
                    'amount'        => $payment_bakong['amount'],
                    'received'      => $payment_bakong['price'],
                    'create_time'   => time(),
                    'is_virtual'    => 0
                ]);

                // Gửi thông báo admin (nếu có hàm sendMessAdmin)
                $my_text = $CMSNT->site('noti_recharge');
                $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
                $my_text = str_replace('{username}', getRowRealtime('users', $payment_bakong['user_id'], 'username'), $my_text);
                $my_text = str_replace('{method}', __('Bakong Wallet Cambodia'), $my_text);
                $my_text = str_replace('{amount}', $payment_bakong['amount'], $my_text);
                $my_text = str_replace('{price}', format_currency($payment_bakong['price']), $my_text);
                $my_text = str_replace('{time}', gettime(), $my_text);
                sendMessAdmin($my_text);

                die(json_encode([
                    'status' => 'success',
                    'msg'    => __('Deposit successful') . ' ' . format_currency($payment_bakong['price'])
                ]));
            } else {
                die(json_encode([
                    'status' => 'success',
                    'msg' => __('Không thể cộng số dư')
                ]));
            }
        }
    }
}
if ($_POST['action'] == 'notication_topup_tmweasyapi') {
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
    require_once(__DIR__ . "/../../libs/tmweasyapi.php");
    require_once(__DIR__ . "/../../libs/database/users.php");
    require_once(__DIR__ . "/../../libs/database/users.php");
    $user = new users;

    foreach (
        $CMSNT->get_list_safe("
        SELECT * FROM `payment_tmweasyapi` 
        WHERE `user_id` = ? 
        AND `status` = 0 
        ORDER BY `id` DESC 
        LIMIT 3
    ", [$getUser['id']]) as $payment_tmweasyapi
    ) {

        // Gọi confirm
        $paramsConfirm = [
            "username" => $CMSNT->site('tmweasyapi_username'),
            "password" => $CMSNT->site('tmweasyapi_password'),
            "con_id"   => $CMSNT->site('tmweasyapi_con_id'),
            "id_pay"   => $payment_tmweasyapi['id_pay'],
            "ip"       => myip(),
            "method"   => "confirm"
        ];
        $result = callMaemaneeApi($paramsConfirm);

        if ($result === false) {
            die(json_encode([
                'status' => 'error',
                'msg' => __('Không thể kết nối API confirm')
            ]));
        }

        // TH1: Thanh toán thành công
        if (!empty($result['status']) && $result['status'] == 1) {
            $ref1   = $result['ref1']   ?? '';
            $amount = check_string($result['amount']) ?? 0;

            // Cộng tiền cho user
            $isCong = $user->AddCredits(
                $payment_tmweasyapi['user_id'],
                $payment_tmweasyapi['price'],
                __('Recharge Tmweasyapi Thailand') . ' #' . $payment_tmweasyapi['trans_id'],
                'TOPUP_Tmweasyapi_' . $payment_tmweasyapi['trans_id']
            );

            if ($isCong) {
                // Cập nhật status = 1 (đã thanh toán)
                $CMSNT->update('payment_tmweasyapi', [
                    'status'     => 1,
                    'updated_at' => gettime(),
                    'notication' => 1
                ], " `id` = ?", [$payment_tmweasyapi['id']]);

                // Tạo log nạp
                $CMSNT->insert('deposit_log', [
                    'user_id'       => $payment_tmweasyapi['user_id'],
                    'method'        => __('Tmweasyapi Thailand'),
                    'amount'        => $amount,
                    'received'      => $payment_tmweasyapi['price'],
                    'create_time'   => time(),
                    'is_virtual'    => 0
                ]);

                // Gửi thông báo admin (nếu có hàm sendMessAdmin)
                $my_text = $CMSNT->site('noti_recharge');
                $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
                $my_text = str_replace('{username}', getRowRealtime('users', $payment_tmweasyapi['user_id'], 'username'), $my_text);
                $my_text = str_replace('{method}', __('Tmweasyapi Thailand'), $my_text);
                $my_text = str_replace('{amount}', $amount, $my_text);
                $my_text = str_replace('{price}', format_currency($payment_tmweasyapi['price']), $my_text);
                $my_text = str_replace('{time}', gettime(), $my_text);
                sendMessAdmin($my_text);

                die(json_encode([
                    'status' => 'success',
                    'msg'    => __('Deposit successful') . ' ' . format_currency($payment_tmweasyapi['price'])
                ]));
            }
        } else {
            // TH2: Chưa thanh toán hoặc lỗi
            // -> Kiểm tra xem hóa đơn đã quá 24 giờ chưa
            // Giả sử cột created_at là datetime
            $timeCreated = strtotime($payment_tmweasyapi['created_at']);
            $timeNow     = time();
            // Nếu > 24 giờ, gọi API cancel
            if (($timeNow - $timeCreated) >= 86400) {
                $CMSNT->update('payment_tmweasyapi', [
                    'status'     => 2, // bạn có thể đặt = 2 để đánh dấu "đã hủy"
                    'updated_at' => gettime()
                ], " `id` = ?", [$payment_tmweasyapi['id']]);
            }
        }
    }
}


if ($_POST['action'] == 'notication_topup_openpix') {
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

    if (!$row = $CMSNT->get_row_safe("SELECT * FROM `payment_openpix` WHERE `notication` = 0 AND `user_id` = ? AND `status` = 1", [$getUser['id']])) {
        die(json_encode(['status' => 'error', 'msg' => __('Không có lịch sử nạp tiền gần đây')]));
    }

    $CMSNT->update('payment_openpix', [
        'notication'    => 1
    ], " `id` = ?", [$row['id']]);

    die(json_encode([
        'status' => 'success',
        'msg' => __('Deposit successful') . ' ' . format_currency($row['price'])
    ]));
}

// LEMPAY NOTIFICATION HANDLER
if ($_POST['action'] == 'notication_topup_lempay') {
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

    if (!$row = $CMSNT->get_row_safe("SELECT * FROM `payment_lempay` WHERE `notication` = 0 AND `user_id` = ? AND `status` = 1", [$getUser['id']])) {
        die(json_encode(['status' => 'error', 'msg' => __('Không có lịch sử nạp tiền gần đây')]));
    }

    $CMSNT->update('payment_lempay', [
        'notication'    => 1
    ], " `id` = ?", [$row['id']]);

    die(json_encode([
        'status' => 'success',
        'msg' => __('Deposit successful') . ' ' . format_currency($row['price'])
    ]));
}

// ZINIPAY NOTIFICATION HANDLER
if ($_POST['action'] == 'notication_topup_zinipay') {
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

    if (!$row = $CMSNT->get_row_safe("SELECT * FROM `payment_zinipay` WHERE `notication` = 0 AND `user_id` = ? AND `status` = 1", [$getUser['id']])) {
        die(json_encode(['status' => 'error', 'msg' => __('Không có lịch sử nạp tiền gần đây')]));
    }

    $CMSNT->update('payment_zinipay', [
        'notication'    => 1
    ], " `id` = ?", [$row['id']]);

    die(json_encode([
        'status' => 'success',
        'msg' => __('Deposit successful') . ' ' . format_currency($row['price'])
    ]));
}

// TRIPAY NOTIFICATION HANDLER
if ($_POST['action'] == 'notication_topup_tripay') {
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

    if (!$row = $CMSNT->get_row_safe("SELECT * FROM `payment_tripay` WHERE `notication` = 0 AND `user_id` = ? AND `status` = 1", [$getUser['id']])) {
        die(json_encode(['status' => 'error', 'msg' => __('Không có lịch sử nạp tiền gần đây')]));
    }

    $CMSNT->update('payment_tripay', [
        'notication'    => 1
    ], " `id` = ?", [$row['id']]);

    die(json_encode([
        'status' => 'success',
        'msg' => __('Deposit successful') . ' ' . format_currency($row['price'])
    ]));
}

// DSOCIOPAY NOTIFICATION HANDLER
if ($_POST['action'] == 'notication_topup_dsociopay') {
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

    if (!$row = $CMSNT->get_row_safe("SELECT * FROM `payment_dsociopay` WHERE `notication` = 0 AND `user_id` = ? AND `status` = 1", [$getUser['id']])) {
        die(json_encode(['status' => 'error', 'msg' => __('Không có lịch sử nạp tiền gần đây')]));
    }

    $CMSNT->update('payment_dsociopay', [
        'notication'    => 1
    ], " `id` = ?", [$row['id']]);

    die(json_encode([
        'status' => 'success',
        'msg' => __('Deposit successful') . ' ' . format_currency($row['price'])
    ]));
}

if ($_POST['action'] == 'CalculateCryptoReceived') {
    // Validate amount
    if (empty($_POST['amount'])) {
        die(json_encode(['status' => 'error', 'received' => '0']));
    }

    $amount = validate_float($_POST['amount'], 0);
    if ($amount === false || $amount <= 0) {
        die(json_encode(['status' => 'error', 'received' => '0']));
    }

    // Lấy tỷ giá
    $crypto_rate = floatval($CMSNT->site('crypto_rate'));
    if ($crypto_rate <= 0) {
        die(json_encode(['status' => 'error', 'received' => '0']));
    }

    // Tính toán số tiền thực nhận (bao gồm khuyến mãi)
    // Bước 1: Tính số tiền USDT sau khi cộng khuyến mãi
    $received = calculateCryptoReceivedAmount($amount, $CMSNT->site('crypto_promotions'));
    // Bước 2: Nhân với tỷ giá để ra số tiền VND
    $received = $received * $crypto_rate;

    // Format số tiền bằng hàm format_currency
    $received_formatted = format_currency($received);

    die(json_encode([
        'status' => 'success',
        'received' => $received_formatted,
        'rate' => format_currency($crypto_rate)
    ]));
}

// Tính số tiền thực nhận ZiniPay theo tiền tệ user đang chọn
if ($_POST['action'] == 'CalculateZinipayReceived') {
    if (empty($_POST['amount'])) {
        die(json_encode(['status' => 'error', 'received' => '0']));
    }

    $amount = validate_float($_POST['amount'], 0);
    if ($amount === false || $amount <= 0) {
        die(json_encode(['status' => 'error', 'received' => '0']));
    }

    $zinipay_rate = floatval($CMSNT->site('zinipay_rate'));
    if ($zinipay_rate <= 0) {
        $zinipay_rate = 1;
    }

    // received tính theo tiền cơ sở; format_currency tự quy đổi theo cookie currency
    $received = $amount * $zinipay_rate;

    die(json_encode([
        'status'   => 'success',
        'received' => format_currency($received)
    ]));
}

// XUẤT TÀI KHOẢN ĐƠN HÀNG
if ($_POST['action'] == 'exportOrderAccounts') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }

    // Validate token
    $token = validate_alphanumeric($_POST['token'], 255);
    if ($token === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }

    if (!$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }

    // Validate trans_id
    if (empty($_POST['trans_id'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Đơn hàng không hợp lệ')]));
    }
    $trans_id = validate_alphanumeric($_POST['trans_id'], 50);
    if ($trans_id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Đơn hàng không hợp lệ')]));
    }

    // Kiểm tra đơn hàng thuộc về user
    if (!$order = $CMSNT->get_row_safe("SELECT * FROM `product_order` WHERE `trans_id` = ? AND `buyer` = ? AND `trash` = 0", [$trans_id, $getUser['id']])) {
        die(json_encode(['status' => 'error', 'msg' => __('Đơn hàng không tồn tại trong hệ thống')]));
    }

    // Kiểm tra bảo mật IP
    if ($order['status_view_order'] == 1 || $CMSNT->site('isPurchaseIpVerified') == 1) {
        if ($order['ip'] != myip()) {
            die(json_encode(['status' => 'error', 'msg' => __('Địa chỉ IP của bạn không khớp với địa chỉ IP bạn dùng để mua hàng')]));
        }
    }
    // Kiểm tra bảo mật device
    if ($order['status_view_order'] == 1 || $CMSNT->site('isPurchaseDeviceVerified') == 1) {
        if ($order['device'] != getUserAgent()) {
            die(json_encode(['status' => 'error', 'msg' => __('Trình duyệt của bạn không khớp với trình duyệt lúc bạn mua hàng')]));
        }
    }

    // Validate file_type
    $file_type = isset($_POST['file_type']) && in_array($_POST['file_type'], ['txt', 'csv']) ? $_POST['file_type'] : 'txt';

    // Validate columns - Secure Column Mapping
    $allowed_columns = [
        'uid'            => ['field' => 'uid',            'label' => 'UID'],
        'account'        => ['field' => 'account',        'label' => __('Tài khoản')],
        'create_gettime' => ['field' => 'create_gettime', 'label' => __('Thời gian')],
        'product_name'   => ['field' => 'product_name',   'label' => __('Tên sản phẩm')]
    ];

    $columns = [];
    if (!empty($_POST['columns']) && is_array($_POST['columns'])) {
        foreach ($_POST['columns'] as $col) {
            $col = validate_string($col, 50);
            if ($col !== false && isset($allowed_columns[$col])) {
                $columns[] = $col;
            }
        }
    }

    if (empty($columns)) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng chọn ít nhất một cột để xuất')]));
    }

    // Lấy toàn bộ tài khoản trong đơn hàng
    $accounts = $CMSNT->get_list_safe(
        "SELECT `uid`, `account`, `create_gettime` FROM `product_sold` WHERE `trans_id` = ? AND `buyer` = ? ORDER BY `id` DESC",
        [$trans_id, $getUser['id']]
    );

    // Xây dựng nội dung export
    $is_csv = ($file_type === 'csv');
    $separator = $is_csv ? ',' : "\t";
    $line_ending = $is_csv ? "\r\n" : PHP_EOL; // CSV dùng CRLF theo chuẩn RFC 4180
    $lines = [];

    // Header row
    $header_parts = [];
    foreach ($columns as $col) {
        $label = $allowed_columns[$col]['label'];
        if ($is_csv) {
            $label = '"' . str_replace('"', '""', $label) . '"';
        }
        $header_parts[] = $label;
    }
    $lines[] = implode($separator, $header_parts);

    // Data rows
    foreach ($accounts as $acc) {
        $row_parts = [];
        foreach ($columns as $col) {
            $value = '';
            if ($col === 'product_name') {
                $value = $order['product_name'];
            } else {
                $value = isset($acc[$allowed_columns[$col]['field']]) ? $acc[$allowed_columns[$col]['field']] : '';
            }
            // Cleansing: flatten multiline content
            $value = str_replace(["\r\n", "\r", "\n"], ' | ', $value);
            // Nếu CSV, escape dấu phẩy và dấu ngoặc kép
            if ($is_csv) {
                $value = '"' . str_replace('"', '""', $value) . '"';
            }
            $row_parts[] = $value;
        }
        $lines[] = implode($separator, $row_parts);
    }

    // Thêm nội dung text_txt ở đầu file (chỉ cho TXT, CSV bỏ qua vì text_txt là free-form text sẽ phá vỡ cấu trúc bảng)
    $content = '';
    if (!$is_csv) {
        $product_text = getRowRealtime('products', $order['product_id'], 'text_txt');
        if ($product_text) {
            $content = fix_order_encoding($product_text) . $line_ending . $line_ending;
        }
    }
    $content .= implode($line_ending, $lines);

    $filename = $trans_id . '_' . count($accounts) . '.' . $file_type;

    // Log
    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => __('Xuất tài khoản đơn hàng') . ' (' . $order['trans_id'] . ') - ' . strtoupper($file_type) . ' - ' . count($accounts) . ' ' . __('tài khoản')
    ]);

    die(json_encode([
        'status' => 'success',
        'msg'    => sprintf(__('Đã xuất %d tài khoản thành công'), count($accounts)),
        'data'   => [
            'content'  => $content,
            'filename' => $filename
        ]
    ]));
}

die(json_encode([
    'status'    => 'error',
    'msg'       => __('Invalid data')
]));
