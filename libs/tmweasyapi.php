<?php

if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

if (!class_exists('SecurityValidator')) {
    require_once __DIR__ . '/session.php';
}

/**
 * Hàm gọi API "แม่มณี พร้อมเพย์" bằng phương thức GET
 * @param array $params Danh sách param (key => value) gửi kèm
 * @return array|false Trả về mảng decode (JSON) nếu thành công, hoặc false nếu lỗi
 */
function callMaemaneeApi(array $params)
{
    // URL API (chọn 1 trong 2 link)
    // 1) https://tmwallet.thaighost.net/api_mn.php
    // 2) https://www.tmweasy.com/api_mn.php
    $apiUrl = "https://tmwallet.thaighost.net/api_mn.php";
    
    $queryString = http_build_query($params);
    $fullUrl     = $apiUrl . '?' . $queryString;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $fullUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return false;
    }
    
    // Giả định API trả về JSON
    $jsonData = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        return $jsonData;
    }
    return false;
}