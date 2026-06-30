<?php
define("IN_SITE", true);
require_once(__DIR__.'/../../config.php');
require_once(__DIR__.'/../../libs/db.php');
require_once(__DIR__."/../../libs/lang.php");
require_once(__DIR__.'/../../libs/helper.php');
require_once(__DIR__.'/../../libs/database/users.php');
require_once(__DIR__.'/../../models/is_ctv.php');

$CMSNT = new DB();

// Kiểm tra demo site
if ($CMSNT->site('status_demo') != 0) {
    $data = json_encode([
        'status'    => 'error',
        'msg'       => __('This function cannot be used because this is a demo site')
    ]);
    die($data);
}

// Kiểm tra action
if(!isset($_POST['action'])){
    $data = json_encode([
        'status'    => 'error',
        'msg'       => __('The Request Not Found')
    ]);
    die($data);   
}

if($CMSNT->site('ctv_status') != 1){
    die(json_encode(['status' => 'error', 'msg' => __('CTV Panel đang bị tắt trong cài đặt hệ thống.')]));
}

// Xử lý rút tiền CTV
if($_POST['action'] == 'CtvWithdraw'){
    // Validate dữ liệu đầu vào
    $token = validate_alphanumeric($_POST['token'], 255);
    if ($token === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Token không hợp lệ!')]));
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
    
    // Kiểm tra token
    if($token != $getUser['token']){
        die(json_encode(['status' => 'error', 'msg' => __('Token không hợp lệ!')]));
    }
    
    // Validate dữ liệu
    if(empty($bank) || empty($stk) || empty($name) || $amount <= 0){
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng điền đầy đủ thông tin!')]));
    }
    
    // Kiểm tra ngân hàng có trong danh sách được phép
    $allowedBanks = explode(PHP_EOL, $CMSNT->site('ctv_banks_withdraw'));
    $allowedBanks = array_map('trim', $allowedBanks); // Loại bỏ khoảng trắng
    $allowedBanks = array_filter($allowedBanks); // Loại bỏ phần tử rỗng
    
    if(!in_array($bank, $allowedBanks)){
        die(json_encode(['status' => 'error', 'msg' => __('Ngân hàng không được hỗ trợ!')]));
    }
    
    // Kiểm tra số tiền tối thiểu
    if($amount < $CMSNT->site('ctv_min_withdraw')){
        die(json_encode(['status' => 'error', 'msg' => __('Số tiền rút tối thiểu là').' '.format_currency($CMSNT->site('ctv_min_withdraw'))]));
    }
    
    // Kiểm tra số dư
    if($amount > getRowRealtime('users', $getUser['id'], 'money')){
        die(json_encode(['status' => 'error', 'msg' => __('Số tiền rút vượt quá số dư khả dụng!')]));
    }
    
    // Tạo mã giao dịch
    $trans_id = $CMSNT->site('ctv_prefix_withdraw') . time() . rand(1000, 9999);
    
    // Kiểm tra mã giao dịch trùng lặp
    while($CMSNT->get_row_safe("SELECT * FROM `ctv_withdraw` WHERE `trans_id` = ?", [$trans_id])){
        $trans_id = $CMSNT->site('ctv_prefix_withdraw') . time() . rand(1000, 9999);
    }
    
    $User = new users();
    $isRemoveCredit = $User->RemoveCredits($getUser['id'], $amount, __('[CTV] Tạo yêu cầu rút tiền CTV').' #'.$trans_id, 'CTV_WITHDRAW_'.$trans_id);
    if(!$isRemoveCredit){
        die(json_encode(['status' => 'error', 'msg' => __('Không thể trừ tiền của CTV, vui lòng liên hệ Admin')]));
    }
    // Tính toán phí rút tiền
    $feePercent = $CMSNT->site('ctv_fee_withdraw');
    $fee = ($amount * $feePercent) / 100;
    $receive = $amount - $fee;
    
    // Thêm yêu cầu rút tiền vào database
    $insert = $CMSNT->insert("ctv_withdraw", [
        'trans_id'  => $trans_id,
        'user_id'   => $getUser['id'],
        'bank'      => $bank,
        'stk'       => $stk,
        'name'      => $name,
        'amount'    => $amount,    // Tổng số tiền CTV rút
        'fee'       => $fee,       // Phí rút tiền
        'receive'   => $receive,   // Số tiền thực nhận sau khi trừ phí
        'created_at' => gettime(),
        'updated_at' => gettime(),
        'status'    => 'pending',
        'reason'    => ''
    ]);
    
    if($insert){
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('[CTV] Tạo yêu cầu rút tiền').' ('.$trans_id.')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);    
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        
        die(json_encode([
            'status' => 'success', 
            'msg' => __('Yêu cầu rút tiền đã được gửi thành công! Vui lòng chờ admin xử lý.')
        ]));
    } else {
        die(json_encode(['status' => 'error', 'msg' => __('Có lỗi xảy ra, vui lòng thử lại!')]));
    }
}

// Xử lý các action khác nếu cần
die(json_encode(['status' => 'error', 'msg' => __('Thao tác không hợp lệ!')]));
 
