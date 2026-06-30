<?php
define("IN_SITE", true);
require_once(__DIR__ . '/libs/db.php');
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/libs/lang.php');
require_once(__DIR__ . '/libs/helper.php');
require_once(__DIR__ . '/libs/database/users.php');
$CMSNT = new DB();



// Định nghĩa hằng số cho thư mục views
define('VIEWS_PATH', __DIR__ . '/views');

// Kiểm tra module và action hợp lệ
$module = !empty($_GET['module']) ? validate_path($_GET['module']) : 'client';
if ($module === false) {
    $module = 'client';
}
$home   = $module == 'client' ? $CMSNT->site('home_page') : 'home';
$action = !empty($_GET['action']) ? validate_path($_GET['action']) : $home;
if ($action === false) {
    $action = $home;
}

// Các Action được phép
$allowed_actions = [
    // Client
    'aff',
    'affiliate-history',
    'affiliate-withdraw',
    'affiliates',
    'blog',
    'blogs',
    'change-password',
    'contact',
    'document-api',
    'faq',
    'favorites',
    'forgot-password',
    'home',
    'login',
    'logout',
    'logs',
    'policy',
    'product',
    'product-order',
    'product-orders',
    'profile',
    'recharge-bakong',
    'recharge-bank',
    'recharge-card',
    'recharge-crypto',
    'vat-invoice',
    'recharge-flutterwave',
    'recharge-korapay',
    'recharge-pocketfi',
    'recharge-paymentpoint',
    'recharge-dsociopay',
    'recharge-manual',
    'recharge-momo',
    'recharge-openpix',
    'recharge-paypal',
    'recharge-perfectmoney',
    'recharge-squadco',
    'recharge-thesieure',
    'recharge-tmweasyapi',
    'recharge-toyyibpay',
    'recharge-xipay',
    'recharge-lempay',
    'recharge-zinipay',
    'recharge-tripay',
    'register',
    'reset-password',
    'security',
    'set-language',
    'tool-2fa',
    'tool-checklive-fb',
    'tool-icon-facebook',
    'tool-random-face',
    'transactions',
    'verify_2fa',
    'verify_otp',
    'widget_tools',

    // Admin
    'admin-logs',
    'affiliate-config',
    'affiliate-history',
    'affiliate-links',
    'affiliate-withdraw',
    'automation-edit',
    'automations',
    'block-ip',
    'blog-add',
    'blog-category',
    'blog-category-edit',
    'blog-edit',
    'blogs',
    'categories',
    'category-add',
    'category-edit',
    'coupon-edit',
    'coupons',
    'ctv-config',
    'ctv-pending-products',
    'ctv-statistics',
    'ctv-withdraw',
    'currency-edit',
    'currency-list',
    'email-campaign-add',
    'email-campaign-edit',
    'email-campaigns',
    'email-queue',
    'email-sending-view',
    'failed-attempts-logs',
    'home',
    'language-edit',
    'language-list',
    'log-auto-bank',
    'login-user',
    'logs',
    'menu-edit',
    'menu-list',
    'product-add',
    'product-api',
    'product-api-add',
    'product-api-edit',
    'product-api-manager',
    'product-edit',
    'product-orders',
    'product-sold',
    'product-stock',
    'product-warehouse',
    'products',
    'promotions',
    'recharge-bakong',
    'recharge-bank',
    'recharge-bank-config',
    'recharge-bank-edit',
    'recharge-card',
    'recharge-crypto',
    'recharge-flutterwave',
    'recharge-korapay',
    'recharge-pocketfi',
    'recharge-paymentpoint',
    'recharge-dsociopay',
    'recharge-manual',
    'recharge-manual-edit',
    'recharge-momo',
    'recharge-openpix',
    'recharge-paypal',
    'recharge-perfectmoney',
    'recharge-squadco',
    'recharge-thesieure',
    'recharge-tmweasyapi',
    'recharge-toyyibpay',
    'recharge-xipay',
    'recharge-lempay',
    'recharge-zinipay',
    'recharge-tripay',
    'role-edit',
    'roles',
    'settings',
    'telegram-logs',
    'telegram-queue',
    'theme',
    'transactions',
    'translate-list',
    'user-edit',
    'users',

    // CTV
    'ctv-withdraw',
    'home',
    'product-add',
    'product-edit',
    'product-orders',
    'product-stock',
    'products',
    'settings',

    // Common
    '404',
    'banned',
    'block-ip',
    'maintenance'
];

$allowed_actions[] = $home;

// Nếu action không nằm trong danh sách cho phép thì trả về 404
if (!in_array($action, $allowed_actions, true)) {
    require_once(VIEWS_PATH . '/common/404.php');
    exit();
}


// Kiểm tra quyền truy cập cho module admin
if ($module == 'admin' && !isSecureCookie('admin_login')) {
    redirect(base_url('client/login'));
}

if ($module == 'admin') {
    require_once __DIR__ . '/models/is_admin.php';
}

// Kiểm tra quyền truy cập cho module CTV
if ($module == 'ctv' && !isSecureCookie('ctv_login')) {
    redirect(base_url('client/login'));
}

if ($module == 'ctv') {
    require_once __DIR__ . '/models/is_ctv.php';
}

if (isset($_GET['utm_source'])) {
    $utm_source = validate_string($_GET['utm_source'], 100);
    if ($utm_source !== false) {
        setcookie('utm_source', $utm_source, time() + (86400 * 30), "/"); // Cookie sẽ tồn tại trong 30 ngày
    }
}
if (isset($_GET['aff'])) {
    $aff = validate_int($_GET['aff'], 1);
    if ($aff !== false) {
        setcookie('aff', $aff, time() + (86400 * 30), "/"); // Cookie sẽ tồn tại trong 30 ngày
        if ($user_ref = $CMSNT->get_row_safe("SELECT id FROM `users` WHERE `id` = ?", [$aff])) {
            // CỘNG LƯỢT CLICK (Prepared)
            $CMSNT->cong('users', 'ref_click', 1, " `id` = ? ", [$user_ref['id']]);
        }
        // Redirect về URL sạch (không có tham số aff) để trông chuyên nghiệp hơn
        $clean_url = strtok($_SERVER['REQUEST_URI'], '?');
        // Giữ lại các tham số khác (nếu có) ngoại trừ 'aff'
        $params = $_GET;
        unset($params['aff']);
        if (!empty($params)) {
            $clean_url .= '?' . http_build_query($params);
        }
        header('Location: ' . $clean_url);
        exit();
    }
}




// Xây dựng đường dẫn an toàn
$path = VIEWS_PATH . '/' . $module . '/' . $action . '.php';

// Kiểm tra file tồn tại và nằm trong thư mục views
if (file_exists($path) && strpos(realpath($path), realpath(VIEWS_PATH)) === 0) {
    require_once($path);
    exit();
} else {
    require_once(VIEWS_PATH . '/common/404.php');
    exit();
}
