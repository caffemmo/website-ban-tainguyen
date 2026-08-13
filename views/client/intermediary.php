<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

require_once __DIR__ . '/../../libs/client-session.php';

$intermediaryUser = client_optional_user($CMSNT);
$intermediaryAdmin = $intermediaryUser && (int) ($intermediaryUser['admin'] ?? 0) > 0
    ? $intermediaryUser
    : null;

if ($intermediaryAdmin === null && isSecureCookie('admin_login') === true && !empty($_COOKIE['admin_login'])) {
    $adminToken = validate_alphanumeric($_COOKIE['admin_login'], 255);
    if ($adminToken !== false) {
        $intermediaryAdmin = $CMSNT->get_row_safe(
            'SELECT * FROM `users` WHERE `token` = ? AND `admin` != 0 AND `banned` = 0 AND `money` >= -500',
            [$adminToken]
        );
    }
}

if (!$intermediaryAdmin) {
    require_once __DIR__ . '/../common/maintenance.php';
    exit();
}

$getUser = $intermediaryAdmin;
require __DIR__ . '/../../public/caffemmo-resolve.html';
