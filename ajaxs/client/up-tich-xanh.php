<?php

define('IN_SITE', true);
require_once dirname(__DIR__, 2) . '/libs/db.php';
require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/libs/lang.php';
require_once dirname(__DIR__, 2) . '/libs/helper.php';
require_once dirname(__DIR__, 2) . '/libs/database/users.php';
require_once dirname(__DIR__, 2) . '/libs/client-session.php';
require_once dirname(__DIR__, 2) . '/libs/uptichxanh.php';
$CMSNT = new DB();
$getUser = client_optional_user($CMSNT);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function uptichxanh_json($payload, $status = 200)
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function uptichxanh_input()
{
    if (!empty($_POST)) {
        return $_POST;
    }
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    return is_array($json) ? $json : [];
}

function uptichxanh_value($input, $key, $default = '')
{
    return isset($input[$key]) && is_scalar($input[$key]) ? trim((string) $input[$key]) : $default;
}

function uptichxanh_upload_image()
{
    if (!isset($_FILES['image']) || !is_array($_FILES['image'])) {
        return ['error' => 'Vui lòng tải ảnh giấy tờ xác minh lên.'];
    }

    $file = $_FILES['image'];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        return ['error' => 'Ảnh tải lên không hợp lệ.'];
    }
    if ((int) ($file['size'] ?? 0) > 10 * 1024 * 1024) {
        return ['error' => 'Ảnh không được vượt quá 10MB.'];
    }

    $mime = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : '';
        if ($finfo) {
            finfo_close($finfo);
        }
    }
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];
    if (!isset($extensions[$mime]) || @getimagesize($file['tmp_name']) === false) {
        return ['error' => 'Chỉ nhận ảnh JPG, PNG hoặc WEBP hợp lệ.'];
    }

    $directory = dirname(__DIR__, 2) . '/assets/storage/up-tich-xanh/';
    if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
        return ['error' => 'Không thể chuẩn bị vùng tải ảnh.'];
    }
    $htaccess = $directory . '.htaccess';
    if (!file_exists($htaccess)) {
        @file_put_contents($htaccess, 'Options -ExecCGI' . PHP_EOL . '<FilesMatch "\.(php|phtml|phar|cgi|pl|py|sh)$">' . PHP_EOL . '    Require all denied' . PHP_EOL . '</FilesMatch>' . PHP_EOL);
    }

    $filename = 'verify_' . date('YmdHis') . '_' . bin2hex(random_bytes(10)) . '.' . $extensions[$mime];
    $path = $directory . $filename;
    if (!move_uploaded_file($file['tmp_name'], $path)) {
        return ['error' => 'Không thể lưu ảnh xác minh.'];
    }
    @chmod($path, 0644);

    return [
        'path' => $path,
        'url' => base_url('assets/storage/up-tich-xanh/' . rawurlencode($filename))
    ];
}

if (!isset($getUser) || !is_array($getUser)) {
    uptichxanh_json(['success' => false, 'message' => 'Vui lòng đăng nhập để sử dụng dịch vụ.'], 401);
}

$input = uptichxanh_input();
$token = uptichxanh_value($input, 'token');
if (empty($getUser['token']) || !hash_equals((string) $getUser['token'], $token)) {
    uptichxanh_json(['success' => false, 'message' => 'Phiên làm việc không hợp lệ, vui lòng tải lại trang.'], 419);
}
if (!uptichxanh_is_configured()) {
    uptichxanh_json(['success' => false, 'message' => 'Dịch vụ hiện chưa sẵn sàng.'], 503);
}

$action = uptichxanh_value($input, 'action');
if ($action !== 'submit') {
    uptichxanh_json(['success' => false, 'message' => 'Thao tác không hợp lệ.'], 400);
}

$service = uptichxanh_value($input, 'service');
$endpoint = uptichxanh_service_endpoint($service);
if ($endpoint === false) {
    uptichxanh_json(['success' => false, 'message' => 'Dịch vụ không hợp lệ.'], 422);
}

$cookie = uptichxanh_value($input, 'cookie');
if ($cookie === '' || mb_strlen($cookie) > 100000) {
    uptichxanh_json(['success' => false, 'message' => 'Vui lòng nhập cookie hợp lệ.'], 422);
}

$payload = ['cookie' => $cookie];
$uploadedPath = '';
if (in_array($service, ['up-fb', 'up-ig'], true)) {
    $upload = uptichxanh_upload_image();
    if (isset($upload['error'])) {
        uptichxanh_json(['success' => false, 'message' => $upload['error']], 422);
    }
    $uploadedPath = $upload['path'];
    $payload['image_url'] = $upload['url'];
}

$salePrice = uptichxanh_service_price($service);
if ($salePrice <= 0) {
    if ($uploadedPath !== '') {
        @unlink($uploadedPath);
    }
    uptichxanh_json(['success' => false, 'message' => 'Dịch vụ chưa được thiết lập giá bán.'], 503);
}
if ((float) $getUser['money'] < $salePrice) {
    if ($uploadedPath !== '') {
        @unlink($uploadedPath);
    }
    uptichxanh_json(['success' => false, 'message' => 'Số dư ví không đủ để sử dụng dịch vụ.'], 422);
}

$transactionId = 'up_tich_xanh_' . bin2hex(random_bytes(8));
$userModel = new users();
if (!$userModel->RemoveCredits($getUser['id'], $salePrice, 'Sử dụng ' . uptichxanh_service_label($service), $transactionId)) {
    if ($uploadedPath !== '') {
        @unlink($uploadedPath);
    }
    uptichxanh_json(['success' => false, 'message' => 'Không thể trừ tiền trong ví, vui lòng thử lại.'], 500);
}

$providerResponse = uptichxanh_api_call('POST', $endpoint, [], $payload);
if ($uploadedPath !== '') {
    @unlink($uploadedPath);
}
if (!$providerResponse['success']) {
    $userModel->RefundCredits($getUser['id'], $salePrice, 'Hoàn tiền dịch vụ ' . uptichxanh_service_label($service) . ' do giao dịch lỗi', $transactionId . '_refund');
    uptichxanh_json(['success' => false, 'message' => uptichxanh_error_text($providerResponse)], 502);
}

$data = isset($providerResponse['data']) && is_array($providerResponse['data']) ? $providerResponse['data'] : [];
$providerCost = isset($data['cost']) && is_numeric($data['cost']) ? (float) $data['cost'] : 0;
$providerBalance = isset($data['new_balance']) && is_numeric($data['new_balance']) ? (float) $data['new_balance'] : null;
$uid = isset($data['uid']) && is_scalar($data['uid']) ? trim((string) $data['uid']) : '';
$link = isset($data['link']) && is_scalar($data['link']) ? trim((string) $data['link']) : '';
if ($link !== '' && (filter_var($link, FILTER_VALIDATE_URL) === false || strtolower((string) parse_url($link, PHP_URL_SCHEME)) !== 'https')) {
    $link = '';
}

uptichxanh_ensure_tables();
$CMSNT->insert('up_tich_xanh_orders', [
    'user_id' => (int) $getUser['id'],
    'service' => $service,
    'provider_uid' => $uid !== '' ? $uid : null,
    'result_link' => $link !== '' ? $link : null,
    'provider_cost' => $providerCost,
    'charged_amount' => $salePrice,
    'provider_balance' => $providerBalance,
    'provider_status' => (string) ($providerResponse['status'] ?? 'success'),
    'provider_response' => json_encode($providerResponse, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    'created_at' => date('Y-m-d H:i:s')
]);

$safeData = [
    'uid' => $uid,
    'link' => $link,
    'charged_amount' => $salePrice,
    'charged_label' => format_currency($salePrice),
    'wallet_balance' => (float) getUser($getUser['id'], 'money')
];
uptichxanh_json([
    'success' => true,
    'message' => $service === 'get-link' ? 'Đã tạo link xác minh.' : 'Yêu cầu xác minh đã được tiếp nhận.',
    'data' => $safeData
]);
