<?php
/**
 * ApexSMM Provider Service Logic
 * Manages service imports, synchronization, and automated order dispatching.
 */

require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/logger.php';
require_once __DIR__ . '/../../providers/provider-manager.php';
require_once __DIR__ . '/../Wallet/WalletManager.php';

class ProviderService
{
    /**
     * Imports services from an external provider into MySQL.
     */
    public static function importServices(int $providerId, ?int $targetCategoryId = null, float $marginPercent = 20.0): array
    {
        $adapter = ProviderManager::getAdapter($providerId);
        $externalServices = $adapter->getServices();

        if (empty($externalServices) || !is_array($externalServices)) {
            return ['imported' => 0, 'updated' => 0, 'total' => 0];
        }

        $imported = 0;
        $updated = 0;

        foreach ($externalServices as $item) {
            $extId    = (string)($item['service'] ?? '');
            $name     = trim((string)($item['name'] ?? ''));
            $categoryName = trim((string)($item['category'] ?? 'General'));
            $provRate = (float)($item['rate'] ?? 0);
            $min      = (int)($item['min'] ?? 10);
            $max      = (int)($item['max'] ?? 10000);
            $type     = (string)($item['type'] ?? 'Default');
            $refill   = !empty($item['refill']) ? 1 : 0;
            $cancel   = !empty($item['cancel']) ? 1 : 0;
            $dripfeed = !empty($item['dripfeed']) ? 1 : 0;

            if ($extId === '' || $name === '') continue;

            // Resolve Category
            $catId = $targetCategoryId;
            if (!$catId) {
                $existingCat = Database::fetchOne("SELECT id FROM categories WHERE name = ?", [$categoryName]);
                if ($existingCat) {
                    $catId = (int)$existingCat['id'];
                } else {
                    Database::execute("INSERT INTO categories (name, status) VALUES (?, 'active')", [$categoryName]);
                    $catId = (int)Database::lastInsertId();
                }
            }

            // Calculate selling rate with margin
            $sellRate = round($provRate * (1 + ($marginPercent / 100)), 4);

            $existing = Database::fetchOne(
                "SELECT id FROM services WHERE provider_id = ? AND provider_service_id = ?",
                [$providerId, $extId]
            );

            if ($existing) {
                Database::execute(
                    "UPDATE services SET 
                        provider_rate = ?, provider_min = ?, provider_max = ?,
                        refill = ?, cancel = ?, dripfeed = ?, provider_status = 'active'
                     WHERE id = ?",
                    [$provRate, $min, $max, $refill, $cancel, $dripfeed, $existing['id']]
                );
                $updated++;
            } else {
                Database::execute(
                    "INSERT INTO services (
                        category_id, provider_id, provider_service_id, name, type,
                        rate, provider_rate, min_quantity, max_quantity, provider_min, provider_max,
                        refill, cancel, dripfeed, status, provider_status
                     ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 'active')",
                    [
                        $catId, $providerId, $extId, $name, $type,
                        $sellRate, $provRate, $min, $max, $min, $max,
                        $refill, $cancel, $dripfeed
                    ]
                );
                $imported++;
            }
        }

        Database::execute("UPDATE providers SET last_sync_at = NOW() WHERE id = ?", [$providerId]);

        return [
            'imported' => $imported,
            'updated'  => $updated,
            'total'    => count($externalServices)
        ];
    }

    /**
     * Submits an order to the external provider API.
     */
    public static function submitOrder(int $orderId): bool
    {
        $order = Database::fetchOne(
            "SELECT o.*, s.provider_id, s.provider_service_id 
             FROM orders o 
             JOIN services s ON o.service_id = s.id 
             WHERE o.id = ?",
            [$orderId]
        );

        if (!$order) {
            throw new \Exception("Order #{$orderId} not found.");
        }

        // If manual service or no provider, leave as pending/processing for manual fulfillment
        if (empty($order['provider_id']) || empty($order['provider_service_id'])) {
            Database::execute("UPDATE orders SET status = 'processing' WHERE id = ?", [$orderId]);
            return true;
        }

        try {
            $adapter = ProviderManager::getAdapter((int)$order['provider_id']);
            $res = $adapter->addOrder($order['provider_service_id'], $order['link'], (int)$order['quantity']);

            if (!empty($res['order'])) {
                Database::execute(
                    "UPDATE orders SET 
                        provider_order_id = ?, 
                        status = 'processing', 
                        provider_response = ?, 
                        error_message = NULL 
                     WHERE id = ?",
                    [(string)$res['order'], json_encode($res), $orderId]
                );
                Logger::provider("Order #{$orderId} submitted to provider. Ext ID: {$res['order']}");
                return true;
            } else {
                $err = $res['error'] ?? 'Unknown error from provider';
                Database::execute(
                    "UPDATE orders SET status = 'failed', error_message = ?, provider_response = ? WHERE id = ?",
                    [$err, json_encode($res), $orderId]
                );
                Logger::error("Order #{$orderId} submission failed: {$err}");
                return false;
            }
        } catch (\Throwable $e) {
            Database::execute(
                "UPDATE orders SET status = 'failed', error_message = ? WHERE id = ?",
                [$e->getMessage(), $orderId]
            );
            Logger::error("Exception submitting order #{$orderId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Synchronizes order status from provider.
     */
    public static function checkOrderStatus(int $orderId): array
    {
        $order = Database::fetchOne(
            "SELECT o.*, s.provider_id 
             FROM orders o 
             JOIN services s ON o.service_id = s.id 
             WHERE o.id = ?",
            [$orderId]
        );

        if (!$order || empty($order['provider_order_id']) || empty($order['provider_id'])) {
            return ['status' => $order['status'] ?? 'unknown'];
        }

        try {
            $adapter = ProviderManager::getAdapter((int)$order['provider_id']);
            $res = $adapter->getStatus($order['provider_order_id']);

            if (!empty($res['status'])) {
                $rawStatus = strtolower(str_replace(' ', '_', $res['status']));
                $startCount = (int)($res['start_count'] ?? $order['start_count']);
                $remains = (int)($res['remains'] ?? $order['remains']);

                // Map standard statuses
                $statusMap = [
                    'completed'   => 'completed',
                    'complete'    => 'completed',
                    'processing'  => 'processing',
                    'in_progress' => 'in_progress',
                    'pending'     => 'pending',
                    'partial'     => 'partial',
                    'canceled'    => 'cancelled',
                    'cancelled'   => 'cancelled',
                    'refunded'    => 'refunded',
                ];

                $newStatus = $statusMap[$rawStatus] ?? $order['status'];

                // Handle partial refund calculation if partial
                if ($newStatus === 'partial' && $order['status'] !== 'partial') {
                    if ($remains > 0 && (int)$order['quantity'] > 0) {
                        $refundRatio = $remains / (int)$order['quantity'];
                        $refundAmount = round((float)$order['charge'] * $refundRatio, 4);
                        if ($refundAmount > 0) {
                            WalletManager::credit(
                                (int)$order['user_id'],
                                $refundAmount,
                                'refund',
                                'ORD-' . $orderId,
                                "Partial order refund for Order #{$orderId} ({$remains} remains)"
                            );
                        }
                    }
                } elseif ($newStatus === 'cancelled' && $order['status'] !== 'cancelled' && $order['status'] !== 'refunded') {
                    // Full refund on cancel
                    WalletManager::credit(
                        (int)$order['user_id'],
                        (float)$order['charge'],
                        'refund',
                        'ORD-' . $orderId,
                        "Full order refund for cancelled Order #{$orderId}"
                    );
                }

                Database::execute(
                    "UPDATE orders SET 
                        status = ?, start_count = ?, remains = ?, provider_response = ? 
                     WHERE id = ?",
                    [$newStatus, $startCount, $remains, json_encode($res), $orderId]
                );

                return ['status' => $newStatus, 'start_count' => $startCount, 'remains' => $remains];
            }
        } catch (\Throwable $e) {
            Logger::error("Failed to check status for order #{$orderId}: " . $e->getMessage());
        }

        return ['status' => $order['status']];
    }
}
