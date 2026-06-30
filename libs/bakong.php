<?php

if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

if (!class_exists('SecurityValidator')) {
    require_once __DIR__ . '/session.php';
}

/**
 * Tạo nút thanh toán Bakong
 * @param array $params Các tham số cần thiết
 * @return string JSON response
 */
function createPaymentBakong($params) {
    global $CMSNT;
    // Kiểm tra các tham số bắt buộc
    if (!isset($params['transaction_id']) || !isset($params['amount']) || !isset($params['success_url'])) {
        return json_encode([
            'status' => 'error',
            'message' => 'Thiếu tham số bắt buộc'
        ]);
    }

    // Cấu hình API
    $my_payment_url = "https://raksmeypay.com/payment/request/".$CMSNT->site('bakong_profile_id');
    $profile_key = $CMSNT->site('bakong_profile_key');

    // Lấy các tham số
    $transaction_id = intval($params['transaction_id']);
    $amount = floatval($params['amount']);
    $success_url = urlencode($params['success_url']);
    $remark = isset($params['remark']) ? $params['remark'] : '';

    // Tạo hash theo đúng công thức API
    $hash = sha1($profile_key . $transaction_id . $amount . $success_url . $remark);

    // Tạo query string
    $parameters = [
        "transaction_id" => $transaction_id,
        "amount" => $amount,
        "success_url" => $success_url,
        "remark" => $remark,
        "hash" => $hash
    ];
    $queryString = http_build_query($parameters);
    $payment_link_url = $my_payment_url . "?" . $queryString;
    return $payment_link_url;
}

/**
 * Xác thực giao dịch Bakong
 * @param int $transaction_id ID giao dịch
 * @param float $amount Số tiền giao dịch
 * @return array Kết quả xác thực
 */
function verifyPaymentBakong($transaction_id, $amount) {
    global $CMSNT;
    
    // Cấu hình API
    $payment_verify_url = "https://raksmeypay.com/api/payment/verify/".$CMSNT->site('bakong_profile_id');
    $profile_key = $CMSNT->site('bakong_profile_key');

    // Tạo hash theo công thức API
    $hash = sha1($profile_key . $transaction_id);

    // Chuẩn bị dữ liệu
    $data = [
        "transaction_id" => intval($transaction_id),
        "hash" => $hash
    ];

    // Gọi API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $payment_verify_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Lấy proxy với định dạng "ip:port" hoặc "ip:port:user:pass"
    $proxyString = $CMSNT->site('bakong_proxy');
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


    $response = curl_exec($ch);
    curl_close($ch);
     
    // Xử lý response
    $response = json_decode($response, true);

    // Kiểm tra kết quả
    if (!empty($response["payment_status"]) 
        && strtoupper($response["payment_status"]) == "SUCCESS" 
        && $response["payment_amount"] == $amount) {
        return [
            'status' => true,
            'message' => 'Thanh toán thành công',
            'data' => $response
        ];
    } else if (!empty($response["payment_status"]) && strtoupper($response["payment_status"]) == "PENDING") {
        return [
            'status' => false,
            'message' => 'Thanh toán đang chờ xử lý',
            'data' => $response
        ];
    } else if (!empty($response["err_msg"])) {
        return [
            'status' => false,
            'message' => $response["err_msg"],
            'data' => $response
        ];
    }

    return [
        'status' => false,
        'message' => 'Không thể xác thực giao dịch',
        'data' => $response
    ];
}

