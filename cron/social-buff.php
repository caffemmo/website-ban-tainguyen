<?php

define('IN_SITE', true);
require_once dirname(__DIR__) . '/libs/db.php';
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/libs/lang.php';
require_once dirname(__DIR__) . '/libs/helper.php';
require_once dirname(__DIR__) . '/libs/social-buff.php';

$CMSNT = new DB();

if (!empty($CMSNT->site('key_cron_job'))
    && (empty($_GET['key']) || !hash_equals((string) $CMSNT->site('key_cron_job'), (string) $_GET['key']))) {
    http_response_code(403);
    die('Key không hợp lệ');
}

if (!social_buff_ensure_tables()) {
    http_response_code(503);
    die('Không thể khởi tạo dữ liệu');
}

$lastRun = (int) $CMSNT->site('check_time_cron_social_buff');
if ($lastRun > 0 && time() - $lastRun < 20) {
    die('Tác vụ vừa được chạy, vui lòng thử lại sau');
}

$CMSNT->update('settings', ['value' => time()], " `name` = 'check_time_cron_social_buff' ");
$synced = social_buff_sync_open_orders(40);
echo 'Đã đồng bộ đơn Buff mạng xã hội: ' . (int) $synced;
