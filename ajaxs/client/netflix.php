<?php
define('IN_SITE', true);
require_once dirname(__DIR__, 2) . '/libs/db.php';
require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/libs/lang.php';
require_once dirname(__DIR__, 2) . '/libs/helper.php';
require_once dirname(__DIR__, 2) . '/libs/database/users.php';
require_once dirname(__DIR__, 2) . '/libs/client-session.php';
require_once dirname(__DIR__, 2) . '/libs/netflix.php';

$CMSNT = new DB();
$getUser = client_optional_user($CMSNT);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function netflix_json($payload, $status = 200)
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!is_array($getUser)) {
    netflix_json(['success' => false, 'message' => 'Vui lòng đăng nhập để sử dụng dịch vụ.'], 401);
}

$token = isset($_POST['token']) && is_scalar($_POST['token']) ? trim((string) $_POST['token']) : '';
if ($token === '' || empty($getUser['token']) || !hash_equals((string) $getUser['token'], $token)) {
    netflix_json(['success' => false, 'message' => 'Phiên làm việc không hợp lệ, vui lòng tải lại trang.'], 419);
}

$action = isset($_POST['action']) && is_scalar($_POST['action']) ? trim((string) $_POST['action']) : '';
if ($action !== 'get_cookie' && $action !== 'regenerate_link') {
    netflix_json(['success' => false, 'message' => 'Thao tác không hợp lệ.'], 400);
}

$logId = isset($_POST['log_id']) && is_scalar($_POST['log_id']) ? trim((string) $_POST['log_id']) : '';
if ($action === 'get_cookie') {
    $salePrice = netflix_service_price();
    if ($salePrice <= 0) {
        netflix_json(['success' => false, 'message' => 'Dịch vụ Netflix chưa được thiết lập giá bán.'], 503);
    }
    if ((float) $getUser['money'] < $salePrice) {
        netflix_json(['success' => false, 'message' => 'Số dư ví không đủ để sử dụng dịch vụ Netflix.'], 422);
    }
    if (!netflix_ensure_orders_table()) {
        netflix_json(['success' => false, 'message' => 'Không thể chuẩn bị lịch sử Netflix, vui lòng thử lại sau.'], 503);
    }

    $transactionId = 'netflix_' . bin2hex(random_bytes(8));
    $userModel = new users();
    if (!$userModel->RemoveCredits($getUser['id'], $salePrice, 'Sử dụng Xem Netflix', $transactionId)) {
        netflix_json(['success' => false, 'message' => 'Không thể trừ tiền trong ví, vui lòng thử lại.'], 500);
    }

    $result = netflix_get_cookie();
} elseif ($action === 'regenerate_link') {
    if (!netflix_ensure_orders_table()) {
        netflix_json(['success' => false, 'message' => 'Không thể kiểm tra quyền tạo lại link Netflix.'], 503);
    }
    $order = $logId !== '' ? netflix_order_for_user($getUser['id'], $logId) : null;
    if (!$order) {
        netflix_json(['success' => false, 'message' => 'Link Netflix không thuộc tài khoản của bạn.'], 403);
    }
    if (!netflix_order_under_warranty($order['created_at'] ?? '')) {
        netflix_json(['success' => false, 'message' => 'Gói bảo hành Netflix 30 ngày của giao dịch này đã hết hạn.'], 410);
    }
    $result = netflix_regenerate_token($logId);
} else {
    netflix_json(['success' => false, 'message' => 'Thao tác không hợp lệ.'], 400);
}
if (!$result['success']) {
    if ($action === 'get_cookie' && isset($userModel, $salePrice, $transactionId)) {
        $userModel->RefundCredits($getUser['id'], $salePrice, 'Hoàn tiền Xem Netflix do giao dịch lỗi', $transactionId . '_refund');
    }
    $status = $result['code'] === 'not_configured' ? 503 : 502;
    netflix_json(['success' => false, 'message' => $result['message']], $status);
}

$data = $result['data'];
if ($action === 'get_cookie') {
    $providerLogId = isset($data['log_id']) && is_scalar($data['log_id']) ? trim((string) $data['log_id']) : '';
    if ($providerLogId !== '' && !netflix_record_order($getUser['id'], $providerLogId, $salePrice)) {
        $userModel->RefundCredits($getUser['id'], $salePrice, 'Hoàn tiền Xem Netflix do không lưu được giao dịch', $transactionId . '_refund');
        netflix_json(['success' => false, 'message' => 'Không thể lưu giao dịch Netflix, vui lòng thử lại sau.'], 503);
    }
    queueServiceOrderNotification(
        $getUser,
        'Xem Netflix',
        $salePrice,
        $providerLogId !== '' ? $providerLogId : $transactionId,
        1,
        'Gói bảo hành 30 ngày',
        ['source' => 'netflix_purchase']
    );
    $data['charged_amount'] = $salePrice;
    $data['charged_label'] = format_currency($salePrice);
    $data['wallet_balance'] = (float) getUser($getUser['id'], 'money');
}

netflix_json([
    'success' => true,
    'message' => 'Đã tạo link xem Netflix.',
    'data' => $data
]);
