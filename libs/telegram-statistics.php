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

function caffemmo_telegram_stats_track_visit()
{
    global $CMSNT;

    if (!isset($CMSNT) || !is_object($CMSNT) || !caffemmo_telegram_stats_ensure_visits_table()) {
        return false;
    }

    $cookieName = 'caffemmo_visitor_id';
    $visitorId = isset($_COOKIE[$cookieName]) ? trim((string) $_COOKIE[$cookieName]) : '';
    if (!preg_match('/^[a-f0-9]{64}$/', $visitorId)) {
        try {
            $visitorId = bin2hex(random_bytes(32));
        } catch (Exception $exception) {
            $visitorId = hash('sha256', uniqid('', true) . '|' . mt_rand());
        }

        setcookie($cookieName, $visitorId, [
            'expires' => time() + 31536000,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        $_COOKIE[$cookieName] = $visitorId;
    }

    $visitorKey = hash('sha256', $visitorId);
    $visitDate = date('Y-m-d');
    $visitedAt = date('Y-m-d H:i:s');
    $existing = $CMSNT->get_row_safe(
        'SELECT `visitor_key` FROM `site_visit_daily` WHERE `visitor_key` = ? AND `visit_date` = ? LIMIT 1',
        [$visitorKey, $visitDate]
    );

    if ($existing) {
        $CMSNT->cong('site_visit_daily', 'visit_count', 1, ' `visitor_key` = ? AND `visit_date` = ? ', [$visitorKey, $visitDate]);
        return $CMSNT->update(
            'site_visit_daily',
            ['last_visited_at' => $visitedAt],
            ' `visitor_key` = ? AND `visit_date` = ? ',
            [$visitorKey, $visitDate]
        );
    }

    return $CMSNT->insert('site_visit_daily', [
        'visitor_key' => $visitorKey,
        'visit_date' => $visitDate,
        'first_visited_at' => $visitedAt,
        'last_visited_at' => $visitedAt,
        'visit_count' => 1
    ]);
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

    if (caffemmo_telegram_stats_table_exists('product_order')) {
        $row = $CMSNT->get_row_safe(
            'SELECT COUNT(*) AS `orders`, COALESCE(SUM(`amount`), 0) AS `quantity`, COALESCE(SUM(`pay`), 0) AS `revenue`, COALESCE(SUM(`cost`), 0) AS `cost` FROM `product_order` WHERE `refund` = 0 AND `trash` = 0 AND `create_gettime` >= ? AND `create_gettime` < ?',
            [$start, $end]
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
            $periodProxyRows = array_filter($proxyRows, function ($row) use ($start, $end) {
                $createdAt = (string) ($row['created_at'] ?? '');
                return $createdAt >= $start && $createdAt < $end;
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
            'SELECT COUNT(*) AS `orders`, COUNT(*) AS `quantity`, COALESCE(SUM(`charged_amount`), 0) AS `revenue`, COALESCE(SUM(`provider_cost`), 0) AS `cost` FROM `up_tich_xanh_orders` WHERE `provider_status` IN (?, ?) AND `created_at` >= ? AND `created_at` < ?',
            ['success', 'completed', $start, $end]
        );
        caffemmo_telegram_stats_add($summary, $row['orders'] ?? 0, $row['quantity'] ?? 0, $row['revenue'] ?? 0, $row['cost'] ?? 0);
    }

    if (caffemmo_telegram_stats_table_exists('netflix_orders')) {
        $row = $CMSNT->get_row_safe(
            'SELECT COUNT(*) AS `orders`, COUNT(*) AS `quantity`, COALESCE(SUM(`charged_amount`), 0) AS `revenue` FROM `netflix_orders` WHERE `created_at` >= ? AND `created_at` < ?',
            [$start, $end]
        );
        caffemmo_telegram_stats_add($summary, $row['orders'] ?? 0, $row['quantity'] ?? 0, $row['revenue'] ?? 0, 0, $row['revenue'] ?? 0);
    }

    if (caffemmo_telegram_stats_table_exists('locket_gold_orders')) {
        $row = $CMSNT->get_row_safe(
            'SELECT COUNT(*) AS `orders`, COALESCE(SUM(`account_count`), 0) AS `quantity`, COALESCE(SUM(`charged_amount`), 0) AS `revenue` FROM `locket_gold_orders` WHERE `status` = ? AND `refund_amount` = 0 AND `created_at` >= ? AND `created_at` < ?',
            ['completed', $start, $end]
        );
        caffemmo_telegram_stats_add($summary, $row['orders'] ?? 0, $row['quantity'] ?? 0, $row['revenue'] ?? 0, 0, $row['revenue'] ?? 0);
    }

    if (caffemmo_telegram_stats_table_exists('dongtien')) {
        $row = $CMSNT->get_row_safe(
            "SELECT COUNT(*) AS `orders`, COALESCE(SUM(`sotienthaydoi`), 0) AS `revenue` FROM `dongtien` WHERE `noidung` LIKE ? AND `thoigian` >= ? AND `thoigian` < ?",
            ['Gia hạn proxy (%', $start, $end]
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
        $visitors = $CMSNT->get_row_safe(
            'SELECT COUNT(*) AS `unique_today`, COALESCE(SUM(`visit_count`), 0) AS `visits_today` FROM `site_visit_daily` WHERE `visit_date` = ?',
            [$todayDate]
        ) ?: $visitors;
        $online = $CMSNT->get_row_safe(
            'SELECT COUNT(*) AS `online` FROM `site_visit_daily` WHERE `last_visited_at` >= ?',
            [$onlineSince]
        ) ?: $online;
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
