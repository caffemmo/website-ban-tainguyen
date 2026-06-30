<?php

define("IN_SITE", true);
require_once(__DIR__ . "/../libs/db.php");
require_once(__DIR__ . "/../config.php");
require_once(__DIR__ . "/../libs/lang.php");
require_once(__DIR__ . "/../libs/helper.php");
require_once(__DIR__ . "/../libs/database/users.php");
$CMSNT = new DB();

if ($CMSNT->site('status') != 1 && isSecureCookie('admin_login') != true) {
    exit('status_website_off');
}

if ($CMSNT->site('telegram_assistant_status') != 1) {
    exit('Trạng thái đang OFF');
}

// -----------------------------------------------------
// HÀM GỬI TIN NHẮN
// -----------------------------------------------------
function sendMessage($chat_id, $text) {
    global $CMSNT;

    if(empty($CMSNT->site('telegram_assistant_LicenseKey'))){
        $text = '⚠️ Vui lòng kích hoạt giấy phép trước khi sử dụng chức năng này';
    }else{
        $checkKey = checkAddonLicense($CMSNT->site('telegram_assistant_LicenseKey'), 'SHOPCLONE7_BOTQUANLY');
        if($checkKey['status'] != true){
            $text = $checkKey['msg'];
        }
    }

    $url = "https://api.telegram.org/bot" . $CMSNT->site('telegram_assistant_token') . "/sendMessage";
    file_get_contents($url . '?' . http_build_query([
        'chat_id'    => $chat_id,
        'text'       => $text,
        'parse_mode' => 'Markdown'
    ]));
}

// -----------------------------------------------------
// HÀM GỬI TIN NHẮN KÈM NÚT BẤM
// -----------------------------------------------------
function sendMessageWithButtons($chat_id, $text, $replyMarkup, $message_id = null) {
    global $CMSNT;
    if(empty($CMSNT->site('telegram_assistant_LicenseKey'))){
        $text = '⚠️ Vui lòng kích hoạt giấy phép trước khi sử dụng chức năng này';
    }else{
        $checkKey = checkAddonLicense($CMSNT->site('telegram_assistant_LicenseKey'), 'SHOPCLONE7_BOTQUANLY');
        if($checkKey['status'] != true){
            $text = $checkKey['msg'];
        }
    }

    $url = "https://api.telegram.org/bot" . $CMSNT->site('telegram_assistant_token') . "/";
    $data = [
        'chat_id'     => $chat_id,
        'text'        => $text,
        'parse_mode'  => 'Markdown',
        'reply_markup'=> $replyMarkup
    ];
    // Nếu có $message_id thì editMessageText, nếu không thì sendMessage mới
    if ($message_id) {
        $url .= "editMessageText";
        $data['message_id'] = $message_id;
    } else {
        $url .= "sendMessage";
    }

    $response = file_get_contents($url . '?' . http_build_query($data));
    // file_put_contents("telegram_api_log.txt", 
    //     "Request: " . print_r($data, true) . "\nResponse: " . $response . "\n", 
    // FILE_APPEND);
}

// -----------------------------------------------------
// HÀM LƯU LỊCH LOG TELEGRAM
// -----------------------------------------------------
function saveTelegramLog($username, $command, $params, $type = 'message') {
    global $CMSNT;
    $CMSNT->insert('telegram_logs', [
        'username' => $username,
        'command'  => $command,
        'params'   => json_encode($params),
        'type'     => $type,
        'time'     => date('Y-m-d H:i:s')
    ]);
}


// -----------------------------------------------------
// NHẬN DỮ LIỆU POST TỪ TELEGRAM
// -----------------------------------------------------
$content = file_get_contents("php://input");
$update  = json_decode($content, true);
ignore_user_abort(true);
header("HTTP/1.1 200 OK");
flush();
if (!$update) {
    exit;
}


// -----------------------------------------------------
// PHÂN TÍCH AI GỬI TIN/AI CLICK NÚT
// -----------------------------------------------------
$message        = $update['message'] ?? null;
$callback_query = $update['callback_query'] ?? null;

// Xác thực Token bí mật (Secret Token):
$secret_token = $CMSNT->site('telegram_assistant_secret_token') ?? '';
$headers      = getallheaders();
if (!isset($headers['X-Telegram-Bot-Api-Secret-Token']) || $headers['X-Telegram-Bot-Api-Secret-Token'] !== $secret_token) {
    exit('Xác thực X-Telegram-Bot-Api-Secret-Token không hợp lệ');
}

// -------------------------------------------
// LẤY $chat_id VÀ $sender_username
// (nếu là tin nhắn thường)
// -------------------------------------------
if ($message) {
    $chat_id         = $message['chat']['id'];
    $sender_username = strtolower($message['from']['username'] ?? '');
} 
// Nếu là callback_query (bấm nút)
elseif ($callback_query) {
    $chat_id         = $callback_query['message']['chat']['id'];
    $sender_username = strtolower($callback_query['from']['username'] ?? '');
}

// Kiểm tra ai được phép dùng bot
$allowed_usernames = array_map('trim', explode(',', $CMSNT->site('telegram_assistant_list_username')));
if (!in_array($sender_username, array_map('strtolower', $allowed_usernames))) {
    exit('Bạn không có quyền ra lệnh cho bot này!');
}

// -----------------------------------------------------
// KHAI BÁO MẢNG LỆNH $commands
// -----------------------------------------------------
$commands = [
    'addfund' => function($params) use ($chat_id, $CMSNT) {
        // Tách tham số
        [$usernameParam, $amountParam, $reasonParam] = $params;

        // Kiểm tra đầu vào
        if (empty($usernameParam) || empty($amountParam)) {
            return sendMessage($chat_id,
                "⚠️ *Vui lòng nhập:* `/addfund username số_tiền lý_do`"
            );
        }

        // Validate tham số
        $username = validate_alphanumeric($usernameParam, 50);
        $amount   = validate_float($amountParam, 0.01);
        $reason   = validate_string($reasonParam ?? '', 255) !== false ? $reasonParam : '';
        if ($username === false || $amount === false) {
            return sendMessage($chat_id, "❌ Tham số không hợp lệ (username/số tiền).");
        }

        // Tìm user theo username (prepared)
        if (!$user = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `username` = ?", [$username])) {
            return sendMessage($chat_id,
                "⚠️ *Tài khoản* `{$username}` *không tồn tại.*"
            );
        }

        // Cộng tiền
        (new users())->AddCredits($user['id'], $amount, "[BOT TELEGRAM] $reason");

        // Gửi tin xác nhận
        $message  = "✅ *[CỘNG TIỀN]*\n";
        $message .= "------------------------------------\n";
        $message .= "👤 *Tài khoản:* `{$username}`\n";
        $message .= "💰 *Số tiền cộng:* *" . format_currency($amount) . "*\n";
        $message .= "📄 *Lý do:* `" . ($reason ?: "Không có") . "`\n";
        $message .= "------------------------------------\n";
        $message .= "*Thao tác hoàn tất!*";

        sendMessage($chat_id, $message);
    },

    'removefund' => function($params) use ($chat_id, $CMSNT) {
        // Tách tham số
        [$usernameParam, $amountParam, $reasonParam] = $params;

        // Kiểm tra đầu vào
        if (empty($usernameParam) || empty($amountParam)) {
            return sendMessage($chat_id, 
                "⚠️ *Vui lòng nhập:* `/removefund username số_tiền lý_do`"
            );
        }

        // Validate tham số
        $username = validate_alphanumeric($usernameParam, 50);
        $amount   = validate_float($amountParam, 0.01);
        $reason   = validate_string($reasonParam ?? '', 255) !== false ? $reasonParam : '';
        if ($username === false || $amount === false) {
            return sendMessage($chat_id, "❌ Tham số không hợp lệ (username/số tiền).");
        }

        // Tìm user (prepared)
        if (!$user = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `username` = ?", [$username])) {
            return sendMessage($chat_id, 
                "⚠️ *Tài khoản* `{$username}` *không tồn tại.*"
            );
        }

        // Kiểm tra số dư
        if ($user['money'] < $amount) {
            return sendMessage($chat_id, 
                "❌ *Số dư không đủ để trừ.*"
            );
        }

        // Trừ tiền
        (new users())->RemoveCredits($user['id'], $amount, "[BOT TELEGRAM] $reason");

        // Gửi tin xác nhận
        $message  = "✅ *[TRỪ TIỀN]*\n";
        $message .= "------------------------------------\n";
        $message .= "👤 *Tài khoản:* `{$username}`\n";
        $message .= "💰 *Số tiền trừ:* *" . format_currency($amount) . "*\n";
        $message .= "📄 *Lý do:* `" . ($reason ?: "Không có") . "`\n";
        $message .= "------------------------------------\n";
        $message .= "*Thao tác hoàn tất!*";

        sendMessage($chat_id, $message);
    },

    'balance' => function($params) use ($chat_id, $CMSNT) {
        // Tách tham số
        [$usernameParam] = $params;

        // Kiểm tra đầu vào
        if (empty($usernameParam)) {
            return sendMessage($chat_id, 
                "⚠️ *Vui lòng nhập:* `/balance username`"
            );
        }

        $username = validate_alphanumeric($usernameParam, 50);
        if ($username === false) {
            return sendMessage($chat_id, "❌ Username không hợp lệ.");
        }

        // Tìm user (prepared)
        if (!$user = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `username` = ?", [$username])) {
            return sendMessage($chat_id, 
                "⚠️ *Tài khoản* `{$username}` *không tồn tại.*"
            );
        }

        // Gửi tin hiển thị số dư
        $message  = "💰 *SỐ DƯ TÀI KHOẢN*\n";
        $message .= "------------------------------------\n";
        $message .= "👤 *Username:* `{$username}`\n";
        $message .= "💸 *Số dư hiện tại:* *" . format_currency($user['money']) . "*\n";
        $message .= "------------------------------------\n";

        sendMessage($chat_id, $message);
    },

    'userinfo' => function($params) use ($chat_id, $CMSNT) {
        [$usernameParam] = $params;
        if (empty($usernameParam)) {
            return sendMessage($chat_id, "⚠️ *Vui lòng nhập:* `/userinfo username`");
        }
        $username = validate_alphanumeric($usernameParam, 50);
        if ($username === false) {
            return sendMessage($chat_id, "❌ Username không hợp lệ.");
        }
        if (!$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `username` = ?", [$username])) {
            return sendMessage($chat_id, "⚠️ *Không tìm thấy thông tin tài khoản: $username*");
        }
        sendMessage($chat_id, "👤 *Thông tin tài khoản:*\n".
            "➖ *ID:* " . $getUser['id'] . "\n".
            "➖ *Username:* " . $getUser['username'] . "\n".
            "➖ *Email:* " . $getUser['email'] . "\n".
            "➖ *Số dư:* " . format_currency($getUser['money']) . "\n".
            "➖ *Tổng nạp:* " . format_currency($getUser['total_money']) . "\n".
            "➖ *Chiết khẩu giảm:* " . $getUser['discount'] . "%\n".
            "➖ *Địa chỉ IP:* " . $getUser['ip'] . "\n".
            "➖ *Device:* " . $getUser['device'] . "\n".
            "➖ *UTM Source:* " . $getUser['utm_source'] . "\n".
            "➖ *Ngày tham gia:* " . $getUser['create_date'] . "\n".
            "➖ *Hoạt động gần nhất:* " . timeAgo(strtotime($getUser['update_date'])) . "\n".
            "➖ *Trạng thái:* " . ($getUser['banned'] == 1 ? "Banned" : "Hoạt động") . ""
        );
    },
    'listusers' => function($params) use ($chat_id, $CMSNT) {
        // Số user hiển thị mỗi trang
        $perPage = 10; 
        // Lấy số trang từ tham số, nếu không có hoặc ko phải số thì mặc định trang 1
        $page = (isset($params[0]) && is_numeric($params[0])) ? (int)$params[0] : 1;
        if ($page < 1) {
            $page = 1;
        }
        $offset     = ($page - 1) * $perPage;
        $totalUsers = $CMSNT->num_rows_safe("SELECT `id` FROM `users`", []);
        $totalPages = ceil($totalUsers / $perPage);
        // Lấy danh sách user theo trang
        $users = $CMSNT->get_list_safe("SELECT * FROM `users` ORDER BY `id` DESC LIMIT ? OFFSET ?", [$perPage, $offset]);
        $message = "📋 *Danh sách người dùng (Trang $page/$totalPages)*\n";
        $message .= "------------------------------------\n";
        foreach ($users as $index => $user) {
            // Bạn có thể tùy chỉnh hiển thị thêm/bớt trường
            $message .= "👤 *Username:* `".$user['username']."`\n";
            $message .= "🆔 *ID:* `".$user['id']."`\n";
            $message .= "📧 *Email:* `".$user['email']."`\n";
            $message .= "💰 *Số dư:* ".format_currency($user['money'])."\n";
            $message .= "------------------------------------\n";
        }
        // Tạo nút phân trang
        $inline_keyboard = [];
        if ($page > 1) {
            $inline_keyboard[] = [[
                'text'          => '⬅️ Trang trước',
                'callback_data' => 'listusers ' . ($page - 1)
            ]];
        }
        if ($page < $totalPages) {
            $inline_keyboard[] = [[
                'text'          => '➡️ Trang sau',
                'callback_data' => 'listusers ' . ($page + 1)
            ]];
        }
        // Gửi hoặc chỉnh sửa tin nhắn (nếu có $GLOBALS['message_id'])
        $replyMarkup = json_encode(['inline_keyboard' => $inline_keyboard]);
        $message_id  = $GLOBALS['message_id'] ?? null;
        sendMessageWithButtons($chat_id, $message, $replyMarkup, $message_id);
    },


    'blockuser' => function($params) use ($chat_id, $CMSNT) {
        [$usernameParam, $reasonParam] = $params;
        if (empty($usernameParam)) {
            return sendMessage($chat_id, "⚠️ *Vui lòng nhập:* `/blockuser username lý_do`");
        }
        $username = validate_alphanumeric($usernameParam, 50);
        $reason = validate_string($reasonParam ?? '', 255) !== false ? $reasonParam : '';
        if ($username === false) {
            return sendMessage($chat_id, "❌ Username không hợp lệ.");
        }
        if (!$CMSNT->get_row_safe("SELECT * FROM `users` WHERE `username` = ?", [$username])) {
            return sendMessage($chat_id, "⚠️ *Không tìm thấy thông tin tài khoản: $username*");
        }
        $CMSNT->update('users', ['banned' => 1, 'reason_banned' => $reason], " `username` = ? ", [$username]);
        sendMessage($chat_id, "✅ Tài khoản `$username` đã bị khóa.");
    },
    'unblockuser' => function($params) use ($chat_id, $CMSNT) {
        [$usernameParam] = $params;
        if (empty($usernameParam)) {
            return sendMessage($chat_id, "⚠️ *Vui lòng nhập:* `/unblockuser username`");
        }
        $username = validate_alphanumeric($usernameParam, 50);
        if ($username === false) {
            return sendMessage($chat_id, "❌ Username không hợp lệ.");
        }
        if (!$CMSNT->get_row_safe("SELECT * FROM `users` WHERE `username` = ?", [$username])) {
            return sendMessage($chat_id, "⚠️ *Không tìm thấy thông tin tài khoản: $username*");
        }
        $CMSNT->update('users', ['banned' => 0], " `username` = ? ", [$username]);
        sendMessage($chat_id, "✅ Tài khoản `$username` đã được mở khóa.");
    },
    'topusers' => function($params) use ($chat_id, $CMSNT) {
        // Số user hiển thị mỗi trang
        $perPage = 10;

        // Lấy số trang từ tham số, nếu không có hoặc ko phải số thì mặc định trang 1
        $page = (isset($params[0]) && is_numeric($params[0])) ? (int)$params[0] : 1;
        if ($page < 1) {
            $page = 1;
        }

        $offset     = ($page - 1) * $perPage;
        // Lấy tổng số users (để biết có bao nhiêu trang)
        $totalUsers = $CMSNT->num_rows_safe("SELECT `id` FROM `users`", []);
        $totalPages = ceil($totalUsers / $perPage);

        // Lấy danh sách user (xếp thứ tự giảm dần theo `total_money`) theo trang
        $users = $CMSNT->get_list_safe("
            SELECT * 
            FROM `users` 
            ORDER BY `total_money` DESC 
            LIMIT ? OFFSET ?
        ", [$perPage, $offset]);

        // Mở đầu thông báo
        $message  = "💹 *TOP Người dùng nạp tiền (Trang $page/$totalPages)*\n";
        $message .= "------------------------------------\n";

        // Mảng huy chương
        $medals = ["🥇", "🥈", "🥉"];

        // Vòng lặp hiển thị từng user
        foreach ($users as $index => $user) {
            // Tính thứ hạng toàn cục:
            // Bản ghi đầu tiên trên trang này chính là hạng = $offset + 1, sau đó +$index
            $globalRank = $offset + $index + 1;

            // Nếu hạng <= 3, lấy huy chương tương ứng, nếu không thì để trống
            $medal = ($globalRank <= 3) ? $medals[$globalRank - 1] : "";

            // Sử dụng Markdown cho đẹp
            $message .= "*Hạng:* `$globalRank` $medal\n";
            $message .= "*Username:* `".$user['username']."`\n";
            $message .= "💰 *Tổng nạp:* *".format_currency($user['total_money'])."*\n";
            $message .= "------------------------------------\n";
        }

        // Tạo nút phân trang
        $inline_keyboard = [];
        if ($page > 1) {
            $inline_keyboard[] = [[
                'text'          => '⬅️ Trang trước',
                'callback_data' => 'topusers ' . ($page - 1)
            ]];
        }
        if ($page < $totalPages) {
            $inline_keyboard[] = [[
                'text'          => '➡️ Trang sau',
                'callback_data' => 'topusers ' . ($page + 1)
            ]];
        }

        // Gửi hoặc chỉnh sửa tin nhắn (nếu có $GLOBALS['message_id'])
        $replyMarkup = json_encode(['inline_keyboard' => $inline_keyboard]);
        $message_id  = $GLOBALS['message_id'] ?? null;
        sendMessageWithButtons($chat_id, $message, $replyMarkup, $message_id);
    },


    'siteinfo' => function() use ($chat_id, $CMSNT) {
        $site_status = $CMSNT->site('status') == 1 ? 'Hoạt động' : 'Bảo trì';
        sendMessage($chat_id, "🌐 *Thông tin website:*\n".
            "Tên site: *" . $CMSNT->site('title') . "*\n".
            "Trạng thái: *$site_status*\n".
            "Người dùng: *" . $CMSNT->num_rows_safe("SELECT `id` FROM `users`", []) . "*");
    },
    'changepassword' => function($params) use ($chat_id, $CMSNT) {
        [$usernameParam, $new_passwordParam] = $params;
        if (empty($usernameParam) || empty($new_passwordParam)) {
            sendMessage($chat_id, "⚠️ *Vui lòng nhập:* `/changepassword username mật_khẩu_mới`");
            return;
        }
        $username = validate_alphanumeric($usernameParam, 50);
        $new_password_result = validate_password($new_passwordParam, 6, 50);
        if ($username === false || !$new_password_result['success']) {
            $error_msg = "❌ Tham số không hợp lệ (username/mật khẩu).";
            if (!$new_password_result['success']) {
                $error_msg .= "\n🔐 Mật khẩu: " . $new_password_result['message'];
            }
            return sendMessage($chat_id, $error_msg);
        }
        $new_password = $new_password_result['password'];
        if (!$CMSNT->get_row_safe("SELECT * FROM `users` WHERE `username` = ?", [$username])) {
            return sendMessage($chat_id, "⚠️ *Không tìm thấy thông tin tài khoản: $username*");
        }
        $CMSNT->update('users', ['password' => TypePassword($new_password)], " `username` = ? ", [$username]);
        sendMessage($chat_id, "✅ Mật khẩu của `$username` đã được thay đổi.");
    },
    'setstatus' => function($params) use ($chat_id, $CMSNT) {
        [$statusParam] = $params;
        $status = validate_int($statusParam, 0, 1);
        if ($status === false)  {
            sendMessage($chat_id, "⚠️ *Vui lòng nhập:* /setstatus 0 hoặc 1");
            return;
        }
        $CMSNT->update('settings', ['value' => $status], " `name` = 'status' ");
        sendMessage($chat_id, "✅ " . ($status == 1 ? "Bật" : "Tắt") . " bảo trì thành công.");
    },
    'revenuetoday' => function() use ($chat_id, $CMSNT) {
        // -----------------------------
        // 1. Tính thời gian HÔM NAY
        // -----------------------------
        $currentDate = date("Y-m-d"); // ví dụ: 2025-01-28 (ngày hôm nay)

        // Lấy dữ liệu (doanh thu, chi phí) HÔM NAY
        $queryToday = "
            SELECT 
                COUNT(id) AS total_orders_today, 
                SUM(pay)  AS total_pay_today, 
                SUM(cost) AS total_cost_today
            FROM `product_order`
            WHERE `refund` = 0
            AND `create_gettime` LIKE ? 
        ";
        $resultToday = $CMSNT->get_row_safe($queryToday, [$currentDate.'%']);

        $total_orders_today = $resultToday['total_orders_today'] ?? 0;
        $total_pay_today    = $resultToday['total_pay_today']    ?? 0;
        $total_cost_today   = $resultToday['total_cost_today']   ?? 0;
        $profit_today       = $total_pay_today - $total_cost_today;

        // Lấy số user đăng ký mới HÔM NAY
        $new_users_today = $CMSNT->get_row_safe("
            SELECT COUNT(id) AS total_users_today 
            FROM `users`
            WHERE `create_date` LIKE ?
        ", [$currentDate])['total_users_today'] ?? 0;

        // -----------------------------
        // 2. Tính thời gian HÔM QUA
        // -----------------------------
        // Lấy ngày hôm qua
        $yesterdayDate = date("Y-m-d", strtotime("-1 day"));

        // Lấy dữ liệu (doanh thu, chi phí) NGÀY HÔM QUA
        $queryYesterday = "
            SELECT 
                SUM(pay)  AS total_pay_yesterday,
                SUM(cost) AS total_cost_yesterday
            FROM `product_order`
            WHERE `refund` = 0
            AND `create_gettime` LIKE ?
        ";
        $resultPrev = $CMSNT->get_row_safe($queryYesterday, [$yesterdayDate.'%']);

        $total_pay_yesterday  = $resultPrev['total_pay_yesterday']  ?? 0;
        $total_cost_yesterday = $resultPrev['total_cost_yesterday'] ?? 0;
        $profit_yesterday     = $total_pay_yesterday - $total_cost_yesterday;

        // -----------------------------
        // 3. Tính mức chênh lệch lợi nhuận
        // -----------------------------
        // Tránh lỗi chia cho 0 nếu hôm qua không có đơn hàng
        if ($profit_yesterday == 0) {
            $difference  = $profit_today;
            $percentDiff = 0;
        } else {
            $difference  = $profit_today - $profit_yesterday;
            $percentDiff = ($difference / $profit_yesterday) * 100;
        }
        $percentDiff = round($percentDiff, 2);

        // Xác định hiển thị mũi tên/emoji XANH (tăng) hoặc ĐỎ (giảm)
        if ($difference > 0) {
            $trendEmoji  = "🟢";  // Hoặc "📈"
            $trendString = "*Tăng:* +{$percentDiff}%";
        } elseif ($difference < 0) {
            $trendEmoji  = "🔴";  // Hoặc "📉"
            $trendString = "*Giảm:* {$percentDiff}%";
        } else {
            $trendEmoji  = "⚪️";
            $trendString = "*Không thay đổi* (0%)";
        }

        // -----------------------------
        // 4. Chuẩn bị nội dung hiển thị
        // -----------------------------
        $message  = "📊 *BÁO CÁO DOANH THU HÔM NAY*\n";
        $message .= "------------------------------------\n";
        $message .= "📅 *Ngày:* `{$currentDate}`\n";
        $message .= "------------------------------------\n";
        $message .= "🛒 *Tổng đơn hàng:* `".format_cash($total_orders_today)."`\n";
        $message .= "💰 *Tổng doanh thu:* *".format_currency($total_pay_today)."*\n";
        $message .= "💸 *Tổng chi phí:* *".format_currency($total_cost_today)."*\n";
        $message .= "📈 *Lợi nhuận:* *".format_currency($profit_today)."*\n";
        $message .= "👥 *Người dùng mới:* `".format_cash($new_users_today)."`\n";
        $message .= "------------------------------------\n";
        $message .= "{$trendEmoji} So với hôm qua: {$trendString}\n";

        // Gửi tin nhắn
        sendMessage($chat_id, $message);
    },

    'revenueweek' => function() use ($chat_id, $CMSNT) {
        // -----------------------------
        // 1. Tính thời gian TUẦN NÀY
        // -----------------------------
        $startOfWeek = date("Y-m-d", strtotime('monday this week')); 
        $endOfWeek   = date("Y-m-d", strtotime('sunday this week'));

        // Lấy dữ liệu (doanh thu, chi phí) TUẦN NÀY
        $queryThisWeek = "
            SELECT 
                COUNT(id) AS total_orders_week, 
                SUM(pay) AS total_pay_week, 
                SUM(cost) AS total_cost_week
            FROM `product_order`
            WHERE `refund` = 0
            AND `create_gettime` BETWEEN '? 00:00:00' AND '? 23:59:59'
        ";
        $resultThis = $CMSNT->get_row_safe($queryThisWeek, [$startOfWeek, $endOfWeek]);

        $total_orders_week = $resultThis['total_orders_week'] ?? 0;
        $total_pay_week    = $resultThis['total_pay_week']    ?? 0;
        $total_cost_week   = $resultThis['total_cost_week']   ?? 0;
        $profit_week       = $total_pay_week - $total_cost_week;

        // Lấy số user đăng ký mới trong TUẦN NÀY
        $new_users_week = $CMSNT->get_row_safe("
            SELECT COUNT(id) AS total_users_week 
            FROM `users`
            WHERE `create_date` BETWEEN ? AND ?
        ", [$startOfWeek, $endOfWeek])['total_users_week'] ?? 0;

        // -----------------------------
        // 2. Tính thời gian TUẦN TRƯỚC
        // -----------------------------
        // Tính 'monday last week' và 'sunday last week'
        // Cách tính: lùi 1 tuần so với Monday/Sunday this week
        $previousWeekStart = date("Y-m-d", strtotime('monday last week')); 
        $previousWeekEnd   = date("Y-m-d", strtotime('sunday last week'));

        // Lấy dữ liệu (doanh thu, chi phí) TUẦN TRƯỚC
        $queryPrevWeek = "
            SELECT 
                SUM(pay) AS total_pay_prev,
                SUM(cost) AS total_cost_prev
            FROM `product_order`
            WHERE `refund` = 0
            AND `create_gettime` BETWEEN '? 00:00:00' AND '? 23:59:59'
        ";
        $resultPrev = $CMSNT->get_row_safe($queryPrevWeek, [$previousWeekStart, $previousWeekEnd]);

        $total_pay_prev  = $resultPrev['total_pay_prev']  ?? 0;
        $total_cost_prev = $resultPrev['total_cost_prev'] ?? 0;
        $profit_previous = $total_pay_prev - $total_cost_prev;

        // -----------------------------
        // 3. Tính mức chênh lệch lợi nhuận
        // -----------------------------
        // Tránh lỗi chia cho 0 nếu tuần trước không có đơn hàng
        if ($profit_previous == 0) {
            $difference  = $profit_week;
            $percentDiff = 0;
        } else {
            $difference  = $profit_week - $profit_previous;
            $percentDiff = ($difference / $profit_previous) * 100;
        }
        $percentDiff = round($percentDiff, 2);

        // Xác định hiển thị mũi tên/emoji XANH (tăng) hoặc ĐỎ (giảm)
        if ($difference > 0) {
            $trendEmoji  = "🟢";  // Hoặc "📈"
            $trendString = "*Tăng:* +{$percentDiff}%";
        } elseif ($difference < 0) {
            $trendEmoji  = "🔴";  // Hoặc "📉"
            $trendString = "*Giảm:* {$percentDiff}%";
        } else {
            $trendEmoji  = "⚪️";
            $trendString = "*Không thay đổi* (0%)";
        }

        // -----------------------------
        // 4. Chuẩn bị nội dung hiển thị
        // -----------------------------
        $message  = "📊 *BÁO CÁO DOANH THU TUẦN NÀY*\n";
        $message .= "------------------------------------\n";
        $message .= "📅 *Khoảng thời gian:* `{$startOfWeek}` → `{$endOfWeek}`\n";
        $message .= "------------------------------------\n";
        $message .= "🛒 *Tổng đơn hàng:* `".format_cash($total_orders_week)."`\n";
        $message .= "💰 *Tổng doanh thu:* *".format_currency($total_pay_week)."*\n";
        $message .= "💸 *Tổng chi phí:* *".format_currency($total_cost_week)."*\n";
        $message .= "📈 *Lợi nhuận:* *".format_currency($profit_week)."*\n";
        $message .= "👥 *Người dùng mới:* `".format_cash($new_users_week)."`\n";
        $message .= "------------------------------------\n";
        $message .= "{$trendEmoji} So với tuần trước: {$trendString}\n";

        // Gửi tin nhắn
        sendMessage($chat_id, $message);
    },

    'revenuemonth' => function() use ($chat_id, $CMSNT) {
        // -----------------------------
        // 1. Tính thời gian THÁNG NÀY
        // -----------------------------
        $startOfMonth = date("Y-m-01");       // đầu tháng này
        $endOfMonth   = date("Y-m-t");        // cuối tháng này

        // Lấy dữ liệu (doanh thu, chi phí) THÁNG NÀY
        $queryThisMonth = "
            SELECT 
                COUNT(id) AS total_orders_month, 
                SUM(pay) AS total_pay_month, 
                SUM(cost) AS total_cost_month
            FROM `product_order`
            WHERE `refund` = 0
            AND `create_gettime` BETWEEN ? AND ?
        ";
        $resultThis = $CMSNT->get_row_safe($queryThisMonth, [$startOfMonth.' 00:00:00', $endOfMonth.' 23:59:59']);

        $total_orders_month = $resultThis['total_orders_month'] ?? 0;
        $total_pay_month    = $resultThis['total_pay_month']    ?? 0;
        $total_cost_month   = $resultThis['total_cost_month']   ?? 0;
        $profit_month       = $total_pay_month - $total_cost_month;

        // Lấy số user đăng ký mới trong THÁNG NÀY
        $new_users_month = $CMSNT->get_row_safe("
            SELECT COUNT(id) AS total_users_month 
            FROM `users`
            WHERE `create_date` BETWEEN ? AND ?
        ", [$startOfMonth, $endOfMonth])['total_users_month'] ?? 0;

        // -----------------------------
        // 2. Tính thời gian THÁNG TRƯỚC
        // -----------------------------
        $previousMonthStart = date("Y-m-01", strtotime("-1 month")); // đầu tháng trước
        $previousMonthEnd   = date("Y-m-t",  strtotime("-1 month")); // cuối tháng trước

        // Lấy dữ liệu (doanh thu, chi phí) THÁNG TRƯỚC
        $queryPrevMonth = "
            SELECT 
                SUM(pay) AS total_pay_prev,
                SUM(cost) AS total_cost_prev
            FROM `product_order`
            WHERE `refund` = 0
            AND `create_gettime` BETWEEN ? AND ?
        ";
        $resultPrev = $CMSNT->get_row_safe($queryPrevMonth, [$previousMonthStart.' 00:00:00', $previousMonthEnd.' 23:59:59']);

        $total_pay_prev  = $resultPrev['total_pay_prev']  ?? 0;
        $total_cost_prev = $resultPrev['total_cost_prev'] ?? 0;
        $profit_previous = $total_pay_prev - $total_cost_prev;

        // -----------------------------
        // 3. Tính mức chênh lệch lợi nhuận
        // -----------------------------
        // (tránh lỗi chia cho 0 nếu tháng trước không có đơn hàng)
        if ($profit_previous == 0) {
            // Nếu tháng trước lợi nhuận = 0, ta có thể xét % là +100% hay hiển thị "Không xác định".
            $difference  = $profit_month - $profit_previous; // = profit_month
            $percentDiff = 0; // hoặc 100
        } else {
            $difference  = $profit_month - $profit_previous;
            $percentDiff = ($difference / $profit_previous) * 100;
        }

        // Định dạng 2 chữ số thập phân
        $percentDiff = round($percentDiff, 2);

        // Xác định hiển thị mũi tên/emoji XANH (tăng) hoặc ĐỎ (giảm)
        if ($difference > 0) {
            // Tăng
            $trendEmoji  = "🟢"; // Hoặc "📈"
            $trendString = "*Tăng:* +{$percentDiff}%";
        } elseif ($difference < 0) {
            // Giảm
            $trendEmoji  = "🔴"; // Hoặc "📉"
            $trendString = "*Giảm:* {$percentDiff}%";
        } else {
            // Bằng 0
            $trendEmoji  = "⚪️"; 
            $trendString = "*Không thay đổi* (0%)";
        }

        // -----------------------------
        // 4. Chuẩn bị nội dung hiển thị
        // -----------------------------
        // Làm đẹp giao diện hiển thị bằng Markdown
        $message  = "📊 *BÁO CÁO DOANH THU THÁNG NÀY*\n";
        $message .= "------------------------------------\n";
        $message .= "📅 *Khoảng thời gian:* `{$startOfMonth}` → `{$endOfMonth}`\n";
        $message .= "------------------------------------\n";
        $message .= "🛒 *Tổng đơn hàng:* `".format_cash($total_orders_month)."`\n";
        $message .= "💰 *Tổng doanh thu:* *".format_currency($total_pay_month)."*\n";
        $message .= "💸 *Tổng chi phí:* *".format_currency($total_cost_month)."*\n";
        $message .= "📈 *Lợi nhuận:* *".format_currency($profit_month)."*\n";
        $message .= "👥 *Người dùng mới:* `".format_cash($new_users_month)."`\n";
        $message .= "------------------------------------\n";

        // Thêm dòng So sánh với tháng trước
        $message .= "{$trendEmoji} So với tháng trước: {$trendString}\n";

        // Gửi tin nhắn
        sendMessage($chat_id, $message);
    },




    'orders' => function($params) use ($CMSNT, $chat_id) {
        $perPage = 5;
        // param[0] = số trang, nếu không có hoặc không hợp lệ thì = 1
        $page = (isset($params[0]) && is_numeric($params[0])) ? (int)$params[0] : 1;
        if ($page < 1) $page = 1;

        $offset      = ($page - 1) * $perPage;
        $totalOrders = $CMSNT->num_rows_safe("SELECT `id` FROM `product_order`", []);
        $totalPages  = ceil($totalOrders / $perPage);

        // Lấy danh sách đơn hàng
        $orders  = $CMSNT->get_list_safe("SELECT * FROM `product_order` 
                                     ORDER BY `id` DESC 
                                     LIMIT ? OFFSET ?", [$perPage, $offset]);

        $message = "📋 *Danh sách đơn hàng (Trang $page/$totalPages):*\n\n";
        foreach ($orders as $order) {
            $username = getRowRealtime('users', $order['buyer'], 'username');
            $message .= "🆔 *Mã giao dịch:* `" . $order['trans_id'] . "`\n";
            $message .= "📦 *Sản phẩm:* `" . $order['product_name'] . "`\n";
            $message .= "👤 *Người mua:* `$username`\n";
            $message .= "🔢 *Số lượng:* " . format_cash($order['amount']) . " - 💰 *Thanh toán:* " . format_currency($order['pay']) . "\n";
            $message .= "--------------------------\n";
        }

        // Tạo nút phân trang
        $inline_keyboard = [];
        if ($page > 1) {
            $inline_keyboard[] = [[
                'text'          => '⬅️ Trang trước',
                'callback_data' => 'orders ' . ($page - 1)
            ]];
        }
        if ($page < $totalPages) {
            $inline_keyboard[] = [[
                'text'          => '➡️ Trang sau',
                'callback_data' => 'orders ' . ($page + 1)
            ]];
        }

        // Gửi/ chỉnh sửa tin nhắn kèm nút
        $replyMarkup = json_encode(['inline_keyboard' => $inline_keyboard]);
        $message_id  = $GLOBALS['message_id'] ?? null; 
        sendMessageWithButtons($chat_id, $message, $replyMarkup, $message_id);
    },

    'checkorder' => function($params) use ($chat_id, $CMSNT) {
        [$trans_idParam] = $params;
        if (empty($trans_idParam)) {
            return sendMessage($chat_id, "⚠️ *Vui lòng nhập:* `/checkorder mã_đơn_hàng`");
        }

        $trans_id = validate_alphanumeric($trans_idParam, 100);
        if ($trans_id === false) {
            return sendMessage($chat_id, "❌ Mã đơn hàng không hợp lệ.");
        }

        // Tìm đơn hàng theo trans_id
        if (!$order = $CMSNT->get_row_safe("SELECT * FROM `product_order` WHERE `trans_id` = ?", [$trans_id])) {
            return sendMessage($chat_id, "⚠️ *Không tìm thấy đơn hàng:* `$trans_id`");
        }
        // Lấy dữ liễu sản phẩm
        $product = $CMSNT->get_row_safe("SELECT * FROM `products` WHERE `id` = ?", [$order['product_id']]);

        // Lấy tên người mua
        $buyerName = getRowRealtime("users", $order['buyer'], "username") 
                    ?: "N/A";

        // Nếu có supplier_id > 0 => Lấy domain nhà cung cấp
        // Nếu supplier_id = 0 => "Trong kho" + Mã kho (ở đây dùng product_id)
        if ($order['supplier_id'] > 0) {
            $supplierDomain = getRowRealtime('suppliers', $order['supplier_id'], 'domain') ?: '';
            $source = "Nhà cung cấp: `{$supplierDomain}`";
        } else {
            // Nhà cung cấp API không có => "Trong kho" kèm mã kho (product_id)
            $source = "Trong kho (Mã kho: `#".$product['code']."`)";
        }

        // Tính lợi nhuận
        $profit = $order['pay'] - $order['cost'];

        // Trạng thái refund
        $refundStatus = ($order['refund'] == 1) ? "Đã hoàn tiền" : "Thành công";

        // Tạo nội dung hiển thị
        $message  = "🔎 *Chi tiết đơn hàng*\n";
        $message .= "------------------------------------\n";
        $message .= "🆔 *Mã đơn hàng:* `#{$order['trans_id']}`\n";

        // Nếu có api_transid thì hiển thị
        if (!empty($order['api_transid'])) {
            $message .= "🌐 *Mã đơn hàng API:* `#{$order['api_transid']}`\n";
        }

        // Hiển thị nguồn hàng
        $message .= "🌐 *Nguồn hàng:* {$source}\n";

        // Người mua
        $message .= "👤 *Người mua:* `{$buyerName}` [ID ".$order['buyer']."]\n";
        $message .= "------------------------------------\n";

        // Số lượng, thanh toán, giá vốn, lợi nhuận
        $message .= "🔢 *Số lượng:* `".format_cash($order['amount'])."`\n";
        $message .= "💰 *Thanh toán:* *".format_currency($order['pay'])."*\n";
        $message .= "💸 *Giá vốn:* *".format_currency($order['cost'])."*\n";
        $message .= "💵 *Lợi nhuận:* *".format_currency($profit)."*\n";
        $message .= "------------------------------------\n";

        // Thông tin sản phẩm
        $message .= "📦 *Sản phẩm:* `{$order['product_name']}`\n";
        $message .= "📅 *Thời gian tạo:* `{$order['create_gettime']}`\n";
        $message .= "------------------------------------\n";
        $message .= "📌 *Trạng thái:* `{$refundStatus}`";

        // Gửi tin nhắn
        sendMessage($chat_id, $message);
    },

    'bestsellers' => function($params) use ($chat_id, $CMSNT) {
        // Số sản phẩm hiển thị mỗi trang
        $perPage = 10;

        // Lấy số trang từ tham số, nếu không có hoặc ko phải số thì mặc định trang 1
        $page = (isset($params[0]) && is_numeric($params[0])) ? (int)$params[0] : 1;
        if ($page < 1) {
            $page = 1;
        }
        $offset = ($page - 1) * $perPage;

        // 1) Đếm tổng số sản phẩm (distinct product_id) trong product_order (tránh đơn refund)
        $rowCount = $CMSNT->get_row_safe("
            SELECT COUNT(DISTINCT `product_id`) AS `total_products`
            FROM `product_order`
            WHERE `refund` = 0
        ", []);
        $totalProducts = $rowCount ? $rowCount['total_products'] : 0;
        $totalPages    = ($totalProducts > 0) ? ceil($totalProducts / $perPage) : 1;

        // 2) Lấy danh sách sản phẩm
        //    Tính SUM(amount) => total_sold, SUM(pay) => total_sales
        //    Sắp xếp theo total_sold giảm dần
        $products = $CMSNT->get_list_safe("
            SELECT 
                `product_id`,
                `product_name`,
                SUM(`amount`) AS `total_sold`,
                SUM(`pay`) AS `total_sales`
            FROM `product_order`
            WHERE `refund` = 0
            GROUP BY `product_id`
            ORDER BY `total_sold` DESC
            LIMIT ? OFFSET ?
        ", [$perPage, $offset]);

        // Mở đầu thông báo
        $message  = "🔥 *TOP Sản phẩm bán chạy (Trang $page/$totalPages)*\n";
        $message .= "------------------------------------\n";

        // Mảng huy chương cho TOP 3
        $medals = ["🥇", "🥈", "🥉"];

        // Vòng lặp hiển thị
        foreach ($products as $index => $item) {
            // Tính thứ hạng toàn cục
            $globalRank = $offset + $index + 1;

            // Nếu hạng <= 3, hiển thị huy chương
            $medal = ($globalRank <= 3) ? $medals[$globalRank - 1] : "";

            // Thêm dữ liệu vào $message
            $message .= "*Hạng:* `$globalRank` $medal\n";
            $message .= "*Tên sản phẩm:* `{$item['product_name']}`\n";
            $message .= "🔢 *Tổng bán:* *" . format_cash($item['total_sold']) . "*\n";
            $message .= "💰 *Tổng tiền:* *" . format_currency($item['total_sales']) . "*\n";
            $message .= "------------------------------------\n";
        }

        // Tạo nút phân trang
        $inline_keyboard = [];
        if ($page > 1) {
            $inline_keyboard[] = [[
                'text'          => '⬅️ Trang trước',
                'callback_data' => 'bestsellers ' . ($page - 1)
            ]];
        }
        if ($page < $totalPages) {
            $inline_keyboard[] = [[
                'text'          => '➡️ Trang sau',
                'callback_data' => 'bestsellers ' . ($page + 1)
            ]];
        }

        // Gửi hoặc chỉnh sửa tin nhắn (nếu có $GLOBALS['message_id'])
        $replyMarkup = json_encode(['inline_keyboard' => $inline_keyboard]);
        $message_id  = $GLOBALS['message_id'] ?? null;

        // Nếu chưa có sản phẩm nào => hiển thị thông báo
        if (!$products) {
            $message = "Không có dữ liệu sản phẩm bán chạy!";
        }

        sendMessageWithButtons($chat_id, $message, $replyMarkup, $message_id);
    },

    'logs' => function($params) use ($chat_id, $CMSNT) {
        // 1) Thiết lập phân trang
        $perPage = 10;
        $page = (isset($params[0]) && is_numeric($params[0])) ? (int)$params[0] : 1;
        if ($page < 1) {
            $page = 1;
        }
        $offset = ($page - 1) * $perPage;

        // 2) Đếm tổng số bản ghi logs
        $totalLogs = $CMSNT->num_rows_safe("SELECT `id` FROM `logs`", []);
        $totalPages = $totalLogs > 0 ? ceil($totalLogs / $perPage) : 1;

        // 3) Lấy danh sách logs (theo trang)
        //    ORDER BY id DESC, v.v.
        $listLogs = $CMSNT->get_list_safe("
            SELECT *
            FROM `logs`
            ORDER BY `id` DESC
            LIMIT ? OFFSET ?
        ", [$perPage, $offset]);

        // Mở đầu tin nhắn
        $message = "📜 *Nhật ký hoạt động (Trang $page/$totalPages)*\n";
        $message .= "------------------------------------\n";

        // Nếu không có dữ liệu
        if (!$listLogs) {
            $message .= "Không có dữ liệu logs.";
        } else {
            // 4) Duyệt từng bản ghi -> xây chuỗi
            foreach ($listLogs as $row) {
                // Lấy username từ bảng users (nếu tồn tại user_id)
                $username = getRowRealtime("users", $row['user_id'], "username") ?: "N/A";

                // Sử dụng timeAgo nếu muốn hiển thị thời gian tương đối
                $timeRelative = timeAgo(strtotime($row['createdate']));

                $message .= "👤 *Username:* `{$username}` [ID {$row['user_id']}]\n";
                $message .= "🔧 *Hành động:* `{$row['action']}`\n";
                $message .= "⏰ *Thời gian:* `{$row['createdate']}` (".$timeRelative.")\n";
                $message .= "🌐 *IP:* {$row['ip']}\n";
                $message .= "💻 *Thiết bị:* {$row['device']}\n";
                $message .= "------------------------------------\n";
            }
        }

        // 5) Nút bấm phân trang
        $inline_keyboard = [];
        if ($page > 1) {
            $inline_keyboard[] = [[
                'text'          => '⬅️ Trang trước',
                'callback_data' => 'logs ' . ($page - 1)
            ]];
        }
        if ($page < $totalPages) {
            $inline_keyboard[] = [[
                'text'          => '➡️ Trang sau',
                'callback_data' => 'logs ' . ($page + 1)
            ]];
        }

        // 6) Gửi tin nhắn/hoặc editMessageText nếu đã có $GLOBALS['message_id']
        $replyMarkup = json_encode(['inline_keyboard' => $inline_keyboard]);
        $message_id  = $GLOBALS['message_id'] ?? null;
        sendMessageWithButtons($chat_id, $message, $replyMarkup, $message_id);
    },

    'log' => function($params) use ($chat_id, $CMSNT) {
        // Tách tham số: [0] = username, [1] = (tuỳ chọn) page
        $username = $params[0] ?? null;
        $pageParam = $params[1] ?? null;

        if (empty($username)) {
            // Nếu không nhập username => hướng dẫn
            return sendMessage($chat_id, "⚠️ *Vui lòng nhập:* `/log username trang`\nVD: `/log ntthanhz 2`");
        }

        // Kiểm tra user có tồn tại hay không
        $usernameValid = validate_alphanumeric($username, 50);
        if ($usernameValid === false) {
            return sendMessage($chat_id, "❌ Username không hợp lệ.");
        }
        $user = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `username` = ?", [$usernameValid]);
        if (!$user) {
            return sendMessage($chat_id, "⚠️ *Không tìm thấy username:* `$username`");
        }

        // Page (nếu có), còn không thì = 1
        $page = (is_numeric($pageParam) && $pageParam > 0) ? (int)$pageParam : 1;
        if ($page < 1) {
            $page = 1;
        }

        $perPage = 10;
        $offset  = ($page - 1) * $perPage;

        // Đếm tổng số bản ghi logs của user này
        $user_id   = $user['id'];
        $totalLogs = $CMSNT->num_rows_safe("SELECT `id` FROM `logs` WHERE `user_id` = ?", [$user_id]);
        $totalPages = $totalLogs > 0 ? ceil($totalLogs / $perPage) : 1;

        // Lấy danh sách logs cho user này theo trang
        $listLogs = $CMSNT->get_list_safe("
            SELECT *
            FROM `logs`
            WHERE `user_id` = ?
            ORDER BY `id` DESC
            LIMIT ? OFFSET ?
        ", [$user_id, $perPage, $offset]);

        // Mở đầu tin nhắn
        $message = "📜 *Nhật ký hoạt động của* `{$username}` *(Trang $page/$totalPages)*\n";
        $message .= "------------------------------------\n";

        if (!$listLogs) {
            $message .= "Không có dữ liệu logs.";
        } else {
            foreach ($listLogs as $row) {
                $timeRelative = timeAgo(strtotime($row['createdate']));
                $message .= "🔧 *Hành động:* `{$row['action']}`\n";
                $message .= "⏰ *Thời gian:* `{$row['createdate']}` ($timeRelative)\n";
                $message .= "🌐 *IP:* {$row['ip']}\n";
                $message .= "💻 *Thiết bị:* {$row['device']}\n";
                $message .= "------------------------------------\n";
            }
        }

        // Tạo nút phân trang (callback_data = 'log username trang')
        $inline_keyboard = [];
        if ($page > 1) {
            $inline_keyboard[] = [[
                'text'          => '⬅️ Trang trước',
                'callback_data' => 'log '.$username.' '.($page - 1)
            ]];
        }
        if ($page < $totalPages) {
            $inline_keyboard[] = [[
                'text'          => '➡️ Trang sau',
                'callback_data' => 'log '.$username.' '.($page + 1)
            ]];
        }

        $replyMarkup = json_encode(['inline_keyboard' => $inline_keyboard]);
        $message_id  = $GLOBALS['message_id'] ?? null;

        sendMessageWithButtons($chat_id, $message, $replyMarkup, $message_id);
    },

    'topmoney' => function($params) use ($chat_id, $CMSNT) {
        // Số user hiển thị mỗi trang
        $perPage = 10;

        // Lấy số trang từ tham số, nếu không có hoặc ko phải số thì mặc định trang 1
        $page = (isset($params[0]) && is_numeric($params[0])) ? (int)$params[0] : 1;
        if ($page < 1) {
            $page = 1;
        }

        $offset     = ($page - 1) * $perPage;
        // Lấy tổng số users (để biết có bao nhiêu trang)
        $totalUsers = $CMSNT->num_rows_safe("SELECT `id` FROM `users`", []);
        $totalPages = ceil($totalUsers / $perPage);

        // Lấy danh sách user (xếp thứ tự giảm dần theo `money`) theo trang
        $users = $CMSNT->get_list_safe("
            SELECT * 
            FROM `users` 
            ORDER BY `money` DESC 
            LIMIT ? OFFSET ?
        ", [$perPage, $offset]);

        // Mở đầu thông báo
        $message  = "💹 *TOP Người dùng có số dư cao nhất (Trang $page/$totalPages)*\n";
        $message .= "------------------------------------\n";

        // Mảng huy chương
        $medals = ["🥇", "🥈", "🥉"];

        // Vòng lặp hiển thị từng user
        foreach ($users as $index => $user) {
            // Tính thứ hạng toàn cục:
            // Bản ghi đầu tiên trên trang này chính là hạng = $offset + 1, sau đó +$index
            $globalRank = $offset + $index + 1;

            // Nếu hạng <= 3, lấy huy chương tương ứng, nếu không thì để trống
            $medal = ($globalRank <= 3) ? $medals[$globalRank - 1] : "";

            // Sử dụng Markdown cho đẹp
            $message .= "*Hạng:* `$globalRank` $medal\n";
            $message .= "*Username:* `".$user['username']."`\n";
            $message .= "💰 *Số dư khả dựng:* *".format_currency($user['money'])."*\n";
            $message .= "------------------------------------\n";
        }

        // Tạo nút phân trang
        $inline_keyboard = [];
        if ($page > 1) {
            $inline_keyboard[] = [[
                'text'          => '⬅️ Trang trước',
                'callback_data' => 'topmoney ' . ($page - 1)
            ]];
        }
        if ($page < $totalPages) {
            $inline_keyboard[] = [[
                'text'          => '➡️ Trang sau',
                'callback_data' => 'topmoney ' . ($page + 1)
            ]];
        }

        // Gửi hoặc chỉnh sửa tin nhắn (nếu có $GLOBALS['message_id'])
        $replyMarkup = json_encode(['inline_keyboard' => $inline_keyboard]);
        $message_id  = $GLOBALS['message_id'] ?? null;
        sendMessageWithButtons($chat_id, $message, $replyMarkup, $message_id);
    },




];

// -----------------------------------------------------
// XỬ LÝ CALLBACK QUERY (KHI BẤM NÚT)
// -----------------------------------------------------
if ($callback_query) {
    // Lấy callback_data và tách thành command + params
    $callback_data = explode(' ', $callback_query['data']);
    $command       = $callback_data[0];
    $params        = array_slice($callback_data, 1);

    // Lấy message_id để edit
    $message_id = $callback_query['message']['message_id'];
    // Gán vào biến toàn cục, hàm orders() có thể sử dụng
    $GLOBALS['message_id'] = $message_id;

    // Nếu command tồn tại trong $commands
    if (isset($commands[$command])) {
        // ===> GHI LOG
        saveTelegramLog($sender_username, $command, $params, 'callback');
        // Gọi hàm xử lý
        $commands[$command]($params);

        // Trả lời callback để Telegram không báo "Loading..."
        file_get_contents("https://api.telegram.org/bot".$CMSNT->site('telegram_assistant_token')."/answerCallbackQuery?".
            http_build_query([
                'callback_query_id' => $callback_query['id'],
                'text'             => 'Tải dữ liệu thành công!',
                'show_alert'       => false
            ])
        );
        exit;
    } else {
        // Trường hợp command không tồn tại
        sendMessage($chat_id, "❌ Lệnh không hợp lệ: `$command`");
        exit;
    }
}



// -----------------------------------------------------
// XỬ LÝ TIN NHẮN GỬI TRỰC TIẾP (CÁC LỆNH /xxx)
// -----------------------------------------------------
if ($message) {
    // Nội dung tin nhắn (chuyển về lowercase và trim)
    $text = strtolower(trim($message['text']));

    // 1) Kiểm tra nếu tin nhắn KHÔNG bắt đầu bằng dấu '/'
    if (substr($text, 0, 1) !== '/') {
        // -> Đây không phải lệnh => bỏ qua, không báo gì.
        return;
    }

    // 2) Nếu bắt đầu bằng '/', ta tách lệnh
    preg_match('/^\/(\w+)\s*(.*)?$/', $text, $matches);
    $command = $matches[1] ?? '';
    $params  = array_filter(explode(' ', trim($matches[2] ?? '')));

    // 3) Nếu lệnh tồn tại trong mảng $commands, thực hiện
    if (isset($commands[$command])) {
        // Ghi log
        saveTelegramLog($sender_username, $command, $params, 'message');
        // Gọi hàm lệnh
        $commands[$command]($params);
    } else {
        // 4) Kiểm tra lệnh đặc biệt /help hoặc /cmd
        if (in_array($command, ['help', 'cmd'])) {
            // Gõ /help hoặc /cmd => hiển thị menu
            sendHelpMenu($chat_id);
        } else {
            // 5) Lệnh không tồn tại -> báo sai, gợi ý /help
            sendMessage($chat_id, "❌ *Lệnh không hợp lệ.*\nVui lòng gõ `/help` hoặc `/cmd` để xem danh sách lệnh.");
        }
    }
}


 

function sendHelpMenu($chat_id) {
    // Menu lệnh
    $helpMessage = 
"⚙️ **DANH SÁCH LỆNH QUẢN LÝ** ⚙️

------------------------------------
**[1.] LỆNH QUẢN LÝ TÀI KHOẢN**
------------------------------------
➕ *Cộng tiền:* `/addfund username số_tiền lý_do`  
➖ *Trừ tiền:* `/removefund username số_tiền lý_do`  
🚫 *Khóa tài khoản:* `/blockuser username lý_do`  
🔑 *Mở khóa tài khoản:* `/unblockuser username`  
🔍 *Kiểm tra số dư:* `/balance username`  
👤 *Thông tin tài khoản:* `/userinfo username`  
🔄 *Đổi mật khẩu:* `/changepassword username mật_khẩu_mới`  
📜 *Xem nhật ký user:* `/log username`

------------------------------------
**[2.] LỆNH QUẢN LÝ HỆ THỐNG**
------------------------------------
ℹ️ *Thông tin website:* `/siteinfo`  
🔄 *Bật/tắt trạng thái website:* `/setstatus 0 hoặc 1`  
📜 *Xem nhật ký hoạt động:* `/logs`

------------------------------------
**[3.] LỆNH QUẢN LÝ CHUNG**
------------------------------------
🛒 *Đơn hàng gần đây:* `/orders`  
📄 *Chi tiết đơn hàng:* `/checkorder mã_đơn_hàng`

------------------------------------
**[4.] LỆNH THỐNG KÊ - BÁO CÁO**
------------------------------------
📅 *Doanh thu hôm nay:* `/revenuetoday`  
📅 *Doanh thu tuần này:* `/revenueweek`  
📅 *Doanh thu tháng này:* `/revenuemonth`  
📊 *Danh sách thành viên:* `/listusers`  
🏆 *TOP Nạp tiền:* `/topusers`  
🏆 *TOP Số dư:* `/topmoney`  
🏆 *TOP Sản phẩm bán chạy:* `/bestsellers`

------------------------------------
**VÍ DỤ SỬ DỤNG**
------------------------------------
`/addfund johndoe 50000 hoàn tiền đơn hàng`
";

    sendMessage($chat_id, $helpMessage);
}
// -----------------------------------------------------
// (Tùy chọn) CÀI ĐẶT LỆNH GỢI Ý CHO BOT
// -----------------------------------------------------
function setBotCommands($botToken) {
    $commands = [
        ['command' => 'help', 'description' => 'Xem danh sách lệnh: /help'],
        ['command' => 'cmd', 'description' => 'Xem danh sách lệnh: /cmd'],

        ['command' => 'addfund', 'description' => 'Cộng tiền: /addfund username số_tiền lý_do'],
        ['command' => 'removefund', 'description' => 'Trừ tiền: /removefund username số_tiền lý_do'],
        ['command' => 'blockuser', 'description' => 'Khóa tài khoản: /blockuser username lý_do'],
        ['command' => 'unblockuser', 'description' => 'Mở khóa tài khoản: /unblockuser username'],
        ['command' => 'balance', 'description' => 'Kiểm tra số dư: /balance username'],
        ['command' => 'userinfo', 'description' => 'Thông tin tài khoản: /userinfo username'],
        ['command' => 'changepassword', 'description' => 'Thay đổi mật khẩu: /changepassword username mật_khẩu_mới'],
        ['command' => 'log', 'description' => 'Xem nhật ký hoạt động từng user: /log username'],
        
        ['command' => 'siteinfo', 'description' => 'Thông tin website: /siteinfo'],
        ['command' => 'setstatus', 'description' => 'Bật/tắt trạng thái website: /setstatus 0 hoặc 1'],
        ['command' => 'logs', 'description' => 'Xem nhật ký hoạt động: /logs'],

        ['command' => 'orders', 'description' => 'Đơn hàng gần đây: /orders'],
        ['command' => 'checkorder', 'description' => 'Chi tiết đơn hàng: /checkorder mã_đơn_hàng'],
        
        ['command' => 'revenuetoday', 'description' => 'Doanh thu hôm nay: /revenuetoday'],
        ['command' => 'revenueweek', 'description' => 'Doanh thu tuần này: /revenueweek'],
        ['command' => 'revenuemonth', 'description' => 'Doanh thu tháng này: /revenuemonth'],
        
        ['command' => 'listusers', 'description' => 'Danh sách thành viên: /listusers'],
        ['command' => 'topusers', 'description' => 'TOP Nạp tiền: /topusers'],
        ['command' => 'topmoney', 'description' => 'TOP Số dư: /topmoney'],
        ['command' => 'bestsellers', 'description' => 'TOP Sản phẩm bán chạy: /bestsellers']
    ];
    $url  = "https://api.telegram.org/bot$botToken/setMyCommands";
    $data = json_encode(['commands' => $commands]);

    $options = [
        'http' => [
            'header'  => "Content-Type: application/json",
            'method'  => 'POST',
            'content' => $data
        ]
    ];
    $context = stream_context_create($options);
    $result  = file_get_contents($url, false, $context);

    return $result ? "Đã thiết lập lệnh gợi ý thành công!" : "Lỗi thiết lập lệnh gợi ý.";
}

$botToken = $CMSNT->site('telegram_assistant_token');
setBotCommands($botToken);


 
 