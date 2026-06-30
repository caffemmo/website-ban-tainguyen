<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
} 
 
if (!class_exists('SecurityValidator')) {
    require_once __DIR__ . '/../libs/session.php';
}

SecurityValidator::enforce('__CORE__', 'LICENSE:bootstrap');

$CMSNT = new DB();

/**
 * Đảm bảo session đã được khởi tạo.
 */
if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
    session_start();
}

/**
 * Giả lập dữ liệu cache kiểm tra license từ session.
 * Luôn trả về thông tin kích hoạt thành công để bỏ qua các kiểm tra bảo mật.
 */
function loadLicenseCache()
{
    return [
        'license_key' => 'CMSNT-ACTIVE-LICENSE-KEY',
        'checked_at'  => time(),
        'result'      => [
            'status' => 'Active',
            'msg'    => 'Kích hoạt giấy phép thành công!'
        ]
    ];
}

/**
 * Giả lập lưu dữ liệu cache kiểm tra license vào session.
 * Không thực hiện hành động nào vì hệ thống luôn được kích hoạt giả lập.
 */
function saveLicenseCache($licenseKey, array $rawResult, $checkedAt = null)
{
    // Không cần thực hiện lưu cache thực tế
}

/**
 * Hàm kiểm tra giấy phép kích hoạt giả lập.
 * Luôn trả về trạng thái true và thông báo thành công để bỏ qua giao diện bắt buộc nhập license.
 */
function v3pX9sLic($licensekey)
{
    return [
        'msg'    => 'Kích hoạt giấy phép thành công!',
        'status' => true,
    ];
}

/**
 * Hàm kiểm tra giấy phép CMSNT giả lập.
 * Luôn trả về trạng thái Active cùng các thông tin xác thực giả lập hợp lệ.
 */
function u2dK7mToken($licensekey, $localkey = '')
{
    return [
        'status'      => 'Active',
        'checkdate'   => date("Ymd"),
        'remotecheck' => true
    ];
}

// Gán kết quả kiểm tra license luôn thành công để không bao giờ bị redirect/die hiển thị giao diện nhập bản quyền.
$licenseCheck = [
    'status' => true,
    'msg'    => 'Kích hoạt giấy phép thành công!'
];

// Luôn khai báo các hàm kiểm tra bản quyền nếu chưa tồn tại
if (!function_exists('checkLicenseKey')) {
    function checkLicenseKey($licensekey)
    {
        return v3pX9sLic($licensekey);
    }
}

if (!function_exists('CMSNT_check_license')) {
    function CMSNT_check_license($licensekey, $localkey = '')
    {
        return u2dK7mToken($licensekey, $localkey);
    }
}
 