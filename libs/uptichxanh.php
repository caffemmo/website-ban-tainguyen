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
    $api2Key = uptichxanh_db_setting('uptichxanh_api2_key');
    $api2BaseUrl = uptichxanh_db_setting('uptichxanh_api2_base_url');
    $api2Enabled = uptichxanh_db_setting('uptichxanh_api2_enabled', '0') === '1';

    if ($apiKey === '') {
        $apiKey = uptichxanh_env('UPTICHXANH_API_KEY');
    }
    if ($baseUrl === '') {
        $baseUrl = uptichxanh_env('UPTICHXANH_API_BASE_URL', 'https://viaxanh69.com/uptichxanh');
    }
    if ($api2Key === '') {
        $api2Key = uptichxanh_env('UPTICHXANH_API2_KEY');
    }
    if ($api2BaseUrl === '') {
        $api2BaseUrl = uptichxanh_env('UPTICHXANH_API2_BASE_URL', 'https://viameta.co/bot');
    }

    $timeout = $timeoutSetting !== ''
        ? (int) $timeoutSetting
        : (int) uptichxanh_env('UPTICHXANH_TIMEOUT', '20');

    return [
        'api_key' => $apiKey,
        'base_url' => rtrim($baseUrl, '/'),
        'api2' => [
            'enabled' => $api2Enabled,
            'api_key' => $api2Key,
            'base_url' => rtrim($api2BaseUrl, '/')
        ],
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
        return ['ok' => false, 'http_code' => $httpCode, 'body' => null, 'error' => 'Không thể kết nối đến dịch vụ.', 'transport_error' => true];
    }

    $body = json_decode($raw, true);
    if (!is_array($body)) {
        return ['ok' => false, 'http_code' => $httpCode, 'body' => null, 'error' => 'Dịch vụ trả về dữ liệu không hợp lệ.', 'transport_error' => false];
    }

    $providerSuccess = isset($body['status']) && strtolower((string) $body['status']) === 'success';
    return [
        'ok' => $httpCode >= 200 && $httpCode < 300 && $providerSuccess,
        'http_code' => $httpCode,
        'body' => $body,
        'error' => '',
        'transport_error' => false
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
            '_http_code' => $response['http_code'],
            '_transport_error' => !empty($response['transport_error']),
            '_provider' => 'api1'
        ];
    }

    $body['success'] = true;
    $body['_http_code'] = $response['http_code'];
    $body['_transport_error'] = false;
    $body['_provider'] = 'api1';
    return $body;
}

function uptichxanh_api2_is_configured()
{
    $config = uptichxanh_config();
    return !empty($config['api2']['enabled'])
        && $config['api2']['api_key'] !== ''
        && filter_var($config['api2']['base_url'], FILTER_VALIDATE_URL) !== false
        && strtolower((string) parse_url($config['api2']['base_url'], PHP_URL_SCHEME)) === 'https';
}

function uptichxanh_should_use_api2($response)
{
    if (!is_array($response) || !uptichxanh_api2_is_configured()) {
        return false;
    }

    $httpCode = (int) ($response['_http_code'] ?? 0);
    if (!empty($response['_transport_error'])) {
        return true;
    }
    if (in_array($httpCode, [400, 401, 403, 413, 415, 422], true)) {
        return false;
    }
    return true;
}

function uptichxanh_api2_up_fb_call($cookie, $imagePath)
{
    $config = uptichxanh_config();
    if (!uptichxanh_api2_is_configured()) {
        return [
            'success' => false,
            'message' => 'API 2 dự phòng chưa được cấu hình.',
            'data' => [],
            '_http_code' => 0,
            '_transport_error' => false,
            '_provider' => 'api2'
        ];
    }
    if (!is_file($imagePath) || !is_readable($imagePath)) {
        return [
            'success' => false,
            'message' => 'Không thể đọc ảnh xác minh để gửi sang API 2.',
            'data' => [],
            '_http_code' => 0,
            '_transport_error' => false,
            '_provider' => 'api2'
        ];
    }
    if ((int) filesize($imagePath) > 5 * 1024 * 1024) {
        return [
            'success' => false,
            'message' => 'Ảnh vượt quá giới hạn 5MB của API 2.',
            'data' => [],
            '_http_code' => 413,
            '_transport_error' => false,
            '_provider' => 'api2'
        ];
    }
    if (!function_exists('curl_init') || !class_exists('CURLFile')) {
        return [
            'success' => false,
            'message' => 'Máy chủ chưa bật PHP cURL để dùng API 2.',
            'data' => [],
            '_http_code' => 0,
            '_transport_error' => false,
            '_provider' => 'api2'
        ];
    }

    $mime = 'application/octet-stream';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo ? finfo_file($finfo, $imagePath) : false;
        if ($finfo) {
            finfo_close($finfo);
        }
        if (is_string($detectedMime) && $detectedMime !== '') {
            $mime = $detectedMime;
        }
    }
    if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
        return [
            'success' => false,
            'message' => 'API 2 chỉ nhận ảnh JPG hoặc PNG.',
            'data' => [],
            '_http_code' => 415,
            '_transport_error' => false,
            '_provider' => 'api2'
        ];
    }

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $config['api2']['base_url'] . '/ajax/uptick_fb.php',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'cookie' => (string) $cookie,
            'confirm' => 'true',
            'image' => new CURLFile($imagePath, $mime, basename($imagePath))
        ],
        CURLOPT_HTTPHEADER => [
            'Accept: text/event-stream',
            'Cache-Control: no-cache',
            'X-Api-Key: ' . $config['api2']['api_key'],
            'User-Agent: Caffemmo-Social/1.0'
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => max(60, $config['timeout']),
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2
    ]);

    $raw = curl_exec($curl);
    $curlError = curl_error($curl);
    $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($raw === false || $curlError !== '') {
        return [
            'success' => false,
            'message' => 'Không thể kết nối đến API 2 dự phòng.',
            'data' => [],
            '_http_code' => $httpCode,
            '_transport_error' => true,
            '_provider' => 'api2'
        ];
    }

    $done = false;
    $message = '';
    $eventLines = preg_split('/\r\n|\n|\r/', (string) $raw);
    foreach ($eventLines as $line) {
        if (strpos($line, 'data:') !== 0) {
            continue;
        }
        $event = json_decode(trim(substr($line, 5)), true);
        if (!is_array($event)) {
            continue;
        }
        $type = strtolower(trim((string) ($event['type'] ?? '')));
        $payload = isset($event['payload']) && is_array($event['payload']) ? $event['payload'] : [];
        $eventMessage = isset($payload['message']) && is_scalar($payload['message'])
            ? trim((string) $payload['message'])
            : (isset($payload['msg']) && is_scalar($payload['msg']) ? trim((string) $payload['msg']) : '');
        if ($eventMessage !== '') {
            $message = $eventMessage;
        }
        if ($type === 'done') {
            $done = true;
            break;
        }
        if ($type === 'error') {
            break;
        }
    }

    if ($httpCode >= 200 && $httpCode < 300 && $done) {
        return [
            'success' => true,
            'status' => 'success',
            'message' => $message !== '' ? $message : 'Yêu cầu xác minh đã được tiếp nhận.',
            'data' => [],
            '_http_code' => $httpCode,
            '_transport_error' => false,
            '_provider' => 'api2'
        ];
    }

    return [
        'success' => false,
        'message' => $message !== '' ? $message : 'API 2 không hoàn tất yêu cầu xác minh.',
        'data' => [],
        '_http_code' => $httpCode,
        '_transport_error' => false,
        '_provider' => 'api2'
    ];
}

function uptichxanh_error_text($response, $fallback = 'Không thể hoàn tất yêu cầu.')
{
    $httpCode = (int) ($response['_http_code'] ?? 0);
    $providerMessage = isset($response['message']) && is_scalar($response['message'])
        ? trim((string) $response['message'])
        : '';
    if (($response['_provider'] ?? '') === 'api2' && $providerMessage !== '') {
        return function_exists('mb_substr') ? mb_substr($providerMessage, 0, 300) : substr($providerMessage, 0, 300);
    }
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
