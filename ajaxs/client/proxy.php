<?php

define('IN_SITE', true);
require_once dirname(__DIR__, 2) . '/libs/db.php';
require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/libs/lang.php';
require_once dirname(__DIR__, 2) . '/libs/helper.php';
require_once dirname(__DIR__, 2) . '/libs/database/users.php';
require_once dirname(__DIR__, 2) . '/libs/client-session.php';
require_once dirname(__DIR__, 2) . '/libs/youproxy.php';
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
    return hash('sha256', $config['base_url'] . '|' . $config['api_key']);
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

function proxy_metadata_fetch()
{
    $typeResponse = youproxy_proxy_types();
    if (!$typeResponse['success']) {
        return ['success' => false, 'message' => youproxy_error_text($typeResponse)];
    }

    $types = youproxy_response_options($typeResponse, ['proxyTypes', 'types', 'items'], ['proxyType', 'code'], ['name', 'title']);
    $availableTypes = [];
    $requests = ['mobile_operators' => ['endpoint' => 'mobileOperator', 'query' => []]];
    foreach ($types as $type) {
        $typeCode = strtoupper((string) $type['value']);
        if (!in_array($typeCode, ['IPV4', 'IPV6', 'MOBILE', 'ISP'], true)) {
            continue;
        }
        $providerType = proxy_type($typeCode);
        if ($providerType === false) {
            continue;
        }
        $availableTypes[$typeCode] = ['value' => $typeCode, 'label' => $type['label'], 'provider_type' => $providerType];
        $requests['countries_' . $typeCode] = ['endpoint' => 'country', 'query' => ['proxyType' => $providerType]];
        $requests['periods_' . $typeCode] = ['endpoint' => 'rentPeriod', 'query' => ['proxyType' => $providerType]];
    }

    $responses = youproxy_multi_get($requests);
    $result = ['types' => [], 'mobile_operators' => [], 'settings' => []];
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
    $goal = proxy_goal(proxy_value($input, 'goal'));
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
        'extend_days' => (int) (youproxy_find_first_value($extend, ['extendDays']) ?: 0)
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
    $rows = $CMSNT->get_list_safe('SELECT `id`, `user_id`, `proxy_type`, `country`, `quantity`, `rent_period_days`, `created_at`, `ip_address_ids` FROM `proxy_orders` WHERE `status` <> ? ORDER BY `created_at` ASC, `id` ASC', ['refunded']);
    if (empty($rows)) {
        return;
    }

    $claimedBy = [];
    $pending = [];
    foreach ($rows as $row) {
        $rowId = (int) ($row['id'] ?? 0);
        $storedIds = proxy_row_stored_ids($row);
        foreach ($storedIds as $id) {
            $claimedBy[$id] = $rowId;
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
        if (!isset($groups[$orderId])) {
            $groups[$orderId] = [];
        }
        $groups[$orderId][] = $record;
    }
    if (empty($groups)) {
        return;
    }

    foreach ($pending as $pendingOrder) {
        $row = $pendingOrder['row'];
        $rowId = (int) $row['id'];
        $quantity = max(1, (int) ($row['quantity'] ?? 1));
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
                if (isset($claimedBy[$recordId]) && $claimedBy[$recordId] !== $rowId) {
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
                $claimedBy[$id] = $rowId;
            }
        }
    }
}

function proxy_owned_access($userId)
{
    global $CMSNT;
    youproxy_ensure_tables();
    $rows = $CMSNT->get_list_safe('SELECT `provider_order_id`, `ip_address_ids`, `provider_payload` FROM `proxy_orders` WHERE `user_id` = ? AND `status` <> ?', [(int) $userId, 'refunded']);
    $ids = [];
    $orders = [];
    $records = [];
    foreach ($rows as $row) {
        if (!empty($row['provider_order_id'])) {
            $orders[] = (string) $row['provider_order_id'];
        }
        $storedIds = json_decode((string) ($row['ip_address_ids'] ?? ''), true);
        if (is_array($storedIds)) {
            foreach ($storedIds as $id) {
                if (is_scalar($id) && trim((string) $id) !== '') {
                    $ids[] = (string) $id;
                }
            }
        }
        $storedPayload = json_decode((string) ($row['provider_payload'] ?? ''), true);
        if (is_array($storedPayload)) {
            foreach (youproxy_normalize_ip_records($storedPayload) as $record) {
                $records[] = $record;
            }
        }
    }
    return [
        'ids' => array_values(array_unique($ids)),
        'orders' => array_values(array_unique($orders)),
        'records' => $records
    ];
}

function proxy_filter_owned_records($records, $userId)
{
    proxy_match_pending_orders($records);
    $access = proxy_owned_access($userId);
    $ownedIds = array_fill_keys($access['ids'], true);
    $ownedOrders = array_fill_keys($access['orders'], true);
    $matchesOwnership = function ($record) use ($ownedIds, $ownedOrders) {
        $id = (string) (youproxy_find_first_value($record, ['ipAddressId', 'id']) ?: '');
        $orderId = (string) (youproxy_find_first_value($record, ['orderId', 'orderID', 'orderNumber']) ?: '');
        return ($id !== '' && isset($ownedIds[$id])) || ($orderId !== '' && isset($ownedOrders[$orderId]));
    };
    $ownedRecords = array_values(array_filter($records, $matchesOwnership));
    $fallbackRecords = array_values(array_filter($access['records'], $matchesOwnership));
    $seen = [];
    $merged = [];
    foreach (array_merge($ownedRecords, $fallbackRecords) as $record) {
        $id = (string) (youproxy_find_first_value($record, ['ipAddressId', 'id']) ?: '');
        $orderId = (string) (youproxy_find_first_value($record, ['orderId', 'orderID', 'orderNumber']) ?: '');
        $key = $id !== '' ? 'id:' . $id : ($orderId !== '' ? 'order:' . $orderId : 'record:' . count($merged));
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
if (!youproxy_is_configured()) {
    proxy_json(['success' => false, 'message' => 'Dịch vụ proxy chưa sẵn sàng.'], 503);
}

if ($action === 'metadata') {
    $result = proxy_metadata_cache_read();
    if ($result !== null) {
        proxy_json(['success' => true, 'data' => $result]);
    }

    $metadata = proxy_metadata_fetch();
    if (empty($metadata['success'])) {
        proxy_json(['success' => false, 'message' => $metadata['message'] ?? 'Không thể tải cấu hình proxy.'], 502);
    }
    proxy_metadata_cache_write($metadata['data']);
    proxy_json(['success' => true, 'data' => $metadata['data']]);
}

if ($action === 'quote') {
    $validated = proxy_request_payload($input);
    if (isset($validated['error'])) {
        proxy_json(['success' => false, 'message' => $validated['error']], 422);
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
    $quote = youproxy_calculate_order($validated['payload']);
    if (!$quote['success']) {
        proxy_json(['success' => false, 'message' => youproxy_error_text($quote)], 422);
    }
    $price = youproxy_price_context($quote);
    if ($price['provider_price'] <= 0 || $price['wallet_amount'] <= 0) {
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

    $providerOrder = youproxy_create_order($validated['payload']);
    if (!$providerOrder['success']) {
        $userModel->RefundCredits($getUser['id'], $price['wallet_amount'], 'Hoàn tiền mua proxy do giao dịch lỗi', $transactionId . '_refund');
        proxy_json(['success' => false, 'message' => youproxy_error_text($providerOrder, 'Mua proxy thất bại, hệ thống đã hoàn tiền vào ví.')], 502);
    }

    $orderId = youproxy_extract_order_id($providerOrder);
    $records = youproxy_normalize_ip_records($providerOrder);
    $recordIds = [];
    foreach ($records as $record) {
        $id = youproxy_find_first_value($record, ['ipAddressId', 'id']);
        if ($id !== null && trim((string) $id) !== '') {
            $recordIds[] = (string) $id;
        }
    }
    $autoExtend = !empty($input['auto_extend']);
    $autoExtendWarning = '';
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
    $CMSNT->insert('proxy_orders', [
        'user_id' => (int) $getUser['id'],
        'provider_order_id' => $orderId !== '' ? $orderId : null,
        'proxy_type' => $validated['payload']['proxyType'],
        'country' => $validated['payload']['country'],
        'quantity' => $validated['payload']['quantity'],
        'rent_period_days' => $validated['payload']['rentPeriodDays'],
        'provider_price' => $price['provider_price'],
        'wallet_amount' => $price['wallet_amount'],
        'provider_currency' => $price['provider_currency'],
        'auto_extend' => $autoExtend ? 1 : 0,
        'status' => 'active',
        'ip_address_ids' => json_encode($recordIds),
        'provider_payload' => json_encode($providerOrder, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    $safeRecords = array_map('proxy_sanitized_record', $records);
    proxy_json([
        'success' => true,
        'message' => $autoExtendWarning !== '' ? $autoExtendWarning : 'Mua proxy thành công.',
        'data' => [
            'order_id' => $orderId,
            'records' => $safeRecords,
            'price' => proxy_price_response($quote),
            'wallet_balance' => (float) getUser($getUser['id'], 'money')
        ]
    ]);
}

if ($action === 'list') {
    $type = proxy_type(proxy_value($input, 'proxy_type'));
    $response = youproxy_ip_addresses($type ?: '');
    if (!$response['success']) {
        proxy_json(['success' => false, 'message' => youproxy_error_text($response)], 502);
    }
    $records = proxy_filter_owned_records(youproxy_normalize_ip_records($response), $getUser['id']);
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
    $listResponse = youproxy_ip_addresses($type);
    if (!$listResponse['success']) {
        proxy_json(['success' => false, 'message' => youproxy_error_text($listResponse)], 502);
    }
    $availableRecords = proxy_filter_owned_records(youproxy_normalize_ip_records($listResponse), $getUser['id']);
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
    $listResponse = youproxy_ip_addresses($type);
    if (!$listResponse['success']) {
        proxy_json(['success' => false, 'message' => youproxy_error_text($listResponse)], 502);
    }
    $allowedIds = proxy_records_for_ids(proxy_filter_owned_records(youproxy_normalize_ip_records($listResponse), $getUser['id']), $ids);
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
