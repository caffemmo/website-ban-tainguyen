<?php

if (!defined('IN_SITE')) {
    die('The Request Not Found');
}


$CMSNT = new DB();


if (isSecureCookie('user_login') != true) {
    logout_user();
} else {
    $token = validate_alphanumeric($_COOKIE['user_login'], 255);
    if ($token === false) {
        logout_user();
    }
    $getUser = $CMSNT->get_row_safe(" SELECT * FROM `users` WHERE `token` = ?", [$token]);
    // chuyển hướng đăng nhập khi thông tin login không tồn tại
    if (!$getUser) {
        // Rate limit
        checkBlockIP('SCAN', 5);
        logout_user();
    }
    if ($CMSNT->site('status_only_device_client') == 1) {
        if ($getUser['device'] != getUserAgent()) {
            logout_user();
        }
    }
    // chuyển hướng khi bị khoá tài khoản
    if ($getUser['banned'] != 0) {
        redirect(base_url('common/banned'));
    }
    // khoá tài khoản trường hợp âm tiền, tránh bug
    if ($getUser['money'] < -500) {
        $User = new users();
        $User->Banned($getUser['id'], 'Tài khoản âm tiền, ghi vấn bug');
        redirect(base_url('common/banned'));
    }
    // nếu phát hiện người dùng đang online thì ngăn chặn khôi phục mật khẩu
    if (!empty($getUser['token_forgot_password'])) {
        $CMSNT->update('users', [
            'token_forgot_password' => NULL
        ], " `id` = ?", [$getUser['id']]);
    }
    /* cập nhật thời gian online */
    $CMSNT->update("users", [
        'time_session'  => time()
    ], " `id` = ?", [$getUser['id']]);

    // Xóa bớt log dòng tiền khi đủ 10.000 dòng
    if (function_exists('apcu_exists') && function_exists('apcu_store')) {
        $cache_key = "delete_log_{$getUser['id']}_" . date('Y-m-d');
        if (!apcu_exists($cache_key)) {
            // Đếm số lượng dòng trước khi xóa
            $count_before = $CMSNT->get_row_safe("SELECT COUNT(id) as total FROM dongtien WHERE user_id = ?", [$getUser['id']])['total'];

            // Thực hiện xóa log
            $CMSNT->query("DELETE
                FROM dongtien
                WHERE user_id = " . $getUser['id'] . "
                AND id NOT IN (
                    SELECT id
                    FROM (
                        SELECT id
                        FROM dongtien
                        WHERE user_id = " . $getUser['id'] . "
                        ORDER BY thoigian DESC
                        LIMIT 10000
                    ) AS t
                )");

            // Đếm số lượng dòng sau khi xóa
            $count_after = $CMSNT->get_row_safe("SELECT COUNT(id) as total FROM dongtien WHERE user_id = ?", [$getUser['id']])['total'];
            $deleted_rows = $count_before - $count_after;

            // Chỉ ghi log khi có dòng thực sự bị xóa
            if ($deleted_rows > 0) {
                $CMSNT->insert("logs", [
                    'user_id'     => 0, // 0 = log hệ thống
                    'action'      => "Tự động xóa $deleted_rows log dòng tiền cũ của user ID {$getUser['id']} username {$getUser['username']}",
                    'createdate'  => gettime(),
                    'ip'          => myip(),
                    'device'      => getUserAgent()
                ]);
            }

            apcu_store($cache_key, true, 86400);
        }
    }
}
