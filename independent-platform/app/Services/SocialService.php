<?php
declare(strict_types=1);

final class SocialService
{
    public static function createRequest(int $userId, string $service, array $safePayload): array
    {
        $allowed = ['get_link_facebook', 'up_facebook', 'up_instagram'];
        if ($userId < 1 || !in_array($service, $allowed, true)) {
            return ['ok' => false, 'status' => 'invalid'];
        }
        $connection = db();
        if (!$connection) {
            return ['ok' => false, 'status' => 'database_unavailable'];
        }
        try {
            $statement = $connection->prepare('INSERT INTO social_requests (user_id, service, status, request_payload) VALUES (?, ?, "pending", ?)');
            $statement->execute([$userId, $service, json_encode($safePayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
            $requestId = (int) $connection->lastInsertId();
            AuditService::record($userId, 'social.request_created', 'social_request', (string) $requestId, ['service' => $service]);
            return ['ok' => true, 'status' => 'pending', 'request_id' => $requestId];
        } catch (Throwable $exception) {
            Logger::error('Social request creation failed', ['user_id' => $userId, 'service' => $service, 'message' => $exception->getMessage()]);
            return ['ok' => false, 'status' => 'failed'];
        }
    }

    public static function processRequest(int $userId, string $service, array $safePayload): array
    {
        $created = self::createRequest($userId, $service, $safePayload);
        if (!$created['ok']) {
            return $created;
        }
        $provider = new SocialProvider();
        if (!$provider->isConfigured()) {
            return ['ok' => false, 'status' => 'provider_not_configured', 'request_id' => $created['request_id']];
        }
        $connection = db();
        try {
            $processing = $connection?->prepare("UPDATE social_requests SET status = 'processing' WHERE id = ?");
            $processing?->execute([$created['request_id']]);
            $response = $provider->submit($service, $safePayload);
            $status = $response['ok'] ? 'completed' : 'failed';
            $payload = json_encode($response['data'] ?? ['error' => $response['error'] ?? 'Provider failed'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $update = $connection?->prepare('UPDATE social_requests SET status = ?, result_payload = ? WHERE id = ?');
            $update?->execute([$status, $payload, $created['request_id']]);
            AuditService::record($userId, 'social.request_' . $status, 'social_request', (string) $created['request_id'], ['service' => $service]);
            return ['ok' => $response['ok'], 'status' => $status, 'request_id' => $created['request_id'], 'data' => $response['data'] ?? null];
        } catch (Throwable $exception) {
            Logger::error('Social provider request failed', ['user_id' => $userId, 'service' => $service, 'message' => $exception->getMessage()]);
            $failed = $connection?->prepare("UPDATE social_requests SET status = 'failed' WHERE id = ?");
            $failed?->execute([$created['request_id']]);
            return ['ok' => false, 'status' => 'failed', 'request_id' => $created['request_id']];
        }
    }
}
