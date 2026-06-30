<?php

/**
 * Google OAuth Callback Handler cho SHOPCLONE7
 * 
 * File này xử lý callback từ Google sau khi user đăng nhập/đăng ký bằng Google.
 * Luồng: User click nút Google → Google xác thực → Redirect về URL này với ?code=xxx&state=xxx
 * 
 * Adapt từ SHOPKEY nhưng sử dụng setSecureCookie() thay createSession()
 * và generateUltraSecureToken() thay generateUserToken()
 */

define("IN_SITE", true);
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../libs/db.php');
require_once(__DIR__ . '/../libs/lang.php');
require_once(__DIR__ . '/../libs/helper.php');
require_once(__DIR__ . '/../libs/database/users.php');

$CMSNT = new DB();

// Kiểm tra thư viện Google API Client có được cài hay không
// Nếu chưa cài → redirect về trang chủ (không crash site)
if (!class_exists('Google_Client')) {
    redirect(base_url());
}

// Kiểm tra tính năng Google Login có được bật không
if ($CMSNT->site('status_google_login') != 1) {
    redirect(base_url());
}

// Lấy cấu hình Client ID/Secret từ admin settings
$clientId = $CMSNT->site('google_login_client_id');
$clientSecret = $CMSNT->site('google_login_client_secret');

// Nếu chưa cấu hình → redirect về (admin chưa nhập Client ID/Secret)
if (empty($clientId) || empty($clientSecret)) {
    redirect(base_url());
}

// ==========================================
// BƯỚC 1: Khởi tạo Google Client
// ==========================================
$client = new Google_Client();
$client->setClientId($clientId);
$client->setClientSecret($clientSecret);
$client->setRedirectUri(base_url('api/callback_google_login.php'));
$client->addScope("email");
$client->addScope("profile");

// ==========================================
// BƯỚC 2: Kiểm tra state chống CSRF
// Đảm bảo request callback đến từ luồng hợp lệ, không bị giả mạo
// ==========================================
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// State phải khớp với giá trị đã lưu trong session khi tạo auth URL
if (
    empty($_GET['state'])
    || empty($_SESSION['google_oauth_state'])
    || !hash_equals($_SESSION['google_oauth_state'], $_GET['state'])
) {
    // State không khớp → có thể bị tấn công CSRF
    unset($_SESSION['google_oauth_state'], $_SESSION['google_oauth_state_expires'], $_SESSION['google_oauth_intent']);
    redirect(base_url('client/login'));
}

// Kiểm tra state đã hết hạn chưa (5 phút)
if (
    !empty($_SESSION['google_oauth_state_expires'])
    && time() > $_SESSION['google_oauth_state_expires']
) {
    // State hết hạn → user mất quá lâu để hoàn tất xác thực Google
    unset($_SESSION['google_oauth_state'], $_SESSION['google_oauth_state_expires'], $_SESSION['google_oauth_intent']);
    redirect(base_url('client/login'));
}

// Lấy intent (login hoặc register) để biết user đến từ trang nào
$intent = $_SESSION['google_oauth_intent'] ?? 'login';

// Xoá state khỏi session sau khi đã kiểm tra (one-time use)
unset($_SESSION['google_oauth_state'], $_SESSION['google_oauth_state_expires'], $_SESSION['google_oauth_intent']);

// ==========================================
// BƯỚC 3: Xử lý khi user huỷ bỏ
// ==========================================
if (isset($_GET['error'])) {
    // User click "Cancel" trên Google → quay về trang login/register
    $fallbackPage = ($intent === 'register') ? 'client/register' : 'client/login';
    redirect(base_url($fallbackPage));
}

// ==========================================
// BƯỚC 4: Lấy access token từ authorization code
// ==========================================
if (empty($_GET['code'])) {
    redirect(base_url('client/login'));
}

try {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
} catch (Exception $e) {
    // Lỗi khi trao đổi code → quay về login
    redirect(base_url('client/login'));
}

// Kiểm tra token có lỗi không
if (isset($token['error'])) {
    redirect(base_url('client/login'));
}

$client->setAccessToken($token);

// ==========================================
// BƯỚC 5: Xác minh id_token
// Leeway 30 giây để bù trừ chênh lệch đồng hồ server vs Google
// ==========================================
if (class_exists('Firebase\JWT\JWT')) {
    \Firebase\JWT\JWT::$leeway = 30;
}

try {
    $idToken = $client->verifyIdToken();
} catch (Exception $e) {
    // Token verification thất bại → có thể do clock skew hoặc token giả
    redirect(base_url('client/login'));
}

if (!$idToken) {
    redirect(base_url('client/login'));
}

// ==========================================
// BƯỚC 6: Trích xuất thông tin user từ Google
// ==========================================
$googleId = $idToken['sub'] ?? '';           // Google User ID (unique, không đổi)
$googleEmail = $idToken['email'] ?? '';       // Email từ Google
$googleName = $idToken['name'] ?? '';         // Tên hiển thị từ Google

// Sanitize tên hiển thị để tránh XSS khi lưu vào DB và hiển thị ra giao diện
$googleName = htmlspecialchars(strip_tags($googleName), ENT_QUOTES, 'UTF-8');

// Validate dữ liệu từ Google
if (empty($googleId) || empty($googleEmail)) {
    redirect(base_url('client/login'));
}

// Validate email hợp lệ
$googleEmail = validate_email($googleEmail);
if ($googleEmail === false) {
    redirect(base_url('client/login'));
}


// ==========================================
// BƯỚC 7: Tìm user trong database
// Ưu tiên tìm theo google_id (nhanh hơn, chính xác hơn),
// sau đó fallback tìm theo email
// ==========================================


// Tìm user đã liên kết Google ID
$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `google_id` = ?", [$googleId]);

if (!$getUser) {
    // Chưa liên kết google_id → thử tìm theo email
    $getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `email` = ?", [$googleEmail]);
}

// ==========================================
// BƯỚC 8: Xử lý đăng nhập hoặc đăng ký
// ==========================================

if ($getUser) {
    // ==========================================
    // TRƯỜNG HỢP A: User đã tồn tại → Đăng nhập
    // ==========================================

    // Kiểm tra tài khoản có bị banned không
    if ($getUser['banned'] == 1) {
        redirect(base_url('client/login'));
    }

    // Liên kết Google ID nếu chưa có (user đăng ký bằng form thường, giờ login bằng Google lần đầu)
    if (empty($getUser['google_id'])) {
        // P1 Fix: Dùng prepared statement cho WHERE clause để chống SQL Injection
        $CMSNT->update('users', [
            'google_id' => $googleId,
            'google_linked_at' => gettime()
        ], " `id` = ? ", [$getUser['id']]);
    }

    // Kiểm tra OTP Mail hoặc 2FA
    // Nếu user bật OTP Mail → redirect đến trang verify OTP
    if ($getUser['status_otp_mail'] == 1) {
        $otp_mail = random('QWERTYUOPASDFGHJKZXCVBNM0126456789', 6);
        // P2 Fix: Dùng CSPRNG thay vì md5(uniqid()) để sinh token OTP an toàn hơn
        $token_otp_mail = generateUltraSecureToken(32);
        // P1 Fix: Dùng prepared statement cho WHERE clause
        $CMSNT->update('users', [
            'token_otp_mail' => $token_otp_mail,
            'otp_mail'       => $otp_mail,
            'limit_otp_mail' => 0
        ], " `id` = ? ", [$getUser['id']]);

        // Gửi email OTP nếu có cấu hình template
        if ($CMSNT->site('email_temp_subject_otp_mail') != '') {
            require_once(__DIR__ . '/../libs/sendEmail.php');
            require_once(__DIR__ . '/../libs/SMTPMailer.php');
            $content = $CMSNT->site('email_temp_content_otp_mail');
            $content = str_replace('{domain}', $_SERVER['SERVER_NAME'], $content);
            $content = str_replace('{title}', $CMSNT->site('title'), $content);
            $content = str_replace('{username}', $getUser['username'], $content);
            $content = str_replace('{otp}', $otp_mail, $content);
            $content = str_replace('{ip}', myip(), $content);
            $content = str_replace('{device}', getUserAgent(), $content);
            $content = str_replace('{time}', gettime(), $content);
            $subject = $CMSNT->site('email_temp_subject_otp_mail');
            $subject = str_replace('{domain}', $_SERVER['SERVER_NAME'], $subject);
            $subject = str_replace('{title}', $CMSNT->site('title'), $subject);
            $subject = str_replace('{username}', $getUser['username'], $subject);
            $subject = str_replace('{otp}', $otp_mail, $subject);
            $bcc = $CMSNT->site('title');
            sendCSM($getUser['email'], $getUser['username'], $subject, $content, $bcc);
        }

        $CMSNT->insert("logs", [
            'user_id'    => $getUser['id'],
            'ip'         => myip(),
            'device'     => getUserAgent(),
            'createdate' => gettime(),
            'action'     => '[Google Login] ' . __('Đăng nhập thành công - đang tiến hành đến bước xác minh OTP Mail')
        ]);

        redirect(base_url('?action=verify_otp&token=' . $token_otp_mail));
    }

    // Nếu user bật 2FA → redirect đến trang verify 2FA
    if ($getUser['status_2fa'] == 1) {
        // P2 Fix: Dùng CSPRNG thay vì md5(random()) + md5(uniqid()) để sinh token 2FA an toàn hơn
        $token_2fa = generateUltraSecureToken(32);
        // P1 Fix: Dùng prepared statement cho WHERE clause
        $CMSNT->update('users', [
            'token_2fa' => $token_2fa,
            'limit_2fa' => 0
        ], " `id` = ? ", [$getUser['id']]);

        $CMSNT->insert("logs", [
            'user_id'    => $getUser['id'],
            'ip'         => myip(),
            'device'     => getUserAgent(),
            'createdate' => gettime(),
            'action'     => '[Google Login] ' . __('Đăng nhập thành công - đang tiến hành đến bước xác minh 2FA')
        ]);

        redirect(base_url('?action=verify_2fa&token=' . $token_2fa));
    }

    // Không có OTP/2FA → đăng nhập trực tiếp
    $remember_token = generateRememberToken($getUser['remember_token'], $getUser['ip']);

    // P1 Fix: Dùng prepared statement cho WHERE clause
    $CMSNT->update("users", [
        'remember_token' => $remember_token,
        'token_2fa'      => NULL,
        'limit_2fa'      => 0,
        'token_otp_mail' => NULL,
        'limit_otp_mail' => 0,
        'otp_mail'       => NULL,
        'ip'             => myip(),
        'time_request'   => time(),
        'time_session'   => time(),
        'update_date'    => gettime(),
        'device'         => getUserAgent()
    ], " `id` = ? ", [$getUser['id']]);

    // Ghi log đăng nhập
    $CMSNT->insert("logs", [
        'user_id'    => $getUser['id'],
        'ip'         => myip(),
        'device'     => getUserAgent(),
        'createdate' => gettime(),
        'action'     => '[Google Login] ' . __('Đăng nhập bằng Google thành công')
    ]);

    // Set cookie phiên đăng nhập (cơ chế session của SHOPCLONE7)
    setSecureCookie('user_login', $getUser['token']);
    setSecureCookie('user_agent', getUserAgent());

    // Nếu user có quyền admin → set cookie admin
    if ($getUser['admin'] > 0) {
        setSecureCookie('admin_login', $getUser['token']);
    }
    // Nếu user là CTV → set cookie CTV
    if ($getUser['ctv'] > 0) {
        setSecureCookie('ctv_login', $getUser['token']);
    }

    redirect(base_url());
} else {
    // ==========================================
    // TRƯỜNG HỢP B: User mới → Đăng ký tài khoản
    // ==========================================


    // Kiểm tra giới hạn đăng ký theo IP
    $user_ip = validate_ip(myip());
    if ($user_ip === false) {
        $user_ip = '127.0.0.1';
    }
    if ($CMSNT->num_rows_safe("SELECT * FROM `users` WHERE `ip` = ?", [$user_ip]) >= $CMSNT->site('max_register_ip')) {
        redirect(base_url('client/login'));
    }

    // Tạo username từ email (phần trước @, loại bỏ ký tự đặc biệt)
    $baseUsername = preg_replace('/[^a-zA-Z0-9]/', '', explode('@', $googleEmail)[0]);
    // Giới hạn 30 ký tự để còn chỗ thêm số random
    $baseUsername = substr($baseUsername, 0, 30);
    if (empty($baseUsername)) {
        $baseUsername = 'user';
    }

    // Đảm bảo username unique bằng cách thêm số random nếu trùng
    $username = $baseUsername;
    $maxAttempts = 10; // Giới hạn số lần thử để tránh vòng lặp vô hạn
    $attempt = 0;
    while ($CMSNT->get_row_safe("SELECT `id` FROM `users` WHERE `username` = ?", [$username]) && $attempt < $maxAttempts) {
        $username = $baseUsername . random('0123456789', 4);
        $attempt++;
    }

    // Nếu sau 10 lần vẫn trùng → dùng timestamp để đảm bảo unique
    if ($attempt >= $maxAttempts) {
        $username = $baseUsername . time();
    }

    // Tạo mật khẩu ngẫu nhiên an toàn (user đăng ký qua Google không cần nhớ mật khẩu)
    $randomPassword = generateUltraSecureToken(16);
    $token = generateUltraSecureToken(64);
    $remember_token = generateUltraSecureToken(64);

    // Xác định role: nếu là user đầu tiên trong hệ thống → admin cao nhất
    $role = 0;
    $log = '[Google Login] ' . __('Đăng ký tài khoản mới bằng Google');
    $query = $CMSNT->query("SELECT COUNT(id) as total FROM `users`");
    if ($query !== false) {
        $row = $query->fetch_assoc();
        $countUsers = isset($row['total']) ? (int)$row['total'] : 1;
        if ($countUsers === 0) {
            $role = 99999;
            $log = '[Google Login] Tài khoản đầu tiên - được set Admin cao nhất';
        }
    }

    // Tạo SecretKey 2FA
    require_once(__DIR__ . '/../libs/Google2FA.php');
    $google2fa = new \PragmaRX\Google2FAQRCode\Google2FA();

    // Insert user mới vào database
    $isCreate = $CMSNT->insert("users", [
        'admin'          => $role,
        'remember_token' => $remember_token,
        'ref_id'         => isset($_COOKIE['aff']) && is_numeric($_COOKIE['aff']) ? intval($_COOKIE['aff']) : 0,
        'utm_source'     => isset($_COOKIE['utm_source']) ? check_string($_COOKIE['utm_source']) : 'google',
        'token'          => $token,
        'username'       => $username,
        'email'          => $googleEmail,
        'google_id'      => $googleId,
        'google_linked_at' => gettime(),
        'password'       => TypePassword($randomPassword),
        'ip'             => myip(),
        'device'         => getUserAgent(),
        'create_date'    => gettime(),
        'update_date'    => gettime(),
        'time_session'   => time(),
        'fullname'       => $googleName,
        'api_key'        => generateUltraSecureToken(48),
        'SecretKey_2fa'  => $google2fa->generateSecretKey()
    ]);

    if (!$isCreate) {
        // Insert thất bại (có thể do trùng google_id unique) → quay về login
        redirect(base_url('client/login'));
    }

    // Ghi log đăng ký
    $CMSNT->insert("logs", [
        'user_id'    => $isCreate,
        'ip'         => myip(),
        'device'     => getUserAgent(),
        'createdate' => gettime(),
        'action'     => $log
    ]);

    // Cộng ref click cho người giới thiệu (nếu có cookie aff)
    if (isset($_COOKIE['aff']) && is_numeric($_COOKIE['aff'])) {
        $refId = intval($_COOKIE['aff']);
        if ($CMSNT->get_row_safe("SELECT `id` FROM `users` WHERE `id` = ?", [$refId])) {
            $CMSNT->cong('users', 'ref_click', 1, " `id` = ? ", [$refId]);
        }
    }

    // Set cookie để đăng nhập luôn sau khi đăng ký
    setSecureCookie('user_login', $token);
    setSecureCookie('user_agent', getUserAgent());

    // Nếu role admin → set cookie admin
    if ($role > 0) {
        setSecureCookie('admin_login', $token);
    }

    // Thông báo Telegram cho admin (nếu bật)
    $my_text = $CMSNT->site('noti_action');
    if (!empty($my_text)) {
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $username, $my_text);
        $my_text = str_replace('{action}', __('Đăng ký tài khoản mới bằng Google') . ' (' . $googleEmail . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
    }

    redirect(base_url());
}
