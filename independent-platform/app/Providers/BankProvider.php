<?php
declare(strict_types=1);

final class BankProvider extends HttpJsonProvider
{
    public function isConfigured(): bool
    {
        return trim((string) env_value('BANK_PROVIDER_BASE_URL', '')) !== ''
            && trim((string) env_value('BANK_PROVIDER_TOKEN', '')) !== '';
    }

    public function transactions(): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'error' => 'Kênh ngân hàng chưa được cấu hình.'];
        }
        $header = (string) env_value('BANK_PROVIDER_AUTH_HEADER', 'Authorization');
        $scheme = trim((string) env_value('BANK_PROVIDER_AUTH_SCHEME', 'Bearer'));
        $token = trim((string) env_value('BANK_PROVIDER_TOKEN', ''));
        $authorization = $scheme === '' ? $token : $scheme . ' ' . $token;
        return $this->request(
            'GET',
            (string) env_value('BANK_PROVIDER_BASE_URL'),
            (string) env_value('BANK_PROVIDER_TRANSACTIONS_PATH', 'transactions'),
            [$header . ': ' . $authorization],
            null,
            (int) env_value('BANK_PROVIDER_TIMEOUT', '20')
        );
    }
}
