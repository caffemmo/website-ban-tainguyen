<?php

/**
 * TriPay Payment Gateway Library
 * API Documentation: https://tripay.co.id/developer
 * 
 * TriPay là cổng thanh toán Indonesia hỗ trợ:
 * - Virtual Account (VA): BRI, BNI, BCA, Mandiri, Permata, CIMB, BSI, Muamalat, Danamon
 * - E-Wallet: OVO, QRIS, ShopeePay, Dana, LinkAja
 * - Retail: Alfamart, Indomaret
 */

if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

/**
 * Lấy Base URL API dựa vào môi trường
 * 
 * @param bool $sandbox - true = sandbox, false = production
 * @return string - API Base URL
 */
function tripayGetBaseUrl($sandbox = false)
{
    return $sandbox ? 'https://tripay.co.id/api-sandbox/' : 'https://tripay.co.id/api/';
}

/**
 * Tạo chữ ký HMAC-SHA256 cho Closed Payment
 * 
 * @param string $merchantCode - Mã merchant từ dashboard
 * @param string $merchantRef - Mã đơn hàng nội bộ
 * @param int $amount - Số tiền (integer)
 * @param string $privateKey - Private Key từ dashboard
 * @return string - Chữ ký
 */
function tripayGenerateSignature($merchantCode, $merchantRef, $amount, $privateKey)
{
    return hash_hmac('sha256', $merchantCode . $merchantRef . $amount, $privateKey);
}

/**
 * Gửi HTTP Request đến TriPay API
 * 
 * @param string $method - GET hoặc POST
 * @param string $endpoint - Endpoint (không có base URL)
 * @param array $config - Cấu hình API (api_key, private_key, merchant_code, sandbox)
 * @param array $data - Dữ liệu gửi đi
 * @return array|false - Kết quả JSON hoặc false nếu lỗi
 */
function tripayRequest($method, $endpoint, $config, $data = [])
{
    $sandbox = isset($config['sandbox']) ? (bool)$config['sandbox'] : false;
    $baseUrl = tripayGetBaseUrl($sandbox);
    $url = $baseUrl . ltrim($endpoint, '/');

    if ($method === 'GET' && !empty($data)) {
        $url .= '?' . http_build_query($data);
    }

    $curl = curl_init();

    $options = [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $config['api_key'],
            'Content-Type: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ];

    if ($method === 'POST') {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = json_encode($data);
    }

    curl_setopt_array($curl, $options);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);

    curl_close($curl);

    if ($error) {
        return [
            'success' => false,
            'message' => 'CURL Error: ' . $error
        ];
    }

    $result = json_decode($response, true);

    if ($result === null) {
        return [
            'success' => false,
            'message' => 'Invalid JSON response: ' . $response
        ];
    }

    return $result;
}

/**
 * Lấy danh sách kênh thanh toán
 * 
 * @param array $config - Cấu hình API
 * @return array - Danh sách payment channels
 */
function tripayGetPaymentChannels($config)
{
    return tripayRequest('GET', 'merchant/payment-channel', $config);
}

/**
 * Tính phí giao dịch
 * 
 * @param array $config - Cấu hình API
 * @param string $code - Mã kênh thanh toán (VD: BRIVA, QRIS)
 * @param int $amount - Số tiền
 * @return array - Thông tin phí
 */
function tripayCalculateFee($config, $code, $amount)
{
    return tripayRequest('GET', 'merchant/fee-calculator', $config, [
        'code' => $code,
        'amount' => $amount
    ]);
}

/**
 * Tạo giao dịch Closed Payment
 * 
 * @param array $config - Cấu hình API (api_key, private_key, merchant_code, sandbox)
 * @param array $params - Tham số giao dịch:
 *   - method: Mã kênh thanh toán (VD: BRIVA, QRIS)
 *   - merchant_ref: Mã đơn hàng nội bộ
 *   - amount: Số tiền (integer)
 *   - customer_name: Tên khách hàng
 *   - customer_email: Email khách hàng
 *   - customer_phone: Số điện thoại (optional)
 *   - order_items: Danh sách sản phẩm
 *   - callback_url: URL nhận callback (optional)
 *   - return_url: URL redirect sau thanh toán (optional)
 *   - expired_time: Thời gian hết hạn Unix timestamp (optional)
 * @return array - Kết quả từ API
 */
function tripayCreateTransaction($config, $params)
{
    // Tạo chữ ký
    $signature = tripayGenerateSignature(
        $config['merchant_code'],
        $params['merchant_ref'],
        $params['amount'],
        $config['private_key']
    );

    // Chuẩn bị dữ liệu
    $data = [
        'method'         => $params['method'],
        'merchant_ref'   => $params['merchant_ref'],
        'amount'         => (int)$params['amount'],
        'customer_name'  => $params['customer_name'],
        'customer_email' => $params['customer_email'],
        'order_items'    => $params['order_items'],
        'signature'      => $signature
    ];

    // Thêm các tham số tùy chọn
    if (!empty($params['customer_phone'])) {
        $data['customer_phone'] = $params['customer_phone'];
    }
    if (!empty($params['callback_url'])) {
        $data['callback_url'] = $params['callback_url'];
    }
    if (!empty($params['return_url'])) {
        $data['return_url'] = $params['return_url'];
    }
    if (!empty($params['expired_time'])) {
        $data['expired_time'] = $params['expired_time'];
    }

    return tripayRequest('POST', 'transaction/create', $config, $data);
}

/**
 * Lấy chi tiết giao dịch
 * 
 * @param array $config - Cấu hình API
 * @param string $reference - Mã tham chiếu từ TriPay
 * @return array - Chi tiết giao dịch
 */
function tripayGetTransactionDetail($config, $reference)
{
    return tripayRequest('GET', 'transaction/detail', $config, [
        'reference' => $reference
    ]);
}

/**
 * Kiểm tra trạng thái giao dịch
 * 
 * @param array $config - Cấu hình API
 * @param string $reference - Mã tham chiếu từ TriPay
 * @return array - Trạng thái giao dịch
 */
function tripayCheckTransactionStatus($config, $reference)
{
    return tripayRequest('GET', 'transaction/check-status', $config, [
        'reference' => $reference
    ]);
}

/**
 * Xác minh callback signature từ TriPay
 * 
 * @param string $signature - Signature từ header X-Callback-Signature
 * @param string $jsonBody - Raw JSON body từ callback
 * @param string $privateKey - Private Key
 * @return bool - True nếu hợp lệ
 */
function tripayVerifyCallback($signature, $jsonBody, $privateKey)
{
    $expected = hash_hmac('sha256', $jsonBody, $privateKey);
    return hash_equals($expected, $signature);
}

/**
 * Kiểm tra IP callback từ TriPay
 * 
 * @param string $ip - IP cần kiểm tra
 * @return bool - True nếu IP hợp lệ
 */
function tripayVerifyIP($ip)
{
    $allowedIPs = [
        '95.111.200.230',           // IPv4
        '2a04:3543:1000:2310:ac92:4cff:fe87:63f9' // IPv6
    ];
    return in_array($ip, $allowedIPs);
}

/**
 * Lấy tên hiển thị của phương thức thanh toán
 * 
 * @param string $code - Mã phương thức
 * @return string - Tên hiển thị
 */
function tripayGetMethodName($code)
{
    $methods = [
        // Virtual Account
        'BRIVA'         => 'BRI Virtual Account',
        'BNIVA'         => 'BNI Virtual Account',
        'MANDIRIVA'     => 'Mandiri Virtual Account',
        'BCAVA'         => 'BCA Virtual Account',
        'PERMATAVA'     => 'Permata Virtual Account',
        'CIMBVA'        => 'CIMB Niaga Virtual Account',
        'BSIVA'         => 'BSI Virtual Account',
        'MUAMALATVA'    => 'Muamalat Virtual Account',
        'DANAMONVA'     => 'Danamon Virtual Account',
        'SAMPOERNAVA'   => 'Sahabat Sampoerna Virtual Account',
        'OCBCVA'        => 'OCBC NISP Virtual Account',

        // E-Wallet
        'QRIS'          => 'QRIS (All E-Wallet)',
        'QRISC'         => 'QRIS (Customizable)',
        'QRIS2'         => 'QRIS 2',
        'OVO'           => 'OVO',
        'SHOPEEPAY'     => 'ShopeePay',
        'DANA'          => 'DANA',
        'LINKAJA'       => 'LinkAja',

        // Retail
        'ALFAMART'      => 'Alfamart',
        'INDOMARET'     => 'Indomaret'
    ];

    return $methods[$code] ?? $code;
}

/**
 * Parse trạng thái từ TriPay về trạng thái nội bộ
 * 
 * @param string $status - Trạng thái từ TriPay
 * @return int - 0=pending, 1=success, 2=failed
 */
function tripayParseStatus($status)
{
    switch (strtoupper($status)) {
        case 'PAID':
            return 1;
        case 'EXPIRED':
        case 'FAILED':
        case 'REFUND':
            return 2;
        case 'UNPAID':
        default:
            return 0;
    }
}
