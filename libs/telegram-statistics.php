<?php

if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

function caffemmo_telegram_stats_ensure_visits_table()
{
    global $CMSNT;
    static $ready = null;

    if ($ready !== null) {
        return $ready;
    }
    if (!isset($CMSNT) || !is_object($CMSNT)) {
        return false;
    }

    $sql = "CREATE TABLE IF NOT EXISTS `site_visit_daily` (
        `visitor_key` CHAR(64) NOT NULL,
        `visit_date` DATE NOT NULL,
        `first_visited_at` DATETIME NOT NULL,
        `last_visited_at` DATETIME NOT NULL,
        `visit_count` INT UNSIGNED NOT NULL DEFAULT 1,
        PRIMARY KEY (`visitor_key`, `visit_date`),
        KEY `site_visit_daily_last_visited_at` (`last_visited_at`),
        KEY `site_visit_daily_visit_date` (`visit_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $ready = $CMSNT->query($sql) !== false;
    return $ready;
}

function caffemmo_telegram_stats_ensure_settings()
{
    global $CMSNT;
    static $ready = null;

    if ($ready !== null) {
        return $ready;
    }
    if (!isset($CMSNT) || !is_object($CMSNT)) {
        return false;
    }

    if (!$CMSNT->get_row_safe('SELECT `name` FROM `settings` WHERE `name` = ? LIMIT 1', ['telegram_visitor_notifications'])) {
        $CMSNT->insert('settings', [
            'name' => 'telegram_visitor_notifications',
            'value' => '0'
        ]);
    }
    $ready = true;
    return true;
}

function caffemmo_telegram_stats_visitor_notifications_enabled()
{
    global $CMSNT;
    caffemmo_telegram_stats_ensure_settings();
    return isset($CMSNT) && is_object($CMSNT) && (string) $CMSNT->site('telegram_visitor_notifications') === '1';
}

function caffemmo_telegram_stats_ensure_presence_table()
{
    global $CMSNT;
    static $ready = null;

    if ($ready !== null) {
        return $ready;
    }
    if (!isset($CMSNT) || !is_object($CMSNT)) {
        return false;
    }

    $sql = "CREATE TABLE IF NOT EXISTS `site_live_sessions` (
        `session_key` CHAR(64) NOT NULL,
        `visitor_key` CHAR(64) NOT NULL,
        `started_at` DATETIME NOT NULL,
        `last_seen_at` DATETIME NOT NULL,
        `ended_at` DATETIME NULL,
        PRIMARY KEY (`session_key`),
        KEY `site_live_sessions_last_seen_at` (`last_seen_at`),
        KEY `site_live_sessions_ended_at` (`ended_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $ready = $CMSNT->query($sql) !== false;
    return $ready;
}

function caffemmo_telegram_stats_cookie_id($name, $expires = 0)
{
    $value = isset($_COOKIE[$name]) ? trim((string) $_COOKIE[$name]) : '';
    if (preg_match('/^[a-f0-9]{64}$/', $value)) {
        return $value;
    }

    try {
        $value = bin2hex(random_bytes(32));
    } catch (Exception $exception) {
        $value = hash('sha256', uniqid('', true) . '|' . mt_rand());
    }

    setcookie($name, $value, [
        'expires' => $expires,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    $_COOKIE[$name] = $value;
    return $value;
}

function caffemmo_telegram_stats_excluded_username()
{
    return 'xuabthabgz221zgg';
}

function caffemmo_telegram_stats_excluded_user_ids()
{
    global $CMSNT;
    static $userIds = null;

    if ($userIds !== null) {
        return $userIds;
    }
    $userIds = [];
    if (!isset($CMSNT) || !is_object($CMSNT)) {
        return $userIds;
    }

    $rows = $CMSNT->get_list_safe(
        'SELECT `id` FROM `users` WHERE LOWER(`username`) = ?',
        [caffemmo_telegram_stats_excluded_username()]
    ) ?: [];
    foreach ($rows as $row) {
        $userIds[] = (int) ($row['id'] ?? 0);
    }
    return $userIds;
}

function caffemmo_telegram_stats_notify_presence($event, $session)
{
    global $CMSNT;
    if (!caffemmo_telegram_stats_visitor_notifications_enabled() || $CMSNT->site('telegram_status') != 1) {
        return false;
    }
    if (!class_exists('TelegramQueue')) {
        require_once __DIR__ . '/TelegramQueue.php';
    }

    $message = $event === 'enter'
        ? "🌐 <b>Có khách vừa vào website</b>\n🕒 Thời gian: " . gettime()
        : "🚪 <b>Có khách đã rời website</b>\n🕒 Phát hiện lúc: " . gettime() . "\nℹ️ Không hoạt động trong 5 phút";
    $queue = new TelegramQueue();
    return $queue->queueMessage($message, null, null, 2, [
        'type' => 'visitor_presence',
        'event' => $event,
        'session_key' => substr((string) ($session['session_key'] ?? ''), 0, 12)
    ]);
}

function caffemmo_telegram_stats_sweep_live_sessions()
{
    global $CMSNT;
    if (!caffemmo_telegram_stats_ensure_presence_table()) {
        return 0;
    }

    $cutoff = date('Y-m-d H:i:s', time() - 300);
    $rows = $CMSNT->get_list_safe(
        'SELECT `session_key`, `started_at`, `last_seen_at` FROM `site_live_sessions` WHERE `ended_at` IS NULL AND `last_seen_at` < ?',
        [$cutoff]
    ) ?: [];
    $closed = 0;
    foreach ($rows as $row) {
        $updated = $CMSNT->update(
            'site_live_sessions',
            ['ended_at' => date('Y-m-d H:i:s')],
            ' `session_key` = ? AND `ended_at` IS NULL ',
            [$row['session_key']]
        );
        if ($updated) {
            $closed++;
            caffemmo_telegram_stats_notify_presence('leave', $row);
        }
    }
    return $closed;
}

function caffemmo_telegram_stats_touch_session()
{
    global $CMSNT;
    $sessionId = isset($_COOKIE['caffemmo_visit_session_id']) ? trim((string) $_COOKIE['caffemmo_visit_session_id']) : '';
    if (!preg_match('/^[a-f0-9]{64}$/', $sessionId) || !caffemmo_telegram_stats_ensure_presence_table()) {
        return false;
    }

    return $CMSNT->update(
        'site_live_sessions',
        ['last_seen_at' => date('Y-m-d H:i:s')],
        ' `session_key` = ? AND `ended_at` IS NULL ',
        [$sessionId]
    );
}

function caffemmo_telegram_stats_live_count()
{
    global $CMSNT;
    if (!caffemmo_telegram_stats_ensure_presence_table()) {
        return 0;
    }
    $row = $CMSNT->get_row_safe(
        'SELECT COUNT(*) AS `online` FROM `site_live_sessions` WHERE `ended_at` IS NULL AND `last_seen_at` >= ?',
        [date('Y-m-d H:i:s', time() - 300)]
    );
    return (int) ($row['online'] ?? 0);
}

function caffemmo_telegram_stats_track_visit()
{
    global $CMSNT;

    if (!isset($CMSNT) || !is_object($CMSNT) || !caffemmo_telegram_stats_ensure_visits_table()) {
        return false;
    }

    caffemmo_telegram_stats_ensure_settings();
    caffemmo_telegram_stats_sweep_live_sessions();
    $visitorId = caffemmo_telegram_stats_cookie_id('caffemmo_visitor_id', time() + 31536000);

    $visitorKey = hash('sha256', $visitorId);
    $visitDate = date('Y-m-d');
    $visitedAt = date('Y-m-d H:i:s');
    $existing = $CMSNT->get_row_safe(
        'SELECT `visitor_key` FROM `site_visit_daily` WHERE `visitor_key` = ? AND `visit_date` = ? LIMIT 1',
        [$visitorKey, $visitDate]
    );

    if ($existing) {
        $CMSNT->cong('site_visit_daily', 'visit_count', 1, ' `visitor_key` = ? AND `visit_date` = ? ', [$visitorKey, $visitDate]);
        $result = $CMSNT->update(
            'site_visit_daily',
            ['last_visited_at' => $visitedAt],
            ' `visitor_key` = ? AND `visit_date` = ? ',
            [$visitorKey, $visitDate]
        );
    } else {
        $result = $CMSNT->insert('site_visit_daily', [
            'visitor_key' => $visitorKey,
            'visit_date' => $visitDate,
            'first_visited_at' => $visitedAt,
            'last_visited_at' => $visitedAt,
            'visit_count' => 1
        ]);
    }

    if (caffemmo_telegram_stats_ensure_presence_table()) {
        $sessionId = caffemmo_telegram_stats_cookie_id('caffemmo_visit_session_id');
        $liveSession = $CMSNT->get_row_safe(
            'SELECT `session_key`, `ended_at` FROM `site_live_sessions` WHERE `session_key` = ? LIMIT 1',
            [$sessionId]
        );
        $wasEnded = $liveSession && !empty($liveSession['ended_at']);
        if ($liveSession) {
            $CMSNT->update(
                'site_live_sessions',
                ['last_seen_at' => $visitedAt, 'ended_at' => null],
                ' `session_key` = ? ',
                [$sessionId]
            );
        } else {
            $CMSNT->insert('site_live_sessions', [
                'session_key' => $sessionId,
                'visitor_key' => $visitorKey,
                'started_at' => $visitedAt,
                'last_seen_at' => $visitedAt,
                'ended_at' => null
            ]);
        }
        if (!$liveSession || $wasEnded) {
            caffemmo_telegram_stats_notify_presence('enter', [
                'session_key' => $sessionId,
                'started_at' => $visitedAt
            ]);
        }
    }

    return $result;
}

function caffemmo_telegram_stats_empty_period()
{
    return [
        'orders' => 0,
        'quantity' => 0,
        'revenue' => 0,
        'cost' => 0,
        'profit' => 0,
        'unknown_cost_revenue' => 0
    ];
}

function caffemmo_telegram_stats_table_exists($table)
{
    global $CMSNT;
    static $cache = [];

    if (!preg_match('/^[a-zA-Z0-9_]+$/', (string) $table)) {
        return false;
    }
    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }
    if (!isset($CMSNT) || !is_object($CMSNT)) {
        return false;
    }

    $row = $CMSNT->get_row_safe(
        'SELECT COUNT(*) AS `total` FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
        [$table]
    );
    $cache[$table] = $row && (int) ($row['total'] ?? 0) > 0;
    return $cache[$table];
}

function caffemmo_telegram_stats_add(&$summary, $orders, $quantity, $revenue, $cost, $unknownCostRevenue = 0)
{
    $summary['orders'] += max(0, (int) $orders);
    $summary['quantity'] += max(0, (int) $quantity);
    $summary['revenue'] += (float) $revenue;
    $summary['cost'] += (float) $cost;
    $summary['unknown_cost_revenue'] += (float) $unknownCostRevenue;
    $summary['profit'] = $summary['revenue'] - $summary['cost'];
}

function caffemmo_telegram_stats_period($start, $end)
{
    global $CMSNT;
    static $proxyRows = null;
    static $proxyReady = null;
    $summary = caffemmo_telegram_stats_empty_period();
    $excludedUsername = caffemmo_telegram_stats_excluded_username();

    if (caffemmo_telegram_stats_table_exists('product_order')) {
        $row = $CMSNT->get_row_safe(
            'SELECT COUNT(po.`id`) AS `orders`, COALESCE(SUM(po.`amount`), 0) AS `quantity`, COALESCE(SUM(po.`pay`), 0) AS `revenue`, COALESCE(SUM(po.`cost`), 0) AS `cost` FROM `product_order` po LEFT JOIN `users` stats_user ON stats_user.`id` = po.`buyer` WHERE po.`refund` = 0 AND po.`trash` = 0 AND po.`create_gettime` >= ? AND po.`create_gettime` < ? AND (stats_user.`id` IS NULL OR LOWER(stats_user.`username`) <> ?)',
            [$start, $end, $excludedUsername]
        );
        caffemmo_telegram_stats_add($summary, $row['orders'] ?? 0, $row['quantity'] ?? 0, $row['revenue'] ?? 0, $row['cost'] ?? 0);
    }

    if (caffemmo_telegram_stats_table_exists('proxy_orders')) {
        if ($proxyReady === null) {
            require_once __DIR__ . '/proxy-accounting.php';
            $proxyReady = function_exists('youproxy_ensure_tables') && youproxy_ensure_tables();
            $proxyRows = $proxyReady ? proxy_accounting_fetch_orders() : [];
        }
        if ($proxyReady) {
            $excludedUserIds = caffemmo_telegram_stats_excluded_user_ids();
            $periodProxyRows = array_filter($proxyRows, function ($row) use ($start, $end, $excludedUserIds) {
                $createdAt = (string) ($row['created_at'] ?? '');
                $userId = (int) ($row['user_id'] ?? 0);
                return $createdAt >= $start && $createdAt < $end && !in_array($userId, $excludedUserIds, true);
            });
            $proxyConfig = function_exists('youproxy_config') ? youproxy_config() : ['usd_rate' => 25000];
            $proxySummary = proxy_accounting_summarize_orders($periodProxyRows, max(1, (float) ($proxyConfig['usd_rate'] ?? 25000)));
            caffemmo_telegram_stats_add(
                $summary,
                $proxySummary['orders'] ?? 0,
                $proxySummary['quantity'] ?? 0,
                $proxySummary['revenue'] ?? 0,
                $proxySummary['cost_of_goods_sold'] ?? 0
            );
        }
    }

    if (caffemmo_telegram_stats_table_exists('up_tich_xanh_orders')) {
        $row = $CMSNT->get_row_safe(
            'SELECT COUNT(ut.`id`) AS `orders`, COUNT(ut.`id`) AS `quantity`, COALESCE(SUM(ut.`charged_amount`), 0) AS `revenue`, COALESCE(SUM(ut.`provider_cost`), 0) AS `cost` FROM `up_tich_xanh_orders` ut LEFT JOIN `users` stats_user ON stats_user.`id` = ut.`user_id` WHERE ut.`provider_status` IN (?, ?) AND ut.`created_at` >= ? AND ut.`created_at` < ? AND (stats_user.`id` IS NULL OR LOWER(stats_user.`username`) <> ?)',
            ['success', 'completed', $start, $end, $excludedUsername]
        );
        caffemmo_telegram_stats_add($summary, $row['orders'] ?? 0, $row['quantity'] ?? 0, $row['revenue'] ?? 0, $row['cost'] ?? 0);
    }

    if (caffemmo_telegram_stats_table_exists('netflix_orders')) {
        $row = $CMSNT->get_row_safe(
            'SELECT COUNT(no.`id`) AS `orders`, COUNT(no.`id`) AS `quantity`, COALESCE(SUM(no.`charged_amount`), 0) AS `revenue` FROM `netflix_orders` no LEFT JOIN `users` stats_user ON stats_user.`id` = no.`user_id` WHERE no.`created_at` >= ? AND no.`created_at` < ? AND (stats_user.`id` IS NULL OR LOWER(stats_user.`username`) <> ?)',
            [$start, $end, $excludedUsername]
        );
        caffemmo_telegram_stats_add($summary, $row['orders'] ?? 0, $row['quantity'] ?? 0, $row['revenue'] ?? 0, 0, $row['revenue'] ?? 0);
    }

    if (caffemmo_telegram_stats_table_exists('locket_gold_orders')) {
        $row = $CMSNT->get_row_safe(
            'SELECT COUNT(lo.`id`) AS `orders`, COALESCE(SUM(lo.`account_count`), 0) AS `quantity`, COALESCE(SUM(lo.`charged_amount`), 0) AS `revenue` FROM `locket_gold_orders` lo LEFT JOIN `users` stats_user ON stats_user.`id` = lo.`user_id` WHERE lo.`status` = ? AND lo.`refund_amount` = 0 AND lo.`created_at` >= ? AND lo.`created_at` < ? AND (stats_user.`id` IS NULL OR LOWER(stats_user.`username`) <> ?)',
            ['completed', $start, $end, $excludedUsername]
        );
        caffemmo_telegram_stats_add($summary, $row['orders'] ?? 0, $row['quantity'] ?? 0, $row['revenue'] ?? 0, 0, $row['revenue'] ?? 0);
    }

    if (caffemmo_telegram_stats_table_exists('dongtien')) {
        $row = $CMSNT->get_row_safe(
            "SELECT COUNT(dt.`id`) AS `orders`, COALESCE(SUM(dt.`sotienthaydoi`), 0) AS `revenue` FROM `dongtien` dt LEFT JOIN `users` stats_user ON stats_user.`id` = dt.`user_id` WHERE dt.`noidung` LIKE ? AND dt.`thoigian` >= ? AND dt.`thoigian` < ? AND (stats_user.`id` IS NULL OR LOWER(stats_user.`username`) <> ?)",
            ['Gia hạn proxy (%', $start, $end, $excludedUsername]
        );
        caffemmo_telegram_stats_add($summary, $row['orders'] ?? 0, $row['orders'] ?? 0, $row['revenue'] ?? 0, 0, $row['revenue'] ?? 0);
    }

    return $summary;
}

function caffemmo_telegram_stats_period_bounds($period)
{
    $now = new DateTimeImmutable('now');
    if ($period === 'week') {
        $start = $now->modify('monday this week')->setTime(0, 0, 0);
        $end = $start->modify('+1 week');
    } elseif ($period === 'month') {
        $start = $now->modify('first day of this month')->setTime(0, 0, 0);
        $end = $start->modify('+1 month');
    } elseif ($period === 'previous_month') {
        $start = $now->modify('first day of last month')->setTime(0, 0, 0);
        $end = $start->modify('+1 month');
    } else {
        $start = $now->setTime(0, 0, 0);
        $end = $start->modify('+1 day');
    }

    return [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')];
}

function caffemmo_telegram_stats_dashboard()
{
    global $CMSNT;
    caffemmo_telegram_stats_ensure_settings();
    $visitsReady = caffemmo_telegram_stats_ensure_visits_table();
    [$todayStart, $todayEnd] = caffemmo_telegram_stats_period_bounds('today');
    [$weekStart, $weekEnd] = caffemmo_telegram_stats_period_bounds('week');
    [$monthStart, $monthEnd] = caffemmo_telegram_stats_period_bounds('month');
    [$previousMonthStart, $previousMonthEnd] = caffemmo_telegram_stats_period_bounds('previous_month');

    $visitors = ['unique_today' => 0, 'visits_today' => 0];
    $online = ['online' => 0];
    if ($visitsReady) {
        $todayDate = date('Y-m-d');
        $onlineSince = date('Y-m-d H:i:s', time() - 300);
        caffemmo_telegram_stats_sweep_live_sessions();
        $visitors = $CMSNT->get_row_safe(
            'SELECT COUNT(*) AS `unique_today`, COALESCE(SUM(`visit_count`), 0) AS `visits_today` FROM `site_visit_daily` WHERE `visit_date` = ?',
            [$todayDate]
        ) ?: $visitors;
        if (caffemmo_telegram_stats_ensure_presence_table()) {
            $online = ['online' => caffemmo_telegram_stats_live_count()];
        } else {
            $online = $CMSNT->get_row_safe(
                'SELECT COUNT(*) AS `online` FROM `site_visit_daily` WHERE `last_visited_at` >= ?',
                [$onlineSince]
            ) ?: $online;
        }
    }

    return [
        'online' => (int) ($online['online'] ?? 0),
        'unique_today' => (int) ($visitors['unique_today'] ?? 0),
        'visits_today' => (int) ($visitors['visits_today'] ?? 0),
        'today' => caffemmo_telegram_stats_period($todayStart, $todayEnd),
        'week' => caffemmo_telegram_stats_period($weekStart, $weekEnd),
        'month' => caffemmo_telegram_stats_period($monthStart, $monthEnd),
        'previous_month' => caffemmo_telegram_stats_period($previousMonthStart, $previousMonthEnd)
    ];
}

function caffemmo_telegram_stats_money($amount)
{
    return format_currency((float) $amount);
}

function caffemmo_telegram_stats_change($current, $previous)
{
    $difference = (float) $current - (float) $previous;
    if ((float) $previous == 0.0) {
        return [$difference, null];
    }
    return [$difference, round(($difference / abs((float) $previous)) * 100, 2)];
}
