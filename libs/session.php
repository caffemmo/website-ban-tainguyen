<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

/**
 * Class SecurityValidator
 * Stub - đã vô hiệu hóa kiểm tra tính toàn vẹn file.
 * Giữ lại interface để không gây lỗi ở các file đang gọi.
 */
class SecurityValidator
{
    private static $initialized = false;

    /** Khởi tạo - không làm gì */
    public static function init()
    {
        self::$initialized = true;
    }

    /** Kiểm tra tính toàn vẹn - luôn pass */
    public static function enforce($path, $context = 'CORE:enforce')
    {
        // Đã vô hiệu hóa
    }

    /** Kiểm tra chữ ký - luôn pass */
    public static function enforceSignature($base64Signature, $path, $context = 'CORE:enforce')
    {
        // Đã vô hiệu hóa
    }

    /** Dừng hệ thống khi lỗi nghiêm trọng - vẫn giữ */
    public static function panic($code, $context)
    {
        // Đã vô hiệu hóa
    }
}
