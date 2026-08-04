<?php

define('IN_SITE', true);
require_once(__DIR__ . '/../libs/db.php');
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../libs/lang.php');
require_once(__DIR__ . '/../libs/helper.php');
require_once(__DIR__ . '/../libs/youproxy.php');

$CMSNT = new DB();
$cronKey = (string) $CMSNT->site('key_cron_job');
if ($cronKey !== '' && (!isset($_GET['key']) || !hash_equals($cronKey, (string) $_GET['key']))) {
    die('Invalid cron key');
}

youproxy_ensure_tables();
$lock = $CMSNT->get_row_safe("SELECT GET_LOCK('caffemmo_ipv6_sync', 0) AS locked");
if (!$lock || (int) ($lock['locked'] ?? 0) !== 1) {
    die('Another IPv6 sync is already running');
}

try {
    $batches = $CMSNT->get_list_safe(
        'SELECT `id` FROM `proxy_ipv6_batches`
         WHERE `status` = ? AND `provider_order_id` IS NOT NULL AND `provider_order_id` <> ?
         ORDER BY `id` ASC LIMIT 5',
        ['pending_sync', '']
    ) ?: [];
    $synced = 0;
    $failed = 0;
    foreach ($batches as $batch) {
        $result = youproxy_ipv6_retail_sync_batch((int) $batch['id']);
        if (!empty($result['success'])) {
            $synced++;
            echo 'Batch #' . (int) $batch['id'] . ': ' . (int) $result['received_quantity'] . '/' . (int) $result['expected_quantity'] . ' ' . (string) $result['status'] . "\n";
        } else {
            $failed++;
            echo 'Batch #' . (int) $batch['id'] . ': sync failed\n';
        }
    }
    echo 'Processed: ' . count($batches) . ', synced: ' . $synced . ', failed: ' . $failed . "\n";
} finally {
    $CMSNT->query("SELECT RELEASE_LOCK('caffemmo_ipv6_sync')");
}
