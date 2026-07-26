<?php

if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

/**
 * Proxy integration helpers.
 * Provider credentials are read from admin settings first, then server env.
 */
function youproxy_env($key, $fallback = '')
{
    if (isset($_ENV[$key]) && trim((string) $_ENV[$key]) !== '') {
        return trim((string) $_ENV[$key]);
    }
    $value = getenv($key);
    return $value !== false && trim((string) $value) !== '' ? trim((string) $value) : $fallback;
}

function youproxy_db_setting($name, $fallback = '')
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

function youproxy_config()
{
    global $CMSNT;

    $apiKey = youproxy_db_setting('youproxy_api_key');
    $baseUrl = youproxy_db_setting('youproxy_api_base_url');
    $usdRate = (float) youproxy_db_setting('youproxy_usd_rate', '0');
    $markupSetting = youproxy_db_setting('youproxy_markup_percent', '');
    $markup = $markupSetting !== '' ? (float) $markupSetting : 0;
    $timeout = (int) youproxy_db_setting('youproxy_timeout', '0');

    if ($apiKey === '') {
        $apiKey = youproxy_env('YOUPROXY_API_KEY');
    }
    if ($baseUrl === '') {
        $baseUrl = youproxy_env('YOUPROXY_API_BASE_URL', 'https://youproxy.io');
    }
    if ($usdRate <= 0) {
        $usdRate = (float) youproxy_env('YOUPROXY_USD_RATE', '0');
    }
    if ($usdRate <= 0 && isset($CMSNT)) {
        $usdRate = (float) $CMSNT->site('usd_rate');
    }
    if ($usdRate <= 0) {
        $usdRate = 25000;
    }

    if ($markupSetting === '') {
        $markup = (float) youproxy_env('YOUPROXY_MARKUP_PERCENT', '20');
    }
    if ($timeout <= 0) {
        $timeout = (int) youproxy_env('YOUPROXY_TIMEOUT', '20');
    }
    return [
        'api_key' => $apiKey,
        'base_url' => rtrim($baseUrl, '/'),
        'usd_rate' => $usdRate,
        'markup_percent' => max(0, $markup),
        'timeout' => max(5, $timeout)
    ];
}

function youproxy_is_configured()
{
    $config = youproxy_config();
    return $config['api_key'] !== '' && filter_var($config['base_url'], FILTER_VALIDATE_URL) !== false;
}

function youproxy_error_text($response, $fallback = 'Không thể hoàn tất yêu cầu proxy.')
{
    $code = (string) youproxy_find_first_value($response, ['errorCode', 'code', 'error_code']);
    $map = [
        '2' => 'Dịch vụ proxy chưa sẵn sàng.',
        '3' => 'Dịch vụ proxy hiện không khả dụng.',
        '4' => 'Loại proxy không hợp lệ.',
        '6' => 'Quốc gia không hợp lệ.',
        '7' => 'Quốc gia này hiện không được cung cấp.',
        '10' => 'Kiểu xác thực không hợp lệ.',
        '11' => 'Thời hạn proxy không hợp lệ.',
        '14' => 'Mục đích sử dụng không được để trống.',
        '16' => 'Dịch vụ proxy hiện không thể xử lý đơn.',
        '19' => 'Số lượng proxy không hợp lệ.',
        '23' => 'Địa chỉ IP xác thực không hợp lệ.',
        '24' => 'Danh sách proxy được chọn không hợp lệ.',
        '25' => 'Mã đơn hàng không hợp lệ.',
        '28' => 'Với loại proxy này cần chọn đủ các IP trong cùng đơn.',
        '35' => 'Dịch vụ proxy báo lỗi không xác định.'
    ];
    if ($code !== '' && isset($map[$code])) {
        return $map[$code];
    }
    return $fallback;
}

function youproxy_request($method, $endpoint, $query = [], $payload = null)
{
    $config = youproxy_config();
    if ($config['api_key'] === '') {
        return ['ok' => false, 'http_code' => 0, 'body' => null, 'error' => 'Dịch vụ proxy chưa được cấu hình trên server.'];
    }
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'http_code' => 0, 'body' => null, 'error' => 'Máy chủ chưa bật PHP cURL.'];
    }

    $url = $config['base_url'] . '/client/api/v1/' . rawurlencode($config['api_key']) . '/' . ltrim($endpoint, '/');
    if (!empty($query)) {
        $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    $headers = ['Accept: application/json', 'User-Agent: Caffemmo-Proxy/1.0'];
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
        $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
        $options[CURLOPT_POSTFIELDS] = json_encode($payload ?: [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    $curl = curl_init();
    curl_setopt_array($curl, $options);
    $raw = curl_exec($curl);
    $curlError = curl_error($curl);
    $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($raw === false || $curlError !== '') {
        return ['ok' => false, 'http_code' => $httpCode, 'body' => null, 'error' => 'Không thể kết nối đến dịch vụ proxy.'];
    }
    $body = json_decode($raw, true);
    if (!is_array($body)) {
        return ['ok' => false, 'http_code' => $httpCode, 'body' => null, 'error' => 'Dịch vụ proxy trả về dữ liệu không hợp lệ.'];
    }
    return ['ok' => $httpCode >= 200 && $httpCode < 300, 'http_code' => $httpCode, 'body' => $body, 'error' => ''];
}

function youproxy_api_call($method, $endpoint, $query = [], $payload = null)
{
    $response = youproxy_request($method, $endpoint, $query, $payload);
    if (!$response['ok']) {
        return ['success' => false, 'error' => $response['error'], '_http_code' => $response['http_code']];
    }
    $body = is_array($response['body']) ? $response['body'] : [];
    if (array_key_exists('success', $body) && !$body['success']) {
        $body['success'] = false;
        $body['error'] = youproxy_error_text($body);
    } else {
        $body['success'] = true;
    }
    $body['_http_code'] = $response['http_code'];
    return $body;
}

function youproxy_proxy_types()
{
    return youproxy_api_call('GET', 'proxyType');
}

function youproxy_countries($proxyType)
{
    return youproxy_api_call('GET', 'country', ['proxyType' => $proxyType]);
}

function youproxy_rent_periods($proxyType)
{
    return youproxy_api_call('GET', 'rentPeriod', ['proxyType' => $proxyType]);
}

function youproxy_mobile_operators()
{
    return youproxy_api_call('GET', 'mobileOperator');
}

function youproxy_balance()
{
    return youproxy_api_call('GET', 'balance');
}

function youproxy_ip_addresses($proxyType = '')
{
    $query = $proxyType !== '' ? ['proxyType' => $proxyType] : [];
    return youproxy_api_call('GET', 'ipAddress', $query);
}

function youproxy_calculate_order($payload)
{
    return youproxy_api_call('POST', 'calculate/order', [], $payload);
}

function youproxy_create_order($payload)
{
    return youproxy_api_call('POST', 'order', [], $payload);
}

function youproxy_calculate_extend($payload)
{
    return youproxy_api_call('POST', 'calculate/extend', [], $payload);
}

function youproxy_extend($payload)
{
    return youproxy_api_call('POST', 'extend', [], $payload);
}

function youproxy_auto_extend($payload, $byOrder = false)
{
    return youproxy_api_call('POST', $byOrder ? 'extend/order/auto' : 'extend/auto', [], $payload);
}

function youproxy_find_first_value($data, $keys)
{
    if (!is_array($data)) {
        return null;
    }
    foreach ($keys as $key) {
        foreach ($data as $currentKey => $value) {
            if (strcasecmp((string) $currentKey, (string) $key) === 0) {
                return $value;
            }
        }
    }
    foreach ($data as $value) {
        if (is_array($value)) {
            $found = youproxy_find_first_value($value, $keys);
            if ($found !== null) {
                return $found;
            }
        }
    }
    return null;
}

function youproxy_payload_data($response)
{
    if (isset($response['data'])) {
        return $response['data'];
    }
    return $response;
}

function youproxy_normalize_option_list($value, $codeKeys = [], $labelKeys = [])
{
    if ($value === null) {
        return [];
    }
    if (!is_array($value)) {
        return [['value' => (string) $value, 'label' => (string) $value]];
    }
    $isList = array_keys($value) === range(0, count($value) - 1);
    if (!$isList) {
        $value = [$value];
    }
    $options = [];
    foreach ($value as $item) {
        if (is_scalar($item)) {
            $code = trim((string) $item);
            if ($code !== '') {
                $options[] = ['value' => $code, 'label' => $code];
            }
            continue;
        }
        if (!is_array($item)) {
            continue;
        }
        $code = youproxy_find_first_value($item, array_merge($codeKeys, ['code', 'id', 'value', 'alpha3', 'proxyType', 'days']));
        $label = youproxy_find_first_value($item, array_merge($labelKeys, ['name', 'title', 'label', 'description', 'days']));
        if (is_array($code) || $code === null || trim((string) $code) === '') {
            continue;
        }
        $code = trim((string) $code);
        $label = is_scalar($label) && trim((string) $label) !== '' ? trim((string) $label) : $code;
        $options[] = ['value' => $code, 'label' => $label];
    }
    $seen = [];
    return array_values(array_filter($options, function ($option) use (&$seen) {
        if (isset($seen[$option['value']])) {
            return false;
        }
        $seen[$option['value']] = true;
        return true;
    }));
}

function youproxy_response_options($response, $keys, $codeKeys = [], $labelKeys = [])
{
    $value = youproxy_find_first_value(youproxy_payload_data($response), $keys);
    if ($value === null) {
        $value = youproxy_payload_data($response);
    }
    return youproxy_normalize_option_list($value, $codeKeys, $labelKeys);
}

function youproxy_collect_ip_records($value, &$records = [])
{
    if (!is_array($value)) {
        return $records;
    }
    $hasIp = youproxy_find_first_value($value, ['ipAddressId']) !== null || youproxy_find_first_value($value, ['ipAddress']) !== null;
    if ($hasIp && !isset($value['success'])) {
        $id = (string) youproxy_find_first_value($value, ['ipAddressId', 'id']);
        if ($id !== '' && !isset($records[$id])) {
            $records[$id] = $value;
        }
        return $records;
    }
    foreach ($value as $child) {
        if (is_array($child)) {
            youproxy_collect_ip_records($child, $records);
        }
    }
    return $records;
}

function youproxy_normalize_ip_records($response)
{
    $payload = youproxy_payload_data($response);
    $records = [];
    if (is_array($payload)) {
        foreach (['IPV4', 'IPV6', 'MOBILE', 'ISP'] as $proxyType) {
            $group = null;
            foreach ($payload as $key => $value) {
                if (strcasecmp((string) $key, $proxyType) === 0) {
                    $group = $value;
                    break;
                }
            }
            if ($group !== null) {
                $groupRecords = [];
                youproxy_collect_ip_records($group, $groupRecords);
                foreach ($groupRecords as $id => $record) {
                    if (youproxy_find_first_value($record, ['proxyType', 'type']) === null) {
                        $record['proxyType'] = $proxyType;
                    }
                    $records[$id] = $record;
                }
            }
        }
    }
    if (empty($records)) {
        youproxy_collect_ip_records($payload, $records);
    }
    return array_values($records);
}

function youproxy_price_usd($response)
{
    $price = youproxy_find_first_value($response, ['price', 'amount', 'totalPrice']);
    return is_numeric($price) ? max(0, (float) $price) : 0;
}

function youproxy_wallet_amount($usd)
{
    $config = youproxy_config();
    return round(max(0, (float) $usd) * $config['usd_rate'] * (1 + ($config['markup_percent'] / 100)), 2);
}

function youproxy_price_context($response)
{
    $usd = youproxy_price_usd($response);
    $wallet = youproxy_wallet_amount($usd);
    return [
        'provider_price' => $usd,
        'provider_currency' => (string) (youproxy_find_first_value($response, ['currency']) ?: 'USD'),
        'wallet_amount' => $wallet,
        'usd_rate' => youproxy_config()['usd_rate'],
        'markup_percent' => youproxy_config()['markup_percent'],
        'wallet_label' => function_exists('format_currency') ? format_currency($wallet) : number_format($wallet, 0, ',', '.') . 'đ'
    ];
}

function youproxy_extract_order_id($response)
{
    $id = youproxy_find_first_value($response, ['orderId', 'orderID', 'orderNumber', 'providerOrderId', 'order_id']);
    return is_scalar($id) && trim((string) $id) !== '' ? trim((string) $id) : '';
}

function youproxy_extract_balance($response)
{
    $balance = youproxy_find_first_value($response, ['balance', 'availableBalance', 'balanceAmount']);
    return is_numeric($balance) ? (float) $balance : null;
}

function youproxy_ensure_tables()
{
    global $CMSNT;
    if (!isset($CMSNT)) {
        return false;
    }
    $sql = "CREATE TABLE IF NOT EXISTS `proxy_orders` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` INT UNSIGNED NOT NULL,
        `provider_order_id` VARCHAR(100) NULL,
        `proxy_type` VARCHAR(20) NOT NULL,
        `country` VARCHAR(12) NULL,
        `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
        `rent_period_days` INT UNSIGNED NOT NULL DEFAULT 0,
        `provider_price` DECIMAL(14,6) NOT NULL DEFAULT 0,
        `wallet_amount` DECIMAL(18,2) NOT NULL DEFAULT 0,
        `provider_currency` VARCHAR(10) NOT NULL DEFAULT 'USD',
        `auto_extend` TINYINT(1) NOT NULL DEFAULT 0,
        `status` VARCHAR(20) NOT NULL DEFAULT 'active',
        `ip_address_ids` LONGTEXT NULL,
        `provider_payload` LONGTEXT NULL,
        `created_at` DATETIME NOT NULL,
        `updated_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `proxy_orders_user_id` (`user_id`),
        KEY `proxy_orders_provider_order_id` (`provider_order_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    return $CMSNT->query($sql) !== false;
}
