<?php

define("IN_SITE", true);
require_once(__DIR__ . "/../../config.php");
require_once(__DIR__ . "/../../libs/db.php");
require_once(__DIR__ . "/../../libs/lang.php");
require_once(__DIR__ . "/../../libs/helper.php");
require_once(__DIR__ . '/../../libs/database/users.php');
require_once(__DIR__ . "/../../libs/toyyibpay.php");




if ($CMSNT->site('status') != 1) {
    $data = json_encode([
        'status'    => 'error',
        'msg'       => __('Hệ thống đang bảo trì!')
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
if ($CMSNT->site('status_demo') != 0) {
    die(json_encode(['status' => 'error', 'msg' => __('Chức năng này không thể sử dụng trên website demo')]));
}
if ($_POST['action'] == 'WithdrawCommission') {
    if ($CMSNT->site('status_demo') != 0) {
        die(json_encode(['status' => 'error', 'msg' => __('This function cannot be used because this is a demo site')]));
    }
    if ($CMSNT->site('affiliate_status') != 1) {
        die(json_encode(['status' => 'error', 'msg' => __('Chức năng này đang được bảo trì')]));
    }
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập')]));
    }

    $token = validate_alphanumeric($_POST['token'], 255);
    if ($token === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Token không hợp lệ!')]));
    }

    if (!$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập')]));
    }
    if (time() - $getUser['time_request'] < $config['max_time_load']) {
        die(json_encode(['status' => 'error', 'msg' => __('Thao tác quá nhanh, vui lòng chờ')]));
    }
    $bank = validate_string($_POST['bank'], 100);
    if ($bank === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Tên ngân hàng không hợp lệ!')]));
    }

    $stk = validate_string($_POST['stk'], 50);
    if ($stk === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Số tài khoản không hợp lệ!')]));
    }

    $name = validate_string($_POST['name'], 100);
    if ($name === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Tên chủ tài khoản không hợp lệ!')]));
    }

    $amount = validate_float($_POST['amount'], 0.01);
    if ($amount === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Số tiền không hợp lệ!')]));
    }
    if ($amount < $CMSNT->site('affiliate_min')) {
        die(json_encode(['status' => 'error', 'msg' => __('Số tiền rút tối thiểu phải là') . ' ' . format_currency($CMSNT->site('affiliate_min'))]));
    }
    if ($getUser['ref_price'] < $amount) {
        die(json_encode(['status' => 'error', 'msg' => __('Số dư hoa hồng khả dụng của bạn không đủ')]));
    }
    $trans_id = random('123456789QWERTYUIOPASDFGHJKLZXCVBNM', 6);

    $User = new users();
    $isTru = $User->RemoveCommission($getUser['id'], $amount, __('Withdraw commission balance') . ' #' . $trans_id);
    if ($isTru) {
        if (getRowRealtime('users', $getUser['id'], 'ref_price') < 0) {
            $User->Banned($getUser['id'], __('Gian lận khi rút số dư hoa hồng'));
            die(json_encode(['status' => 'error', 'msg' => __('Tài khoản của bạn đã bị khóa vì gian lận')]));
        }
        $isInsert = $CMSNT->insert('aff_withdraw', [
            'trans_id'  => $trans_id,
            'user_id'   => $getUser['id'],
            'bank'      => $bank,
            'stk'       => $stk,
            'name'      => $name,
            'amount'    => $amount,
            'status'    => 'pending',
            'create_gettime'    => gettime(),
            'update_gettime'    => gettime(),
            'reason'    => NULL
        ]);
        if ($isInsert) {
            /** NOTE ACTION */
            $my_text = $CMSNT->site('noti_affiliate_withdraw');
            $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
            $my_text = str_replace('{username}', $getUser['username'], $my_text);
            $my_text = str_replace('{bank}', $bank, $my_text);
            $my_text = str_replace('{account_number}', $stk, $my_text);
            $my_text = str_replace('{account_name}', $name, $my_text);
            $my_text = str_replace('{amount}', format_currency($amount), $my_text);
            $my_text = str_replace('{ip}', myip(), $my_text);
            $my_text = str_replace('{time}', gettime(), $my_text);
            sendMessTelegram($my_text, '', $CMSNT->site('affiliate_chat_id_telegram'));

            die(json_encode(['status' => 'success', 'msg' => __('Yêu cầu rút tiền được tạo thành công, vui lòng đợi ADMIN xử lý')]));
        }
        die(json_encode(['status' => 'error', 'msg' => 'ERROR 1 - ' . __('System error')]));
    } else {
        die(json_encode(['status' => 'error', 'msg' => 'ERROR 2 - ' . __('System error')]));
    }
}
if ($_POST['action'] == 'nap_the') {
    if ($CMSNT->site('card_status') != 1) {
        die(json_encode(['status' => 'error', 'msg' => __('Chức năng nạp thẻ đang được tắt')]));
    }
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập')]));
    }

    $token = validate_alphanumeric($_POST['token'], 255);
    if ($token === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Token không hợp lệ!')]));
    }

    if (!$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập')]));
    }
    if (time() - $getUser['time_request'] < $config['max_time_load']) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn đang thao tác quá nhanh, vui lòng chờ')]));
    }
    $telco = validate_string($_POST['telco'], 50);
    if ($telco === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Nhà mạng không hợp lệ!')]));
    }

    $amount = validate_int($_POST['amount'], 1);
    if ($amount === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Mệnh giá không hợp lệ!')]));
    }

    $serial = validate_string($_POST['serial'], 50);
    if ($serial === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Serial thẻ không hợp lệ!')]));
    }

    $pin = validate_string($_POST['pin'], 50);
    if ($pin === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Mã thẻ không hợp lệ!')]));
    }
    // Lấy danh sách loại thẻ cho phép từ cấu hình
    $list_network_topup_card = $CMSNT->site('list_network_topup_card');
    $cards = explode("\n", $list_network_topup_card);
    $allowed_cards = [];
    foreach ($cards as $card) {
        $card = trim($card);
        if (!$card) {
            continue;
        }
        $arr = explode('|', $card);
        if (count($arr) == 2) {
            $allowed_cards[] = $arr[0];
        }
    }
    // Nếu loại thẻ không nằm trong danh sách cho phép thì dừng xử lý
    if (!in_array($telco, $allowed_cards)) {
        die(json_encode(['status' => 'error', 'msg' => __('Loại thẻ không được hỗ trợ')]));
    }


    $checkResult = checkFormatCard($telco, $serial, $pin);
    if ($checkResult['status'] !== true) {
        die(json_encode(['status' => 'error', 'msg' => $checkResult['msg']]));
    }
    if ($CMSNT->num_rows_safe("SELECT * FROM `cards` WHERE `user_id` = ? AND `status` = 'pending'", [$getUser['id']]) > 5) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng không spam!')]));
    }
    if (
        $CMSNT->num_rows_safe("SELECT * FROM `cards` WHERE `status` = 'error' AND `user_id` = ? AND `create_date` >= DATE(NOW()) AND `create_date` < DATE(NOW()) + INTERVAL 1 DAY", [$getUser['id']]) -
        $CMSNT->num_rows_safe("SELECT * FROM `cards` WHERE `status` = 'complted' AND `user_id` = ? AND `create_date` >= DATE(NOW()) AND `create_date` < DATE(NOW()) + INTERVAL 1 DAY", [$getUser['id']]) >= 5
    ) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn đã bị chặn sử dụng chức năng nạp thẻ trong 1 ngày')]));
    }
    $trans_id = random('QWERTYUIOPASDFGHJKLZXCVBNM', 6) . time();
    $data = card24h($telco, $amount, $serial, $pin, $trans_id);
    // Kiểm tra API card24h có trả về kết quả hợp lệ không (có thể null khi timeout/lỗi mạng)
    if (!is_array($data) || !isset($data['status'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Không thể kết nối đến hệ thống nạp thẻ, vui lòng thử lại sau')]));
    }
    if ($data['status'] == 99) {
        $isInsert = $CMSNT->insert("cards", array(
            'trans_id'  => $trans_id,
            'telco'     => $telco,
            'amount'    => $amount,
            'serial'    => $serial,
            'pin'       => $pin,
            'price'     => 0,
            'user_id'   => $getUser['id'],
            'status'    => 'pending',
            'reason'    => '',
            'create_date'    => gettime(),
            'update_date'    => gettime()
        ));
        if ($isInsert) {
            // Cập nhật thời gian request chống spam
            $CMSNT->update("users", [
                'time_request' => time()
            ], " `id` = ?", [$getUser['id']]);
            $CMSNT->insert("logs", [
                'user_id'       => $getUser['id'],
                'ip'            => myip(),
                'device'        => getUserAgent(),
                'createdate'    => gettime(),
                'action'        => "Thực hiện nạp thẻ Serial: $serial - Pin: $pin"
            ]);
            // Rate limit
            checkBlockIP('PAYMENT', 5);
            die(json_encode(['status' => 'success', 'msg' => __('Đẩy thẻ lên thành công, vui lòng chờ xử lý thẻ trong giây lát!')]));
        } else {
            die(json_encode(['status' => 'error', 'msg' => __('Nạp thẻ thất bại, vui lòng liên hệ Admin')]));
        }
    } else {
        // Lấy thông báo lỗi từ API, fallback nếu không có
        $errorMsg = isset($data['data']['msg']) ? $data['data']['msg'] : __('Nạp thẻ thất bại, vui lòng thử lại');
        die(json_encode(['status' => 'error', 'msg' => $errorMsg]));
    }
}



if ($_POST['action'] == 'RechargeCryptoNew') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Please log in')]));
    }

    $token = validate_alphanumeric($_POST['token'], 255);
    if ($token === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Token không hợp lệ!')]));
    }

    if (!$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Please log in')]));
    }

    $amount = validate_float($_POST['amount'], 0.01);
    if ($amount === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Số tiền không hợp lệ!')]));
    }
    if ($amount < $CMSNT->site('crypto_min')) {
        die(json_encode(['status' => 'error', 'msg' => __('The minimum deposit amount is:') . ' $' . $CMSNT->site('crypto_min')]));
    }
    if ($amount > $CMSNT->site('crypto_max')) {
        die(json_encode(['status' => 'error', 'msg' => __('The maximum deposit amount is:') . ' $' . format_cash($CMSNT->site('crypto_max'))]));
    }
    if ($CMSNT->site('crypto_status') != 1) {
        die(json_encode(['status' => 'error', 'msg' => __('This function is under maintenance')]));
    }
    if ($CMSNT->site('crypto_merchant_id') == '' || $CMSNT->site('crypto_api_key') == '') {
        die(json_encode(['status' => 'error', 'msg' => __('Chức năng này chưa được cấu hình, vui lòng liên hệ Admin')]));
    }
    if ($CMSNT->num_rows_safe("SELECT * FROM `payment_crypto` WHERE `user_id` = ? AND `status` = 'waiting' AND ROUND(`amount`) = ?", [$getUser['id'], $amount]) >= 3) {
        die(json_encode(['status' => 'error', 'msg' => __('Please do not SPAM')]));
    }
    $request_id = md5(time() . random('qwertyuiopasdfghjklzxcvbnm0123456789', 5));

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://app.fpayment.net/api/AddInvoice",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array(
            'merchant_id'     => $CMSNT->site('crypto_merchant_id'),                          // Không được để lộ thông tin này
            'api_key'         => $CMSNT->site('crypto_api_key'),                 // Không được để lộ thông tin này
            'name'            => 'Recharge ' . validate_string($_SERVER['HTTP_HOST'], 100),                         // Tên hóa đơn ví dụ như: Nạp tiền vào website abc.xyz
            'description'     => 'Recharge invoice to ' . $getUser['username'],                  // Mô tả hóa đơn ví dụ như: Username hoặc Email của user tạo hóa đơn
            'amount'          => $amount,                                   // Số tiền user muốn nạp vào hệ thống bạn
            'request_id'      => $request_id,                // Mã giao dịch bí mật của hệ thống bạn dùng để so sánh giao dịch của user nào
            'callback_url'    => base_url('api/callback_crypto_new.php'),     // Liên kết dùng nhận kết quả giao dịch
            'success_url'     => base_url('client/recharge-crypto'),      // Liên kết khi người dùng nhấn nút Return to Website khi hóa đơn xử lý thành công
            'cancel_url'      => base_url('client/recharge-crypto')        // Liên kết khi người dùng nhấn nút Return to Website khi hóa đơn hết hạn
        ),
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    $result = json_decode($response, true);
    if (!isset($result['status'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng liên hệ Admin FPAYMENT khắc phục vấn đề này nếu bạn là Admin website.')]));
    }
    // Xử lý kết quả cURL sau đó lưu vào Database để đem ra xử lý khi có callback
    if ($result['status'] == 'success') {
        // Tạo hóa đơn thành công, lưu thông tin hóa đơn vào Database
        $trans_id       = validate_alphanumeric($result['data']['trans_id'], 128);      // Mã giao dịch FPAYMENT trả về
        $amount         = validate_float($result['data']['amount'], 0.01);        // Số tiền mà user cần phải chuyển đúng chính xác
        $status         = validate_string($result['data']['status'], 20);        // Trạng thái hóa đơn sau khi tạo là waiting
        $url_payment    = validate_url($result['data']['url_payment']);   // Liên kết trang thanh toán, chuyển hướng đến link này để cho user tiến hành thanh toán

        // Đoạn code lưu thông tin hóa đơn vào database
        $received = calculateCryptoReceivedAmount($amount, $CMSNT->site('crypto_promotions'));
        $received = $received * $CMSNT->site('crypto_rate');
        $isInsert = $CMSNT->insert('payment_crypto', [
            'trans_id'          => $trans_id,
            'user_id'           => $getUser['id'],
            'request_id'        => $request_id,
            'amount'            => $amount,
            'received'          => $received,
            'exchange_rate'     => $CMSNT->site('crypto_rate'), // Lưu tỷ giá USDT tại thời điểm tạo hóa đơn
            'create_gettime'    => gettime(),
            'update_gettime'    => gettime(),
            'status'            => $status,
            'url_payment'       => $url_payment,
            'msg'               => NULL
        ]);
        if ($isInsert) {
            $CMSNT->insert("logs", [
                'user_id'       => $getUser['id'],
                'ip'            => myip(),
                'device'        => getUserAgent(),
                'createdate'    => gettime(),
                'action'        => __('Tạo hóa đơn nạp tiền điện tử') . ' #' . $trans_id
            ]);
            // Rate limit
            checkBlockIP('PAYMENT', 5);
            die(json_encode([
                'url'       => $url_payment,
                'status'    => 'success',
                'msg'       => __('Tạo hóa đơn nạp tiền thành công')
            ]));
        } else {
            die(json_encode([
                'status'    => 'error',
                'msg'       => __('Tạo hóa đơn nạp tiền thất bại')
            ]));
        }
    } else {
        die(json_encode(['status' => 'error', 'msg' => __($result['msg'])]));
    }
}

if ($_POST['action'] == 'CreateToyyibpay') {
    if ($CMSNT->site('status_demo') != 0) {
        die(json_encode(['status' => 'error', 'msg' => __('You cannot use this function because this is a demo site')]));
    }
    if ($CMSNT->site('status') != 1 && isSecureCookie('admin_login') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('The system is maintenance')]));
    }
    if ($CMSNT->site('toyyibpay_status') != 1) {
        die(json_encode(['status' => 'error', 'msg' => __('This function is under maintenance')]));
    }
    if ($CMSNT->site('toyyibpay_userSecretKey') == '') {
        die(json_encode(['status' => 'error', 'msg' => __('This function has not been configured')]));
    }
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Please log in')]));
    }

    $token = validate_alphanumeric($_POST['token'], 255);
    if ($token === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Token không hợp lệ!')]));
    }

    if (!$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Please log in')]));
    }
    if (time() - $getUser['time_request'] < $config['max_time_load']) {
        die(json_encode(['status' => 'error', 'msg' => __('You are working too fast, please wait')]));
    }
    $amount = validate_float($_POST['amount'], 0.01);
    if ($amount === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Số tiền không hợp lệ!')]));
    }

    if ($amount < $CMSNT->site('toyyibpay_min')) {
        die(json_encode(['status' => 'error', 'msg' => __('Minimum deposit amount is RM' . $CMSNT->site('toyyibpay_min') . '')]));
    }
    $trans_id = random('QWERTYUIOPASDFGHJKLZXCVBNM', 3) . time();

    $toyyibpay = new toyyibpay($CMSNT->site('toyyibpay_userSecretKey'));
    $result = $toyyibpay->createBill([
        'categoryCode' => $CMSNT->site('toyyibpay_categoryCode'),
        'billName' => 'Invoice - RM ' . $amount,
        'billDescription' => 'Recharge invoice on website ' . validate_string($_SERVER['HTTP_HOST'], 100),
        'billPriceSetting' => 1,
        'billPayorInfo' => 0,
        'billAmount'    => $amount * 100,
        'billReturnUrl' => base_url('client/recharge-toyyibpay'),
        'billCallbackUrl'   => base_url('api/callback_toyyibpay.php'),
        'billExternalReferenceNo' => $trans_id,
        'billTo'    =>  $getUser['username'],
        'billEmail' => !empty($getUser['email']) ? $getUser['email'] : 'None',
        'billPhone' => !empty($getUser['phone']) ? $getUser['phone'] : 0,
        'billSplitPayment' => 0,
        'billSplitPaymentArgs' => '',
        'billPaymentChannel' => 0,
        'billContentEmail' => 'Thank you for using our system',
        'billChargeToCustomer'  => $CMSNT->site('toyyibpay_billChargeToCustomer'),
        'billExpiryDate'    => '',
        'billExpiryDays'    => 3
    ]);
    $result = json_decode($result, true);
    $BillCode = $result[0]['BillCode'];

    if (!isset($BillCode)) {
        die(json_encode(['status' => 'error', 'msg' => __('Error API!')]));
    }
    $isInsert = $CMSNT->insert('payment_toyyibpay', array(
        'user_id'           => $getUser['id'],
        'trans_id'          => $trans_id,
        'billName'          => 'Invoice - RM ' . $amount,
        'amount'            => $amount,
        'status'            => 0,
        'BillCode'          => $BillCode,
        'create_gettime'       => gettime(),
        'update_gettime'       => gettime(),
        'notication'          => 0
    ));
    if ($isInsert) {
        $CMSNT->update("users", [
            'time_request' => time()
        ], " `id` = ?", [$getUser['id']]);

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Create Recharge Bank Malaysia Invoice #') . " $trans_id"
        ]);
        // Rate limit
        checkBlockIP('PAYMENT', 5);
        die(json_encode(['invoice_url'  => 'https://toyyibpay.com/' . $BillCode, 'status' => 'success', 'msg' => __('Successful!')]));
    } else {
        die(json_encode(['status' => 'error', 'msg' => __('Error!')]));
    }
}


if ($_POST['action'] == 'RechargeKorapay') {
    if ($CMSNT->site('status_demo') != 0) {
        die(json_encode(['status' => 'error', 'msg' => __('You cannot use this function because this is a demo site')]));
    }
    if ($CMSNT->site('status') != 1 && isSecureCookie('admin_login') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('The system is maintenance')]));
    }
    if ($CMSNT->site('korapay_status') != 1) {
        die(json_encode(['status' => 'error', 'msg' => __('This function is under maintenance')]));
    }
    if ($CMSNT->site('korapay_secretKey') == '') {
        die(json_encode(['status' => 'error', 'msg' => __('This function has not been configured')]));
    }
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Please log in')]));
    }

    $token = validate_alphanumeric($_POST['token'], 255);
    if ($token === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Token không hợp lệ!')]));
    }

    if (!$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Please log in')]));
    }
    if (time() - $getUser['time_request'] < $config['max_time_load']) {
        die(json_encode(['status' => 'error', 'msg' => __('You are working too fast, please wait')]));
    }
    $amount = validate_float($_POST['amount'], 0.01);
    if ($amount === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Số tiền không hợp lệ!')]));
    }

    if ($amount < $CMSNT->site('korapay_min')) {
        die(json_encode(['status' => 'error', 'msg' => __('Minimum deposit amount is ' . $CMSNT->site('korapay_min') . '')]));
    }
    if ($amount > $CMSNT->site('korapay_max')) {
        die(json_encode(['status' => 'error', 'msg' => __('Maximum deposit amount is ' . $CMSNT->site('korapay_max') . '')]));
    }
    $trans_id = random('QWERTYUIOPASDFGHJKLZXCVBNM', 3) . time();
    // Tính số tiền thực nhận: áp dụng khuyến mãi nạp tiền (nếu có) rồi nhân tỷ giá
    $received_amount = calculateCryptoReceivedAmount($amount, $CMSNT->site('korapay_promotions'));
    $price = $received_amount * $CMSNT->site('korapay_rate');

    require_once(__DIR__ . "/../../libs/korapay.php");

    // Các tham số cần thiết cho khởi tạo giao dịch
    $params = [
        "amount"      => (int)$amount,       // Đảm bảo là kiểu số
        "currency"    => $CMSNT->site('korapay_currency_code'),
        "reference"   => $trans_id,
        "redirect_url" => base_url('?action=recharge-korapay'), // URL nhận kết quả redirect sau khi thanh toán
        "notification_url" => base_url('api/callback_korapay.php'),
        "narration"        => "Deposit money into " . $getUser['username'],
        "customer"    => [
            "email" => $getUser['email']
        ],
        // Tùy chọn thêm
        // "channels"       => ["bank_transfer", "card"], v.v.
        // "metadata"       => [ "customField" => "anyValue" ],
        // "merchant_bears_cost" => true (hoặc false),
    ];

    // Gọi hàm khởi tạo giao dịch (Initialize Charge)
    $secretKey = $CMSNT->site('korapay_secretKey'); // Thay bằng secret key thực tế
    $response  = korapayInitializeCharge($secretKey, $params);

    // Kiểm tra phản hồi từ API
    if ($response && isset($response['status']) && $response['status'] === true) {
        // Lấy checkout_url từ data được trả về bởi API
        $checkoutUrl = $response['data']['checkout_url'];
        $reference = $response['data']['reference'];

        $isInsert = $CMSNT->insert('payment_korapay', array(
            'user_id'           => $getUser['id'],
            'trans_id'          => $reference,
            'price'             => $price,
            'amount'            => $amount,
            'status'            => 0,
            'created_at'        => gettime(),
            'updated_at'        => gettime(),
            'checkout_url'      => $checkoutUrl
        ));
        if ($isInsert) {
            $CMSNT->update("users", [
                'time_request' => time()
            ], " `id` = ?", [$getUser['id']]);

            $CMSNT->insert("logs", [
                'user_id'       => $getUser['id'],
                'ip'            => myip(),
                'device'        => getUserAgent(),
                'createdate'    => gettime(),
                'action'        => __('Create Korapay top-up invoice #') . " $trans_id"
            ]);
            // Rate limit
            checkBlockIP('PAYMENT', 5);
            die(json_encode(['invoice_url'  => $checkoutUrl, 'status' => 'success', 'msg' => __('Successful!')]));
        }
    } else {
        // Xử lý lỗi nếu khởi tạo không thành công
        die(json_encode(['status' => 'error', 'msg' => $response['message']]));
    }
}

if ($_POST['action'] == 'RechargePocketfi') {
    if ($CMSNT->site('status_demo') != 0) {
        die(json_encode(['status' => 'error', 'msg' => __('You cannot use this function because this is a demo site')]));
    }
    if ($CMSNT->site('status') != 1 && isSecureCookie('admin_login') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('The system is maintenance')]));
    }
    if ($CMSNT->site('pocketfi_status') != 1) {
        die(json_encode(['status' => 'error', 'msg' => __('This function is under maintenance')]));
    }
    if ($CMSNT->site('pocketfi_api_token') == '' || $CMSNT->site('pocketfi_business_id') == '') {
        die(json_encode(['status' => 'error', 'msg' => __('This function has not been configured')]));
    }
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Please log in')]));
    }

    $token = validate_alphanumeric($_POST['token'], 255);
    if ($token === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Token không hợp lệ!')]));
    }

    if (!$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Please log in')]));
    }
    if (time() - $getUser['time_request'] < $config['max_time_load']) {
        die(json_encode(['status' => 'error', 'msg' => __('You are working too fast, please wait')]));
    }
    $amount = validate_float($_POST['amount'], 0.01);
    if ($amount === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Số tiền không hợp lệ!')]));
    }

    if ($amount < $CMSNT->site('pocketfi_min')) {
        die(json_encode(['status' => 'error', 'msg' => __('Minimum deposit amount is ' . $CMSNT->site('pocketfi_min') . '')]));
    }
    if ($amount > $CMSNT->site('pocketfi_max')) {
        die(json_encode(['status' => 'error', 'msg' => __('Maximum deposit amount is ' . $CMSNT->site('pocketfi_max') . '')]));
    }
    $trans_id = random('QWERTYUIOPASDFGHJKLZXCVBNM', 3) . time();
    $price = $amount * $CMSNT->site('pocketfi_rate');

    require_once(__DIR__ . "/../../libs/pocketfi.php");

    // Xử lý phone - PocketFi yêu cầu số điện thoại Nigeria hợp lệ (11 số, bắt đầu bằng 0)
    $phone = $getUser['phone'] ? preg_replace('/[^0-9]/', '', $getUser['phone']) : '';
    if (empty($phone) || strlen($phone) < 10) {
        // Tạo số điện thoại giả định nếu không có
        $phone = '09' . str_pad(mt_rand(0, 999999999), 9, '0', STR_PAD_LEFT);
    }

    // Xử lý email - đảm bảo có email hợp lệ
    $email = $getUser['email'];
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email = $getUser['username'] . '@example.com';
    }

    // Các tham số cần thiết cho khởi tạo giao dịch PocketFi
    $params = [
        "first_name"    => $getUser['username'],
        "last_name"     => "User",
        "phone"         => $phone,
        "business_id"   => strval($CMSNT->site('pocketfi_business_id')),
        "email"         => $email,
        "redirect_link" => base_url('?action=recharge-pocketfi'),
        "amount"        => strval($amount)
    ];

    // Gọi hàm khởi tạo giao dịch PocketFi
    $apiToken = $CMSNT->site('pocketfi_api_token');
    $response = pocketfiInitializeCharge($apiToken, $params);

    // Kiểm tra phản hồi từ API
    if ($response && isset($response['status']) && $response['status'] === 'success') {
        // Lấy checkout_url từ data được trả về bởi API
        $checkoutUrl = $response['payment_link'];
        $paymentId = $response['payment_id'];

        $isInsert = $CMSNT->insert('payment_pocketfi', array(
            'user_id'           => $getUser['id'],
            'trans_id'          => $trans_id,
            'payment_id'        => $paymentId,
            'price'             => $price,
            'amount'            => $amount,
            'status'            => 0,
            'created_at'        => gettime(),
            'updated_at'        => gettime(),
            'checkout_url'      => $checkoutUrl
        ));
        if ($isInsert) {
            $CMSNT->update("users", [
                'time_request' => time()
            ], " `id` = ?", [$getUser['id']]);

            $CMSNT->insert("logs", [
                'user_id'       => $getUser['id'],
                'ip'            => myip(),
                'device'        => getUserAgent(),
                'createdate'    => gettime(),
                'action'        => __('Create PocketFi top-up invoice #') . " $trans_id"
            ]);
            // Rate limit
            checkBlockIP('PAYMENT', 5);
            die(json_encode(['invoice_url'  => $checkoutUrl, 'status' => 'success', 'msg' => __('Successful!')]));
        }
    } else {
        // Xử lý lỗi nếu khởi tạo không thành công
        $errorMsg = isset($response['message']) ? $response['message'] : __('Error creating payment');
        die(json_encode(['status' => 'error', 'msg' => $errorMsg]));
    }
}

if ($_POST['action'] == 'RechargeTmweasyapi') {
    if ($CMSNT->site('status_demo') != 0) {
        die(json_encode(['status' => 'error', 'msg' => __('You cannot use this function because this is a demo site')]));
    }
    if ($CMSNT->site('status') != 1 && isSecureCookie('admin_login') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('The system is maintenance')]));
    }
    if ($CMSNT->site('tmweasyapi_status') != 1) {
        die(json_encode(['status' => 'error', 'msg' => __('This function is under maintenance')]));
    }
    if ($CMSNT->site('tmweasyapi_username') == '' || $CMSNT->site('tmweasyapi_password') == '' || $CMSNT->site('tmweasyapi_con_id') == '') {
        die(json_encode(['status' => 'error', 'msg' => __('This function has not been configured')]));
    }
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Please log in')]));
    }

    $token = validate_alphanumeric($_POST['token'], 255);
    if ($token === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Token không hợp lệ!')]));
    }

    if (!$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Please log in')]));
    }
    if (time() - $getUser['time_request'] < $config['max_time_load']) {
        die(json_encode(['status' => 'error', 'msg' => __('You are working too fast, please wait')]));
    }
    $amount = validate_float($_POST['amount'], 0.01);
    if ($amount === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Số tiền không hợp lệ!')]));
    }

    if ($amount < $CMSNT->site('tmweasyapi_min')) {
        die(json_encode(['status' => 'error', 'msg' => __('Minimum deposit amount is ' . $CMSNT->site('tmweasyapi_min') . '')]));
    }
    if ($amount > $CMSNT->site('tmweasyapi_max')) {
        die(json_encode(['status' => 'error', 'msg' => __('Maximum deposit amount is ' . $CMSNT->site('tmweasyapi_max') . '')]));
    }
    $trans_id = random('QWERTYUIOPASDFGHJKLZXCVBNM', 3) . time();
    $price = $amount * $CMSNT->site('tmweasyapi_rate');

    require_once(__DIR__ . "/../../libs/tmweasyapi.php");


    $paramsCreate = [
        "username" => $CMSNT->site('tmweasyapi_username'),
        "password" => $CMSNT->site('tmweasyapi_password'),
        "con_id"   => $CMSNT->site('tmweasyapi_con_id'),
        "amount"   => $amount,
        "ref1"     => $trans_id,
        "method"   => "create_pay"
    ];

    // Gọi hàm
    $responseCreate = callMaemaneeApi($paramsCreate);
    if ($responseCreate === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Lỗi gọi API create_pay')]));
    }
    // Kiểm tra response
    if (isset($responseCreate['status']) && $responseCreate['status'] == 1) {
        $idPay = $responseCreate['id_pay'];  // Lưu để dùng ở bước tiếp
        $paramsDetail = [
            "username" => $CMSNT->site('tmweasyapi_username'),
            "password" => $CMSNT->site('tmweasyapi_password'),
            "con_id"   => $CMSNT->site('tmweasyapi_con_id'),
            "id_pay"   => $idPay,
            "qr"       => 1,
            "method"   => "detail_pay"
        ];

        $responseDetail = callMaemaneeApi($paramsDetail);
        if ($responseDetail === false) {
            die(json_encode(['status' => 'error', 'msg' => __('Lỗi gọi API detail_pay')]));
        }

        // Kiểm tra kết quả
        if (isset($responseDetail['status']) && $responseDetail['status'] == 1) {
            $ref1     = validate_alphanumeric($responseDetail['ref1'], 50);
            $amount   = validate_float($responseDetail['amount'], 0.01);
            $urlPay   = validate_url($responseDetail['urlpay']);
            $timeOut  = validate_int($responseDetail['time_out'], 1);
            $isInsert = $CMSNT->insert('payment_tmweasyapi', array(
                'user_id'           => $getUser['id'],
                'trans_id'          => $trans_id,
                'id_pay'            => $idPay,
                'price'             => $price,
                'amount'            => $amount,
                'status'            => 0,
                'created_at'        => gettime(),
                'updated_at'        => gettime(),
                'checkout_url'      => $urlPay
            ));
            if ($isInsert) {
                $CMSNT->update("users", [
                    'time_request' => time()
                ], " `id` = ?", [$getUser['id']]);

                $CMSNT->insert("logs", [
                    'user_id'       => $getUser['id'],
                    'ip'            => myip(),
                    'device'        => getUserAgent(),
                    'createdate'    => gettime(),
                    'action'        => __('Create Tmweasyapi Thailand top-up invoice #') . " $trans_id"
                ]);
                // Rate limit
                checkBlockIP('PAYMENT', 5);
                die(json_encode([
                    'invoice_url'  => $urlPay,
                    'qr'           => $responseDetail['qr_base64_image'],
                    'time_out'     => $timeOut,
                    'amount'       => $amount,
                    'status'       => 'success',
                    'msg'          => __('Successful!')
                ]));
            }

            die(json_encode(['status' => 'error', 'msg' => __('Không thể tạo hóa đơn nạp tiền!')]));
        } else {
            $msgError = isset($responseDetail['msg']) ? $responseDetail['msg'] : "Không rõ lỗi";
            die("Không thể lấy chi tiết thanh toán. Lý do: " . $msgError);
        }
    } else {
        // Thất bại
        $msgError = isset($responseCreate['msg']) ? validate_string($responseCreate['msg'], 500) : "Không rõ lỗi";
        die(json_encode(['status' => 'error', 'msg' => $msgError]));
    }
}


if ($_POST['action'] == 'RechargeOpenPix') {
    if ($CMSNT->site('status_demo') != 0) {
        die(json_encode(['status' => 'error', 'msg' => __('You cannot use this function because this is a demo site')]));
    }
    if ($CMSNT->site('status') != 1 && isSecureCookie('admin_login') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('The system is maintenance')]));
    }
    if ($CMSNT->site('openpix_status') != 1) {
        die(json_encode(['status' => 'error', 'msg' => __('This function is under maintenance')]));
    }
    if ($CMSNT->site('openpix_api_key') == '') {
        die(json_encode(['status' => 'error', 'msg' => __('This function has not been configured')]));
    }
    if ($CMSNT->site('openpix_HMAC_key') == '') {
        die(json_encode(['status' => 'error', 'msg' => __('This function has not been configured')]));
    }
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Please log in')]));
    }

    $token = validate_alphanumeric($_POST['token'], 255);
    if ($token === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Token không hợp lệ!')]));
    }

    if (!$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Please log in')]));
    }
    if (time() - $getUser['time_request'] < $config['max_time_load']) {
        die(json_encode(['status' => 'error', 'msg' => __('You are working too fast, please wait')]));
    }
    $amount = validate_float($_POST['amount'], 0.01);
    if ($amount === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Số tiền không hợp lệ!')]));
    }

    if ($amount < $CMSNT->site('openpix_min')) {
        die(json_encode(['status' => 'error', 'msg' => __('Minimum deposit amount is ' . $CMSNT->site('openpix_min') . '')]));
    }
    if ($amount > $CMSNT->site('openpix_max')) {
        die(json_encode(['status' => 'error', 'msg' => __('Maximum deposit amount is ' . $CMSNT->site('openpix_max') . '')]));
    }
    $trans_id = random('QWERTYUIOPASDFGHJKLZXCVBNM', 3) . time();
    $price = $amount * $CMSNT->site('openpix_rate');

    // Chuyển đổi số tiền để đảm bảo API OpenPix nhận đúng giá trị
    // API OpenPix coi 1.00 là 1 Real, nên cần chuyển đổi giá trị input
    // Nếu người dùng nhập 100, cần gửi 100 thay vì API hiểu thành 1.00
    $openpix_value = (float)$amount * 100; // Nhân với 100 để đảm bảo giá trị đúng format

    // JSON data to be sent
    $data = array(
        'correlationID' => $trans_id,
        'value' => $openpix_value, // Giá trị đã được xử lý đúng format
        'comment' => 'Topup ' . $getUser['username'],
    );
    // Encode the data to JSON format
    $json = json_encode($data);
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_URL => 'https://api.openpix.com.br/api/v1/charge?return_existing=true',
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_POSTFIELDS => $json,
        CURLOPT_POST => 1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_HTTPHEADER => [
            "Authorization: " . $CMSNT->site('openpix_api_key'),
            "content-type: application/json"
        ]
    ]);

    // Execute the cURL request
    $response = curl_exec($ch);
    // Close the cURL handle
    curl_close($ch);
    $response = json_decode($response, true);


    // Kiểm tra phản hồi từ API
    if (($response && isset($response['charge']) && $response['charge']['status'] === 'ACTIVE') || ($response && isset($response['status']) && $response['status'] === 'ACTIVE')) {

        // Lưu thông tin cần thiết
        $transactionID = isset($response['charge']) ? $response['charge']['transactionID'] : $response['pix']['transactionID'];
        $qrCodeImage = isset($response['charge']) ? $response['charge']['qrCodeImage'] : $response['pix']['qrCodeImage'];
        $checkoutUrl = isset($response['charge']) ? $response['charge']['paymentLinkUrl'] : $response['paymentLinkUrl'];
        $reference = isset($response['charge']) ? $response['charge']['correlationID'] : $response['correlationID'];

        $isInsert = $CMSNT->insert('payment_openpix', array(
            'user_id'           => $getUser['id'],
            'trans_id'          => $trans_id,
            'price'             => $price,
            'amount'            => $amount,
            'status'            => 0,
            'created_at'        => gettime(),
            'updated_at'        => gettime(),
            'checkout_url'      => $checkoutUrl
        ));
        if ($isInsert) {
            $CMSNT->update("users", [
                'time_request' => time()
            ], " `id` = ?", [$getUser['id']]);


            $CMSNT->insert("logs", [
                'user_id'       => $getUser['id'],
                'ip'            => myip(),
                'device'        => getUserAgent(),
                'createdate'    => gettime(),
                'action'        => __('Create OpenPix top-up invoice #') . " $trans_id"
            ]);
            // Rate limit
            checkBlockIP('PAYMENT', 5);
            die(json_encode(['invoice_url'  => $checkoutUrl, 'status' => 'success', 'msg' => __('Successful!')]));
        } else {
            die(json_encode(['status' => 'error', 'msg' => __('Failed to create invoice')]));
        }
    } else {
        // Xử lý lỗi nếu khởi tạo không thành công
        if (isset($response['error'])) {
            die(json_encode(['status' => 'error', 'msg' => $response['error']]));
        } else {
            die(json_encode(['status' => 'error', 'msg' => 'Unknown error occurred']));
        }
    }
}

if ($_POST['action'] == 'RechargeBakong') {
    if ($CMSNT->site('status_demo') != 0) {
        die(json_encode(['status' => 'error', 'msg' => __('You cannot use this function because this is a demo site')]));
    }
    if ($CMSNT->site('status') != 1 && isSecureCookie('admin_login') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('The system is maintenance')]));
    }
    if ($CMSNT->site('bakong_status') != 1) {
        die(json_encode(['status' => 'error', 'msg' => __('This function is under maintenance')]));
    }
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Please log in')]));
    }

    $token = validate_alphanumeric($_POST['token'], 255);
    if ($token === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Token không hợp lệ!')]));
    }

    if (!$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Please log in')]));
    }
    if (time() - $getUser['time_request'] < $config['max_time_load']) {
        die(json_encode(['status' => 'error', 'msg' => __('You are working too fast, please wait')]));
    }
    $amount = validate_float($_POST['amount'], 0.01);
    if ($amount === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Số tiền không hợp lệ!')]));
    }

    if ($amount < $CMSNT->site('bakong_min')) {
        die(json_encode(['status' => 'error', 'msg' => __('Minimum deposit amount is ' . $CMSNT->site('bakong_min') . '')]));
    }
    if ($amount > $CMSNT->site('bakong_max')) {
        die(json_encode(['status' => 'error', 'msg' => __('Maximum deposit amount is ' . $CMSNT->site('bakong_max') . '')]));
    }
    $trans_id = random('123456789', 4) . time();
    $price = $amount * $CMSNT->site('bakong_rate');

    require_once(__DIR__ . "/../../libs/bakong.php");

    $params = [
        'amount' => $amount,
        'transaction_id' => $trans_id,
        'success_url' => base_url('?action=recharge-bakong'),
        'remark' => 'Topup ' . $getUser['username']
    ];
    $response = createPaymentBakong($params);
    if (isset($response)) {
        $isInsert = $CMSNT->insert('payment_bakong', array(
            'user_id'           => $getUser['id'],
            'trans_id'          => $trans_id,
            'price'             => $price,
            'amount'            => $amount,
            'status'            => 0,
            'created_at'        => gettime(),
            'updated_at'        => gettime(),
            'checkout_url'      => NULL
        ));
        if ($isInsert) {
            $CMSNT->update("users", [
                'time_request' => time()
            ], " `id` = ?", [$getUser['id']]);


            $CMSNT->insert("logs", [
                'user_id'       => $getUser['id'],
                'ip'            => myip(),
                'device'        => getUserAgent(),
                'createdate'    => gettime(),
                'action'        => __('Create Bakong Wallet Cambodia top-up invoice #') . " $trans_id"
            ]);
            // Rate limit
            checkBlockIP('PAYMENT', 5);
            die(json_encode([
                'status' => 'success',
                'msg' => __('Successful!'),
                'invoice_url'  => $response
            ]));
        }
        die(json_encode(['status' => 'error', 'msg' => __('Failed to create invoice 1')]));
    } else {
        die(json_encode(['status' => 'error', 'msg' => __('Failed to create invoice')]));
    }
}

if ($_POST['action'] == 'CreatePaymentpointAccount') {
    if ($CMSNT->site('status_demo') != 0) {
        die(json_encode(['status' => 'error', 'msg' => __('You cannot use this function because this is a demo site')]));
    }
    if ($CMSNT->site('status') != 1 && isSecureCookie('admin_login') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('The system is maintenance')]));
    }
    if ($CMSNT->site('paymentpoint_status') != 1) {
        die(json_encode(['status' => 'error', 'msg' => __('This function is under maintenance')]));
    }
    if ($CMSNT->site('paymentpoint_api_secret') == '' || $CMSNT->site('paymentpoint_api_key') == '') {
        die(json_encode(['status' => 'error', 'msg' => __('This function has not been configured')]));
    }
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Please log in')]));
    }

    $token = validate_alphanumeric($_POST['token'], 255);
    if ($token === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Token không hợp lệ!')]));
    }

    if (!$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Please log in')]));
    }
    if (time() - $getUser['time_request'] < $config['max_time_load']) {
        die(json_encode(['status' => 'error', 'msg' => __('You are working too fast, please wait')]));
    }

    $trans_id = random('QWERTYUIOPASDFGHJKLZXCVBNM', 3) . time();

    require_once(__DIR__ . "/../../libs/paymentpoint.php");

    // Xử lý phone
    $phone = $getUser['phone'] ? preg_replace('/[^0-9]/', '', $getUser['phone']) : '';
    if (empty($phone) || strlen($phone) < 10) {
        $phone = '070' . str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
    }

    // Xử lý email
    $email = $getUser['email'];
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email = $getUser['username'] . '@example.com';
    }

    // Lấy danh sách bank codes từ settings
    $bankCodesStr = $CMSNT->site('paymentpoint_bank_codes');
    $bankCodes = array_map('trim', explode(',', $bankCodesStr));
    if (empty($bankCodes) || (count($bankCodes) == 1 && $bankCodes[0] == '')) {
        $bankCodes = ['20946', '20897']; // Default: PALMPAY, OPAY
    }

    // Các tham số cần thiết cho API PaymentPoint
    $params = [
        "email"       => $email,
        "name"        => $getUser['username'],
        "phoneNumber" => $phone,
        "bankCode"    => $bankCodes,
        "businessId"  => $CMSNT->site('paymentpoint_business_id')
    ];

    // Gọi hàm tạo virtual account
    $apiSecret = $CMSNT->site('paymentpoint_api_secret');
    $apiKey = $CMSNT->site('paymentpoint_api_key');
    $response = paymentpointCreateVirtualAccount($apiSecret, $apiKey, $params);

    // Kiểm tra phản hồi từ API
    if ($response && isset($response['status']) && $response['status'] === 'success') {
        // Lấy thông tin bank accounts từ response
        $bankAccounts = isset($response['bankAccounts']) ? $response['bankAccounts'] : [];
        $customerId = isset($response['customer']['customer_id']) ? $response['customer']['customer_id'] : '';

        if (empty($bankAccounts)) {
            die(json_encode(['status' => 'error', 'msg' => __('No bank account created')]));
        }

        // Lưu vào database
        $isInsert = $CMSNT->insert('payment_paymentpoint', array(
            'user_id'               => $getUser['id'],
            'trans_id'              => $trans_id,
            'customer_id'           => $customerId,
            'price'                 => 0,
            'amount'                => 0,
            'status'                => 0,
            'created_at'            => gettime(),
            'updated_at'            => gettime(),
            'account_number'        => $bankAccounts[0]['accountNumber'],
            'account_name'          => $bankAccounts[0]['accountName'],
            'bank_name'             => $bankAccounts[0]['bankName'],
            'bank_code'             => $bankAccounts[0]['bankCode'],
            'webhook_transaction_id' => NULL
        ));

        if ($isInsert) {
            $CMSNT->update("users", [
                'time_request' => time()
            ], " `id` = ?", [$getUser['id']]);

            $CMSNT->insert("logs", [
                'user_id'       => $getUser['id'],
                'ip'            => myip(),
                'device'        => getUserAgent(),
                'createdate'    => gettime(),
                'action'        => __('Create PaymentPoint virtual account #') . " $trans_id"
            ]);
            // Rate limit
            checkBlockIP('PAYMENT', 5);
            die(json_encode([
                'status'        => 'success',
                'msg'           => __('Virtual account created successfully!'),
                'trans_id'      => $trans_id,
                'bank_accounts' => $bankAccounts
            ]));
        }
        die(json_encode(['status' => 'error', 'msg' => __('Failed to save invoice')]));
    } else {
        // Xử lý lỗi nếu khởi tạo không thành công
        $errorMsg = isset($response['message']) ? $response['message'] : __('Error creating virtual account');
        die(json_encode(['status' => 'error', 'msg' => $errorMsg]));
    }
}

// ============ DSocioPay - Create Virtual Account ============
if ($_POST['action'] == 'CreateDsociopayAccount') {
    if ($CMSNT->site('status_demo') != 0) {
        die(json_encode(['status' => 'error', 'msg' => __('You cannot use this function because this is a demo site')]));
    }
    if ($CMSNT->site('status') != 1 && isSecureCookie('admin_login') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('The system is maintenance')]));
    }
    if ($CMSNT->site('dsociopay_status') != 1) {
        die(json_encode(['status' => 'error', 'msg' => __('This function is under maintenance')]));
    }
    if ($CMSNT->site('dsociopay_private_key') == '') {
        die(json_encode(['status' => 'error', 'msg' => __('This function has not been configured')]));
    }
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Please log in')]));
    }

    $token = validate_alphanumeric($_POST['token'], 255);
    if ($token === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Token không hợp lệ!')]));
    }

    if (!$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Please log in')]));
    }
    if (time() - $getUser['time_request'] < $config['max_time_load']) {
        die(json_encode(['status' => 'error', 'msg' => __('You are working too fast, please wait')]));
    }

    // Kiểm tra đã có tài khoản chưa
    require_once(__DIR__ . "/../../libs/dsociopay.php");
    $existingAccount = dsociopayGetUserVirtualAccount($getUser['id']);
    if ($existingAccount && !empty($existingAccount['account_number'])) {
        die(json_encode([
            'status'         => 'success',
            'msg'            => __('Virtual account already exists'),
            'account_number' => $existingAccount['account_number'],
            'account_name'   => $existingAccount['account_name'],
            'bank_name'      => $existingAccount['bank_name']
        ]));
    }

    $trans_id = random('QWERTYUIOPASDFGHJKLZXCVBNM', 3) . time();

    // Xử lý email
    $email = $getUser['email'];
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email = $getUser['username'] . '@example.com';
    }

    // Xử lý phone
    $phone = $getUser['phone'] ? preg_replace('/[^0-9]/', '', $getUser['phone']) : '';
    if (empty($phone) || strlen($phone) < 10) {
        $phone = '070' . str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
    }

    // Các tham số cần thiết cho API DSocioPay
    $params = [
        "email"        => $email,
        "username"     => $getUser['username'],
        "phone_number" => $phone
    ];

    // Gọi hàm tạo virtual account
    $privateKey = $CMSNT->site('dsociopay_private_key');
    $response = dsociopayCreateVirtualAccount($privateKey, $params);

    // Kiểm tra phản hồi từ API
    if ($response && isset($response['status']) && $response['status'] === 'success') {
        $accountNumber = isset($response['account_number']) ? $response['account_number'] : '';
        $accountName   = isset($response['account_name']) ? $response['account_name'] : '';
        $bankName      = isset($response['bank_name']) ? $response['bank_name'] : '';

        if (empty($accountNumber)) {
            die(json_encode(['status' => 'error', 'msg' => __('No virtual account created')]));
        }

        // Lưu vào database
        $isInsert = $CMSNT->insert('payment_dsociopay', array(
            'user_id'               => $getUser['id'],
            'trans_id'              => $trans_id,
            'price'                 => 0,
            'amount'                => 0,
            'status'                => 0,
            'created_at'            => gettime(),
            'updated_at'            => gettime(),
            'account_number'        => $accountNumber,
            'account_name'          => $accountName,
            'bank_name'             => $bankName,
            'webhook_transaction_id' => NULL
        ));

        if ($isInsert) {
            $CMSNT->update("users", [
                'time_request' => time()
            ], " `id` = ?", [$getUser['id']]);

            $CMSNT->insert("logs", [
                'user_id'       => $getUser['id'],
                'ip'            => myip(),
                'device'        => getUserAgent(),
                'createdate'    => gettime(),
                'action'        => __('Create DSocioPay virtual account #') . " $trans_id"
            ]);
            // Rate limit
            checkBlockIP('PAYMENT', 5);
            die(json_encode([
                'status'         => 'success',
                'msg'            => __('Virtual account created successfully!'),
                'trans_id'       => $trans_id,
                'account_number' => $accountNumber,
                'account_name'   => $accountName,
                'bank_name'      => $bankName
            ]));
        }
        die(json_encode(['status' => 'error', 'msg' => __('Failed to save invoice')]));
    } else {
        // Xử lý lỗi nếu khởi tạo không thành công
        $errorMsg = isset($response['message']) ? $response['message'] : __('Error creating virtual account');
        die(json_encode(['status' => 'error', 'msg' => $errorMsg]));
    }
}

if ($_POST['action'] == 'RechargeLempay') {
    if ($CMSNT->site('status_demo') != 0) {
        die(json_encode(['status' => 'error', 'msg' => __('You cannot use this function because this is a demo site')]));
    }
    if ($CMSNT->site('status') != 1 && isSecureCookie('admin_login') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('The system is maintenance')]));
    }
    if ($CMSNT->site('lempay_status') != 1) {
        die(json_encode(['status' => 'error', 'msg' => __('This function is under maintenance')]));
    }
    if ($CMSNT->site('lempay_pid') == '' || $CMSNT->site('lempay_key') == '') {
        die(json_encode(['status' => 'error', 'msg' => __('This function has not been configured')]));
    }
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Please log in')]));
    }

    $token = validate_alphanumeric($_POST['token'], 255);
    if ($token === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Token không hợp lệ!')]));
    }

    if (!$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Please log in')]));
    }
    if (time() - $getUser['time_request'] < $config['max_time_load']) {
        die(json_encode(['status' => 'error', 'msg' => __('You are working too fast, please wait')]));
    }

    // Validate amount
    $amount = validate_float($_POST['amount'], 0.01);
    if ($amount === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Số tiền không hợp lệ!')]));
    }

    // Validate payment type
    $paymentType = validate_string($_POST['payment_type'] ?? 'alipay', 20);
    $allowedTypes = ['alipay', 'wxpay', 'usdt'];
    if (!in_array($paymentType, $allowedTypes)) {
        die(json_encode(['status' => 'error', 'msg' => __('Invalid payment type')]));
    }

    // Check min/max
    if ($amount < $CMSNT->site('lempay_min')) {
        die(json_encode(['status' => 'error', 'msg' => __('Minimum deposit amount is ') . $CMSNT->site('lempay_min') . ' CNY']));
    }
    if ($amount > $CMSNT->site('lempay_max')) {
        die(json_encode(['status' => 'error', 'msg' => __('Maximum deposit amount is ') . $CMSNT->site('lempay_max') . ' CNY']));
    }

    // Get rate based on payment type
    $rateKey = 'lempay_rate_' . $paymentType;
    $rate = $CMSNT->site($rateKey);
    if (empty($rate) || $rate <= 0) {
        $rate = $CMSNT->site('lempay_rate'); // Fallback to default rate
    }

    $trans_id = random('QWERTYUIOPASDFGHJKLZXCVBNM', 3) . time();
    $price = $amount * $rate;

    require_once(__DIR__ . "/../../libs/lempay.php");

    // Cấu hình API
    $lempayConfig = [
        'pid'     => $CMSNT->site('lempay_pid'),
        'key'     => $CMSNT->site('lempay_key'),
        'api_url' => $CMSNT->site('lempay_api_url') ?: 'https://a119a.lempay.com'
    ];

    // Tham số thanh toán
    $params = [
        'type'         => $paymentType,
        'out_trade_no' => $trans_id,
        'notify_url'   => base_url('api/callback_lempay.php'),
        'return_url'   => base_url('?action=recharge-lempay'),
        'name'         => 'Topup ' . $getUser['username'],
        'money'        => $amount,
        'sitename'     => $_SERVER['HTTP_HOST'],
        'clientip'     => myip(),
        'device'       => lempayDetectDevice()
    ];

    // Gọi API tạo thanh toán
    $response = lempayCreatePayment($lempayConfig, $params);

    // Kiểm tra phản hồi
    if ($response && isset($response['code']) && $response['code'] == 1) {
        $payurl = $response['payurl'] ?? '';
        $tradeNo = $response['trade_no'] ?? '';

        $isInsert = $CMSNT->insert('payment_lempay', array(
            'user_id'    => $getUser['id'],
            'trans_id'   => $trans_id,
            'trade_no'   => $tradeNo,
            'type'       => $paymentType,
            'price'      => $price,
            'amount'     => $amount,
            'status'     => 0,
            'created_at' => gettime(),
            'updated_at' => gettime(),
            'payurl'     => $payurl,
            'notication' => 0
        ));

        if ($isInsert) {
            $CMSNT->update("users", [
                'time_request' => time()
            ], " `id` = ?", [$getUser['id']]);

            $CMSNT->insert("logs", [
                'user_id'    => $getUser['id'],
                'ip'         => myip(),
                'device'     => getUserAgent(),
                'createdate' => gettime(),
                'action'     => __('Create LemPay top-up invoice #') . " $trans_id"
            ]);

            // Rate limit
            checkBlockIP('PAYMENT', 5);

            die(json_encode([
                'invoice_url' => $payurl,
                'status'      => 'success',
                'msg'         => __('Successful!')
            ]));
        }
        die(json_encode(['status' => 'error', 'msg' => __('Failed to create invoice')]));
    } else {
        // Xử lý lỗi
        $errorMsg = isset($response['msg']) ? $response['msg'] : __('Error creating payment');
        die(json_encode(['status' => 'error', 'msg' => $errorMsg]));
    }
}

if ($_POST['action'] == 'RechargeZinipay') {
    if ($CMSNT->site('status_demo') != 0) {
        die(json_encode(['status' => 'error', 'msg' => __('You cannot use this function because this is a demo site')]));
    }
    if ($CMSNT->site('status') != 1 && isSecureCookie('admin_login') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('The system is maintenance')]));
    }
    if ($CMSNT->site('zinipay_status') != 1) {
        die(json_encode(['status' => 'error', 'msg' => __('This function is under maintenance')]));
    }
    if ($CMSNT->site('zinipay_api_key') == '') {
        die(json_encode(['status' => 'error', 'msg' => __('This function has not been configured')]));
    }
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Please log in')]));
    }

    $token = validate_alphanumeric($_POST['token'], 255);
    if ($token === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Token không hợp lệ!')]));
    }

    if (!$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Please log in')]));
    }
    if (time() - $getUser['time_request'] < $config['max_time_load']) {
        die(json_encode(['status' => 'error', 'msg' => __('You are working too fast, please wait')]));
    }

    // Validate amount
    $amount = validate_float($_POST['amount'], 0.01);
    if ($amount === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Số tiền không hợp lệ!')]));
    }

    // Check min/max
    if ($amount < $CMSNT->site('zinipay_min')) {
        die(json_encode(['status' => 'error', 'msg' => __('Minimum deposit amount is ') . $CMSNT->site('zinipay_min') . ' BDT']));
    }
    if ($amount > $CMSNT->site('zinipay_max')) {
        die(json_encode(['status' => 'error', 'msg' => __('Maximum deposit amount is ') . $CMSNT->site('zinipay_max') . ' BDT']));
    }

    $rate = $CMSNT->site('zinipay_rate');
    if (empty($rate) || $rate <= 0) {
        $rate = 1;
    }

    $trans_id = random('QWERTYUIOPASDFGHJKLZXCVBNM', 3) . time();
    $price = $amount * $rate;

    require_once(__DIR__ . "/../../libs/zinipay.php");

    // Cấu hình API
    $zinipayConfig = [
        'api_key' => $CMSNT->site('zinipay_api_key'),
        'api_url' => $CMSNT->site('zinipay_api_url') ?: 'https://api.zinipay.com'
    ];

    // Tham số thanh toán
    $params = [
        'cus_name'     => $getUser['username'],
        'cus_email'    => $getUser['email'] ?: 'noemail@example.com',
        'amount'       => $amount,
        'redirect_url' => base_url('?action=recharge-zinipay'),
        'cancel_url'   => base_url('?action=recharge-zinipay'),
        'webhook_url'  => base_url('api/callback_zinipay.php?secret=' . $CMSNT->site('zinipay_callback_secret')),
        'metadata'     => ['trans_id' => $trans_id, 'user_id' => $getUser['id']]
    ];

    // Gọi API tạo thanh toán
    $response = zinipayCreatePayment($zinipayConfig, $params);

    // Kiểm tra phản hồi (status === true)
    if ($response && isset($response['status']) && ($response['status'] === true || $response['status'] === 'true') && !empty($response['payment_url'])) {
        $payurl = $response['payment_url'];
        $invoiceId = $response['invoice_id'] ?? zinipayExtractInvoiceId($payurl);

        $isInsert = $CMSNT->insert('payment_zinipay', array(
            'user_id'    => $getUser['id'],
            'trans_id'   => $trans_id,
            'trade_no'   => $invoiceId,
            'price'      => $price,
            'amount'     => $amount,
            'status'     => 0,
            'created_at' => gettime(),
            'updated_at' => gettime(),
            'payurl'     => $payurl,
            'notication' => 0
        ));

        if ($isInsert) {
            $CMSNT->update("users", [
                'time_request' => time()
            ], " `id` = ?", [$getUser['id']]);

            $CMSNT->insert("logs", [
                'user_id'    => $getUser['id'],
                'ip'         => myip(),
                'device'     => getUserAgent(),
                'createdate' => gettime(),
                'action'     => __('Create ZiniPay top-up invoice #') . " $trans_id"
            ]);

            // Rate limit
            checkBlockIP('PAYMENT', 5);

            die(json_encode([
                'invoice_url' => $payurl,
                'status'      => 'success',
                'msg'         => __('Successful!')
            ]));
        }
        die(json_encode(['status' => 'error', 'msg' => __('Failed to create invoice')]));
    } else {
        // Xử lý lỗi
        $errorMsg = isset($response['message']) ? $response['message'] : (isset($response['msg']) ? $response['msg'] : __('Error creating payment'));
        die(json_encode(['status' => 'error', 'msg' => $errorMsg]));
    }
}

if ($_POST['action'] == 'RechargeTripay') {
    if ($CMSNT->site('status_demo') != 0) {
        die(json_encode(['status' => 'error', 'msg' => __('You cannot use this function because this is a demo site')]));
    }
    if ($CMSNT->site('status') != 1 && isSecureCookie('admin_login') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('The system is maintenance')]));
    }
    if ($CMSNT->site('tripay_status') != 1) {
        die(json_encode(['status' => 'error', 'msg' => __('This function is under maintenance')]));
    }
    if ($CMSNT->site('tripay_api_key') == '' || $CMSNT->site('tripay_private_key') == '' || $CMSNT->site('tripay_merchant_code') == '') {
        die(json_encode(['status' => 'error', 'msg' => __('This function has not been configured')]));
    }
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Please log in')]));
    }

    $token = validate_alphanumeric($_POST['token'], 255);
    if ($token === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Token không hợp lệ!')]));
    }

    if (!$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Please log in')]));
    }
    if (time() - $getUser['time_request'] < $config['max_time_load']) {
        die(json_encode(['status' => 'error', 'msg' => __('You are working too fast, please wait')]));
    }

    // Validate amount
    $amount = validate_int($_POST['amount'], 1);
    if ($amount === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Số tiền không hợp lệ!')]));
    }

    // Check min/max
    if ($amount < $CMSNT->site('tripay_min')) {
        die(json_encode(['status' => 'error', 'msg' => __('Minimum deposit amount is ') . number_format($CMSNT->site('tripay_min')) . ' IDR']));
    }
    if ($amount > $CMSNT->site('tripay_max')) {
        die(json_encode(['status' => 'error', 'msg' => __('Maximum deposit amount is ') . number_format($CMSNT->site('tripay_max')) . ' IDR']));
    }

    // Validate payment method
    $paymentMethod = validate_string($_POST['payment_method'] ?? 'QRIS', 20);
    if ($paymentMethod === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Invalid payment method')]));
    }

    $trans_id = random('QWERTYUIOPASDFGHJKLZXCVBNM', 3) . time();
    $price = $amount * $CMSNT->site('tripay_rate');

    require_once(__DIR__ . "/../../libs/tripay.php");

    // Cấu hình API
    $tripayConfig = [
        'api_key'       => $CMSNT->site('tripay_api_key'),
        'private_key'   => $CMSNT->site('tripay_private_key'),
        'merchant_code' => $CMSNT->site('tripay_merchant_code'),
        'sandbox'       => $CMSNT->site('tripay_sandbox') == 1
    ];

    // Xử lý email
    $email = $getUser['email'];
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email = $getUser['username'] . '@example.com';
    }

    // Tham số giao dịch
    $params = [
        'method'         => $paymentMethod,
        'merchant_ref'   => $trans_id,
        'amount'         => (int)$amount,
        'customer_name'  => $getUser['username'],
        'customer_email' => $email,
        'customer_phone' => $getUser['phone'] ?? '',
        'order_items'    => [
            [
                'sku'      => 'TOPUP',
                'name'     => 'Deposit ' . $getUser['username'],
                'price'    => (int)$amount,
                'quantity' => 1,
                'subtotal' => (int)$amount
            ]
        ],
        'callback_url'   => base_url('api/callback_tripay.php'),
        'return_url'     => base_url('?action=recharge-tripay'),
        'expired_time'   => time() + (24 * 60 * 60) // 24 giờ
    ];

    // Gọi API tạo giao dịch
    $response = tripayCreateTransaction($tripayConfig, $params);

    // Kiểm tra phản hồi
    if ($response && isset($response['success']) && $response['success'] === true) {
        $checkoutUrl = $response['data']['checkout_url'] ?? '';
        $reference = $response['data']['reference'] ?? '';

        $isInsert = $CMSNT->insert('payment_tripay', array(
            'user_id'      => $getUser['id'],
            'trans_id'     => $trans_id,
            'reference'    => $reference,
            'method'       => $paymentMethod,
            'price'        => $price,
            'amount'       => $amount,
            'status'       => 0,
            'created_at'   => gettime(),
            'updated_at'   => gettime(),
            'checkout_url' => $checkoutUrl,
            'notication'   => 0
        ));

        if ($isInsert) {
            $CMSNT->update("users", [
                'time_request' => time()
            ], " `id` = ?", [$getUser['id']]);

            $CMSNT->insert("logs", [
                'user_id'    => $getUser['id'],
                'ip'         => myip(),
                'device'     => getUserAgent(),
                'createdate' => gettime(),
                'action'     => __('Create TriPay top-up invoice #') . " $trans_id"
            ]);

            // Rate limit
            checkBlockIP('PAYMENT', 5);

            die(json_encode([
                'invoice_url' => $checkoutUrl,
                'status'      => 'success',
                'msg'         => __('Successful!')
            ]));
        }
        die(json_encode(['status' => 'error', 'msg' => __('Failed to create invoice')]));
    } else {
        // Xử lý lỗi
        $errorMsg = isset($response['message']) ? $response['message'] : __('Error creating payment');
        die(json_encode(['status' => 'error', 'msg' => $errorMsg]));
    }
}

die(json_encode([
    'status'    => 'error',
    'msg'       => __('Request does not exist')
]));
