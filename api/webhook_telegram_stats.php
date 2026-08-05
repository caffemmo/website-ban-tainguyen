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

function caffemmo_telegram_stats_message($stats)
{
    $today = $stats['today'];
    $week = $stats['week'];
    $month = $stats['month'];
    $previousMonth = $stats['previous_month'];
    [$profitChange, $profitPercent] = caffemmo_telegram_stats_change($month['profit'], $previousMonth['profit']);
    [$revenueChange, $revenuePercent] = caffemmo_telegram_stats_change($month['revenue'], $previousMonth['revenue']);
    $profitTrend = $profitChange > 0 ? '🟢 Tăng' : ($profitChange < 0 ? '🔴 Giảm' : '⚪ Không đổi');
    $revenueTrend = $revenueChange > 0 ? '🟢 Tăng' : ($revenueChange < 0 ? '🔴 Giảm' : '⚪ Không đổi');
    $profitPercentText = $profitPercent === null ? 'N/A' : ($profitPercent >= 0 ? '+' : '') . $profitPercent . '%';
    $revenuePercentText = $revenuePercent === null ? 'N/A' : ($revenuePercent >= 0 ? '+' : '') . $revenuePercent . '%';

    $message = "📊 *THỐNG KÊ WEBSITE*\n";
    $message .= "------------------------------------\n";
    $message .= "🟢 *Đang online:* `" . format_cash($stats['online']) . "` khách\n";
    $message .= "👥 *Khách duy nhất hôm nay:* `" . format_cash($stats['unique_today']) . "`\n";
    $message .= "🔁 *Tổng lượt vào hôm nay:* `" . format_cash($stats['visits_today']) . "`\n";
    $message .= "------------------------------------\n";
    $message .= "📅 *Hôm nay*\n";
    $message .= "🛒 Đơn: `" . format_cash($today['orders']) . "` | Doanh thu: *" . caffemmo_telegram_stats_money($today['revenue']) . "*\n";
    $message .= "📈 Lợi nhuận tạm tính: *" . caffemmo_telegram_stats_money($today['profit']) . "*\n";
    $message .= "📅 *Tuần này*\n";
    $message .= "🛒 Đơn: `" . format_cash($week['orders']) . "` | Doanh thu: *" . caffemmo_telegram_stats_money($week['revenue']) . "*\n";
    $message .= "📈 Lợi nhuận tạm tính: *" . caffemmo_telegram_stats_money($week['profit']) . "*\n";
    $message .= "📅 *Tháng này*\n";
    $message .= "🛒 Đơn: `" . format_cash($month['orders']) . "` | Doanh thu: *" . caffemmo_telegram_stats_money($month['revenue']) . "*\n";
    $message .= "📈 Lợi nhuận tạm tính: *" . caffemmo_telegram_stats_money($month['profit']) . "*\n";
    $message .= "⚠️ Doanh thu chưa có giá vốn: *" . caffemmo_telegram_stats_money($month['unknown_cost_revenue']) . "*\n";
    $message .= "------------------------------------\n";
    $message .= "📊 *So với tháng trước*\n";
    $message .= "💰 Doanh thu: {$revenueTrend} `{$revenuePercentText}` (" . caffemmo_telegram_stats_money($revenueChange) . ")\n";
    $message .= "📈 Lợi nhuận: {$profitTrend} `{$profitPercentText}` (" . caffemmo_telegram_stats_money($profitChange) . ")\n";
    $message .= "⚠️ Lợi nhuận là số tạm tính theo giá vốn đã lưu; Netflix, Locket Gold và gia hạn proxy chưa có giá vốn riêng trong database.";
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
if (!preg_match('/^\/(stats|help)(?:@[a-zA-Z0-9_]+)?(?:\s|$)/i', $text, $matches)) {
    http_response_code(200);
    exit;
}

$chatId = (string) ($message['chat']['id'] ?? '');
if (strtolower($matches[1]) === 'help') {
    caffemmo_telegram_stats_send($chatId, "🤖 *Bot thống kê Caffemmo*\n\nGửi `/stats` để xem online, lượt truy cập, doanh thu và lợi nhuận website.");
} else {
    caffemmo_telegram_stats_send($chatId, caffemmo_telegram_stats_message(caffemmo_telegram_stats_dashboard()));
}

http_response_code(200);
echo 'ok';
