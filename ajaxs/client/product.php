<?php

define("IN_SITE", true);
require_once(__DIR__ . "/../../config.php");
require_once(__DIR__ . "/../../libs/db.php");
require_once(__DIR__ . "/../../libs/lang.php");
require_once(__DIR__ . "/../../libs/helper.php");
require_once(__DIR__ . "/../../libs/sendEmail.php");
require_once(__DIR__ . "/../../libs/SMTPMailer.php");
require_once(__DIR__ . '/../../libs/TelegramQueue.php');
require_once(__DIR__ . '/../../libs/suppliers.php');
require_once(__DIR__ . '/../../libs/database/users.php');

header('Content-Type: application/json; charset=utf-8');


if ($CMSNT->site('status') != 1) {
    http_response_code(503); // Service Unavailable
    $data = json_encode([
        'status'    => 'error',
        'msg'       => __('Hệ thống đang bảo trì!')
    ]);
    die($data);
}
if (!isset($_REQUEST['action'])) {
    http_response_code(400); // Bad Request
    $data = json_encode([
        'status'    => 'error',
        'msg'       => __('The Request Not Found')
    ]);
    die($data);
}
if ($_REQUEST['action'] == 'buyProduct') {
    if ($CMSNT->site('status_demo') != 0) {
        http_response_code(403); // Forbidden
        die(json_encode(['status' => 'error', 'msg' => __('This function cannot be used because this is a demo site')]));
    }
    // Xử lý User khi mua bằng API
    if (!empty($_REQUEST['api_key'])) {
        $api_key = validate_alphanumeric($_REQUEST['api_key']);
        if ($api_key === false) {
            checkBlockIP('API', 5);
            http_response_code(400); // Bad Request
            die(json_encode(['status' => 'error', 'msg' => __('API key không hợp lệ!')]));
        }

        if (!$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `api_key` = ? AND `banned` = 0", [$api_key])) {
            // Rate limit
            checkBlockIP('API', 5);
            http_response_code(401); // Unauthorized
            die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập')]));
        }
        // Kiểm tra IP có trong Whitelist hay không
        $client_ip = myip();
        if (!checkIPWhitelist($getUser['ip_whitelist_api'], $client_ip)) {
            checkBlockIP('IP_NOT_WHITELIST_API', 5);
            http_response_code(403); // Forbidden
            die(json_encode([
                'status' => 'error',
                'msg' => __('IP của bạn không nằm trong Whitelist API của User này'),
                'client_ip' => $client_ip
            ]));
        }
    }
    // Xử lý User khi mua tại web
    else if (!empty($_REQUEST['token'])) {
        $token = validate_alphanumeric($_REQUEST['token'], 255);
        if ($token === false) {
            checkBlockIP('API', 5);
            http_response_code(400); // Bad Request
            die(json_encode(['status' => 'error', 'msg' => __('Token không hợp lệ!')]));
        }

        if (!$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0", [$token])) {
            // Rate limit
            checkBlockIP('API', 5);
            http_response_code(401); // Unauthorized
            die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập')]));
        }
    } else {
        http_response_code(401); // Unauthorized
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập')]));
    }
    //

    if ($getUser['banned'] != 0) {
        http_response_code(403); // Forbidden
        die(json_encode(['status' => 'error', 'msg' => __('Tài khoản của bạn đã bị cấm')]));
    }
    if ($getUser['ctv'] != 0) {
        http_response_code(403); // Forbidden
        die(json_encode(['status' => 'error', 'msg' => __('Tài khoản CTV không được phép mua hàng')]));
    }
    if (time() > $getUser['time_request'] && time() - $getUser['time_request'] < $CMSNT->site('thoi_gian_mua_cach_nhau')) {
        http_response_code(429); // Too Many Requests
        die(json_encode(['status' => 'error', 'msg' => __('Thao tác quá nhanh, vui lòng chờ')]));
    }
    $product_id = validate_int($_REQUEST['id'], 1);
    if ($product_id === false) {
        http_response_code(400); // Bad Request
        die(json_encode(['status' => 'error', 'msg' => __('ID sản phẩm không hợp lệ!')]));
    }

    if (!$product = $CMSNT->get_row_safe("SELECT * FROM `products` WHERE `id` = ? AND `status` = 1", [$product_id])) {
        http_response_code(404); // Not Found
        die(json_encode(['status' => 'error', 'msg' => __('Sản phẩm không tồn tại trong hệ thống')]));
    }

    $amount = validate_int($_REQUEST['amount'], 1);
    if ($amount === false) {
        http_response_code(400); // Bad Request
        die(json_encode(['status' => 'error', 'msg' => __('Số lượng không hợp lệ!')]));
    }
    if ($amount < $product['min']) {
        http_response_code(400); // Bad Request
        die(json_encode(['status' => 'error', 'msg' => __('Số lượng cần mua tối thiểu là') . ' ' . format_cash($product['min'])]));
    }
    if ($amount > $product['max']) {
        http_response_code(400); // Bad Request
        die(json_encode(['status' => 'error', 'msg' => __('Số lượng cần mua tối đa là') . ' ' . format_cash($product['max'])]));
    }
    if (is_numeric($amount) && floor($amount) != $amount) {
        http_response_code(400); // Bad Request
        die(json_encode(['status' => 'error', 'msg' => __('Số lượng mua không hợp lệ')]));
    }
    if ($product['supplier_id'] == 0) {
        // KIỂM TRA STOCK HỆ THỐNG
        $stock_count = $CMSNT->get_row_safe("SELECT COUNT(id) as total FROM `product_stock` WHERE `product_code` = ?", [$product['code']])['total'];
        if ($stock_count < $amount) {
            http_response_code(400); // Bad Request
            die(json_encode(['status' => 'error', 'msg' => __('Số lượng còn lại trong hệ thống không đủ')]));
        }
    } else {
        // KIỂM TRA STOCK API
        // if($product['api_stock'] < $amount){
        //     die(json_encode(['status' => 'error', 'msg' => __('Số lượng còn lại trong hệ thống không đủ')]));
        // }
        if (!$supplier = $CMSNT->get_row_safe("SELECT * FROM `suppliers` WHERE `id` = ? AND `status` = 1", [$product['supplier_id']])) {
            http_response_code(503); // Service Unavailable
            die(json_encode(['status' => 'error', 'msg' => __('Sản phẩm này đang bảo trì, không thể mua hàng vào lúc này')]));
        }
    }
    $trans_id = random('QWERTYUOPASDFGHJKZXCVBNM123456789', 4) . uniqid();
    $price = $product['discount'] == 0 ? $product['price'] : $product['price'] - $product['price'] * $product['discount'] / 100;
    $money = $amount * $price; // giá gốc
    $pay = $money;
    $discount = 0;
    $discount_coupon = 0;
    // xử lý giảm giá bằng chiết khấu
    if ($getUser['discount'] == 0) {
        $discount = $money * getDiscount($amount, $product['id']) / 100;
        // Xử lý giảm giá bằng coupon
        if (!empty($_REQUEST['coupon'])) {
            $coupon = validate_alphanumeric($_REQUEST['coupon'], 50);
            if ($coupon !== false) {
                // Lấy số tiền giảm từ Coupon
                $discount_coupon = checkCoupon($product['id'], $coupon, $getUser['id'], $money);
            }
        }
        $pay = $money - $discount - $discount_coupon;
    } else {
        $discount = $money * $getUser['discount'] / 100;
        $pay = $money - $discount;
    }

    $price_vat      = $CMSNT->site('tax_vat') > 0 ? $pay * $CMSNT->site('tax_vat') / 100 : 0; // Số tiền thuế VAT cần trả thêm
    $pay            = $pay + $price_vat; // Số tiền thanh toán sau khi tính thuế VAT


    if (getRowRealtime('users', $getUser['id'], 'money') < $pay) {
        http_response_code(402); // Payment Required
        die(json_encode(['status' => 'error', 'msg' => __('Số dư không đủ, vui lòng nạp thêm')]));
    }
    $User = new users();
    $isTru = $User->RemoveCredits($getUser['id'], $pay, __('Thanh toán đơn hàng mua tài khoản') . ' <b>' . $product['name'] . '</b> - #' . $trans_id, 'ORDER_' . $trans_id);
    if ($isTru) {
        if (getRowRealtime("users", $getUser['id'], "money") < -500) {
            $User->Banned($getUser['id'], __('Gian lận khi mua tài khoản'));
            http_response_code(403); // Forbidden
            die(json_encode(['status' => 'error', 'msg' => __('Bạn đã bị khoá tài khoản vì gian lận')]));
        }
        $api_trans_id = NULL;
        $isValue = 0;

        // Lấy hàng từ API
        if ($product['supplier_id'] != 0) {
            // LẤY HÀNG TỪ API
            if ($supplier['type'] == 'SHOPCLONE6') {
                $data = buy_API_SHOPCLONE6($supplier['domain'], $supplier['username'], $supplier['password'], $product['api_id'], $amount, $supplier['proxy']);
                $data = json_decode($data, true);
                $http_code = validate_string($data['http_code']);
                if (!isset($data) || $data['status'] == 'error2') {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[$http_code][Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503); // Service Unavailable
                    die(json_encode(['status' => 'error', 'msg' => "[$http_code] " . __('Mất kết nối đến kho hàng')]));
                }
                if ($data['status'] == 'error') {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 3] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);

                    // Kiểm tra nếu có HTTP code là 402 thì gửi thông báo qua Telegram
                    if (isset($data['http_code']) && $data['http_code'] == 402) {
                        /** NOTE ACTION */
                        $my_text = $CMSNT->site('noti_api_out_of_money');
                        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
                        $my_text = str_replace('{username}', $getUser['username'], $my_text);
                        $my_text = str_replace('{supplier_name}', $supplier['domain'], $my_text);
                        $my_text = str_replace('{product_name}', $product['name'], $my_text);
                        $my_text = str_replace('{product_id}', $product['id'], $my_text);
                        $my_text = str_replace('{pay}', format_currency($pay), $my_text);
                        $my_text = str_replace('{amount}', format_cash($amount), $my_text);
                        $my_text = str_replace('{ip}', myip(), $my_text);
                        $my_text = str_replace('{time}', gettime(), $my_text);
                        sendMessAdmin($my_text);
                    }
                    http_response_code(503); // Service Unavailable
                    die(json_encode(['status' => 'error', 'msg' => __($data['msg'])]));
                }
                $api_trans_id = $data['data']['trans_id'];
                foreach ($data['data']['lists'] as $account) {
                    $account = check_string($account['account']);
                    $uid = explode('|', $account)[0];
                    $isInsertAPI = $CMSNT->insert("product_sold", [
                        'type'              => $supplier['domain'],
                        'product_code'      => NULL,
                        'supplier_id'       => $product['supplier_id'],
                        'trans_id'          => $trans_id,
                        'buyer'             => $getUser['id'],
                        'seller'            => $product['user_id'],
                        'uid'               => $uid,
                        'account'           => $account,
                        'create_gettime'    => gettime()
                    ]);
                    if ($isInsertAPI) {
                        $isValue++;
                    }
                }
            }
            //
            if ($supplier['type'] == 'SHOPCLONE7') {
                $data = buy_API_SHOPCLONE7($supplier['domain'], $supplier['coupon'], $supplier['api_key'], $product['api_id'], $amount, $supplier['proxy']);
                $data = json_decode($data, true);
                $http_code = validate_string($data['http_code']);
                if (!isset($data) || $data['status'] == 'error2') {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[$http_code][Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503); // Service Unavailable
                    die(json_encode(['status' => 'error', 'msg' => "[$http_code] " . __('Mất kết nối đến kho hàng')]));
                }
                if ($data['status'] == 'error') {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);

                    // Kiểm tra nếu có HTTP code là 402 thì gửi thông báo qua Telegram
                    if (isset($data['http_code']) && $data['http_code'] == 402) {
                        /** NOTE ACTION */
                        $my_text = $CMSNT->site('noti_api_out_of_money');
                        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
                        $my_text = str_replace('{username}', $getUser['username'], $my_text);
                        $my_text = str_replace('{supplier_name}', $supplier['domain'], $my_text);
                        $my_text = str_replace('{product_name}', $product['name'], $my_text);
                        $my_text = str_replace('{product_id}', $product['id'], $my_text);
                        $my_text = str_replace('{pay}', format_currency($pay), $my_text);
                        $my_text = str_replace('{amount}', format_cash($amount), $my_text);
                        $my_text = str_replace('{ip}', myip(), $my_text);
                        $my_text = str_replace('{time}', gettime(), $my_text);
                        sendMessAdmin($my_text);
                    }
                    http_response_code(503); // Service Unavailable
                    die(json_encode(['status' => 'error', 'msg' => __($data['msg'])]));
                }
                $api_trans_id = $data['trans_id'];
                foreach ($data['data'] as $account) {
                    $account = check_string($account);
                    $uid = explode('|', $account)[0];
                    $isInsertAPI = $CMSNT->insert("product_sold", [
                        'type'              => $supplier['domain'],
                        'product_code'      => NULL,
                        'supplier_id'       => $product['supplier_id'],
                        'trans_id'          => $trans_id,
                        'buyer'             => $getUser['id'],
                        'seller'            => $product['user_id'],
                        'uid'               => $uid,
                        'account'           => $account,
                        'create_gettime'    => gettime()
                    ]);
                    if ($isInsertAPI) {
                        $isValue++;
                    }
                }
            }
            //
            if ($supplier['type'] == 'API_1') {
                $dataPost = [
                    'api_key' => $supplier['api_key'],
                    'id_product' => $product['api_id'],
                    'quantity' => $amount,
                ];
                $response = buy_API_1($supplier['domain'], $dataPost);
                $data = json_decode($response, true);
                if (!isset($data)) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503); // Service Unavailable
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng')]));
                }
                if ($data['status'] == false) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __($data['msg'])]));
                }
                $api_trans_id = $data['order_id'];
                $response = order_API_1($supplier['domain'], $supplier['api_key'], $api_trans_id);
                $result = json_decode($response, true);
                foreach ($result['data'] as $account) {
                    $account = check_string($account['full_info']);
                    $uid = explode('|', $account)[0];
                    $isInsertAPI = $CMSNT->insert("product_sold", [
                        'type'              => $supplier['domain'],
                        'product_code'      => NULL,
                        'supplier_id'       => $product['supplier_id'],
                        'trans_id'          => $trans_id,
                        'buyer'             => $getUser['id'],
                        'seller'            => $product['user_id'],
                        'uid'               => $uid,
                        'account'           => $account,
                        'create_gettime'    => gettime()
                    ]);
                    if ($isInsertAPI) {
                        $isValue++;
                    }
                }
            }
            //
            if ($supplier['type'] == 'API_4') {
                $response = buy_API_4($supplier['domain'], $supplier['token'], $product['api_id'], $amount);
                $result = json_decode($response, true);
                if (!isset($result)) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503); // Service Unavailable
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng')]));
                }
                if (!isset($result['data'])) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __($result['message']['messageVNI'])]));
                }
                $api_trans_id = NULL;
                foreach ($result['data'] as $account) {
                    $account = check_string($account);
                    $uid = explode('|', $account)[0];
                    $isInsertAPI = $CMSNT->insert("product_sold", [
                        'type'              => $supplier['domain'],
                        'product_code'      => NULL,
                        'supplier_id'       => $product['supplier_id'],
                        'trans_id'          => $trans_id,
                        'buyer'             => $getUser['id'],
                        'seller'            => $product['user_id'],
                        'uid'               => $uid,
                        'account'           => $account,
                        'create_gettime'    => gettime()
                    ]);
                    if ($isInsertAPI) {
                        $isValue++;
                    }
                }
            }
            //
            if ($supplier['type'] == 'API_6') {
                $response = curl_get2($supplier['domain'] . '/api.php?apikey=' . $supplier['api_key'] . '&action=create-order&service_id=' . $product['api_id'] . '&amount=' . $amount);
                $data = json_decode($response, true);
                if (!isset($data)) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503); // Service Unavailable
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng')]));
                }
                if ($data['code'] != 200) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __($data['message'])]));
                }
                $api_trans_id = $data['order_id'];
                while (true) {
                    $response = curl_get2($supplier['domain'] . '/api.php?apikey=' . $supplier['api_key'] . '&action=get-order-detail&order_id=' . $api_trans_id);
                    $data_account = json_decode($response, true);
                    if ($data_account['order']['status'] == 1) {
                        break;
                    }
                }
                if (explode(PHP_EOL, $data_account['order']['data'])) {
                    $lines = explode(PHP_EOL, $data_account['order']['data']);
                } else {
                    // FIX DO API BMTRAU THAY ĐỔI JSON API
                    $lines = $data_account['order']['data'];
                }
                foreach ($lines as $account) {
                    if (empty($account)) {
                        continue;
                    }
                    $account = check_string($account);
                    $uid = explode('|', $account)[0];
                    if (!isset(explode('|', $account)[1])) {
                        continue;
                    }
                    $isInsertAPI = $CMSNT->insert("product_sold", [
                        'type'              => $supplier['domain'],
                        'product_code'      => NULL,
                        'supplier_id'       => $product['supplier_id'],
                        'trans_id'          => $trans_id,
                        'buyer'             => $getUser['id'],
                        'seller'            => $product['user_id'],
                        'uid'               => $uid,
                        'account'           => $account,
                        'create_gettime'    => gettime()
                    ]);
                    if ($isInsertAPI) {
                        $isValue++;
                    }
                }
            }
            //
            if ($supplier['type'] == 'API_9') {
                $dataPost = [
                    'type_id'   => $product['api_id'],
                    'quantity'  => $amount
                ];
                $response = buy_API_9($supplier['domain'], $supplier['api_key'], $dataPost);
                $result = json_decode($response, true);
                if (!isset($result)) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503); // Service Unavailable
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng')]));
                }
                if ($result['error'] != 0) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __($result['error'])]));
                }
                $api_trans_id = $result['data']['buy_id'];
                foreach ($result['data']['data'] as $account) {
                    $account = check_string($account);
                    $uid = explode('|', $account)[0];
                    $isInsertAPI = $CMSNT->insert("product_sold", [
                        'type'              => $supplier['domain'],
                        'product_code'      => NULL,
                        'supplier_id'       => $product['supplier_id'],
                        'trans_id'          => $trans_id,
                        'buyer'             => $getUser['id'],
                        'seller'            => $product['user_id'],
                        'uid'               => $uid,
                        'account'           => $account,
                        'create_gettime'    => gettime()
                    ]);
                    if ($isInsertAPI) {
                        $isValue++;
                    }
                }
            }
            //
            if ($supplier['type'] == 'API_14') {
                $response = buy_API_14($supplier['domain'], $supplier['token'], $product['api_id'], $amount);
                $data = json_decode($response, true);
                if (!isset($data)) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503); // Service Unavailable
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng')]));
                }
                if ($data['error_code'] == 1) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __($data['message'])]));
                }
                $api_trans_id = $data['order_id'];
                while (true) {
                    $response = getOrder_API_14($supplier['domain'], $supplier['token'], $api_trans_id);
                    $data_account = json_decode($response, true);
                    if (isset($data_account['data'])) {
                        break;
                    }
                }
                $lines = explode(PHP_EOL, $data_account['data']['data']);
                foreach ($lines as $account) {
                    if ($account == '') {
                        continue;
                    }
                    $account = check_string($account);
                    $uid = explode('|', $account)[0];
                    $isInsertAPI = $CMSNT->insert("product_sold", [
                        'type'              => $supplier['domain'],
                        'product_code'      => NULL,
                        'supplier_id'       => $product['supplier_id'],
                        'trans_id'          => $trans_id,
                        'buyer'             => $getUser['id'],
                        'seller'            => $product['user_id'],
                        'uid'               => $uid,
                        'account'           => $account,
                        'create_gettime'    => gettime()
                    ]);
                    if ($isInsertAPI) {
                        $isValue++;
                    }
                }
            }
            //
            if ($supplier['type'] == 'API_17') {
                $data = buy_API_17($supplier['domain'], $supplier['username'], $supplier['password'], $product['api_id'], $amount);
                $data = json_decode($data, true);
                if (!isset($data)) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503); // Service Unavailable
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng')]));
                }
                if ($data['status'] == 'error') {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __($data['msg'])]));
                }
                $api_trans_id = $data['data']['trans_id'];
                foreach ($data['data']['lists'] as $account) {
                    $account = check_string($account['account']);
                    $uid = explode('|', $account)[0];
                    $isInsertAPI = $CMSNT->insert("product_sold", [
                        'type'              => $supplier['domain'],
                        'product_code'      => NULL,
                        'supplier_id'       => $product['supplier_id'],
                        'trans_id'          => $trans_id,
                        'buyer'             => $getUser['id'],
                        'seller'            => $product['user_id'],
                        'uid'               => $uid,
                        'account'           => $account,
                        'create_gettime'    => gettime()
                    ]);
                    if ($isInsertAPI) {
                        $isValue++;
                    }
                }
            }
            //
            if ($supplier['type'] == 'API_18') {
                $response = buy_API_18($supplier['domain'], $supplier['api_key'], $product['api_id'], $amount);
                $data = json_decode($response, true);
                if (!isset($data)) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503); // Service Unavailable
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng')]));
                }
                if (isset($data['error'])) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __('Số lượng còn lại trong hệ thống không đủ')]));
                }
                $api_trans_id = $data['Data']['TransId'];
                foreach ($data['Data']['Emails'] as $account) {
                    $isInsertAPI = $CMSNT->insert("product_sold", [
                        'type'              => $supplier['domain'],
                        'product_code'      => NULL,
                        'supplier_id'       => $product['supplier_id'],
                        'trans_id'          => $trans_id,
                        'buyer'             => $getUser['id'],
                        'seller'            => $product['user_id'],
                        'uid'               => $account['Email'],
                        'account'           => $account['Email'] . '|' . $account['Password'] . '|' . $account['RefreshToken'] . '|' . $account['AccessToken'] . '|' . $account['ClientId'],
                        'create_gettime'    => gettime()
                    ]);
                    if ($isInsertAPI) {
                        $isValue++;
                    }
                }
            }
            //
            if ($supplier['type'] == 'API_19') {
                $response = buy_API_19($supplier['domain'], $supplier['api_key'], $product['api_id'], $amount);
                $data = json_decode($response, true);
                if (!isset($data)) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503); // Service Unavailable
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng')]));
                }
                if ($data['error_code'] != 200) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __($data['message'])]));
                }
                $api_trans_id = $data['data']['order_code'];
                foreach ($data['data']['list_data'] as $account) {
                    $isInsertAPI = $CMSNT->insert("product_sold", [
                        'type'              => $supplier['domain'],
                        'product_code'      => NULL,
                        'supplier_id'       => $product['supplier_id'],
                        'trans_id'          => $trans_id,
                        'buyer'             => $getUser['id'],
                        'seller'            => $product['user_id'],
                        'uid'               => explode('|', $account)[0],
                        'account'           => $account,
                        'create_gettime'    => gettime()
                    ]);
                    if ($isInsertAPI) {
                        $isValue++;
                    }
                }
            }
            //
            if ($supplier['type'] == 'API_20') {
                $response = curl_get($supplier['domain'] . 'api/buyProducts?kioskToken=' . $supplier['api_key'] . '&userToken=' . $supplier['token'] . '&quantity=' . $amount);
                $data = json_decode($response, true);
                if (!isset($data)) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503); // Service Unavailable
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng')]));
                }
                if ($data['success'] != true) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __($data['description'])]));
                }
                $api_trans_id = $data['order_id'];
                sleep(5);
                $response = curl_get($supplier['domain'] . 'api/getProducts?orderId=' . $api_trans_id . '&userToken=' . $supplier['token']);
                $result = json_decode($response, true);
                if ($result['success'] == true) {
                    if (isset($result['data'])) {
                        foreach ($result['data'] as $account) {
                            $account = check_string($account['product']);
                            $uid = explode('|', $account)[0];
                            $isInsertAPI = $CMSNT->insert("product_sold", [
                                'type'              => $supplier['domain'],
                                'product_code'      => NULL,
                                'supplier_id'       => $product['supplier_id'],
                                'trans_id'          => $trans_id,
                                'buyer'             => $getUser['id'],
                                'seller'            => $product['user_id'],
                                'uid'               => $uid,
                                'account'           => $account,
                                'create_gettime'    => gettime()
                            ]);
                            if ($isInsertAPI) {
                                $isValue++;
                            }
                        }
                    } else {
                        die(json_encode(['status' => 'error', 'msg' => __($data)]));
                    }
                } else {
                    die(json_encode(['status' => 'error', 'msg' => __($data['description'])]));
                }
            }
            //
            if ($supplier['type'] == 'API_21') {
                $response = buy_API_21($supplier['domain'], $supplier['token'], $product['api_id'], $amount);
                $data = json_decode($response, true);
                if (!isset($data)) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503); // Service Unavailable
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng')]));
                }
                if ($data['status'] != true) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __($data['message'])]));
                }
                $api_trans_id = NULL;
                foreach ($data['data'] as $account) {
                    $isInsertAPI = $CMSNT->insert("product_sold", [
                        'type'              => $supplier['domain'],
                        'product_code'      => NULL,
                        'supplier_id'       => $product['supplier_id'],
                        'trans_id'          => $trans_id,
                        'buyer'             => $getUser['id'],
                        'seller'            => $product['user_id'],
                        'uid'               => explode('|', $account)[0],
                        'account'           => $account,
                        'create_gettime'    => gettime()
                    ]);
                    if ($isInsertAPI) {
                        $isValue++;
                    }
                }
            }
            //
            if ($supplier['type'] == 'API_22') {
                $response = buy_API_22($supplier['domain'], $supplier['token'], $product['api_id'], $amount);
                $data = json_decode($response, true);
                if (!isset($data)) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503); // Service Unavailable
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng')]));
                }
                if ($data['status'] != 'success') {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __($data['message'])]));
                }
                $api_trans_id = NULL;
                foreach ($data['data'] as $account) {
                    $isInsertAPI = $CMSNT->insert("product_sold", [
                        'type'              => $supplier['domain'],
                        'product_code'      => NULL,
                        'supplier_id'       => $product['supplier_id'],
                        'trans_id'          => $trans_id,
                        'buyer'             => $getUser['id'],
                        'seller'            => $product['user_id'],
                        'uid'               => $account['email'],
                        'account'           => $account['email'] . '|' . $account['password'] . '|' . $account['refresh_token'] . '|' . $account['client_id'],
                        'create_gettime'    => gettime()
                    ]);
                    if ($isInsertAPI) {
                        $isValue++;
                    }
                }
            }
            //
            if ($supplier['type'] == 'API_23') {
                $response = buy_API_23($supplier['domain'], $supplier['api_key'], $product['api_id'], $amount);
                $data = json_decode($response, true);
                if (!isset($data)) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503); // Service Unavailable
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng')]));
                }
                if (isset($data['Code']) && $data['Code'] == 1) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __('Số lượng còn lại trong hệ thống không đủ')]));
                }
                $api_trans_id = $data['PurchaseId'];
                foreach ($data['Accounts'] as $account) {
                    $isInsertAPI = $CMSNT->insert("product_sold", [
                        'type'              => $supplier['domain'],
                        'product_code'      => NULL,
                        'supplier_id'       => $product['supplier_id'],
                        'trans_id'          => $trans_id,
                        'buyer'             => $getUser['id'],
                        'seller'            => $product['user_id'],
                        'uid'               => $account['Email'],
                        'account'           => $account['Email'] . '|' . $account['Password'] . '|' . $account['RefreshToken'] . '|' . $account['ClientId'],
                        'create_gettime'    => gettime()
                    ]);
                    if ($isInsertAPI) {
                        $isValue++;
                    }
                }
            }
            //
            if ($supplier['type'] == 'API_24') {
                $response = buy_API_24($supplier['domain'], $supplier['api_key'], $product['api_id'], $amount);
                $data = json_decode($response, true);
                if (!isset($data)) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503); // Service Unavailable
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng')]));
                }
                if (!isset($data['data'])) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __('Số lượng còn lại trong hệ thống không đủ')]));
                }
                foreach ($data['data'] as $account) {
                    $api_trans_id = $account['order_id'];
                    $isInsertAPI = $CMSNT->insert("product_sold", [
                        'type'              => $supplier['domain'],
                        'product_code'      => NULL,
                        'supplier_id'       => $product['supplier_id'],
                        'trans_id'          => $trans_id,
                        'buyer'             => $getUser['id'],
                        'seller'            => $product['user_id'],
                        'uid'               => $account['email'],
                        'account'           => $account['email'] . '|' . $account['password'],
                        'create_gettime'    => gettime()
                    ]);
                    if ($isInsertAPI) {
                        $isValue++;
                    }
                }
            }
            //
            if ($supplier['type'] == 'API_25') {
                $response = buy_API_25($supplier['domain'], $supplier['api_key'], $product['api_id'], $amount);
                $data = json_decode($response, true);
                if (!isset($data)) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503); // Service Unavailable
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng')]));
                }
                if (!isset($data['Data']) || $data['Code'] == 1) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __('Số lượng còn lại trong hệ thống không đủ')]));
                }
                $api_trans_id = $data['Data']['PurchaseId'];
                foreach ($data['Data']['Accounts'] as $account) {
                    $isInsertAPI = $CMSNT->insert("product_sold", [
                        'type'              => $supplier['domain'],
                        'product_code'      => NULL,
                        'supplier_id'       => $product['supplier_id'],
                        'trans_id'          => $trans_id,
                        'buyer'             => $getUser['id'],
                        'seller'            => $product['user_id'],
                        'uid'               => $account['Email'],
                        'account'           => $account['Email'] . '|' . $account['Password'] . '|' . $account['RefreshToken'] . '|' . $account['ClientId'],
                        'create_gettime'    => gettime()
                    ]);
                    if ($isInsertAPI) {
                        $isValue++;
                    }
                }
            }
            //
            if ($supplier['type'] == 'API_26') {
                $response = buy_API_26($supplier['domain'], $supplier['api_key'], $supplier['token'], $product['api_id'], $amount);
                $data = json_decode($response, true);
                if (!isset($data)) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503); // Service Unavailable
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng')]));
                }
                if (!isset($data['status']) || $data['status'] != 'ok') {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __($data['error'])]));
                }
                $api_trans_id = $data['invoice'];
                $response = getOrder_API_26($supplier['domain'], $supplier['api_key'], $supplier['token'], $api_trans_id);
                foreach (explode("\n", $response) as $account) {
                    if ($account == '') {
                        continue;
                    }
                    $uid = explode(" ", $account);
                    $isInsertAPI = $CMSNT->insert("product_sold", [
                        'type'              => $supplier['domain'],
                        'product_code'      => NULL,
                        'supplier_id'       => $product['supplier_id'],
                        'trans_id'          => $trans_id,
                        'buyer'             => $getUser['id'],
                        'seller'            => $product['user_id'],
                        'uid'               => $uid[0],
                        'account'           => str_replace(":", "|", $account),
                        'create_gettime'    => gettime()
                    ]);
                    if ($isInsertAPI) {
                        $isValue++;
                    }
                }
            }
            //

            //
            if ($supplier['type'] == 'API_28') {
                $data = buy_API_28($supplier['domain'], $supplier['token'], $product['api_id'], $amount);
                $data = json_decode($data, true);
                if (!isset($data)) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503); // Service Unavailable
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng')]));
                }
                if ($data['status'] != 'success') {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __($data['message'])]));
                }
                $api_trans_id = $data['data']['order_code'];
                $accounts = json_decode($data['data']['account'], true);
                if (is_array($accounts)) {
                    foreach ($accounts as $account) {
                        $account = check_string($account);
                        $uid = explode('|', $account)[0];
                        $isInsertAPI = $CMSNT->insert("product_sold", [
                            'type'              => $supplier['domain'],
                            'product_code'      => NULL,
                            'supplier_id'       => $product['supplier_id'],
                            'trans_id'          => $trans_id,
                            'buyer'             => $getUser['id'],
                            'seller'            => $product['user_id'],
                            'uid'               => $uid,
                            'account'           => $account,
                            'create_gettime'    => gettime()
                        ]);
                        if ($isInsertAPI) {
                            $isValue++;
                        }
                    }
                }
            }
            //
            if ($supplier['type'] == 'API_29') {
                $response = buy_API_29($supplier['domain'], $supplier['api_key'], $product['api_id'], $amount);
                $data = json_decode($response, true);
                if (!isset($data)) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503); // Service Unavailable
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng')]));
                }
                if (!isset($data['data']) || $data['code'] != 0) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __(check_string($data['message']))]));
                }
                $api_trans_id = NULL;
                foreach ($data['data'] as $account) {
                    $account = check_string($account);
                    $account = str_replace(":", "|", $account);
                    $isInsertAPI = $CMSNT->insert("product_sold", [
                        'type'              => $supplier['domain'],
                        'product_code'      => NULL,
                        'supplier_id'       => $product['supplier_id'],
                        'trans_id'          => $trans_id,
                        'buyer'             => $getUser['id'],
                        'seller'            => $product['user_id'],
                        'uid'               => explode('|', $account)[0],
                        'account'           => $account,
                        'create_gettime'    => gettime()
                    ]);
                    if ($isInsertAPI) {
                        $isValue++;
                    }
                }
            }
            //
            if ($supplier['type'] == 'API_30') {
                $response = buy_API_30($supplier['domain'], $supplier['api_key'], $product['api_id'], $amount);

                // Kiểm tra nếu response là JSON có status error
                $data = json_decode($response, true);
                if (!isset($data)) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503); // Service Unavailable
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng')]));
                }
                if (is_array($data) && (isset($data['status']) && $data['status'] == -1)) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __(check_string($data['msg']))]));
                }

                $api_trans_id = NULL;
                // Xử lý response như text, tách từng dòng
                $lines = explode("\n", trim($response));
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;

                    // Thay "----" thành "|"
                    $account = str_replace("----", "|", $line);
                    $account = check_string($account);

                    $isInsertAPI = $CMSNT->insert("product_sold", [
                        'type'              => $supplier['domain'],
                        'product_code'      => NULL,
                        'supplier_id'       => $product['supplier_id'],
                        'trans_id'          => $trans_id,
                        'buyer'             => $getUser['id'],
                        'seller'            => $product['user_id'],
                        'uid'               => explode('|', $account)[0],
                        'account'           => $account,
                        'create_gettime'    => gettime()
                    ]);
                    if ($isInsertAPI) {
                        $isValue++;
                    }
                }
            }
            //
            if ($supplier['type'] == 'API_31') {
                $data = buy_API_31($supplier['domain'], $supplier['coupon'], $supplier['api_key'], $product['api_id'], $amount, $supplier['proxy']);
                $data = json_decode($data, true);
                if (!isset($data)) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503); // Service Unavailable
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng')]));
                }
                if ($data['status'] == 'error') {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __($data['msg'])]));
                }
                $api_trans_id = $data['trans_id'];
                foreach ($data['data'] as $account) {
                    $account = check_string($account);
                    $uid = explode('|', $account)[0];
                    $isInsertAPI = $CMSNT->insert("product_sold", [
                        'type'              => $supplier['domain'],
                        'product_code'      => NULL,
                        'supplier_id'       => $product['supplier_id'],
                        'trans_id'          => $trans_id,
                        'buyer'             => $getUser['id'],
                        'seller'            => $product['user_id'],
                        'uid'               => $uid,
                        'account'           => $account,
                        'create_gettime'    => gettime()
                    ]);
                    if ($isInsertAPI) {
                        $isValue++;
                    }
                }
            }
            if ($supplier['type'] == 'API_32') {
                $data = buy_API_32($supplier['domain'], $supplier['api_key'], $product['api_id'], $amount, $supplier['proxy']);
                $data = json_decode($data, true);
                if (!isset($data)) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503); // Service Unavailable
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng')]));
                }
                if ($data['success'] != true) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __($data['message'])]));
                }
                $api_trans_id = $data['data']['trans_id'];
                foreach ($data['data']['accounts'] as $account) {
                    $account = check_string($account);
                    $uid = explode('|', $account)[0];
                    $isInsertAPI = $CMSNT->insert("product_sold", [
                        'type'              => $supplier['domain'],
                        'product_code'      => NULL,
                        'supplier_id'       => $product['supplier_id'],
                        'trans_id'          => $trans_id,
                        'buyer'             => $getUser['id'],
                        'seller'            => $product['user_id'],
                        'uid'               => $uid,
                        'account'           => $account,
                        'create_gettime'    => gettime()
                    ]);
                    if ($isInsertAPI) {
                        $isValue++;
                    }
                }
            }
            if ($supplier['type'] == 'API_33') {
                // Bước 1: Tạo invoice (mua activation codes)
                $data = buy_API_33($supplier['domain'], $supplier['token'], $product['api_id'], $amount, $supplier['proxy']);
                $data = json_decode($data, true);

                // Xử lý lỗi kết nối
                if (!isset($data)) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503);
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng')]));
                }

                // Xử lý lỗi từ API
                if ($data['code'] != '200000') {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __($data['message'])]));
                }

                // Lấy invoice code từ response
                $api_trans_id = $data['data']['code'];

                // Chờ API xử lý
                sleep(3);

                // Bước 2: Lấy danh sách activation codes
                $result_accounts = getInvoiceAPI_33($supplier['domain'], $supplier['token'], $api_trans_id, $supplier['proxy']);
                $result_accounts = json_decode($result_accounts, true);

                // Dữ liệu cơ bản cho product_sold (tránh lặp lại)
                $baseProductSoldData = [
                    'type'           => $supplier['domain'],
                    'product_code'   => NULL,
                    'supplier_id'    => $product['supplier_id'],
                    'trans_id'       => $trans_id,
                    'buyer'          => $getUser['id'],
                    'seller'         => $product['user_id'],
                    'create_gettime' => gettime()
                ];

                // Helper function để insert product_sold và tăng $isValue
                $insertProductSold = function ($uid, $account) use ($CMSNT, $baseProductSoldData, &$isValue) {
                    $data = array_merge($baseProductSoldData, [
                        'uid'     => $uid,
                        'account' => $account
                    ]);
                    if ($CMSNT->insert("product_sold", $data)) {
                        $isValue++;
                        return true;
                    }
                    return false;
                };

                // Xử lý response lấy activation codes
                if (isset($result_accounts) && $result_accounts['code'] == '200000') {
                    // Kiểm tra có data không
                    if (!isset($result_accounts['data']['data']) || empty($result_accounts['data']['data'])) {
                        // Không có activation codes -> ghi log lỗi
                        $insertProductSold(time() . rand(1000, 9999), __('[Error 2] Vui lòng liên hệ Admin để nhận Activation Code'));
                    } else {
                        // Lặp qua từng activation code
                        foreach ($result_accounts['data']['data'] as $account_item) {
                            $uid = check_string($account_item['code']);
                            $account_text = 'Activation Code: ' . $uid;

                            // Insert activation code, nếu thất bại thì insert error record
                            if (!$insertProductSold($uid, $account_text)) {
                                $insertProductSold(time() . rand(1000, 9999), __('[Error 1] Vui lòng liên hệ Admin để nhận Activation Code'));
                            }
                        }
                    }
                } else {
                    // Lấy activation codes thất bại -> ghi log
                    $insertProductSold(time() . rand(1000, 9999), __('Vui lòng liên hệ Admin để nhận Activation Code'));
                }
            }
            //
            if ($supplier['type'] == 'API_34') {
                $response = buy_API_34($supplier['domain'], $supplier['api_key'], $product['api_id'], $amount, $supplier['proxy']);
                $data = json_decode($response, true);

                // Xử lý lỗi kết nối
                if (!isset($data)) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503);
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng')]));
                }

                // Xử lý lỗi từ API
                if (!isset($data['success']) || $data['success'] != true) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    $errorMsg = isset($data['message']) ? $data['message'] : 'Lỗi không xác định từ API';
                    die(json_encode(['status' => 'error', 'msg' => __($errorMsg)]));
                }

                $api_trans_id = NULL;

                // Lặp qua từng account trong accountData
                foreach ($data['data']['accountData'] as $account) {
                    $account = check_string($account);
                    $uid = explode('|', $account)[0];
                    $isInsertAPI = $CMSNT->insert("product_sold", [
                        'type'              => $supplier['domain'],
                        'product_code'      => NULL,
                        'supplier_id'       => $product['supplier_id'],
                        'trans_id'          => $trans_id,
                        'buyer'             => $getUser['id'],
                        'seller'            => $product['user_id'],
                        'uid'               => $uid,
                        'account'           => $account,
                        'create_gettime'    => gettime()
                    ]);
                    if ($isInsertAPI) {
                        $isValue++;
                    }
                }
            }
            //
            if ($supplier['type'] == 'API_35') {
                // api_key lưu email, token lưu token
                $response = buy_API_35($supplier['domain'], $supplier['api_key'], $supplier['token'], $product['api_id'], $amount, $supplier['proxy']);
                $data = json_decode($response, true);

                // Xử lý lỗi kết nối
                if (!isset($data)) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503);
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng')]));
                }

                // Xử lý lỗi từ API
                if (!isset($data['success']) || $data['success'] != true) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    $errorMsg = isset($data['message']) ? $data['message'] : 'Lỗi không xác định từ API';
                    die(json_encode(['status' => 'error', 'msg' => __($errorMsg)]));
                }

                // Lấy invoice_id làm trans_id
                $api_trans_id = isset($data['data']['invoice_id']) ? $data['data']['invoice_id'] : NULL;

                // Xử lý card_content - có thể chứa nhiều dòng với \r\n
                $card_content = isset($data['data']['card_content']) ? $data['data']['card_content'] : '';
                $lines = preg_split('/\r\n|\r|\n/', trim($card_content));

                foreach ($lines as $account) {
                    $account = trim($account);
                    if (empty($account)) continue;

                    $account = check_string($account);
                    $uid = explode('|', $account)[0];
                    $isInsertAPI = $CMSNT->insert("product_sold", [
                        'type'              => $supplier['domain'],
                        'product_code'      => NULL,
                        'supplier_id'       => $product['supplier_id'],
                        'trans_id'          => $trans_id,
                        'buyer'             => $getUser['id'],
                        'seller'            => $product['user_id'],
                        'uid'               => $uid,
                        'account'           => $account,
                        'create_gettime'    => gettime()
                    ]);
                    if ($isInsertAPI) {
                        $isValue++;
                    }
                }
            }
            //
            // API_36 - humkt.com
            if ($supplier['type'] == 'API_36') {
                // Bước 1: Tạo đơn hàng
                $response = buy_API_36($supplier['domain'], $supplier['token'], $product['api_id'], $amount, $supplier['proxy']);
                $data = json_decode($response, true);

                // Xử lý lỗi kết nối
                if (!isset($data)) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503);
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng')]));
                }

                // Xử lý lỗi từ API
                if (!isset($data['code']) || $data['code'] != 1) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    $errorMsg = isset($data['message']) ? $data['message'] : 'Lỗi không xác định từ API';
                    die(json_encode(['status' => 'error', 'msg' => __($errorMsg)]));
                }

                // Lấy order ID từ response
                $api_trans_id = isset($data['data']['id']) ? $data['data']['id'] : NULL;

                // Chờ API xử lý
                sleep(2);

                // Bước 2: Lấy chi tiết đơn hàng
                $order_response = getOrder_API_36($supplier['domain'], $supplier['token'], $api_trans_id, $supplier['proxy']);
                $order_data = json_decode($order_response, true);

                if (isset($order_data) && $order_data['code'] == 1 && isset($order_data['data']['items'])) {
                    // Lặp qua từng item
                    foreach ($order_data['data']['items'] as $account) {
                        $account = check_string($account);
                        $uid = explode('|', $account)[0];
                        $isInsertAPI = $CMSNT->insert("product_sold", [
                            'type'              => $supplier['domain'],
                            'product_code'      => NULL,
                            'supplier_id'       => $product['supplier_id'],
                            'trans_id'          => $trans_id,
                            'buyer'             => $getUser['id'],
                            'seller'            => $product['user_id'],
                            'uid'               => $uid,
                            'account'           => $account,
                            'create_gettime'    => gettime()
                        ]);
                        if ($isInsertAPI) {
                            $isValue++;
                        }
                    }
                } else {
                    // Nếu không lấy được order detail, hoàn tiền
                    $User->RefundCredits($getUser['id'], $pay, "[Error 2] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __('Không thể lấy chi tiết đơn hàng từ API')]));
                }
            }
            //
            // API_37
            if ($supplier['type'] == 'API_37') {
                // Gọi API mua hàng
                $response = buy_API_37($supplier['domain'], $supplier['token'], $product['api_id'], $amount, $supplier['proxy']);
                $data = json_decode($response, true);

                // Xử lý lỗi kết nối
                if (!isset($data)) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503);
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng')]));
                }

                // Xử lý lỗi từ API
                if (!isset($data['status']) || $data['status'] != 1) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    $errorMsg = isset($data['message']) ? $data['message'] : 'Lỗi không xác định từ API';
                    die(json_encode(['status' => 'error', 'msg' => __($errorMsg)]));
                }

                // Lấy order_id làm trans_id
                $api_trans_id = isset($data['order_id']) ? $data['order_id'] : NULL;

                // Xử lý data - chứa các keys, có thể nhiều dòng
                $keys_data = isset($data['data']) ? $data['data'] : '';
                $lines = preg_split('/\r\n|\r|\n/', trim($keys_data));

                foreach ($lines as $account) {
                    $account = trim($account);
                    if (empty($account)) continue;

                    $account = check_string($account);
                    $uid = explode('|', $account)[0];
                    $isInsertAPI = $CMSNT->insert("product_sold", [
                        'type'              => $supplier['domain'],
                        'product_code'      => NULL,
                        'supplier_id'       => $product['supplier_id'],
                        'trans_id'          => $trans_id,
                        'buyer'             => $getUser['id'],
                        'seller'            => $product['user_id'],
                        'uid'               => $uid,
                        'account'           => $account,
                        'create_gettime'    => gettime()
                    ]);
                    if ($isInsertAPI) {
                        $isValue++;
                    }
                }
            }
            //
            // API_38 - API Shared (Partner API với MD5 Signature)
            if ($supplier['type'] == 'API_38') {
                // API_38 sử dụng api_key làm app_id và token làm app_key
                $app_id = $supplier['api_key'];
                $app_key = $supplier['token'];

                // Gọi API mua hàng (trade)
                $response = buy_API_38($supplier['domain'], $app_id, $app_key, $product['api_id'], $amount, $supplier['proxy']);
                $data = json_decode($response, true);

                // Debug log nếu cần
                if ($CMSNT->site('debug_api_suppliers') == 1) {
                    error_log("API_38 Buy Response: " . $response);
                }

                // Xử lý lỗi kết nối
                if (!isset($data)) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503);
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng')]));
                }

                // Xử lý lỗi từ API (code != 200)
                if (!isset($data['code']) || $data['code'] != 200) {
                    $errorMsg = isset($data['msg']) ? $data['msg'] : 'Lỗi không xác định từ API';
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __($errorMsg)]));
                }

                // Lấy tradeNo từ response
                $tradeNo = isset($data['data']['tradeNo']) ? $data['data']['tradeNo'] : null;
                if (!$tradeNo) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 2] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __('Không nhận được mã đơn hàng từ API')]));
                }

                $api_trans_id = $tradeNo;

                // Gọi API query để lấy chi tiết đơn hàng (lấy secret/nội dung giao hàng)
                $orderResponse = getOrder_API_38($supplier['domain'], $app_id, $app_key, $tradeNo, $supplier['proxy']);
                $orderData = json_decode($orderResponse, true);

                // Debug log
                if ($CMSNT->site('debug_api_suppliers') == 1) {
                    error_log("API_38 Order Response: " . $orderResponse);
                }

                if (isset($orderData['code']) && $orderData['code'] == 200 && isset($orderData['data']['secret'])) {
                    // secret chứa nội dung giao hàng (có thể nhiều dòng)
                    $secret = $orderData['data']['secret'];
                    $lines = preg_split('/\r\n|\r|\n/', trim($secret));

                    foreach ($lines as $account) {
                        $account = trim($account);
                        if (empty($account)) continue;

                        $account = check_string($account);
                        $uid = explode('|', $account)[0];
                        $isInsertAPI = $CMSNT->insert("product_sold", [
                            'type'              => $supplier['domain'],
                            'product_code'      => NULL,
                            'supplier_id'       => $product['supplier_id'],
                            'trans_id'          => $trans_id,
                            'buyer'             => $getUser['id'],
                            'seller'            => $product['user_id'],
                            'uid'               => $uid,
                            'account'           => $account,
                            'create_gettime'    => gettime()
                        ]);
                        if ($isInsertAPI) {
                            $isValue++;
                        }
                    }
                } else {
                    // Nếu không lấy được order detail, hoàn tiền
                    $User->RefundCredits($getUser['id'], $pay, "[Error 3] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __('Không thể lấy chi tiết đơn hàng từ API')]));
                }
            }
            //
            // API_39 
            if ($supplier['type'] == 'API_39') {
                // API_39 sử dụng token để xác thực qua header x-api-token
                $api_token = $supplier['token'];
                // Lấy productId và variantId từ chuỗi "productId|variantId" (nếu có)
                $api_id_parts = explode('|', $product['api_id']);
                $datammo_product_id = $api_id_parts[0];
                $datammo_variant_id = isset($api_id_parts[1]) ? $api_id_parts[1] : '';

                // Gọi API mua hàng (POST /api/v1/orders)
                $response = buy_API_39($supplier['domain'], $api_token, $datammo_product_id, $amount, $datammo_variant_id, $supplier['proxy']);
                $data = json_decode($response, true);


                // Xử lý lỗi kết nối
                if (!isset($data)) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503);
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng')]));
                }

                // Xử lý lỗi từ API (success != true)
                if (!isset($data['success']) || $data['success'] != true) {
                    $errorMsg = isset($data['message']) ? $data['message'] : 'Lỗi không xác định từ API';
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __($errorMsg)]));
                }

                // Lấy order ID từ response
                // Nếu data là mảng các order thì lấy order ID của item đầu tiên
                $order_id = '';
                if (isset($data['data'][0]['id'])) {
                    $order_id = $data['data'][0]['id'];
                } elseif (isset($data['data']['_id'])) {
                    $order_id = $data['data']['_id'];
                } elseif (isset($data['data']['id'])) {
                    $order_id = $data['data']['id'];
                }
                $api_trans_id = $order_id;

                if (empty($order_id)) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 2] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __('Không nhận được mã đơn hàng từ API')]));
                }

                // Chờ API xử lý đơn hàng
                sleep(1);

                // Bước 2: Gọi API lấy chi tiết đơn hàng (GET /api/v1/orders/:id)
                $order_response = getOrder_API_39($supplier['domain'], $api_token, $order_id, $supplier['proxy']);
                $order_data = json_decode($order_response, true);

                // Xử lý lỗi kết nối getOrder
                if (!isset($order_data)) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 3] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __('Không thể lấy chi tiết đơn hàng từ API')]));
                }

                // Xử lý lỗi từ getOrder API
                if (!isset($order_data['success']) || $order_data['success'] != true) {
                    $errorMsg = isset($order_data['message']) ? $order_data['message'] : 'Không thể lấy chi tiết đơn hàng';
                    $User->RefundCredits($getUser['id'], $pay, "[Error 4] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __($errorMsg)]));
                }

                // Xử lý deliveredContent từ getOrder response
                // Format: {"user1@email.com": "password123", "user2@email.com": "password456"}
                $deliveredContent = isset($order_data['data']['deliveredContent']) ? $order_data['data']['deliveredContent'] : '';
                $all_lines = [];

                if (is_array($deliveredContent) || is_object($deliveredContent)) {
                    // deliveredContent là object/associative array: {"key": "value", ...}
                    foreach ($deliveredContent as $key => $value) {
                        if (is_array($value)) {
                            // Trường hợp value là array (nested)
                            $val_str = isset($value['value']) ? $value['value'] : json_encode($value);
                            $key_str = isset($value['key']) ? $value['key'] : $key;
                            if (!empty($val_str)) {
                                $all_lines[] = !empty($key_str) ? $key_str . '|' . $val_str : $val_str;
                            }
                        } else {
                            // Key-value pair đơn giản: "user@email.com": "password"
                            if (!empty($value)) {
                                $all_lines[] = $key . '|' . $value;
                            }
                        }
                    }
                } elseif (is_string($deliveredContent) && !empty($deliveredContent)) {
                    // Nếu deliveredContent là chuỗi JSON string
                    $parsed = @json_decode($deliveredContent, true);
                    if (is_array($parsed)) {
                        foreach ($parsed as $key => $value) {
                            if (is_array($value)) {
                                $val_str = isset($value['value']) ? $value['value'] : json_encode($value);
                                $key_str = isset($value['key']) ? $value['key'] : $key;
                                if (!empty($val_str)) {
                                    $all_lines[] = !empty($key_str) ? $key_str . '|' . $val_str : $val_str;
                                }
                            } else {
                                if (!empty($value)) {
                                    $all_lines[] = $key . '|' . $value;
                                }
                            }
                        }
                    } else {
                        // Chuỗi thông thường, tách theo dòng
                        $lines_tmp = preg_split('/\r\n|\r|\n/', trim($deliveredContent));
                        foreach ($lines_tmp as $l) {
                            if (trim($l) != '') $all_lines[] = trim($l);
                        }
                    }
                }

                $content = implode("\n", $all_lines);

                if (empty(trim($content))) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 5] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __('Không nhận được nội dung giao hàng từ API')]));
                }

                $lines = preg_split('/\r\n|\r|\n/', trim($content));
                foreach ($lines as $account) {
                    $account = trim($account);
                    if (empty($account)) continue;

                    $account = check_string($account);
                    $uid = explode('|', $account)[0];
                    $isInsertAPI = $CMSNT->insert("product_sold", [
                        'type'              => $supplier['domain'],
                        'product_code'      => NULL,
                        'supplier_id'       => $product['supplier_id'],
                        'trans_id'          => $trans_id,
                        'buyer'             => $getUser['id'],
                        'seller'            => $product['user_id'],
                        'uid'               => $uid,
                        'account'           => $account,
                        'create_gettime'    => gettime()
                    ]);
                    if ($isInsertAPI) {
                        $isValue++;
                    }
                }
            }
            // API_40 - Telegram Shop API
            // Gọi POST /api/buy với api_key + product_id (position_id) + quantity
            if ($supplier['type'] == 'API_40') {
                $response = buy_API_40($supplier['domain'], $supplier['api_key'], $product['api_id'], $amount, $supplier['proxy']);
                $data = json_decode($response, true);

                // Xử lý lỗi kết nối — cURL trả rỗng hoặc JSON không hợp lệ
                if ($data === null) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503);
                    // Hiển thị lý do cụ thể: nếu API trả text lỗi (VD: "Internal Server Error") thì show ra
                    $errorDetail = ($response === false) ? 'cURL error' : trim(substr($response, 0, 200));
                    $errorDetail = !empty($errorDetail) ? " ($errorDetail)" : '';
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng') . $errorDetail]));
                }

                // Xử lý lỗi từ API — response trả success = false
                if (!isset($data['success']) || $data['success'] != true) {
                    $errorMsg = isset($data['message']) ? $data['message'] : 'Lỗi không xác định từ API';
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __($errorMsg)]));
                }

                $api_trans_id = NULL;

                // Xử lý nội dung giao hàng từ API
                // API 40 trả nội dung qua danh sách URL download (mảng "downloads")
                // VD: {"success": true, "downloads": ["http://127.0.0.1:8000/download/file.txt"]}
                // URL có thể dùng 127.0.0.1/localhost → cần thay bằng domain thực
                $content = '';
                if (isset($data['downloads']) && is_array($data['downloads']) && !empty($data['downloads'])) {
                    // Lấy host:port thực từ domain supplier để thay thế localhost
                    $parsed_supplier = parse_url(rtrim($supplier['domain'], '/'));
                    $supplier_host = $parsed_supplier['host'];
                    $supplier_port = isset($parsed_supplier['port']) ? ':' . $parsed_supplier['port'] : '';

                    foreach ($data['downloads'] as $download_url) {
                        // Thay 127.0.0.1 hoặc localhost bằng IP/domain thực của API
                        $download_url = preg_replace('/127\.0\.0\.1(:\d+)?/', $supplier_host . $supplier_port, $download_url);
                        $download_url = preg_replace('/localhost(:\d+)?/', $supplier_host . $supplier_port, $download_url);

                        // Phân biệt file text (.txt) và file binary (.zip, .rar, .7z, ...)
                        // File text → tải nội dung về parse từng dòng
                        // File binary → lưu URL download trực tiếp để khách tự tải
                        $file_ext = strtolower(pathinfo(parse_url($download_url, PHP_URL_PATH), PATHINFO_EXTENSION));
                        if (in_array($file_ext, ['zip', 'rar', '7z', 'gz', 'tar', 'session', 'json'])) {
                            // File binary/đặc biệt: lưu URL để khách hàng tự download
                            $content .= $download_url . "\n";
                        } else {
                            // File text (.txt hoặc không có extension): tải nội dung
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $download_url);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                            $file_content = curl_exec($ch);
                            curl_close($ch);

                            if (!empty($file_content)) {
                                $content .= $file_content . "\n";
                            }
                        }
                    }
                } elseif (isset($data['data'])) {
                    // Fallback: nếu API trả data trực tiếp (không qua downloads)
                    $content = is_array($data['data']) ? implode("\n", $data['data']) : $data['data'];
                }

                // Kiểm tra nội dung giao hàng — nếu rỗng thì hoàn tiền
                if (empty(trim($content))) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 2] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __('Không nhận được nội dung giao hàng từ API')]));
                }

                // Tách từng dòng nội dung và lưu vào product_sold
                $lines = preg_split('/\r\n|\r|\n/', trim($content));
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;

                    $account = check_string($line);
                    $uid = explode('|', $account)[0];
                    $isInsertAPI = $CMSNT->insert("product_sold", [
                        'type'              => $supplier['domain'],
                        'product_code'      => NULL,
                        'supplier_id'       => $product['supplier_id'],
                        'trans_id'          => $trans_id,
                        'buyer'             => $getUser['id'],
                        'seller'            => $product['user_id'],
                        'uid'               => $uid,
                        'account'           => $account,
                        'create_gettime'    => gettime()
                    ]);
                    if ($isInsertAPI) {
                        $isValue++;
                    }
                }
            }
            // API_41 — Digital Store
            // api_id có thể là "productId" hoặc "productId|variantId" (nếu sản phẩm có variants)
            if ($supplier['type'] == 'API_41') {
                // Tách api_id để lấy productId và variantId (nếu có)
                $api_id_parts = explode('|', $product['api_id']);
                $purchase_product_id = $api_id_parts[0];
                $purchase_variant_id = isset($api_id_parts[1]) ? $api_id_parts[1] : null;

                $response = buy_API_41($supplier['domain'], $supplier['api_key'], $purchase_product_id, $amount, $supplier['proxy'], $purchase_variant_id);
                $data = json_decode($response, true);

                // Xử lý lỗi kết nối — cURL trả rỗng hoặc JSON không hợp lệ
                if ($data === null) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503);
                    // Hiển thị lý do cụ thể: nếu API trả text lỗi thì show ra
                    $errorDetail = ($response === false) ? 'cURL error' : trim(substr($response, 0, 200));
                    $errorDetail = !empty($errorDetail) ? " ($errorDetail)" : '';
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng') . $errorDetail]));
                }

                // Xử lý lỗi từ API — response trả success = false
                if (!isset($data['success']) || $data['success'] != true) {
                    $errorMsg = isset($data['error']) ? $data['error'] : (isset($data['message']) ? $data['message'] : 'Lỗi không xác định từ API');
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __($errorMsg)]));
                }

                // Lưu orderId từ API làm mã giao dịch tham chiếu
                $api_trans_id = isset($data['data']['orderId']) ? $data['data']['orderId'] : NULL;

                // Xử lý nội dung giao hàng từ API
                // API 41 trả deliveredData[] dạng text trực tiếp (khác API 40 trả download URLs)
                // VD: {"data": {"deliveredData": ["account1@email.com|pass123", "account2|pass456"]}}
                $content = '';
                if (isset($data['data']['deliveredData']) && is_array($data['data']['deliveredData']) && !empty($data['data']['deliveredData'])) {
                    $content = implode("\n", $data['data']['deliveredData']);
                }

                // Kiểm tra nội dung giao hàng — nếu rỗng thì hoàn tiền
                if (empty(trim($content))) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 2] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __('Không nhận được nội dung giao hàng từ API')]));
                }

                // Tách từng dòng nội dung và lưu vào product_sold
                $lines = preg_split('/\r\n|\r|\n/', trim($content));
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;

                    $account = check_string($line);
                    $uid = explode('|', $account)[0];
                    $isInsertAPI = $CMSNT->insert("product_sold", [
                        'type'              => $supplier['domain'],
                        'product_code'      => NULL,
                        'supplier_id'       => $product['supplier_id'],
                        'trans_id'          => $trans_id,
                        'buyer'             => $getUser['id'],
                        'seller'            => $product['user_id'],
                        'uid'               => $uid,
                        'account'           => $account,
                        'create_gettime'    => gettime()
                    ]);
                    if ($isInsertAPI) {
                        $isValue++;
                    }
                }
            }
            // API_42 — mail555.com Gmail/Email Accounts Supplier
            // Flow: POST /api/orders (tạo đơn) → poll GET /api/orders/{id}/download (tải accounts)
            if ($supplier['type'] == 'API_42') {
                // Bước 1: Tạo đơn hàng
                $response = buy_API_42($supplier['domain'], $supplier['api_key'], $product['api_id'], $amount, $supplier['proxy']);
                $data = json_decode($response, true);

                // Xử lý lỗi kết nối — cURL trả rỗng hoặc JSON không hợp lệ
                if ($data === null) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503);
                    // Hiển thị lý do cụ thể: nếu API trả text lỗi thì show ra
                    $errorDetail = ($response === false) ? 'cURL error' : trim(substr($response, 0, 200));
                    $errorDetail = !empty($errorDetail) ? " ($errorDetail)" : '';
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng') . $errorDetail]));
                }

                // Xử lý lỗi từ API — response trả success = false
                if (!isset($data['success']) || $data['success'] != true) {
                    $errorMsg = isset($data['error']) ? $data['error'] : (isset($data['message']) ? $data['message'] : 'Lỗi không xác định từ API');
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __($errorMsg)]));
                }

                // Lấy orderId từ response làm mã giao dịch tham chiếu
                $api_trans_id = isset($data['data']['orderId']) ? $data['data']['orderId'] : NULL;
                if (empty($api_trans_id)) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 2] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __('Không nhận được mã đơn hàng từ API')]));
                }

                // Bước 2: Poll download endpoint (đơn trả status=pending nên cần chờ)
                // Chiến lược: sleep 2s → thử download, tối đa 5 lần (~10s tổng cộng)
                // Tránh sleep ngay lần đầu để nếu API xử lý nhanh thì không phải đợi
                $content = '';
                $maxRetries = 5;
                $retryDelay = 2;
                for ($i = 0; $i < $maxRetries; $i++) {
                    // Lần đầu không sleep; các lần sau mới chờ để đỡ lãng phí thời gian
                    if ($i > 0) sleep($retryDelay);

                    $download = getOrder_API_42($supplier['domain'], $supplier['api_key'], $api_trans_id, $supplier['proxy']);
                    $body = isset($download['body']) ? $download['body'] : '';
                    $http_code = isset($download['http_code']) ? intval($download['http_code']) : 0;

                    // HTTP 200 + body có dấu | → gần như chắc chắn là text accounts hợp lệ
                    // (tránh nhầm với JSON error trả HTTP 200)
                    if ($http_code === 200 && !empty(trim($body)) && strpos($body, '|') !== false) {
                        // Double-check: nếu body bắt đầu bằng { thì vẫn là JSON (lỗi), bỏ qua
                        $firstChar = ltrim($body);
                        if (!empty($firstChar) && $firstChar[0] !== '{') {
                            $content = $body;
                            break;
                        }
                    }
                }

                // Kiểm tra nội dung giao hàng — nếu vẫn rỗng sau khi retry thì hoàn tiền
                // Lưu ý: orderId đã được lưu nên admin có thể download thủ công sau nếu API xử lý chậm
                if (empty(trim($content))) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 3] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id . " (Order API: $api_trans_id)", 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __('Đơn hàng chưa được xử lý sau ' . ($maxRetries * $retryDelay) . 's. Mã đơn API: ' . $api_trans_id)]));
                }

                // Tách từng dòng nội dung và lưu vào product_sold
                // Format: email|password|recovery@example.com
                $lines = preg_split('/\r\n|\r|\n/', trim($content));
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;

                    $account = check_string($line);
                    $uid = explode('|', $account)[0];
                    $isInsertAPI = $CMSNT->insert("product_sold", [
                        'type'              => $supplier['domain'],
                        'product_code'      => NULL,
                        'supplier_id'       => $product['supplier_id'],
                        'trans_id'          => $trans_id,
                        'buyer'             => $getUser['id'],
                        'seller'            => $product['user_id'],
                        'uid'               => $uid,
                        'account'           => $account,
                        'create_gettime'    => gettime()
                    ]);
                    if ($isInsertAPI) {
                        $isValue++;
                    }
                }
            }
            // API_43 
            if ($supplier['type'] == 'API_43') {
                // Gọi API tạo đơn hàng BuyFB
                $response = buy_API_43($supplier['domain'], $supplier['token'], $product['api_id'], $amount, $supplier['proxy']);
                $data = json_decode($response, true);

                // Xử lý lỗi kết nối
                if ($data === null) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503);
                    $errorDetail = ($response === false) ? 'cURL error' : trim(substr($response, 0, 200));
                    $errorDetail = !empty($errorDetail) ? " ($errorDetail)" : '';
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng') . $errorDetail]));
                }

                // Xử lý lỗi từ API
                if (!isset($data['success']) || $data['success'] != true) {
                    $errorMsg = isset($data['message']) ? $data['message'] : (isset($data['error']) ? $data['error'] : 'Lỗi không xác định từ API');
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __($errorMsg)]));
                }

                // Kiểm tra xem có mảng orders không
                if (!isset($data['orders']) || !is_array($data['orders']) || empty($data['orders'])) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 2] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __('Không nhận được nội dung giao hàng từ API')]));
                }

                // Lấy trans_id từ đơn hàng nhận được đầu tiên
                $api_trans_id = isset($data['orders'][0]['id']) ? $data['orders'][0]['id'] : NULL;

                // Tiến hành import từng account vào kho
                foreach ($data['orders'] as $order) {
                    if (empty($order['data'])) continue;

                    $account = check_string($order['data']);
                    $uid = explode('|', $account)[0];
                    $isInsertAPI = $CMSNT->insert("product_sold", [
                        'type'              => $supplier['domain'],
                        'product_code'      => NULL,
                        'supplier_id'       => $product['supplier_id'],
                        'trans_id'          => $trans_id,
                        'buyer'             => $getUser['id'],
                        'seller'            => $product['user_id'],
                        'uid'               => $uid,
                        'account'           => $account,
                        'create_gettime'    => gettime()
                    ]);
                    if ($isInsertAPI) {
                        $isValue++;
                    }
                }
            }
            // API_44 - Buyer API (X-Buyer-Key header authentication)
            // Endpoint: POST /api/buyer/order → trả về items ngay lập tức (không cần getOrder)
            // Response: {"order_id": int, "items": [{"product": "email|pass|..."}], "total": float, "new_balance": float}
            if ($supplier['type'] == 'API_44') {
                // Gọi API mua hàng — api_id lưu là product_id integer của Buyer API
                $response = buy_API_44($supplier['domain'], $supplier['token'], $product['api_id'], $amount, $supplier['proxy']);
                $data = json_decode($response, true);

                // Xử lý lỗi kết nối — cURL thất bại hoặc API trả JSON không hợp lệ
                if ($data === null) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503);
                    $errorDetail = ($response === false) ? 'cURL error' : trim(substr($response, 0, 200));
                    $errorDetail = !empty($errorDetail) ? " ($errorDetail)" : '';
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng') . $errorDetail]));
                }

                // Xử lý lỗi từ API — response báo lỗi (có trường "error")
                // Buyer API trả lỗi dạng {"error": "Insufficient balance"} khi không đủ số dư
                if (isset($data['error'])) {
                    $errorMsg = $data['error'];
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __($errorMsg)]));
                }

                // Kiểm tra response có mảng items hợp lệ không
                // Mỗi item có trường "product" chứa nội dung tài khoản (email|pass|recovery)
                if (!isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 2] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __('Không nhận được nội dung giao hàng từ API')]));
                }

                // Lưu order_id từ API làm mã giao dịch tham chiếu
                $api_trans_id = isset($data['order_id']) ? (string)$data['order_id'] : NULL;

                // Import từng tài khoản từ mảng items vào kho product_sold
                // Mỗi item có trường "product" là nội dung tài khoản (email|pass|recovery)
                foreach ($data['items'] as $item) {
                    // Bỏ qua item rỗng hoặc không có trường "product"
                    if (!isset($item['product']) || empty(trim($item['product']))) continue;

                    $account = check_string(trim($item['product']));
                    // uid là phần đầu trước dấu | (thường là email hoặc username)
                    $uid = explode('|', $account)[0];

                    $isInsertAPI = $CMSNT->insert("product_sold", [
                        'type'           => $supplier['domain'],
                        'product_code'   => NULL,
                        'supplier_id'    => $product['supplier_id'],
                        'trans_id'       => $trans_id,
                        'buyer'          => $getUser['id'],
                        'seller'         => $product['user_id'],
                        'uid'            => $uid,
                        'account'        => $account,
                        'create_gettime' => gettime()
                    ]);
                    if ($isInsertAPI) {
                        $isValue++;
                    }
                }
            }
            // API_45 
            // Body JSON: {key, product_id (string ObjectId), quantity}
            // Response: {success, orderCode, deliveredAccounts: [{user, password, verifyEmail}]}
            // Khác API44: deliveredAccounts là mảng object (không phải string), cần ghép thủ công
            if ($supplier['type'] == 'API_45') {
                $response = buy_API_45('', $supplier['token'], $product['api_id'], $amount, $supplier['proxy']);
                $data = json_decode($response, true);

                // Xử lý lỗi kết nối — cURL thất bại hoặc JSON không hợp lệ
                if ($data === null) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503);
                    $errorDetail = ($response === false) ? 'cURL error' : trim(substr($response, 0, 200));
                    $errorDetail = !empty($errorDetail) ? " ($errorDetail)" : '';
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng') . $errorDetail]));
                }

                // Xử lý lỗi từ API — response trả {success: false, message: "..."}
                if (!isset($data['success']) || $data['success'] != true) {
                    $errorMsg = isset($data['message']) ? $data['message'] : 'Lỗi không xác định từ API';
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __($errorMsg)]));
                }

                // Kiểm tra mảng deliveredAccounts hợp lệ
                if (!isset($data['deliveredAccounts']) || !is_array($data['deliveredAccounts']) || empty($data['deliveredAccounts'])) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 2] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __('Không nhận được nội dung giao hàng từ API')]));
                }

                // Lưu orderCode từ API làm mã giao dịch tham chiếu
                $api_trans_id = isset($data['orderCode']) ? check_string($data['orderCode']) : NULL;

                // Import từng tài khoản từ deliveredAccounts vào kho product_sold
                // Mỗi account là object {user, password, verifyEmail}
                // Ghép thành chuỗi "user|password|verifyEmail" để lưu vào trường account
                foreach ($data['deliveredAccounts'] as $acc) {
                    // Bỏ qua nếu không có trường user (tài khoản không hợp lệ)
                    if (!isset($acc['user']) || empty(trim($acc['user']))) continue;

                    $user_part    = check_string(trim($acc['user']));
                    $pass_part    = isset($acc['password']) ? check_string(trim($acc['password'])) : '';
                    $recover_part = (isset($acc['verifyEmail']) && !empty($acc['verifyEmail'])) ? check_string(trim($acc['verifyEmail'])) : '';

                    // Ghép theo format chuẩn: email|password|recovery (bỏ phần rỗng cuối)
                    $account_parts = array_filter([$user_part, $pass_part, $recover_part], function ($v) {
                        return $v !== '';
                    });
                    $account = implode('|', $account_parts);

                    $isInsertAPI = $CMSNT->insert("product_sold", [
                        'type'           => $supplier['domain'] ?: 'API_45',
                        'product_code'   => NULL,
                        'supplier_id'    => $product['supplier_id'],
                        'trans_id'       => $trans_id,
                        'buyer'          => $getUser['id'],
                        'seller'         => $product['user_id'],
                        'uid'            => $user_part,
                        'account'        => $account,
                        'create_gettime' => gettime()
                    ]);
                    if ($isInsertAPI) {
                        $isValue++;
                    }
                }
            }
            // API_50 
            // Body JSON: {key, product_id (string ObjectId), quantity}
            // Response: {success, orderCode, deliveredAccounts: [{user, password, verifyEmail}]}
            // Tương tự API_45 nhưng gọi buy_API_50 với domain tùy chọn từ DB ($supplier['domain'])
            if ($supplier['type'] == 'API_50') {
                $response = buy_API_50($supplier['domain'], $supplier['token'], $product['api_id'], $amount, $supplier['proxy']);
                $data = json_decode($response, true);

                // Xử lý lỗi kết nối — cURL thất bại hoặc JSON không hợp lệ
                if ($data === null) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503);
                    $errorDetail = ($response === false) ? 'cURL error' : trim(substr($response, 0, 200));
                    $errorDetail = !empty($errorDetail) ? " ($errorDetail)" : '';
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng') . $errorDetail]));
                }

                // Xử lý lỗi từ API — response trả {success: false, message: "..."}
                if (!isset($data['success']) || $data['success'] != true) {
                    $errorMsg = isset($data['message']) ? $data['message'] : 'Lỗi không xác định từ API';
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __($errorMsg)]));
                }

                // Kiểm tra mảng deliveredAccounts hợp lệ
                if (!isset($data['deliveredAccounts']) || !is_array($data['deliveredAccounts']) || empty($data['deliveredAccounts'])) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 2] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __('Không nhận được nội dung giao hàng từ API')]));
                }

                // Lưu orderCode từ API làm mã giao dịch tham chiếu
                $api_trans_id = isset($data['orderCode']) ? check_string($data['orderCode']) : NULL;

                // Import từng tài khoản từ deliveredAccounts vào kho product_sold
                // Mỗi account là object {user, password, verifyEmail}
                // Ghép thành chuỗi "user|password|verifyEmail" để lưu vào trường account
                foreach ($data['deliveredAccounts'] as $acc) {
                    // Bỏ qua nếu không có trường user (tài khoản không hợp lệ)
                    if (!isset($acc['user']) || empty(trim($acc['user']))) continue;

                    $user_part    = check_string(trim($acc['user']));
                    $pass_part    = isset($acc['password']) ? check_string(trim($acc['password'])) : '';
                    $recover_part = (isset($acc['verifyEmail']) && !empty($acc['verifyEmail'])) ? check_string(trim($acc['verifyEmail'])) : '';

                    // Ghép theo format chuẩn: email|password|recovery (bỏ phần rỗng cuối)
                    $account_parts = array_filter([$user_part, $pass_part, $recover_part], function ($v) {
                        return $v !== '';
                    });
                    $account = implode('|', $account_parts);

                    $isInsertAPI = $CMSNT->insert("product_sold", [
                        'type'           => $supplier['domain'] ?: 'API_50',
                        'product_code'   => NULL,
                        'supplier_id'    => $product['supplier_id'],
                        'trans_id'       => $trans_id,
                        'buyer'          => $getUser['id'],
                        'seller'         => $product['user_id'],
                        'uid'            => $user_part,
                        'account'        => $account,
                        'create_gettime' => gettime()
                    ]);
                    if ($isInsertAPI) {
                        $isValue++;
                    }
                }
            }
            // API_51 - Nas Nabi API
            // Body: {productId, quantity}
            // Headers: {X-API-Key: api_key}
            // Response: {ok, order: {orderCode, shopOrderId, product, qty, price, total, status, balanceAfter, accounts: [...]}}
            if ($supplier['type'] == 'API_51') {
                $response = buy_API_51($supplier['domain'], $supplier['api_key'], $product['api_id'], $amount, $supplier['proxy']);
                $data = json_decode($response, true);

                // Xử lý lỗi kết nối — cURL thất bại hoặc JSON không hợp lệ
                if ($data === null) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503);
                    $errorDetail = ($response === false) ? 'cURL error' : trim(substr($response, 0, 300));
                    $errorDetail = !empty($errorDetail) ? " ($errorDetail)" : '';
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng') . $errorDetail]));
                }

                // Xử lý lỗi từ API — response trả {ok: false, error: "..."}
                if (!isset($data['ok']) || $data['ok'] != true) {
                    $errorMsg = isset($data['error']) ? $data['error'] : (isset($data['message']) ? $data['message'] : (isset($data['msg']) ? $data['msg'] : __('Lỗi không xác định từ API. Chi tiết phản hồi: ') . trim(substr($response, 0, 300))));
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __($errorMsg)]));
                }

                // Kiểm tra mảng accounts hợp lệ bên trong order
                if (!isset($data['order']['accounts']) || !is_array($data['order']['accounts']) || empty($data['order']['accounts'])) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 2] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    $rawDetail = !empty($response) ? ' (Chi tiết phản hồi: ' . trim(substr($response, 0, 300)) . ')' : '';
                    die(json_encode(['status' => 'error', 'msg' => __('Không nhận được nội dung giao hàng từ API') . $rawDetail]));
                }

                // Lưu orderCode từ API làm mã giao dịch tham chiếu
                $api_trans_id = isset($data['order']['orderCode']) ? check_string($data['order']['orderCode']) : NULL;

                // Import từng tài khoản từ accounts vào kho product_sold
                foreach ($data['order']['accounts'] as $acc) {
                    $acc = trim($acc);
                    if (empty($acc)) continue;

                    $account = check_string($acc);
                    $acc_parts = explode('|', $account);
                    $uid_part = isset($acc_parts[0]) ? trim($acc_parts[0]) : $account;

                    $isInsertAPI = $CMSNT->insert("product_sold", [
                        'type'           => $supplier['domain'] ?: 'API_51',
                        'product_code'   => NULL,
                        'supplier_id'    => $product['supplier_id'],
                        'trans_id'       => $trans_id,
                        'buyer'          => $getUser['id'],
                        'seller'         => $product['user_id'],
                        'uid'            => $uid_part,
                        'account'        => $account,
                        'create_gettime' => gettime()
                    ]);
                    if ($isInsertAPI) {
                        $isValue++;
                    }
                }
            }
            // API_46 - Shop Bot API
            // Body JSON: {"product_id": "str", "quantity": int}
            // Response: {"success": true, "order": {"order_code": "str", "accounts": ["acc1|pass1"]}}
            if ($supplier['type'] == 'API_46') {
                $response = buy_API_46($supplier['domain'], $supplier['token'], $product['api_id'], $amount, $supplier['proxy']);
                $data = json_decode($response, true);

                // Xử lý lỗi kết nối — cURL thất bại hoặc JSON không hợp lệ
                if ($data === null) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503);
                    $errorDetail = ($response === false) ? 'cURL error' : trim(substr($response, 0, 200));
                    $errorDetail = !empty($errorDetail) ? " ($errorDetail)" : '';
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng') . $errorDetail]));
                }

                // Xử lý lỗi từ API — response trả {"success": false, "message": "..."}
                if (!isset($data['success']) || $data['success'] != true) {
                    $errorMsg = isset($data['message']) ? $data['message'] : 'Lỗi không xác định từ API';
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __($errorMsg)]));
                }

                // Kiểm tra mảng accounts hợp lệ
                if (!isset($data['order']['accounts']) || !is_array($data['order']['accounts']) || empty($data['order']['accounts'])) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 2] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __('Không nhận được nội dung giao hàng từ API')]));
                }

                // Lưu order_code từ API làm mã giao dịch tham chiếu
                $api_trans_id = isset($data['order']['order_code']) ? check_string($data['order']['order_code']) : NULL;

                // Import từng tài khoản từ mảng accounts vào kho product_sold
                foreach ($data['order']['accounts'] as $acc) {
                    if (empty(trim($acc))) continue;

                    $account   = check_string(trim($acc));
                    $acc_parts = explode('|', $account);
                    $uid_part  = isset($acc_parts[0]) ? trim($acc_parts[0]) : $account;

                    $isInsertAPI = $CMSNT->insert("product_sold", [
                        'type'           => $supplier['domain'] ?: 'API_46',
                        'product_code'   => NULL,
                        'supplier_id'    => $product['supplier_id'],
                        'trans_id'       => $trans_id,
                        'buyer'          => $getUser['id'],
                        'seller'         => $product['user_id'],
                        'uid'            => $uid_part,
                        'account'        => $account,
                        'create_gettime' => gettime()
                    ]);
                    if ($isInsertAPI) {
                        $isValue++;
                    }
                }
            }
            // API_48 - APIv7 Compatibility: kiểu response giống SHOPCLONE7 (status/msg/trans_id/data)
            if ($supplier['type'] == 'API_48') {
                // Gọi POST /api/v7/buy_product với action=buyProduct
                $response = buy_API_48($supplier['domain'], $supplier['api_key'], $product['api_id'], $amount, $supplier['proxy']);
                $data = json_decode($response, true);
                $http_code = isset($data['http_code']) ? validate_string($data['http_code']) : 0;

                // Xử lý lỗi kết nối / response không hợp lệ
                if (!isset($data) || (isset($data['status']) && $data['status'] == 'error2')) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[$http_code][Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503);
                    die(json_encode(['status' => 'error', 'msg' => "[$http_code] " . __('Mất kết nối đến kho hàng')]));
                }

                // Xử lý lỗi nghiệp vụ từ API (status != success)
                if (!isset($data['status']) || $data['status'] != 'success') {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);

                    // HTTP 402 = nhà cung cấp hết tiền -> bắn cảnh báo Telegram để admin nạp gấp
                    if (isset($data['http_code']) && $data['http_code'] == 402) {
                        /** NOTE ACTION */
                        $my_text = $CMSNT->site('noti_api_out_of_money');
                        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
                        $my_text = str_replace('{username}', $getUser['username'], $my_text);
                        $my_text = str_replace('{supplier_name}', $supplier['domain'], $my_text);
                        $my_text = str_replace('{product_name}', $product['name'], $my_text);
                        $my_text = str_replace('{product_id}', $product['id'], $my_text);
                        $my_text = str_replace('{pay}', format_currency($pay), $my_text);
                        $my_text = str_replace('{amount}', format_cash($amount), $my_text);
                        $my_text = str_replace('{ip}', myip(), $my_text);
                        $my_text = str_replace('{time}', gettime(), $my_text);
                        sendMessAdmin($my_text);
                    }
                    http_response_code(503);
                    $errorMsg = isset($data['msg']) ? $data['msg'] : 'Lỗi không xác định từ API';
                    die(json_encode(['status' => 'error', 'msg' => __($errorMsg)]));
                }

                // Lấy trans_id của bên nhà cung cấp để tra cứu khi cần
                $api_trans_id = isset($data['trans_id']) ? $data['trans_id'] : NULL;

                // Đảm bảo data là mảng các dòng account (định dạng email|password hoặc tương tự)
                $accounts = [];
                if (isset($data['data'])) {
                    if (is_array($data['data'])) {
                        $accounts = $data['data'];
                    } else {
                        // Phòng trường hợp API trả về data dạng string với \n phân tách
                        $accounts = preg_split('/\r\n|\r|\n/', trim($data['data']));
                    }
                }

                foreach ($accounts as $account) {
                    $account = trim($account);
                    if (empty($account)) continue;

                    $account = check_string($account);
                    $uid = explode('|', $account)[0];
                    $isInsertAPI = $CMSNT->insert("product_sold", [
                        'type'              => $supplier['domain'],
                        'product_code'      => NULL,
                        'supplier_id'       => $product['supplier_id'],
                        'trans_id'          => $trans_id,
                        'buyer'             => $getUser['id'],
                        'seller'            => $product['user_id'],
                        'uid'               => $uid,
                        'account'           => $account,
                        'create_gettime'    => gettime()
                    ]);
                    if ($isInsertAPI) {
                        $isValue++;
                    }
                }
            }
            // API_49 - Mua proxy tự động
            if ($supplier['type'] == 'API_49') {
                // Thực hiện gọi API mua proxy từ nhà cung cấp
                $response = buy_API_49($supplier['domain'], $supplier['api_key'], $product['api_id'], $amount, $supplier['proxy']);
                $data = json_decode($response, true);

                // Xử lý lỗi mất kết nối đến API đối tác
                if (!isset($data)) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503);
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng đối tác')]));
                }

                // Dữ liệu trả về có thể là 1 object hoặc mảng các object proxy.
                // Chúng ta gom hết các proxy mua thành công vào mảng $purchase_items
                $purchase_items = [];
                $has_error = false;
                $error_msg = '';
                $error_status = 0;

                if (isset($data['status'])) {
                    // Phản hồi dạng Object đơn lẻ (thường khi mua số lượng 1)
                    if ($data['status'] == 100) {
                        $purchase_items[] = $data;
                    } else {
                        $has_error = true;
                        $error_status = $data['status'];
                    }
                } else if (is_array($data)) {
                    // Phản hồi dạng mảng nhiều Object (khi mua số lượng > 1)
                    foreach ($data as $item) {
                        if (isset($item['status'])) {
                            if ($item['status'] == 100) {
                                $purchase_items[] = $item;
                            } else if (in_array($item['status'], [101, 102, 103, 104, 201])) {
                                $has_error = true;
                                $error_status = $item['status'];
                            }
                        }
                    }
                }

                // Nếu có lỗi trả về từ API và không có proxy nào được tạo thành công
                if ($has_error && empty($purchase_items)) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    
                    // Bản đồ mã lỗi của nhà cung cấp
                    if ($error_status == 101) {
                        $error_msg = 'Key không tồn tại';
                    } elseif ($error_status == 102) {
                        $error_msg = 'Không đủ tiền tài khoản API đối tác';
                        // Gửi thông báo Telegram cảnh báo admin
                        /** NOTE ACTION */
                        $my_text = $CMSNT->site('noti_api_out_of_money');
                        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
                        $my_text = str_replace('{username}', $getUser['username'], $my_text);
                        $my_text = str_replace('{supplier_name}', $supplier['domain'], $my_text);
                        $my_text = str_replace('{product_name}', $product['name'], $my_text);
                        $my_text = str_replace('{product_id}', $product['id'], $my_text);
                        $my_text = str_replace('{pay}', format_currency($pay), $my_text);
                        $my_text = str_replace('{amount}', format_cash($amount), $my_text);
                        $my_text = str_replace('{ip}', myip(), $my_text);
                        $my_text = str_replace('{time}', gettime(), $my_text);
                        sendMessAdmin($my_text);
                    } elseif ($error_status == 103) {
                        $error_msg = 'Loại proxy này đang hết hàng';
                    } elseif ($error_status == 104) {
                        $error_msg = 'Lỗi không xác định từ nhà cung cấp';
                    } elseif ($error_status == 201) {
                        $error_msg = 'Đã mua thành công nhưng không đủ số lượng';
                    } else {
                        $error_msg = 'Lỗi hệ thống đối tác, mã status: ' . $error_status;
                    }
                    
                    http_response_code(503);
                    die(json_encode(['status' => 'error', 'msg' => __($error_msg)]));
                }

                // Trường hợp API không báo lỗi rõ ràng nhưng không trả về proxy nào
                if (empty($purchase_items)) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 2] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    http_response_code(503);
                    die(json_encode(['status' => 'error', 'msg' => __('Không thể mua proxy từ API của nhà cung cấp')]));
                }

                // Duyệt qua danh sách proxy đã mua để lưu vào bảng bán hàng product_sold
                foreach ($purchase_items as $item) {
                    $account_info = '';
                    if (!empty($item['proxy'])) {
                        $account_info = $item['proxy'];
                    } else {
                        $account_info = $item['ip'] . ':' . $item['port'] . ':' . $item['user'] . ':' . $item['password'];
                    }
                    
                    // Thêm thông tin bổ sung nếu có
                    if (!empty($item['time'])) {
                        $account_info .= ' | Hết hạn: ' . date('Y-m-d H:i:s', $item['time']);
                    }
                    if (!empty($item['idproxy'])) {
                        $account_info .= ' | ID Proxy: ' . $item['idproxy'];
                    }

                    $account_info = check_string($account_info);
                    $uid = !empty($item['idproxy']) ? check_string($item['idproxy']) : explode(':', $account_info)[0];
                    
                    $isInsertAPI = $CMSNT->insert("product_sold", [
                        'type'              => $supplier['domain'],
                        'product_code'      => NULL,
                        'supplier_id'       => $product['supplier_id'],
                        'trans_id'          => $trans_id,
                        'buyer'             => $getUser['id'],
                        'seller'            => $product['user_id'],
                        'uid'               => $uid,
                        'account'           => $account_info,
                        'create_gettime'    => gettime()
                    ]);
                    if ($isInsertAPI) {
                        $isValue++;
                    }
                }
            }
            // API_47 - API_47 API
            if ($supplier['type'] == 'API_47') {
                $token = $supplier['token'];

                // Gọi API mua hàng (buy)
                $response = buy_API_47($supplier['domain'], $token, $product['api_id'], $amount, $supplier['proxy']);
                $data = json_decode($response, true);

                // Ghi log debug nếu được bật cấu hình
                if ($CMSNT->site('debug_api_suppliers') == 1) {
                    error_log("API_47 Buy Response: " . $response);
                }

                // Xử lý lỗi kết nối
                if (!isset($data)) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503);
                    die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng')]));
                }

                // Xử lý lỗi từ API (success != true)
                if (!isset($data['success']) || $data['success'] != true) {
                    $errorMsg = isset($data['error']) ? $data['error'] : 'Lỗi không xác định từ API';
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __($errorMsg)]));
                }

                // Kiểm tra mảng items hợp lệ
                if (!isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 2] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __('Không nhận được nội dung giao hàng từ API')]));
                }

                // Lưu order_code từ API làm mã giao dịch tham chiếu
                $api_trans_id = isset($data['order_code']) ? check_string($data['order_code']) : NULL;

                // Import từng tài khoản từ mảng items vào kho product_sold
                foreach ($data['items'] as $acc) {
                    $acc = trim($acc);
                    if (empty($acc)) continue;

                    $account = check_string($acc);
                    $acc_parts = explode('|', $account);
                    $uid_part = isset($acc_parts[0]) ? trim($acc_parts[0]) : $account;

                    $isInsertAPI = $CMSNT->insert("product_sold", [
                        'type'           => $supplier['domain'] ?: 'API_47',
                        'product_code'   => NULL,
                        'supplier_id'    => $product['supplier_id'],
                        'trans_id'       => $trans_id,
                        'buyer'          => $getUser['id'],
                        'seller'         => $product['user_id'],
                        'uid'            => $uid_part,
                        'account'        => $account,
                        'create_gettime' => gettime()
                    ]);
                    if ($isInsertAPI) {
                        $isValue++;
                    }
                }
            }
            // SHOPKEY - API với header-based authentication (X-API-Key, X-API-Secret)
            if ($supplier['type'] == 'SHOPKEY') {
                // Bước 1: Gọi API tạo đơn hàng
                $response = buy_API_SHOPKEY($supplier['domain'], $supplier['coupon'], $supplier['api_key'], $supplier['token'], $product['api_id'], $amount, $supplier['proxy']);
                $data = json_decode($response, true);
                $http_code = isset($data['http_code']) ? validate_string($data['http_code']) : 0;

                // Xử lý lỗi kết nối
                if (!isset($data)) {
                    if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                        $User->RefundCredits($getUser['id'], $pay, "[$http_code][Error] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    }
                    http_response_code(503);
                    die(json_encode(['status' => 'error', 'msg' => "[$http_code] " . __('Mất kết nối đến kho hàng')]));
                }

                // Xử lý lỗi từ API
                if (!isset($data['success']) || $data['success'] != true) {
                    $errorMsg = isset($data['message']) ? $data['message'] : (isset($data['msg']) ? $data['msg'] : 'Lỗi không xác định từ API');
                    $User->RefundCredits($getUser['id'], $pay, "[Error 1] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);

                    // Kiểm tra nếu có HTTP code là 402 thì gửi thông báo qua Telegram
                    if (isset($data['http_code']) && $data['http_code'] == 402) {
                        $my_text = $CMSNT->site('noti_api_out_of_money');
                        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
                        $my_text = str_replace('{username}', $getUser['username'], $my_text);
                        $my_text = str_replace('{supplier_name}', $supplier['domain'], $my_text);
                        $my_text = str_replace('{product_name}', $product['name'], $my_text);
                        $my_text = str_replace('{product_id}', $product['id'], $my_text);
                        $my_text = str_replace('{pay}', format_currency($pay), $my_text);
                        $my_text = str_replace('{amount}', format_cash($amount), $my_text);
                        $my_text = str_replace('{ip}', myip(), $my_text);
                        $my_text = str_replace('{time}', gettime(), $my_text);
                        sendMessAdmin($my_text);
                    }
                    http_response_code(503);
                    die(json_encode(['status' => 'error', 'msg' => __($errorMsg)]));
                }

                // Lấy trans_id từ orders[0]
                $shopkey_trans_id = null;
                if (isset($data['data']['orders']) && is_array($data['data']['orders']) && count($data['data']['orders']) > 0) {
                    $shopkey_trans_id = $data['data']['orders'][0]['trans_id'];
                }

                if (empty($shopkey_trans_id)) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 2] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __('Không nhận được mã đơn hàng từ API')]));
                }

                $api_trans_id = $shopkey_trans_id;

                // Bước 2: Gọi API lấy chi tiết đơn hàng để lấy delivery items
                $order_response = getOrder_API_SHOPKEY($supplier['domain'], $supplier['api_key'], $supplier['token'], $shopkey_trans_id, $supplier['proxy']);
                $order_data = json_decode($order_response, true);

                // Xử lý lấy delivery items
                if (isset($order_data['success']) && $order_data['success'] == true && isset($order_data['data']['delivery']['items'])) {
                    foreach ($order_data['data']['delivery']['items'] as $account) {
                        $account = check_string($account);
                        $uid = explode('|', $account)[0];

                        $isInsertAPI = $CMSNT->insert("product_sold", [
                            'type'              => $supplier['domain'],
                            'product_code'      => NULL,
                            'supplier_id'       => $product['supplier_id'],
                            'trans_id'          => $trans_id,
                            'buyer'             => $getUser['id'],
                            'seller'            => $product['user_id'],
                            'uid'               => $uid,
                            'account'           => $account,
                            'create_gettime'    => gettime()
                        ]);
                        if ($isInsertAPI) {
                            $isValue++;
                        }
                    }
                } else {
                    // Nếu không lấy được delivery items, hoàn tiền
                    $User->RefundCredits($getUser['id'], $pay, "[Error 3] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                    die(json_encode(['status' => 'error', 'msg' => __('Không thể lấy chi tiết đơn hàng từ API')]));
                }
            }
            //

        }
        // Lấy hàng từ Kho
        else {

            // LẤY HÀNG TỪ KHO
            $order_by = 'ORDER BY id ASC';
            if ($product['order_by'] == 1) {
                // Check live gần nhất
                $order_by = 'ORDER BY time_check_live DESC';
            } else if ($product['order_by'] == 2) {
                // Import lâu nhất
                $order_by = 'ORDER BY id ASC';
            } else if ($product['order_by'] == 3) {
                // Import gần nhất
                $order_by = 'ORDER BY id DESC';
            } else if ($product['order_by'] == 4) {
                // Ngẫu nhiên
                $order_by = 'ORDER BY RAND()';
            }

            // Bắt đầu transaction
            $CMSNT->query("START TRANSACTION");
            $success = true;
            $inserted_accounts = [];

            // Sử dụng FOR UPDATE để lock rows, tránh race condition khi nhiều người mua cùng lúc
            $for_update = ($CMSNT->site('isForUpdateBuy') == 1) ? ' FOR UPDATE' : '';

            try {
                foreach ($CMSNT->get_list_safe("SELECT * FROM `product_stock` WHERE `product_code` = ? $order_by LIMIT ?" . $for_update, [$product['code'], $amount]) as $product_stock) {
                    $isInsertSold = $CMSNT->insert('product_sold', [
                        'type'              => $product_stock['type'],
                        'product_code'      => $product_stock['product_code'],
                        'supplier_id'       => $product['supplier_id'],
                        'trans_id'          => $trans_id,
                        'buyer'             => $getUser['id'],
                        'seller'            => $product_stock['seller'],
                        'uid'               => $product_stock['uid'],
                        'account'           => $product_stock['account'],
                        'create_gettime'    => gettime(),
                        'time_check_live'   => $product_stock['time_check_live']
                    ]);

                    if ($isInsertSold) {
                        $isValue++;
                        $inserted_accounts[] = $product_stock['id'];
                        $isRemoved = $CMSNT->remove('product_stock', " `id` = ?", [$product_stock['id']]);
                        if (!$isRemoved) {
                            throw new Exception('Lỗi khi xóa tài khoản khỏi kho');
                        }
                    } else {
                        throw new Exception('Lỗi khi insert tài khoản');
                    }
                }

                // Kiểm tra số lượng xuất có đủ không TRƯỚC KHI COMMIT
                if ($isValue < $amount) {
                    throw new Exception('Số lượng còn lại trong hệ thống không đủ');
                }

                // Nếu tất cả đều thành công, commit transaction
                $CMSNT->query("COMMIT");
            } catch (Exception $e) {
                // Nếu có lỗi, rollback transaction
                $CMSNT->query("ROLLBACK");

                // Hoàn tiền cho người dùng
                $User->RefundCredits($getUser['id'], $pay, __('[Error 4] Hoàn tiền đơn hàng mua tài khoản do lỗi hệ thống') . ' #' . $trans_id, 'REFUND_' . $trans_id);
                die(json_encode(['status' => 'error', 'msg' => $e->getMessage()]));
            }
        }


        if ($isValue > 0) {
            // TIỀN HOA HỒNG MẶC ĐỊNH LÀ 0
            $commission_fee = 0;
            // TÍNH TIỀN HOA HỒNG
            if ($CMSNT->site('affiliate_status') == 1 && $getUser['ref_id'] != 0) {
                $ck = $CMSNT->site('affiliate_ck');
                if (getRowRealtime('users', $getUser['ref_id'], 'ref_ck') != 0) {
                    $ck = getRowRealtime('users', $getUser['ref_id'], 'ref_ck');
                }
                $commission_fee = $pay * $ck / 100;
            }

            /* TẠO ĐƠN HÀNG */
            $isInsertOrder = $CMSNT->insert('product_order', [
                'trans_id'          => $trans_id,
                'api_transid'       => $api_trans_id,
                'supplier_id'       => $product['supplier_id'],
                'product_id'        => $product['id'],
                'product_name'      => $product['name'],
                'buyer'             => $getUser['id'],
                'seller'            => $product['user_id'],
                'amount'            => $amount,
                'money'             => $money,
                'pay'               => $pay,
                'cost'              => $product['cost'] * $amount,
                //'commission_fee'    => $commission_fee,
                'create_gettime'    => gettime(),
                'update_gettime'    => gettime(),
                'trash'             => 0,
                'status_view_order' => $getUser['status_view_order'],
                'ip'                => myip(),
                'device'            => getUserAgent()
            ]);
            if ($isInsertOrder) {
                if ($CMSNT->site('cong_tien_nguoi_ban') == 1) {
                    $User->AddCredits($product['user_id'], $pay, __('Doanh thu đơn hàng mua tài khoản') . ' <b>' . $product['name'] . '</b> - #' . $trans_id, 'DOANH_THU_' . $trans_id);
                }
                // CỘNG HOA HỒNG
                if ($CMSNT->site('affiliate_status') == 1 && $getUser['ref_id'] != 0) {
                    $User->AddCommission($getUser['ref_id'], $getUser['id'], $commission_fee, __('Hoa hồng thành viên' . ' ' . $getUser['username']));
                }
                /* SỬ DỤNG MÃ GIẢM GIÁ */
                if (isset($discount_coupon) && $discount_coupon > 0 && isset($coupon)) {
                    $isAddCoupon = $CMSNT->cong("coupons", "used", 1, " `code` = ? ", [$coupon]);
                    if ($isAddCoupon) {
                        $coupon_data = $CMSNT->get_row_safe("SELECT * FROM `coupons` WHERE `code` = ?", [$coupon]);
                        if ($coupon_data) {
                            $CMSNT->insert("coupon_used", [
                                'coupon_id'     => $coupon_data['id'],
                                'user_id'       => $getUser['id'],
                                'trans_id'      => $trans_id,
                                'create_gettime'    => gettime()
                            ]);
                        }
                    }
                }
                /* CỘNG ĐÃ BÁN */
                $CMSNT->cong('products', 'sold', $amount, " `id` = ? ", [$product['id']]);
                $accounts = [];
                $file_txt_email = '';
                foreach ($CMSNT->get_list_safe("SELECT * FROM `product_sold` WHERE `trans_id` = ?", [$trans_id]) as $account_sold) {
                    $accounts[] = preg_replace("/\r/", "", $account_sold['account']);
                    $file_txt_email .= PHP_EOL . htmlspecialchars_decode($account_sold['account']);
                }

                // CẬP NHẬT USER
                $CMSNT->update('users', [
                    'time_request'  => time()
                ], " `id` = ?", [$getUser['id']]);

                // TẠO LOG GIAO DỊCH GẦN ĐÂY
                $CMSNT->insert('order_log', [
                    'buyer'         => $getUser['id'],
                    'product_name'  => $product['name'],
                    'pay'           => $pay,
                    'amount'        => $amount,
                    'create_time'   => time(),
                    'is_virtual'    => 0
                ]);
                // Gửi email đơn hàng qua Queue (non-blocking)
                try {
                    $mailer = new SMTPMailer($CMSNT);
                    $templateVars = [
                        '{username}' => $getUser['username'],
                        '{ip}' => myip(),
                        '{device}' => getUserAgent(),
                        '{time}' => gettime(),
                        '{product}' => $product['name'],
                        '{amount}' => format_cash($amount),
                        '{trans_id}' => $trans_id,
                        '{pay}' => format_currency($pay)
                    ];

                    // Đính kèm nội dung file .txt chứa dữ liệu tài khoản
                    $attachmentName = 'order_' . $trans_id . '.txt';

                    $mailer->queueOrderEmail(
                        $getUser,
                        $templateVars,
                        $file_txt_email, // Nội dung file đính kèm
                        $attachmentName
                    );
                } catch (Exception $e) {
                    error_log('[EmailQueue] Lỗi queue email đơn hàng: ' . $e->getMessage());
                }
                if ($CMSNT->site('noti_buy_product') != '') {
                    /** SEND NOTI CHO ADMIN qua TelegramQueue */
                    $my_text = $CMSNT->site('noti_buy_product');
                    $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
                    $my_text = str_replace('{username}', $getUser['username'], $my_text);
                    $my_text = str_replace('{product}', $product['name'], $my_text);
                    $my_text = str_replace('{amount}', format_cash($amount), $my_text);
                    $my_text = str_replace('{trans_id}', $trans_id, $my_text);
                    $my_text = str_replace('{pay}', format_currency($pay), $my_text);
                    $my_text = str_replace('{ip}', myip(), $my_text);
                    $my_text = str_replace('{time}', gettime(), $my_text);
                    $telegramQueue = new TelegramQueue();
                    $telegramQueue->queueMessage($my_text, null, null, 3, [
                        'type' => 'admin_order_notification',
                        'source' => 'product_buy'
                    ]);
                }
                /**
                 * GỬI THÔNG BÁO TELEGRAM CHO CTV
                 * Nếu sản phẩm thuộc CTV và CTV đã cấu hình Chat ID Telegram,
                 * gửi thông báo trực tiếp qua TelegramQueue (không dùng sendMessAdmin)
                 */
                if ($product['user_id'] > 0 && $CMSNT->site('noti_buy_product') != '') {
                    // Lấy Chat ID Telegram của CTV (chủ sản phẩm)
                    $ctv_info = $CMSNT->get_row_safe("SELECT `telegram_chat_id` FROM `users` WHERE `id` = ? AND `ctv` != 0", [$product['user_id']]);
                    if ($ctv_info && !empty($ctv_info['telegram_chat_id'])) {
                        // Format nội dung thông báo giống admin nhưng gửi riêng cho CTV
                        $ctv_text = $CMSNT->site('noti_buy_product');
                        $ctv_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $ctv_text);
                        $ctv_text = str_replace('{username}', $getUser['username'], $ctv_text);
                        $ctv_text = str_replace('{product}', $product['name'], $ctv_text);
                        $ctv_text = str_replace('{amount}', format_cash($amount), $ctv_text);
                        $ctv_text = str_replace('{trans_id}', $trans_id, $ctv_text);
                        $ctv_text = str_replace('{pay}', format_currency($pay), $ctv_text);
                        $ctv_text = str_replace('{ip}', myip(), $ctv_text);
                        $ctv_text = str_replace('{time}', gettime(), $ctv_text);

                        // Gửi qua TelegramQueue trực tiếp tới Chat ID của CTV
                        $telegramQueue = new TelegramQueue();
                        $telegramQueue->queueMessage($ctv_text, $ctv_info['telegram_chat_id'], null, 3, [
                            'type' => 'ctv_order_notification',
                            'source' => 'product_buy',
                            'ctv_id' => $product['user_id']
                        ]);
                    }
                }
                $isTaphoammo = isset($_REQUEST['is_taphoammo']) ? true : false;
                if ($isTaphoammo) {
                    $formatted_accounts = array_map(function ($account) {
                        return ["product" => $account];
                    }, $accounts);
                    die(json_encode($formatted_accounts, JSON_PRETTY_PRINT));
                } else {
                    die(json_encode([
                        'status'    => 'success',
                        'msg'       => __('Tạo đơn hàng thành công!'),
                        'trans_id'  => $trans_id,
                        'data'      => $accounts
                    ]));
                }
            }
        } else {
            if ($product['supplier_id'] != 0) {
                if ($CMSNT->site('auto_refund_order_failed_api') == 1) {
                    $User->RefundCredits($getUser['id'], $pay, "[Error 2] " . __('Hoàn tiền đơn hàng mua tài khoản') . " <b>" . $product['name'] . "</b> - #" . $trans_id, 'REFUND_' . $trans_id);
                }
                die(json_encode(['status' => 'error', 'msg' => __('Mất kết nối đến kho hàng')]));
            } else {
                $User->RefundCredits($getUser['id'], $pay, __('[Error 2] Hoàn tiền đơn hàng mua tài khoản') . ' #' . $trans_id, 'REFUND_' . $trans_id);
                die(json_encode(['status' => 'error', 'msg' => __('Số lượng còn lại trong hệ thống không đủ')]));
            }
        }
        die(json_encode(['status' => 'error', 'msg' => 'ERROR 1 - ' . __('System error')]));
    } else {
        die(json_encode(['status' => 'error', 'msg' => 'ERROR 2 - ' . __('Không thể thanh toán đơn hàng, vui lòng thử lại')]));
    }
}


if ($_REQUEST['action'] == 'total_payment') {
    $product_id = validate_int($_REQUEST['id'], 1);
    if ($product_id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID sản phẩm không hợp lệ!')]));
    }

    $amount = validate_int($_REQUEST['amount'], 1);
    if ($amount === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Số lượng không hợp lệ!')]));
    }

    if (!$product = $CMSNT->get_row_safe("SELECT * FROM `products` WHERE `id` = ? AND `status` = 1", [$product_id])) {
        die(json_encode(['status' => 'error', 'msg' => __('Sản phẩm không khả dụng')]));
    }
    $discount = 0; // số tiền được giảm
    $discount_coupon = 0;
    $price = $product['discount'] == 0 ? $product['price'] : $product['price'] - $product['price'] * $product['discount'] / 100;
    $money = $amount * $price; // giá gốc
    $pay = $money; // số tiền cần thanh toán nếu không có khuyến mãi
    if (!empty($_REQUEST['token'])) {
        $token = validate_alphanumeric($_REQUEST['token'], 255);
        if ($token !== false && $getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0", [$token])) {
            if ($getUser['discount'] == 0) {
                // Giảm giá bằng điều kiện nếu user ko dc ck
                $discount = $money * getDiscount($amount, $product['id']) / 100;
                // Xử lý giảm giá bằng coupon
                if (!empty($_REQUEST['coupon'])) {
                    $coupon = validate_alphanumeric($_REQUEST['coupon'], 50);
                    if ($coupon !== false) {
                        // Lấy số tiền giảm từ Coupon
                        $discount_coupon = checkCoupon($product['id'], $coupon, $getUser['id'], $money);
                    }
                }
                // Số tiền thanh toán sau khi trừ discount
                $pay = $money - $discount - $discount_coupon;
            } else {
                $discount = $money * $getUser['discount'] / 100;
                $pay = $money - $discount;
            }
        }
    } else {
        $discount = $money * getDiscount($amount, $product['id']) / 100;
    }

    $price          = $pay; // Số tiền thanh toán ban đầu chưa bao gồm VAT
    $price_vat      = $CMSNT->site('tax_vat') > 0 ? $pay * $CMSNT->site('tax_vat') / 100 : 0; // Số tiền thuế VAT cần trả thêm
    $pay            = $price + $price_vat; // Số tiền thanh toán sau khi tính thuế VAT

    die(json_encode([
        'status'    => 'success',
        'money'     => format_currency($money),
        'discount'      => format_currency($money - $price),
        'discount_number'  => $money - $price,
        'pay'           => format_currency($pay),               // Số tiền thanh toán sau khi tính thuế VAT
        'price'         => format_currency($price),             // Số tiền chưa tính thuế
        'price_vat'     => format_currency($price_vat),         // Số tiền thuế VAT
        'tax_vat'       => floatval($CMSNT->site('tax_vat'))    // Thuế VAT (%)

    ]));
}

/**
 * Action: buyProductByUid
 * Mua sản phẩm bằng cách chọn cụ thể các UID từ kho hàng
 * Chỉ hỗ trợ sản phẩm có supplier_id == 0 (kho nội bộ) và preview_uid == 1
 */
if ($_REQUEST['action'] == 'buyProductByUid') {
    if ($CMSNT->site('status_demo') != 0) {
        http_response_code(403);
        die(json_encode(['status' => 'error', 'msg' => __('This function cannot be used because this is a demo site')]));
    }

    // Xác thực user
    if (!empty($_REQUEST['token'])) {
        $token = validate_alphanumeric($_REQUEST['token'], 255);
        if ($token === false) {
            checkBlockIP('API', 5);
            http_response_code(400);
            die(json_encode(['status' => 'error', 'msg' => __('Token không hợp lệ!')]));
        }
        if (!$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0", [$token])) {
            checkBlockIP('API', 5);
            http_response_code(401);
            die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập')]));
        }
    } else {
        http_response_code(401);
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập')]));
    }

    if ($getUser['banned'] != 0) {
        http_response_code(403);
        die(json_encode(['status' => 'error', 'msg' => __('Tài khoản của bạn đã bị cấm')]));
    }
    if ($getUser['ctv'] != 0) {
        http_response_code(403);
        die(json_encode(['status' => 'error', 'msg' => __('Tài khoản CTV không được phép mua hàng')]));
    }
    if (time() > $getUser['time_request'] && time() - $getUser['time_request'] < $CMSNT->site('thoi_gian_mua_cach_nhau')) {
        http_response_code(429);
        die(json_encode(['status' => 'error', 'msg' => __('Thao tác quá nhanh, vui lòng chờ')]));
    }

    // Validate product
    $product_id = validate_int($_REQUEST['id'], 1);
    if ($product_id === false) {
        http_response_code(400);
        die(json_encode(['status' => 'error', 'msg' => __('ID sản phẩm không hợp lệ!')]));
    }
    if (!$product = $CMSNT->get_row_safe("SELECT * FROM `products` WHERE `id` = ? AND `status` = 1", [$product_id])) {
        http_response_code(404);
        die(json_encode(['status' => 'error', 'msg' => __('Sản phẩm không tồn tại trong hệ thống')]));
    }

    // Chỉ hỗ trợ kho nội bộ và preview_uid = 1
    if ($product['supplier_id'] != 0) {
        http_response_code(400);
        die(json_encode(['status' => 'error', 'msg' => __('Sản phẩm từ API không hỗ trợ chọn UID')]));
    }
    if (($product['preview_uid'] ?? 0) != 1) {
        http_response_code(400);
        die(json_encode(['status' => 'error', 'msg' => __('Sản phẩm không hỗ trợ xem trước UID')]));
    }

    // Validate stock_ids
    if (empty($_REQUEST['stock_ids']) || !is_array($_REQUEST['stock_ids'])) {
        http_response_code(400);
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng chọn ít nhất một UID')]));
    }

    $stock_ids = array_map('intval', $_REQUEST['stock_ids']);
    $stock_ids = array_filter($stock_ids, function ($id) {
        return $id > 0;
    });
    $stock_ids = array_unique($stock_ids);
    $amount = count($stock_ids);

    if ($amount === 0) {
        http_response_code(400);
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng chọn ít nhất một UID')]));
    }
    if ($amount < $product['min']) {
        http_response_code(400);
        die(json_encode(['status' => 'error', 'msg' => __('Số lượng cần mua tối thiểu là') . ' ' . format_cash($product['min'])]));
    }
    if ($amount > $product['max']) {
        http_response_code(400);
        die(json_encode(['status' => 'error', 'msg' => __('Số lượng cần mua tối đa là') . ' ' . format_cash($product['max'])]));
    }

    // Kiểm tra stock_ids thuộc đúng product_code
    $placeholders = implode(',', array_fill(0, count($stock_ids), '?'));
    $params = array_merge([$product['code']], $stock_ids);
    $valid_stocks = $CMSNT->get_list_safe(
        "SELECT `id` FROM `product_stock` WHERE `product_code` = ? AND `id` IN ($placeholders)",
        $params
    );
    $valid_ids = array_column($valid_stocks, 'id');

    if (count($valid_ids) < $amount) {
        http_response_code(400);
        die(json_encode(['status' => 'error', 'msg' => __('Một số UID đã được mua hoặc không tồn tại, vui lòng chọn lại')]));
    }

    // Tính giá
    $trans_id = random('QWERTYUOPASDFGHJKZXCVBNM123456789', 4) . uniqid();
    $price = $product['discount'] == 0 ? $product['price'] : $product['price'] - $product['price'] * $product['discount'] / 100;
    $money = $amount * $price;
    $pay = $money;
    $discount = 0;
    $discount_coupon = 0;

    if ($getUser['discount'] == 0) {
        $discount = $money * getDiscount($amount, $product['id']) / 100;
        if (!empty($_REQUEST['coupon'])) {
            $coupon = validate_alphanumeric($_REQUEST['coupon'], 50);
            if ($coupon !== false) {
                $discount_coupon = checkCoupon($product['id'], $coupon, $getUser['id'], $money);
            }
        }
        $pay = $money - $discount - $discount_coupon;
    } else {
        $discount = $money * $getUser['discount'] / 100;
        $pay = $money - $discount;
    }

    $price_vat = $CMSNT->site('tax_vat') > 0 ? $pay * $CMSNT->site('tax_vat') / 100 : 0;
    $pay = $pay + $price_vat;

    if (getRowRealtime('users', $getUser['id'], 'money') < $pay) {
        http_response_code(402);
        die(json_encode(['status' => 'error', 'msg' => __('Số dư không đủ, vui lòng nạp thêm')]));
    }

    $User = new users();
    $isTru = $User->RemoveCredits($getUser['id'], $pay, __('Thanh toán đơn hàng mua tài khoản') . ' <b>' . $product['name'] . '</b> - #' . $trans_id, 'ORDER_' . $trans_id);

    if ($isTru) {
        if (getRowRealtime("users", $getUser['id'], "money") < -500) {
            $User->Banned($getUser['id'], __('Gian lận khi mua tài khoản'));
            http_response_code(403);
            die(json_encode(['status' => 'error', 'msg' => __('Bạn đã bị khoá tài khoản vì gian lận')]));
        }

        $isValue = 0;
        $api_trans_id = NULL;

        // Bắt đầu transaction
        $CMSNT->query("START TRANSACTION");
        $inserted_accounts = [];

        $for_update = ($CMSNT->site('isForUpdateBuy') == 1) ? ' FOR UPDATE' : '';

        try {
            // Lấy hàng theo stock_ids cụ thể
            $stock_items = $CMSNT->get_list_safe(
                "SELECT * FROM `product_stock` WHERE `product_code` = ? AND `id` IN ($placeholders)" . $for_update,
                $params
            );

            foreach ($stock_items as $product_stock) {
                $isInsertSold = $CMSNT->insert('product_sold', [
                    'type'              => $product_stock['type'],
                    'product_code'      => $product_stock['product_code'],
                    'supplier_id'       => $product['supplier_id'],
                    'trans_id'          => $trans_id,
                    'buyer'             => $getUser['id'],
                    'seller'            => $product_stock['seller'],
                    'uid'               => $product_stock['uid'],
                    'account'           => $product_stock['account'],
                    'create_gettime'    => gettime(),
                    'time_check_live'   => $product_stock['time_check_live']
                ]);

                if ($isInsertSold) {
                    $isValue++;
                    $inserted_accounts[] = $product_stock['id'];
                    $isRemoved = $CMSNT->remove('product_stock', " `id` = ?", [$product_stock['id']]);
                    if (!$isRemoved) {
                        throw new Exception('Lỗi khi xóa tài khoản khỏi kho');
                    }
                } else {
                    throw new Exception('Lỗi khi insert tài khoản');
                }
            }

            if ($isValue < $amount) {
                throw new Exception('Số lượng còn lại trong hệ thống không đủ');
            }

            $CMSNT->query("COMMIT");
        } catch (Exception $e) {
            $CMSNT->query("ROLLBACK");
            $User->RefundCredits($getUser['id'], $pay, __('[Error 4] Hoàn tiền đơn hàng mua tài khoản do lỗi hệ thống') . ' #' . $trans_id, 'REFUND_' . $trans_id);
            die(json_encode(['status' => 'error', 'msg' => $e->getMessage()]));
        }

        if ($isValue > 0) {
            $commission_fee = 0;
            if ($CMSNT->site('affiliate_status') == 1 && $getUser['ref_id'] != 0) {
                $ck = $CMSNT->site('affiliate_ck');
                if (getRowRealtime('users', $getUser['ref_id'], 'ref_ck') != 0) {
                    $ck = getRowRealtime('users', $getUser['ref_id'], 'ref_ck');
                }
                $commission_fee = $pay * $ck / 100;
            }

            $isInsertOrder = $CMSNT->insert('product_order', [
                'trans_id'          => $trans_id,
                'api_transid'       => $api_trans_id,
                'supplier_id'       => $product['supplier_id'],
                'product_id'        => $product['id'],
                'product_name'      => $product['name'],
                'buyer'             => $getUser['id'],
                'seller'            => $product['user_id'],
                'amount'            => $amount,
                'money'             => $money,
                'pay'               => $pay,
                'cost'              => $product['cost'] * $amount,
                'create_gettime'    => gettime(),
                'update_gettime'    => gettime(),
                'trash'             => 0,
                'status_view_order' => $getUser['status_view_order'],
                'ip'                => myip(),
            ]);

            if ($isInsertOrder) {
                // Cập nhật thời gian request
                $CMSNT->update('users', ['time_request' => time()], " `id` = " . $getUser['id']);
                // Cập nhật số lượng đã bán
                $CMSNT->query("UPDATE `products` SET `sold` = `sold` + $amount WHERE `id` = " . $product['id']);
                // Ghi log đơn hàng
                $CMSNT->insert('order_log', [
                    'product_name'  => $product['name'],
                    'buyer'         => $getUser['id'],
                    'amount'        => $amount,
                    'pay'           => $pay,
                    'create_time'   => time()
                ]);

                // Hoa hồng tiếp thị liên kết
                if ($CMSNT->site('affiliate_status') == 1 && $getUser['ref_id'] != 0 && $commission_fee > 0) {
                    $User->AddCredits($getUser['ref_id'], $commission_fee, __('Hoa hồng tiếp thị liên kết từ') . ' ' . $getUser['username'] . ' - #' . $trans_id, 'COMMISSION_' . $trans_id);
                }

                // Lấy danh sách tài khoản đã mua
                $accounts = [];
                $file_txt_email = '';
                $sold_items = $CMSNT->get_list_safe("SELECT `account` FROM `product_sold` WHERE `trans_id` = ?", [$trans_id]);
                foreach ($sold_items as $sold_item) {
                    $accounts[] = $sold_item['account'];
                    $file_txt_email .= PHP_EOL . htmlspecialchars_decode($sold_item['account']);
                }

                // Gửi email đơn hàng qua Queue (non-blocking)
                try {
                    $mailer = new SMTPMailer($CMSNT);
                    $templateVars = [
                        '{username}' => $getUser['username'],
                        '{ip}' => myip(),
                        '{device}' => getUserAgent(),
                        '{time}' => gettime(),
                        '{product}' => $product['name'],
                        '{amount}' => format_cash($amount),
                        '{trans_id}' => $trans_id,
                        '{pay}' => format_currency($pay)
                    ];

                    // Đính kèm nội dung file .txt chứa dữ liệu tài khoản
                    $attachmentName = 'order_' . $trans_id . '.txt';

                    $mailer->queueOrderEmail(
                        $getUser,
                        $templateVars,
                        $file_txt_email,
                        $attachmentName
                    );
                } catch (Exception $e) {
                    error_log('[EmailQueue] Lỗi queue email đơn hàng (buyByUid): ' . $e->getMessage());
                }

                // Gửi thông báo Telegram
                if ($CMSNT->site('telegram_status') == 1 && !empty($CMSNT->site('noti_buy_product'))) {
                    $my_text = $CMSNT->site('noti_buy_product');
                    $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
                    $my_text = str_replace('{username}', $getUser['username'], $my_text);
                    $my_text = str_replace('{product_name}', $product['name'], $my_text);
                    $my_text = str_replace('{product_id}', $product['id'], $my_text);
                    $my_text = str_replace('{pay}', format_currency($pay), $my_text);
                    $my_text = str_replace('{amount}', format_cash($amount), $my_text);
                    $my_text = str_replace('{ip}', myip(), $my_text);
                    $my_text = str_replace('{time}', gettime(), $my_text);
                    $my_text = str_replace('{trans_id}', $trans_id, $my_text);
                    $telegramQueue = new TelegramQueue();
                    $telegramQueue->queueMessage($my_text, null, null, 3, [
                        'type' => 'admin_order_notification',
                        'source' => 'product_buy_uid'
                    ]);
                }
                /**
                 * GỬI THÔNG BÁO TELEGRAM CHO CTV (mua bằng chọn UID)
                 * Logic tương tự phần mua thường ở trên
                 */
                if ($product['user_id'] > 0 && !empty($CMSNT->site('noti_buy_product'))) {
                    $ctv_info = $CMSNT->get_row_safe("SELECT `telegram_chat_id` FROM `users` WHERE `id` = ? AND `ctv` != 0", [$product['user_id']]);
                    if ($ctv_info && !empty($ctv_info['telegram_chat_id'])) {
                        $ctv_text = $CMSNT->site('noti_buy_product');
                        $ctv_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $ctv_text);
                        $ctv_text = str_replace('{username}', $getUser['username'], $ctv_text);
                        $ctv_text = str_replace('{product_name}', $product['name'], $ctv_text);
                        $ctv_text = str_replace('{product_id}', $product['id'], $ctv_text);
                        $ctv_text = str_replace('{product}', $product['name'], $ctv_text);
                        $ctv_text = str_replace('{pay}', format_currency($pay), $ctv_text);
                        $ctv_text = str_replace('{amount}', format_cash($amount), $ctv_text);
                        $ctv_text = str_replace('{ip}', myip(), $ctv_text);
                        $ctv_text = str_replace('{time}', gettime(), $ctv_text);
                        $ctv_text = str_replace('{trans_id}', $trans_id, $ctv_text);

                        $telegramQueue = new TelegramQueue();
                        $telegramQueue->queueMessage($ctv_text, $ctv_info['telegram_chat_id'], null, 3, [
                            'type' => 'ctv_order_notification',
                            'source' => 'product_buy_uid',
                            'ctv_id' => $product['user_id']
                        ]);
                    }
                }

                die(json_encode([
                    'status'    => 'success',
                    'msg'       => __('Tạo đơn hàng thành công!'),
                    'trans_id'  => $trans_id,
                    'data'      => $accounts
                ]));
            }
        }

        // Nếu không lấy được hàng, hoàn tiền
        $User->RefundCredits($getUser['id'], $pay, __('[Error] Hoàn tiền đơn hàng mua tài khoản') . ' #' . $trans_id, 'REFUND_' . $trans_id);
        die(json_encode(['status' => 'error', 'msg' => __('Đã xảy ra lỗi, vui lòng thử lại')]));
    }

    die(json_encode(['status' => 'error', 'msg' => __('Đã xảy ra lỗi, vui lòng thử lại')]));
}

die(json_encode([
    'status'    => 'error',
    'msg'       => __('Request does not exist')
]));
