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

function youproxy_api_result($response)
{
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

function youproxy_api_call($method, $endpoint, $query = [], $payload = null)
{
    return youproxy_api_result(youproxy_request($method, $endpoint, $query, $payload));
}

/**
 * Fetch several read-only provider endpoints at once. Metadata requests used to
 * run one after another, making the proxy purchase page wait for every country
 * and duration response before it could render.
 */
function youproxy_multi_get($requests)
{
    $results = [];
    if (!is_array($requests) || empty($requests)) {
        return $results;
    }

    $config = youproxy_config();
    if ($config['api_key'] === '' || !function_exists('curl_multi_init')) {
        foreach ($requests as $key => $request) {
            $results[$key] = youproxy_api_call('GET', $request['endpoint'] ?? '', $request['query'] ?? []);
        }
        return $results;
    }

    $multi = curl_multi_init();
    if ($multi === false) {
        foreach ($requests as $key => $request) {
            $results[$key] = youproxy_api_call('GET', $request['endpoint'] ?? '', $request['query'] ?? []);
        }
        return $results;
    }

    $handles = [];
    foreach ($requests as $key => $request) {
        $endpoint = ltrim((string) ($request['endpoint'] ?? ''), '/');
        if ($endpoint === '') {
            $results[$key] = ['success' => false, 'error' => 'Yêu cầu dịch vụ proxy không hợp lệ.', '_http_code' => 0];
            continue;
        }

        $url = $config['base_url'] . '/client/api/v1/' . rawurlencode($config['api_key']) . '/' . $endpoint;
        $query = isset($request['query']) && is_array($request['query']) ? $request['query'] : [];
        if (!empty($query)) {
            $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        $curl = curl_init();
        if ($curl === false) {
            $results[$key] = ['success' => false, 'error' => 'Không thể kết nối đến dịch vụ proxy.', '_http_code' => 0];
            continue;
        }
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => $config['timeout'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'User-Agent: Caffemmo-Proxy/1.0']
        ]);
        $handles[(string) $key] = $curl;
        curl_multi_add_handle($multi, $curl);
    }

    do {
        $status = curl_multi_exec($multi, $running);
    } while ($status === CURLM_CALL_MULTI_PERFORM);

    while ($running && $status === CURLM_OK) {
        $selected = curl_multi_select($multi, 1.0);
        if ($selected === -1) {
            usleep(50000);
        }
        do {
            $status = curl_multi_exec($multi, $running);
        } while ($status === CURLM_CALL_MULTI_PERFORM);
    }

    foreach ($handles as $key => $curl) {
        $raw = curl_multi_getcontent($curl);
        $curlError = curl_error($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_multi_remove_handle($multi, $curl);
        curl_close($curl);

        if ($raw === false || $curlError !== '') {
            $results[$key] = youproxy_api_result(['ok' => false, 'http_code' => $httpCode, 'body' => null, 'error' => 'Không thể kết nối đến dịch vụ proxy.']);
            continue;
        }
        $body = json_decode($raw, true);
        if (!is_array($body)) {
            $results[$key] = youproxy_api_result(['ok' => false, 'http_code' => $httpCode, 'body' => null, 'error' => 'Dịch vụ proxy trả về dữ liệu không hợp lệ.']);
            continue;
        }
        $results[$key] = youproxy_api_result(['ok' => $httpCode >= 200 && $httpCode < 300, 'http_code' => $httpCode, 'body' => $body, 'error' => '']);
    }
    curl_multi_close($multi);

    return $results;
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
        $code = youproxy_find_first_value($item, array_merge($codeKeys, [
            'code', 'id', 'value', 'alpha3', 'alpha3code', 'countryCode',
            'proxyType', 'days', 'rentPeriodDays', 'rentPeriod', 'period', 'duration'
        ]));
        $label = youproxy_find_first_value($item, array_merge($labelKeys, [
            'name', 'title', 'label', 'description', 'countryName',
            'country', 'days', 'rentPeriodDays', 'rentPeriod', 'period', 'duration'
        ]));
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

function youproxy_find_direct_value($data, $keys)
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
    return null;
}

function youproxy_collect_ip_records($value, &$records = [], $context = [])
{
    if (!is_array($value)) {
        return $records;
    }

    // The provider wraps IP records inside order objects. Only direct fields
    // identify an IP record; recursive lookup would mistake the wrapper for it.
    $idValue = youproxy_find_direct_value($value, ['ipAddressId', 'id']);
    $ipValue = youproxy_find_direct_value($value, ['ipAddressIp', 'ipAddress', 'ip']);
    $hasIp = $idValue !== null || $ipValue !== null;
    if ($hasIp && !array_key_exists('success', $value)) {
        $record = $value;
        $orderId = youproxy_find_direct_value($record, ['orderId', 'orderID', 'orderNumber']);
        if (($orderId === null || trim((string) $orderId) === '') && !empty($context['orderId'])) {
            $record['orderId'] = $context['orderId'];
        }
        $proxyType = youproxy_find_direct_value($record, ['proxyType', 'type']);
        if (($proxyType === null || trim((string) $proxyType) === '') && !empty($context['proxyType'])) {
            $record['proxyType'] = $context['proxyType'];
        }
        $id = is_scalar($idValue) ? trim((string) $idValue) : '';
        if ($id !== '') {
            $records[$id] = $record;
        }
        return $records;
    }

    $nextContext = $context;
    $orderId = youproxy_find_direct_value($value, ['orderId', 'orderID', 'orderNumber']);
    if (is_scalar($orderId) && trim((string) $orderId) !== '') {
        $nextContext['orderId'] = trim((string) $orderId);
    }
    $proxyType = youproxy_find_direct_value($value, ['proxyType', 'type']);
    if (is_scalar($proxyType) && trim((string) $proxyType) !== '') {
        $nextContext['proxyType'] = trim((string) $proxyType);
    }
    foreach ($value as $child) {
        if (is_array($child)) {
            youproxy_collect_ip_records($child, $records, $nextContext);
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
                youproxy_collect_ip_records($group, $groupRecords, ['proxyType' => $proxyType]);
                foreach ($groupRecords as $id => $record) {
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

function youproxy_ipv6_retail_batch_quantity()
{
    return 10;
}

function youproxy_ipv6_retail_release_expired_reservations()
{
    global $CMSNT;
    if (!isset($CMSNT)) {
        return false;
    }

    return $CMSNT->update('proxy_ipv6_inventory', [
        'status' => 'available',
        'reservation_token' => null,
        'reserved_until' => null,
        'updated_at' => date('Y-m-d H:i:s')
    ], '`status` = ? AND `reserved_until` IS NOT NULL AND `reserved_until` < ?', ['reserved', date('Y-m-d H:i:s')]);
}

function youproxy_ipv6_retail_available_items($payload, $quantity)
{
    global $CMSNT;
    if (!isset($CMSNT) || !youproxy_ensure_tables()) {
        return [];
    }

    youproxy_ipv6_retail_release_expired_reservations();
    $rentDays = max(1, (int) ($payload['rentPeriodDays'] ?? 0));
    $expiryThreshold = date('Y-m-d H:i:s', time() + max(0, $rentDays - 1) * 86400);
    return $CMSNT->get_list_safe(
        'SELECT `id`, `provider_ip_id`, `provider_order_id`, `retail_price`, `date_end`
        FROM `proxy_ipv6_inventory`
        WHERE `status` = ?
            AND `country` = ?
            AND `protocol` = ?
            AND `auth_type` = ?
            AND `rent_period_days` = ?
            AND `date_end` >= ?
        ORDER BY `date_end` ASC, `id` ASC
        LIMIT ?',
        [
            'available',
            (string) ($payload['country'] ?? ''),
            (string) ($payload['protocol'] ?? 'HTTP'),
            (string) ($payload['authType'] ?? 'LOGIN'),
            $rentDays,
            $expiryThreshold,
            max(1, (int) $quantity)
        ]
    ) ?: [];
}

function youproxy_ipv6_retail_reserve_items($payload, $quantity, $reservationToken)
{
    global $CMSNT;
    $items = youproxy_ipv6_retail_available_items($payload, $quantity);
    if (count($items) !== (int) $quantity) {
        return [];
    }

    $reservedUntil = date('Y-m-d H:i:s', time() + 120);
    $claimedIds = [];
    foreach ($items as $item) {
        $itemId = (int) ($item['id'] ?? 0);
        if ($itemId <= 0) {
            youproxy_ipv6_retail_release_items($reservationToken);
            return [];
        }
        $updated = $CMSNT->update('proxy_ipv6_inventory', [
            'status' => 'reserved',
            'reservation_token' => $reservationToken,
            'reserved_until' => $reservedUntil,
            'updated_at' => date('Y-m-d H:i:s')
        ], '`id` = ? AND `status` = ?', [$itemId, 'available']);
        $claimed = $updated !== false ? $CMSNT->get_row_safe(
            'SELECT `id` FROM `proxy_ipv6_inventory` WHERE `id` = ? AND `status` = ? AND `reservation_token` = ? LIMIT 1',
            [$itemId, 'reserved', $reservationToken]
        ) : false;
        if (!$claimed) {
            youproxy_ipv6_retail_release_items($reservationToken);
            return [];
        }
        $claimedIds[] = $itemId;
    }

    $placeholders = implode(',', array_fill(0, count($claimedIds), '?'));
    $rows = $CMSNT->get_list_safe(
        'SELECT `id`, `provider_ip_id`, `retail_price`, `date_end` FROM `proxy_ipv6_inventory` WHERE `reservation_token` = ? AND `status` = ? AND `id` IN (' . $placeholders . ') ORDER BY `date_end` ASC, `id` ASC',
        array_merge([$reservationToken, 'reserved'], $claimedIds)
    ) ?: [];
    if (count($rows) !== count($claimedIds)) {
        youproxy_ipv6_retail_release_items($reservationToken);
        return [];
    }
    return $rows;
}

function youproxy_ipv6_retail_release_items($reservationToken)
{
    global $CMSNT;
    if (!isset($CMSNT) || $reservationToken === '') {
        return false;
    }
    return $CMSNT->update('proxy_ipv6_inventory', [
        'status' => 'available',
        'reservation_token' => null,
        'reserved_until' => null,
        'updated_at' => date('Y-m-d H:i:s')
    ], '`status` = ? AND `reservation_token` = ?', ['reserved', $reservationToken]);
}

function youproxy_ipv6_retail_allocate_items($reservationToken, $userId, $customerOrderId, $expectedQuantity)
{
    global $CMSNT;
    if (!isset($CMSNT) || $reservationToken === '') {
        return false;
    }
    $updated = $CMSNT->update('proxy_ipv6_inventory', [
        'status' => 'sold',
        'customer_user_id' => (int) $userId,
        'customer_proxy_order_id' => (int) $customerOrderId,
        'reservation_token' => null,
        'reserved_until' => null,
        'updated_at' => date('Y-m-d H:i:s')
    ], '`status` = ? AND `reservation_token` = ?', ['reserved', $reservationToken]);
    if ($updated === false) {
        return false;
    }

    $count = $CMSNT->get_row_safe(
        'SELECT COUNT(*) AS total FROM `proxy_ipv6_inventory` WHERE `customer_proxy_order_id` = ? AND `customer_user_id` = ? AND `status` = ?',
        [(int) $customerOrderId, (int) $userId, 'sold']
    );
    return (int) ($count['total'] ?? 0) === max(1, (int) $expectedQuantity);
}

function youproxy_ipv6_retail_revert_allocation($userId, $customerOrderId)
{
    global $CMSNT;
    if (!isset($CMSNT)) {
        return false;
    }

    return $CMSNT->update('proxy_ipv6_inventory', [
        'status' => 'available',
        'customer_user_id' => null,
        'customer_proxy_order_id' => null,
        'updated_at' => date('Y-m-d H:i:s')
    ], '`status` = ? AND `customer_user_id` = ? AND `customer_proxy_order_id` = ?', [
        'sold',
        (int) $userId,
        (int) $customerOrderId
    ]);
}

function youproxy_ipv6_retail_store_batch($adminUserId, $payload, $quote, $providerOrder)
{
    global $CMSNT;
    if (!isset($CMSNT) || !youproxy_ensure_tables()) {
        return ['success' => false, 'message' => 'Không thể chuẩn bị kho IPv6.'];
    }

    $batchQuantity = youproxy_ipv6_retail_batch_quantity();
    $price = youproxy_price_context($quote);
    $providerOrderId = youproxy_extract_order_id($providerOrder);
    $batchId = $CMSNT->insert('proxy_ipv6_batches', [
        'provider_order_id' => $providerOrderId !== '' ? $providerOrderId : null,
        'country' => (string) ($payload['country'] ?? ''),
        'protocol' => (string) ($payload['protocol'] ?? 'HTTP'),
        'auth_type' => (string) ($payload['authType'] ?? 'LOGIN'),
        'rent_period_days' => (int) ($payload['rentPeriodDays'] ?? 0),
        'goal' => (string) ($payload['goal'] ?? ''),
        'expected_quantity' => $batchQuantity,
        'received_quantity' => 0,
        'provider_price' => (float) $price['provider_price'],
        'retail_unit_price' => round((float) $price['wallet_amount'] / $batchQuantity, 2),
        'provider_currency' => (string) $price['provider_currency'],
        'status' => 'pending_sync',
        'provider_payload' => json_encode($providerOrder, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'created_by' => (int) $adminUserId,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    if (!$batchId) {
        return ['success' => false, 'message' => 'Không thể lưu lô IPv6 vừa mua.'];
    }

    $records = youproxy_normalize_ip_records($providerOrder);
    if ($providerOrderId !== '') {
        $records = array_values(array_filter($records, function ($record) use ($providerOrderId) {
            $recordOrderId = youproxy_find_direct_value($record, ['orderId', 'orderID', 'orderNumber', 'providerOrderId', 'order_id']);
            return is_scalar($recordOrderId) && trim((string) $recordOrderId) === $providerOrderId;
        }));
    }
    if (count($records) < $batchQuantity && $providerOrderId !== '') {
        $listResponse = youproxy_ip_addresses('IPv6');
        if (!empty($listResponse['success'])) {
            $records = array_values(array_filter(youproxy_normalize_ip_records($listResponse), function ($record) use ($providerOrderId) {
                $recordOrderId = youproxy_find_direct_value($record, ['orderId', 'orderID', 'orderNumber', 'providerOrderId', 'order_id']);
                return is_scalar($recordOrderId) && trim((string) $recordOrderId) === $providerOrderId;
            }));
        }
    }

    $received = 0;
    $seen = [];
    foreach ($records as $record) {
        $recordType = strtoupper(trim((string) (youproxy_find_direct_value($record, ['proxyType', 'type']) ?: 'IPv6')));
        if ($recordType !== 'IPV6') {
            continue;
        }
        $providerIpId = trim((string) (youproxy_find_direct_value($record, ['ipAddressId', 'id']) ?: ''));
        if ($providerIpId === '' || isset($seen[$providerIpId])) {
            continue;
        }
        $seen[$providerIpId] = true;
        $dateStartValue = youproxy_find_first_value($record, ['dateStart', 'startDate']);
        $dateEndValue = youproxy_find_first_value($record, ['dateEnd', 'endDate']);
        $dateStart = $dateStartValue && strtotime((string) $dateStartValue) !== false ? date('Y-m-d H:i:s', strtotime((string) $dateStartValue)) : null;
        $dateEnd = $dateEndValue && strtotime((string) $dateEndValue) !== false ? date('Y-m-d H:i:s', strtotime((string) $dateEndValue)) : null;
        $inserted = $CMSNT->insert('proxy_ipv6_inventory', [
            'batch_id' => (int) $batchId,
            'provider_ip_id' => $providerIpId,
            'provider_order_id' => $providerOrderId !== '' ? $providerOrderId : null,
            'country' => (string) ($payload['country'] ?? ''),
            'protocol' => (string) ($payload['protocol'] ?? 'HTTP'),
            'auth_type' => (string) ($payload['authType'] ?? 'LOGIN'),
            'rent_period_days' => (int) ($payload['rentPeriodDays'] ?? 0),
            'retail_price' => round((float) $price['wallet_amount'] / $batchQuantity, 2),
            'date_start' => $dateStart,
            'date_end' => $dateEnd,
            'status' => $dateEnd === null ? 'pending_sync' : 'available',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        if ($inserted) {
            $received++;
        }
    }

    $status = $received >= $batchQuantity ? 'active' : 'pending_sync';
    $CMSNT->update('proxy_ipv6_batches', [
        'received_quantity' => $received,
        'status' => $status,
        'updated_at' => date('Y-m-d H:i:s')
    ], '`id` = ?', [(int) $batchId]);

    return [
        'success' => true,
        'batch_id' => (int) $batchId,
        'received_quantity' => $received,
        'expected_quantity' => $batchQuantity,
        'status' => $status
    ];
}

function youproxy_ipv6_retail_sync_batch($batchId)
{
    global $CMSNT;
    if (!isset($CMSNT) || !youproxy_ensure_tables()) {
        return ['success' => false, 'message' => 'Không thể truy cập kho IPv6.'];
    }

    $batch = $CMSNT->get_row_safe('SELECT * FROM `proxy_ipv6_batches` WHERE `id` = ? LIMIT 1', [(int) $batchId]);
    if (!$batch || empty($batch['provider_order_id'])) {
        return ['success' => false, 'message' => 'Lô IPv6 chưa có mã đơn nhà cung cấp để đồng bộ.'];
    }

    $listResponse = youproxy_ip_addresses('IPv6');
    if (empty($listResponse['success'])) {
        return ['success' => false, 'message' => youproxy_error_text($listResponse, 'Không thể đồng bộ danh sách IPv6 từ nhà cung cấp.')];
    }

    $records = array_values(array_filter(youproxy_normalize_ip_records($listResponse), function ($record) use ($batch) {
        $recordOrderId = youproxy_find_direct_value($record, ['orderId', 'orderID', 'orderNumber', 'providerOrderId', 'order_id']);
        $recordType = strtoupper(trim((string) (youproxy_find_direct_value($record, ['proxyType', 'type']) ?: 'IPv6')));
        return $recordType === 'IPV6' && is_scalar($recordOrderId) && trim((string) $recordOrderId) === (string) $batch['provider_order_id'];
    }));

    foreach ($records as $record) {
        $providerIpId = trim((string) (youproxy_find_direct_value($record, ['ipAddressId', 'id']) ?: ''));
        if ($providerIpId === '') {
            continue;
        }
        $dateStartValue = youproxy_find_first_value($record, ['dateStart', 'startDate']);
        $dateEndValue = youproxy_find_first_value($record, ['dateEnd', 'endDate']);
        $dateStart = $dateStartValue && strtotime((string) $dateStartValue) !== false ? date('Y-m-d H:i:s', strtotime((string) $dateStartValue)) : null;
        $dateEnd = $dateEndValue && strtotime((string) $dateEndValue) !== false ? date('Y-m-d H:i:s', strtotime((string) $dateEndValue)) : null;
        $existing = $CMSNT->get_row_safe('SELECT `id`, `status` FROM `proxy_ipv6_inventory` WHERE `provider_ip_id` = ? LIMIT 1', [$providerIpId]);
        if ($existing) {
            $updates = [
                'date_start' => $dateStart,
                'date_end' => $dateEnd,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            if (in_array((string) $existing['status'], ['pending_sync', 'available'], true)) {
                $updates['status'] = $dateEnd === null ? 'pending_sync' : 'available';
            }
            $CMSNT->update('proxy_ipv6_inventory', $updates, '`id` = ?', [(int) $existing['id']]);
            continue;
        }
        $CMSNT->insert('proxy_ipv6_inventory', [
            'batch_id' => (int) $batch['id'],
            'provider_ip_id' => $providerIpId,
            'provider_order_id' => (string) $batch['provider_order_id'],
            'country' => (string) $batch['country'],
            'protocol' => (string) $batch['protocol'],
            'auth_type' => (string) $batch['auth_type'],
            'rent_period_days' => (int) $batch['rent_period_days'],
            'retail_price' => (float) $batch['retail_unit_price'],
            'date_start' => $dateStart,
            'date_end' => $dateEnd,
            'status' => $dateEnd === null ? 'pending_sync' : 'available',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    $count = $CMSNT->get_row_safe('SELECT COUNT(*) AS total FROM `proxy_ipv6_inventory` WHERE `batch_id` = ?', [(int) $batch['id']]);
    $received = (int) ($count['total'] ?? 0);
    $status = $received >= max(1, (int) $batch['expected_quantity']) ? 'active' : 'pending_sync';
    $CMSNT->update('proxy_ipv6_batches', [
        'received_quantity' => $received,
        'status' => $status,
        'updated_at' => date('Y-m-d H:i:s')
    ], '`id` = ?', [(int) $batch['id']]);

    return [
        'success' => true,
        'batch_id' => (int) $batch['id'],
        'received_quantity' => $received,
        'expected_quantity' => (int) $batch['expected_quantity'],
        'status' => $status
    ];
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
    if ($CMSNT->query($sql) === false) {
        return false;
    }

    $batchSql = "CREATE TABLE IF NOT EXISTS `proxy_ipv6_batches` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `provider_order_id` VARCHAR(100) NULL,
        `country` VARCHAR(12) NOT NULL,
        `protocol` VARCHAR(10) NOT NULL,
        `auth_type` VARCHAR(10) NOT NULL,
        `rent_period_days` INT UNSIGNED NOT NULL,
        `goal` VARCHAR(200) NOT NULL,
        `expected_quantity` INT UNSIGNED NOT NULL DEFAULT 10,
        `received_quantity` INT UNSIGNED NOT NULL DEFAULT 0,
        `provider_price` DECIMAL(14,6) NOT NULL DEFAULT 0,
        `retail_unit_price` DECIMAL(18,2) NOT NULL DEFAULT 0,
        `provider_currency` VARCHAR(10) NOT NULL DEFAULT 'USD',
        `status` VARCHAR(20) NOT NULL DEFAULT 'pending_sync',
        `provider_payload` LONGTEXT NULL,
        `created_by` INT UNSIGNED NOT NULL,
        `created_at` DATETIME NOT NULL,
        `updated_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `proxy_ipv6_batches_status` (`status`),
        KEY `proxy_ipv6_batches_provider_order_id` (`provider_order_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if ($CMSNT->query($batchSql) === false) {
        return false;
    }

    $inventorySql = "CREATE TABLE IF NOT EXISTS `proxy_ipv6_inventory` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `batch_id` INT UNSIGNED NOT NULL,
        `provider_ip_id` VARCHAR(100) NOT NULL,
        `provider_order_id` VARCHAR(100) NULL,
        `country` VARCHAR(12) NOT NULL,
        `protocol` VARCHAR(10) NOT NULL,
        `auth_type` VARCHAR(10) NOT NULL,
        `rent_period_days` INT UNSIGNED NOT NULL,
        `retail_price` DECIMAL(18,2) NOT NULL DEFAULT 0,
        `date_start` DATETIME NULL,
        `date_end` DATETIME NULL,
        `status` VARCHAR(20) NOT NULL DEFAULT 'available',
        `reservation_token` VARCHAR(80) NULL,
        `reserved_until` DATETIME NULL,
        `customer_user_id` INT UNSIGNED NULL,
        `customer_proxy_order_id` INT UNSIGNED NULL,
        `created_at` DATETIME NOT NULL,
        `updated_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `proxy_ipv6_inventory_provider_ip_id` (`provider_ip_id`),
        KEY `proxy_ipv6_inventory_available` (`status`, `country`, `protocol`, `auth_type`, `rent_period_days`, `date_end`),
        KEY `proxy_ipv6_inventory_customer` (`customer_user_id`),
        KEY `proxy_ipv6_inventory_order` (`customer_proxy_order_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    return $CMSNT->query($inventorySql) !== false;
}
