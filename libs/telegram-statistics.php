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
        `visit_id` BIGINT UNSIGNED NULL,
        `visitor_type` VARCHAR(16) NOT NULL DEFAULT 'returning',
        `last_page_key` VARCHAR(191) NULL,
        `last_page_label` VARCHAR(255) NULL,
        PRIMARY KEY (`session_key`),
        KEY `site_live_sessions_last_seen_at` (`last_seen_at`),
        KEY `site_live_sessions_ended_at` (`ended_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $ready = $CMSNT->query($sql) !== false;
    if (!$ready) {
        return false;
    }

    $historySql = "CREATE TABLE IF NOT EXISTS `site_visit_history` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `session_key` CHAR(64) NOT NULL,
        `visitor_key` CHAR(64) NOT NULL,
        `visitor_code` CHAR(16) NOT NULL,
        `visitor_type` VARCHAR(16) NOT NULL DEFAULT 'returning',
        `started_at` DATETIME NOT NULL,
        `last_seen_at` DATETIME NOT NULL,
        `ended_at` DATETIME NULL,
        `duration_seconds` INT UNSIGNED NOT NULL DEFAULT 0,
        `last_page_key` VARCHAR(191) NULL,
        `last_page_label` VARCHAR(255) NULL,
        `leave_reason` VARCHAR(255) NULL,
        PRIMARY KEY (`id`),
        KEY `site_visit_history_visitor_key` (`visitor_key`),
        KEY `site_visit_history_started_at` (`started_at`),
        KEY `site_visit_history_ended_at` (`ended_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $activitySql = "CREATE TABLE IF NOT EXISTS `site_visit_activity` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `visit_id` BIGINT UNSIGNED NOT NULL,
        `session_key` CHAR(64) NOT NULL,
        `visitor_key` CHAR(64) NOT NULL,
        `page_key` VARCHAR(191) NOT NULL,
        `page_label` VARCHAR(255) NOT NULL,
        `entered_at` DATETIME NOT NULL,
        `last_seen_at` DATETIME NOT NULL,
        `duration_seconds` INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `site_visit_activity_visit_id` (`visit_id`),
        KEY `site_visit_activity_entered_at` (`entered_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $ready = $CMSNT->query($historySql) !== false && $CMSNT->query($activitySql) !== false;
    if (!$ready) {
        return false;
    }

    $columns = $CMSNT->get_list_safe(
        'SELECT `COLUMN_NAME` FROM information_schema.columns WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = ?',
        ['site_live_sessions']
    ) ?: [];
    $existingColumns = [];
    foreach ($columns as $column) {
        $existingColumns[(string) ($column['COLUMN_NAME'] ?? '')] = true;
    }
    $missingColumns = [
        'visit_id' => 'BIGINT UNSIGNED NULL',
        'visitor_type' => "VARCHAR(16) NOT NULL DEFAULT 'returning'",
        'last_page_key' => 'VARCHAR(191) NULL',
        'last_page_label' => 'VARCHAR(255) NULL'
    ];
    foreach ($missingColumns as $column => $definition) {
        if (!isset($existingColumns[$column])) {
            $ready = $CMSNT->query("ALTER TABLE `site_live_sessions` ADD COLUMN `{$column}` {$definition}") !== false && $ready;
        }
    }
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

function caffemmo_telegram_stats_page_context($pageKey = '')
{
    $pageKey = trim((string) $pageKey);
    $pageKey = preg_replace('/[^a-zA-Z0-9_\/-]/', '', $pageKey);
    $pageKey = trim($pageKey, '/');
    if ($pageKey === '') {
        $pageKey = 'client/home';
    }
    $clientPosition = strpos($pageKey, 'client/');
    if ($clientPosition !== false && $clientPosition > 0) {
        $pageKey = substr($pageKey, $clientPosition);
    }
    $pageKey = preg_replace('#/\d+$#', '', $pageKey);

    $labels = [
        'client/home' => 'Trang chủ',
        'client/products' => 'Danh sách sản phẩm',
        'client/product' => 'Chi tiết sản phẩm',
        'client/product-order' => 'Đặt hàng tài nguyên',
        'client/product-orders' => 'Lịch sử tài nguyên',
        'client/proxy-buy' => 'Mua proxy',
        'client/proxy-list' => 'Danh sách proxy',
        'client/proxy-renew' => 'Gia hạn proxy',
        'client/up-tich-xanh' => 'Dịch vụ Up Tích Xanh',
        'client/up-tich-xanh-history' => 'Lịch sử Up Tích Xanh',
        'client/netflix' => 'Dịch vụ Netflix',
        'client/netflix-history' => 'Lịch sử Netflix',
        'client/locket-gold' => 'Dịch vụ Locket Gold',
        'client/locket-gold-history' => 'Lịch sử Locket Gold',
        'client/recharge-bank' => 'Nạp tiền ngân hàng',
        'client/transactions' => 'Lịch sử giao dịch',
        'client/profile' => 'Hồ sơ tài khoản',
        'client/login' => 'Đăng nhập',
        'client/register' => 'Đăng ký'
    ];
    $label = $labels[$pageKey] ?? ('Mục: ' . str_replace(['client/', '-', '_'], ['', ' ', ' '], $pageKey));
    return ['key' => substr($pageKey, 0, 191), 'label' => substr($label, 0, 255)];
}

function caffemmo_telegram_stats_visitor_code($visitorKey)
{
    return 'KH-' . strtoupper(substr(hash('sha256', (string) $visitorKey), 0, 8));
}

function caffemmo_telegram_stats_seconds_between($start, $end)
{
    $startTime = strtotime((string) $start);
    $endTime = strtotime((string) $end);
    return $startTime && $endTime ? max(0, $endTime - $startTime) : 0;
}

function caffemmo_telegram_stats_duration($seconds)
{
    $seconds = max(0, (int) $seconds);
    $minutes = intdiv($seconds, 60);
    $remainingSeconds = $seconds % 60;
    return $minutes > 0 ? $minutes . ' phút ' . $remainingSeconds . ' giây' : $remainingSeconds . ' giây';
}

function caffemmo_telegram_stats_html($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function caffemmo_telegram_stats_visit_counts($date)
{
    global $CMSNT;
    $start = $date . ' 00:00:00';
    $end = date('Y-m-d 00:00:00', strtotime($date . ' +1 day'));
    $entries = $CMSNT->get_row_safe(
        'SELECT COUNT(*) AS `total`, COUNT(DISTINCT `visitor_key`) AS `unique_visitors` FROM `site_visit_history` WHERE `started_at` >= ? AND `started_at` < ?',
        [$start, $end]
    ) ?: [];
    $leaves = $CMSNT->get_row_safe(
        'SELECT COUNT(*) AS `total`, COUNT(DISTINCT `visitor_key`) AS `unique_visitors` FROM `site_visit_history` WHERE `ended_at` >= ? AND `ended_at` < ?',
        [$start, $end]
    ) ?: [];
    return [
        'entries' => (int) ($entries['total'] ?? 0),
        'unique_entries' => (int) ($entries['unique_visitors'] ?? 0),
        'leaves' => (int) ($leaves['total'] ?? 0),
        'unique_leaves' => (int) ($leaves['unique_visitors'] ?? 0)
    ];
}

function caffemmo_telegram_stats_top_pages($date, $limit = 5)
{
    global $CMSNT;
    $start = $date . ' 00:00:00';
    $end = date('Y-m-d 00:00:00', strtotime($date . ' +1 day'));
    return $CMSNT->get_list_safe(
        'SELECT `page_key`, `page_label`, COUNT(*) AS `visits`, COUNT(DISTINCT `visitor_key`) AS `unique_visitors` FROM `site_visit_activity` WHERE `entered_at` >= ? AND `entered_at` < ? GROUP BY `page_key`, `page_label` ORDER BY `unique_visitors` DESC, `visits` DESC LIMIT ' . (int) $limit,
        [$start, $end]
    ) ?: [];
}

function caffemmo_telegram_stats_recent_visits($limit = 8)
{
    global $CMSNT;
    $rows = $CMSNT->get_list_safe(
        'SELECT `id`, `visitor_key`, `visitor_type`, `started_at`, `ended_at`, `duration_seconds`, `last_page_label` FROM `site_visit_history` ORDER BY `started_at` DESC LIMIT ' . (int) $limit
    ) ?: [];
    foreach ($rows as &$row) {
        if (empty($row['ended_at'])) {
            $row['duration_seconds'] = caffemmo_telegram_stats_seconds_between($row['started_at'], date('Y-m-d H:i:s'));
            $row['status'] = 'online';
        } else {
            $row['status'] = 'left';
        }
        $row['visitor_code'] = caffemmo_telegram_stats_visitor_code($row['visitor_key'] ?? '');
        $row['activity'] = caffemmo_telegram_stats_activity_log($row['id'], 8);
    }
    unset($row);
    return $rows;
}

function caffemmo_telegram_stats_presence_snapshot()
{
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    return [
        'online' => caffemmo_telegram_stats_live_count(),
        'today' => caffemmo_telegram_stats_visit_counts($today),
        'yesterday' => caffemmo_telegram_stats_visit_counts($yesterday)
    ];
}

function caffemmo_telegram_stats_activity_log($visitId, $limit = 8)
{
    global $CMSNT;
    $rows = $CMSNT->get_list_safe(
        'SELECT `page_label`, `duration_seconds` FROM `site_visit_activity` WHERE `visit_id` = ? ORDER BY `entered_at` ASC LIMIT ' . (int) $limit,
        [(int) $visitId]
    ) ?: [];
    $history = [];
    foreach ($rows as $row) {
        $history[] = (string) ($row['page_label'] ?? 'Không rõ') . ' (' . caffemmo_telegram_stats_duration($row['duration_seconds'] ?? 0) . ')';
    }
    return $history;
}

function caffemmo_telegram_stats_record_activity($visitId, $sessionKey, $visitorKey, $page, $visitedAt)
{
    global $CMSNT;
    $latest = $CMSNT->get_row_safe(
        'SELECT `id`, `page_key`, `entered_at`, `last_seen_at` FROM `site_visit_activity` WHERE `visit_id` = ? ORDER BY `id` DESC LIMIT 1',
        [(int) $visitId]
    );
    if ($latest && (string) $latest['page_key'] === (string) $page['key']) {
        $duration = caffemmo_telegram_stats_seconds_between($latest['entered_at'], $visitedAt);
        $CMSNT->update('site_visit_activity', [
            'last_seen_at' => $visitedAt,
            'duration_seconds' => $duration
        ], ' `id` = ? ', [(int) $latest['id']]);
        return;
    }
    if ($latest) {
        $duration = caffemmo_telegram_stats_seconds_between($latest['entered_at'], $latest['last_seen_at']);
        $CMSNT->update('site_visit_activity', ['duration_seconds' => $duration], ' `id` = ? ', [(int) $latest['id']]);
    }
    $CMSNT->insert('site_visit_activity', [
        'visit_id' => (int) $visitId,
        'session_key' => $sessionKey,
        'visitor_key' => $visitorKey,
        'page_key' => $page['key'],
        'page_label' => $page['label'],
        'entered_at' => $visitedAt,
        'last_seen_at' => $visitedAt,
        'duration_seconds' => 0
    ]);
}

function caffemmo_telegram_stats_visitor_type($visitorKey, $fallback = null)
{
    global $CMSNT;
    if ($fallback !== null) {
        return $fallback === 'new' ? 'new' : 'returning';
    }
    $known = $CMSNT->get_row_safe(
        'SELECT `id` FROM `site_visit_history` WHERE `visitor_key` = ? LIMIT 1',
        [$visitorKey]
    );
    if (!$known) {
        $known = $CMSNT->get_row_safe(
            'SELECT `visitor_key` FROM `site_visit_daily` WHERE `visitor_key` = ? LIMIT 1',
            [$visitorKey]
        );
    }
    return $known ? 'returning' : 'new';
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

    $snapshot = caffemmo_telegram_stats_presence_snapshot();
    $today = $snapshot['today'];
    $yesterday = $snapshot['yesterday'];
    $typeLabel = ($session['visitor_type'] ?? 'returning') === 'new' ? 'Khách mới' : 'Khách cũ';
    $visitorCode = caffemmo_telegram_stats_visitor_code($session['visitor_key'] ?? $session['session_key'] ?? '');
    $message = $event === 'enter'
        ? "🌐 <b>KHÁCH VỪA VÀO WEBSITE</b>\n"
            . "👤 Mã khách: <code>" . caffemmo_telegram_stats_html($visitorCode) . "</code> (ẩn danh)\n"
            . "🏷 Loại: <b>" . caffemmo_telegram_stats_html($typeLabel) . "</b>\n"
            . "📍 Đang xem: <b>" . caffemmo_telegram_stats_html($session['last_page_label'] ?? 'Trang chủ') . "</b>\n"
            . "🟢 Đang online: <b>" . format_cash($snapshot['online']) . "</b>\n"
            . "↗ Lượt vào hôm nay: <b>" . format_cash($today['entries']) . "</b> (hôm qua " . format_cash($yesterday['entries']) . ")\n"
            . "👥 Khách duy nhất hôm nay: <b>" . format_cash($today['unique_entries']) . "</b>"
        : "🚪 <b>KHÁCH RỜI WEBSITE</b>\n"
            . "👤 Mã khách: <code>" . caffemmo_telegram_stats_html($visitorCode) . "</code> (ẩn danh)\n"
            . "🏷 Loại: <b>" . caffemmo_telegram_stats_html($typeLabel) . "</b>\n"
            . "⏱ Ở lại: <b>" . caffemmo_telegram_stats_duration($session['duration_seconds'] ?? 0) . "</b>\n"
            . "📍 Mục cuối: <b>" . caffemmo_telegram_stats_html($session['last_page_label'] ?? 'Không rõ') . "</b>\n"
            . "📚 Lịch sử: " . caffemmo_telegram_stats_html(implode(' → ', caffemmo_telegram_stats_activity_log($session['visit_id'] ?? 0))) . "\n"
            . "ℹ️ Lý do ghi nhận: Không nhận heartbeat quá 5 phút; không thể kết luận chính xác khách đóng tab hay mất kết nối.\n"
            . "🟢 Đang online: <b>" . format_cash($snapshot['online']) . "</b>\n"
            . "↘ Lượt rời hôm nay: <b>" . format_cash($today['leaves']) . "</b> (hôm qua " . format_cash($yesterday['leaves']) . ")";
    $queue = new TelegramQueue();
    return $queue->queueMessage($message, null, null, 2, [
        'type' => 'visitor_presence',
        'event' => $event,
        'session_key' => substr((string) ($session['session_key'] ?? ''), 0, 12),
        'visit_id' => (int) ($session['visit_id'] ?? 0)
    ]);
}

function caffemmo_telegram_stats_capture_session($pageKey = '', $visitorType = null, $notifyEnter = true)
{
    global $CMSNT;
    if (!caffemmo_telegram_stats_ensure_presence_table()) {
        return false;
    }
    $sessionId = isset($_COOKIE['caffemmo_visit_session_id']) ? trim((string) $_COOKIE['caffemmo_visit_session_id']) : '';
    if (!preg_match('/^[a-f0-9]{64}$/', $sessionId)) {
        return false;
    }
    $visitorId = caffemmo_telegram_stats_cookie_id('caffemmo_visitor_id', time() + 31536000);
    $visitorKey = hash('sha256', $visitorId);
    $page = caffemmo_telegram_stats_page_context($pageKey);
    $visitedAt = date('Y-m-d H:i:s');
    $liveSession = $CMSNT->get_row_safe(
        'SELECT * FROM `site_live_sessions` WHERE `session_key` = ? LIMIT 1',
        [$sessionId]
    );

    if ($liveSession && empty($liveSession['ended_at']) && (int) ($liveSession['visit_id'] ?? 0) > 0) {
        $visitId = (int) $liveSession['visit_id'];
        $CMSNT->update('site_live_sessions', [
            'last_seen_at' => $visitedAt,
            'last_page_key' => $page['key'],
            'last_page_label' => $page['label']
        ], ' `session_key` = ? ', [$sessionId]);
        $CMSNT->update('site_visit_history', [
            'last_seen_at' => $visitedAt,
            'last_page_key' => $page['key'],
            'last_page_label' => $page['label']
        ], ' `id` = ? AND `ended_at` IS NULL ', [$visitId]);
        caffemmo_telegram_stats_record_activity($visitId, $sessionId, $visitorKey, $page, $visitedAt);
        return $visitId;
    }

    $type = caffemmo_telegram_stats_visitor_type($visitorKey, $visitorType);
    $historyId = $CMSNT->insert('site_visit_history', [
        'session_key' => $sessionId,
        'visitor_key' => $visitorKey,
        'visitor_code' => caffemmo_telegram_stats_visitor_code($visitorKey),
        'visitor_type' => $type,
        'started_at' => $visitedAt,
        'last_seen_at' => $visitedAt,
        'ended_at' => null,
        'duration_seconds' => 0,
        'last_page_key' => $page['key'],
        'last_page_label' => $page['label'],
        'leave_reason' => null
    ]);
    if (!$historyId) {
        return false;
    }
    $liveData = [
        'visitor_key' => $visitorKey,
        'started_at' => $visitedAt,
        'last_seen_at' => $visitedAt,
        'ended_at' => null,
        'visit_id' => (int) $historyId,
        'visitor_type' => $type,
        'last_page_key' => $page['key'],
        'last_page_label' => $page['label']
    ];
    if ($liveSession) {
        $CMSNT->update('site_live_sessions', $liveData, ' `session_key` = ? ', [$sessionId]);
    } else {
        $liveData['session_key'] = $sessionId;
        $CMSNT->insert('site_live_sessions', $liveData);
    }
    caffemmo_telegram_stats_record_activity($historyId, $sessionId, $visitorKey, $page, $visitedAt);
    if ($notifyEnter) {
        caffemmo_telegram_stats_notify_presence('enter', [
            'session_key' => $sessionId,
            'visit_id' => (int) $historyId,
            'visitor_key' => $visitorKey,
            'visitor_type' => $type,
            'last_page_label' => $page['label']
        ]);
    }
    return (int) $historyId;
}

function caffemmo_telegram_stats_sweep_live_sessions()
{
    global $CMSNT;
    if (!caffemmo_telegram_stats_ensure_presence_table()) {
        return 0;
    }

    $cutoff = date('Y-m-d H:i:s', time() - 300);
    $endedAt = date('Y-m-d H:i:s');
    $rows = $CMSNT->get_list_safe(
        'SELECT * FROM `site_live_sessions` WHERE `ended_at` IS NULL AND `last_seen_at` < ?',
        [$cutoff]
    ) ?: [];
    $closed = 0;
    foreach ($rows as $row) {
        $visitId = (int) ($row['visit_id'] ?? 0);
        $duration = caffemmo_telegram_stats_seconds_between($row['started_at'] ?? $endedAt, $row['last_seen_at'] ?? $endedAt);
        $updated = $CMSNT->update(
            'site_live_sessions',
            ['ended_at' => $endedAt],
            ' `session_key` = ? AND `ended_at` IS NULL ',
            [$row['session_key']]
        );
        if ($updated) {
            if ($visitId > 0) {
                $latestActivity = $CMSNT->get_row_safe(
                    'SELECT `id`, `entered_at` FROM `site_visit_activity` WHERE `visit_id` = ? ORDER BY `id` DESC LIMIT 1',
                    [$visitId]
                );
                if ($latestActivity) {
                    $CMSNT->update('site_visit_activity', [
                        'last_seen_at' => $row['last_seen_at'],
                        'duration_seconds' => caffemmo_telegram_stats_seconds_between($latestActivity['entered_at'], $row['last_seen_at'])
                    ], ' `id` = ? ', [(int) $latestActivity['id']]);
                }
                $CMSNT->update('site_visit_history', [
                    'last_seen_at' => $row['last_seen_at'],
                    'ended_at' => $endedAt,
                    'duration_seconds' => $duration,
                    'last_page_key' => $row['last_page_key'] ?? null,
                    'last_page_label' => $row['last_page_label'] ?? null,
                    'leave_reason' => 'Không nhận heartbeat quá 5 phút'
                ], ' `id` = ? AND `ended_at` IS NULL ', [$visitId]);
            }
            $closed++;
            caffemmo_telegram_stats_notify_presence('leave', [
                'session_key' => $row['session_key'],
                'visit_id' => $visitId,
                'visitor_key' => $row['visitor_key'],
                'visitor_type' => $row['visitor_type'] ?? 'returning',
                'duration_seconds' => $duration,
                'last_page_label' => $row['last_page_label'] ?? 'Không rõ'
            ]);
        }
    }
    return $closed;
}

function caffemmo_telegram_stats_touch_session($pageKey = '')
{
    global $CMSNT;
    if (!preg_match('/^[a-f0-9]{64}$/', (string) ($_COOKIE['caffemmo_visit_session_id'] ?? ''))) {
        return false;
    }
    caffemmo_telegram_stats_sweep_live_sessions();
    return caffemmo_telegram_stats_capture_session($pageKey, null, true);
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

function caffemmo_telegram_stats_track_visit($pageKey = '')
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
    $wasKnown = (bool) $CMSNT->get_row_safe(
        'SELECT `visitor_key` FROM `site_visit_daily` WHERE `visitor_key` = ? LIMIT 1',
        [$visitorKey]
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
    caffemmo_telegram_stats_cookie_id('caffemmo_visit_session_id');
    caffemmo_telegram_stats_capture_session($pageKey, $wasKnown ? 'returning' : 'new', true);
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
        'estimated_profit' => 0,
        'known_revenue' => 0,
        'known_profit' => 0,
        'unknown_cost_revenue' => 0,
        'breakdown' => []
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
    $unknownCostRevenue = max(0, (float) $unknownCostRevenue);
    $summary['orders'] += max(0, (int) $orders);
    $summary['quantity'] += max(0, (int) $quantity);
    $summary['revenue'] += (float) $revenue;
    $summary['cost'] += (float) $cost;
    $summary['unknown_cost_revenue'] += $unknownCostRevenue;
    $summary['estimated_profit'] = $summary['revenue'] - $summary['cost'];
    $summary['known_revenue'] = $summary['revenue'] - $summary['unknown_cost_revenue'];
    $summary['known_profit'] = $summary['known_revenue'] - $summary['cost'];
    $summary['profit'] = $summary['estimated_profit'];
}

function caffemmo_telegram_stats_add_service(&$summary, $key, $label, $orders, $quantity, $revenue, $cost, $unknownCostRevenue = 0)
{
    caffemmo_telegram_stats_add($summary, $orders, $quantity, $revenue, $cost, $unknownCostRevenue);
    $unknownCostRevenue = max(0, (float) $unknownCostRevenue);
    $knownRevenue = (float) $revenue - $unknownCostRevenue;
    $summary['breakdown'][$key] = [
        'label' => $label,
        'orders' => max(0, (int) $orders),
        'quantity' => max(0, (int) $quantity),
        'revenue' => (float) $revenue,
        'cost' => (float) $cost,
        'unknown_cost_revenue' => $unknownCostRevenue,
        'known_profit' => $knownRevenue - (float) $cost,
        'estimated_profit' => (float) $revenue - (float) $cost
    ];
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
        caffemmo_telegram_stats_add_service($summary, 'product', 'Tai nguyen', $row['orders'] ?? 0, $row['quantity'] ?? 0, $row['revenue'] ?? 0, $row['cost'] ?? 0);
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
            caffemmo_telegram_stats_add_service(
                $summary,
                'proxy',
                'Proxy',
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
        caffemmo_telegram_stats_add_service($summary, 'up_tich_xanh', 'Up Tich Xanh', $row['orders'] ?? 0, $row['quantity'] ?? 0, $row['revenue'] ?? 0, $row['cost'] ?? 0);
    }

    if (caffemmo_telegram_stats_table_exists('netflix_orders')) {
        $row = $CMSNT->get_row_safe(
            'SELECT COUNT(no.`id`) AS `orders`, COUNT(no.`id`) AS `quantity`, COALESCE(SUM(no.`charged_amount`), 0) AS `revenue` FROM `netflix_orders` no LEFT JOIN `users` stats_user ON stats_user.`id` = no.`user_id` WHERE no.`created_at` >= ? AND no.`created_at` < ? AND (stats_user.`id` IS NULL OR LOWER(stats_user.`username`) <> ?)',
            [$start, $end, $excludedUsername]
        );
        caffemmo_telegram_stats_add_service($summary, 'netflix', 'Netflix', $row['orders'] ?? 0, $row['quantity'] ?? 0, $row['revenue'] ?? 0, 0, $row['revenue'] ?? 0);
    }

    if (caffemmo_telegram_stats_table_exists('locket_gold_orders')) {
        $row = $CMSNT->get_row_safe(
            'SELECT COUNT(lo.`id`) AS `orders`, COALESCE(SUM(lo.`account_count`), 0) AS `quantity`, COALESCE(SUM(lo.`charged_amount`), 0) AS `revenue` FROM `locket_gold_orders` lo LEFT JOIN `users` stats_user ON stats_user.`id` = lo.`user_id` WHERE lo.`status` = ? AND lo.`refund_amount` = 0 AND lo.`created_at` >= ? AND lo.`created_at` < ? AND (stats_user.`id` IS NULL OR LOWER(stats_user.`username`) <> ?)',
            ['completed', $start, $end, $excludedUsername]
        );
        caffemmo_telegram_stats_add_service($summary, 'locket_gold', 'Locket Gold', $row['orders'] ?? 0, $row['quantity'] ?? 0, $row['revenue'] ?? 0, 0, $row['revenue'] ?? 0);
    }

    if (caffemmo_telegram_stats_table_exists('dongtien')) {
        $row = $CMSNT->get_row_safe(
            "SELECT COUNT(dt.`id`) AS `orders`, COALESCE(SUM(dt.`sotienthaydoi`), 0) AS `revenue` FROM `dongtien` dt LEFT JOIN `users` stats_user ON stats_user.`id` = dt.`user_id` WHERE dt.`noidung` LIKE ? AND dt.`thoigian` >= ? AND dt.`thoigian` < ? AND (stats_user.`id` IS NULL OR LOWER(stats_user.`username`) <> ?)",
            ['Gia hạn proxy (%', $start, $end, $excludedUsername]
        );
        caffemmo_telegram_stats_add_service($summary, 'proxy_renewal', 'Gia han proxy', $row['orders'] ?? 0, $row['orders'] ?? 0, $row['revenue'] ?? 0, 0, $row['revenue'] ?? 0);
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
    $presenceReady = caffemmo_telegram_stats_ensure_presence_table();
    [$todayStart, $todayEnd] = caffemmo_telegram_stats_period_bounds('today');
    [$weekStart, $weekEnd] = caffemmo_telegram_stats_period_bounds('week');
    [$monthStart, $monthEnd] = caffemmo_telegram_stats_period_bounds('month');
    [$previousMonthStart, $previousMonthEnd] = caffemmo_telegram_stats_period_bounds('previous_month');

    $visitors = ['unique_today' => 0, 'visits_today' => 0];
    $online = ['online' => 0];
    $todayTraffic = ['entries' => 0, 'unique_entries' => 0, 'leaves' => 0, 'unique_leaves' => 0];
    $yesterdayTraffic = $todayTraffic;
    $topPagesToday = [];
    $recentVisits = [];
    if ($visitsReady) {
        $todayDate = date('Y-m-d');
        caffemmo_telegram_stats_sweep_live_sessions();
        $visitors = $CMSNT->get_row_safe(
            'SELECT COUNT(*) AS `unique_today`, COALESCE(SUM(`visit_count`), 0) AS `visits_today` FROM `site_visit_daily` WHERE `visit_date` = ?',
            [$todayDate]
        ) ?: $visitors;
        if ($presenceReady) {
            $todayTraffic = caffemmo_telegram_stats_visit_counts($todayDate);
            $yesterdayTraffic = caffemmo_telegram_stats_visit_counts(date('Y-m-d', strtotime('-1 day')));
            $topPagesToday = caffemmo_telegram_stats_top_pages($todayDate);
            $recentVisits = caffemmo_telegram_stats_recent_visits();
            $online = ['online' => caffemmo_telegram_stats_live_count()];
        }
    }

    return [
        'online' => (int) ($online['online'] ?? 0),
        'unique_today' => (int) ($todayTraffic['unique_entries'] ?? $visitors['unique_today'] ?? 0),
        'visits_today' => (int) ($todayTraffic['entries'] ?? $visitors['visits_today'] ?? 0),
        'traffic_today' => $todayTraffic,
        'traffic_yesterday' => $yesterdayTraffic,
        'top_pages_today' => $topPagesToday,
        'recent_visits' => $recentVisits,
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
