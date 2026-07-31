<?php
declare(strict_types=1);

final class AuditService
{
    public static function record(?int $userId, string $action, ?string $targetType = null, ?string $targetId = null, array $metadata = []): void
    {
        $connection = db();
        if (!$connection) {
            return;
        }
        try {
            $statement = $connection->prepare('INSERT INTO audit_logs (user_id, action, target_type, target_id, ip_address, metadata) VALUES (?, ?, ?, ?, ?, ?)');
            $statement->execute([
                $userId,
                $action,
                $targetType,
                $targetId,
                $_SERVER['REMOTE_ADDR'] ?? null,
                json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
        } catch (Throwable $exception) {
            Logger::error('Audit log failed', ['action' => $action, 'message' => $exception->getMessage()]);
        }
    }
}
