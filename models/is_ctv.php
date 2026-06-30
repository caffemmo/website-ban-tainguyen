<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$CMSNT = new DB();



if (isSecureCookie('ctv_login') != true) {
    redirect(base_url('client/logout'));
} else {
    $token = validate_alphanumeric($_COOKIE['ctv_login'], 255);
    if ($token === false) {
        redirect(base_url('client/logout'));
    }
    $getUser = $CMSNT->get_row_safe(" SELECT * FROM `users` WHERE `token` = ? AND `ctv` != 0", [$token]);
    // chuyển hướng đăng nhập khi thông tin login không tồn tại
    if (!$getUser) {
        // Rate limit
        checkBlockIP('SCAN', 5);
        redirect(base_url('client/logout'));
    }
    if ($CMSNT->site('status_only_device_admin') == 1) {
        if ($getUser['device'] != getUserAgent()) {
            redirect(base_url('client/logout'));
        }
    }
    // chuyển hướng khi bị khoá tài khoản
    if ($getUser['banned'] != 0) {
        redirect(base_url('common/banned'));
    }
    if ($getUser['ctv'] <= 0) {
        // Rate limit
        checkBlockIP('CTV', 5);
        redirect(base_url('client/logout'));
    }
    // khoá tài khoản trường hợp âm tiền, tránh bug
    if ($getUser['money'] < -500) {
        $User = new users();
        $User->Banned($getUser['id'], 'Tài khoản âm tiền, ghi vấn bug');
        redirect(base_url('common/banned'));
    }
    if ($CMSNT->site('status_only_ip_login_admin') == 1) {
        if ($getUser['ip'] != myip()) {
            redirect(base_url('client/logout'));
        }
    }


    /* cập nhật thời gian online */
    $CMSNT->update("users", [
        'time_session'  => time()
    ], " `id` = ?", [$getUser['id']]);

    // log_admin_request();

}
