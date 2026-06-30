<?php

define("IN_SITE", true);

require_once(__DIR__ . '/../libs/db.php');
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../libs/lang.php');
require_once(__DIR__ . '/../libs/helper.php');
require_once(__DIR__ . '/../libs/database/users.php');
require_once(__DIR__ . '/../libs/telegram-shop/TelegramShopBot.php');
$CMSNT = new DB();
$user = new users();

// Kiểu nạp tiền: prefix_id (mặc định) hoặc fullname_transfer
$bankRechargeType = $CMSNT->site('bank_recharge_type') ?: 'prefix_id';


// Nếu có đặt key cron job thì kiểm tra key hợp lệ
if (!empty($CMSNT->site('key_cron_job'))) {
    if (empty($_GET['key']) || $_GET['key'] != $CMSNT->site('key_cron_job')) {
        die(__('Key không hợp lệ'));
    }
}

$elapsed = time() - (int)$CMSNT->site('check_time_cron_bank');
    if ($elapsed >= 0 && $elapsed < 5) {
        die('[ÉT O ÉT ]Thao tác quá nhanh, vui lòng đợi');
    }
$CMSNT->update("settings", ['value' => time()], " `name` = 'check_time_cron_bank' ");

// Nếu loại API là pay2s.vn thì không cần chạy cron bank (dùng webhook)
if ($CMSNT->site('bank_api_type') == 'pay2s') {
    die('API type is pay2s.vn — using webhook, cron not needed.');
}

foreach ($CMSNT->get_list_safe(" SELECT * FROM `banks` WHERE `status` = ? ", [1]) as $bank) {
    // Skip bank nếu chưa có token API
    if (empty($bank['token'])) {
        continue;
    }
    if (strtolower($bank['short_name']) == 'seab' || strtolower($bank['short_name']) == 'seabank') {
        $result = curl_get2("https://api.web2m.com/historyapiseabank/" . $bank['token']);
        if ($CMSNT->site('debug_auto_bank') == 1) {
            echo $result;
        }
        $result = json_decode($result, true);
        foreach ($result['data'] as $data) {
            $tid            = check_string($data['transID']);
            $description    = str_replace(' ', '.', check_string($data['description']));
            $amount         = check_string($data['totalAmount']);
            $type           = check_string($data['ngTranType']);
            // TÁCH NỘI DUNG CHUYỂN TIỀN
            if ($type != 'inward') {
                continue;
            }
            if ($amount < $CMSNT->site('bank_min') || $amount > $CMSNT->site('bank_max')) {
                continue;
            }
            if ($getUser = findUserByDescription($description, $bankRechargeType)) {
                if ($CMSNT->num_rows_safe(" SELECT * FROM `payment_bank` WHERE `tid` = ? AND `description` = ? ", [$tid, $description]) == 0) {
                    $received = checkPromotion($amount);
                    $insertSv2 = $CMSNT->insert("payment_bank", array(
                        'tid'               => $tid,
                        'method'            => $bank['short_name'],
                        'user_id'           => $getUser['id'],
                        'description'       => $description,
                        'amount'            => $amount,
                        'received'          => $received,
                        'create_gettime'    => gettime(),
                        'create_time'       => time()
                    ));
                    if ($insertSv2) {
                        $isCong = $user->AddCredits($getUser['id'], $received, "Nạp tiền tự động qua " . $bank['short_name'] . " (#$tid - $description - $amount)", 'TOPUP_' . $bank['accountNumber'] . '_' . $tid);
                        if ($isCong) {

                            // XỬ LÝ TIỀN NỢ NẾU CÓ
                            debit_processing($getUser['id']);
                            // TẠO LOG GIAO DỊCH GẦN ĐÂY
                            $CMSNT->insert('deposit_log', [
                                'user_id'       => $getUser['id'],
                                'method'        => $bank['short_name'],
                                'amount'        => $amount,
                                'received'      => $received,
                                'create_time'   => time(),
                                'is_virtual'    => 0
                            ]);
                            /** SEND NOTI CHO ADMIN */
                            $my_text = $CMSNT->site('noti_recharge');
                            $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
                            $my_text = str_replace('{username}', $getUser['username'], $my_text);
                            $my_text = str_replace('{method}', $bank['short_name'], $my_text);
                            $my_text = str_replace('{amount}', format_currency($amount), $my_text);
                            $my_text = str_replace('{price}', format_currency($received), $my_text);
                            $my_text = str_replace('{time}', gettime(), $my_text);
                            sendMessAdmin($my_text);
                            // Gửi thông báo Telegram cho user (nếu đăng ký qua Telegram)
                            TelegramShopBot::notifyRecharge($getUser['id'], $bank['short_name'], format_currency($amount), $received);
                            echo '[<b style="color:green">-</b>] Xử lý thành công 1 hoá đơn.' . PHP_EOL;
                        }
                    }
                }
            }
            if ($CMSNT->get_row_safe(" SELECT COUNT(id) FROM `log_bank_auto` WHERE `tid` = ? AND `description` = ? AND `method` = ? ", [$tid, $description, $bank['short_name']])['COUNT(id)'] == 0) {
                $CMSNT->insert("log_bank_auto", array(
                    'tid'               => $tid,
                    'method'            => $bank['short_name'],
                    'description'       => $description,
                    'type'              => $type,
                    'amount'            => $amount,
                    'create_gettime'    => gettime()
                ));
            }
        }
        continue;
    }
    if (strtolower($bank['short_name']) == 'acb') {
        $result = curl_get2("https://api.web2m.com/historyapiacb/" . $bank['password'] . "/" . $bank['accountNumber'] . "/" . $bank['token']);
        if ($CMSNT->site('debug_auto_bank') == 1) {
            echo $result;
        }
        $result = json_decode($result, true);
        foreach ($result['transactions'] as $data) {
            if ($data['type'] == 'OUT') {
                continue;
            }
            $tid            = check_string($data['transactionNumber']);
            $description    = str_replace(' ', '.', check_string($data['description']));
            $amount         = check_string($data['amount']);
            // TÁCH NỘI DUNG CHUYỂN TIỀN (findUserByDescription)
            if ($amount < $CMSNT->site('bank_min') || $amount > $CMSNT->site('bank_max')) {
                continue;
            }
            if ($getUser = findUserByDescription($description, $bankRechargeType)) {
                if ($CMSNT->num_rows_safe(" SELECT * FROM `payment_bank` WHERE `tid` = ? AND `description` = ? ", [$tid, $description]) == 0) {
                    $received = checkPromotion($amount);
                    $insertSv2 = $CMSNT->insert("payment_bank", array(
                        'tid'               => $tid,
                        'method'            => $bank['short_name'],
                        'user_id'           => $getUser['id'],
                        'description'       => $description,
                        'amount'            => $amount,
                        'received'          => $received,
                        'create_gettime'    => gettime(),
                        'create_time'       => time()
                    ));
                    if ($insertSv2) {
                        $isCong = $user->AddCredits($getUser['id'], $received, "Nạp tiền tự động qua " . $bank['short_name'] . " (#$tid - $description - $amount)", 'TOPUP_' . $bank['accountNumber'] . '_' . $tid);
                        if ($isCong) {

                            // XỬ LÝ TIỀN NỢ NẾU CÓ
                            debit_processing($getUser['id']);
                            // TẠO LOG GIAO DỊCH GẦN ĐÂY
                            $CMSNT->insert('deposit_log', [
                                'user_id'       => $getUser['id'],
                                'method'        => $bank['short_name'],
                                'amount'        => $amount,
                                'received'      => $received,
                                'create_time'   => time(),
                                'is_virtual'    => 0
                            ]);
                            /** SEND NOTI CHO ADMIN */
                            $my_text = $CMSNT->site('noti_recharge');
                            $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
                            $my_text = str_replace('{username}', $getUser['username'], $my_text);
                            $my_text = str_replace('{method}', $bank['short_name'], $my_text);
                            $my_text = str_replace('{amount}', format_currency($amount), $my_text);
                            $my_text = str_replace('{price}', format_currency($received), $my_text);
                            $my_text = str_replace('{time}', gettime(), $my_text);
                            sendMessAdmin($my_text);
                            // Gửi thông báo Telegram cho user (nếu đăng ký qua Telegram)
                            TelegramShopBot::notifyRecharge($getUser['id'], $bank['short_name'], format_currency($amount), $received);
                            echo '[<b style="color:green">-</b>] Xử lý thành công 1 hoá đơn.' . PHP_EOL;
                        }
                    }
                }
            }
            if ($CMSNT->get_row_safe(" SELECT COUNT(id) FROM `log_bank_auto` WHERE `tid` = ? AND `description` = ? AND `method` = ? ", [$tid, $description, $bank['short_name']])['COUNT(id)'] == 0) {
                $CMSNT->insert("log_bank_auto", array(
                    'tid'               => $tid,
                    'method'            => $bank['short_name'],
                    'description'       => $description,
                    'type'              => $data['type'],
                    'amount'            => $amount,
                    'create_gettime'    => gettime()
                ));
            }
        }
        continue;
    }

    if (strtolower($bank['short_name']) == 'vietinbank' || strtolower($bank['short_name']) == 'vtb') {
        $result = curl_get2("https://api.web2m.com/historyapivtb/" . $bank['password'] . "/" . $bank['accountNumber'] . "/" . $bank['token']);
        if ($CMSNT->site('debug_auto_bank') == 1) {
            echo $result;
        }
        $result = json_decode($result, true);
        foreach ($result['transactions'] as $data) {
            $tid            = check_string($data['trxId']);
            $description    = check_string($data['remark']);
            $amount         = check_string(intval($data['amount']));
            // TÁCH NỘI DUNG CHUYỂN TIỀN (findUserByDescription)
            if ($amount < $CMSNT->site('bank_min') || $amount > $CMSNT->site('bank_max')) {
                continue;
            }
            if ($getUser = findUserByDescription($description, $bankRechargeType)) {
                if ($CMSNT->num_rows_safe(" SELECT * FROM `payment_bank` WHERE `tid` = ? AND `description` = ? ", [$tid, $description]) == 0) {
                    $received = checkPromotion($amount);
                    $insertSv2 = $CMSNT->insert("payment_bank", array(
                        'tid'               => $tid,
                        'method'            => $bank['short_name'],
                        'user_id'           => $getUser['id'],
                        'description'       => $description,
                        'amount'            => $amount,
                        'received'          => $received,
                        'create_gettime'    => gettime(),
                        'create_time'       => time()
                    ));
                    if ($insertSv2) {
                        $isCong = $user->AddCredits($getUser['id'], $received, "Nạp tiền tự động qua " . $bank['short_name'] . " (#$tid - $description - $amount)", 'TOPUP_' . $bank['accountNumber'] . '_' . $tid);
                        if ($isCong) {

                            // XỬ LÝ TIỀN NỢ NẾU CÓ
                            debit_processing($getUser['id']);
                            // TẠO LOG GIAO DỊCH GẦN ĐÂY
                            $CMSNT->insert('deposit_log', [
                                'user_id'       => $getUser['id'],
                                'method'        => $bank['short_name'],
                                'amount'        => $amount,
                                'received'      => $received,
                                'create_time'   => time(),
                                'is_virtual'    => 0
                            ]);
                            /** SEND NOTI CHO ADMIN */
                            $my_text = $CMSNT->site('noti_recharge');
                            $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
                            $my_text = str_replace('{username}', $getUser['username'], $my_text);
                            $my_text = str_replace('{method}', $bank['short_name'], $my_text);
                            $my_text = str_replace('{amount}', format_currency($amount), $my_text);
                            $my_text = str_replace('{price}', format_currency($received), $my_text);
                            $my_text = str_replace('{time}', gettime(), $my_text);
                            sendMessAdmin($my_text);
                            // Gửi thông báo Telegram cho user (nếu đăng ký qua Telegram)
                            TelegramShopBot::notifyRecharge($getUser['id'], $bank['short_name'], format_currency($amount), $received);
                            echo '[<b style="color:green">-</b>] Xử lý thành công 1 hoá đơn.' . PHP_EOL;
                        }
                    }
                }
            }
            if ($CMSNT->get_row_safe(" SELECT COUNT(id) FROM `log_bank_auto` WHERE `tid` = ? AND `description` = ? AND `method` = ? ", [$tid, $description, $bank['short_name']])['COUNT(id)'] == 0) {
                $CMSNT->insert("log_bank_auto", array(
                    'tid'               => $tid,
                    'method'            => $bank['short_name'],
                    'description'       => $description,
                    'type'              => $data['type'],
                    'amount'            => $amount,
                    'create_gettime'    => gettime()
                ));
            }
        }
        continue;
    }


    if (strtolower($bank['short_name']) == 'techcombank' || strtolower($bank['short_name']) == 'tcb') {
        $result = curl_get2("https://api.web2m.com/historyapitcb/" . $bank['password'] . "/" . $bank['accountNumber'] . "/" . $bank['token']);
        if ($CMSNT->site('debug_auto_bank') == 1) {
            echo $result;
        }
        $result = json_decode($result, true);
        foreach ($result['transactions'] as $data) {
            $tid            = check_string($data['TransID']);
            $description    = check_string($data['Description']);
            $amount         = check_string(str_replace(',', '', $data['Amount']));
            // TÁCH NỘI DUNG CHUYỂN TIỀN (findUserByDescription)
            if ($amount < $CMSNT->site('bank_min') || $amount > $CMSNT->site('bank_max')) {
                continue;
            }
            if ($getUser = findUserByDescription($description, $bankRechargeType)) {
                if ($CMSNT->num_rows_safe(" SELECT * FROM `payment_bank` WHERE `tid` = ? AND `description` = ? ", [$tid, $description]) == 0) {
                    $received = checkPromotion($amount);
                    $insertSv2 = $CMSNT->insert("payment_bank", array(
                        'tid'               => $tid,
                        'method'            => $bank['short_name'],
                        'user_id'           => $getUser['id'],
                        'description'       => $description,
                        'amount'            => $amount,
                        'received'          => $received,
                        'create_gettime'    => gettime(),
                        'create_time'       => time()
                    ));
                    if ($insertSv2) {
                        $isCong = $user->AddCredits($getUser['id'], $received, "Nạp tiền tự động qua " . $bank['short_name'] . " (#$tid - $description - $amount)", 'TOPUP_' . $bank['accountNumber'] . '_' . $tid);
                        if ($isCong) {

                            // XỬ LÝ TIỀN NỢ NẾU CÓ
                            debit_processing($getUser['id']);
                            // TẠO LOG GIAO DỊCH GẦN ĐÂY
                            $CMSNT->insert('deposit_log', [
                                'user_id'       => $getUser['id'],
                                'method'        => $bank['short_name'],
                                'amount'        => $amount,
                                'received'      => $received,
                                'create_time'   => time(),
                                'is_virtual'    => 0
                            ]);
                            /** SEND NOTI CHO ADMIN */
                            $my_text = $CMSNT->site('noti_recharge');
                            $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
                            $my_text = str_replace('{username}', $getUser['username'], $my_text);
                            $my_text = str_replace('{method}', $bank['short_name'], $my_text);
                            $my_text = str_replace('{amount}', format_currency($amount), $my_text);
                            $my_text = str_replace('{price}', format_currency($received), $my_text);
                            $my_text = str_replace('{time}', gettime(), $my_text);
                            sendMessAdmin($my_text);
                            // Gửi thông báo Telegram cho user (nếu đăng ký qua Telegram)
                            TelegramShopBot::notifyRecharge($getUser['id'], $bank['short_name'], format_currency($amount), $received);
                            echo '[<b style="color:green">-</b>] Xử lý thành công 1 hoá đơn.' . PHP_EOL;
                        }
                    }
                }
            }
            if ($CMSNT->get_row_safe(" SELECT COUNT(id) FROM `log_bank_auto` WHERE `tid` = ? AND `description` = ? AND `method` = ? ", [$tid, $description, $bank['short_name']])['COUNT(id)'] == 0) {
                $CMSNT->insert("log_bank_auto", array(
                    'tid'               => $tid,
                    'method'            => $bank['short_name'],
                    'description'       => $description,
                    'type'              => $data['type'],
                    'amount'            => $amount,
                    'create_gettime'    => gettime()
                ));
            }
        }
        continue;
    }


    if (strtolower($bank['short_name']) == 'vcb' || strtolower($bank['short_name']) == 'vietcombank') {
        $result = curl_get2("https://api.web2m.com/historyapivcb/" . $bank['password'] . "/" . $bank['accountNumber'] . "/" . $bank['token']);
        if ($CMSNT->site('debug_auto_bank') == 1) {
            echo $result;
        }
        $result = json_decode($result, true);
        foreach ($result['data']['ChiTietGiaoDich'] as $data) {
            $tid            = check_string($data['SoThamChieu']);
            $description    = check_string($data['MoTa']);
            $amount         = check_string(str_replace(',', '', $data['SoTienGhiCo']));
            // TÁCH NỘI DUNG CHUYỂN TIỀN (findUserByDescription)
            if ($amount < $CMSNT->site('bank_min') || $amount > $CMSNT->site('bank_max')) {
                continue;
            }
            if ($getUser = findUserByDescription($description, $bankRechargeType)) {
                if ($CMSNT->num_rows_safe(" SELECT * FROM `payment_bank` WHERE `tid` = ? AND `description` = ? ", [$tid, $description]) == 0) {
                    $received = checkPromotion($amount);
                    $insertSv2 = $CMSNT->insert("payment_bank", array(
                        'tid'               => $tid,
                        'method'            => $bank['short_name'],
                        'user_id'           => $getUser['id'],
                        'description'       => $description,
                        'amount'            => $amount,
                        'received'          => $received,
                        'create_gettime'    => gettime(),
                        'create_time'       => time()
                    ));
                    if ($insertSv2) {
                        $isCong = $user->AddCredits($getUser['id'], $received, "Nạp tiền tự động qua " . $bank['short_name'] . " (#$tid - $description - $amount)", 'TOPUP_' . $bank['accountNumber'] . '_' . $tid);
                        if ($isCong) {

                            // XỬ LÝ TIỀN NỢ NẾU CÓ
                            debit_processing($getUser['id']);
                            // TẠO LOG GIAO DỊCH GẦN ĐÂY
                            $CMSNT->insert('deposit_log', [
                                'user_id'       => $getUser['id'],
                                'method'        => $bank['short_name'],
                                'amount'        => $amount,
                                'received'      => $received,
                                'create_time'   => time(),
                                'is_virtual'    => 0
                            ]);
                            /** SEND NOTI CHO ADMIN */
                            $my_text = $CMSNT->site('noti_recharge');
                            $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
                            $my_text = str_replace('{username}', $getUser['username'], $my_text);
                            $my_text = str_replace('{method}', $bank['short_name'], $my_text);
                            $my_text = str_replace('{amount}', format_currency($amount), $my_text);
                            $my_text = str_replace('{price}', format_currency($received), $my_text);
                            $my_text = str_replace('{time}', gettime(), $my_text);
                            sendMessAdmin($my_text);
                            // Gửi thông báo Telegram cho user (nếu đăng ký qua Telegram)
                            TelegramShopBot::notifyRecharge($getUser['id'], $bank['short_name'], format_currency($amount), $received);
                            echo '[<b style="color:green">-</b>] Xử lý thành công 1 hoá đơn.' . PHP_EOL;
                        }
                    }
                }
            }
            if ($CMSNT->get_row_safe(" SELECT COUNT(id) FROM `log_bank_auto` WHERE `tid` = ? AND `description` = ? AND `method` = ? ", [$tid, $description, $bank['short_name']])['COUNT(id)'] == 0) {
                $CMSNT->insert("log_bank_auto", array(
                    'tid'               => $tid,
                    'method'            => $bank['short_name'],
                    'description'       => $description,
                    'type'              => $data['type'],
                    'amount'            => $amount,
                    'create_gettime'    => gettime()
                ));
            }
        }
        continue;
    }


    if (strtolower($bank['short_name']) == 'vpbank' || strtolower($bank['short_name']) == 'vpb') {
        $result = curl_get2("https://api.web2m.com/historyapivpb/" . $bank['password'] . "/" . $bank['accountNumber'] . "/" . $bank['token']);
        if ($CMSNT->site('debug_auto_bank') == 1) {
            echo $result;
        }
        $result = json_decode($result, true);
        foreach ($result['d']['DepositAccountTransactions']['results'] as $data) {
            $tid            = check_string($data['ReferenceNumber']);
            $description    = check_string($data['Description']);
            $amount         = check_string($data['Amount']);
            // TÁCH NỘI DUNG CHUYỂN TIỀN (findUserByDescription)
            if ($amount < $CMSNT->site('bank_min') || $amount > $CMSNT->site('bank_max')) {
                continue;
            }
            if ($getUser = findUserByDescription($description, $bankRechargeType)) {
                if ($CMSNT->num_rows_safe(" SELECT * FROM `payment_bank` WHERE `tid` = ? AND `description` = ? ", [$tid, $description]) == 0) {
                    $received = checkPromotion($amount);
                    $insertSv2 = $CMSNT->insert("payment_bank", array(
                        'tid'               => $tid,
                        'method'            => $bank['short_name'],
                        'user_id'           => $getUser['id'],
                        'description'       => $description,
                        'amount'            => $amount,
                        'received'          => $received,
                        'create_gettime'    => gettime(),
                        'create_time'       => time()
                    ));
                    if ($insertSv2) {
                        $isCong = $user->AddCredits($getUser['id'], $received, "Nạp tiền tự động qua " . $bank['short_name'] . " (#$tid - $description - $amount)", 'TOPUP_' . $bank['accountNumber'] . '_' . $tid);
                        if ($isCong) {

                            // XỬ LÝ TIỀN NỢ NẾU CÓ
                            debit_processing($getUser['id']);
                            // TẠO LOG GIAO DỊCH GẦN ĐÂY
                            $CMSNT->insert('deposit_log', [
                                'user_id'       => $getUser['id'],
                                'method'        => $bank['short_name'],
                                'amount'        => $amount,
                                'received'      => $received,
                                'create_time'   => time(),
                                'is_virtual'    => 0
                            ]);
                            /** SEND NOTI CHO ADMIN */
                            $my_text = $CMSNT->site('noti_recharge');
                            $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
                            $my_text = str_replace('{username}', $getUser['username'], $my_text);
                            $my_text = str_replace('{method}', $bank['short_name'], $my_text);
                            $my_text = str_replace('{amount}', format_currency($amount), $my_text);
                            $my_text = str_replace('{price}', format_currency($received), $my_text);
                            $my_text = str_replace('{time}', gettime(), $my_text);
                            sendMessAdmin($my_text);
                            // Gửi thông báo Telegram cho user (nếu đăng ký qua Telegram)
                            TelegramShopBot::notifyRecharge($getUser['id'], $bank['short_name'], format_currency($amount), $received);
                            echo '[<b style="color:green">-</b>] Xử lý thành công 1 hoá đơn.' . PHP_EOL;
                        }
                    }
                }
            }
            if ($CMSNT->get_row_safe(" SELECT COUNT(id) FROM `log_bank_auto` WHERE `tid` = ? AND `description` = ? AND `method` = ? ", [$tid, $description, $bank['short_name']])['COUNT(id)'] == 0) {
                $CMSNT->insert("log_bank_auto", array(
                    'tid'               => $tid,
                    'method'            => $bank['short_name'],
                    'description'       => $description,
                    'type'              => $data['type'],
                    'amount'            => $amount,
                    'create_gettime'    => gettime()
                ));
            }
        }
        continue;
    }


    if (strtolower($bank['short_name']) == 'mb' || strtolower($bank['short_name']) == 'mbbank') {
        $result = curl_get2("https://api.web2m.com/historyapimbnoti/" . $bank['password'] . "/" . $bank['accountNumber'] . "/" . $bank['token']);
        if ($CMSNT->site('debug_auto_bank') == 1) {
            echo $result;
        }
        $result = json_decode($result, true);
        foreach ($result['data'] as $data) {
            $tid            = check_string($data['refNo']);
            $description    = str_replace(' ', '.', check_string($data['description']));
            $amount         = check_string($data['creditAmount']);
            // TÁCH NỘI DUNG CHUYỂN TIỀN (findUserByDescription)
            if ($amount < $CMSNT->site('bank_min') || $amount > $CMSNT->site('bank_max')) {
                continue;
            }
            if ($getUser = findUserByDescription($description, $bankRechargeType)) {
                if ($CMSNT->num_rows_safe(" SELECT * FROM `payment_bank` WHERE `tid` = ? AND `description` = ? ", [$tid, $description]) == 0) {
                    $received = checkPromotion($amount);
                    $insertSv2 = $CMSNT->insert("payment_bank", array(
                        'tid'               => $tid,
                        'method'            => $bank['short_name'],
                        'user_id'           => $getUser['id'],
                        'description'       => $description,
                        'amount'            => $amount,
                        'received'          => $received,
                        'create_gettime'    => gettime(),
                        'create_time'       => time()
                    ));
                    if ($insertSv2) {
                        $isCong = $user->AddCredits($getUser['id'], $received, "Nạp tiền tự động qua " . $bank['short_name'] . " (#$tid - $description - $amount)", 'TOPUP_' . $bank['accountNumber'] . '_' . $tid);
                        if ($isCong) {

                            // XỬ LÝ TIỀN NỢ NẾU CÓ
                            debit_processing($getUser['id']);
                            // TẠO LOG GIAO DỊCH GẦN ĐÂY
                            $CMSNT->insert('deposit_log', [
                                'user_id'       => $getUser['id'],
                                'method'        => $bank['short_name'],
                                'amount'        => $amount,
                                'received'      => $received,
                                'create_time'   => time(),
                                'is_virtual'    => 0
                            ]);
                            /** SEND NOTI CHO ADMIN */
                            $my_text = $CMSNT->site('noti_recharge');
                            $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
                            $my_text = str_replace('{username}', $getUser['username'], $my_text);
                            $my_text = str_replace('{method}', $bank['short_name'], $my_text);
                            $my_text = str_replace('{amount}', format_currency($amount), $my_text);
                            $my_text = str_replace('{price}', format_currency($received), $my_text);
                            $my_text = str_replace('{time}', gettime(), $my_text);
                            sendMessAdmin($my_text);
                            // Gửi thông báo Telegram cho user (nếu đăng ký qua Telegram)
                            TelegramShopBot::notifyRecharge($getUser['id'], $bank['short_name'], format_currency($amount), $received);
                            echo '[<b style="color:green">-</b>] Xử lý thành công 1 hoá đơn.' . PHP_EOL;
                        }
                    }
                }
            }
            if ($CMSNT->get_row_safe(" SELECT COUNT(id) FROM `log_bank_auto` WHERE `tid` = ? AND `description` = ? AND `method` = ? ", [$tid, $description, $bank['short_name']])['COUNT(id)'] == 0) {
                $CMSNT->insert("log_bank_auto", array(
                    'tid'               => $tid,
                    'method'            => $bank['short_name'],
                    'description'       => $description,
                    'type'              => $data['type'],
                    'amount'            => $amount,
                    'create_gettime'    => gettime()
                ));
            }
        }
        continue;
    }

    if (strtolower($bank['short_name']) == 'tpbank' || strtolower($bank['short_name']) == 'tpb') {
        $result = curl_get2("https://api.web2m.com/historyapitpb/" . $bank['token']);
        if ($CMSNT->site('debug_auto_bank') == 1) {
            echo $result;
        }
        $result = json_decode($result, true);
        foreach ($result['transactionInfos'] as $data) {
            $tid            = check_string($data['id']);
            $description    = check_string($data['description']);
            $amount         = check_string($data['amount']);
            // TÁCH NỘI DUNG CHUYỂN TIỀN (findUserByDescription)
            if ($amount < $CMSNT->site('bank_min') || $amount > $CMSNT->site('bank_max')) {
                continue;
            }
            if ($getUser = findUserByDescription($description, $bankRechargeType)) {
                if ($CMSNT->num_rows_safe(" SELECT * FROM `payment_bank` WHERE `tid` = ? AND `description` = ? ", [$tid, $description]) == 0) {
                    $received = checkPromotion($amount);
                    $insertSv2 = $CMSNT->insert("payment_bank", array(
                        'tid'               => $tid,
                        'method'            => $bank['short_name'],
                        'user_id'           => $getUser['id'],
                        'description'       => $description,
                        'amount'            => $amount,
                        'received'          => $received,
                        'create_gettime'    => gettime(),
                        'create_time'       => time()
                    ));
                    if ($insertSv2) {
                        $isCong = $user->AddCredits($getUser['id'], $received, "Nạp tiền tự động qua " . $bank['short_name'] . " (#$tid - $description - $amount)", 'TOPUP_' . $bank['accountNumber'] . '_' . $tid);
                        if ($isCong) {

                            // XỬ LÝ TIỀN NỢ NẾU CÓ
                            debit_processing($getUser['id']);
                            // TẠO LOG GIAO DỊCH GẦN ĐÂY
                            $CMSNT->insert('deposit_log', [
                                'user_id'       => $getUser['id'],
                                'method'        => $bank['short_name'],
                                'amount'        => $amount,
                                'received'      => $received,
                                'create_time'   => time(),
                                'is_virtual'    => 0
                            ]);
                            /** SEND NOTI CHO ADMIN */
                            $my_text = $CMSNT->site('noti_recharge');
                            $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
                            $my_text = str_replace('{username}', $getUser['username'], $my_text);
                            $my_text = str_replace('{method}', $bank['short_name'], $my_text);
                            $my_text = str_replace('{amount}', format_currency($amount), $my_text);
                            $my_text = str_replace('{price}', format_currency($received), $my_text);
                            $my_text = str_replace('{time}', gettime(), $my_text);
                            sendMessAdmin($my_text);
                            // Gửi thông báo Telegram cho user (nếu đăng ký qua Telegram)
                            TelegramShopBot::notifyRecharge($getUser['id'], $bank['short_name'], format_currency($amount), $received);
                            echo '[<b style="color:green">-</b>] Xử lý thành công 1 hoá đơn.' . PHP_EOL;
                        }
                    }
                }
            }
            if ($CMSNT->get_row_safe(" SELECT COUNT(id) FROM `log_bank_auto` WHERE `tid` = ? AND `description` = ? AND `method` = ? ", [$tid, $description, $bank['short_name']])['COUNT(id)'] == 0) {
                $CMSNT->insert("log_bank_auto", array(
                    'tid'               => $tid,
                    'method'            => $bank['short_name'],
                    'description'       => $description,
                    'type'              => '',
                    'amount'            => $amount,
                    'create_gettime'    => gettime()
                ));
            }
        }
        continue;
    }

    if (strtolower($bank['short_name']) == 'bidv') {
        $result = curl_get2("https://api.web2m.com/historyapibidv/" . $bank['password'] . "/" . $bank['accountNumber'] . "/" . $bank['token']);
        if ($CMSNT->site('debug_auto_bank') == 1) {
            echo $result;
        }
        $result = json_decode($result, true);
        foreach ($result['data']['ChiTietGiaoDich'] as $data) {
            $tid            = check_string($data['SoThamChieu']);
            $description    = check_string($data['MoTa']);
            $amount         = check_string(str_replace(',', '', $data['SoTienGhiCo']));
            // TÁCH NỘI DUNG CHUYỂN TIỀN (findUserByDescription)
            if ($amount < $CMSNT->site('bank_min') || $amount > $CMSNT->site('bank_max')) {
                continue;
            }
            if ($getUser = findUserByDescription($description, $bankRechargeType)) {
                if ($CMSNT->num_rows_safe(" SELECT * FROM `payment_bank` WHERE `tid` = ? AND `description` = ? ", [$tid, $description]) == 0) {
                    $received = checkPromotion($amount);
                    $insertSv2 = $CMSNT->insert("payment_bank", array(
                        'tid'               => $tid,
                        'method'            => $bank['short_name'],
                        'user_id'           => $getUser['id'],
                        'description'       => $description,
                        'amount'            => $amount,
                        'received'          => $received,
                        'create_gettime'    => gettime(),
                        'create_time'       => time()
                    ));
                    if ($insertSv2) {
                        $isCong = $user->AddCredits($getUser['id'], $received, "Nạp tiền tự động qua " . $bank['short_name'] . " (#$tid - $description - $amount)", 'TOPUP_' . $bank['accountNumber'] . '_' . $tid);
                        if ($isCong) {

                            // XỬ LÝ TIỀN NỢ NẾU CÓ
                            debit_processing($getUser['id']);
                            // TẠO LOG GIAO DỊCH GẦN ĐÂY
                            $CMSNT->insert('deposit_log', [
                                'user_id'       => $getUser['id'],
                                'method'        => $bank['short_name'],
                                'amount'        => $amount,
                                'received'      => $received,
                                'create_time'   => time(),
                                'is_virtual'    => 0
                            ]);
                            /** SEND NOTI CHO ADMIN */
                            $my_text = $CMSNT->site('noti_recharge');
                            $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
                            $my_text = str_replace('{username}', $getUser['username'], $my_text);
                            $my_text = str_replace('{method}', $bank['short_name'], $my_text);
                            $my_text = str_replace('{amount}', format_currency($amount), $my_text);
                            $my_text = str_replace('{price}', format_currency($received), $my_text);
                            $my_text = str_replace('{time}', gettime(), $my_text);
                            sendMessAdmin($my_text);
                            // Gửi thông báo Telegram cho user (nếu đăng ký qua Telegram)
                            TelegramShopBot::notifyRecharge($getUser['id'], $bank['short_name'], format_currency($amount), $received);
                            echo '[<b style="color:green">-</b>] Xử lý thành công 1 hoá đơn.' . PHP_EOL;
                        }
                    }
                }
            }
            if ($CMSNT->get_row_safe(" SELECT COUNT(id) FROM `log_bank_auto` WHERE `tid` = ? AND `description` = ? AND `method` = ? ", [$tid, $description, $bank['short_name']])['COUNT(id)'] == 0) {
                $CMSNT->insert("log_bank_auto", array(
                    'tid'               => $tid,
                    'method'            => $bank['short_name'],
                    'description'       => $description,
                    'type'              => $data['type'],
                    'amount'            => $amount,
                    'create_gettime'    => gettime()
                ));
            }
        }
        continue;
    }

    if (strtolower($bank['short_name']) == 'seabank' || strtolower($bank['short_name']) == 'seab') {
        $result = curl_get2("https://api.web2m.com/historyapiseabank/" . $bank['token']);
        if ($CMSNT->site('debug_auto_bank') == 1) {
            echo $result;
        }
        $result = json_decode($result, true);
        foreach ($result['data'] as $data) {
            $tid            = check_string($data['transID']);
            $description    = check_string($data['description']);
            $amount         = check_string($data['totalAmount']);
            // TÁCH NỘI DUNG CHUYỂN TIỀN (findUserByDescription)
            if ($amount < $CMSNT->site('bank_min') || $amount > $CMSNT->site('bank_max')) {
                continue;
            }
            if ($getUser = findUserByDescription($description, $bankRechargeType)) {
                if ($CMSNT->num_rows_safe(" SELECT * FROM `payment_bank` WHERE `tid` = ? AND `description` = ? ", [$tid, $description]) == 0) {
                    $received = checkPromotion($amount);
                    $insertSv2 = $CMSNT->insert("payment_bank", array(
                        'tid'               => $tid,
                        'method'            => $bank['short_name'],
                        'user_id'           => $getUser['id'],
                        'description'       => $description,
                        'amount'            => $amount,
                        'received'          => $received,
                        'create_gettime'    => gettime(),
                        'create_time'       => time()
                    ));
                    if ($insertSv2) {
                        $isCong = $user->AddCredits($getUser['id'], $received, "Nạp tiền tự động qua " . $bank['short_name'] . " (#$tid - $description - $amount)", 'TOPUP_' . $bank['accountNumber'] . '_' . $tid);
                        if ($isCong) {

                            // XỬ LÝ TIỀN NỢ NẾU CÓ
                            debit_processing($getUser['id']);
                            // TẠO LOG GIAO DỊCH GẦN ĐÂY
                            $CMSNT->insert('deposit_log', [
                                'user_id'       => $getUser['id'],
                                'method'        => $bank['short_name'],
                                'amount'        => $amount,
                                'received'      => $received,
                                'create_time'   => time(),
                                'is_virtual'    => 0
                            ]);
                            /** SEND NOTI CHO ADMIN */
                            $my_text = $CMSNT->site('noti_recharge');
                            $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
                            $my_text = str_replace('{username}', $getUser['username'], $my_text);
                            $my_text = str_replace('{method}', $bank['short_name'], $my_text);
                            $my_text = str_replace('{amount}', format_currency($amount), $my_text);
                            $my_text = str_replace('{price}', format_currency($received), $my_text);
                            $my_text = str_replace('{time}', gettime(), $my_text);
                            sendMessAdmin($my_text);
                            // Gửi thông báo Telegram cho user (nếu đăng ký qua Telegram)
                            TelegramShopBot::notifyRecharge($getUser['id'], $bank['short_name'], format_currency($amount), $received);
                            echo '[<b style="color:green">-</b>] Xử lý thành công 1 hoá đơn.' . PHP_EOL;
                        }
                    }
                }
            }
            if ($CMSNT->get_row_safe(" SELECT COUNT(id) FROM `log_bank_auto` WHERE `tid` = ? AND `description` = ? AND `method` = ? ", [$tid, $description, $bank['short_name']])['COUNT(id)'] == 0) {
                $CMSNT->insert("log_bank_auto", array(
                    'tid'               => $tid,
                    'method'            => $bank['short_name'],
                    'description'       => $description,
                    'type'              => $data['type'],
                    'amount'            => $amount,
                    'create_gettime'    => gettime()
                ));
            }
        }
        continue;
    }
}
