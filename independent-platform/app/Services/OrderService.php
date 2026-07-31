<?php
declare(strict_types=1);

final class OrderService
{
    public static function create(int $userId, array $items, string $description = 'Mua sản phẩm'): array
    {
        if ($userId < 1 || $items === []) {
            return ['ok' => false, 'status' => 'invalid'];
        }
        $connection = db();
        if (!$connection) {
            return ['ok' => false, 'status' => 'database_unavailable'];
        }
        try {
            $connection->beginTransaction();
            $userStatement = $connection->prepare('SELECT balance FROM users WHERE id = ? AND status = "active" LIMIT 1 FOR UPDATE');
            $userStatement->execute([$userId]);
            $user = $userStatement->fetch();
            if (!$user) {
                $connection->rollBack();
                return ['ok' => false, 'status' => 'user_not_found'];
            }

            $resolved = [];
            $total = 0.0;
            foreach ($items as $item) {
                $productId = (int) ($item['product_id'] ?? 0);
                $quantity = min(100, max(1, (int) ($item['quantity'] ?? 1)));
                $productStatement = $connection->prepare("SELECT id, name, product_type, price, status FROM products WHERE id = ? AND status = 'active' LIMIT 1 FOR UPDATE");
                $productStatement->execute([$productId]);
                $product = $productStatement->fetch();
                if (!$product || in_array($product['product_type'], ['proxy', 'social'], true)) {
                    $connection->rollBack();
                    return ['ok' => false, 'status' => 'product_not_available'];
                }
                $stock = [];
                if ($product['product_type'] === 'digital') {
                    $stockStatement = $connection->query('SELECT id, secret_payload FROM product_stock WHERE product_id = ' . $productId . ' AND status = "available" ORDER BY id ASC LIMIT ' . $quantity . ' FOR UPDATE');
                    $stock = $stockStatement->fetchAll() ?: [];
                    if (count($stock) < $quantity) {
                        $connection->rollBack();
                        return ['ok' => false, 'status' => 'out_of_stock'];
                    }
                }
                $unitPrice = max(0, (float) $product['price']);
                $total += $unitPrice * $quantity;
                $resolved[] = ['product' => $product, 'quantity' => $quantity, 'stock' => $stock, 'unit_price' => $unitPrice];
            }
            if ($total <= 0 || (float) $user['balance'] < $total) {
                $connection->rollBack();
                return ['ok' => false, 'status' => 'insufficient_balance'];
            }

            $code = 'CFM-' . strtoupper(bin2hex(random_bytes(5)));
            $order = $connection->prepare('INSERT INTO orders (user_id, order_code, subtotal, total, status) VALUES (?, ?, ?, ?, "completed")');
            $order->execute([$userId, $code, $total, $total]);
            $orderId = (int) $connection->lastInsertId();
            $itemStatement = $connection->prepare('INSERT INTO order_items (order_id, product_id, quantity, unit_price, metadata) VALUES (?, ?, ?, ?, ?)');
            $stockUpdate = $connection->prepare('UPDATE product_stock SET status = "sold", sold_order_id = ? WHERE id = ?');
            $delivered = [];
            foreach ($resolved as $entry) {
                $payloads = array_map(static fn (array $row): string => (string) $row['secret_payload'], $entry['stock']);
                $itemStatement->execute([$orderId, (int) $entry['product']['id'], $entry['quantity'], $entry['unit_price'], json_encode(['delivery_count' => count($payloads)], JSON_UNESCAPED_UNICODE)]);
                foreach ($entry['stock'] as $row) {
                    $stockUpdate->execute([$orderId, (int) $row['id']]);
                    $delivered[] = ['product_id' => (int) $entry['product']['id'], 'payload' => (string) $row['secret_payload']];
                }
            }
            $before = (float) $user['balance'];
            $after = $before - $total;
            $transaction = $connection->prepare('INSERT INTO wallet_transactions (user_id, direction, amount, balance_before, balance_after, description, metadata) VALUES (?, "debit", ?, ?, ?, ?, ?)');
            $transaction->execute([$userId, $total, $before, $after, $description, json_encode(['order_id' => $orderId], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
            $update = $connection->prepare('UPDATE users SET balance = ? WHERE id = ?');
            $update->execute([$after, $userId]);
            $connection->commit();
            AuditService::record($userId, 'order.created', 'order', (string) $orderId, ['total' => $total]);
            return ['ok' => true, 'status' => 'completed', 'order_id' => $orderId, 'order_code' => $code, 'balance_after' => $after, 'delivered' => $delivered];
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            Logger::error('Order creation failed', ['user_id' => $userId, 'message' => $exception->getMessage()]);
            return ['ok' => false, 'status' => 'failed'];
        }
    }
}
