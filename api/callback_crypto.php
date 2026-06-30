<?php

// define("IN_SITE", true);
// require_once(__DIR__."/../libs/db.php");
// require_once(__DIR__."/../libs/lang.php");
// require_once(__DIR__."/../libs/helper.php");
// require_once(__DIR__."/../libs/database/users.php");
// $CMSNT = new DB();
 
 

// if(empty($_GET['request_id'])){
//     die('request_id empty');
// }
// if(empty($_GET['token'])){
//     die('token empty');
// }
// if(empty($_GET['status'])){
//     die('status empty');
// }

// // DỮ LIỆU CALLBACK VỀ
// $request_id  = isset($_GET['request_id']) ? validate_alphanumeric($_GET['request_id'], 255) : NULL; // REQUEST ID XÁC MINH GIAO DỊCH
// $token = isset($_GET['token']) ? validate_string($_GET['token'], 2000) : NULL; // TOKEN XÁC MINH
// $received = isset($_GET['received']) ? validate_float($_GET['received'], 0.0) : NULL; // SỐ TIỀN NHẬN ĐƯỢC
// $status = isset($_GET['status']) ? validate_string($_GET['status'], 50) : NULL; // TRẠNG THÁI HOÁ ĐƠN
// $from_address = isset($_GET['from_address']) ? validate_string($_GET['from_address'], 2000) : NULL; // ĐỊA CHỈ NGƯỜI GỬI
// $transaction_id = isset($_GET['transaction_id']) ? validate_string($_GET['transaction_id'], 2000) : NULL; // MÃ GIAO DỊCH TRÊN BLOCKCHAIN
     
// if($token === false || $token != $CMSNT->site('crypto_token')){
//     die('Token xác minh không chính xác');
// }

// if($request_id === false || !$row = $CMSNT->get_row_safe(" SELECT * FROM `payment_crypto` WHERE `request_id` = ? ", [$request_id])){
//     die('Hoá đơn không tồn tại');
// }
// $amount = $row['received'];
// // xử lý khuyến mãi
// $received = checkPromotion($amount);
// $getUser = $CMSNT->get_row_safe(" SELECT * FROM `users` WHERE `id` = ? ", [$row['user_id']]);

// // HOÁ ĐƠN ĐÃ CỘNG TIỀN SẼ KHÔNG THAY ĐỔI TRẠNG THÁI
// if($row['status'] == 'completed'){
//     die('Hoá đơn này đã được xử lý rồi');
// }

// // XỬ LÝ HOÁ ĐƠN HẾT HẠN
// if($status == 'expired'){
//     $CMSNT->update('payment_crypto', [
//         'status'            => 'expired',
//         'update_gettime'    => gettime()
//     ], " `id` = ? ", [$row['id']]);
//     die('cập nhật trạng thái expired');
// }

// // XỬ LÝ HOÁ ĐƠN HOÀN TẤT
// if($status == 'completed'){
//     $isUpdate = $CMSNT->update('payment_crypto', [
//         'status'            => 'completed',
//         'update_gettime'    => gettime()
//     ], " `id` = ? ", [$row['id']]);
//     if($isUpdate){
//         $User = new users();
//         $isCong = $User->AddCredits($row['user_id'], $received, "Crypto Recharge #".$row['trans_id'], 'TOPUP_CRYPTO_'.$row['trans_id']);
//         if($isCong){
            
//             /** SEND NOTI CHO ADMIN */
//             $my_text = $CMSNT->site('noti_recharge');
//             $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
//             $my_text = str_replace('{username}', $getUser['username'], $my_text);
//             $my_text = str_replace('{method}', 'Crypto', $my_text);
//             $my_text = str_replace('{amount}', format_currency($amount), $my_text);
//             $my_text = str_replace('{price}', format_currency($received), $my_text);
//             $my_text = str_replace('{time}', gettime(), $my_text);
//             sendMessAdmin($my_text);

//             // TẠO LOG GIAO DỊCH GẦN ĐÂY
//             $CMSNT->insert('deposit_log',[
//                 'user_id'       => $getUser['id'],
//                 'method'        => 'USDT',
//                 'amount'        => $amount,
//                 'received'      => $received,
//                 'create_time'   => time(),
//                 'is_virtual'    => 0
//             ]);
//             die('Cập nhật trạng thái completed thành công!');
//         }else{
//             die('Hóa đơn này đã được cộng tiền rồi');
//         }
//     }
// }