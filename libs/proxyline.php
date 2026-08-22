<?php

if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

function proxyline_config()
{
    $apiKey = youproxy_db_setting('proxyline_api_key');
    $baseUrl = youproxy_db_setting('proxyline_api_base_url');
    $timeout = (int) youproxy_db_setting('proxyline_timeout', '0');

    if ($apiKey === '') {
        $apiKey = youproxy_env('PROXYLINE_API_KEY');
    }
    if ($baseUrl === '') {
        $baseUrl = youproxy_env('PROXYLINE_API_BASE_URL', 'https://panel.proxyline.net/api');
    }
    if ($timeout <= 0) {
        $timeout = (int) youproxy_env('PROXYLINE_TIMEOUT', '20');
    }

    return [
        'api_key' => $apiKey,
        'base_url' => rtrim($baseUrl, '/'),
        'timeout' => max(5, $timeout)
    ];
}

function proxyline_is_configured()
{
    $config = proxyline_config();
    return $config['api_key'] !== ''
        && filter_var($config['base_url'], FILTER_VALIDATE_URL) !== false
        && strtolower((string) parse_url($config['base_url'], PHP_URL_SCHEME)) === 'https';
}

function proxyline_request($method, $endpoint, $query = [], $payload = [])
{
    $config = proxyline_config();
    if ($config['api_key'] === '') {
        return ['success' => false, 'http_code' => 0, 'body' => null, 'message' => 'ProxyLine chưa được cấu hình trên server.'];
    }
    if (strtolower((string) parse_url($config['base_url'], PHP_URL_SCHEME)) !== 'https') {
        return ['success' => false, 'http_code' => 0, 'body' => null, 'message' => 'URL dịch vụ proxy phải dùng HTTPS.'];
    }
    if (!function_exists('curl_init')) {
        return ['success' => false, 'http_code' => 0, 'body' => null, 'message' => 'Máy chủ chưa bật PHP cURL.'];
    }

    $query['api_key'] = $config['api_key'];
    $url = $config['base_url'] . '/' . ltrim($endpoint, '/');
    $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    $method = strtoupper($method);
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
    if ($method === 'POST') {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/x-www-form-urlencoded';
        $options[CURLOPT_POSTFIELDS] = http_build_query($payload ?: [], '', '&', PHP_QUERY_RFC3986);
    }

    $curl = curl_init();
    curl_setopt_array($curl, $options);
    $raw = curl_exec($curl);
    $curlError = curl_error($curl);
    $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($raw === false || $curlError !== '') {
        return ['success' => false, 'http_code' => $httpCode, 'body' => null, 'message' => 'Không thể kết nối đến ProxyLine.'];
    }
    $body = json_decode($raw, true);
    if (!is_array($body)) {
        return ['success' => false, 'http_code' => $httpCode, 'body' => null, 'message' => 'ProxyLine trả về dữ liệu không hợp lệ.'];
    }
    $response = [
        'success' => $httpCode >= 200 && $httpCode < 300,
        'http_code' => $httpCode,
        'body' => $body,
        'message' => ''
    ];
    if (!$response['success']) {
        $response['message'] = proxyline_error_text($response);
    }
    return $response;
}

function proxyline_error_text($response, $fallback = 'ProxyLine không thể hoàn tất yêu cầu.')
{
    $body = is_array($response) && isset($response['body']) && is_array($response['body']) ? $response['body'] : $response;
    foreach (['message', 'error', 'errors', 'detail', 'description'] as $key) {
        if (!is_array($body) || !array_key_exists($key, $body)) {
            continue;
        }
        $value = $body[$key];
        if (is_array($value)) {
            $value = implode(', ', array_map('strval', $value));
        }
        if (is_scalar($value) && trim((string) $value) !== '') {
            return trim((string) $value);
        }
    }
    $httpCode = (int) (is_array($response) ? ($response['http_code'] ?? 0) : 0);
    return $httpCode === 401 || $httpCode === 403 ? 'API key ProxyLine không hợp lệ hoặc không có quyền.' : $fallback;
}

function proxyline_payload($response)
{
    $body = is_array($response) ? ($response['body'] ?? []) : [];
    if (is_array($body) && isset($body['data']) && is_array($body['data'])) {
        return $body['data'];
    }
    return is_array($body) ? $body : [];
}

function proxyline_direct_value($record, $keys)
{
    if (!is_array($record)) {
        return null;
    }
    foreach ($keys as $key) {
        if (array_key_exists($key, $record) && is_scalar($record[$key])) {
            return $record[$key];
        }
    }
    return null;
}

function proxyline_api_call($method, $endpoint, $query = [], $payload = [])
{
    return proxyline_request($method, $endpoint, $query, $payload);
}

function proxyline_countries()
{
    return proxyline_api_call('GET', 'countries/');
}

function proxyline_periods()
{
    return [5, 10, 20, 30, 60, 90, 120, 150, 180, 210, 240, 270, 300, 330, 360];
}

function proxyline_country_options($response)
{
    $payload = proxyline_payload($response);
    $value = $payload;
    if (is_array($payload)) {
        foreach (['countries', 'items', 'results'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                $value = $payload[$key];
                break;
            }
        }
    }
    if (!is_array($value)) {
        return [];
    }
    $options = [];
    foreach ($value as $key => $item) {
        if (is_scalar($item) && is_string($key) && preg_match('/^[a-z]{2,3}$/i', $key)) {
            $code = strtolower(trim($key));
            $label = trim((string) $item);
        } elseif (is_scalar($item)) {
            $code = strtolower(trim((string) $item));
            $label = strtoupper($code);
        } elseif (is_array($item)) {
            $code = strtolower(trim((string) (proxyline_direct_value($item, ['code', 'country', 'country_code', 'value', 'id']) ?: '')));
            $label = trim((string) (proxyline_direct_value($item, ['name', 'title', 'label', 'country_name']) ?: strtoupper($code)));
        } else {
            continue;
        }
        if ($code !== '' && preg_match('/^[a-z]{2,3}$/', $code)) {
            $options[] = ['value' => $code, 'label' => $label];
        }
    }
    return array_values(array_reduce($options, function ($carry, $option) {
        $carry[$option['value']] = $option;
        return $carry;
    }, []));
}

function proxyline_rent_period_options()
{
    return array_map(function ($days) {
        return ['value' => (string) $days, 'label' => (string) $days];
    }, proxyline_periods());
}

function proxyline_ips($country = '')
{
    $query = ['ip_version' => 4, 'type' => 'dedicated'];
    if ($country !== '') {
        $query['country'] = strtolower($country);
    }
    return proxyline_api_call('GET', 'ips/', $query);
}

function proxyline_new_order($payload)
{
    $request = [
        'type' => 'dedicated_ipv4',
        'country' => strtolower((string) ($payload['country'] ?? '')),
        'period' => (int) ($payload['rentPeriodDays'] ?? 0),
        'quantity' => (int) ($payload['quantity'] ?? 1)
    ];
    if (!empty($payload['promoCode'])) {
        $request['coupon'] = (string) $payload['promoCode'];
    }
    return proxyline_api_call('POST', 'new-order/', [], $request);
}

function proxyline_record_from_array($item)
{
    if (!is_array($item)) {
        return null;
    }
    $id = trim((string) (proxyline_direct_value($item, ['id', 'ip_id', 'proxy_id']) ?: ''));
    $ip = trim((string) (proxyline_direct_value($item, ['ip', 'ip_address', 'address']) ?: ''));
    if ($id === '' && $ip === '') {
        return null;
    }
    $login = trim((string) (proxyline_direct_value($item, ['username', 'user', 'login', 'user_name', 'người dùng', 'tên người dùng']) ?: ''));
    $password = trim((string) (proxyline_direct_value($item, ['password', 'pass', 'mật khẩu']) ?: ''));
    return [
        'id' => $id !== '' ? $id : $ip,
        'ip' => $ip,
        'httpsPort' => (string) (proxyline_direct_value($item, ['http_port', 'https_port', 'httpPort', 'port', 'cổng http', 'port_http']) ?: ''),
        'socks5Port' => (string) (proxyline_direct_value($item, ['socks5_port', 'socks5Port', 'port_socks5']) ?: ''),
        'authInfo' => ['login' => $login, 'password' => $password],
        'orderId' => (string) (proxyline_direct_value($item, ['order_id', 'orderId', 'order']) ?: ''),
        'country' => strtolower((string) (proxyline_direct_value($item, ['country', 'country_code', 'quốc gia']) ?: '')),
        'proxyType' => 'IPv4',
        'dateStart' => (string) (proxyline_direct_value($item, ['date_start', 'start_date', 'date', 'ngày']) ?: ''),
        'dateEnd' => (string) (proxyline_direct_value($item, ['date_end', 'end_date', 'expires_at']) ?: ''),
        'provider_code' => 'proxyline'
    ];
}

function proxyline_collect_records($value, &$records = [])
{
    if (!is_array($value)) {
        return $records;
    }
    $record = proxyline_record_from_array($value);
    if ($record !== null) {
        $records[$record['id']] = $record;
        return $records;
    }
    foreach ($value as $child) {
        if (is_array($child)) {
            proxyline_collect_records($child, $records);
        }
    }
    return $records;
}

function proxyline_normalize_records($response)
{
    $records = [];
    proxyline_collect_records(proxyline_payload($response), $records);
    return array_values($records);
}

function proxyline_extract_order_id($response)
{
    $body = proxyline_payload($response);
    $id = is_array($body) ? proxyline_direct_value($body, ['order_id', 'orderId', 'order']) : null;
    if ($id === null) {
        foreach (proxyline_normalize_records($response) as $record) {
            if (!empty($record['orderId'])) {
                return (string) $record['orderId'];
            }
        }
    }
    return is_scalar($id) ? trim((string) $id) : '';
}

function proxyline_ipv4_price_per_ip_day()
{
    $value = youproxy_db_setting('proxyline_ipv4_price_per_ip_day', '0');
    return is_numeric($value) && (float) $value > 0 ? round((float) $value, 2) : 0;
}

function proxyline_ipv4_quote($payload)
{
    $unitPrice = proxyline_ipv4_price_per_ip_day();
    $quantity = max(1, (int) ($payload['quantity'] ?? 1));
    $days = max(1, (int) ($payload['rentPeriodDays'] ?? 0));
    if (!in_array($days, proxyline_periods(), true)) {
        return ['success' => false, 'message' => 'Thời hạn IPv4 không được nhà cung cấp hỗ trợ.'];
    }
    $amount = round($unitPrice * $quantity * $days, 2);
    if ($amount <= 0) {
        return ['success' => false, 'message' => 'Giá bán IPv4 chưa được cấu hình trong Admin.'];
    }
    return [
        'success' => true,
        'data' => [
            'wallet_amount' => $amount,
            'wallet_label' => function_exists('format_currency') ? format_currency($amount) : number_format($amount, 0, ',', '.') . 'đ'
        ],
        'price' => [
            'provider_price' => 0,
            'provider_currency' => 'VND',
            'provider_cost_vnd' => 0,
            'wallet_amount' => $amount,
            'wallet_label' => function_exists('format_currency') ? format_currency($amount) : number_format($amount, 0, ',', '.') . 'đ'
        ]
    ];
}
