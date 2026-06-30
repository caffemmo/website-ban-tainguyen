<?php
/**
 * PaymentPoint Payment Gateway Library
 * API Documentation: https://paymentpoint.co
 */

if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

/**
 * Tạo Virtual Account PaymentPoint
 * 
 * @param string $apiSecret - Bearer token từ PaymentPoint
 * @param string $apiKey - API Key từ PaymentPoint
 * @param array $params - Tham số giao dịch
 * @return array|false
 */
function paymentpointCreateVirtualAccount($apiSecret, $apiKey, $params) {
    global $CMSNT;
    
    $url = 'https://api.paymentpoint.co/api/v1/createVirtualAccount';
    
    $postData = json_encode($params);
    
    $headers = array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiSecret,
        'api-key: ' . $apiKey
    );
    
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return array(
            'status' => 'error',
            'message' => 'CURL Error: ' . $error
        );
    }
    
    $result = json_decode($response, true);
    
    if ($result === null) {
        return array(
            'status' => 'error',
            'message' => 'Invalid JSON response'
        );
    }
    
    return $result;
}

/**
 * Lấy thông tin Virtual Account đã tạo cho user
 * Kiểm tra xem user đã có virtual account chưa
 * 
 * @param int $userId - ID user
 * @return array|false
 */
function paymentpointGetUserVirtualAccount($userId) {
    global $CMSNT;
    
    $account = $CMSNT->get_row_safe(
        "SELECT * FROM `payment_paymentpoint` WHERE `user_id` = ? AND `account_number` IS NOT NULL ORDER BY `id` DESC LIMIT 1",
        [$userId]
    );
    
    return $account;
}


