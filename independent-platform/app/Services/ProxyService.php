<?php
declare(strict_types=1);

final class ProxyService
{
    public static function createPending(int $userId, array $configuration): array
    {
        $required = ['proxy_type', 'country_code', 'rent_period_days', 'quantity'];
        foreach ($required as $key) {
            if (!isset($configuration[$key]) || trim((string) $configuration[$key]) === '') {
                return ['ok' => false, 'status' => 'invalid'];
            }
        }
        $connection = db();
        if (!$connection) {
            return ['ok' => false, 'status' => 'database_unavailable'];
        }
        try {
            $statement = $connection->prepare('INSERT INTO proxy_orders (user_id, proxy_type, country_code, quantity, rent_period_days, auth_mode, auto_renew, status) VALUES (?, ?, ?, ?, ?, ?, ?, "pending")');
            $statement->execute([
                $userId,
                trim((string) $configuration['proxy_type']),
                strtoupper(trim((string) $configuration['country_code'])),
                max(1, (int) $configuration['quantity']),
                max(1, (int) $configuration['rent_period_days']),
                in_array($configuration['auth_mode'] ?? '', ['login_password', 'ip_whitelist'], true) ? $configuration['auth_mode'] : 'login_password',
                !empty($configuration['auto_renew']) ? 1 : 0,
            ]);
            $id = (int) $connection->lastInsertId();
            AuditService::record($userId, 'proxy.intent_created', 'proxy_order', (string) $id, ['proxy_type' => $configuration['proxy_type']]);
            return ['ok' => true, 'status' => 'pending', 'proxy_order_id' => $id];
        } catch (Throwable $exception) {
            Logger::error('Proxy intent creation failed', ['user_id' => $userId, 'message' => $exception->getMessage()]);
            return ['ok' => false, 'status' => 'failed'];
        }
    }

    public static function purchase(int $userId, array $configuration): array
    {
        $proxyType = trim((string) ($configuration['proxy_type'] ?? ''));
        $countryCode = strtoupper(trim((string) ($configuration['country_code'] ?? '')));
        $days = max(1, (int) ($configuration['rent_period_days'] ?? 0));
        $quantity = max(1, (int) ($configuration['quantity'] ?? 0));
        if ($proxyType === '' || $countryCode === '') {
            return ['ok' => false, 'status' => 'invalid_configuration'];
        }
        $unitPrice = max(0, (float) Settings::get('proxy_daily_price', env_value('YOUPROXY_DEFAULT_DAILY_PRICE', '33800')));
        $total = $unitPrice * $quantity * $days;
        if ($total <= 0) {
            return ['ok' => false, 'status' => 'quote_unavailable'];
        }
        $provider = new YouProxyProvider();
        if (!$provider->isConfigured()) {
            return ['ok' => false, 'status' => 'provider_not_configured'];
        }
        $debit = WalletService::debit($userId, $total, 'Mua proxy premium', ['configuration' => [
            'proxy_type' => $proxyType,
            'country_code' => $countryCode,
            'days' => $days,
            'quantity' => $quantity,
        ]]);
        if (!$debit['ok']) {
            return $debit;
        }
        $providerResult = $provider->createOrder([
            'proxyType' => $proxyType,
            'country' => $countryCode,
            'quantity' => $quantity,
            'rentPeriodDays' => $days,
            'authType' => (string) ($configuration['auth_mode'] ?? 'login_password'),
        ]);
        if (!$providerResult['ok']) {
            WalletService::creditOnce($userId, $total, 'internal_refund', 'proxy-failed-' . bin2hex(random_bytes(8)), 'Hoàn tiền đơn proxy lỗi', ['provider' => $providerResult]);
            Logger::error('Proxy purchase provider failed', ['user_id' => $userId, 'status' => $providerResult['status'] ?? 0]);
            return ['ok' => false, 'status' => 'provider_failed'];
        }
        $data = is_array($providerResult['data'] ?? null) ? $providerResult['data'] : [];
        $providerOrderId = (string) ($data['orderId'] ?? '');
        $providerOrderNumber = (string) ($data['orderNumber'] ?? '');
        $connection = db();
        if (!$connection) {
            return ['ok' => false, 'status' => 'database_unavailable'];
        }
        try {
            $statement = $connection->prepare('INSERT INTO proxy_orders (user_id, provider_order_id, provider_order_number, proxy_type, country_code, quantity, rent_period_days, auth_mode, auto_renew, provider_payload, status, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "active", DATE_ADD(UTC_TIMESTAMP(), INTERVAL ? DAY))');
            $statement->execute([$userId, $providerOrderId !== '' ? $providerOrderId : null, $providerOrderNumber !== '' ? $providerOrderNumber : null, $proxyType, $countryCode, $quantity, $days, $configuration['auth_mode'] ?? 'login_password', !empty($configuration['auto_renew']) ? 1 : 0, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $days]);
            $id = (int) $connection->lastInsertId();
            AuditService::record($userId, 'proxy.purchase_completed', 'proxy_order', (string) $id, ['provider_order_id' => $providerOrderId]);
            return ['ok' => true, 'status' => 'active', 'proxy_order_id' => $id, 'provider_order_id' => $providerOrderId, 'provider_order_number' => $providerOrderNumber, 'provider_payload' => $data, 'balance_after' => $debit['balance_after']];
        } catch (Throwable $exception) {
            Logger::error('Proxy order persistence failed', ['user_id' => $userId, 'message' => $exception->getMessage()]);
            return ['ok' => false, 'status' => 'persistence_failed'];
        }
    }
}
