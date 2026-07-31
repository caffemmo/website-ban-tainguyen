<?php
declare(strict_types=1);

final class CatalogService
{
    public static function products(): array
    {
        $connection = db();
        if (!$connection) {
            return [];
        }

        try {
            $statement = $connection->query(
                "SELECT p.id, p.name, p.slug, p.description, p.product_type, p.price, p.stock_count, p.provider_code, c.name AS category_name
                 FROM products p
                 LEFT JOIN categories c ON c.id = p.category_id
                 WHERE p.status = 'active'
                 ORDER BY COALESCE(c.sort_order, 999), p.id DESC"
            );
            return $statement->fetchAll() ?: [];
        } catch (Throwable $exception) {
            Logger::error('Catalog query failed', ['message' => $exception->getMessage()]);
            return [];
        }
    }

    public static function dashboard(int $userId): array
    {
        $empty = [
            'orders' => 0,
            'processing_orders' => 0,
            'active_proxies' => 0,
            'social_requests' => 0,
            'recent_activity' => [],
        ];
        $connection = db();
        if (!$connection || $userId < 1) {
            return $empty;
        }

        try {
            $count = static function (string $sql) use ($connection, $userId): int {
                $statement = $connection->prepare($sql);
                $statement->execute([$userId]);
                return (int) $statement->fetchColumn();
            };
            $empty['orders'] = $count('SELECT COUNT(*) FROM orders WHERE user_id = ?');
            $empty['processing_orders'] = $count("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status IN ('pending','processing')");
            $empty['active_proxies'] = $count("SELECT COUNT(*) FROM proxy_orders WHERE user_id = ? AND status = 'active'");
            $empty['social_requests'] = $count("SELECT COUNT(*) FROM social_requests WHERE user_id = ? AND status IN ('pending','processing')");
            $activity = $connection->prepare(
                "SELECT direction AS kind, amount, description, created_at
                 FROM wallet_transactions WHERE user_id = ? ORDER BY id DESC LIMIT 5"
            );
            $activity->execute([$userId]);
            $empty['recent_activity'] = $activity->fetchAll() ?: [];
            return $empty;
        } catch (Throwable $exception) {
            Logger::error('Dashboard query failed', ['user_id' => $userId, 'message' => $exception->getMessage()]);
            return $empty;
        }
    }

    public static function orders(int $userId): array
    {
        $connection = db();
        if (!$connection || $userId < 1) {
            return [];
        }
        try {
            $statement = $connection->prepare(
                "SELECT id, order_code, subtotal, total, status, provider_order_id, created_at
                 FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 50"
            );
            $statement->execute([$userId]);
            return $statement->fetchAll() ?: [];
        } catch (Throwable $exception) {
            Logger::error('Order list query failed', ['user_id' => $userId, 'message' => $exception->getMessage()]);
            return [];
        }
    }

    public static function proxies(int $userId): array
    {
        $connection = db();
        if (!$connection || $userId < 1) {
            return [];
        }
        try {
            $statement = $connection->prepare(
                "SELECT id, provider_order_id, provider_order_number, proxy_type, country_code, quantity, rent_period_days, auth_mode,
                        auto_renew, provider_payload, status, expires_at, created_at
                 FROM proxy_orders WHERE user_id = ? ORDER BY id DESC LIMIT 100"
            );
            $statement->execute([$userId]);
            return $statement->fetchAll() ?: [];
        } catch (Throwable $exception) {
            Logger::error('Proxy list query failed', ['user_id' => $userId, 'message' => $exception->getMessage()]);
            return [];
        }
    }

    public static function wallet(int $userId): array
    {
        $connection = db();
        if (!$connection || $userId < 1) {
            return [];
        }
        try {
            $statement = $connection->prepare(
                "SELECT direction, amount, balance_before, balance_after, provider, description, created_at
                 FROM wallet_transactions WHERE user_id = ? ORDER BY id DESC LIMIT 50"
            );
            $statement->execute([$userId]);
            return $statement->fetchAll() ?: [];
        } catch (Throwable $exception) {
            Logger::error('Wallet list query failed', ['user_id' => $userId, 'message' => $exception->getMessage()]);
            return [];
        }
    }

    public static function adminStats(): array
    {
        $empty = ['users' => 0, 'orders_today' => 0, 'deposits_today' => 0, 'proxy_active' => 0, 'recent_logs' => []];
        $connection = db();
        if (!$connection) {
            return $empty;
        }
        try {
            $empty['users'] = (int) $connection->query('SELECT COUNT(*) FROM users')->fetchColumn();
            $empty['orders_today'] = (int) $connection->query('SELECT COUNT(*) FROM orders WHERE created_at >= UTC_DATE()')->fetchColumn();
            $empty['deposits_today'] = (int) $connection->query("SELECT COALESCE(SUM(amount), 0) FROM wallet_deposits WHERE status = 'paid' AND paid_at >= UTC_DATE()")->fetchColumn();
            $empty['proxy_active'] = (int) $connection->query("SELECT COUNT(*) FROM proxy_orders WHERE status = 'active'")->fetchColumn();
            $empty['recent_logs'] = $connection->query(
                'SELECT action, target_type, target_id, created_at FROM audit_logs ORDER BY id DESC LIMIT 12'
            )->fetchAll() ?: [];
            return $empty;
        } catch (Throwable $exception) {
            Logger::error('Admin stats query failed', ['message' => $exception->getMessage()]);
            return $empty;
        }
    }
}
