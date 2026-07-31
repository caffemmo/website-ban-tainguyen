<?php
declare(strict_types=1);

final class WalletService
{
    public static function createPendingDeposit(int $userId, float $amount, string $provider, string $description = ''): array
    {
        if ($userId < 1 || $amount <= 0 || trim($provider) === '') {
            return ['ok' => false, 'status' => 'invalid'];
        }
        $connection = db();
        if (!$connection) {
            return ['ok' => false, 'status' => 'database_unavailable'];
        }
        try {
            $depositCode = 'CFM' . strtoupper(bin2hex(random_bytes(6)));
            $statement = $connection->prepare('INSERT INTO wallet_deposits (user_id, provider, deposit_code, amount, status, description) VALUES (?, ?, ?, ?, "pending", ?)');
            $statement->execute([$userId, $provider, $depositCode, $amount, $description !== '' ? $description : 'Nạp tiền ' . $depositCode]);
            $id = (int) $connection->lastInsertId();
            AuditService::record($userId, 'wallet.deposit_intent_created', 'wallet_deposit', (string) $id, ['provider' => $provider, 'amount' => $amount]);
            return ['ok' => true, 'status' => 'pending', 'deposit_id' => $id, 'deposit_code' => $depositCode];
        } catch (Throwable $exception) {
            Logger::error('Deposit intent creation failed', ['user_id' => $userId, 'message' => $exception->getMessage()]);
            return ['ok' => false, 'status' => 'failed'];
        }
    }

    public static function debit(int $userId, float $amount, string $description, array $metadata = []): array
    {
        if ($userId < 1 || $amount <= 0) {
            return ['ok' => false, 'status' => 'invalid'];
        }
        $connection = db();
        if (!$connection) {
            return ['ok' => false, 'status' => 'database_unavailable'];
        }
        try {
            $connection->beginTransaction();
            $statement = $connection->prepare('SELECT balance FROM users WHERE id = ? AND status = "active" LIMIT 1 FOR UPDATE');
            $statement->execute([$userId]);
            $user = $statement->fetch();
            if (!$user || (float) $user['balance'] < $amount) {
                $connection->rollBack();
                return ['ok' => false, 'status' => 'insufficient_balance'];
            }
            $before = (float) $user['balance'];
            $after = $before - $amount;
            $update = $connection->prepare('UPDATE users SET balance = ? WHERE id = ?');
            $update->execute([$after, $userId]);
            $transaction = $connection->prepare('INSERT INTO wallet_transactions (user_id, direction, amount, balance_before, balance_after, description, metadata) VALUES (?, "debit", ?, ?, ?, ?, ?)');
            $transaction->execute([$userId, $amount, $before, $after, $description, json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
            $transactionId = (int) $connection->lastInsertId();
            $connection->commit();
            AuditService::record($userId, 'wallet.debit', 'wallet_transaction', (string) $transactionId, ['amount' => $amount]);
            return ['ok' => true, 'status' => 'debited', 'balance_after' => $after, 'transaction_id' => $transactionId];
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            Logger::error('Wallet debit failed', ['user_id' => $userId, 'message' => $exception->getMessage()]);
            return ['ok' => false, 'status' => 'failed'];
        }
    }

    public static function creditOnce(int $userId, float $amount, string $provider, string $externalId, string $description, array $metadata = []): array
    {
        if ($userId < 1 || $amount <= 0 || trim($externalId) === '') {
            return ['ok' => false, 'status' => 'invalid'];
        }
        $connection = db();
        if (!$connection) {
            return ['ok' => false, 'status' => 'database_unavailable'];
        }
        try {
            $connection->beginTransaction();
            $existing = $connection->prepare('SELECT id, status FROM wallet_deposits WHERE provider = ? AND external_id = ? LIMIT 1 FOR UPDATE');
            $existing->execute([$provider, $externalId]);
            $deposit = $existing->fetch();
            if ($deposit) {
                $connection->rollBack();
                return ['ok' => $deposit['status'] === 'paid', 'status' => 'duplicate', 'deposit_id' => (int) $deposit['id']];
            }

            $userStatement = $connection->prepare('SELECT balance FROM users WHERE id = ? AND status = "active" LIMIT 1 FOR UPDATE');
            $userStatement->execute([$userId]);
            $user = $userStatement->fetch();
            if (!$user) {
                $connection->rollBack();
                return ['ok' => false, 'status' => 'user_not_found'];
            }
            $before = (float) $user['balance'];
            $after = $before + $amount;
            $depositCode = strtoupper((string) ($metadata['deposit_code'] ?? ''));
            $pending = null;
            if ($depositCode !== '') {
                $pendingStatement = $connection->prepare("SELECT id FROM wallet_deposits WHERE user_id = ? AND deposit_code = ? AND status = 'pending' LIMIT 1 FOR UPDATE");
                $pendingStatement->execute([$userId, $depositCode]);
                $pending = $pendingStatement->fetch();
            }
            if ($pending) {
                $depositUpdate = $connection->prepare('UPDATE wallet_deposits SET provider = ?, external_id = ?, amount = ?, status = "paid", description = ?, provider_payload = ?, paid_at = NOW() WHERE id = ?');
                $depositUpdate->execute([$provider, $externalId, $amount, $description, json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), (int) $pending['id']]);
                $depositId = (int) $pending['id'];
            } else {
                $depositStatement = $connection->prepare('INSERT INTO wallet_deposits (user_id, provider, external_id, amount, status, description, provider_payload, paid_at) VALUES (?, ?, ?, ?, "paid", ?, ?, NOW())');
                $depositStatement->execute([$userId, $provider, $externalId, $amount, $description, json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
                $depositId = (int) $connection->lastInsertId();
            }
            $transaction = $connection->prepare('INSERT INTO wallet_transactions (user_id, direction, amount, balance_before, balance_after, provider, external_id, description, metadata) VALUES (?, "credit", ?, ?, ?, ?, ?, ?, ?)');
            $transaction->execute([$userId, $amount, $before, $after, $provider, $externalId, $description, json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
            $update = $connection->prepare('UPDATE users SET balance = ? WHERE id = ?');
            $update->execute([$after, $userId]);
            $connection->commit();
            AuditService::record($userId, 'wallet.credit', 'wallet_deposit', (string) $depositId, ['provider' => $provider, 'amount' => $amount]);
            return ['ok' => true, 'status' => 'paid', 'deposit_id' => $depositId, 'balance_after' => $after];
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            Logger::error('Wallet credit failed', ['provider' => $provider, 'external_id' => $externalId, 'message' => $exception->getMessage()]);
            return ['ok' => false, 'status' => 'failed'];
        }
    }
}
