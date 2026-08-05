<?php

define('IN_SITE', true);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../libs/db.php';
require_once __DIR__ . '/../libs/lang.php';
require_once __DIR__ . '/../libs/helper.php';
require_once __DIR__ . '/../libs/telegram-statistics.php';

$CMSNT = new DB();
caffemmo_telegram_stats_touch_session();

http_response_code(204);
exit;
