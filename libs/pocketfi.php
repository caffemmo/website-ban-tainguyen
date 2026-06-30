<?php
/**
 * PocketFi Payment Gateway Library
 * API Documentation: https://pocketfi.org
 */

if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

/**
 * Khởi tạo giao dịch PocketFi
 * 
 * @param string $apiToken - Bearer token từ PocketFi
 * @param array $params - Tham số giao dịch
 * @return array|false
 */
function pocketfiInitializeCharge($apiToken, $params) {
    global $CMSNT;
    
    $url = 'https://api.pocketfi.ng/api/v1/checkout/request';
    
    $postData = json_encode($params);
    
    $headers = array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiToken
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
 * Xác nhận/Kiểm tra trạng thái giao dịch PocketFi
 * 
 * @param string $apiToken - Bearer token từ PocketFi
 * @param string $paymentId - Mã giao dịch PocketFi (VD: PFI|7000439313)
 * @return array|false
 */
function pocketfiVerifyCharge($apiToken, $paymentId) {
    global $CMSNT;
    
    $url = 'https://api.pocketfi.ng/api/v1/checkout/confirm';
    
    $postData = json_encode(array(
        'payment_id' => $paymentId
    ));
    
    $headers = array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiToken
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

