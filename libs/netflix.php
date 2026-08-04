<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

if (!function_exists('netflix_api_key')) {
    function netflix_api_key()
    {
        $key = getenv('CAFFEMMO_NETFLIX_API_KEY');
        if ($key === false || $key === '') {
            $key = isset($_SERVER['CAFFEMMO_NETFLIX_API_KEY']) ? $_SERVER['CAFFEMMO_NETFLIX_API_KEY'] : '';
        }
        if (($key === false || $key === '') && isset($_ENV['CAFFEMMO_NETFLIX_API_KEY'])) {
            $key = $_ENV['CAFFEMMO_NETFLIX_API_KEY'];
        }
        return trim((string) $key);
    }
}

if (!function_exists('netflix_api_is_configured')) {
    function netflix_api_is_configured()
    {
        return netflix_api_key() !== '';
    }
}

if (!function_exists('netflix_safe_login_link')) {
    function netflix_safe_login_link($url)
    {
        $url = trim((string) $url);
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return '';
        }

        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower(rtrim((string) ($parts['host'] ?? ''), '.'));
        $isNetflixHost = $host === 'netflix.com'
            || (strlen($host) > strlen('.netflix.com') && substr($host, -strlen('.netflix.com')) === '.netflix.com');
        if ($scheme !== 'https' || !$isNetflixHost) {
            return '';
        }

        return $url;
    }
}

if (!function_exists('netflix_get_cookie')) {
    function netflix_get_cookie()
    {
        $apiKey = netflix_api_key();
        if ($apiKey === '') {
            return [
                'success' => false,
                'code' => 'not_configured',
                'message' => 'Dịch vụ Netflix chưa được cấu hình.'
            ];
        }
        if (!function_exists('curl_init')) {
            return [
                'success' => false,
                'code' => 'curl_unavailable',
                'message' => 'Máy chủ chưa bật cURL.'
            ];
        }

        $ch = curl_init('https://api.tiembanh4k.com/api/ctv-api/get-cookie');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => [
                'X-API-Key: ' . $apiKey,
                'Accept: application/json',
                'User-Agent: Caffemmo/NetflixService'
            ]
        ]);

        $rawResponse = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($rawResponse === false) {
            error_log('[Netflix API] cURL error: ' . $curlError);
            return [
                'success' => false,
                'code' => 'request_failed',
                'message' => 'Không thể kết nối đến dịch vụ Netflix.'
            ];
        }
        if ($httpCode < 200 || $httpCode >= 300) {
            error_log('[Netflix API] HTTP status: ' . $httpCode);
            return [
                'success' => false,
                'code' => 'provider_http_error',
                'message' => 'Dịch vụ Netflix đang bận, vui lòng thử lại sau.'
            ];
        }

        $data = json_decode($rawResponse, true);
        if (!is_array($data) || !isset($data['success']) || $data['success'] !== true) {
            error_log('[Netflix API] Invalid or unsuccessful response.');
            return [
                'success' => false,
                'code' => 'provider_error',
                'message' => 'Dịch vụ Netflix không trả về link hợp lệ.'
            ];
        }

        $pcLink = netflix_safe_login_link($data['pcLoginLink'] ?? '');
        $mobileLink = netflix_safe_login_link($data['mobileLoginLink'] ?? '');
        if ($pcLink === '' && $mobileLink === '') {
            error_log('[Netflix API] Missing safe login links.');
            return [
                'success' => false,
                'code' => 'missing_links',
                'message' => 'Dịch vụ Netflix chưa trả về link xem.'
            ];
        }

        $expiresAt = isset($data['tokenExpires']) && is_numeric($data['tokenExpires'])
            ? (int) $data['tokenExpires']
            : 0;
        $timeRemaining = isset($data['timeRemaining']) && is_numeric($data['timeRemaining'])
            ? max(0, (int) $data['timeRemaining'])
            : max(0, $expiresAt - time());
        $quota = isset($data['quota']) && is_array($data['quota']) ? $data['quota'] : [];

        return [
            'success' => true,
            'data' => [
                'log_id' => isset($data['logId']) && is_scalar($data['logId']) ? trim((string) $data['logId']) : '',
                'pc_link' => $pcLink,
                'mobile_link' => $mobileLink,
                'expires_at' => $expiresAt,
                'time_remaining' => $timeRemaining,
                'quota' => [
                    'used' => isset($quota['used']) && is_numeric($quota['used']) ? (int) $quota['used'] : null,
                    'max' => isset($quota['max']) && is_numeric($quota['max']) ? (int) $quota['max'] : null,
                    'remaining' => isset($quota['remaining']) && is_numeric($quota['remaining']) ? (int) $quota['remaining'] : null
                ]
            ]
        ];
    }
}

if (!function_exists('netflix_regenerate_token')) {
    function netflix_regenerate_token($logId)
    {
        $apiKey = netflix_api_key();
        $logId = trim((string) $logId);
        if ($apiKey === '') {
            return [
                'success' => false,
                'code' => 'not_configured',
                'message' => 'Dịch vụ Netflix chưa được cấu hình.'
            ];
        }
        if ($logId === '' || !preg_match('/^[A-Za-z0-9_-]{4,100}$/', $logId)) {
            return [
                'success' => false,
                'code' => 'invalid_log_id',
                'message' => 'Mã link Netflix không hợp lệ.'
            ];
        }
        if (!function_exists('curl_init')) {
            return [
                'success' => false,
                'code' => 'curl_unavailable',
                'message' => 'Máy chủ chưa bật cURL.'
            ];
        }

        $ch = curl_init('https://backend-c0r3-7xpq9zn2025.onrender.com/api/ctv-api/regenerate-token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['logId' => $logId]),
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => [
                'X-API-Key: ' . $apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: Caffemmo/NetflixService'
            ]
        ]);

        $rawResponse = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($rawResponse === false) {
            error_log('[Netflix API] cURL error while regenerating token: ' . $curlError);
            return [
                'success' => false,
                'code' => 'request_failed',
                'message' => 'Không thể làm mới link Netflix.'
            ];
        }
        if ($httpCode < 200 || $httpCode >= 300) {
            error_log('[Netflix API] Regenerate HTTP status: ' . $httpCode);
            return [
                'success' => false,
                'code' => 'provider_http_error',
                'message' => 'Dịch vụ Netflix đang bận, vui lòng thử lại sau.'
            ];
        }

        $data = json_decode($rawResponse, true);
        if (!is_array($data) || !isset($data['success']) || $data['success'] !== true) {
            error_log('[Netflix API] Invalid regenerate response.');
            return [
                'success' => false,
                'code' => 'provider_error',
                'message' => 'Không thể làm mới link Netflix.'
            ];
        }

        $mobileLink = netflix_safe_login_link($data['tokenLink'] ?? '');
        $pcLink = netflix_safe_login_link(str_replace('unsupported', 'browse', (string) ($data['tokenLink'] ?? '')));
        if ($pcLink === '' && $mobileLink === '') {
            error_log('[Netflix API] Missing safe regenerated link.');
            return [
                'success' => false,
                'code' => 'missing_links',
                'message' => 'Dịch vụ Netflix chưa trả về link mới.'
            ];
        }

        $expiresAt = isset($data['tokenExpires']) && is_numeric($data['tokenExpires'])
            ? (int) $data['tokenExpires']
            : 0;

        return [
            'success' => true,
            'data' => [
                'log_id' => $logId,
                'pc_link' => $pcLink,
                'mobile_link' => $mobileLink,
                'expires_at' => $expiresAt,
                'time_remaining' => max(0, $expiresAt - time())
            ]
        ];
    }
}
