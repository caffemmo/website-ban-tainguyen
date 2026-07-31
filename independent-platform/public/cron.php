<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

$provided = (string) ($_GET['key'] ?? $_SERVER['HTTP_X_CRON_KEY'] ?? '');
$expected = (string) env_value('CRON_SECRET', '');
if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
    http_response_code(401);
    exit('Unauthorized');
}

$started = microtime(true);
$job = 'platform-maintenance';
$connection = db();
if (!$connection) {
    http_response_code(503);
    exit('Database unavailable');
}

try {
    $connection->beginTransaction();
    $expired = $connection->exec("UPDATE proxy_orders SET status = 'expired' WHERE status = 'active' AND expires_at IS NOT NULL AND expires_at <= UTC_TIMESTAMP()");
    $connection->commit();
    $bank = PaymentService::syncBankTransactions();
    $duration = (int) round((microtime(true) - $started) * 1000);
    $run = $connection->prepare('INSERT INTO cron_runs (job_name, status, summary, duration_ms) VALUES (?, "success", ?, ?)');
    $run->execute([$job, 'Expired proxy orders: ' . (int) $expired . '; bank credits: ' . (int) ($bank['credited'] ?? 0), $duration]);
    Logger::info('Cron completed', ['job' => $job, 'expired_proxy_orders' => (int) $expired, 'bank_credits' => (int) ($bank['credited'] ?? 0)]);
    json_response(['ok' => true, 'job' => $job, 'expired_proxy_orders' => (int) $expired, 'bank' => $bank, 'duration_ms' => $duration]);
} catch (Throwable $exception) {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }
    Logger::error('Cron failed', ['job' => $job, 'message' => $exception->getMessage()]);
    http_response_code(500);
    exit('Cron failed');
}
