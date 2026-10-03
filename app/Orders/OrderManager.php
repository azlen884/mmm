<?php
/**
 * ApexSMM Order Processing Engine
 * Handles order validation, pricing calculations, transactional placement, and status monitoring.
 */

require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/logger.php';
require_once __DIR__ . '/../Wallet/WalletManager.php';
require_once __DIR__ . '/../Providers/ProviderService.php';

class OrderManager
{
    /**
     * Creates and dispatches a new order with atomic wallet deduction.
     */
    public static function createOrder(int $userId, int $serviceId, string $link, int $quantity): array
    {
        $link = trim($link);

        if ($link === '') {
            throw new \InvalidArgumentException('Please provide a valid target URL or username.');
        }

        // 1. Fetch and validate service
        $service = Database::fetchOne(
            "SELECT * FROM services WHERE id = ? AND status = 'active'",
            [$serviceId]
        );

        if (!$service) {
            throw new \Exception('Selected service is unavailable or inactive.');
        }

        // 2. Validate quantity limits
        $min = (int)$service['min_quantity'];
        $max = (int)$service['max_quantity'];

        if ($quantity < $min || $quantity > $max) {
            throw new \InvalidArgumentException("Quantity must be between " . number_format($min) . " and " . number_format($max) . ".");
        }

        // 3. Server-side price calculation: rate is per 1000 units
        $ratePer1000 = (float)$service['rate'];
        $charge = round(($ratePer1000 / 1000) * $quantity, 4);

        if ($charge < 0.0001) {
            $charge = 0.0001;
        }

        // 4. Atomic Transaction: Debit Wallet & Create Order Record
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            // Deduct user balance
            WalletManager::debit(
                $userId,
                $charge,
                'order',
                null,
                "Payment for order on service: {$service['name']} (Qty: {$quantity})"
            );

            // Insert order into MySQL
            $stmt = $pdo->prepare(
                "INSERT INTO orders (
                    user_id, service_id, provider_id, link, quantity, 
                    charge, start_count, remains, status, runs, interval_minutes
                 ) VALUES (?, ?, ?, ?, ?, ?, 0, ?, 'pending', 0, 0)"
            );
            $stmt->execute([
                $userId,
                $serviceId,
                $service['provider_id'] ?: null,
                $link,
                $quantity,
                $charge,
                $quantity
            ]);

            $orderId = (int)$pdo->lastInsertId();

            // Link reference to the latest transaction
            $pdo->prepare("UPDATE transactions SET reference_id = ? WHERE user_id = ? AND reference_id IS NULL ORDER BY id DESC LIMIT 1")
                ->execute(['ORD-' . $orderId, $userId]);

            $pdo->commit();

            // 5. Submit to provider API asynchronously or synchronously
            if (!empty($service['provider_id'])) {
                try {
                    ProviderService::submitOrder($orderId);
                } catch (\Throwable $e) {
                    Logger::error("Provider dispatch for Order #{$orderId} caught error: " . $e->getMessage());
                }
            }

            audit_log('order_created', 'order', $orderId, [
                'service'  => $service['name'],
                'quantity' => $quantity,
                'charge'   => $charge,
                'link'     => $link,
            ], $userId);

            return [
                'success'  => true,
                'order_id' => $orderId,
                'charge'   => $charge,
                'message'  => "Order #{$orderId} placed successfully!"
            ];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Logger::error("Order placement failed for User #{$userId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Retrieves paginated orders for a user with filters.
     */
    public static function getUserOrders(int $userId, string $status = 'all', string $search = '', int $page = 1, int $perPage = 15): array
    {
        $params = [$userId];
        $where = "WHERE o.user_id = ?";

        if ($status !== 'all' && in_array($status, ['pending', 'processing', 'in_progress', 'completed', 'partial', 'cancelled', 'refunded', 'failed'])) {
            $where .= " AND o.status = ?";
            $params[] = $status;
        }

        if ($search !== '') {
            $where .= " AND (o.id = ? OR o.link LIKE ? OR s.name LIKE ?)";
            $params[] = is_numeric($search) ? (int)$search : 0;
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $countSql = "SELECT COUNT(*) FROM orders o LEFT JOIN services s ON o.service_id = s.id {$where}";
        $total = (int)Database::fetchValue($countSql, $params);

        $offset = max(0, ($page - 1) * $perPage);
        $sql = "SELECT o.*, s.name as service_name, s.refill, s.cancel, c.name as category_name 
                FROM orders o 
                LEFT JOIN services s ON o.service_id = s.id 
                LEFT JOIN categories c ON s.category_id = c.id 
                {$where} 
                ORDER BY o.id DESC 
                LIMIT {$perPage} OFFSET {$offset}";

        $orders = Database::fetchAll($sql, $params);

        return [
            'data'         => $orders,
            'total'        => $total,
            'page'         => $page,
            'per_page'     => $perPage,
            'total_pages'  => ceil($total / $perPage) ?: 1,
        ];
    }
}
