<?php

define("IN_SITE", true);
require_once(__DIR__ . "/../../config.php");
require_once(__DIR__ . "/../../libs/db.php");
require_once(__DIR__ . "/../../libs/lang.php");
require_once(__DIR__ . "/../../libs/helper.php");
require_once(__DIR__ . '/../../libs/database/users.php');
require_once(__DIR__ . "/../../libs/sendEmail.php");
require_once(__DIR__ . "/../../libs/SMTPMailer.php");

$CMSNT = new DB();

use PragmaRX\Google2FAQRCode\Google2FA;


if (!isset($_POST['action'])) {
    die(json_encode(['status' => 'error', 'msg' => __('Thao tác không hợp lệ')]));
}

if ($CMSNT->site('status') != 1 && isSecureCookie('admin_login') != true) {
    die(json_encode(['status' => 'error', 'msg' => __('The system is under maintenance, please come back later!')]));
}

// ĐĂNG NHẬP
if ($_POST['action'] == 'Login') {
    // Kiểm tra CSRF Token để ngăn chặn tấn công CSRF
    // if (empty($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    //     die(json_encode([
    //         'status' => 'error',
    //         'msg'    => __('Invalid CSRF Protection Token')
    //     ]));
    // }
    // Validate input với validation functions mới
    $username = validate_string($_POST['username'], 50);
    if ($username === false) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Vui lòng nhập username')
        ]));
    }

    if (empty($_POST['password'])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Vui lòng nhập mật khẩu')
        ]));
    }
    $password_result = validate_password($_POST['password'], 1, 255);
    if (!$password_result['success']) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => $password_result['message']
        ]));
    }
    $password = $password_result['password'];

    // Xác thực Captcha (tự điều kiện theo module trong verifyCaptchaResponse)
    $captchaResponse = check_string($_POST['captcha_response'] ?? $_POST['recaptcha'] ?? $_POST['cf-turnstile-response'] ?? '');
    if ($captchaResponse === false) {
        $captchaResponse = '';
    }
    $captchaResult = verifyCaptchaResponse($captchaResponse, myip(), 'login');
    if (!$captchaResult['success']) {
        die(json_encode(['status' => 'error', 'msg' => $captchaResult['error_message']]));
    }

    // Sử dụng prepared statement để tránh SQL injection
    $getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `username` = ?", [$username]);
    if (!$getUser) {
        // Rate limit
        checkBlockIP('LOGIN', 5);

        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Thông tin đăng nhập không chính xác')
        ]));
    }
    if (time() > $getUser['time_request']) {
        if (time() - $getUser['time_request'] < $config['max_time_load']) {
            die(json_encode(['status' => 'error', 'msg' => __('Bạn đang thao tác quá nhanh, vui lòng đợi')]));
        }
    }
    if ($CMSNT->site('type_password') == 'bcrypt') {
        if (!password_verify($password, $getUser['password'])) {
            // Rate limit
            checkBlockIP('LOGIN', 5);
            if ($getUser['login_attempts'] >= $CMSNT->site('limit_block_client_login')) {
                $User = new users();
                $User->Banned($getUser['id'], __('Đăng nhập thất bại nhiều lần'));
                die(json_encode(['status' => 'error', 'msg' => __('Tài khoản của bạn đã bị tạm khoá do đang nhập sai nhiều lần')]));
            }
            $CMSNT->cong('users', 'login_attempts', 1, " `id` = ? ", [$getUser['id']]);
            die(json_encode([
                'status'    => 'error',
                'msg'       => __('Thông tin đăng nhập không chính xác')
            ]));
        }
    } else {
        if ($getUser['password'] != TypePassword($password)) {
            // Rate limit
            checkBlockIP('LOGIN', 5);
            if ($getUser['login_attempts'] >= $CMSNT->site('limit_block_client_login')) {
                $User = new users();
                $User->Banned($getUser['id'], __('Đăng nhập thất bại nhiều lần'));
                die(json_encode(['status' => 'error', 'msg' => __('Tài khoản của bạn đã bị tạm khoá do đang nhập sai nhiều lần')]));
            }
            $CMSNT->cong('users', 'login_attempts', 1, " `id` = ? ", [$getUser['id']]);
            die(json_encode([
                'status'    => 'error',
                'msg'       => __('Thông tin đăng nhập không chính xác')
            ]));
        }
    }
    if ($getUser['banned'] == 1) {
        die(json_encode(['status' => 'error', 'msg' => __('Tài khoản của bạn đã bị khoá truy cập')]));
    }
    if ($getUser['status_otp_mail'] == 1) {
        $otp_mail = random('QWERTYUOPASDFGHJKZXCVBNM0126456789', 6);
        $token_otp_mail = md5(uniqid()) . md5(random('QWERTYUOPASDFGHJKZXCVBNM0126456789', 12));
        $CMSNT->update('users', [
            'token_otp_mail'    => $token_otp_mail,
            'otp_mail'          => $otp_mail,
            'limit_otp_mail'    => 0
        ], " `id` = '" . $getUser['id'] . "' ");
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => '[Warning] ' . __('Đăng nhập thành công - đang tiến hành đến bước xác minh OTP Mail')
        ]);
        if ($CMSNT->site('email_temp_subject_otp_mail') != '') {
            $content = $CMSNT->site('email_temp_content_otp_mail');
            $content = str_replace('{domain}', $_SERVER['SERVER_NAME'], $content);
            $content = str_replace('{title}', $CMSNT->site('title'), $content);
            $content = str_replace('{username}', $getUser['username'], $content);
            $content = str_replace('{otp}', $otp_mail, $content);
            $content = str_replace('{ip}', myip(), $content);
            $content = str_replace('{device}', getUserAgent(), $content);
            $content = str_replace('{time}', gettime(), $content);
            ////////////////////////////////////////////////////////////////////
            $subject = $CMSNT->site('email_temp_subject_otp_mail');
            $subject = str_replace('{domain}', $_SERVER['SERVER_NAME'], $subject);
            $subject = str_replace('{title}', $CMSNT->site('title'), $subject);
            $subject = str_replace('{username}', $getUser['username'], $subject);
            $subject = str_replace('{otp}', $otp_mail, $subject);
            $subject = str_replace('{ip}', myip(), $subject);
            $subject = str_replace('{device}', getUserAgent(), $subject);
            $subject = str_replace('{time}', gettime(), $subject);
            $bcc = $CMSNT->site('title');
            sendCSM($getUser['email'], $getUser['username'], $subject, $content, $bcc);
        }

        die(json_encode([
            'status'    => 'verify',
            'url'       => base_url('?action=verify_otp&token=' . $token_otp_mail),
            'msg'       => __('Vui lòng xác minh OTP để hoàn tất quá trình đăng nhập')
        ]));
    }
    if ($getUser['status_2fa'] == 1) {
        $token_2fa = md5(random('qwertyuiopasdfghjklzxcvbnm0123456789', 55)) . md5(uniqid());
        $CMSNT->update('users', [
            'token_2fa' => $token_2fa,
            'limit_2fa' => 0
        ], " `id` = '" . $getUser['id'] . "' ");
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => '[Warning] ' . __('Đăng nhập thành công - đang tiến hành đến bước xác minh 2FA')
        ]);
        die(json_encode([
            'status'    => 'verify',
            'url'       => base_url('?action=verify_2fa&token=' . $token_2fa),
            'msg'       => __('Vui lòng xác minh 2FA để hoàn tất quá trình đăng nhập')
        ]));
    }
    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => '[Warning] ' . __('Thực hiện đăng nhập vào website')
    ]);
    $remember_token = generateRememberToken($getUser['remember_token'], $getUser['ip']);
    $CMSNT->update("users", [
        'remember_token'    => $remember_token,
        'token_2fa'         => NULL,
        'limit_2fa'         => 0,
        'token_otp_mail'    => NULL,
        'limit_otp_mail'    => 0,
        'otp_mail'          => NULL,
        'ip'                => myip(),
        'time_request'      => time(),
        'time_session'      => time(),
        'update_date'       => gettime(),
        'device'            => getUserAgent()
    ], " `id` = '" . $getUser['id'] . "' ");
    // THÔNG BÁO VỀ MAIL KHI LOGIN (Queue - non-blocking)
    if ($getUser['status_noti_login_to_mail'] == 1 && $CMSNT->site('email_temp_subject_warning_login') != '') {
        try {
            $mailer = new SMTPMailer($CMSNT);
            $mailer->queueWarningLoginEmail($getUser, [
                '{username}' => $getUser['username'],
                '{ip}' => myip(),
                '{device}' => getUserAgent(),
                '{time}' => gettime()
            ]);
        } catch (Exception $e) {
            error_log('[EmailQueue] Lỗi queue warning login email: ' . $e->getMessage());
        }
    }

    // Lưu phiên đăng nhập
    setSecureCookie('user_login', $getUser['token']);
    setSecureCookie('user_agent', getUserAgent());
    if ($getUser['admin'] > 0) {
        // Lưu phiên của Admin
        setSecureCookie('admin_login', $getUser['token']);
    }
    if ($getUser['ctv'] > 0) {
        // Lưu phiên của Ctv
        setSecureCookie('ctv_login', $getUser['token']);
    }
    die(json_encode(['status' => 'success', 'msg' => __('Đăng nhập thành công!')]));
}

// XÁC MINH 2FA
if ($_POST['action'] == 'Verify2FA') {

    if (empty($_POST['token_2fa'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng thực hiện đăng nhập lại')]));
    }
    $token_2fa = validate_alphanumeric($_POST['token_2fa'], 255);
    if ($token_2fa === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Token không hợp lệ')]));
    }

    if (empty($_POST['code'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập mã xác minh')]));
    }
    $code = validate_alphanumeric($_POST['code'], 10);
    if ($code === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Mã xác minh không hợp lệ')]));
    }

    // Xác thực Captcha
    $captchaResponse = check_string($_POST['captcha_response'] ?? $_POST['recaptcha'] ?? $_POST['cf-turnstile-response'] ?? '');
    if ($captchaResponse === false) {
        $captchaResponse = '';
    }
    $captchaResult = verifyCaptchaResponse($captchaResponse, myip(), 'verify_2fa');
    if (!$captchaResult['success']) {
        die(json_encode(['status' => 'error', 'msg' => $captchaResult['error_message']]));
    }

    // Sử dụng prepared statement
    $getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token_2fa` = ? AND `token_2fa` IS NOT NULL", [$token_2fa]);
    if (!$getUser) {
        // Rate limit
        checkBlockIP('2FA', 5);
        die(json_encode(['status' => 'error', 'msg' => __('Thông tin đăng nhập không chính xác')]));
    }
    if (empty($getUser['token_2fa'])) {
        // Rate limit
        checkBlockIP('2FA', 5);
        die(json_encode(['status' => 'error', 'msg' => __('Dữ liệu không hợp lệ')]));
    }
    if ($getUser['limit_2fa'] >= 5) {
        $CMSNT->update("users", [
            'limit_2fa' => 0,
            'token_2fa' => NULL
        ], " `id` = '" . $getUser['id'] . "' ");
        $CMSNT->insert('block_ip', [
            'ip'                => myip(),
            'attempts'          => $getUser['limit_2fa'],
            'create_gettime'    => gettime(),
            'banned'            => 1,
            'reason'            => __('Nhập sai 2FA quá 5 lần')
        ]);
        // Rate limit
        checkBlockIP('2FA', 5);
        die(json_encode(['status' => 'error', 'msg' => __('Bạn đã nhập sai quá nhiều lần, vui lòng xác minh lại từ đầu')]));
    }
    $google2fa = new Google2FA();
    if ($google2fa->verifyKey($getUser['SecretKey_2fa'], $code, 2) != true) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => '[Warning] Phát hiện có người đang cố gắng nhập mã xác minh 2FA'
        ]);
        $CMSNT->cong('users', 'limit_2fa', 1, " `id` = ? ", [$getUser['id']]);
        // Rate limit
        checkBlockIP('2FA', 5);
        die(json_encode(['status' => 'error', 'msg' => __('Mã xác minh không chính xác')]));
    }
    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => '[Warning] ' . __('Xác thực 2FA thành công - đã đăng nhập vào website')
    ]);
    $remember_token = generateRememberToken($getUser['remember_token'], $getUser['ip']);
    $CMSNT->update("users", [
        'remember_token'    => $remember_token,
        'token_2fa' => NULL,
        'limit_2fa' => 0,
        'ip'        => myip(),
        'time_request' => time(),
        'time_session' => time(),
        'update_date'       => gettime(),
        'device'    => getUserAgent()
    ], " `id` = '" . $getUser['id'] . "' ");
    // THÔNG BÁO VỀ MAIL KHI LOGIN (Queue - non-blocking)
    if ($getUser['status_noti_login_to_mail'] == 1 && $CMSNT->site('email_temp_subject_warning_login') != '') {
        try {
            $mailer = new SMTPMailer($CMSNT);
            $mailer->queueWarningLoginEmail($getUser, [
                '{username}' => $getUser['username'],
                '{ip}' => myip(),
                '{device}' => getUserAgent(),
                '{time}' => gettime()
            ]);
        } catch (Exception $e) {
            error_log('[EmailQueue] Lỗi queue warning login email: ' . $e->getMessage());
        }
    }
    // Lưu phiên đăng nhập
    setSecureCookie('user_login', $getUser['token']);
    setSecureCookie('user_agent', getUserAgent());
    if ($getUser['admin'] > 0) {
        // Lưu phiên của Admin
        setSecureCookie('admin_login', $getUser['token']);
    }
    if ($getUser['ctv'] > 0) {
        // Lưu phiên của Ctv
        setSecureCookie('ctv_login', $getUser['token']);
    }
    die(json_encode(['status' => 'success', 'msg' => __('Đăng nhập thành công!')]));
}

// XÁC MINH OTP
if ($_POST['action'] == 'VerifyOTP') {
    if (empty($_POST['token_otp_mail'])) {
        // Rate limit
        checkBlockIP('OTP', 15);
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng thực hiện đăng nhập lại')]));
    }
    $token_otp_mail = validate_alphanumeric($_POST['token_otp_mail'], 255);
    if ($token_otp_mail === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Token không hợp lệ')]));
    }

    if (empty($_POST['code'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập OTP')]));
    }
    $code = validate_alphanumeric($_POST['code'], 10);
    if ($code === false) {
        die(json_encode(['status' => 'error', 'msg' => __('OTP không hợp lệ')]));
    }

    // Xác thực Captcha
    $captchaResponse = check_string($_POST['captcha_response'] ?? $_POST['recaptcha'] ?? $_POST['cf-turnstile-response'] ?? '');
    if ($captchaResponse === false) {
        $captchaResponse = '';
    }
    $captchaResult = verifyCaptchaResponse($captchaResponse, myip(), 'verify_otp');
    if (!$captchaResult['success']) {
        die(json_encode(['status' => 'error', 'msg' => $captchaResult['error_message']]));
    }

    // Sử dụng prepared statement
    $getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token_otp_mail` = ? AND `token_otp_mail` IS NOT NULL", [$token_otp_mail]);
    if (!$getUser) {
        // Rate limit
        checkBlockIP('OTP', 15);
        die(json_encode(['status' => 'error', 'msg' => __('Thông tin đăng nhập không chính xác')]));
    }
    if (empty($getUser['token_otp_mail'])) {
        // Rate limit
        checkBlockIP('OTP', 15);
        die(json_encode(['status' => 'error', 'msg' => __('Thông tin đăng nhập không chính xác')]));
    }
    if ($getUser['limit_otp_mail'] >= 5) {
        $CMSNT->update("users", [
            'limit_otp_mail' => 0,
            'token_otp_mail' => NULL,
            'otp_mail'       => NULL
        ], " `id` = '" . $getUser['id'] . "' ");
        $CMSNT->insert('block_ip', [
            'ip'                => myip(),
            'attempts'          => $getUser['limit_otp_mail'],
            'create_gettime'    => gettime(),
            'banned'            => 1,
            'reason'            => __('Nhập sai OTP quá 5 lần')
        ]);
        // Rate limit
        checkBlockIP('OTP', 15);
        die(json_encode(['status' => 'error', 'msg' => __('Bạn đã nhập sai quá nhiều lần, vui lòng xác minh lại từ đầu')]));
    }
    if ($code != $getUser['otp_mail']) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => '[Warning] Phát hiện có người đang cố gắng nhập OTP'
        ]);
        $CMSNT->cong('users', 'limit_otp_mail', 1, " `id` = ? ", [$getUser['id']]);
        // Rate limit
        checkBlockIP('OTP', 15);
        die(json_encode(['status' => 'error', 'msg' => __('OTP không chính xác')]));
    }
    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => '[Warning] ' . __('Xác thực OTP thành công - đã đăng nhập vào website')
    ]);
    $remember_token = generateRememberToken($getUser['remember_token'], $getUser['ip']);
    $CMSNT->update("users", [
        'remember_token'    => $remember_token,
        'token_otp_mail' => NULL,
        'limit_otp_mail' => 0,
        'otp_mail'  => NULL,
        'ip'        => myip(),
        'time_request' => time(),
        'time_session' => time(),
        'update_date'       => gettime(),
        'device'    => getUserAgent()
    ], " `id` = '" . $getUser['id'] . "' ");
    // THÔNG BÁO VỀ MAIL KHI LOGIN (Queue - non-blocking)
    if ($getUser['status_noti_login_to_mail'] == 1 && $CMSNT->site('email_temp_subject_warning_login') != '') {
        try {
            $mailer = new SMTPMailer($CMSNT);
            $mailer->queueWarningLoginEmail($getUser, [
                '{username}' => $getUser['username'],
                '{ip}' => myip(),
                '{device}' => getUserAgent(),
                '{time}' => gettime()
            ]);
        } catch (Exception $e) {
            error_log('[EmailQueue] Lỗi queue warning login email: ' . $e->getMessage());
        }
    }
    // Lưu phiên đăng nhập
    setSecureCookie('user_login', $getUser['token']);
    setSecureCookie('user_agent', getUserAgent());
    if ($getUser['admin'] > 0) {
        // Lưu phiên của Admin
        setSecureCookie('admin_login', $getUser['token']);
    }
    if ($getUser['ctv'] > 0) {
        // Lưu phiên của Ctv
        setSecureCookie('ctv_login', $getUser['token']);
    }
    die(json_encode(['status' => 'success', 'msg' => __('Đăng nhập thành công!')]));
}

// ĐĂNG KÝ TÀI KHOẢN
if ($_POST['action'] == 'Register') {
    // Kiểm tra CSRF Token để ngăn chặn tấn công CSRF
    // if (empty($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    //     die(json_encode([
    //         'status' => 'error',
    //         'msg'    => __('Invalid CSRF Protection Token')
    //     ]));
    // }
    if (empty($_POST['username'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập username')]));
    }
    $username = validate_alphanumeric($_POST['username'], 50);
    if ($username === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Username không hợp lệ')]));
    }

    if (empty($_POST['email'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập địa chỉ Email')]));
    }
    $email = validate_email($_POST['email']);
    if ($email === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Định dạng Email không hợp lệ')]));
    }

    if (empty($_POST['password'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập mật khẩu')]));
    }
    if (empty($_POST['repassword'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập lại mật khẩu')]));
    }
    $password_result = validate_password($_POST['password'], 6, 50);
    if (!$password_result['success']) {
        die(json_encode([
            'status' => 'error',
            'msg' => $password_result['message']
        ]));
    }
    $password = $password_result['password'];

    $repassword_result = validate_password($_POST['repassword'], 6, 50);
    if (!$repassword_result['success']) {
        die(json_encode([
            'status' => 'error',
            'msg' => __('Mật khẩu xác nhận không hợp lệ: ') . $repassword_result['message']
        ]));
    }
    $repassword = $repassword_result['password'];

    if ($password != $repassword) {
        die(json_encode(['status' => 'error', 'msg' => __('Xác minh mật khẩu không chính xác')]));
    }

    // Xác thực Captcha
    $captchaResponse = check_string($_POST['captcha_response'] ?? $_POST['recaptcha'] ?? $_POST['cf-turnstile-response'] ?? '');
    if ($captchaResponse === false) {
        $captchaResponse = '';
    }
    $captchaResult = verifyCaptchaResponse($captchaResponse, myip(), 'register');
    if (!$captchaResult['success']) {
        die(json_encode(['status' => 'error', 'msg' => $captchaResult['error_message']]));
    }

    // Sử dụng prepared statements để kiểm tra tồn tại
    if ($CMSNT->get_row_safe("SELECT * FROM `users` WHERE `username` = ?", [$username])) {
        die(json_encode(['status' => 'error', 'msg' => __('Tên đăng nhập đã tồn tại trong hệ thống')]));
    }
    if ($CMSNT->get_row_safe("SELECT * FROM `users` WHERE `email` = ?", [$email])) {
        die(json_encode(['status' => 'error', 'msg' => __('Địa chỉ email đã tồn tại trong hệ thống')]));
    }

    $user_ip = validate_ip(myip());
    if ($user_ip === false) {
        $user_ip = '127.0.0.1'; // Fallback IP
    }
    if ($CMSNT->num_rows_safe("SELECT * FROM `users` WHERE `ip` = ?", [$user_ip]) >= $CMSNT->site('max_register_ip')) {
        die(json_encode(['status' => 'error', 'msg' => __('IP của bạn đã đạt đến giới hạn tạo tài khoản cho phép')]));
    }


    $role = 0; // Mặc định user thường
    $log = __('Create an account');
    // Thực hiện truy vấn
    $query = $CMSNT->query("SELECT COUNT(id) as total FROM `users`");
    // Kiểm tra nếu truy vấn thất bại
    if ($query === false) {
        $role = 0; // Đảm bảo role là 0 nếu MySQL bị lỗi
    } else {
        // Lấy dữ liệu và ép kiểu
        $row = $query->fetch_assoc();
        $countUsers = isset($row['total']) ? (int)$row['total'] : 1;

        // Nếu bảng users rỗng, đặt role = 99999 (admin)
        if ($countUsers === 0) {
            $role = 99999;
            $log = 'Tài khoản đầu tiên khi đăng ký được set Admin cao nhất';
        }
    }





    $google2fa = new Google2FA();
    $remember_token = generateUltraSecureToken(64);
    $token = generateUltraSecureToken(64);
    $isCreate = $CMSNT->insert("users", [
        'admin'          => $role,
        'remember_token'    => $remember_token,
        'ref_id'        => isset($_COOKIE['aff']) && is_numeric($_COOKIE['aff']) ? intval($_COOKIE['aff']) : 0,
        'utm_source'    => isset($_COOKIE['utm_source']) ? check_string($_COOKIE['utm_source']) : 'web',
        'token'         => $token,
        'username'      => $username,
        'email'         => $email,
        'password'      => TypePassword($password),
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'create_date'   => gettime(),
        'update_date'   => gettime(),
        'time_session'  => time(),
        'api_key'       => md5($username . time()) . random('QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm0123456789', 32),
        'SecretKey_2fa' => $google2fa->generateSecretKey()
    ]);
    if ($isCreate) {
        $CMSNT->insert("logs", [
            'user_id'       => $isCreate,
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => $log
        ]);
        // Lưu phiên đăng nhập
        setSecureCookie('user_login', $token);
        setSecureCookie('user_agent', getUserAgent());

        die(json_encode(['status' => 'success', 'msg' => __('Đăng ký thành công!')]));
    } else {
        die(json_encode(['status' => 'error', 'msg' => __('Tạo tài khoản không thành công, vui lòng thử lại')]));
    }
}

// THAY ĐỔI HỒ SƠ
if ($_POST['action'] == 'ChangeProfile') {
    if ($CMSNT->site('status_demo') != 0) {
        die(json_encode(['status' => 'error', 'msg' => __('Chức năng này không thể sử dụng trên website demo')]));
    }
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập')]));
    }

    $token = validate_alphanumeric($_POST['token'], 255);
    if ($token === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Token không hợp lệ')]));
    }

    $getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ?", [$token]);
    if (!$getUser) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập')]));
    }
    // Validate input data
    $fullname = isset($_POST['fullname']) ? validate_string($_POST['fullname'], 100) : null;
    $telegram_chat_id = isset($_POST['telegram_chat_id']) ? validate_alphanumeric($_POST['telegram_chat_id'], 50) : null;
    $phone = isset($_POST['phone']) ? validate_phone($_POST['phone']) : null;

    $isUpdate = $CMSNT->update("users", [
        'fullname' => $fullname,
        'telegram_chat_id' => $telegram_chat_id,
        'phone' => $phone
    ], " `token` = ?", [$token]);
    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Change profile information')
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Lưu thành công')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Lưu thất bại')]));
}

// THAY ĐỔI MẬT KHẨU HỒ SƠ
if ($_POST['action'] == 'ChangePasswordProfile') {
    if ($CMSNT->site('status_demo') != 0) {
        die(json_encode(['status' => 'error', 'msg' => __('Chức năng này không thể sử dụng trên website demo')]));
    }
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập')]));
    }

    $token = validate_alphanumeric($_POST['token'], 255);
    if ($token === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Token không hợp lệ')]));
    }

    $getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ?", [$token]);
    if (!$getUser) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập')]));
    }
    if (empty($_POST['password'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập mật khẩu hiện tại')]));
    }
    if (empty($_POST['newpassword'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập mật khẩu mới')]));
    }
    $newpassword_result = validate_password($_POST['newpassword'], 6, 50);
    if (!$newpassword_result['success']) {
        die(json_encode([
            'status' => 'error',
            'msg' => __('Mật khẩu mới không hợp lệ: ') . $newpassword_result['message']
        ]));
    }

    if (empty($_POST['renewpassword'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập lại mật khẩu mới')]));
    }

    $renewpassword_result = validate_password($_POST['renewpassword'], 6, 50);
    if (!$renewpassword_result['success']) {
        die(json_encode([
            'status' => 'error',
            'msg' => __('Mật khẩu xác nhận không hợp lệ: ') . $renewpassword_result['message']
        ]));
    }

    if ($newpassword_result['password'] != $renewpassword_result['password']) {
        die(json_encode(['status' => 'error', 'msg' => __('Xác nhận mật khẩu không chính xác')]));
    }
    $password = check_string($_POST['password']);
    if ($CMSNT->site('type_password') == 'bcrypt') {
        if (!password_verify($password, $getUser['password'])) {
            die(json_encode(['status' => 'error', 'msg' => __('Mật khẩu hiện tại không đúng')]));
        }
    } else {
        if ($getUser['password'] != TypePassword($password)) {
            die(json_encode(['status' => 'error', 'msg' => __('Mật khẩu hiện tại không đúng')]));
        }
    }

    $new_token = generateUltraSecureToken(64);
    $isUpdate = $CMSNT->update("users", [
        'remember_token'    => bin2hex(random_bytes(64)),
        'password'  => TypePassword($_POST['newpassword']),
        'token'     => $new_token
    ], " `token` = ?", [$token]);
    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Change Password')
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Change password successfully!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Password change failed!')]));
}

// LƯU THÔNG TIN VAT
if ($_POST['action'] == 'SaveVatInfo') {
    if ($CMSNT->site('status_demo') != 0) {
        die(json_encode(['status' => 'error', 'msg' => __('Chức năng này không thể sử dụng trên website demo')]));
    }
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập')]));
    }

    $token = validate_alphanumeric($_POST['token'], 255);
    if ($token === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Token không hợp lệ')]));
    }

    $getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ?", [$token]);
    if (!$getUser) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập')]));
    }

    // Validate VAT type
    $vat_type = isset($_POST['vat_type']) ? validate_string($_POST['vat_type'], 20) : null;
    if ($vat_type === false || !in_array($vat_type, ['personal', 'business'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Loại VAT không hợp lệ')]));
    }

    // Validate VAT name
    $vat_name = isset($_POST['vat_name']) ? validate_string($_POST['vat_name'], 255, 1) : null;
    if ($vat_name === false || empty($vat_name)) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập tên/họ tên')]));
    }

    // Validate VAT address
    $vat_address = isset($_POST['vat_address']) ? validate_string($_POST['vat_address'], 500, 1) : null;
    if ($vat_address === false || empty($vat_address)) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập địa chỉ')]));
    }

    // Validate VAT tax code - bắt buộc cho business, không bắt buộc cho personal
    $vat_tax_code = isset($_POST['vat_tax_code']) ? validate_string($_POST['vat_tax_code'], 50) : '';
    if ($vat_type === 'business') {
        if ($vat_tax_code === false || empty($vat_tax_code)) {
            die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập mã số thuế')]));
        }
    } else {
        // Personal: không bắt buộc, có thể để trống
        if ($vat_tax_code === false) {
            $vat_tax_code = '';
        }
    }

    // Validate VAT CCCD - chỉ cho personal, không bắt buộc
    $vat_cccd = '';
    if ($vat_type === 'personal') {
        $vat_cccd = isset($_POST['vat_cccd']) ? validate_string($_POST['vat_cccd'], 20) : '';
        if ($vat_cccd === false) {
            $vat_cccd = '';
        }
    }

    // Tạo mảng dữ liệu VAT để lưu dưới dạng JSON
    $vat_data = [
        'vat_type' => $vat_type,
        'vat_name' => $vat_name,
        'vat_address' => $vat_address,
        'vat_tax_code' => $vat_tax_code,
        'updated_at' => date('Y-m-d H:i:s')
    ];

    // Thêm CCCD nếu là personal và có giá trị
    if ($vat_type === 'personal' && !empty($vat_cccd)) {
        $vat_data['vat_cccd'] = $vat_cccd;
    }

    // Chuyển đổi sang JSON
    $vat_info_json = json_encode($vat_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // Lưu thông tin VAT dưới dạng JSON vào cột vat_info
    $isUpdate = $CMSNT->update("users", [
        'vat_info' => $vat_info_json
    ], " `token` = ?", [$token]);

    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Lưu thông tin VAT: ' . $vat_type)
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Lưu thông tin VAT thành công')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Lưu thông tin VAT thất bại')]));
}

// LƯU HỌ VÀ TÊN CHUYỂN KHOẢN (PREFIX FULLNAME)
if ($_POST['action'] == 'SavePrefixFullname') {
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
    // Nếu đã có prefix_fullname rồi thì không cho đổi
    if (!empty($getUser['prefix_fullname'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn đã cập nhật họ và tên rồi, không thể thay đổi.')]));
    }

    $fullname = isset($_POST['fullname']) ? check_string($_POST['fullname']) : '';
    if (empty($fullname)) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập họ và tên')]));
    }

    // Bỏ dấu tiếng Việt (server-side)
    $fullname = removeVietnameseAccents($fullname);
    // Ghi hoa toàn bộ
    $fullname = mb_strtoupper($fullname, 'UTF-8');
    // Loại bỏ khoảng trắng thừa
    $fullname = preg_replace('/\s+/', ' ', trim($fullname));

    // Kiểm tra định dạng: chỉ chấp nhận chữ cái A-Z và khoảng trắng, ít nhất 2 từ, tối đa 5 từ
    if (!preg_match('/^[A-Z]+(\s[A-Z]+){1,4}$/', $fullname)) {
        die(json_encode(['status' => 'error', 'msg' => __('Họ và tên không hợp lệ. Vui lòng nhập đúng định dạng (VD: NGUYEN VAN A), ít nhất 2 từ.')]));
    }

    // Kiểm tra trùng, nếu trùng thì thêm số vào cuối
    $originalFullname = $fullname;
    $counter = 0;
    while ($CMSNT->get_row_safe("SELECT `id` FROM `users` WHERE `prefix_fullname` = ? AND `id` != ?", [$fullname, $getUser['id']])) {
        $counter++;
        $fullname = $originalFullname . $counter;
    }

    // Lưu vào cột prefix_fullname
    $CMSNT->update("users", [
        'prefix_fullname' => $fullname
    ], " `id` = ?", [$getUser['id']]);

    die(json_encode([
        'status' => 'success',
        'msg' => __('Cập nhật họ và tên thành công!'),
        'prefix_fullname' => $fullname
    ]));
}

// QUÊN MẬT KHẨU
if ($_POST['action'] == 'ForgotPassword') {
    // Kiểm tra CSRF Token để ngăn chặn tấn công CSRF
    // if (empty($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    //     die(json_encode([
    //         'status' => 'error',
    //         'msg'    => __('Invalid CSRF Protection Token')
    //     ]));
    // }
    if ($CMSNT->site('status_demo') != 0) {
        die(json_encode(['status' => 'error', 'msg' => __('Chức năng này không thể sử dụng trên website demo')]));
    }
    if ($CMSNT->site('status_demo') != 0) {
        die(json_encode(['status' => 'error', 'msg' => __('Chức năng này không thể sử dụng trên website demo')]));
    }
    if (empty($CMSNT->site('smtp_password'))) {
        die(json_encode(['status' => 'error', 'msg' => __('Website chưa được cấu hình SMTP, vui lòng liên hệ Admin')]));
    }
    if (empty($_POST['email'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập địa chỉ Email')]));
    }

    $email = validate_email($_POST['email']);
    if ($email === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Định dạng Email không hợp lệ')]));
    }

    // Xác thực Captcha
    $captchaResponse = check_string($_POST['captcha_response'] ?? $_POST['recaptcha'] ?? $_POST['cf-turnstile-response'] ?? '');
    if ($captchaResponse === false) {
        $captchaResponse = '';
    }
    $captchaResult = verifyCaptchaResponse($captchaResponse, myip(), 'forgot_password');
    if (!$captchaResult['success']) {
        die(json_encode(['status' => 'error', 'msg' => $captchaResult['error_message']]));
    }

    $getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `email` = ?", [$email]);
    if (!$getUser) {
        checkBlockIP('RESET_PASSWORD', 15);
        die(json_encode(['status' => 'error', 'msg' => __('Địa chỉ Email này không tồn tại trong hệ thống')]));
    }
    if (time() - $getUser['time_forgot_password'] < 60) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng thử lại trong ít phút')]));
    }
    $token = md5(random('QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm0123456789', 6) . time()) . md5(random('QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm0123456789', 55));

    if ($CMSNT->site('email_temp_subject_forgot_password') != '') {
        $content = $CMSNT->site('email_temp_content_forgot_password');
        $content = str_replace('{domain}', $_SERVER['SERVER_NAME'], $content);
        $content = str_replace('{title}', $CMSNT->site('title'), $content);
        $content = str_replace('{username}', $getUser['username'], $content);
        $content = str_replace('{link}', '<a target="_blank" href="' . base_url('?action=reset-password&token=' . $token) . '">' . base_url('?action=reset-password&token=' . $token) . '</a>', $content);
        $content = str_replace('{ip}', myip(), $content);
        $content = str_replace('{device}', getUserAgent(), $content);
        $content = str_replace('{time}', gettime(), $content);
        ////////////////////////////////////////////////////////////////////
        $subject = $CMSNT->site('email_temp_subject_forgot_password');
        $subject = str_replace('{domain}', $_SERVER['SERVER_NAME'], $subject);
        $subject = str_replace('{title}', $CMSNT->site('title'), $subject);
        $subject = str_replace('{username}', $getUser['username'], $subject);
        $subject = str_replace('{link}', '<a target="_blank" href="' . base_url('?action=reset-password&token=' . $token) . '">' . base_url('?action=reset-password&token=' . $token) . '</a>', $subject);
        $subject = str_replace('{ip}', myip(), $subject);
        $subject = str_replace('{device}', getUserAgent(), $subject);
        $subject = str_replace('{time}', gettime(), $subject);
        $bcc = $CMSNT->site('title');
        sendCSM($getUser['email'], $getUser['username'], $subject, $content, $bcc);
    }

    $isUpdate = $CMSNT->update('users', [
        'token_forgot_password' => $token,
        'time_forgot_password'  => time()
    ], " `id` = '" . $getUser['id'] . "' ");
    if ($isUpdate) {
        die(json_encode(['status' => 'success', 'msg' => __('Vui lòng kiểm tra Email của bạn để hoàn tất quá trình đặt lại mật khẩu')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Có lỗi hệ thống, vui lòng liên hệ Developer')]));
}

// ĐỔI MẬT KHẨU KHÔI PHỤC
if ($_POST['action'] == 'ChangePassword') {
    if ($CMSNT->site('status_demo') != 0) {
        die(json_encode(['status' => 'error', 'msg' => __('Chức năng này không thể sử dụng trên website demo')]));
    }
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Liên kết không hợp lệ')]));
    }

    $token = validate_alphanumeric($_POST['token'], 255);
    if ($token === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Token không hợp lệ')]));
    }

    $getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token_forgot_password` = ? AND `token_forgot_password` IS NOT NULL", [$token]);
    if (!$getUser) {
        checkBlockIP('RESET_PASSWORD', 15);
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập')]));
    }
    if (empty($getUser['token_forgot_password'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Liên kết không tồn tại')]));
    }
    if (empty($_POST['newpassword'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập mật khẩu mới')]));
    }
    $newpassword_result = validate_password($_POST['newpassword'], 6, 50);
    if (!$newpassword_result['success']) {
        die(json_encode([
            'status' => 'error',
            'msg' => __('Mật khẩu mới không hợp lệ: ') . $newpassword_result['message']
        ]));
    }

    if (empty($_POST['renewpassword'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập lại mật khẩu mới')]));
    }

    $renewpassword_result = validate_password($_POST['renewpassword'], 6, 50);
    if (!$renewpassword_result['success']) {
        die(json_encode([
            'status' => 'error',
            'msg' => __('Mật khẩu xác nhận không hợp lệ: ') . $renewpassword_result['message']
        ]));
    }

    if ($newpassword_result['password'] != $renewpassword_result['password']) {
        die(json_encode(['status' => 'error', 'msg' => __('Xác nhận mật khẩu không chính xác')]));
    }
    $password = $newpassword_result['password'];
    $token = generateUltraSecureToken(64);
    $isUpdate = $CMSNT->update("users", [
        'remember_token'    => bin2hex(random_bytes(64)),
        'token_forgot_password' => NULL,
        'password'  => TypePassword($password),
        'token'     => $token
    ], " `id` = ?", [$getUser['id']]);
    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Khôi phục lại mật khẩu')
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Khôi phục lại mật khẩu'), $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        die(json_encode(['status' => 'success', 'msg' => __('Thay đổi mật khẩu thành công')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Thay đổi mật khẩu thất bại')]));
}

if ($_POST['action'] == 'changeAPIKey') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }

    $token = validate_alphanumeric($_POST['token'], 255);
    if ($token === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Token không hợp lệ')]));
    }

    $getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0", [$token]);
    if (!$getUser) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }

    $api_key = md5($getUser['username'] . time()) . random('QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm0123456789', 32);
    $isUpdate = $CMSNT->update('users', [
        'api_key'       => $api_key
    ], " `id` = ?", [$getUser['id']]);
    if ($isUpdate) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Thay đổi API KEY')
        ]);
        $data = json_encode([
            'api_key'   => $api_key,
            'status'    => 'success',
            'msg'       => __('Thay đổi API KEY thành công!')
        ]);
        die($data);
    }
}

if ($_POST['action'] == 'changeSecurity') {
    if ($CMSNT->site('status_demo') != 0) {
        die(json_encode(['status' => 'error', 'msg' => __('Chức năng này không thể sử dụng trên website demo')]));
    }
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }

    $token = validate_alphanumeric($_POST['token'], 255);
    if ($token === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Token không hợp lệ')]));
    }

    $getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0", [$token]);
    if (!$getUser) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if ($CMSNT->site('smtp_status') != 1) {
        die(json_encode(['status' => 'error', 'msg' => __('SMTP chưa được cấu hình, vui lòng liên hệ Admin')]));
    }
    // Validate boolean values
    $status_noti_login_to_mail = validate_boolean($_POST['status_noti_login_to_mail'] ?? 0);
    $status_otp_mail = validate_boolean($_POST['status_otp_mail'] ?? 0);
    $status_view_order = validate_boolean($_POST['status_view_order'] ?? 0);

    $isUpdate = $CMSNT->update('users', [
        'status_noti_login_to_mail'         => $status_noti_login_to_mail ? 1 : 0,
        'status_otp_mail'                   => $status_otp_mail ? 1 : 0,
        'status_view_order'                 => $status_view_order ? 1 : 0
    ], " `id` = ?", [$getUser['id']]);
    if ($isUpdate) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Cấu hình bảo mật')
        ]);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => __('Lưu thay đổi thành công!')
        ]);
        die($data);
    }
}

if ($_POST['action'] == 'Save2FA') {
    if ($CMSNT->site('status_demo') != 0) {
        die(json_encode(['status' => 'error', 'msg' => __('Chức năng này không thể sử dụng trên website demo')]));
    }
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }

    $token = validate_alphanumeric($_POST['token'], 255);
    if ($token === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Token không hợp lệ')]));
    }

    $getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0", [$token]);
    if (!$getUser) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }

    if (empty($_POST['secret'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập mã xác minh 2FA')]));
    }

    $secret = validate_alphanumeric($_POST['secret'], 32);
    if ($secret === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Mã xác minh không hợp lệ')]));
    }

    $google2fa = new Google2FA();
    if ($google2fa->verifyKey($getUser['SecretKey_2fa'], $secret) != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Mã xác minh không chính xác')]));
    }

    $status_2fa = validate_boolean($_POST['status_2fa'] ?? 0) ? 1 : 0;
    if ($status_2fa == 1) {
        $action = __('Bật xác thực Google Authenticator');
        $SecretKey_2fa = $getUser['SecretKey_2fa'];
    } else {
        $action = __('Tắt xác thực Google Authenticator');
        $SecretKey_2fa = $google2fa->generateSecretKey();
    }

    $isUpdate = $CMSNT->update('users', [
        'status_2fa'    => $status_2fa,
        'SecretKey_2fa' => $SecretKey_2fa
    ], " `id` = ?", [$getUser['id']]);
    if ($isUpdate) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => $action
        ]);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => $action . ' ' . __('thành công')
        ]);
        die($data);
    }
}
