<?php

define('IN_SITE', true);
require_once dirname(__DIR__, 2) . '/libs/db.php';
require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/libs/lang.php';
require_once dirname(__DIR__, 2) . '/libs/helper.php';
require_once dirname(__DIR__, 2) . '/libs/database/users.php';
require_once dirname(__DIR__, 2) . '/libs/client-session.php';
require_once dirname(__DIR__, 2) . '/libs/youproxy.php';
require_once dirname(__DIR__, 2) . '/libs/proxyline.php';
$CMSNT = new DB();
$getUser = client_optional_user($CMSNT);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function proxy_json($payload, $status = 200)
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function proxy_metadata_cache_path()
{
    return dirname(dirname(__DIR__, 2)) . '/.caffemmo-cache/youproxy-metadata.json';
}

function proxy_metadata_cache_key()
{
    $config = youproxy_config();
    $proxyline = proxyline_config();
    return hash('sha256', $config['base_url'] . '|' . $config['api_key'] . '|' . json_encode(youproxy_proxy_type_states()) . '|' . $proxyline['base_url'] . '|' . $proxyline['api_key'] . '|' . proxyline_ipv4_price_per_ip_day());
}

function proxy_metadata_cache_read()
{
    $path = proxy_metadata_cache_path();
    if (!is_file($path) || !is_readable($path)) {
        return null;
    }
    $payload = json_decode((string) @file_get_contents($path), true);
    if (!is_array($payload)
        || empty($payload['created_at'])
        || !isset($payload['config_key'])
        || !isset($payload['data'])
        || !is_array($payload['data'])) {
        return null;
    }
    if (!hash_equals((string) $payload['config_key'], proxy_metadata_cache_key())) {
        return null;
    }
    // Countries and rental periods rarely change, while prices remain live.
    if (time() - (int) $payload['created_at'] > 3600) {
        return null;
    }
    return $payload['data'];
}

function proxy_metadata_cache_write($data)
{
    $path = proxy_metadata_cache_path();
    $directory = dirname($path);
    if (!is_dir($directory) && !@mkdir($directory, 0755, true)) {
        return;
    }
    $payload = json_encode([
        'created_at' => time(),
        'config_key' => proxy_metadata_cache_key(),
        'data' => $data
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($payload === false) {
        return;
    }
    $temporary = $path . '.' . bin2hex(random_bytes(6)) . '.tmp';
    if (@file_put_contents($temporary, $payload, LOCK_EX) !== false) {
        @rename($temporary, $path);
    }
}

function proxy_metadata_filter_ipv6_inventory($data)
{
    global $CMSNT;
    if (!is_array($data) || !isset($data['types']['IPV6']['countries'])) {
        return $data;
    }

    $availableCountries = [];
    if (isset($CMSNT) && youproxy_ensure_tables()) {
        $rows = $CMSNT->get_list_safe(
            'SELECT DISTINCT `country` FROM `proxy_ipv6_inventory` WHERE `status` = ? AND `date_end` >= ?',
            ['available', date('Y-m-d H:i:s')]
        ) ?: [];
        foreach ($rows as $row) {
            $country = strtoupper(trim((string) ($row['country'] ?? '')));
            if ($country !== '') {
                $availableCountries[$country] = true;
            }
        }
    }

    $data['types']['IPV6']['countries'] = array_values(array_filter(
        (array) $data['types']['IPV6']['countries'],
        function ($country) use ($availableCountries) {
            if (!is_array($country)) {
                return false;
            }
            $value = strtoupper(trim((string) ($country['value'] ?? '')));
            return $value !== '' && isset($availableCountries[$value]);
        }
    ));
    return $data;
}

function proxy_metadata_fetch()
{
    $youproxyConfigured = youproxy_is_configured();
    $typeResponse = $youproxyConfigured ? youproxy_proxy_types() : ['success' => true, 'body' => []];
    if ($youproxyConfigured && !$typeResponse['success'] && !proxyline_is_configured()) {
        return ['success' => false, 'message' => youproxy_error_text($typeResponse)];
    }

    $types = $youproxyConfigured ? youproxy_response_options($typeResponse, ['proxyTypes', 'types', 'items'], ['proxyType', 'code'], ['name', 'title']) : [];
    $typeStates = youproxy_proxy_type_states();
    $availableTypes = [];
    $requests = $youproxyConfigured ? ['mobile_operators' => ['endpoint' => 'mobileOperator', 'query' => []]] : [];
    foreach ($types as $type) {
        $typeCode = strtoupper((string) $type['value']);
        if (!in_array($typeCode, ['IPV4', 'IPV6', 'MOBILE', 'ISP'], true)) {
            continue;
        }
        $providerType = proxy_type($typeCode);
        if ($providerType === false) {
            continue;
        }
        if (isset($typeStates[$typeCode]) && !$typeStates[$typeCode]['enabled']) {
            continue;
        }
        $availableTypes[$typeCode] = ['value' => $typeCode, 'label' => $type['label'], 'provider_type' => $providerType];
        $requests['countries_' . $typeCode] = ['endpoint' => 'country', 'query' => ['proxyType' => $providerType]];
        $requests['periods_' . $typeCode] = ['endpoint' => 'rentPeriod', 'query' => ['proxyType' => $providerType]];
    }

    $responses = !empty($requests) ? youproxy_multi_get($requests) : [];
    $result = ['types' => [], 'mobile_operators' => [], 'settings' => [], 'type_states' => $typeStates];
    foreach ($availableTypes as $typeCode => $type) {
        $countryResponse = $responses['countries_' . $typeCode] ?? ['success' => false];
        $rentResponse = $responses['periods_' . $typeCode] ?? ['success' => false];
        $result['types'][$typeCode] = [
            'value' => $typeCode,
            'label' => $type['label'],
            'countries' => !empty($countryResponse['success']) ? youproxy_response_options($countryResponse, ['countries', 'items'], ['alpha3code', 'alpha3', 'countryCode', 'code'], ['name', 'countryName', 'title']) : [],
            'rent_periods' => !empty($rentResponse['success']) ? youproxy_response_options($rentResponse, ['rentPeriodDays', 'rentPeriods', 'periods', 'items'], ['days', 'rentPeriodDays', 'value', 'code'], ['label', 'name', 'days', 'rentPeriodDays']) : []
        ];
    }
    $mobileResponse = $responses['mobile_operators'] ?? ['success' => false];
    if (!empty($mobileResponse['success'])) {
        $result['mobile_operators'] = youproxy_response_options($mobileResponse, ['mobileOperators', 'operators', 'items'], ['code', 'id', 'operator'], ['name', 'title', 'operator']);
    }

    if (proxyline_is_configured() && !empty($typeStates['IPV4']['enabled'])) {
        $countryResponse = proxyline_countries();
        $result['types']['IPV4'] = [
            'value' => 'IPV4',
            'label' => 'Proxy IPv4 Datacenter',
            'countries' => !empty($countryResponse['success']) ? proxyline_country_options($countryResponse) : [],
            'rent_periods' => proxyline_rent_period_options()
        ];
    }

    return ['success' => true, 'data' => $result];
}

function proxy_input()
{
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    return is_array($json) ? $json : $_POST;
}

function proxy_value($input, $key, $default = '')
{
    return isset($input[$key]) && is_scalar($input[$key]) ? trim((string) $input[$key]) : $default;
}

function proxy_type($value)
{
    $value = strtoupper(trim((string) $value));
    $types = [
        'IPV4' => 'IPv4',
        'IPV6' => 'IPv6',
        'MOBILE' => 'MOBILE',
        'ISP' => 'ISP'
    ];
    return isset($types[$value]) ? $types[$value] : false;
}

function proxy_country($value)
{
    $value = strtoupper(trim((string) $value));
    return preg_match('/^[A-Z0-9_-]{2,12}$/', $value) ? $value : false;
}

function proxy_positive_int($value, $min, $max)
{
    $value = filter_var($value, FILTER_VALIDATE_INT);
    return $value !== false && $value >= $min && $value <= $max ? $value : false;
}

function proxy_ids($value)
{
    if (!is_array($value)) {
        return [];
    }
    $ids = [];
    foreach (array_slice($value, 0, 500) as $item) {
        if (is_scalar($item) && preg_match('/^[a-zA-Z0-9_-]{1,100}$/', (string) $item)) {
            $ids[] = (string) $item;
        }
    }
    return array_values(array_unique($ids));
}

function proxy_goal($value)
{
    $value = trim((string) $value);
    return $value !== '' && mb_strlen($value) <= 200 ? $value : false;
}

function proxy_request_payload($input, $includeQuantity = true)
{
    $type = proxy_type(proxy_value($input, 'proxy_type'));
    $country = proxy_country(proxy_value($input, 'country'));
    $rent = proxy_positive_int(proxy_value($input, 'rent_period_days'), 1, 3650);
    $goal = proxy_goal(proxy_value($input, 'goal', 'Facebook'));
    $quantity = proxy_positive_int(proxy_value($input, 'quantity', '1'), 1, 100);
    $authType = strtoupper(proxy_value($input, 'auth_type', 'LOGIN'));
    $promoCode = proxy_value($input, 'promo_code');

    if ($type === false || $country === false || $rent === false || $goal === false || ($includeQuantity && $quantity === false)) {
        return ['error' => 'Vui lòng kiểm tra loại proxy, quốc gia, thời hạn và thông tin bắt buộc.'];
    }
    if (!in_array($authType, ['LOGIN', 'IP'], true)) {
        return ['error' => 'Kiểu xác thực không hợp lệ.'];
    }

    $payload = [
        'proxyType' => $type,
        'country' => $country,
        'rentPeriodDays' => $rent,
        'goal' => $goal,
        'authType' => $authType
    ];
    if ($includeQuantity) {
        $payload['quantity'] = $quantity;
    }
    if ($promoCode !== '') {
        $payload['promoCode'] = mb_substr($promoCode, 0, 60);
    }
    if ($authType === 'IP') {
        $authIp = trim(proxy_value($input, 'auth_ip'));
        if (!filter_var($authIp, FILTER_VALIDATE_IP)) {
            return ['error' => 'Vui lòng nhập một địa chỉ IP xác thực hợp lệ.'];
        }
        $payload['authIp'] = $authIp;
    }

    if ($type === 'IPv6') {
        if ($authType !== 'LOGIN') {
            return ['error' => 'IPv6 bán lẻ chỉ hỗ trợ xác thực Login / Password.'];
        }
        $protocol = strtoupper(proxy_value($input, 'protocol', 'HTTP'));
        if (!in_array($protocol, ['HTTP', 'SOCKS'], true)) {
            return ['error' => 'IPv6 cần chọn protocol HTTP hoặc SOCKS.'];
        }
        $payload['protocol'] = $protocol;
    }

    $mobileOperator = proxy_value($input, 'mobile_operator');
    $rotationTime = proxy_value($input, 'rotation_time');
    if ($type === 'MOBILE' && $mobileOperator !== '') {
        $payload['mobileOperator'] = mb_substr($mobileOperator, 0, 100);
    }
    if ($type === 'MOBILE' && $rotationTime !== '') {
        $rotationTimeNumber = proxy_positive_int($rotationTime, 1, 100000);
        if ($rotationTimeNumber === false) {
            return ['error' => 'Rotation time của Mobile không hợp lệ.'];
        }
        $payload['rotationTime'] = $rotationTimeNumber;
    }
    return ['payload' => $payload];
}

function proxy_provider_for_type($type)
{
    return $type === 'IPv4' && proxyline_is_configured() ? 'proxyline' : 'youproxy';
}

function proxy_record_provider($record)
{
    $provider = strtolower(trim((string) (youproxy_find_first_value($record, ['provider_code', 'provider']) ?: 'youproxy')));
    return in_array($provider, ['youproxy', 'proxyline'], true) ? $provider : 'youproxy';
}

function proxy_mark_records_provider($records, $provider)
{
    $provider = in_array($provider, ['youproxy', 'proxyline'], true) ? $provider : 'youproxy';
    foreach ($records as &$record) {
        if (!is_array($record)) {
            continue;
        }
        if (trim((string) ($record['provider_code'] ?? '')) === '') {
            $record['provider_code'] = $provider;
        }
    }
    unset($record);
    return $records;
}

function proxy_row_provider($row)
{
    $provider = strtolower(trim((string) ($row['provider_code'] ?? 'youproxy')));
    return in_array($provider, ['youproxy', 'proxyline'], true) ? $provider : 'youproxy';
}

function proxy_provider_key($provider, $value)
{
    $value = trim((string) $value);
    return $value === '' ? '' : proxy_row_provider(['provider_code' => $provider]) . ':' . $value;
}

function proxy_normalize_provider_records($payload, $provider)
{
    return $provider === 'proxyline'
        ? proxyline_normalize_records($payload)
        : youproxy_normalize_ip_records($payload);
}

function proxy_owned_provider_for_ids($userId, $ids)
{
    global $CMSNT;
    $ids = array_values(array_unique(array_filter(array_map('strval', (array) $ids))));
    if (empty($ids)) {
        return 'youproxy';
    }
    youproxy_ensure_tables();
    $rows = $CMSNT->get_list_safe('SELECT `provider_code`, `ip_address_ids` FROM `proxy_orders` WHERE `user_id` = ? AND `status` <> ?', [(int) $userId, 'refunded']);
    $providers = [];
    foreach ($rows as $row) {
        $storedIds = proxy_row_stored_ids($row);
        if (empty(array_intersect($ids, $storedIds))) {
            continue;
        }
        $providers[proxy_row_provider($row)] = true;
    }
    if (count($providers) === 1) {
        return (string) array_key_first($providers);
    }
    return count($providers) > 1 ? 'mixed' : 'youproxy';
}

function proxy_sanitized_record($record)
{
    $auth = isset($record['authInfo']) && is_array($record['authInfo']) ? $record['authInfo'] : [];
    $extend = isset($record['extendInfo']) && is_array($record['extendInfo']) ? $record['extendInfo'] : [];
    return [
        'id' => (string) (youproxy_find_first_value($record, ['ipAddressId', 'id']) ?: ''),
        'ip' => (string) (youproxy_find_first_value($record, ['ipAddressIp', 'ipAddress', 'ip']) ?: ''),
        'order_id' => (string) (youproxy_find_first_value($record, ['orderId', 'orderID', 'orderNumber']) ?: ''),
        'country' => (string) (youproxy_find_first_value($record, ['country', 'countryCode']) ?: ''),
        'proxy_type' => (string) (youproxy_find_first_value($record, ['proxyType', 'type']) ?: ''),
        'date_start' => (string) (youproxy_find_first_value($record, ['dateStart', 'startDate']) ?: ''),
        'date_end' => (string) (youproxy_find_first_value($record, ['dateEnd', 'endDate']) ?: ''),
        'https_port' => (string) (youproxy_find_first_value($record, ['httpsPort', 'port']) ?: ''),
        'socks5_port' => (string) (youproxy_find_first_value($record, ['socks5Port', 'socksPort']) ?: ''),
        'mobile_operator' => (string) (youproxy_find_first_value($record, ['mobileOperator']) ?: ''),
        'rotation_time' => (string) (youproxy_find_first_value($record, ['rotationTime']) ?: ''),
        'reboot_link' => (string) (youproxy_find_first_value($record, ['rebootLink']) ?: ''),
        'login' => (string) (youproxy_find_first_value($auth, ['login', 'username']) ?: ''),
        'password' => (string) (youproxy_find_first_value($auth, ['password']) ?: ''),
        'auto_extend' => (bool) (youproxy_find_first_value($extend, ['autoExtend']) ?: false),
        'extend_days' => (int) (youproxy_find_first_value($extend, ['extendDays']) ?: 0),
        'renewal_supported' => proxy_record_provider($record) !== 'proxyline'
    ];
}

function proxy_records_for_ids($records, $ids)
{
    $lookup = array_fill_keys($ids, true);
    $selected = [];
    foreach ($records as $record) {
        $id = (string) (youproxy_find_first_value($record, ['ipAddressId', 'id']) ?: '');
        if ($id !== '' && isset($lookup[$id])) {
            $selected[] = $id;
        }
    }
    return array_values(array_unique($selected));
}

function proxy_row_stored_ids($row)
{
    $storedIds = json_decode((string) ($row['ip_address_ids'] ?? ''), true);
    if (!is_array($storedIds)) {
        return [];
    }
    $ids = [];
    foreach ($storedIds as $id) {
        if (is_scalar($id) && trim((string) $id) !== '') {
            $ids[] = trim((string) $id);
        }
    }
    return array_values(array_unique($ids));
}

function proxy_record_id($record)
{
    return trim((string) (youproxy_find_first_value($record, ['ipAddressId', 'id']) ?: ''));
}

function proxy_record_order_id($record)
{
    return trim((string) (youproxy_find_first_value($record, ['orderId', 'orderID', 'orderNumber']) ?: ''));
}

function proxy_record_type($record)
{
    return strtoupper(trim((string) (youproxy_find_first_value($record, ['proxyType', 'type']) ?: '')));
}

function proxy_record_country_code($record)
{
    return strtoupper(trim((string) (youproxy_find_first_value($record, ['country', 'countryCode']) ?: '')));
}

function proxy_record_timestamp($record, $keys)
{
    $value = youproxy_find_first_value($record, $keys);
    $timestamp = $value !== null ? strtotime((string) $value) : false;
    return $timestamp !== false ? $timestamp : null;
}

function proxy_match_pending_orders($records)
{
    global $CMSNT;
    if (empty($records)) {
        return;
    }
    youproxy_ensure_tables();
    $rows = $CMSNT->get_list_safe('SELECT `id`, `user_id`, `provider_code`, `proxy_type`, `country`, `quantity`, `rent_period_days`, `created_at`, `ip_address_ids` FROM `proxy_orders` WHERE `status` <> ? ORDER BY `created_at` ASC, `id` ASC', ['refunded']);
    if (empty($rows)) {
        return;
    }

    $claimedBy = [];
    $pending = [];
    foreach ($rows as $row) {
        $rowId = (int) ($row['id'] ?? 0);
        $provider = proxy_row_provider($row);
        $storedIds = proxy_row_stored_ids($row);
        foreach ($storedIds as $id) {
            $claimedBy[proxy_provider_key($provider, $id)] = $rowId;
        }
        if ($rowId > 0 && count($storedIds) < max(1, (int) ($row['quantity'] ?? 1))) {
            $pending[] = ['row' => $row, 'stored_ids' => $storedIds];
        }
    }
    if (empty($pending)) {
        return;
    }

    $groups = [];
    foreach ($records as $record) {
        $recordId = proxy_record_id($record);
        $orderId = proxy_record_order_id($record);
        if ($recordId === '' || $orderId === '') {
            continue;
        }
        $groupKey = proxy_record_provider($record) . ':' . $orderId;
        if (!isset($groups[$groupKey])) {
            $groups[$groupKey] = [];
        }
        $groups[$groupKey][] = $record;
    }
    if (empty($groups)) {
        return;
    }

    foreach ($pending as $pendingOrder) {
        $row = $pendingOrder['row'];
        $rowId = (int) $row['id'];
        $quantity = max(1, (int) ($row['quantity'] ?? 1));
        $expectedProvider = proxy_row_provider($row);
        $expectedType = strtoupper(trim((string) ($row['proxy_type'] ?? '')));
        $expectedCountry = strtoupper(trim((string) ($row['country'] ?? '')));
        $expectedDays = max(1, (int) ($row['rent_period_days'] ?? 0));
        $createdAt = strtotime((string) ($row['created_at'] ?? ''));
        if ($createdAt === false) {
            continue;
        }

        $candidates = [];
        foreach ($groups as $orderId => $group) {
            if (count($group) !== $quantity) {
                continue;
            }
            $groupStart = null;
            $groupEnd = null;
            $validGroup = true;
            foreach ($group as $record) {
                $recordId = proxy_record_id($record);
                if (proxy_record_provider($record) !== $expectedProvider) {
                    $validGroup = false;
                    break;
                }
                if (isset($claimedBy[proxy_provider_key($expectedProvider, $recordId)]) && $claimedBy[proxy_provider_key($expectedProvider, $recordId)] !== $rowId) {
                    $validGroup = false;
                    break;
                }
                if ($expectedType !== '' && proxy_record_type($record) !== $expectedType) {
                    $validGroup = false;
                    break;
                }
                if ($expectedCountry !== '' && proxy_record_country_code($record) !== $expectedCountry) {
                    $validGroup = false;
                    break;
                }
                $start = proxy_record_timestamp($record, ['dateStart', 'startDate']);
                $end = proxy_record_timestamp($record, ['dateEnd', 'endDate']);
                if ($start === null || $end === null) {
                    $validGroup = false;
                    break;
                }
                $groupStart = $groupStart === null ? $start : min($groupStart, $start);
                $groupEnd = $groupEnd === null ? $end : max($groupEnd, $end);
            }
            if (!$validGroup || $groupStart === null || $groupEnd === null) {
                continue;
            }

            $durationDays = (int) round(($groupEnd - $groupStart) / 86400);
            if (abs($durationDays - $expectedDays) > 2) {
                continue;
            }
            $timeDistance = abs($groupStart - $createdAt);
            // Provider provisioning can be delayed, but a stale order must not
            // be silently assigned to a newer customer order.
            if ($timeDistance > 14 * 86400) {
                continue;
            }
            $candidates[] = [
                'order_id' => $orderId,
                'records' => $group,
                'time_distance' => $timeDistance
            ];
        }
        if (empty($candidates)) {
            continue;
        }
        usort($candidates, function ($left, $right) {
            return $left['time_distance'] <=> $right['time_distance'];
        });
        if (isset($candidates[1]) && ($candidates[1]['time_distance'] - $candidates[0]['time_distance']) < 120) {
            continue;
        }

        $matchedIds = array_map('proxy_record_id', $candidates[0]['records']);
        $mergedIds = array_values(array_unique(array_merge($pendingOrder['stored_ids'], $matchedIds)));
        if (count($mergedIds) < $quantity) {
            continue;
        }
        $updated = $CMSNT->update('proxy_orders', [
            'ip_address_ids' => json_encode($mergedIds, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at' => date('Y-m-d H:i:s')
        ], '`id` = ?', [$rowId]);
        if ($updated !== false) {
            foreach ($mergedIds as $id) {
                $claimedBy[proxy_provider_key($expectedProvider, $id)] = $rowId;
            }
        }
    }
}

function proxy_owned_access($userId)
{
    global $CMSNT;
    youproxy_ensure_tables();
    $rows = $CMSNT->get_list_safe('SELECT `provider_code`, `provider_order_id`, `ip_address_ids`, `provider_payload` FROM `proxy_orders` WHERE `user_id` = ? AND `status` <> ?', [(int) $userId, 'refunded']);
    $ids = [];
    $orders = [];
    $providerIds = [];
    $providerOrders = [];
    $records = [];
    foreach ($rows as $row) {
        $provider = proxy_row_provider($row);
        if (!empty($row['provider_order_id'])) {
            $orderId = (string) $row['provider_order_id'];
            $orders[] = $orderId;
            $providerOrders[] = proxy_provider_key($provider, $orderId);
        }
        $storedIds = json_decode((string) ($row['ip_address_ids'] ?? ''), true);
        if (is_array($storedIds)) {
            foreach ($storedIds as $id) {
                if (is_scalar($id) && trim((string) $id) !== '') {
                    $id = (string) $id;
                    $ids[] = $id;
                    $providerIds[] = proxy_provider_key($provider, $id);
                }
            }
        }
        $storedPayload = json_decode((string) ($row['provider_payload'] ?? ''), true);
        if (is_array($storedPayload)) {
            foreach (proxy_mark_records_provider(proxy_normalize_provider_records($storedPayload, $provider), $provider) as $record) {
                $records[] = $record;
            }
        }
    }
    return [
        'ids' => array_values(array_unique($ids)),
        'orders' => array_values(array_unique($orders)),
        'provider_ids' => array_values(array_unique($providerIds)),
        'provider_orders' => array_values(array_unique($providerOrders)),
        'records' => $records
    ];
}

function proxy_filter_owned_records($records, $userId)
{
    proxy_match_pending_orders($records);
    $access = proxy_owned_access($userId);
    $ownedIds = array_fill_keys($access['ids'], true);
    $ownedOrders = array_fill_keys($access['orders'], true);
    $ownedProviderIds = array_fill_keys($access['provider_ids'], true);
    $ownedProviderOrders = array_fill_keys($access['provider_orders'], true);
    $matchesOwnership = function ($record) use ($ownedIds, $ownedOrders, $ownedProviderIds, $ownedProviderOrders) {
        $id = (string) (youproxy_find_first_value($record, ['ipAddressId', 'id']) ?: '');
        $orderId = (string) (youproxy_find_first_value($record, ['orderId', 'orderID', 'orderNumber']) ?: '');
        $provider = proxy_record_provider($record);
        return ($id !== '' && isset($ownedProviderIds[proxy_provider_key($provider, $id)]))
            || ($orderId !== '' && isset($ownedProviderOrders[proxy_provider_key($provider, $orderId)]))
            || ($provider === 'youproxy' && (($id !== '' && isset($ownedIds[$id])) || ($orderId !== '' && isset($ownedOrders[$orderId]))));
    };
    $ownedRecords = array_values(array_filter($records, $matchesOwnership));
    $fallbackRecords = array_values(array_filter($access['records'], $matchesOwnership));
    $seen = [];
    $merged = [];
    foreach (array_merge($ownedRecords, $fallbackRecords) as $record) {
        $id = (string) (youproxy_find_first_value($record, ['ipAddressId', 'id']) ?: '');
        $orderId = (string) (youproxy_find_first_value($record, ['orderId', 'orderID', 'orderNumber']) ?: '');
        $provider = proxy_record_provider($record);
        $key = $id !== '' ? 'id:' . proxy_provider_key($provider, $id) : ($orderId !== '' ? 'order:' . proxy_provider_key($provider, $orderId) : 'record:' . count($merged));
        if (!isset($seen[$key])) {
            $seen[$key] = true;
            $merged[] = $record;
        }
    }
    return $merged;
}

function proxy_price_response($providerResponse)
{
    $price = youproxy_price_context($providerResponse);
    return [
        'wallet_amount' => $price['wallet_amount'],
        'wallet_label' => $price['wallet_label']
    ];
}

function proxy_ipv6_retail_price_response($items)
{
    $amount = 0;
    foreach ($items as $item) {
        $amount += max(0, (float) ($item['retail_price'] ?? 0));
    }
    $amount = round($amount, 2);
    return [
        'wallet_amount' => $amount,
        'wallet_label' => function_exists('format_currency') ? format_currency($amount) : number_format($amount, 0, ',', '.') . 'd'
    ];
}

function proxy_ipv6_retail_quote($payload)
{
    $quantity = max(1, (int) ($payload['quantity'] ?? 1));
    $items = youproxy_ipv6_retail_available_items($payload, $quantity);
    if (count($items) !== $quantity) {
        return [
            'success' => false,
            'message' => 'Kho IPv6 hiện không đủ số lượng hoặc thời hạn bạn đã chọn. Vui lòng giảm số lượng, đổi cấu hình hoặc thử lại sau.'
        ];
    }
    return ['success' => true, 'data' => proxy_ipv6_retail_price_response($items)];
}

function proxy_ipv6_retail_purchase($payload, $input, $getUser)
{
    global $CMSNT;

    if (!youproxy_ensure_tables()) {
        proxy_json(['success' => false, 'message' => 'Không thể chuẩn bị kho IPv6. Vui lòng thử lại sau.'], 500);
    }

    $quantity = max(1, (int) ($payload['quantity'] ?? 1));
    try {
        $reservationToken = bin2hex(random_bytes(24));
    } catch (Exception $exception) {
        proxy_json(['success' => false, 'message' => 'Không thể khởi tạo đơn mua IPv6. Vui lòng thử lại.'], 500);
    }

    $items = youproxy_ipv6_retail_reserve_items($payload, $quantity, $reservationToken);
    if (count($items) !== $quantity) {
        proxy_json(['success' => false, 'message' => 'Kho IPv6 vừa thay đổi, vui lòng tải lại giá và thử lại.'], 409);
    }

    $price = proxy_ipv6_retail_price_response($items);
    if ((float) $price['wallet_amount'] <= 0) {
        youproxy_ipv6_retail_release_items($reservationToken);
        proxy_json(['success' => false, 'message' => 'Giá IPv6 trong kho chưa hợp lệ. Vui lòng liên hệ hỗ trợ.'], 502);
    }
    if ((float) $getUser['money'] < (float) $price['wallet_amount']) {
        youproxy_ipv6_retail_release_items($reservationToken);
        proxy_json(['success' => false, 'message' => 'Số dư ví không đủ. Vui lòng nạp thêm tiền trước khi mua proxy.'], 422);
    }

    $transactionId = 'proxy_ipv6_' . bin2hex(random_bytes(8));
    $userModel = new users();
    if (!$userModel->RemoveCredits($getUser['id'], $price['wallet_amount'], 'Mua IPv6 lẻ (' . $quantity . ' IP)', $transactionId)) {
        youproxy_ipv6_retail_release_items($reservationToken);
        proxy_json(['success' => false, 'message' => 'Không thể trừ tiền trong ví, vui lòng thử lại.'], 500);
    }

    $ipAddressIds = array_values(array_unique(array_filter(array_map(function ($item) {
        return trim((string) ($item['provider_ip_id'] ?? ''));
    }, $items))));
    if (count($ipAddressIds) !== $quantity) {
        $userModel->RefundCredits($getUser['id'], $price['wallet_amount'], 'Hoàn tiền mua IPv6 do kho không hợp lệ', $transactionId . '_refund');
        youproxy_ipv6_retail_release_items($reservationToken);
        proxy_json(['success' => false, 'message' => 'Kho IPv6 không hợp lệ. Hệ thống đã hoàn tiền vào ví.'], 500);
    }

    $customerOrderId = $CMSNT->insert('proxy_orders', [
        'user_id' => (int) $getUser['id'],
        'provider_order_id' => null,
        'proxy_type' => 'IPv6',
        'country' => $payload['country'],
        'quantity' => $quantity,
        'rent_period_days' => $payload['rentPeriodDays'],
        'provider_price' => 0,
        'wallet_amount' => $price['wallet_amount'],
        'provider_currency' => 'VND',
        'auto_extend' => 0,
        'status' => 'active',
        'ip_address_ids' => json_encode($ipAddressIds, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'provider_payload' => json_encode([
            'source' => 'ipv6_retail_inventory',
            'inventory_item_ids' => array_map(function ($item) {
                return (int) ($item['id'] ?? 0);
            }, $items)
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    if (!$customerOrderId) {
        $userModel->RefundCredits($getUser['id'], $price['wallet_amount'], 'Hoàn tiền mua IPv6 do không thể tạo đơn', $transactionId . '_refund');
        youproxy_ipv6_retail_release_items($reservationToken);
        proxy_json(['success' => false, 'message' => 'Không thể tạo đơn IPv6. Hệ thống đã hoàn tiền vào ví.'], 500);
    }

    if (!youproxy_ipv6_retail_allocate_items($reservationToken, $getUser['id'], $customerOrderId, $quantity)) {
        youproxy_ipv6_retail_revert_allocation($getUser['id'], $customerOrderId);
        youproxy_ipv6_retail_release_items($reservationToken);
        $CMSNT->update('proxy_orders', ['status' => 'refunded', 'updated_at' => date('Y-m-d H:i:s')], '`id` = ?', [(int) $customerOrderId]);
        $userModel->RefundCredits($getUser['id'], $price['wallet_amount'], 'Hoàn tiền mua IPv6 do cấp phát không thành công', $transactionId . '_refund');
        proxy_json(['success' => false, 'message' => 'Không thể cấp IPv6. Hệ thống đã hoàn tiền vào ví.'], 500);
    }

    $autoExtend = !empty($input['auto_extend']);
    $autoExtendWarning = '';
    if ($autoExtend) {
        $autoResponse = youproxy_auto_extend([
            'proxyType' => 'IPv6',
            'ipAddressIds' => $ipAddressIds,
            'rentPeriodDays' => $payload['rentPeriodDays'],
            'autoExtend' => true
        ]);
        if (!$autoResponse['success']) {
            $autoExtend = false;
            $autoExtendWarning = 'IPv6 đã được cấp nhưng chưa bật được tự động gia hạn.';
        }
    }
    if ($autoExtend) {
        $CMSNT->update('proxy_orders', ['auto_extend' => 1, 'updated_at' => date('Y-m-d H:i:s')], '`id` = ?', [(int) $customerOrderId]);
    }

    queueServiceOrderNotification(
        $getUser,
        'Mua Proxy IPv6',
        $price['wallet_amount'],
        (string) $customerOrderId,
        $quantity,
        $payload['country'] . ' | ' . $payload['rentPeriodDays'] . ' ngày',
        ['source' => 'proxy_ipv6_purchase']
    );

    proxy_json([
        'success' => true,
        'message' => $autoExtendWarning !== '' ? $autoExtendWarning : 'Đã cấp ' . $quantity . ' IPv6 từ kho.',
        'data' => [
            'order_id' => (string) $customerOrderId,
            'records' => [],
            'price' => $price,
            'wallet_balance' => (float) getUser($getUser['id'], 'money')
        ]
    ]);
}

$input = proxy_input();
$action = proxy_value($input, 'action');
if (!in_array($action, ['metadata', 'quote'], true) && (!isset($getUser) || !is_array($getUser))) {
    proxy_json(['success' => false, 'message' => 'Vui lòng đăng nhập để sử dụng dịch vụ proxy.'], 401);
}

$tokenMatchesSession = !empty($getUser['token'])
    && hash_equals((string) $getUser['token'], proxy_value($input, 'token'));
if (!in_array($action, ['metadata', 'quote'], true) && !$tokenMatchesSession) {
    proxy_json(['success' => false, 'message' => 'Phiên làm việc không hợp lệ, vui lòng tải lại trang.'], 419);
}
if (!youproxy_is_configured() && !proxyline_is_configured()) {
    proxy_json(['success' => false, 'message' => 'Dịch vụ proxy chưa sẵn sàng.'], 503);
}

if ($action === 'metadata') {
    $result = proxy_metadata_cache_read();
    if ($result !== null) {
        proxy_json(['success' => true, 'data' => proxy_metadata_filter_ipv6_inventory($result)]);
    }

    $metadata = proxy_metadata_fetch();
    if (empty($metadata['success'])) {
        proxy_json(['success' => false, 'message' => $metadata['message'] ?? 'Không thể tải cấu hình proxy.'], 502);
    }
    proxy_metadata_cache_write($metadata['data']);
    proxy_json(['success' => true, 'data' => proxy_metadata_filter_ipv6_inventory($metadata['data'])]);
}

if ($action === 'quote') {
    $validated = proxy_request_payload($input);
    if (isset($validated['error'])) {
        proxy_json(['success' => false, 'message' => $validated['error']], 422);
    }
    if (!youproxy_proxy_type_enabled($validated['payload']['proxyType'])) {
        proxy_json(['success' => false, 'message' => 'Loại proxy này đang tạm ngưng mở bán.'], 503);
    }
    if (proxy_provider_for_type($validated['payload']['proxyType']) === 'proxyline') {
        $proxylineQuote = proxyline_ipv4_quote($validated['payload']);
        if (empty($proxylineQuote['success'])) {
            proxy_json(['success' => false, 'message' => $proxylineQuote['message'] ?? 'Không thể báo giá IPv4.'], 422);
        }
        proxy_json(['success' => true, 'data' => $proxylineQuote['data']]);
    }
    if ($validated['payload']['proxyType'] === 'IPv6') {
        $retailQuote = proxy_ipv6_retail_quote($validated['payload']);
        if (empty($retailQuote['success'])) {
            proxy_json(['success' => false, 'message' => $retailQuote['message'] ?? 'Không thể báo giá IPv6 từ kho.'], 422);
        }
        proxy_json(['success' => true, 'data' => $retailQuote['data']]);
    }
    $quote = youproxy_calculate_order($validated['payload']);
    if (!$quote['success']) {
        proxy_json(['success' => false, 'message' => youproxy_error_text($quote)], 422);
    }
    proxy_json(['success' => true, 'data' => proxy_price_response($quote)]);
}

if ($action === 'buy') {
    $validated = proxy_request_payload($input);
    if (isset($validated['error'])) {
        proxy_json(['success' => false, 'message' => $validated['error']], 422);
    }
    if (!youproxy_proxy_type_enabled($validated['payload']['proxyType'])) {
        proxy_json(['success' => false, 'message' => 'Loại proxy này đang tạm ngưng mở bán.'], 503);
    }
    if ($validated['payload']['proxyType'] === 'IPv6') {
        proxy_ipv6_retail_purchase($validated['payload'], $input, $getUser);
    }
    $providerCode = proxy_provider_for_type($validated['payload']['proxyType']);
    if ($providerCode === 'proxyline') {
        $quote = proxyline_ipv4_quote($validated['payload']);
        if (empty($quote['success'])) {
            proxy_json(['success' => false, 'message' => $quote['message'] ?? 'Không thể tạo báo giá IPv4.'], 422);
        }
        $price = $quote['price'];
    } else {
        $quote = youproxy_calculate_order($validated['payload']);
        if (!$quote['success']) {
            proxy_json(['success' => false, 'message' => youproxy_error_text($quote)], 422);
        }
        $price = youproxy_price_context($quote);
    }
    if ($price['wallet_amount'] <= 0 || ($providerCode !== 'proxyline' && $price['provider_price'] <= 0)) {
        proxy_json(['success' => false, 'message' => 'Dịch vụ proxy chưa trả về giá hợp lệ cho cấu hình này.'], 502);
    }
    if ((float) $getUser['money'] < $price['wallet_amount']) {
        proxy_json(['success' => false, 'message' => 'Số dư ví không đủ. Vui lòng nạp thêm tiền trước khi mua proxy.'], 422);
    }

    youproxy_ensure_tables();
    $transactionId = 'proxy_' . bin2hex(random_bytes(8));
    $debitReason = 'Mua proxy ' . $validated['payload']['proxyType'] . ' (' . $validated['payload']['quantity'] . ' IP)';
    $userModel = new users();
    if (!$userModel->RemoveCredits($getUser['id'], $price['wallet_amount'], $debitReason, $transactionId)) {
        proxy_json(['success' => false, 'message' => 'Không thể trừ tiền trong ví, vui lòng thử lại.'], 500);
    }

    $providerOrder = $providerCode === 'proxyline'
        ? proxyline_new_order($validated['payload'])
        : youproxy_create_order($validated['payload']);
    if (!$providerOrder['success']) {
        if ($providerCode === 'proxyline') {
            $userModel->RefundCredits($getUser['id'], $price['wallet_amount'], 'Hoàn tiền mua proxy do giao dịch lỗi', $transactionId . '_refund');
            proxy_json(['success' => false, 'message' => 'Mua proxy thất bại, hệ thống đã hoàn tiền vào ví.'], 502);
        }
        $userModel->RefundCredits($getUser['id'], $price['wallet_amount'], 'Hoàn tiền mua proxy do giao dịch lỗi', $transactionId . '_refund');
        proxy_json(['success' => false, 'message' => youproxy_error_text($providerOrder, 'Mua proxy thất bại, hệ thống đã hoàn tiền vào ví.')], 502);
    }

    $orderId = $providerCode === 'proxyline' ? proxyline_extract_order_id($providerOrder) : youproxy_extract_order_id($providerOrder);
    $records = proxy_mark_records_provider(proxy_normalize_provider_records($providerOrder, $providerCode), $providerCode);
    $recordIds = [];
    foreach ($records as $record) {
        $id = youproxy_find_first_value($record, ['ipAddressId', 'id']);
        if ($id !== null && trim((string) $id) !== '') {
            $recordIds[] = (string) $id;
        }
    }
    $autoExtend = !empty($input['auto_extend']);
    $autoExtendWarning = '';
    if ($autoExtend && $providerCode === 'proxyline') {
        $autoExtend = false;
        $autoExtendWarning = 'Đơn đã tạo nhưng chưa bật được tự động gia hạn.';
    }
    if ($autoExtend && $orderId !== '') {
        $autoResponse = youproxy_auto_extend([
            'proxyType' => $validated['payload']['proxyType'],
            'orderIds' => [$orderId],
            'rentPeriodDays' => $validated['payload']['rentPeriodDays'],
            'autoExtend' => true
        ], true);
        if (!$autoResponse['success']) {
            $autoExtendWarning = 'Đơn đã tạo nhưng chưa bật được tự động gia hạn.';
            $autoExtend = false;
        }
    }

    global $CMSNT;
    $customerOrderId = $CMSNT->insert('proxy_orders', [
        'user_id' => (int) $getUser['id'],
        'provider_order_id' => $orderId !== '' ? $orderId : null,
        'provider_code' => $providerCode,
        'proxy_type' => $validated['payload']['proxyType'],
        'country' => $validated['payload']['country'],
        'quantity' => $validated['payload']['quantity'],
        'rent_period_days' => $validated['payload']['rentPeriodDays'],
        'provider_price' => $price['provider_price'],
        'provider_cost_vnd' => $providerCode === 'proxyline' ? 0 : youproxy_provider_cost_vnd($price),
        'wallet_amount' => $price['wallet_amount'],
        'provider_currency' => $price['provider_currency'],
        'auto_extend' => $autoExtend ? 1 : 0,
        'status' => 'active',
        'ip_address_ids' => json_encode($recordIds),
        'provider_payload' => json_encode($providerOrder, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    queueServiceOrderNotification(
        $getUser,
        'Mua Proxy ' . $validated['payload']['proxyType'],
        $price['wallet_amount'],
        $orderId !== '' ? $orderId : $transactionId,
        $validated['payload']['quantity'],
        $validated['payload']['country'] . ' | ' . $validated['payload']['rentPeriodDays'] . ' ngày',
        ['source' => 'proxy_purchase', 'customer_order_id' => $customerOrderId ?: null]
    );
    $safeRecords = array_map('proxy_sanitized_record', $records);
    proxy_json([
        'success' => true,
        'message' => $autoExtendWarning !== '' ? $autoExtendWarning : 'Mua proxy thành công.',
        'data' => [
            'order_id' => $orderId,
            'records' => $safeRecords,
            'price' => $providerCode === 'proxyline' ? $quote['data'] : proxy_price_response($quote),
            'wallet_balance' => (float) getUser($getUser['id'], 'money')
        ]
    ]);
}

if ($action === 'list') {
    $type = proxy_type(proxy_value($input, 'proxy_type'));
    $records = [];
    $providerSucceeded = false;
    if (($type === false || $type === 'IPv4') && proxyline_is_configured()) {
        $proxylineResponse = proxyline_proxies();
        if (!empty($proxylineResponse['success'])) {
            $records = array_merge($records, proxy_mark_records_provider(proxyline_normalize_records($proxylineResponse), 'proxyline'));
            $providerSucceeded = true;
        }
    }
    if (youproxy_is_configured()) {
        $youproxyResponse = youproxy_ip_addresses($type ?: '');
        if (!empty($youproxyResponse['success'])) {
            $records = array_merge($records, proxy_mark_records_provider(youproxy_normalize_ip_records($youproxyResponse), 'youproxy'));
            $providerSucceeded = true;
        }
    }
    $response = ['success' => $providerSucceeded, 'body' => []];
    if (!$response['success']) {
        proxy_json(['success' => false, 'message' => youproxy_error_text($response)], 502);
    }
    $records = proxy_filter_owned_records($records, $getUser['id']);
    $now = time();
    $active = 0;
    $expiring = 0;
    foreach ($records as $record) {
        $dateEnd = youproxy_find_first_value($record, ['dateEnd', 'endDate']);
        if ($dateEnd && strtotime((string) $dateEnd) >= $now) {
            $active++;
            if (strtotime((string) $dateEnd) <= $now + (7 * 86400)) {
                $expiring++;
            }
        }
    }
    proxy_json(['success' => true, 'data' => [
        'records' => array_map('proxy_sanitized_record', $records),
        'stats' => ['total' => count($records), 'active' => $active, 'expiring' => $expiring, 'status_label' => 'Sẵn sàng']
    ]]);
}

if ($action === 'renew_quote' || $action === 'renew') {
    $type = proxy_type(proxy_value($input, 'proxy_type'));
    $rent = proxy_positive_int(proxy_value($input, 'rent_period_days'), 1, 3650);
    $ids = proxy_ids($input['ip_address_ids'] ?? []);
    $promoCode = proxy_value($input, 'promo_code');
    if ($type === false || $rent === false || empty($ids)) {
        proxy_json(['success' => false, 'message' => 'Vui lòng chọn proxy và thời hạn gia hạn hợp lệ.'], 422);
    }
    $providerCode = proxy_owned_provider_for_ids($getUser['id'], $ids);
    if ($providerCode === 'mixed') {
        proxy_json(['success' => false, 'message' => 'Vui lòng chỉ chọn proxy cùng một nhóm để gia hạn.'], 422);
    }
    if ($providerCode === 'proxyline') {
        proxy_json(['success' => false, 'message' => 'Chức năng gia hạn proxy đang được cập nhật, vui lòng liên hệ hỗ trợ.'], 503);
    }
    $listResponse = youproxy_ip_addresses($type);
    if (!$listResponse['success']) {
        proxy_json(['success' => false, 'message' => youproxy_error_text($listResponse)], 502);
    }
    $availableRecords = proxy_filter_owned_records(proxy_mark_records_provider(youproxy_normalize_ip_records($listResponse), 'youproxy'), $getUser['id']);
    $allowedIds = proxy_records_for_ids($availableRecords, $ids);
    if (count($allowedIds) !== count($ids)) {
        proxy_json(['success' => false, 'message' => 'Một hoặc nhiều proxy không thuộc tài khoản của bạn.'], 403);
    }
    $payload = ['proxyType' => $type, 'ipAddressIds' => $allowedIds, 'rentPeriodDays' => $rent];
    if ($promoCode !== '') {
        $payload['promoCode'] = mb_substr($promoCode, 0, 60);
    }
    $quote = youproxy_calculate_extend($payload);
    if (!$quote['success']) {
        proxy_json(['success' => false, 'message' => youproxy_error_text($quote)], 422);
    }
    $priceContext = youproxy_price_context($quote);
    $price = proxy_price_response($quote);
    if ($action === 'renew_quote') {
        proxy_json(['success' => true, 'data' => $price]);
    }
    if ((float) $getUser['money'] < (float) $priceContext['wallet_amount']) {
        proxy_json(['success' => false, 'message' => 'Số dư ví không đủ để gia hạn nhóm proxy đã chọn.'], 422);
    }
    if ((float) $priceContext['provider_price'] <= 0 || (float) $priceContext['wallet_amount'] <= 0) {
        proxy_json(['success' => false, 'message' => 'Dịch vụ proxy chưa trả về giá gia hạn hợp lệ.'], 502);
    }
    $transactionId = 'proxy_extend_' . bin2hex(random_bytes(8));
    $userModel = new users();
    if (!$userModel->RemoveCredits($getUser['id'], $priceContext['wallet_amount'], 'Gia hạn proxy (' . count($allowedIds) . ' IP)', $transactionId)) {
        proxy_json(['success' => false, 'message' => 'Không thể trừ tiền trong ví, vui lòng thử lại.'], 500);
    }
    $extendResponse = youproxy_extend($payload);
    if (!$extendResponse['success']) {
        $userModel->RefundCredits($getUser['id'], $priceContext['wallet_amount'], 'Hoàn tiền gia hạn proxy do giao dịch lỗi', $transactionId . '_refund');
        proxy_json(['success' => false, 'message' => youproxy_error_text($extendResponse, 'Gia hạn thất bại, hệ thống đã hoàn tiền vào ví.')], 502);
    }
    queueServiceOrderNotification(
        $getUser,
        'Gia hạn Proxy ' . $type,
        $priceContext['wallet_amount'],
        $transactionId,
        count($allowedIds),
        $rent . ' ngày',
        ['source' => 'proxy_renew']
    );
    proxy_json(['success' => true, 'message' => 'Gia hạn proxy thành công.', 'data' => [
        'price' => $price,
        'wallet_balance' => (float) getUser($getUser['id'], 'money')
    ]]);
}

if ($action === 'auto_extend') {
    $type = proxy_type(proxy_value($input, 'proxy_type'));
    $rent = proxy_positive_int(proxy_value($input, 'rent_period_days', '30'), 1, 3650);
    $ids = proxy_ids($input['ip_address_ids'] ?? []);
    $enabled = !empty($input['auto_extend']);
    if ($type === false || $rent === false || empty($ids)) {
        proxy_json(['success' => false, 'message' => 'Vui lòng chọn proxy hợp lệ.'], 422);
    }
    $providerCode = proxy_owned_provider_for_ids($getUser['id'], $ids);
    if ($providerCode === 'mixed') {
        proxy_json(['success' => false, 'message' => 'Vui lòng chỉ chọn proxy cùng một nhóm để gia hạn.'], 422);
    }
    if ($providerCode === 'proxyline') {
        proxy_json(['success' => false, 'message' => 'Chức năng gia hạn proxy đang được cập nhật, vui lòng liên hệ hỗ trợ.'], 503);
    }
    $listResponse = youproxy_ip_addresses($type);
    if (!$listResponse['success']) {
        proxy_json(['success' => false, 'message' => youproxy_error_text($listResponse)], 502);
    }
    $allowedIds = proxy_records_for_ids(proxy_filter_owned_records(proxy_mark_records_provider(youproxy_normalize_ip_records($listResponse), 'youproxy'), $getUser['id']), $ids);
    if (count($allowedIds) !== count($ids)) {
        proxy_json(['success' => false, 'message' => 'Một hoặc nhiều proxy không thuộc tài khoản của bạn.'], 403);
    }
    $autoResponse = youproxy_auto_extend([
        'proxyType' => $type,
        'ipAddressIds' => $allowedIds,
        'rentPeriodDays' => $rent,
        'autoExtend' => $enabled
    ]);
    if (!$autoResponse['success']) {
        proxy_json(['success' => false, 'message' => youproxy_error_text($autoResponse)], 422);
    }
    proxy_json(['success' => true, 'message' => $enabled ? 'Đã bật tự động gia hạn.' : 'Đã tắt tự động gia hạn.']);
}

proxy_json(['success' => false, 'message' => 'Thao tác proxy không hợp lệ.'], 400);
