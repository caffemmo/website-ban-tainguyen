<?php

/**
 * ZiniPay Payment Gateway Library
 * API Documentation: https://zinipay.com/docs
 *
 * ZiniPay (Bangladesh) hỗ trợ thanh toán qua bKash, Nagad...
 * Xác thực bằng header `zini-api-key`, không dùng chữ ký.
 * Webhook chỉ gửi invoice_id + status nên BẮT BUỘC phải gọi
 * lại endpoint verify từ backend trước khi cộng tiền.
 */

if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

/**
 * Gửi request tới ZiniPay API (JSON + header zini-api-key)
 *
 * @param array  $config   Cấu hình (api_key, api_url)
 * @param string $endpoint Đường dẫn endpoint (vd: /v1/payment/create)
 * @param array  $payload  Dữ liệu gửi đi
 * @return array Kết quả đã decode (kèm code = -1 nếu lỗi CURL)
 */
function zinipayRequest($config, $endpoint, $payload)
{
    $url = rtrim($config['api_url'], '/') . $endpoint;

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => '',
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
            'zini-api-key: ' . $config['api_key']
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);

    $response = curl_exec($ch);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['code' => -1, 'msg' => 'CURL Error: ' . $error];
    }

    $result = json_decode($response, true);

    if ($result === null) {
        return ['code' => -1, 'msg' => 'Invalid JSON response: ' . $response];
    }

    return $result;
}

/**
 * Tạo hóa đơn thanh toán (hosted invoice)
 *
 * @param array $config Cấu hình API (api_key, api_url)
 * @param array $params Tham số thanh toán
 * @return array Kết quả từ API (status, message, payment_url)
 */
function zinipayCreatePayment($config, $params)
{
    $payload = [
        'cus_name'     => $params['cus_name'] ?? 'Customer',
        'cus_email'    => $params['cus_email'] ?? 'noemail@example.com',
        'amount'       => $params['amount'],
        'redirect_url' => $params['redirect_url'],
        'cancel_url'   => $params['cancel_url'] ?? $params['redirect_url'],
        'webhook_url'  => $params['webhook_url'],
        'metadata'     => $params['metadata'] ?? []
    ];

    return zinipayRequest($config, '/v1/payment/create', $payload);
}

/**
 * Xác minh trạng thái thanh toán trực tiếp từ backend.
 * ZiniPay khuyến nghị LUÔN gọi hàm này trước khi cộng tiền.
 *
 * @param array  $config    Cấu hình API (api_key, api_url)
 * @param string $invoiceId Mã hóa đơn ZiniPay
 * @return array Kết quả (status: PENDING|COMPLETED|FAILED, amount, transaction_id...)
 */
function zinipayVerifyPayment($config, $invoiceId)
{
    return zinipayRequest($config, '/v1/payment/verify', [
        'invoice_id' => $invoiceId
    ]);
}

/**
 * Trích xuất invoice_id từ payment_url trả về.
 * payment_url có dạng: https://secure.zinipay.com/payment/INVOICE_ID
 *
 * @param string $paymentUrl
 * @return string Mã hóa đơn (rỗng nếu không lấy được)
 */
function zinipayExtractInvoiceId($paymentUrl)
{
    if (empty($paymentUrl)) {
        return '';
    }
    $path = parse_url($paymentUrl, PHP_URL_PATH);
    if (!$path) {
        return '';
    }
    return basename(rtrim($path, '/'));
}
