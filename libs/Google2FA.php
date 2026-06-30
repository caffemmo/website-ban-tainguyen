<?php

if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

if (!class_exists('SecurityValidator')) {
    require_once __DIR__ . '/session.php';
}

use PragmaRX\Google2FAQRCode\Google2FA;

// tạo mã google 2fa
function generateSecretKey_Google2FA()
{
    $google2fa = new Google2FA();
    return $google2fa->generateSecretKey();
}
