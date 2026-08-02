<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

if (!function_exists('client_optional_user')) {
    /** Load the current customer without redirecting public pages to login. */
    function client_optional_user($db)
    {
        if (!is_object($db) || !function_exists('isSecureCookie') || !function_exists('validate_alphanumeric')) {
            return null;
        }
        if (isSecureCookie('user_login') !== true || empty($_COOKIE['user_login'])) {
            return null;
        }

        $token = validate_alphanumeric($_COOKIE['user_login'], 255);
        if ($token === false) {
            return null;
        }

        $user = $db->get_row_safe('SELECT * FROM `users` WHERE `token` = ?', [$token]);
        if (!$user || (int) ($user['banned'] ?? 0) !== 0) {
            return null;
        }
        if (isset($user['money']) && (float) $user['money'] < -500) {
            return null;
        }
        if (function_exists('getUserAgent')
            && (int) $db->site('status_only_device_client') === 1
            && (string) ($user['device'] ?? '') !== (string) getUserAgent()) {
            return null;
        }

        return $user;
    }
}
