<?php
declare(strict_types=1);

final class YouProxyProvider extends HttpJsonProvider
{
    public function isConfigured(): bool
    {
        return trim((string) env_value('YOUPROXY_API_KEY', '')) !== '';
    }

    public function options(): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'error' => 'Dịch vụ đang được cấu hình.'];
        }
        return $this->request('GET', (string) env_value('YOUPROXY_API_BASE_URL', 'https://youproxy.io'), (string) env_value('YOUPROXY_PROXY_TYPES_PATH', 'client/api/v1/proxyType'), [
            'X-API-Key: ' . env_value('YOUPROXY_API_KEY', ''),
        ], null, (int) env_value('YOUPROXY_TIMEOUT', '20'));
    }

    public function createOrder(array $configuration): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'error' => 'Dịch vụ đang được cấu hình.'];
        }
        return $this->request(
            'POST',
            (string) env_value('YOUPROXY_API_BASE_URL', 'https://youproxy.io'),
            (string) env_value('YOUPROXY_ORDER_PATH', 'client/api/v1/order'),
            ['X-API-Key: ' . env_value('YOUPROXY_API_KEY', '')],
            $configuration,
            (int) env_value('YOUPROXY_TIMEOUT', '20')
        );
    }

    public function ipAddresses(string $proxyType): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'error' => 'Dịch vụ đang được cấu hình.'];
        }
        $path = (string) env_value('YOUPROXY_IP_ADDRESS_PATH', 'client/api/v1/ipAddress');
        $separator = str_contains($path, '?') ? '&' : '?';
        return $this->request(
            'GET',
            (string) env_value('YOUPROXY_API_BASE_URL', 'https://youproxy.io'),
            $path . $separator . http_build_query(['proxyType' => $proxyType]),
            ['X-API-Key: ' . env_value('YOUPROXY_API_KEY', '')],
            null,
            (int) env_value('YOUPROXY_TIMEOUT', '20')
        );
    }

    public function renew(string $providerOrderId, int $days): array
    {
        if (!$this->isConfigured() || trim($providerOrderId) === '' || $days < 1) {
            return ['ok' => false, 'error' => 'Cấu hình gia hạn không hợp lệ.'];
        }
        return $this->request(
            'POST',
            (string) env_value('YOUPROXY_API_BASE_URL', 'https://youproxy.io'),
            (string) env_value('YOUPROXY_RENEW_PATH', 'client/api/v1/renew'),
            ['X-API-Key: ' . env_value('YOUPROXY_API_KEY', '')],
            ['orderId' => $providerOrderId, 'rentPeriodDays' => $days],
            (int) env_value('YOUPROXY_TIMEOUT', '20')
        );
    }
}
