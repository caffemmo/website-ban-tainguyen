<?php

/**
 * LemPay Payment Gateway Library
 * API Documentation: https://api.lemzf.com/doc.html
 * 
 * LemPay hỗ trợ 3 phương thức thanh toán:
 * - alipay: Alipay
 * - wxpay: WeChat Pay  
 * - usdt: USDT Cryptocurrency
 */

if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

/**
 * Tạo chữ ký MD5 theo chuẩn LemPay
 * 
 * @param array $params - Các tham số cần ký
 * @param string $merchantKey - Merchant Key từ LemPay
 * @return string - Chữ ký MD5
 */
function lempayGenerateSign($params, $merchantKey)
{
    // Loại bỏ sign và sign_type
    unset($params['sign'], $params['sign_type']);

    // Loại bỏ các giá trị rỗng
    $params = array_filter($params, function ($value) {
        return $value !== '' && $value !== null;
    });

    // Sắp xếp theo key A-Z
    ksort($params);

    // Nối chuỗi key=value&key=value...
    $signStr = '';
    foreach ($params as $key => $value) {
        $signStr .= "{$key}={$value}&";
    }
    $signStr = rtrim($signStr, '&');

    // Thêm Merchant Key vào cuối (không có dấu &)
    $signStr .= $merchantKey;

    // Trả về MD5
    return md5($signStr);
}

/**
 * Tạo hóa đơn thanh toán qua API Interface (mapi.php)
 * Trả về payurl hoặc mã QR để hiển thị
 * 
 * @param array $config - Cấu hình API (pid, key, api_url)
 * @param array $params - Tham số thanh toán
 * @return array - Kết quả từ API
 */
function lempayCreatePayment($config, $params)
{
    global $CMSNT;

    $url = rtrim($config['api_url'], '/') . '/mapi.php';

    // Các tham số bắt buộc
    $postData = [
        'pid'          => $config['pid'],
        'type'         => $params['type'],           // alipay, wxpay, usdt
        'out_trade_no' => $params['out_trade_no'],   // Mã đơn hàng nội bộ
        'notify_url'   => $params['notify_url'],     // URL callback
        'return_url'   => $params['return_url'],     // URL redirect sau thanh toán
        'name'         => $params['name'],           // Tên sản phẩm
        'money'        => $params['money'],          // Số tiền (CNY)
        'sitename'     => $params['sitename'] ?? $_SERVER['HTTP_HOST'],
        'clientip'     => $params['clientip'] ?? $_SERVER['REMOTE_ADDR'],
        'device'       => $params['device'] ?? 'pc', // pc hoặc mobile
    ];

    // Tạo chữ ký
    $postData['sign'] = lempayGenerateSign($postData, $config['key']);
    $postData['sign_type'] = 'MD5';

    // Gửi request
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => http_build_query($postData),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded'
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return [
            'code' => -1,
            'msg' => 'CURL Error: ' . $error
        ];
    }

    $result = json_decode($response, true);

    if ($result === null) {
        return [
            'code' => -1,
            'msg' => 'Invalid JSON response: ' . $response
        ];
    }

    return $result;
}

/**
 * Tạo URL thanh toán chuyển hướng (submit.php)
 * User sẽ được redirect đến trang thanh toán LemPay
 * 
 * @param array $config - Cấu hình API (pid, key, api_url)
 * @param array $params - Tham số thanh toán
 * @return string - URL chuyển hướng
 */
function lempayGetRedirectUrl($config, $params)
{
    $url = rtrim($config['api_url'], '/') . '/submit.php';

    $postData = [
        'pid'          => $config['pid'],
        'type'         => $params['type'],
        'out_trade_no' => $params['out_trade_no'],
        'notify_url'   => $params['notify_url'],
        'return_url'   => $params['return_url'],
        'name'         => $params['name'],
        'money'        => $params['money'],
        'sitename'     => $params['sitename'] ?? $_SERVER['HTTP_HOST'],
    ];

    // Tạo chữ ký
    $postData['sign'] = lempayGenerateSign($postData, $config['key']);
    $postData['sign_type'] = 'MD5';

    return $url . '?' . http_build_query($postData);
}

/**
 * Xác thực callback từ LemPay
 * 
 * @param array $params - Tham số callback từ $_GET
 * @param string $merchantKey - Merchant Key
 * @return bool - True nếu chữ ký hợp lệ
 */
function lempayVerifyCallback($params, $merchantKey)
{
    if (!isset($params['sign'])) {
        return false;
    }

    $receivedSign = $params['sign'];

    // Tạo lại chữ ký từ các tham số
    $expectedSign = lempayGenerateSign($params, $merchantKey);

    return $receivedSign === $expectedSign;
}

/**
 * Lấy tên hiển thị của phương thức thanh toán
 * 
 * @param string $type - Loại thanh toán (alipay, wxpay, usdt)
 * @return string - Tên hiển thị
 */
function lempayGetPaymentTypeName($type)
{
    $types = [
        'alipay' => 'Alipay',
        'wxpay'  => 'WeChat Pay',
        'usdt'   => 'USDT'
    ];
    return $types[$type] ?? $type;
}

/**
 * Detect thiết bị (PC hoặc Mobile)
 * 
 * @return string - 'mobile' hoặc 'pc'
 */
function lempayDetectDevice()
{
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $mobileKeywords = ['Mobile', 'Android', 'iPhone', 'iPad', 'iPod', 'Windows Phone'];

    foreach ($mobileKeywords as $keyword) {
        if (stripos($userAgent, $keyword) !== false) {
            return 'mobile';
        }
    }

    return 'pc';
}
