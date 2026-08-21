<?php

if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

/* Server-side adapter for the Hacklike17 SMM V3 API. */
function social_buff_env($key, $fallback = '')
{
    if (isset($_ENV[$key]) && trim((string) $_ENV[$key]) !== '') {
        return trim((string) $_ENV[$key]);
    }

    $value = getenv($key);
    return $value !== false && trim((string) $value) !== '' ? trim((string) $value) : $fallback;
}

function social_buff_config()
{
    $baseUrl = social_buff_env('HACKLIKE17_API_URL', 'https://hacklike17.com/api/v3');
    $markup = (float) social_buff_env('SOCIAL_BUFF_MARKUP_PERCENT', '0');
    $timeout = (int) social_buff_env('HACKLIKE17_TIMEOUT', '30');

    return [
        'api_key' => social_buff_env('HACKLIKE17_API_KEY'),
        'base_url' => rtrim($baseUrl, '/'),
        'markup_percent' => max(0, min(500, $markup)),
        'timeout' => max(10, min(60, $timeout))
    ];
}

function social_buff_setting($name, $fallback = '')
{
    global $CMSNT;
    static $settings = [];

    if (array_key_exists($name, $settings)) {
        return $settings[$name];
    }
    if (!isset($CMSNT) || !is_object($CMSNT)) {
        return $fallback;
    }

    $setting = $CMSNT->get_row_safe('SELECT `value` FROM `settings` WHERE `name` = ? LIMIT 1', [$name]);
    $settings[$name] = $setting && isset($setting['value']) ? trim((string) $setting['value']) : $fallback;
    return $settings[$name];
}

function social_buff_maintenance_enabled()
{
    return social_buff_setting('social_buff_maintenance', '0') === '1';
}

function social_buff_is_admin_user($user)
{
    return is_array($user) && (int) ($user['admin'] ?? 0) !== 0;
}

function social_buff_can_place_order($user)
{
    return !social_buff_maintenance_enabled() || social_buff_is_admin_user($user);
}

function social_buff_is_list($array)
{
    if (!is_array($array)) {
        return false;
    }
    $index = 0;
    foreach ($array as $key => $value) {
        if ($key !== $index) {
            return false;
        }
        $index++;
    }
    return true;
}

function social_buff_contains($haystack, $needle)
{
    return strpos((string) $haystack, (string) $needle) !== false;
}

function social_buff_is_configured()
{
    $config = social_buff_config();
    $scheme = strtolower((string) parse_url($config['base_url'], PHP_URL_SCHEME));

    return $config['api_key'] !== ''
        && filter_var($config['base_url'], FILTER_VALIDATE_URL) !== false
        && $scheme === 'https';
}

function social_buff_ensure_tables()
{
    global $CMSNT;
    if (!isset($CMSNT) || !is_object($CMSNT)) {
        return false;
    }

    $sql = "CREATE TABLE IF NOT EXISTS `social_buff_orders` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `order_code` VARCHAR(50) NOT NULL,
        `idempotency_key` VARCHAR(80) NOT NULL,
        `user_id` INT UNSIGNED NOT NULL,
        `service_id` VARCHAR(80) NOT NULL,
        `service_name` VARCHAR(255) NOT NULL,
        `platform` VARCHAR(50) NOT NULL DEFAULT 'Khac',
        `target_url` TEXT NOT NULL,
        `quantity` INT UNSIGNED NOT NULL,
        `provider_rate` DECIMAL(18,4) NOT NULL DEFAULT 0,
        `retail_rate` DECIMAL(18,4) NOT NULL DEFAULT 0,
        `charged_amount` DECIMAL(18,2) NOT NULL DEFAULT 0,
        `provider_order_id` VARCHAR(100) NULL DEFAULT NULL,
        `provider_status` VARCHAR(50) NOT NULL DEFAULT 'creating',
        `provider_start_count` VARCHAR(100) NULL DEFAULT NULL,
        `provider_remains` VARCHAR(100) NULL DEFAULT NULL,
        `provider_response` LONGTEXT NULL,
        `last_checked_at` DATETIME NULL DEFAULT NULL,
        `created_at` DATETIME NOT NULL,
        `updated_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `social_buff_orders_code` (`order_code`),
        UNIQUE KEY `social_buff_orders_request` (`idempotency_key`),
        KEY `social_buff_orders_user` (`user_id`, `created_at`),
        KEY `social_buff_orders_status` (`provider_status`, `last_checked_at`),
        KEY `social_buff_orders_provider` (`provider_order_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    return $CMSNT->query($sql) !== false;
}

function social_buff_provider_request($action, $payload = [])
{
    $config = social_buff_config();
    if (!social_buff_is_configured()) {
        return [
            'ok' => false,
            'known_failure' => true,
            'transport_error' => false,
            'http_code' => 0,
            'body' => null,
            'message' => 'Dịch vụ chưa được cấu hình trên máy chủ.'
        ];
    }
    if (!function_exists('curl_init')) {
        return [
            'ok' => false,
            'known_failure' => true,
            'transport_error' => false,
            'http_code' => 0,
            'body' => null,
            'message' => 'May chu chua bat PHP cURL.'
        ];
    }

    $request = array_merge([
        'key' => $config['api_key'],
        'action' => $action
    ], is_array($payload) ? $payload : []);

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $config['base_url'],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($request, '', '&', PHP_QUERY_RFC3986),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => $config['timeout'],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded',
            'User-Agent: Caffemmo-Social-Buff/1.0'
        ]
    ]);

    $raw = curl_exec($curl);
    $curlError = curl_error($curl);
    $curlErrno = curl_errno($curl);
    $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($raw === false || $curlError !== '') {
        error_log('[SOCIAL_BUFF] provider transport error action=' . $action . ' http=' . $httpCode . ' errno=' . $curlErrno);
        return [
            'ok' => false,
            'known_failure' => false,
            'transport_error' => true,
            'http_code' => $httpCode,
            'body' => null,
            'message' => 'Không thể xác nhận kết quả từ nhà cung cấp.'
        ];
    }

    $body = json_decode($raw, true);
    if (!is_array($body)) {
        return [
            'ok' => false,
            'known_failure' => $httpCode >= 400 && $httpCode < 500,
            'transport_error' => false,
            'http_code' => $httpCode,
            'body' => null,
            'message' => 'Nhà cung cấp trả về dữ liệu không hợp lệ.'
        ];
    }

    $accepted = $httpCode >= 200 && $httpCode < 300 && social_buff_provider_accepted($body);
    return [
        'ok' => $accepted,
        'known_failure' => !$accepted,
        'transport_error' => false,
        'http_code' => $httpCode,
        'body' => $body,
        'message' => social_buff_provider_message($body)
    ];
}

function social_buff_provider_accepted($body)
{
    if (!is_array($body)) {
        return false;
    }
    if (social_buff_is_list($body)) {
        return true;
    }

    $candidates = [$body];
    if (isset($body['data']) && is_array($body['data']) && !social_buff_is_list($body['data'])) {
        $candidates[] = $body['data'];
    }

    foreach ($candidates as $candidate) {
        if (array_key_exists('success', $candidate) && filter_var($candidate['success'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === false) {
            return false;
        }
        $status = strtolower(trim((string) ($candidate['status'] ?? '')));
        if (in_array($status, ['error', 'failed', 'fail', 'canceled', 'cancelled'], true)) {
            return false;
        }
        $code = isset($candidate['code']) ? (string) $candidate['code'] : '';
        if ($code !== '' && !in_array($code, ['0', '1', '200', '201'], true)) {
            return false;
        }
    }

    return true;
}

function social_buff_provider_message($body, $fallback = '')
{
    if (!is_array($body)) {
        return $fallback;
    }
    foreach ([$body, isset($body['data']) && is_array($body['data']) ? $body['data'] : []] as $candidate) {
        foreach (['message', 'msg', 'error', 'description'] as $key) {
            if (isset($candidate[$key]) && is_scalar($candidate[$key]) && trim((string) $candidate[$key]) !== '') {
                $message = trim((string) $candidate[$key]);
                return function_exists('mb_substr') ? mb_substr($message, 0, 300) : substr($message, 0, 300);
            }
        }
    }
    return $fallback;
}

function social_buff_service_list($response)
{
    if (!is_array($response) || empty($response['ok']) || !isset($response['body']) || !is_array($response['body'])) {
        return [];
    }

    $body = $response['body'];
    $items = [];
    if (social_buff_is_list($body)) {
        $items = $body;
    } elseif (isset($body['data']) && is_array($body['data'])) {
        $items = $body['data'];
    } elseif (isset($body['services']) && is_array($body['services'])) {
        $items = $body['services'];
    }

    $services = [];
    foreach ($items as $item) {
        $normalized = social_buff_normalize_service($item);
        if ($normalized !== null) {
            $services[] = $normalized;
        }
    }

    usort($services, function ($left, $right) {
        return strnatcasecmp($left['name'], $right['name']);
    });
    return $services;
}

function social_buff_normalize_service($item)
{
    if (!is_array($item)) {
        return null;
    }
    $serviceId = trim((string) ($item['service'] ?? $item['id'] ?? ''));
    $name = trim((string) ($item['name'] ?? $item['service_name'] ?? ''));
    $name = trim((string) preg_replace('/\b(?:hacklike17|hacklike)\b/i', '', $name));
    $category = trim((string) ($item['category'] ?? $item['platform'] ?? 'Khac'));
    $min = (int) ($item['min'] ?? $item['minimum'] ?? 0);
    $max = (int) ($item['max'] ?? $item['maximum'] ?? 0);
    $rate = (float) ($item['rate'] ?? $item['price'] ?? 0);

    if ($serviceId === '' || $name === '' || !preg_match('/^[A-Za-z0-9._:-]{1,80}$/', $serviceId) || $min < 1 || $max < $min || $rate < 0) {
        return null;
    }

    $config = social_buff_config();
    $retailRate = round($rate * (1 + ($config['markup_percent'] / 100)), 4);
    $serviceText = strtolower($name . ' ' . $category . ' ' . (string) ($item['type'] ?? ''));
    $isVideo = social_buff_contains($serviceText, 'video') || social_buff_contains($serviceText, 'view') || social_buff_contains($serviceText, 'livestream') || social_buff_contains($serviceText, 'reel') || social_buff_contains($serviceText, 'story');

    return [
        'id' => $serviceId,
        'name' => $name,
        'category' => $category,
        'platform' => social_buff_detect_platform($serviceText),
        'description' => trim((string) ($item['description'] ?? $item['desc'] ?? '')),
        'min' => $min,
        'max' => $max,
        'provider_rate' => $rate,
        'rate' => $retailRate,
        'type' => trim((string) ($item['type'] ?? 'default')),
        'refill' => filter_var($item['refill'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'cancel' => filter_var($item['cancel'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'is_video' => $isVideo
    ];
}

function social_buff_detect_platform($serviceText)
{
    $platforms = [
        'Facebook' => ['facebook', ' fb '],
        'Instagram' => ['instagram', ' ig '],
        'TikTok' => ['tiktok'],
        'YouTube' => ['youtube', ' yt '],
        'Shopee' => ['shopee'],
        'X / Twitter' => ['twitter', ' x '],
        'Google' => ['google', 'map']
    ];

    foreach ($platforms as $platform => $needles) {
        foreach ($needles as $needle) {
            if (social_buff_contains($serviceText, $needle)) {
                return $platform;
            }
        }
    }
    return 'Khac';
}

function social_buff_find_service($serviceId, $services)
{
    foreach ($services as $service) {
        if (isset($service['id']) && hash_equals((string) $service['id'], (string) $serviceId)) {
            return $service;
        }
    }
    return null;
}

function social_buff_calculate_price($rate, $quantity)
{
    return (float) ceil(max(0, (float) $rate) * max(0, (int) $quantity) / 1000);
}

function social_buff_valid_target_url($url)
{
    if (!is_string($url) || strlen($url) > 2000 || filter_var($url, FILTER_VALIDATE_URL) === false) {
        return false;
    }
    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    $host = (string) parse_url($url, PHP_URL_HOST);
    return in_array($scheme, ['http', 'https'], true) && $host !== '';
}

function social_buff_new_order_code()
{
    try {
        return 'SMM' . date('Ymd') . strtoupper(bin2hex(random_bytes(5)));
    } catch (Exception $exception) {
        return 'SMM' . date('Ymd') . strtoupper(uniqid());
    }
}

function social_buff_snapshot($payload)
{
    $clean = social_buff_redact_payload($payload);
    return json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function social_buff_redact_payload($value)
{
    if (!is_array($value)) {
        return $value;
    }
    $clean = [];
    foreach ($value as $key => $item) {
        if (in_array(strtolower((string) $key), ['key', 'api_key', 'token', 'authorization', 'password'], true)) {
            $clean[$key] = '[redacted]';
        } else {
            $clean[$key] = social_buff_redact_payload($item);
        }
    }
    return $clean;
}

function social_buff_extract_provider_order_id($body)
{
    if (!is_array($body)) {
        return '';
    }
    $candidates = [$body];
    if (isset($body['data']) && is_array($body['data'])) {
        $candidates[] = $body['data'];
    }
    foreach ($candidates as $candidate) {
        foreach (['order', 'order_id', 'id'] as $key) {
            if (isset($candidate[$key]) && is_scalar($candidate[$key]) && trim((string) $candidate[$key]) !== '') {
                return trim((string) $candidate[$key]);
            }
        }
    }
    return '';
}

function social_buff_extract_order_meta($body)
{
    $data = [];
    if (is_array($body)) {
        $data = isset($body['data']) && is_array($body['data']) ? array_merge($body, $body['data']) : $body;
    }
    $rawStatus = trim((string) ($data['status'] ?? 'pending'));

    return [
        'status' => social_buff_normalize_order_status($rawStatus),
        'start_count' => isset($data['start_count']) && is_scalar($data['start_count']) ? (string) $data['start_count'] : null,
        'remains' => isset($data['remains']) && is_scalar($data['remains']) ? (string) $data['remains'] : null
    ];
}

function social_buff_normalize_order_status($status)
{
    $value = strtolower(trim((string) $status));
    $map = [
        'pending' => 'pending',
        'awaiting' => 'pending',
        'queued' => 'pending',
        'processing' => 'processing',
        'in progress' => 'processing',
        'in_progress' => 'processing',
        'progress' => 'processing',
        'completed' => 'completed',
        'complete' => 'completed',
        'success' => 'completed',
        'partial' => 'partial',
        'canceled' => 'canceled',
        'cancelled' => 'canceled',
        'refunded' => 'refunded',
        'failed' => 'failed',
        'error' => 'failed'
    ];
    return $map[$value] ?? 'pending';
}

function social_buff_status_meta($status)
{
    $rawStatus = strtolower(trim((string) $status));
    $status = in_array($rawStatus, ['creating', 'awaiting_confirmation', 'failed_refunded'], true)
        ? $rawStatus
        : social_buff_normalize_order_status($status);
    $map = [
        'pending' => ['label' => 'Chờ xử lý', 'class' => 'pending'],
        'processing' => ['label' => 'Đang xử lý', 'class' => 'processing'],
        'completed' => ['label' => 'Hoàn tất', 'class' => 'completed'],
        'partial' => ['label' => 'Hoàn một phần', 'class' => 'partial'],
        'canceled' => ['label' => 'Đã hủy', 'class' => 'canceled'],
        'refunded' => ['label' => 'Đã hoàn tiền', 'class' => 'canceled'],
        'failed' => ['label' => 'Không thành công', 'class' => 'canceled'],
        'creating' => ['label' => 'Đang tạo đơn', 'class' => 'processing'],
        'awaiting_confirmation' => ['label' => 'Cần xác minh', 'class' => 'pending'],
        'failed_refunded' => ['label' => 'Lỗi - đã hoàn tiền', 'class' => 'canceled']
    ];
    return $map[$status] ?? ['label' => 'Đang xử lý', 'class' => 'pending'];
}

function social_buff_debit($userId, $amount, $reason, $transId)
{
    global $CMSNT;
    if (!isset($CMSNT) || !is_object($CMSNT) || $amount < 0) {
        return false;
    }

    $CMSNT->query('START TRANSACTION');
    try {
        $user = $CMSNT->get_row_safe('SELECT `money` FROM `users` WHERE `id` = ? FOR UPDATE', [(int) $userId]);
        $balance = $user ? (float) ($user['money'] ?? 0) : -1;
        if ($balance < $amount) {
            $CMSNT->query('ROLLBACK');
            return false;
        }

        $logged = $CMSNT->insert('dongtien', [
            'sotientruoc' => $balance,
            'sotienthaydoi' => $amount,
            'sotiensau' => $balance - $amount,
            'thoigian' => gettime(),
            'noidung' => $reason,
            'user_id' => (int) $userId,
            'transid' => $transId
        ]);
        $updated = $logged && $CMSNT->query('UPDATE `users` SET `money` = `money` - ' . (float) $amount . ' WHERE `id` = ' . (int) $userId);
        if (!$updated) {
            $CMSNT->query('ROLLBACK');
            return false;
        }
        $CMSNT->query('COMMIT');
        return true;
    } catch (Exception $exception) {
        $CMSNT->query('ROLLBACK');
        return false;
    }
}

function social_buff_sync_order($order)
{
    global $CMSNT;
    if (!is_array($order) || empty($order['provider_order_id']) || !isset($CMSNT)) {
        return false;
    }

    $response = social_buff_provider_request('status', ['order' => (string) $order['provider_order_id']]);
    if (empty($response['ok'])) {
        return false;
    }

    $meta = social_buff_extract_order_meta($response['body']);
    return $CMSNT->update('social_buff_orders', [
        'provider_status' => $meta['status'],
        'provider_start_count' => $meta['start_count'],
        'provider_remains' => $meta['remains'],
        'provider_response' => social_buff_snapshot($response['body']),
        'last_checked_at' => gettime(),
        'updated_at' => gettime()
    ], ' `id` = ? ', [(int) $order['id']]);
}

function social_buff_sync_open_orders($limit = 40)
{
    global $CMSNT;
    if (!social_buff_is_configured() || !social_buff_ensure_tables() || !isset($CMSNT)) {
        return 0;
    }

    $limit = max(1, min(100, (int) $limit));
    $orders = $CMSNT->get_list_safe("SELECT * FROM `social_buff_orders` WHERE `provider_order_id` IS NOT NULL AND `provider_status` NOT IN ('completed', 'partial', 'canceled', 'refunded', 'failed', 'failed_refunded') ORDER BY COALESCE(`last_checked_at`, `created_at`) ASC LIMIT " . $limit);
    $synced = 0;
    foreach ($orders as $order) {
        if (social_buff_sync_order($order)) {
            $synced++;
        }
    }
    return $synced;
}
