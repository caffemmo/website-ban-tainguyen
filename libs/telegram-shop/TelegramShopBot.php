<?php

/**
 * TelegramShopBot - Lớp core xử lý Bot Telegram Shop
 * 
 * Kiến trúc:
 * - Entry point: telegram-shop.php (slim router)
 * - Core class: TelegramShopBot (file này)
 * - Handlers: libs/telegram-shop/handlers/*.php (mỗi file 1 nhóm chức năng)
 * 
 * Mở rộng:
 * - Tạo file handler mới trong thư mục handlers/
 * - Đăng ký handler bằng $bot->on('prefix', 'function_name') tại telegram-shop.php
 * - callback_data format: "prefix:action:param1:param2"
 * 
 * Debug:
 * - Mọi request đều được ghi vào bảng telegram_logs
 * - Lỗi runtime được bắt bằng try-catch và gửi thông báo cho admin
 */
class TelegramShopBot
{
    /** @var DB Instance database */
    public $CMSNT;

    /** @var string Token của Bot Telegram */
    public $bot_token;

    /** @var array|null Dữ liệu user trong bảng users (null nếu chưa xác thực) */
    public $user = null;

    /** @var int Chat ID của người dùng đang tương tác */
    public $chat_id = 0;

    /** @var int|null Message ID đang xử lý (dùng để edit message khi nhấn nút) */
    public $message_id = null;

    /** @var string|null Callback query ID (dùng để answer callback) */
    public $callback_id = null;

    /** @var bool Đánh dấu request hiện tại là callback_query (nhấn nút) hay message (gõ text) */
    public $is_callback = false;

    /** @var array Thông tin Telegram của người nhắn tin (from object) */
    public $from = [];

    /** @var array Danh sách handler đã đăng ký, format: ['prefix' => callable] */
    private $handlers = [];

    /**
     * Khởi tạo Bot
     * 
     * @param DB $CMSNT Instance database
     * @param string $bot_token Token Bot Telegram từ BotFather
     */
    public function __construct($CMSNT, $bot_token)
    {
        $this->CMSNT = $CMSNT;
        $this->bot_token = $bot_token;
    }

    /**
     * Lấy username của Bot Telegram từ database hoặc gọi API getMe để cache lại
     * 
     * @return string Username của Bot (không có dấu @)
     */
    public function getBotUsername()
    {
        $username = $this->CMSNT->site('telegram_shop_bot_username');
        if (empty($username)) {
            // Lấy động từ API và lưu lại vào DB để tránh gọi lại nhiều lần gây trễ
            $response = $this->callApi('getMe', []);
            if (isset($response['ok']) && $response['ok'] == true && isset($response['result']['username'])) {
                $username = $response['result']['username'];
                $this->CMSNT->update("settings", ['value' => $username], " `name` = 'telegram_shop_bot_username' ");
            }
        }
        return $username;
    }

    // =========================================================================
    // ĐĂNG KÝ HANDLER
    // =========================================================================

    /**
     * Đăng ký handler cho một prefix callback_data
     * Khi user nhấn nút có callback_data bắt đầu bằng prefix, handler sẽ được gọi
     * 
     * @param string $prefix Tiền tố callback_data (VD: 'menu', 'account', 'shop')
     * @param callable $callable Hàm xử lý, nhận 2 tham số: ($bot, $params)
     */
    public function on($prefix, $callable)
    {
        $this->handlers[$prefix] = $callable;
    }

    // =========================================================================
    // XỬ LÝ UPDATE TỪ TELEGRAM
    // =========================================================================

    /**
     * Xử lý update từ Telegram webhook
     * Tự động phân loại: callback_query (nhấn nút) hoặc message (gõ text)
     * 
     * @param array $update Dữ liệu update từ Telegram
     */
    public function processUpdate($update)
    {
        // Xử lý nhấn nút (InlineKeyboard callback)
        if (isset($update['callback_query'])) {
            $this->processCallback($update['callback_query']);
            return;
        }

        // Xử lý tin nhắn text thông thường
        if (isset($update['message'])) {
            $this->processMessage($update['message']);
            return;
        }
    }

    /**
     * Xử lý callback_query - khi user nhấn nút InlineKeyboard
     * Flow: Trích xuất info → Xác thực user → Answer callback → Route handler
     * 
     * @param array $callback Dữ liệu callback_query từ Telegram
     */
    private function processCallback($callback)
    {
        $this->is_callback = true;
        $this->callback_id = $callback['id'];
        $this->chat_id = $callback['from']['id'];
        $this->from = $callback['from'];
        $this->message_id = isset($callback['message']['message_id']) ? $callback['message']['message_id'] : null;

        // Xác thực hoặc đăng ký user
        $this->authenticateUser();

        // Inject ngôn ngữ/tiền tệ ưa thích từ DB vào $_COOKIE (Bot không có cookie)
        $this->applyUserPreferences();

        // Answer callback ngay lập tức để bỏ trạng thái loading trên nút
        $this->answerCallback($this->callback_id);

        // Kiểm tra user bị ban
        if ($this->isUserBanned()) {
            return;
        }

        // User bấm nút → xóa state chờ text nếu có (đã chuyển sang tương tác bằng nút)
        $this->clearState();

        // Phân tích callback_data và route tới handler tương ứng
        $data = isset($callback['data']) ? $callback['data'] : '';
        $this->routeCallback($data);
    }

    /**
     * Xử lý message - khi user gõ text hoặc gửi lệnh /start
     * Flow: Trích xuất info → Xác thực user → Route handler
     * 
     * @param array $message Dữ liệu message từ Telegram
     */
    private function processMessage($message)
    {
        $this->is_callback = false;
        $this->chat_id = $message['from']['id'];
        $this->from = $message['from'];
        $this->message_id = null;

        // Xác thực hoặc đăng ký user
        $isNewUser = $this->authenticateUser();

        // Inject ngôn ngữ/tiền tệ ưa thích từ DB vào $_COOKIE (Bot không có cookie)
        $this->applyUserPreferences();

        // Kiểm tra user bị ban
        if ($this->isUserBanned()) {
            return;
        }

        // Nếu là user mới vừa đăng ký → không cần route thêm (đã gửi welcome message)
        if ($isNewUser) {
            return;
        }

        $text = isset($message['text']) ? trim($message['text']) : '';

        // Lệnh /start luôn xóa state và hiển thị menu chính
        if (strpos($text, '/start') === 0) {
            $this->clearState();
            $this->routeCallback('menu:main');
            return;
        }

        if (!empty($text)) {
            // Ưu tiên 1: check fullname (dùng prefix_fullname == NULL làm flag tự nhiên)
            if ($this->isWaitingFullname()) {
                $this->routeCallback('recharge:process_fullname:' . $text);
                return;
            }

            // Ưu tiên 2: check telegram_state (dùng cho crypto amount, search, v.v.)
            $state = $this->getState();
            if (!empty($state)) {
                $this->clearState();
                $this->routeCallback($state . ':' . $text);
                return;
            }
        }

        // Mọi tin nhắn text khác → hiển thị menu chính (bot chỉ hoạt động bằng nút bấm)
        $this->routeCallback('menu:main');
    }

    /**
     * Kiểm tra user có đang ở trạng thái chờ nhập họ tên không
     * Điều kiện: bank_recharge_type == fullname_transfer VÀ prefix_fullname chưa có
     * 
     * @return bool true nếu cần nhập họ tên
     */
    private function isWaitingFullname()
    {
        if (!$this->user) {
            return false;
        }
        $bankRechargeType = $this->CMSNT->site('bank_recharge_type');
        return $bankRechargeType == 'fullname_transfer' && empty($this->user['prefix_fullname']);
    }

    /**
     * Route callback_data tới handler đã đăng ký
     * Format callback_data: "prefix:action:param1:param2"
     * 
     * @param string $data Chuỗi callback_data
     */
    private function routeCallback($data)
    {
        // Tách prefix và params từ callback_data
        $parts = explode(':', $data);
        $prefix = $parts[0];
        $params = array_slice($parts, 1);

        // Tìm handler đã đăng ký cho prefix này
        if (isset($this->handlers[$prefix])) {
            call_user_func($this->handlers[$prefix], $this, $params);
        } else {
            // Không tìm thấy handler → log và thông báo
            // Chuỗi log có thể được hiển thị ở admin nên vẫn chuẩn hóa qua i18n.
            $this->logDebug('ROUTE_NOT_FOUND', sprintf(__('Không tìm thấy handler cho prefix: %s'), $prefix));
            $this->sendMessage(__('⚠️ Chức năng này chưa được triển khai.'));
        }
    }

    // =========================================================================
    // XÁC THỰC & ĐĂNG KÝ USER
    // =========================================================================

    /**
     * Xác thực user dựa trên chat_id
     * - Nếu đã có tài khoản → cập nhật session
     * - Nếu chưa có → tự động đăng ký và gửi welcome message
     * 
     * @return bool true nếu là user mới vừa đăng ký, false nếu đã có tài khoản
     */
    private function authenticateUser()
    {
        $tg_user_id = 'tg_' . $this->chat_id;
        $this->user = $this->CMSNT->get_row_safe("SELECT * FROM `users` WHERE `username` = ?", [$tg_user_id]);

        if ($this->user) {
            // User đã tồn tại → cập nhật thời gian hoạt động
            $this->CMSNT->update("users", [
                'time_session' => time(),
                'update_date'  => gettime()
            ], " `id` = ? ", [$this->user['id']]);
            return false;
        }

        // User chưa tồn tại → Đăng ký tài khoản mới
        return $this->registerNewUser($tg_user_id);
    }

    /**
     * Đăng ký tài khoản mới từ thông tin Telegram
     * Username format: tg_{chat_id} (đảm bảo unique vì chat_id là duy nhất toàn cầu)
     * 
     * @param string $tg_user_id Username dạng tg_{chat_id}
     * @return bool true nếu đăng ký thành công
     */
    private function registerNewUser($tg_user_id)
    {
        $firstname = isset($this->from['first_name']) ? $this->from['first_name'] : '';
        $lastname = isset($this->from['last_name']) ? $this->from['last_name'] : '';
        $fullname = trim($firstname . ' ' . $lastname);
        if (empty($fullname)) {
            $fullname = $tg_user_id;
        }

        // Sinh các token bảo mật
        $random_password = generateUltraSecureToken(16);
        $token = generateUltraSecureToken(64);
        $remember_token = generateUltraSecureToken(64);

        $isCreate = $this->CMSNT->insert("users", [
            'admin'            => 0,
            'remember_token'   => $remember_token,
            'ref_id'           => 0,
            'utm_source'       => 'telegram_shop',
            'token'            => $token,
            'username'         => $tg_user_id,
            'email'            => $tg_user_id . '@telegram.local',
            'fullname'         => $fullname,
            'password'         => TypePassword($random_password),
            'ip'               => '0.0.0.0',
            'device'           => 'Telegram Bot',
            'create_date'      => gettime(),
            'update_date'      => gettime(),
            'time_session'     => time(),
            'api_key'          => md5($tg_user_id . time()) . random('QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm0123456789', 32),
            'telegram_chat_id' => $this->chat_id
        ]);

        if (!$isCreate) {
            $this->sendMessage(__('❌ Có lỗi xảy ra khi tạo tài khoản. Vui lòng thử lại sau.'));
            return false;
        }

        // Ghi log đăng ký
        $this->CMSNT->insert("logs", [
            'user_id'    => $isCreate,
            'ip'         => '0.0.0.0',
            'device'     => 'Telegram Bot',
            'createdate' => gettime(),
            // Dùng placeholder để chuỗi dịch cố định, đảm bảo map đa ngôn ngữ chính xác.
            'action'     => sprintf(__('Đăng ký tài khoản tự động qua Telegram Shop (chat_id: %s)'), $this->chat_id)
        ]);

        // Lấy lại thông tin user vừa tạo
        $this->user = $this->CMSNT->get_row_safe("SELECT * FROM `users` WHERE `id` = ?", [$isCreate]);

        // Gửi tin nhắn chào mừng kèm nút menu chính
        $site_title = $this->CMSNT->site('title');
        // Tách text thành các chuỗi dịch cố định để hệ thống i18n có thể quản lý đầy đủ.
        $welcome_msg  = sprintf(__('🎉 <b>Chào mừng bạn đến với %s!</b>'), $site_title) . "\n\n";
        $welcome_msg .= __('✅ Tài khoản đã được tạo tự động:') . "\n";
        $welcome_msg .= sprintf(__('👤 <b>Username:</b> <code>%s</code>'), $tg_user_id) . "\n";
        $welcome_msg .= __('💰 <b>Số dư:</b> <code>0</code>') . "\n\n";
        $welcome_msg .= __('Nhấn nút bên dưới để bắt đầu mua sắm! 🛒');

        $keyboard = self::inlineKeyboard([
            [
                ['text' => __('🏪 Vào Shop'), 'data' => 'menu:main']
            ]
        ]);

        $this->sendMessage($welcome_msg, $keyboard);
        return true;
    }

    /**
     * Kiểm tra user có bị ban không, nếu có thì gửi thông báo
     * 
     * @return bool true nếu user bị ban
     */
    private function isUserBanned()
    {
        if ($this->user && $this->user['banned'] == 1) {
            $reason = !empty($this->user['reason_banned']) ? $this->user['reason_banned'] : __('Không rõ');
            // Escape lý do trước khi đưa vào message HTML để giữ an toàn XSS.
            $safeReason = htmlspecialchars($reason, ENT_QUOTES, 'UTF-8');
            $this->sendMessage(sprintf(__('🚫 <b>Tài khoản của bạn đã bị khóa.</b>' . "\n\n" . '<b>Lý do:</b> %s'), $safeReason));
            return true;
        }
        return false;
    }

    // =========================================================================
    // TELEGRAM API HELPERS
    // =========================================================================

    /**
     * Gửi tin nhắn mới tới chat hiện tại
     * 
     * @param string $text Nội dung tin nhắn (hỗ trợ HTML)
     * @param array|null $keyboard InlineKeyboard markup (tạo bằng self::inlineKeyboard())
     * @return array|false Kết quả từ Telegram API
     */
    public function sendMessage($text, $keyboard = null)
    {
        $params = [
            'chat_id'                  => $this->chat_id,
            'text'                     => $text,
            'parse_mode'               => 'HTML',
            'disable_web_page_preview' => true
        ];

        if ($keyboard !== null) {
            $params['reply_markup'] = json_encode($keyboard);
        }

        return $this->callApi('sendMessage', $params);
    }

    /**
     * Sửa nội dung tin nhắn đã gửi (dùng khi user nhấn nút inline)
     * Giúp giao diện mượt mà, không spam tin nhắn mới
     * 
     * @param string $text Nội dung mới
     * @param array|null $keyboard InlineKeyboard markup mới
     * @return array|false Kết quả từ Telegram API
     */
    public function editMessage($text, $keyboard = null)
    {
        // Nếu không có message_id (VD: từ tin nhắn text) → fallback gửi tin nhắn mới
        if ($this->message_id === null) {
            return $this->sendMessage($text, $keyboard);
        }

        $params = [
            'chat_id'                  => $this->chat_id,
            'message_id'               => $this->message_id,
            'text'                     => $text,
            'parse_mode'               => 'HTML',
            'disable_web_page_preview' => true
        ];

        if ($keyboard !== null) {
            $params['reply_markup'] = json_encode($keyboard);
        }

        return $this->callApi('editMessageText', $params);
    }

    /**
     * Phản hồi thông minh: tự động chọn edit hoặc send dựa trên ngữ cảnh
     * - Nếu là callback (nhấn nút) → edit message hiện tại
     * - Nếu là message (gõ text) → gửi tin nhắn mới
     * 
     * @param string $text Nội dung tin nhắn
     * @param array|null $keyboard InlineKeyboard markup
     * @return array|false
     */
    public function reply($text, $keyboard = null)
    {
        if ($this->is_callback && $this->message_id !== null) {
            return $this->editMessage($text, $keyboard);
        }
        return $this->sendMessage($text, $keyboard);
    }

    /**
     * Answer callback query - bắt buộc gọi khi nhận callback để bỏ loading
     * Có thể hiển thị toast message ngắn trên Telegram
     * 
     * @param string $callback_id ID của callback_query
     * @param string $text Text hiển thị dạng toast (tùy chọn)
     * @param bool $show_alert true = popup alert, false = toast nhỏ
     */
    public function answerCallback($callback_id, $text = '', $show_alert = false)
    {
        $params = [
            'callback_query_id' => $callback_id,
            'show_alert'        => $show_alert
        ];

        if (!empty($text)) {
            $params['text'] = $text;
        }

        $this->callApi('answerCallbackQuery', $params);
    }

    /**
     * Gửi ảnh kèm caption tới chat hiện tại
     * Dùng cho gửi QR code ngân hàng, ảnh sản phẩm, v.v.
     * 
     * @param string $photo_url URL ảnh (Telegram sẽ download từ URL này)
     * @param string $caption Mô tả kèm ảnh (hỗ trợ HTML)
     * @param array|null $keyboard InlineKeyboard markup
     * @return array|false Kết quả từ Telegram API
     */
    public function sendPhoto($photo_url, $caption = '', $keyboard = null)
    {
        $params = [
            'chat_id'    => $this->chat_id,
            'photo'      => $photo_url,
            'parse_mode' => 'HTML'
        ];

        if (!empty($caption)) {
            $params['caption'] = $caption;
        }

        if ($keyboard !== null) {
            $params['reply_markup'] = json_encode($keyboard);
        }

        return $this->callApi('sendPhoto', $params);
    }

    /**
     * Gửi file (document) kèm caption tới chat hiện tại
     * Dùng cho gửi file .txt chứa danh sách tài khoản mua được khi quá dài
     * 
     * @param string $filepath Đường dẫn tuyệt đối tới file trên server
     * @param string $filename Tên file hiển thị trên Telegram (VD: "order_xxx.txt")
     * @param string $mime_type MIME type (mặc định text/plain)
     * @param string $caption Mô tả đi kèm (hỗ trợ HTML)
     * @param array|null $keyboard InlineKeyboard markup
     * @return array|false Kết quả Telegram API
     */
    public function sendDocument($filepath, $filename = 'file.txt', $mime_type = 'text/plain', $caption = '', $keyboard = null)
    {
        // File không tồn tại → bỏ qua, tránh gửi request lỗi
        if (!is_file($filepath)) {
            $this->logDebug('SEND_DOC_ERROR', 'File not found: ' . $filepath);
            return false;
        }

        // Dùng CURLFile để upload multipart/form-data
        $params = [
            'chat_id'    => $this->chat_id,
            'document'   => new CURLFile($filepath, $mime_type, $filename),
            'parse_mode' => 'HTML'
        ];

        if (!empty($caption)) {
            $params['caption'] = $caption;
        }

        if ($keyboard !== null) {
            $params['reply_markup'] = json_encode($keyboard);
        }

        // sendDocument phải dùng multipart (array) thay vì http_build_query
        $url = "https://api.telegram.org/bot" . $this->bot_token . "/sendDocument";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $result = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->logDebug('CURL_ERROR', 'sendDocument: ' . $error);
            return false;
        }

        $response = json_decode($result, true);
        if (isset($response['ok']) && $response['ok'] === false) {
            $this->logDebug('API_ERROR', 'sendDocument: ' . ($response['description'] ?? 'Unknown error'));
        }

        return $response;
    }

    /**
     * Xóa một tin nhắn
     * 
     * @param int|null $msg_id ID tin nhắn cần xóa (mặc định = message hiện tại)
     */
    public function deleteMessage($msg_id = null)
    {
        $this->callApi('deleteMessage', [
            'chat_id'    => $this->chat_id,
            'message_id' => $msg_id !== null ? $msg_id : $this->message_id
        ]);
    }

    /**
     * Gọi Telegram Bot API bằng cURL (server-side)
     * Tất cả request đều đi qua hàm này để dễ debug và xử lý lỗi
     * 
     * @param string $method Tên method API (sendMessage, editMessageText, ...)
     * @param array $params Tham số gửi kèm
     * @return array|false Kết quả JSON decode, false nếu lỗi
     */
    private function callApi($method, $params)
    {
        $url = "https://api.telegram.org/bot" . $this->bot_token . "/" . $method;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $result = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->logDebug('CURL_ERROR', $method . ': ' . $error);
            return false;
        }

        $response = json_decode($result, true);

        // Log lỗi từ Telegram API để dễ debug
        if (isset($response['ok']) && $response['ok'] === false) {
            $this->logDebug('API_ERROR', $method . ': ' . ($response['description'] ?? 'Unknown error'));
        }

        return $response;
    }

    // =========================================================================
    // KEYBOARD BUILDER
    // =========================================================================

    /**
     * Tạo InlineKeyboard markup từ mảng nút bấm
     * 
     * Cách dùng:
     * $keyboard = TelegramShopBot::inlineKeyboard([
     *     // Hàng 1: 2 nút
     *     [
     *         ['text' => 'Nút 1', 'data' => 'prefix:action'],
     *         ['text' => 'Nút 2', 'data' => 'prefix:action']
     *     ],
     *     // Hàng 2: 1 nút
     *     [
     *         ['text' => 'Nút 3', 'url' => 'https://example.com']
     *     ]
     * ]);
     * 
     * @param array $rows Mảng hàng, mỗi hàng là mảng nút
     * @return array Telegram InlineKeyboardMarkup
     */
    public static function inlineKeyboard($rows)
    {
        $keyboard = [];
        foreach ($rows as $row) {
            $keyboardRow = [];
            foreach ($row as $button) {
                $btn = ['text' => $button['text']];

                // Nút callback (nhấn → gửi callback_query)
                if (isset($button['data'])) {
                    $btn['callback_data'] = $button['data'];
                }

                // Nút URL (nhấn → mở link ngoài trình duyệt)
                if (isset($button['url'])) {
                    $btn['url'] = $button['url'];
                }

                // Nút Web App (nhấn → mở URL trong trình duyệt tích hợp Telegram)
                if (isset($button['web_app'])) {
                    $btn['web_app'] = ['url' => $button['web_app']];
                }

                $keyboardRow[] = $btn;
            }
            $keyboard[] = $keyboardRow;
        }

        return ['inline_keyboard' => $keyboard];
    }

    // =========================================================================
    // LOGGING & DEBUG
    // =========================================================================

    /**
     * Ghi log request webhook vào bảng telegram_logs
     * Giúp debug khi Bot hoạt động không đúng
     * 
     * @param array $update Dữ liệu update gốc từ Telegram
     */
    public function logWebhook($update)
    {
        $tg_username = '';
        $command = '';

        // Trích xuất thông tin từ message hoặc callback_query
        if (isset($update['message'])) {
            $tg_username = isset($update['message']['from']['username']) ? $update['message']['from']['username'] : '';
            $command = isset($update['message']['text']) ? $update['message']['text'] : '';
        } elseif (isset($update['callback_query'])) {
            $tg_username = isset($update['callback_query']['from']['username']) ? $update['callback_query']['from']['username'] : '';
            $command = isset($update['callback_query']['data']) ? 'callback:' . $update['callback_query']['data'] : '';
        }

        $this->CMSNT->insert('telegram_logs', [
            'username' => $tg_username,
            'command'  => $command,
            'params'   => json_encode($update),
            'type'     => 'telegram_shop',
            'time'     => gettime()
        ]);
    }

    /**
     * Ghi log debug nội bộ để xử lý sự cố
     * 
     * @param string $type Loại log (CURL_ERROR, API_ERROR, ROUTE_NOT_FOUND, ...)
     * @param string $message Chi tiết lỗi
     */
    public function logDebug($type, $message)
    {
        $this->CMSNT->insert('telegram_logs', [
            'username' => 'SYSTEM',
            'command'  => $type,
            'params'   => $message,
            'type'     => 'telegram_shop_debug',
            'time'     => gettime()
        ]);
    }

    // =========================================================================
    // NGÔN NGỮ & TIỀN TỆ CHO TELEGRAM USER
    // =========================================================================

    /**
     * Inject ngôn ngữ/tiền tệ ưa thích của user vào $_COOKIE
     * Vì Bot Telegram không có cookie, nên ta inject trước khi handler chạy
     * -> Hàm __() và format_currency() đọc $_COOKIE tự động hoạt động đúng
     * 
     * Nếu user chưa tùy chỉnh (telegram_lang/telegram_currency rỗng),
     * fallback về ngôn ngữ/tiền tệ mặc định đã cấu hình trong admin panel.
     * Điều này tránh để __() phải query lại DB mỗi lần gọi.
     */
    private function applyUserPreferences()
    {
        if (!$this->user) {
            return;
        }

        // Inject ngôn ngữ: ưu tiên lựa chọn cá nhân → fallback về ngôn ngữ mặc định admin
        if (!empty($this->user['telegram_lang'])) {
            $_COOKIE['language'] = $this->user['telegram_lang'];
        } else {
            // User chưa chọn ngôn ngữ → lấy ngôn ngữ mặc định từ bảng languages (admin cấu hình)
            $defaultLang = $this->CMSNT->get_row("SELECT `lang` FROM `languages` WHERE `lang_default` = 1");
            if ($defaultLang && !empty($defaultLang['lang'])) {
                $_COOKIE['language'] = $defaultLang['lang'];
            }
        }

        // Inject tiền tệ: ưu tiên lựa chọn cá nhân → fallback về tiền tệ mặc định admin
        if (!empty($this->user['telegram_currency'])) {
            $_COOKIE['currency'] = $this->user['telegram_currency'];
        } else {
            // User chưa chọn tiền tệ → lấy tiền tệ mặc định từ bảng currencies (admin cấu hình)
            $defaultCurrency = $this->CMSNT->get_row_safe("SELECT `id` FROM `currencies` WHERE `default_currency` = 1 AND `display` = 1", []);
            if ($defaultCurrency && !empty($defaultCurrency['id'])) {
                $_COOKIE['currency'] = $defaultCurrency['id'];
            }
        }
    }

    // =========================================================================
    // TIỆN ÍCH DÙNG CHUNG CHO HANDLERS
    // =========================================================================

    /**
     * Đặt state chờ input text từ user
     * Khi user gửi text tiếp theo, processMessage sẽ route đến callback này
     * VD: setState('recharge:crypto_custom') → user gõ "25" → route đến recharge:crypto_custom:25
     * 
     * Chỉ dùng khi không có flag tự nhiên (fullname dùng prefix_fullname == NULL)
     * 
     * @param string $state Callback prefix sẽ route đến (VD: "recharge:crypto_custom")
     */
    public function setState($state)
    {
        if ($this->user) {
            $this->CMSNT->update("users", [
                'telegram_state' => $state
            ], " `id` = ? ", [$this->user['id']]);
        }
    }

    /**
     * Lấy state hiện tại
     * 
     * @return string|null
     */
    public function getState()
    {
        if ($this->user && !empty($this->user['telegram_state'])) {
            return $this->user['telegram_state'];
        }
        return null;
    }

    /**
     * Xóa state — user không còn ở chế độ chờ input
     */
    public function clearState()
    {
        if ($this->user && !empty($this->user['telegram_state'])) {
            $this->user['telegram_state'] = null;
            $this->CMSNT->update("users", [
                'telegram_state' => null
            ], " `id` = ? ", [$this->user['id']]);
        }
    }

    /**
     * Lấy số dư user đã format theo tiền tệ
     * 
     * @return string Số dư đã format (VD: "1.000.000")
     */
    public function getFormattedBalance()
    {
        return format_currency($this->user['money']);
    }

    /**
     * Tạo nút "⬅️ Quay lại" để quay về một callback_data
     * Dùng thống nhất ở tất cả handler để có UX nhất quán
     * 
     * @param string $callback_data Callback data khi nhấn nút quay lại
     * @param string|null $text Label của nút (mặc định: "⬅️ Quay lại" theo ngôn ngữ hiện tại)
     * @return array Mảng nút cho inlineKeyboard
     */
    public static function backButton($callback_data, $text = null)
    {
        // Không thể gọi hàm __() trong default parameter, nên set fallback trong thân hàm.
        if ($text === null) {
            $text = __('⬅️ Quay lại');
        }
        return ['text' => $text, 'data' => $callback_data];
    }

    // =========================================================================
    // THÔNG BÁO TỪ BÊN NGOÀI (CALLBACK, CRON)
    // =========================================================================

    /**
     * Gửi thông báo nạp tiền thành công đến user trên Telegram
     * 
     * Dùng trong callback/cron khi xử lý thanh toán xong — không cần khởi tạo cả bot.
     * Chỉ gửi nếu user có telegram_chat_id (đăng ký qua Telegram).
     * 
     * @param int $user_id ID user trong DB
     * @param string $method Phương thức nạp (VD: "Crypto", "TriPay QRIS", "ACB")
     * @param string $amount_label Nhãn số tiền gốc (VD: "$10 USDT", "100.000 IDR")
     * @param float $received Số tiền thực nhận (đã quy đổi theo tỷ giá hệ thống)
     */
    public static function notifyRecharge($user_id, $method, $amount_label, $received)
    {
        global $CMSNT;

        // Lấy thông tin user + kiểm tra có telegram_chat_id không
        $user = $CMSNT->get_row_safe(
            "SELECT `telegram_chat_id`, `money` FROM `users` WHERE `id` = ? AND `telegram_chat_id` IS NOT NULL AND `telegram_chat_id` != ''",
            [$user_id]
        );
        if (!$user) {
            return;
        }

        $bot_token = $CMSNT->site('telegram_shop_bot_token');
        if (empty($bot_token)) {
            return;
        }

        $new_balance = format_currency($user['money']);
        $received_formatted = format_currency($received);

        $text  = __('✅ <b>Nạp tiền thành công!</b>') . "\n\n";
        $text .= sprintf(__('💳 <b>Phương thức:</b> %s'), $method) . "\n";
        $text .= sprintf(__('💵 <b>Số tiền:</b> %s'), $amount_label) . "\n";
        $text .= sprintf(__('💰 <b>Thực nhận:</b> %s'), $received_formatted) . "\n";
        $text .= sprintf(__('🏦 <b>Số dư mới:</b> %s'), $new_balance) . "\n\n";
        $text .= __('Cảm ơn bạn đã sử dụng dịch vụ!');

        // Thêm nút quay về menu
        $keyboard = json_encode(self::inlineKeyboard([
            [
                self::backButton('menu:main', __('🏠 Menu chính'))
            ]
        ]));

        // Gửi trực tiếp qua Telegram API, không cần khởi tạo class
        $params = http_build_query([
            'chat_id'                  => $user['telegram_chat_id'],
            'text'                     => $text,
            'parse_mode'               => 'HTML',
            'disable_web_page_preview' => true,
            'reply_markup'             => $keyboard
        ]);

        $ch = curl_init("https://api.telegram.org/bot{$bot_token}/sendMessage");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_exec($ch);
        curl_close($ch);
    }

    /**
     * Gửi thông báo broadcast đến TẤT CẢ user đã kết nối Telegram Shopping
     * 
     * Điều kiện: user có telegram_chat_id — tức đã từng /start Bot trên Telegram.
     * Không áp dụng cho user chỉ đăng ký trên web.
     * 
     * Rate limit: Telegram giới hạn ~30 msg/s global → delay 35ms giữa các message.
     * Nếu gặp lỗi 429 (Too Many Requests), tự sleep theo retry_after trước khi tiếp tục.
     * 
     * @param string $message Nội dung thông báo (hỗ trợ HTML: <b>, <i>, <code>, <a href>)
     * @return array ['total' => int, 'sent' => int, 'failed' => int]
     */
    public static function broadcast($message)
    {
        global $CMSNT;

        $result = ['total' => 0, 'sent' => 0, 'failed' => 0];

        $bot_token = $CMSNT->site('telegram_shop_bot_token');
        if (empty($bot_token)) {
            return $result;
        }

        // Lấy danh sách user đã kết nối Telegram Shopping
        $users = $CMSNT->get_list_safe(
            "SELECT `telegram_chat_id` FROM `users` 
             WHERE `telegram_chat_id` IS NOT NULL AND `telegram_chat_id` != '' AND `banned` = 0",
            []
        );

        $result['total'] = count($users);
        if ($result['total'] === 0) {
            return $result;
        }

        $api_url = "https://api.telegram.org/bot{$bot_token}/sendMessage";

        foreach ($users as $user) {
            $params = http_build_query([
                'chat_id'                  => $user['telegram_chat_id'],
                'text'                     => $message,
                'parse_mode'               => 'HTML',
                'disable_web_page_preview' => true
            ]);

            $ch = curl_init($api_url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $raw = curl_exec($ch);
            curl_close($ch);

            $response = json_decode($raw, true);
            if (isset($response['ok']) && $response['ok'] === true) {
                $result['sent']++;
            } else {
                $result['failed']++;
                // Xử lý 429 (rate limit): sleep theo retry_after để tránh spam API
                if (isset($response['error_code']) && $response['error_code'] == 429) {
                    $retry_after = isset($response['parameters']['retry_after']) ? (int) $response['parameters']['retry_after'] : 1;
                    sleep(min($retry_after, 10));
                }
            }

            // Delay 35ms giữa các message → an toàn với giới hạn 30 msg/s của Telegram
            usleep(35000);
        }

        return $result;
    }
}
