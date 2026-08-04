<?php
define('IN_SITE', true);
require_once dirname(__DIR__, 2) . '/libs/db.php';
require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/libs/lang.php';
require_once dirname(__DIR__, 2) . '/libs/helper.php';
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
    $result = netflix_get_cookie();
} elseif ($action === 'regenerate_link') {
    $result = netflix_regenerate_token($logId);
} else {
    netflix_json(['success' => false, 'message' => 'Thao tác không hợp lệ.'], 400);
}
if (!$result['success']) {
    $status = $result['code'] === 'not_configured' ? 503 : 502;
    netflix_json(['success' => false, 'message' => $result['message']], $status);
}

netflix_json([
    'success' => true,
    'message' => 'Đã tạo link xem Netflix.',
    'data' => $result['data']
]);
