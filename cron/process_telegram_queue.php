<?php

/**
 * Telegram Queue Processor - Web Cron
 * 
 * Xử lý telegram queue để gửi thông báo async, không ảnh hưởng tốc độ trang.
 * 
 * URL: https://yoursite.com/cron/process_telegram_queue.php?key=YOUR_KEY_CRON_JOB
 * 
 * @author CMSNT.CO
 * @version 1.0.0
 */

define("IN_SITE", true);
require_once(__DIR__ . '/../libs/db.php');
require_once(__DIR__ . '/../libs/lang.php');
require_once(__DIR__ . '/../libs/helper.php');
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../libs/TelegramQueue.php');

$CMSNT = new DB();

// Kiểm tra key
if (!isset($_GET['key'])) {
    die(__('Vui lòng nhập Key Cron Job vào đường dẫn'));
}
if (isset($_GET['key']) && $_GET['key'] != $CMSNT->site('key_cron_job')) {
    die(__('Key không hợp lệ'));
}

// Chống spam - tối thiểu 10 giây giữa mỗi lần chạy
$lastRun = (int) $CMSNT->site('check_time_cron_telegram_queue');
$elapsed = time() - $lastRun;
if ($lastRun > 0 && $elapsed >= 0 && $elapsed < 10) {
    die('Thao tác quá nhanh, vui lòng thử lại sau ' . (10 - (time() - $lastRun)) . ' giây!');
}

// Cập nhật thời gian chạy
$CMSNT->update("settings", [
    'value' => time()
], " `name` = 'check_time_cron_telegram_queue' ");

// Khởi tạo TelegramQueue
$telegramQueue = new TelegramQueue();

// Kiểm tra Telegram đã bật chưa
if ($CMSNT->site('telegram_status') != 1) {
    die(__('Telegram chưa được kích hoạt'));
}

// Xử lý queue (tối đa 20 tin nhắn mỗi lần)
$stats = $telegramQueue->processQueue(20);

echo "Processed: {$stats['processed']}, Success: {$stats['success']}, Failed: {$stats['failed']}<br>";

// Dọn dẹp queue cũ (mỗi giờ)
if (date('i') == '00') {
    $deleted = $telegramQueue->cleanQueue(30);
    if ($deleted > 0) {
        echo "Cleaned: {$deleted} old entries<br>";
    }
}

// Hiển thị thống kê
$queueStats = $telegramQueue->getQueueStats();
echo "Queue: Pending={$queueStats['pending']}, Sent={$queueStats['sent']}, Failed={$queueStats['failed']}";
