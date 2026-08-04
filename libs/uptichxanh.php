<?php

if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

function uptichxanh_env($key, $fallback = '')
{
    if (isset($_ENV[$key]) && trim((string) $_ENV[$key]) !== '') {
        return trim((string) $_ENV[$key]);
    }
    $value = getenv($key);
    return $value !== false && trim((string) $value) !== '' ? trim((string) $value) : $fallback;
}

function uptichxanh_db_setting($name, $fallback = '')
{
    global $CMSNT;
    static $cache = [];

    if (array_key_exists($name, $cache)) {
        return $cache[$name];
    }
    if (!isset($CMSNT) || !is_object($CMSNT)) {
        return $fallback;
    }

    $row = $CMSNT->get_row_safe('SELECT `value` FROM `settings` WHERE `name` = ? LIMIT 1', [$name]);
    $cache[$name] = $row && isset($row['value']) ? trim((string) $row['value']) : $fallback;
    return $cache[$name];
}

function uptichxanh_config()
{
    $priceDefaults = [
        'get-link' => 24000,
        'up-fb' => 24000,
        'up-ig' => 24000
    ];
    $prices = [];

    foreach ($priceDefaults as $service => $default) {
        $settingName = 'uptichxanh_price_' . str_replace('-', '_', $service);
        $envName = 'UPTICHXANH_PRICE_' . strtoupper(str_replace('-', '_', $service));
        $value = uptichxanh_db_setting($settingName, '');
        if ($value === '') {
            $value = uptichxanh_env($envName, (string) $default);
        }
        $prices[$service] = max(0, (float) $value);
    }

    $apiKey = uptichxanh_db_setting('uptichxanh_api_key');
    $baseUrl = uptichxanh_db_setting('uptichxanh_api_base_url');
    $timeoutSetting = uptichxanh_db_setting('uptichxanh_timeout', '');

    if ($apiKey === '') {
        $apiKey = uptichxanh_env('UPTICHXANH_API_KEY');
    }
    if ($baseUrl === '') {
        $baseUrl = uptichxanh_env('UPTICHXANH_API_BASE_URL', 'https://viaxanh69.com/uptichxanh');
    }

    $timeout = $timeoutSetting !== ''
        ? (int) $timeoutSetting
        : (int) uptichxanh_env('UPTICHXANH_TIMEOUT', '20');

    return [
        'api_key' => $apiKey,
        'base_url' => rtrim($baseUrl, '/'),
        'timeout' => max(5, min(120, $timeout)),
        'prices' => $prices
    ];
}

function uptichxanh_is_configured()
{
    $config = uptichxanh_config();
    return $config['api_key'] !== ''
        && filter_var($config['base_url'], FILTER_VALIDATE_URL) !== false
        && strtolower((string) parse_url($config['base_url'], PHP_URL_SCHEME)) === 'https';
}

function uptichxanh_service_endpoint($service)
{
    $map = [
        'get-link' => 'getlink',
        'up-fb' => 'upfb',
        'up-ig' => 'upig'
    ];
    return isset($map[$service]) ? $map[$service] : false;
}

function uptichxanh_service_label($service)
{
    $map = [
        'get-link' => 'Get Link Facebook',
        'up-fb' => 'Up tích Facebook',
        'up-ig' => 'Up tích Instagram'
    ];
    return isset($map[$service]) ? $map[$service] : 'Dịch vụ Up Tích Xanh';
}

function uptichxanh_order_status($status)
{
    $status = strtolower(trim((string) $status));
    $map = [
        'success' => ['label' => 'Đã tiếp nhận', 'class' => 'success'],
        'completed' => ['label' => 'Hoàn tất', 'class' => 'success'],
        'processing' => ['label' => 'Đang xử lý', 'class' => 'pending'],
        'pending' => ['label' => 'Đang chờ xử lý', 'class' => 'pending'],
        'failed' => ['label' => 'Không thành công', 'class' => 'error'],
        'error' => ['label' => 'Không thành công', 'class' => 'error']
    ];

    return $map[$status] ?? ['label' => 'Đã tiếp nhận', 'class' => 'pending'];
}

function uptichxanh_request($method, $endpoint, $query = [], $payload = null)
{
    $config = uptichxanh_config();
    if ($config['api_key'] === '') {
        return ['ok' => false, 'http_code' => 0, 'body' => null, 'error' => 'Dịch vụ chưa được cấu hình trên máy chủ.'];
    }
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'http_code' => 0, 'body' => null, 'error' => 'Máy chủ chưa bật PHP cURL.'];
    }

    $url = $config['base_url'] . '/api/' . ltrim($endpoint, '/');
    if (!empty($query)) {
        $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'X-API-Key: ' . $config['api_key'],
        'User-Agent: Caffemmo-Social/1.0'
    ];
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => $config['timeout'],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => $headers
    ];

    $method = strtoupper($method);
    if ($method === 'POST') {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = json_encode($payload ?: [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    $curl = curl_init();
    curl_setopt_array($curl, $options);
    $raw = curl_exec($curl);
    $curlError = curl_error($curl);
    $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($raw === false || $curlError !== '') {
        return ['ok' => false, 'http_code' => $httpCode, 'body' => null, 'error' => 'Không thể kết nối đến dịch vụ.'];
    }

    $body = json_decode($raw, true);
    if (!is_array($body)) {
        return ['ok' => false, 'http_code' => $httpCode, 'body' => null, 'error' => 'Dịch vụ trả về dữ liệu không hợp lệ.'];
    }

    $providerSuccess = isset($body['status']) && strtolower((string) $body['status']) === 'success';
    return [
        'ok' => $httpCode >= 200 && $httpCode < 300 && $providerSuccess,
        'http_code' => $httpCode,
        'body' => $body,
        'error' => ''
    ];
}

function uptichxanh_api_call($method, $endpoint, $query = [], $payload = null)
{
    $response = uptichxanh_request($method, $endpoint, $query, $payload);
    $body = is_array($response['body']) ? $response['body'] : [];
    $message = isset($body['message']) && is_scalar($body['message']) ? trim((string) $body['message']) : '';

    if (!$response['ok']) {
        return [
            'success' => false,
            'status' => isset($body['status']) ? (string) $body['status'] : 'error',
            'message' => $message !== '' ? $message : $response['error'],
            'data' => isset($body['data']) && is_array($body['data']) ? $body['data'] : [],
            '_http_code' => $response['http_code']
        ];
    }

    $body['success'] = true;
    $body['_http_code'] = $response['http_code'];
    return $body;
}

function uptichxanh_error_text($response, $fallback = 'Không thể hoàn tất yêu cầu.')
{
    $httpCode = (int) ($response['_http_code'] ?? 0);
    $map = [
        400 => 'Thông tin gửi lên chưa hợp lệ.',
        401 => 'Cấu hình xác thực dịch vụ không hợp lệ.',
        403 => 'Tài khoản dịch vụ hiện không được phép sử dụng.',
        429 => 'Dịch vụ đang quá tải, vui lòng thử lại sau.',
        500 => 'Dịch vụ đang gặp lỗi nội bộ.'
    ];
    return isset($map[$httpCode]) ? $map[$httpCode] : $fallback;
}

function uptichxanh_service_price($service)
{
    $config = uptichxanh_config();
    return isset($config['prices'][$service]) ? (float) $config['prices'][$service] : 0;
}

function uptichxanh_ensure_tables()
{
    global $CMSNT;
    if (!isset($CMSNT)) {
        return false;
    }

    $sql = "CREATE TABLE IF NOT EXISTS `up_tich_xanh_orders` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` INT UNSIGNED NOT NULL,
        `service` VARCHAR(30) NOT NULL,
        `provider_uid` VARCHAR(100) NULL,
        `result_link` TEXT NULL,
        `provider_cost` DECIMAL(18,2) NOT NULL DEFAULT 0,
        `charged_amount` DECIMAL(18,2) NOT NULL DEFAULT 0,
        `provider_balance` DECIMAL(18,2) NULL,
        `provider_status` VARCHAR(30) NOT NULL DEFAULT 'success',
        `provider_response` LONGTEXT NULL,
        `created_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `up_tich_xanh_orders_user_id` (`user_id`),
        KEY `up_tich_xanh_orders_service` (`service`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    return $CMSNT->query($sql) !== false;
}
