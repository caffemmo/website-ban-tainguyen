<?php
declare(strict_types=1);

final class SocialProvider extends HttpJsonProvider
{
    public function isConfigured(): bool
    {
        return trim((string) env_value('SOCIAL_PROVIDER_BASE_URL', '')) !== ''
            && trim((string) env_value('SOCIAL_PROVIDER_API_KEY', '')) !== '';
    }

    public function submit(string $service, array $payload): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'error' => 'Dịch vụ đang được cấu hình.'];
        }
        $endpoint = match ($service) {
            'get_link_facebook' => '/api/getlink',
            'up_facebook' => '/api/upfb',
            'up_instagram' => '/api/upig',
            default => '',
        };
        if ($endpoint === '') {
            return ['ok' => false, 'error' => 'Dịch vụ không hợp lệ.'];
        }
        return $this->request('POST', (string) env_value('SOCIAL_PROVIDER_BASE_URL'), $endpoint, [
            'X-API-Key: ' . env_value('SOCIAL_PROVIDER_API_KEY', ''),
        ], $payload, (int) env_value('SOCIAL_PROVIDER_TIMEOUT', '20'));
    }
}
