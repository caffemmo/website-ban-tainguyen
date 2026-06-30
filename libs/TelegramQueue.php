<?php

/**
 * Telegram Queue - Gửi thông báo Telegram bất đồng bộ
 * 
 * Theo pattern từ SMTPMailer email_queue
 * Không ảnh hưởng tốc độ checkout
 * 
 * @package SHOPCLONE7
 * @author CMSNT
 * @version 1.0.0
 */

if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

class TelegramQueue
{
    private $db;
    private $errors = [];

    // Giới hạn
    const MAX_MESSAGE_SIZE = 4096; // Telegram limit

    public function __construct()
    {
        global $CMSNT;
        $this->db = $CMSNT;
    }

    /**
     * Thêm tin nhắn vào queue
     * 
     * @param string $message Nội dung tin nhắn
     * @param string $chatId Chat ID (null = dùng mặc định admin)
     * @param string $token Bot token (null = dùng mặc định)
     * @param int $priority 1=cao, 5=thấp
     * @param array $metadata Dữ liệu bổ sung
     * @return int|false Queue ID hoặc false nếu lỗi
     */
    public function queueMessage(
        $message,
        $chatId = null,
        $token = null,
        $priority = 3,
        $metadata = []
    ) {
        // Skip if Telegram is disabled
        if ($this->db->site('telegram_status') != 1) {
            return false;
        }

        if (empty($message)) {
            $this->addError('Tin nhắn trống');
            return false;
        }

        // Sử dụng chat_id mặc định nếu không có
        if (empty($chatId)) {
            $chatId = $this->db->site('telegram_chat_id');
        }

        if (empty($chatId)) {
            $this->addError('Không có Chat ID');
            return false;
        }

        // Truncate message nếu quá dài
        if (strlen($message) > self::MAX_MESSAGE_SIZE) {
            $message = substr($message, 0, self::MAX_MESSAGE_SIZE - 20) . "\n\n... (truncated)";
        }

        try {
            $queueId = $this->db->insert('telegram_queue', [
                'chat_id' => $chatId,
                'token' => $token,
                'message' => $message,
                'priority' => max(1, min(5, $priority)),
                'status' => 'pending',
                'attempts' => 0,
                'max_attempts' => 3,
                'metadata' => !empty($metadata) ? json_encode($metadata) : null,
                'created_at' => date('Y-m-d H:i:s'),
                'scheduled_at' => date('Y-m-d H:i:s')
            ]);

            return $queueId;
        } catch (Exception $e) {
            $this->addError('Không thể thêm vào queue: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Queue thông báo đơn hàng cho Admin
     * 
     * @param array $user User data
     * @param array $orders Orders data
     * @param float $totalAmount Tổng thanh toán
     * @param float $discountAmount Số tiền giảm giá
     * @param string $couponCode Mã coupon
     * @return int|false
     */
    public function queueOrderNotificationAdmin(
        $user,
        $orders,
        $totalAmount,
        $discountAmount = 0,
        $couponCode = ''
    ) {
        // Kiểm tra Telegram đã bật chưa
        if ($this->db->site('telegram_status') != 1) {
            return false;
        }

        $template = $this->db->site('noti_order_success_admin');

        // Template trống = tắt thông báo
        if (empty($template)) {
            return false;
        }

        // Build order IDs
        $orderIds = array_column($orders, 'trans_id');

        // Build order details
        $orderDetails = [];
        foreach ($orders as $order) {
            $orderDetails[] = "• " . $order['product_name'] . " x" . $order['quantity'] . " = " . format_currency($order['total']);
        }

        $message = str_replace([
            '{domain}',
            '{username}',
            '{order_count}',
            '{total_amount}',
            '{discount_amount}',
            '{coupon_code}',
            '{order_ids}',
            '{order_details}',
            '{ip}',
            '{time}',
            '{new_balance}'
        ], [
            isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'N/A',
            isset($user['username']) ? $user['username'] : 'N/A',
            count($orders),
            format_currency($totalAmount),
            format_currency($discountAmount),
            $couponCode ? $couponCode : 'N/A',
            implode(', ', $orderIds),
            implode("\n", $orderDetails),
            myip(),
            gettime(),
            format_currency(isset($user['money']) ? $user['money'] : 0)
        ], $template);

        return $this->queueMessage($message, null, null, 1, [
            'type' => 'order_success_admin',
            'user_id' => isset($user['id']) ? $user['id'] : null,
            'order_count' => count($orders)
        ]);
    }

    /**
     * Queue thông báo đơn hàng ORDER (pending) cho Admin
     * Chỉ gửi khi có đơn hàng cần xử lý thủ công
     * 
     * @param array $user User data
     * @param array $pendingOrders Danh sách đơn hàng pending
     * @param float $totalAmount Tổng thanh toán các đơn pending
     * @return int|false
     */
    public function queuePendingOrderNotificationAdmin(
        $user,
        $pendingOrders,
        $totalAmount
    ) {
        // Không có đơn pending thì bỏ qua
        if (empty($pendingOrders)) {
            return false;
        }

        // Kiểm tra Telegram đã bật chưa
        if ($this->db->site('telegram_status') != 1) {
            return false;
        }

        $template = $this->db->site('noti_pending_order_admin');

        // Template trống = tắt thông báo
        if (empty($template)) {
            return false;
        }

        // Build order IDs
        $orderIds = array_column($pendingOrders, 'trans_id');

        // Build order details
        $orderDetails = [];
        foreach ($pendingOrders as $order) {
            $total = isset($order['total']) ? $order['total'] : (isset($order['final_amount']) ? $order['final_amount'] : 0);
            $orderDetails[] = "• " . $order['product_name'] . " x" . $order['quantity'] . " = " . format_currency($total);
        }

        $message = str_replace([
            '{domain}',
            '{username}',
            '{order_count}',
            '{total_amount}',
            '{order_ids}',
            '{order_details}',
            '{ip}',
            '{time}'
        ], [
            isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'N/A',
            isset($user['username']) ? $user['username'] : 'N/A',
            count($pendingOrders),
            format_currency($totalAmount),
            implode(', ', $orderIds),
            implode("\n", $orderDetails),
            myip(),
            gettime()
        ], $template);

        // Kiểm tra Chat ID riêng cho đơn hàng ORDER
        $customChatId = $this->db->site('pending_order_telegram_chat_id');
        $chatId = !empty($customChatId) ? $customChatId : null;

        return $this->queueMessage($message, $chatId, null, 1, [
            'type' => 'pending_order_admin',
            'user_id' => isset($user['id']) ? $user['id'] : null,
            'order_count' => count($pendingOrders)
        ]);
    }

    /**
     * Queue thông báo đơn hàng cho User
     * Gửi vào Telegram cá nhân của user nếu đã liên kết
     * 
     * @param array $user User data
     * @param array $orders Orders data
     * @param float $totalAmount Tổng thanh toán
     * @param float $discountAmount Số tiền giảm giá
     * @param string $couponCode Mã coupon
     * @return int|false
     */
    public function queueOrderNotificationUser(
        $user,
        $orders,
        $totalAmount,
        $discountAmount = 0,
        $couponCode = ''
    ) {
        // Kiểm tra user có liên kết Telegram không
        if (empty($user['telegram_chat_id'])) {
            return false;
        }

        // Kiểm tra Telegram đã bật chưa
        if ($this->db->site('telegram_status') != 1) {
            return false;
        }

        $template = $this->db->site('noti_order_success_user');

        // Template trống = tắt thông báo
        if (empty($template)) {
            return false;
        }

        // Build order IDs
        $orderIds = array_column($orders, 'trans_id');

        $message = str_replace([
            '{domain}',
            '{username}',
            '{order_count}',
            '{total_amount}',
            '{discount_amount}',
            '{coupon_code}',
            '{order_ids}',
            '{ip}',
            '{time}',
            '{new_balance}'
        ], [
            isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'N/A',
            isset($user['username']) ? $user['username'] : 'N/A',
            count($orders),
            format_currency($totalAmount),
            format_currency($discountAmount),
            $couponCode ? $couponCode : 'N/A',
            implode(', ', $orderIds),
            myip(),
            gettime(),
            format_currency(isset($user['money']) ? $user['money'] : 0)
        ], $template);

        return $this->queueMessage($message, $user['telegram_chat_id'], null, 2, [
            'type' => 'order_success_user',
            'user_id' => isset($user['id']) ? $user['id'] : null,
            'order_count' => count($orders)
        ]);
    }

    /**
     * Queue thông báo đánh giá sản phẩm mới cho Admin
     * 
     * @param array $user User data (người đánh giá)
     * @param array $product Product data
     * @param int $rating Số sao đánh giá (1-5)
     * @param string $title Tiêu đề đánh giá
     * @param string $content Nội dung đánh giá
     * @return int|false
     */
    public function queueReviewNotificationAdmin(
        $user,
        $product,
        $rating,
        $title = '',
        $content = ''
    ) {
        // Kiểm tra Telegram đã bật chưa
        if ($this->db->site('telegram_status') != 1) {
            return false;
        }

        // Kiểm tra có chat_id admin không
        if (empty($this->db->site('telegram_chat_id'))) {
            return false;
        }

        $template = $this->db->site('noti_new_review');

        // Template trống = tắt thông báo
        if (empty($template)) {
            return false;
        }

        $product_name = html_entity_decode(isset($product['name']) ? $product['name'] : '', ENT_QUOTES, 'UTF-8');
        $stars = str_repeat('⭐', $rating);
        $review_content = mb_substr($content, 0, 200) . (mb_strlen($content) > 200 ? '...' : '');

        $message = str_replace([
            '{domain}',
            '{username}',
            '{product_name}',
            '{rating}',
            '{stars}',
            '{title}',
            '{content}',
            '{time}'
        ], [
            isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'N/A',
            isset($user['username']) ? $user['username'] : 'N/A',
            $product_name,
            $rating,
            $stars,
            $title ? $title : 'Không có tiêu đề',
            $review_content,
            gettime()
        ], $template);

        return $this->queueMessage($message, null, null, 2, [
            'type' => 'new_review_admin',
            'user_id' => isset($user['id']) ? $user['id'] : null,
            'product_id' => isset($product['id']) ? $product['id'] : null,
            'rating' => $rating
        ]);
    }

    /**
     * Xử lý queue (gọi từ cron job)
     * 
     * @param int $limit Số lượng xử lý tối đa
     * @return array ['processed' => int, 'success' => int, 'failed' => int]
     */
    public function processQueue($limit = 10)
    {
        $stats = ['processed' => 0, 'success' => 0, 'failed' => 0];

        // Kiểm tra Telegram đã bật chưa
        if ($this->db->site('telegram_status') != 1) {
            return $stats;
        }

        try {
            // Lấy pending messages (dùng PHP time để đồng bộ timezone)
            $now = date('Y-m-d H:i:s');
            $messages = $this->db->get_list_safe(
                "SELECT * FROM `telegram_queue` 
                 WHERE `status` = 'pending' 
                 AND `scheduled_at` <= ?
                 AND `attempts` < `max_attempts`
                 ORDER BY `priority` ASC, `created_at` ASC 
                 LIMIT ?",
                [$now, $limit]
            );

            if (empty($messages)) {
                return $stats;
            }

            foreach ($messages as $msg) {
                $stats['processed']++;

                // Mark processing
                $this->db->update('telegram_queue', [
                    'status' => 'processing',
                    'attempts' => $msg['attempts'] + 1,
                    'last_attempt_at' => date('Y-m-d H:i:s')
                ], "`id` = ?", [$msg['id']]);

                // Gửi tin nhắn
                $sent = $this->sendTelegram(
                    $msg['message'],
                    $msg['token'],
                    $msg['chat_id']
                );

                if ($sent) {
                    // Mark sent
                    $this->db->update('telegram_queue', [
                        'status' => 'sent',
                        'sent_at' => date('Y-m-d H:i:s'),
                        'error_message' => null
                    ], "`id` = ?", [$msg['id']]);

                    $stats['success']++;
                } else {
                    // Check max attempts
                    $newAttempts = $msg['attempts'] + 1;
                    $newStatus = ($newAttempts >= $msg['max_attempts']) ? 'failed' : 'pending';

                    $this->db->update('telegram_queue', [
                        'status' => $newStatus,
                        'error_message' => $this->getLastError()
                    ], "`id` = ?", [$msg['id']]);

                    if ($newStatus === 'failed') {
                        $stats['failed']++;
                    }
                }

                // Delay nhỏ để tránh rate limit
                usleep(100000); // 0.1 giây
            }
        } catch (Exception $e) {
            error_log('[TelegramQueue] processQueue error: ' . $e->getMessage());
        }

        return $stats;
    }

    /**
     * Gửi tin nhắn Telegram trực tiếp
     */
    private function sendTelegram($message, $token, $chatId)
    {
        if (empty($token)) {
            $token = $this->db->site('telegram_token');
        }

        if (empty($token) || empty($chatId)) {
            $this->addError('Token hoặc Chat ID trống');
            return false;
        }

        try {
            $telegram_url = $this->db->site('telegram_url') . 'bot' . $token . '/sendMessage';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $telegram_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML'
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

            // Thêm proxy nếu có
            $proxy = $this->db->site('telegram_proxy');
            $proxyType = $this->db->site('telegram_proxy_type');
            if (!empty($proxy)) {
                $proxy_parts = explode(':', $proxy);
                if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
                    curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
                    curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
                } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
                    curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
                }

                if ($proxyType == 'SOCKS5') {
                    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
                } else {
                    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
                }
            }

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            // Log vào bảng telegram_logs
            $this->db->insert('telegram_logs', [
                'username' => $chatId,
                'command' => 'queue_send',
                'params' => mb_substr($message, 0, 500),
                'type' => 'message',
                'time' => gettime()
            ]);

            if (!empty($curlError)) {
                $this->addError('CURL error: ' . $curlError);
                return false;
            }

            if ($httpCode !== 200) {
                $this->addError('HTTP ' . $httpCode);
                return false;
            }

            $result = json_decode($response, true);
            if (!isset($result['ok']) || $result['ok'] !== true) {
                $this->addError(isset($result['description']) ? $result['description'] : 'Unknown error');
                return false;
            }

            return true;
        } catch (Exception $e) {
            $this->addError('Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Thống kê queue
     */
    public function getQueueStats()
    {
        try {
            $stats = $this->db->get_row(
                "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
                 FROM `telegram_queue`"
            );

            return $stats ? $stats : [
                'total' => 0,
                'pending' => 0,
                'processing' => 0,
                'sent' => 0,
                'failed' => 0
            ];
        } catch (Exception $e) {
            return [
                'total' => 0,
                'pending' => 0,
                'processing' => 0,
                'sent' => 0,
                'failed' => 0
            ];
        }
    }

    /**
     * Dọn dẹp queue cũ
     */
    public function cleanQueue($days = 30)
    {
        try {
            $days = max(1, min(365, intval($days)));
            $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

            $count = $this->db->num_rows_safe(
                "SELECT id FROM `telegram_queue` WHERE `status` IN ('sent', 'failed') AND `created_at` < ?",
                [$cutoffDate]
            );

            $this->db->remove(
                'telegram_queue',
                "`status` IN ('sent', 'failed') AND `created_at` < ?",
                [$cutoffDate]
            );

            return $count;
        } catch (Exception $e) {
            return 0;
        }
    }

    private function addError($message)
    {
        $this->errors[] = $message;
        error_log('[TelegramQueue] ' . $message);
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getLastError()
    {
        $last = end($this->errors);
        return $last ? $last : '';
    }
}
