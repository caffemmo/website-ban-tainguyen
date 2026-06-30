<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$CMSNT = new DB();



if (isSecureCookie('admin_login') != true) {
    logout_admin();
} else {
    $token = validate_alphanumeric($_COOKIE['admin_login'], 255);
    if ($token === false) {
        logout_admin();
    }
    $getUser = $CMSNT->get_row_safe(" SELECT * FROM `users` WHERE `token` = ? AND `admin` != 0", [$token]);
    // chuyển hướng đăng nhập khi thông tin login không tồn tại
    if (!$getUser) {
        // Rate limit
        checkBlockIP('SCAN', 5);
        logout_admin();
    }
    if($CMSNT->site('status_only_device_admin') == 1){
        if ($getUser['device'] != getUserAgent()){
            logout_admin();
        }
    }
    // chuyển hướng khi bị khoá tài khoản
    if ($getUser['banned'] != 0) {
        redirect(base_url('common/banned'));
    }
    if($getUser['admin'] <= 0){
        // Rate limit
        checkBlockIP('ADMIN', 5);
        logout_admin();
    }
    // khoá tài khoản trường hợp âm tiền, tránh bug
    if ($getUser['money'] < -500) {
        $User = new users();
        $User->Banned($getUser['id'], 'Tài khoản âm tiền, ghi vấn bug');
        redirect(base_url('common/banned'));
    }
    if($CMSNT->site('status_only_ip_login_admin') == 1){
        if($getUser['ip'] != myip()){
            logout_admin();
        }
    }
    

    /* cập nhật thời gian online */
    $CMSNT->update("users", [
        'time_session'  => time()
    ], " `id` = ?", [$getUser['id']]);

    // log_admin_request();
    
}
