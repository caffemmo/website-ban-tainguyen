<?php

if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

if (!class_exists('SecurityValidator')) {
    require_once __DIR__ . '/session.php';
}

/**
 * Khởi tạo giao dịch Korapay (Checkout Redirect)
 *
 * @param string $secretKey      Secret key của bạn trên Korapay
 * @param array  $params         Mảng dữ liệu chứa thông tin giao dịch
 * @return array|false           Trả về mảng JSON (decode) từ Korapay hoặc false nếu lỗi
 */
function korapayInitializeCharge($secretKey, $params){
    global $CMSNT;
    // Endpoint khởi tạo giao dịch
    $url = "https://api.korapay.com/merchant/api/v1/charges/initialize";

    // Header yêu cầu
    $headers = [
        "Authorization: Bearer $secretKey",
        "Content-Type: application/json",
        "Referer: https://korapay.com"
    ];

    // Chuyển mảng $params thành JSON
    $payload = json_encode($params);

    // Khởi tạo cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


    // Lấy proxy với định dạng "ip:port" hoặc "ip:port:user:pass"
    $proxyString = $CMSNT->site('korapay_proxy');
    if (!empty($proxyString)) {
        $proxyParts = explode(':', $proxyString);
        // Nếu có ít nhất 2 phần (ip và port)
        if(count($proxyParts) >= 2) {
            $proxy_ip = $proxyParts[0];
            $proxy_port = $proxyParts[1];
            curl_setopt($ch, CURLOPT_PROXY, "$proxy_ip:$proxy_port");

            // Nếu có đủ 4 phần, tức là có user và pass
            if(count($proxyParts) == 4) {
                $proxy_user = $proxyParts[2];
                $proxy_pass = $proxyParts[3];
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, "$proxy_user:$proxy_pass");
            }
        }
    }

    // Thực thi
    $response = curl_exec($ch);
    $error    = curl_error($ch);
    curl_close($ch);

    // Kiểm tra lỗi cURL
    if ($error) {
        // Xử lý lỗi theo nhu cầu
        return $error;
    }

    // Giải mã JSON trả về
    return json_decode($response, true);
}



/**
 * Xác thực trạng thái giao dịch Korapay
 *
 * @param string $secretKey      Secret key của bạn trên Korapay
 * @param string $reference      Mã tham chiếu (reference) của giao dịch
 * @return array|false           Trả về mảng JSON (decode) từ Korapay hoặc false nếu lỗi
 */
function korapayVerifyCharge($secretKey, $reference)
{
    global $CMSNT;
    // Giả định endpoint verify là như sau (có thể thay đổi tùy theo tài liệu chính thức):
    $url = "https://api.korapay.com/merchant/api/v1/charges/" . urlencode($reference);

    $headers = [
        "Authorization: Bearer $secretKey",
        "Content-Type: application/json"
    ];

    // Khởi tạo cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64)");

    // Lấy proxy với định dạng "ip:port" hoặc "ip:port:user:pass"
    $proxyString = $CMSNT->site('korapay_proxy');
    if (!empty($proxyString)) {
        $proxyParts = explode(':', $proxyString);
        // Nếu có ít nhất 2 phần (ip và port)
        if(count($proxyParts) >= 2) {
            $proxy_ip = $proxyParts[0];
            $proxy_port = $proxyParts[1];
            curl_setopt($ch, CURLOPT_PROXY, "$proxy_ip:$proxy_port");

            // Nếu có đủ 4 phần, tức là có user và pass
            if(count($proxyParts) == 4) {
                $proxy_user = $proxyParts[2];
                $proxy_pass = $proxyParts[3];
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, "$proxy_user:$proxy_pass");
            }
        }
    }

    // Thực thi
    $response = curl_exec($ch);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        // Xử lý lỗi theo nhu cầu
        return $error;
    }

    return json_decode($response, true);
    //return $response;
}
