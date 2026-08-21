<?php

define('IN_SITE', true);
require_once dirname(__DIR__, 2) . '/libs/db.php';
require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/libs/lang.php';
require_once dirname(__DIR__, 2) . '/libs/helper.php';
require_once dirname(__DIR__, 2) . '/libs/database/users.php';
require_once dirname(__DIR__, 2) . '/libs/client-session.php';
require_once dirname(__DIR__, 2) . '/libs/social-buff.php';

$CMSNT = new DB();
$userModel = new users();
$getUser = client_optional_user($CMSNT);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function social_buff_json($payload, $status = 200)
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function social_buff_input()
{
    if (!empty($_POST)) {
        return $_POST;
    }
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    return is_array($json) ? $json : [];
}

function social_buff_input_value($input, $key, $fallback = '')
{
    return isset($input[$key]) && is_scalar($input[$key]) ? trim((string) $input[$key]) : $fallback;
}

function social_buff_same_origin_post()
{
    if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
        return true;
    }
    if (strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) !== 'xmlhttprequest') {
        return false;
    }

    $host = strtolower((string) preg_replace('/:\\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));
    foreach (['HTTP_ORIGIN', 'HTTP_REFERER'] as $header) {
        if (empty($_SERVER[$header])) {
            continue;
        }
        $sourceHost = strtolower((string) parse_url((string) $_SERVER[$header], PHP_URL_HOST));
        if ($sourceHost === '' || $sourceHost !== $host) {
            return false;
        }
    }
    return true;
}

function social_buff_order_payload($order)
{
    $status = social_buff_status_meta((string) ($order['provider_status'] ?? 'pending'));
    return [
        'code' => (string) ($order['order_code'] ?? ''),
        'service_name' => (string) ($order['service_name'] ?? ''),
        'platform' => (string) ($order['platform'] ?? 'Khac'),
        'quantity' => (int) ($order['quantity'] ?? 0),
        'charged_amount' => (float) ($order['charged_amount'] ?? 0),
        'status' => (string) ($order['provider_status'] ?? 'pending'),
        'status_label' => $status['label'],
        'status_class' => $status['class'],
        'start_count' => (string) ($order['provider_start_count'] ?? ''),
        'remains' => (string) ($order['provider_remains'] ?? ''),
        'created_at' => (string) ($order['created_at'] ?? '')
    ];
}

function social_buff_save_order($id, $data)
{
    global $CMSNT;
    $data['updated_at'] = gettime();
    return $CMSNT->update('social_buff_orders', $data, ' `id` = ? ', [(int) $id]);
}

function social_buff_public_services($services)
{
    $public = [];
    foreach ($services as $service) {
        unset($service['provider_rate'], $service['description'], $service['category'], $service['type'], $service['refill'], $service['cancel']);
        $public[] = $service;
    }
    return $public;
}

if (!is_array($getUser)) {
    social_buff_json(['success' => false, 'message' => 'Vui lòng đăng nhập để sử dụng dịch vụ.'], 401);
}

if (!social_buff_ensure_tables()) {
    social_buff_json(['success' => false, 'message' => 'Không thể khởi tạo dữ liệu dịch vụ.'], 503);
}

$input = social_buff_input();
$action = strtolower(social_buff_input_value($input, 'action', (string) ($_GET['action'] ?? 'services')));

if ($action === 'services') {
    if (!social_buff_can_place_order($getUser)) {
        social_buff_json([
            'success' => true,
            'configured' => false,
            'services' => [],
            'message' => 'Dịch vụ đang bảo trì. Vui lòng quay lại sau.'
        ]);
    }
    if (!social_buff_is_configured()) {
        social_buff_json([
            'success' => true,
            'configured' => false,
            'services' => [],
            'message' => 'Dịch vụ đang được cấu hình.'
        ]);
    }

    $response = social_buff_provider_request('services');
    if (empty($response['ok'])) {
        social_buff_json([
            'success' => false,
            'configured' => true,
            'services' => [],
            'message' => 'Chưa thể tải danh sách dịch vụ. Vui lòng thử lại sau.'
        ], 502);
    }

    social_buff_json([
        'success' => true,
        'configured' => true,
        'services' => social_buff_public_services(social_buff_service_list($response))
    ]);
}

if ($action === 'history') {
    $orders = $CMSNT->get_list_safe('SELECT * FROM `social_buff_orders` WHERE `user_id` = ? ORDER BY `id` DESC LIMIT 30', [(int) $getUser['id']]);
    $items = [];
    foreach ($orders as $order) {
        $items[] = social_buff_order_payload($order);
    }
    social_buff_json(['success' => true, 'orders' => $items]);
}

if (!in_array($action, ['place_order', 'refresh'], true)) {
    social_buff_json(['success' => false, 'message' => 'Yêu cầu không hợp lệ.'], 400);
}

if (!social_buff_same_origin_post()) {
    social_buff_json(['success' => false, 'message' => 'Yêu cầu không được xác thực.'], 403);
}

if ($action === 'place_order' && !social_buff_can_place_order($getUser)) {
    social_buff_json(['success' => false, 'message' => 'Dịch vụ đang bảo trì. Vui lòng quay lại sau.'], 503);
}

if (!social_buff_is_configured()) {
    social_buff_json(['success' => false, 'message' => 'Dịch vụ hiện chưa sẵn sàng.'], 503);
}

if ($action === 'refresh') {
    $orderCode = social_buff_input_value($input, 'order_code');
    if (!preg_match('/^[A-Z0-9]{8,50}$/', $orderCode)) {
        social_buff_json(['success' => false, 'message' => 'Mã đơn không hợp lệ.'], 422);
    }
    $order = $CMSNT->get_row_safe('SELECT * FROM `social_buff_orders` WHERE `order_code` = ? AND `user_id` = ? LIMIT 1', [$orderCode, (int) $getUser['id']]);
    if (!$order) {
        social_buff_json(['success' => false, 'message' => 'Không tìm thấy đơn dịch vụ.'], 404);
    }
    if (empty($order['provider_order_id'])) {
        social_buff_json(['success' => true, 'order' => social_buff_order_payload($order)]);
    }
    if (!social_buff_sync_order($order)) {
        social_buff_json(['success' => false, 'message' => 'Hệ thống chưa thể cập nhật trạng thái đơn. Vui lòng thử lại sau.'], 502);
    }
    $order = $CMSNT->get_row_safe('SELECT * FROM `social_buff_orders` WHERE `id` = ? LIMIT 1', [(int) $order['id']]);
    social_buff_json(['success' => true, 'order' => social_buff_order_payload($order)]);
}

$serviceId = social_buff_input_value($input, 'service_id');
$targetUrl = social_buff_input_value($input, 'target_url');
$quantityText = social_buff_input_value($input, 'quantity');
$requestKey = social_buff_input_value($input, 'request_key');

if (!preg_match('/^[A-Za-z0-9._:-]{1,80}$/', $serviceId)
    || !preg_match('/^\\d{1,9}$/', $quantityText)
    || !preg_match('/^[A-Za-z0-9_-]{12,80}$/', $requestKey)
    || !social_buff_valid_target_url($targetUrl)) {
    social_buff_json(['success' => false, 'message' => 'Thông tin đặt dịch vụ chưa hợp lệ.'], 422);
}

$existing = $CMSNT->get_row_safe('SELECT * FROM `social_buff_orders` WHERE `idempotency_key` = ? AND `user_id` = ? LIMIT 1', [$requestKey, (int) $getUser['id']]);
if ($existing) {
    social_buff_json([
        'success' => true,
        'duplicate' => true,
        'message' => 'Yêu cầu này đã được tiếp nhận trước đó.',
        'order' => social_buff_order_payload($existing)
    ]);
}

$serviceResponse = social_buff_provider_request('services');
if (empty($serviceResponse['ok'])) {
    social_buff_json(['success' => false, 'message' => 'Chưa thể xác nhận dịch vụ đã chọn. Vui lòng thử lại sau.'], 502);
}
$service = social_buff_find_service($serviceId, social_buff_service_list($serviceResponse));
$quantity = (int) $quantityText;
if (!$service || $quantity < $service['min'] || $quantity > $service['max'] || $service['rate'] <= 0) {
    social_buff_json(['success' => false, 'message' => 'Dịch vụ hoặc số lượng không còn hợp lệ. Vui lòng tải lại danh sách.'], 422);
}

$chargedAmount = social_buff_calculate_price($service['rate'], $quantity);
$orderCode = social_buff_new_order_code();
$orderId = $CMSNT->insert('social_buff_orders', [
    'order_code' => $orderCode,
    'idempotency_key' => $requestKey,
    'user_id' => (int) $getUser['id'],
    'service_id' => $service['id'],
    'service_name' => $service['name'],
    'platform' => $service['platform'],
    'target_url' => $targetUrl,
    'quantity' => $quantity,
    'provider_rate' => (float) $service['provider_rate'],
    'retail_rate' => (float) $service['rate'],
    'charged_amount' => 0.0,
    'provider_status' => 'creating',
    'created_at' => gettime(),
    'updated_at' => gettime()
]);
if (!$orderId) {
    $existing = $CMSNT->get_row_safe('SELECT * FROM `social_buff_orders` WHERE `idempotency_key` = ? AND `user_id` = ? LIMIT 1', [$requestKey, (int) $getUser['id']]);
    if ($existing) {
        social_buff_json([
            'success' => true,
            'duplicate' => true,
            'message' => 'Yêu cầu này đã được tiếp nhận trước đó.',
            'order' => social_buff_order_payload($existing)
        ]);
    }
    social_buff_json(['success' => false, 'message' => 'Không thể tạo đơn dịch vụ.'], 503);
}

$transactionId = 'SOCIAL_BUFF_' . $orderCode;
$reason = 'Đặt dịch vụ Buff mạng xã hội ' . $service['name'] . ' - ' . $orderCode;
if (!social_buff_debit((int) $getUser['id'], $chargedAmount, $reason, $transactionId)) {
    social_buff_save_order($orderId, ['provider_status' => 'failed']);
    social_buff_json(['success' => false, 'message' => 'Số dư không đủ hoặc không thể trừ tiền.'], 422);
}
social_buff_save_order($orderId, ['charged_amount' => $chargedAmount]);

$providerResponse = social_buff_provider_request('add', [
    'service' => $service['id'],
    'link' => $targetUrl,
    'quantity' => $quantity
]);
$providerOrderId = social_buff_extract_provider_order_id($providerResponse['body'] ?? []);

if (!empty($providerResponse['ok']) && $providerOrderId !== '') {
    $meta = social_buff_extract_order_meta($providerResponse['body']);
    social_buff_save_order($orderId, [
        'provider_order_id' => $providerOrderId,
        'provider_status' => $meta['status'],
        'provider_start_count' => $meta['start_count'],
        'provider_remains' => $meta['remains'],
        'provider_response' => social_buff_snapshot($providerResponse['body']),
        'last_checked_at' => gettime()
    ]);
    $order = $CMSNT->get_row_safe('SELECT * FROM `social_buff_orders` WHERE `id` = ? LIMIT 1', [(int) $orderId]);
    social_buff_json([
        'success' => true,
        'message' => 'Đã tiếp nhận đơn dịch vụ.',
        'order' => social_buff_order_payload($order)
    ]);
}

if (!empty($providerResponse['ok']) || !empty($providerResponse['transport_error']) || (int) ($providerResponse['http_code'] ?? 0) >= 500) {
    social_buff_save_order($orderId, [
        'provider_status' => 'awaiting_confirmation',
        'provider_response' => social_buff_snapshot($providerResponse)
    ]);
    $order = $CMSNT->get_row_safe('SELECT * FROM `social_buff_orders` WHERE `id` = ? LIMIT 1', [(int) $orderId]);
    social_buff_json([
        'success' => true,
        'message' => 'Đơn đang được hệ thống xác minh. Vui lòng không gửi lại yêu cầu này.',
        'order' => social_buff_order_payload($order)
    ]);
}

$refundReason = 'Hoàn tiền đơn Buff mạng xã hội ' . $orderCode;
$refunded = $userModel->RefundCredits((int) $getUser['id'], $chargedAmount, $refundReason, $transactionId . '_REFUND');
social_buff_save_order($orderId, [
    'provider_status' => $refunded ? 'failed_refunded' : 'failed',
    'provider_response' => social_buff_snapshot($providerResponse)
]);

social_buff_json([
    'success' => false,
    'message' => $refunded
        ? 'Đơn chưa thể xử lý. Số dư đã được hoàn lại.'
        : 'Đơn chưa thể xử lý. Vui lòng liên hệ hỗ trợ với mã đơn ' . $orderCode . '.'
], 422);
