<?php

use Detection\MobileDetect;

 if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
 
require_once(__DIR__.'/../../models/is_admin.php');
if(checkPermission($getUser['admin'], 'login_user') != true){
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}
if (isset($_GET['id'])){
    $CMSNT = new DB();
    $id = check_string($_GET['id']);
    $user = $CMSNT->get_row("SELECT * FROM `users` WHERE `id` = '$id' ");
    if (!$user) {
        redirect(base_url_admin());
    }
    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => 'Login user '.$user['username']
    ]);
    $_COOKIE['user_login'] = $user['token'];
    redirect(base_url());
}
redirect(base_url_admin());
