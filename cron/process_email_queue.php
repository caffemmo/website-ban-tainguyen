<?php

/**
 * Email Queue Processor - Web Cron
 * 
 * Xử lý email queue để gửi email async, không ảnh hưởng tốc độ trang.
 * 
 * URL: https://yoursite.com/cron/process_email_queue.php?key=YOUR_KEY_CRON_JOB
 * 
 * @author CMSNT.CO
 * @version 1.0.0
 */

define("IN_SITE", true);
require_once(__DIR__ . '/../libs/db.php');
require_once(__DIR__ . '/../libs/lang.php');
require_once(__DIR__ . '/../libs/helper.php');
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../libs/SMTPMailer.php');

$CMSNT = new DB();

// Kiểm tra key
if (!isset($_GET['key'])) {
    die(__('Vui lòng nhập Key Cron Job vào đường dẫn'));
}
if (isset($_GET['key']) && $_GET['key'] != $CMSNT->site('key_cron_job')) {
    die(__('Key không hợp lệ'));
}

// Chống spam - tối thiểu 30 giây giữa mỗi lần chạy
$lastRun = (int) $CMSNT->site('check_time_cron_email_queue');
$elapsed = time() - $lastRun;
if ($lastRun > 0 && $elapsed >= 0 && $elapsed < 30) {
    die('Thao tác quá nhanh, vui lòng thử lại sau ' . (30 - (time() - $lastRun)) . ' giây!');
}

// Cập nhật thời gian chạy
$CMSNT->update("settings", [
    'value' => time()
], " `name` = 'check_time_cron_email_queue' ");

// Khởi tạo SMTPMailer
$mailer = new SMTPMailer($CMSNT);

// Kiểm tra SMTP đã bật chưa
if (!$mailer->isEnabled()) {
    die(__('SMTP chưa được kích hoạt'));
}

// Xử lý queue (tối đa 10 email mỗi lần)
$stats = $mailer->processQueue(10);

echo "Processed: {$stats['processed']}, Success: {$stats['success']}, Failed: {$stats['failed']}<br>";

// Dọn dẹp queue cũ (mỗi giờ, khi phút = 00)
if (date('i') == '00') {
    $cleaned = $mailer->cleanQueue(30);
    if ($cleaned) {
        echo "Cleaned old entries (30+ days)<br>";
    }
}

// Hiển thị thống kê
$queueStats = $mailer->getQueueStats();
echo "Queue: Pending={$queueStats['pending']}, Sent={$queueStats['sent']}, Failed={$queueStats['failed']}";
