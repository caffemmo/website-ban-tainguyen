<?php

$token = "8124095809:AAE3x4Xb3lCyvTxVoHwfuBgyYB6ViDNV-BQ";
$api = "https://api.telegram.org/bot$token/";

$update = json_decode(file_get_contents("php://input"), true);

$chat_id = $update["message"]["chat"]["id"] ?? null;
$text = $update["message"]["text"] ?? "";

if (!$chat_id) exit;

// Lệnh /start
if ($text == "/start") {
    $msg = "🤖 Bot Check UID Facebook\n\nGõ /checkuid để bắt đầu kiểm tra.";
    file_get_contents($api."sendMessage?chat_id=$chat_id&text=" . urlencode($msg));
    exit;
}

// Lệnh /checkuid
if ($text == "/checkuid") {
    file_get_contents($api."sendMessage?chat_id=$chat_id&text=👉 Hãy gửi UID Facebook cần check");
    exit;
}

// Nếu người dùng gửi UID
if (is_numeric($text)) {
    $uid = trim($text);

    // Gửi request tới web trung gian
    $url = "https://checkuid.live/api.php?uid=" . $uid;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64)");
    $response = curl_exec($ch);
    curl_close($ch);

    // Phân tích kết quả
    if (strpos($response, "LIVE") !== false) {
        $status = "✅ LIVE";
    } elseif (strpos($response, "DIE") !== false) {
        $status = "❌ DIE";
    } else {
        $status = "⚠️ Không xác định";
    }

    $msg = "🔍 UID: $uid\n📊 Trạng thái: $status";
    file_get_contents($api."sendMessage?chat_id=$chat_id&text=" . urlencode($msg));
}
?>
