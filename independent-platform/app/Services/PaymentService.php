<?php
declare(strict_types=1);

final class PaymentService
{
    public static function syncBankTransactions(): array
    {
        $provider = new BankProvider();
        if (!$provider->isConfigured()) {
            return ['ok' => false, 'status' => 'not_configured', 'credited' => 0];
        }
        $response = $provider->transactions();
        if (!$response['ok']) {
            Logger::error('Bank provider request failed', ['status' => $response['status'] ?? 0, 'error' => $response['error'] ?? 'unknown']);
            return ['ok' => false, 'status' => 'provider_failed', 'credited' => 0];
        }
        $payload = $response['data'] ?? [];
        $transactions = $payload['transactions'] ?? $payload['data'] ?? $payload;
        if (!is_array($transactions)) {
            return ['ok' => false, 'status' => 'invalid_provider_payload', 'credited' => 0];
        }

        $credited = 0;
        foreach ($transactions as $transaction) {
            if (!is_array($transaction)) {
                continue;
            }
            $type = strtoupper((string) ($transaction['type'] ?? $transaction['direction'] ?? 'IN'));
            $amount = (float) ($transaction['amount'] ?? 0);
            $externalId = (string) ($transaction['transactionID'] ?? $transaction['transaction_id'] ?? $transaction['id'] ?? '');
            $description = trim((string) ($transaction['description'] ?? $transaction['content'] ?? ''));
            if (!in_array($type, ['IN', 'CREDIT', 'TRANSFER_IN'], true) || $amount <= 0 || $externalId === '') {
                continue;
            }
            if (!preg_match('/\bCFM[A-Z0-9]{6,32}\b/i', strtoupper($description), $match)) {
                continue;
            }
            $depositCode = strtoupper($match[0]);
            $userId = self::userForDepositCode($depositCode);
            if ($userId < 1) {
                continue;
            }
            $result = WalletService::creditOnce($userId, $amount, 'bank', $externalId, 'Nạp tiền qua ngân hàng', [
                'deposit_code' => $depositCode,
                'provider_transaction' => $transaction,
            ]);
            if (($result['status'] ?? '') === 'paid') {
                $credited++;
            }
        }
        return ['ok' => true, 'status' => 'synced', 'credited' => $credited];
    }

    private static function userForDepositCode(string $depositCode): int
    {
        $connection = db();
        if (!$connection) {
            return 0;
        }
        try {
            $statement = $connection->prepare("SELECT user_id FROM wallet_deposits WHERE deposit_code = ? AND status = 'pending' LIMIT 1");
            $statement->execute([$depositCode]);
            return (int) $statement->fetchColumn();
        } catch (Throwable $exception) {
            Logger::error('Deposit code lookup failed', ['message' => $exception->getMessage()]);
            return 0;
        }
    }
}
