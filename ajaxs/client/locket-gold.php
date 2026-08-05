<?php
define('IN_SITE', true);
require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/libs/db.php';
require_once dirname(__DIR__, 2) . '/libs/helper.php';
require_once dirname(__DIR__, 2) . '/libs/database/users.php';
require_once dirname(__DIR__, 2) . '/libs/client-session.php';
require_once dirname(__DIR__, 2) . '/libs/locket-gold.php';

header('Content-Type: application/json; charset=UTF-8');

function locket_gold_json($payload, $status = 200)
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$CMSNT = new DB();
$getUser = client_optional_user($CMSNT);
if (!$getUser) {
    locket_gold_json(['success' => false, 'message' => 'Vui lòng đăng nhập để sử dụng dịch vụ.'], 401);
}

$token = isset($_POST['token']) && is_scalar($_POST['token']) ? trim((string) $_POST['token']) : '';
if ($token === '' || empty($getUser['token']) || !hash_equals((string) $getUser['token'], $token)) {
    locket_gold_json(['success' => false, 'message' => 'Phiên làm việc không hợp lệ, vui lòng tải lại trang.'], 419);
}

$action = isset($_POST['action']) && is_scalar($_POST['action']) ? trim((string) $_POST['action']) : '';
if ($action !== 'create_order') {
    locket_gold_json(['success' => false, 'message' => 'Thao tác không hợp lệ.'], 400);
}
if (!locket_gold_enabled()) {
    locket_gold_json(['success' => false, 'message' => 'Dịch vụ đang tạm đóng, vui lòng quay lại sau.'], 503);
}

$packageKey = isset($_POST['package_key']) && is_scalar($_POST['package_key']) ? trim((string) $_POST['package_key']) : '';
$package = locket_gold_package($packageKey);
if (!$package || (float) $package['price'] <= 0) {
    locket_gold_json(['success' => false, 'message' => 'Gói dịch vụ không hợp lệ.'], 422);
}

$normalized = locket_gold_normalize_usernames($_POST['usernames'] ?? '', $package['max_accounts']);
if (empty($normalized['success'])) {
    locket_gold_json(['success' => false, 'message' => $normalized['message'] ?? 'Username không hợp lệ.'], 422);
}

$salePrice = (float) $package['price'];
if ((float) $getUser['money'] < $salePrice) {
    locket_gold_json(['success' => false, 'message' => 'Số dư ví không đủ để tạo đơn này.'], 422);
}
if (!locket_gold_ensure_orders_table()) {
    locket_gold_json(['success' => false, 'message' => 'Không thể chuẩn bị lịch sử đơn, vui lòng thử lại sau.'], 503);
}

$orderCode = locket_gold_order_code();
$transactionId = 'LOCKET_GOLD_' . $orderCode;
$userModel = new users();
if (!$userModel->RemoveCredits($getUser['id'], $salePrice, 'Tạo đơn Locket Gold Vĩnh Viễn ' . $package['label'], $transactionId)) {
    locket_gold_json(['success' => false, 'message' => 'Không thể trừ tiền trong ví, vui lòng thử lại.'], 500);
}

$now = date('Y-m-d H:i:s');
$inserted = $CMSNT->insert('locket_gold_orders', [
    'order_code' => $orderCode,
    'user_id' => (int) $getUser['id'],
    'package_key' => $package['key'],
    'package_name' => $package['label'],
    'account_limit' => (int) $package['max_accounts'],
    'account_count' => count($normalized['usernames']),
    'usernames' => implode("\n", $normalized['usernames']),
    'charged_amount' => $salePrice,
    'status' => 'pending',
    'created_at' => $now,
    'updated_at' => $now
]);

if (!$inserted) {
    $userModel->RefundCredits($getUser['id'], $salePrice, 'Hoàn tiền đơn Locket Gold Vĩnh Viễn lỗi lưu đơn ' . $orderCode, 'REFUND_' . $transactionId);
    locket_gold_json(['success' => false, 'message' => 'Không thể tạo đơn, số dư đã được hoàn lại nếu giao dịch thành công.'], 500);
}

queueServiceOrderNotification(
    $getUser,
    'Locket Gold Vĩnh Viễn - ' . $package['label'],
    $salePrice,
    $orderCode,
    count($normalized['usernames']),
    'Trạng thái: Đang xử lý',
    ['source' => 'locket_gold_purchase', 'package_key' => $package['key']]
);

$freshUser = $CMSNT->get_row_safe('SELECT `money` FROM `users` WHERE `id` = ? LIMIT 1', [(int) $getUser['id']]);
locket_gold_json([
    'success' => true,
    'order_code' => $orderCode,
    'charged_amount' => $salePrice,
    'balance' => (float) ($freshUser['money'] ?? 0),
    'message' => 'Đã tiếp nhận yêu cầu.'
]);
