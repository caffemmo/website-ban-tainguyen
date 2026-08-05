<?php

define('IN_SITE', true);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../libs/db.php';
require_once __DIR__ . '/../libs/lang.php';
require_once __DIR__ . '/../libs/helper.php';
require_once __DIR__ . '/../libs/telegram-statistics.php';

$CMSNT = new DB();
$pageKey = isset($_POST['page']) ? trim((string) $_POST['page']) : '';
caffemmo_telegram_stats_touch_session($pageKey);

http_response_code(204);
exit;
