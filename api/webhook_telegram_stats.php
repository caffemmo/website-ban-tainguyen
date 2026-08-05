<?php

define('IN_SITE', true);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../libs/db.php';
require_once __DIR__ . '/../libs/lang.php';
require_once __DIR__ . '/../libs/helper.php';
require_once __DIR__ . '/../libs/telegram-statistics.php';

$CMSNT = new DB();

function caffemmo_telegram_stats_header_value($name)
{
    $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
    if (isset($_SERVER[$serverKey])) {
        return trim((string) $_SERVER[$serverKey]);
    }

    if (function_exists('getallheaders')) {
        foreach (getallheaders() as $headerName => $headerValue) {
            if (strtolower((string) $headerName) === strtolower($name)) {
                return trim((string) $headerValue);
            }
        }
    }
    return '';
}

function caffemmo_telegram_stats_api_call($method, $fields = [])
{
    global $CMSNT;
    $token = trim((string) $CMSNT->site('telegram_token'));
    $baseUrl = trim((string) $CMSNT->site('telegram_url'));
    if ($token === '') {
        return ['ok' => false, 'description' => 'Telegram bot token is not configured.'];
    }
    if ($baseUrl === '') {
        $baseUrl = 'https://api.telegram.org/';
    }

    $ch = curl_init(rtrim($baseUrl, '/') . '/bot' . $token . '/' . $method);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error !== '') {
        return ['ok' => false, 'description' => $error];
    }
    $decoded = json_decode((string) $response, true);
    return is_array($decoded) ? $decoded : ['ok' => false, 'description' => 'Invalid Telegram API response.'];
}

function caffemmo_telegram_stats_send($chatId, $text)
{
    return caffemmo_telegram_stats_api_call('sendMessage', [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'Markdown'
    ]);
}

function caffemmo_telegram_stats_authorized($message)
{
    global $CMSNT;
    $chatId = (string) ($message['chat']['id'] ?? '');
    $configuredChatId = trim((string) $CMSNT->site('telegram_chat_id'));
    if ($configuredChatId !== '' && hash_equals($configuredChatId, $chatId)) {
        return true;
    }

    $username = strtolower(trim((string) ($message['from']['username'] ?? '')));
    $allowedUsernames = array_filter(array_map('strtolower', array_map('trim', explode(',', (string) $CMSNT->site('telegram_assistant_list_username')))));
    return $username !== '' && in_array($username, $allowedUsernames, true);
}

function caffemmo_telegram_stats_compare_text($label, $current, $previous)
{
    [$change, $percent] = caffemmo_telegram_stats_change($current, $previous);
    $trend = $change > 0 ? 'Tăng' : ($change < 0 ? 'Giảm' : 'Không đổi');
    $percentText = $percent === null ? 'N/A' : ($percent >= 0 ? '+' : '') . $percent . '%';
    return $label . ': ' . $trend . ' ' . $percentText . ' (' . caffemmo_telegram_stats_money($change) . ')';
}

function caffemmo_telegram_stats_period_short($label, $period)
{
    return $label . ': ' . format_cash($period['orders']) . ' don | DT ' . caffemmo_telegram_stats_money($period['revenue'])
        . ' | Von da ghi nhan ' . caffemmo_telegram_stats_money($period['cost'])
        . ' | LN sau von ' . caffemmo_telegram_stats_money($period['known_profit']);
}

function caffemmo_telegram_stats_revenue_message($stats)
{
    $month = $stats['month'];
    $previousMonth = $stats['previous_month'];
    $message = "💰 *DOANH THU VA LOI NHUAN*\n";
    $message .= "\n" . caffemmo_telegram_stats_period_short('Hom nay', $stats['today']);
    $message .= "\n" . caffemmo_telegram_stats_period_short('Tuan nay', $stats['week']);
    $message .= "\n" . caffemmo_telegram_stats_period_short('Thang nay', $month);
    $message .= "\n\n*So voi thang truoc*\n";
    $message .= caffemmo_telegram_stats_compare_text('Doanh thu', $month['revenue'], $previousMonth['revenue']);
    $message .= "\n" . caffemmo_telegram_stats_compare_text('Loi nhuan sau von da ghi nhan', $month['known_profit'], $previousMonth['known_profit']);
    $message .= "\n\n*Chi tiet thang nay*\n";
    foreach ($month['breakdown'] as $service) {
        $message .= "\n- " . $service['label'] . ': ' . format_cash($service['orders']) . ' don | DT ' . caffemmo_telegram_stats_money($service['revenue']);
        if ((float) $service['unknown_cost_revenue'] > 0) {
            $message .= ' | Von: CHUA CO | LN: CHUA XAC DINH';
        } else {
            $message .= ' | Von ' . caffemmo_telegram_stats_money($service['cost']) . ' | LN ' . caffemmo_telegram_stats_money($service['known_profit']);
        }
    }
    $message .= "\n\n*Pham vi tinh:* Tai nguyen khong hoan/rac, proxy chua hoan, Up Tich Xanh thanh cong, Netflix, Locket Gold hoan tat va gia han proxy. Tai khoan chu shop `xuabthabgz221zgg` da loai tru.\n";
    $message .= "Von da ghi nhan chua bao gom gia von rieng cua Netflix, Locket Gold va gia han proxy.";
    return $message;
}

function caffemmo_telegram_stats_message($stats)
{
    $traffic = $stats['traffic_today'];
    $yesterday = $stats['traffic_yesterday'];
    $message = "📊 *THONG KE WEBSITE*\n";
    $message .= "\n🟢 Online: *" . format_cash($stats['online']) . "*\n";
    $message .= "↗ Khach vao hom nay: *" . format_cash($traffic['entries']) . "* (hom qua " . format_cash($yesterday['entries']) . ")\n";
    $message .= "👥 Khach duy nhat: *" . format_cash($traffic['unique_entries']) . "*\n";
    $message .= "↘ Khach roi hom nay: *" . format_cash($traffic['leaves']) . "* (hom qua " . format_cash($yesterday['leaves']) . ")\n";
    $message .= "\n*Top muc duoc xem hom nay*\n";
    if (empty($stats['top_pages_today'])) {
        $message .= "Chua co du lieu.\n";
    } else {
        foreach ($stats['top_pages_today'] as $index => $page) {
            $message .= ($index + 1) . '. ' . $page['page_label'] . ': ' . format_cash($page['unique_visitors']) . ' khach / ' . format_cash($page['visits']) . ' luot\n';
        }
    }
    $message .= "\n*Phien gan day*\n";
    foreach (array_slice($stats['recent_visits'], 0, 5) as $visit) {
        $type = ($visit['visitor_type'] ?? '') === 'new' ? 'moi' : 'cu';
        $status = ($visit['status'] ?? '') === 'online' ? 'online' : 'da roi';
        $activity = implode(' > ', array_slice($visit['activity'] ?? [], 0, 3));
        $message .= '- `' . $visit['visitor_code'] . '` ' . $type . ' | ' . $status . ' | ' . caffemmo_telegram_stats_duration($visit['duration_seconds']) . ' | ' . ($activity ?: ($visit['last_page_label'] ?: 'Khong ro')) . "\n";
    }
    $message .= "\nDoanh thu va loi nhuan chi xem khi gui /revenue.";
    return $message;
}

if ($CMSNT->site('telegram_status') != 1 || trim((string) $CMSNT->site('telegram_token')) === '') {
    http_response_code(200);
    exit;
}

$secret = trim((string) $CMSNT->site('telegram_assistant_secret_token'));
if ($secret !== '' && !hash_equals($secret, caffemmo_telegram_stats_header_value('X-Telegram-Bot-Api-Secret-Token'))) {
    http_response_code(403);
    exit('Invalid webhook secret');
}

$update = json_decode((string) file_get_contents('php://input'), true);
$message = is_array($update) ? ($update['message'] ?? null) : null;
if (!is_array($message) || !caffemmo_telegram_stats_authorized($message)) {
    http_response_code(200);
    exit;
}

$text = trim((string) ($message['text'] ?? ''));
if (!preg_match('/^\/(stats|revenue|help)(?:@[a-zA-Z0-9_]+)?(?:\s|$)/i', $text, $matches)) {
    http_response_code(200);
    exit;
}

$chatId = (string) ($message['chat']['id'] ?? '');
if (strtolower($matches[1]) === 'help') {
    caffemmo_telegram_stats_send($chatId, "🤖 *Bot thong ke Caffemmo*\n\n`/stats` - online, khach vao/roi, muc duoc xem va phien gan day.\n`/revenue` - doanh thu, von da ghi nhan, loi nhuan va so sanh thang.");
} elseif (strtolower($matches[1]) === 'revenue') {
    caffemmo_telegram_stats_send($chatId, caffemmo_telegram_stats_revenue_message(caffemmo_telegram_stats_dashboard()));
} else {
    caffemmo_telegram_stats_send($chatId, caffemmo_telegram_stats_message(caffemmo_telegram_stats_dashboard()));
}

http_response_code(200);
echo 'ok';
