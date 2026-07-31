<?php
declare(strict_types=1);

abstract class HttpJsonProvider implements ProviderInterface
{
    protected function request(string $method, string $baseUrl, string $path, array $headers = [], ?array $payload = null, int $timeout = 20): array
    {
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'status' => 0, 'error' => 'Server chưa bật cURL.'];
        }
        $url = rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
        $handle = curl_init($url);
        if ($handle === false) {
            return ['ok' => false, 'status' => 0, 'error' => 'Không thể khởi tạo kết nối.'];
        }
        $requestHeaders = array_merge(['Accept: application/json'], $headers);
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $requestHeaders,
            CURLOPT_CONNECTTIMEOUT => min($timeout, 10),
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];
        if ($payload !== null) {
            $options[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $requestHeaders[] = 'Content-Type: application/json';
            $options[CURLOPT_HTTPHEADER] = $requestHeaders;
        }
        curl_setopt_array($handle, $options);
        $body = curl_exec($handle);
        $error = curl_error($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);
        if ($body === false) {
            return ['ok' => false, 'status' => $status, 'error' => 'Nhà cung cấp không phản hồi.'];
        }
        $decoded = json_decode((string) $body, true);
        if (!is_array($decoded)) {
            return ['ok' => false, 'status' => $status, 'error' => 'Dữ liệu nhà cung cấp không hợp lệ.'];
        }
        $providerOk = array_key_exists('success', $decoded) ? (bool) $decoded['success'] : true;
        if (array_key_exists('status', $decoded) && is_bool($decoded['status'])) {
            $providerOk = $providerOk && $decoded['status'];
        }
        $providerError = (string) ($decoded['message'] ?? $decoded['error'] ?? $error);
        return ['ok' => $status >= 200 && $status < 300 && $providerOk, 'status' => $status, 'data' => $decoded, 'error' => $providerOk ? $error : $providerError];
    }
}
