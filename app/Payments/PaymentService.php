<?php
/**
 * ApexSMM Payment Service
 * Secure payment initiation, idempotency validation, and transactional wallet crediting.
 */

require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/logger.php';
require_once __DIR__ . '/../../payments/payment-manager.php';
require_once __DIR__ . '/../Wallet/WalletManager.php';

class PaymentService
{
    /**
     * Initializes a new payment invoice.
     */
    public static function createPayment(int $userId, string $gatewayCode, float $amount): array
    {
        $gateway = PaymentManager::getGateway($gatewayCode);
        if (!$gateway || $gateway['status'] !== 'active') {
            throw new \Exception('This payment gateway is currently unavailable.');
        }

        $min = (float)$gateway['min_amount'];
        $max = (float)$gateway['max_amount'];

        if ($amount < $min || $amount > $max) {
            throw new \InvalidArgumentException("Deposit amount must be between " . format_currency($min) . " and " . format_currency($max) . ".");
        }

        $feePercent = (float)$gateway['fee_percentage'];
        $feeFixed   = (float)$gateway['fee_fixed'];
        $fee        = round(($amount * ($feePercent / 100)) + $feeFixed, 4);
        $netAmount  = round($amount + $fee, 4);

        $txId = 'PAY-' . strtoupper(substr(bin2hex(random_bytes(6)), 0, 10)) . '-' . time();

        Database::execute(
            "INSERT INTO payments (user_id, gateway, transaction_id, amount, fee, net_amount, currency, status) 
             VALUES (?, ?, ?, ?, ?, ?, 'USD', 'pending')",
            [$userId, $gatewayCode, $txId, $amount, $fee, $netAmount]
        );

        $payment = Database::fetchOne("SELECT * FROM payments WHERE transaction_id = ?", [$txId]);

        $handler = PaymentManager::getHandler($gatewayCode);
        if (!$handler) {
            throw new \Exception("Payment handler for {$gatewayCode} not found.");
        }

        $credentials = json_decode($gateway['credentials'] ?? '{}', true) ?: [];
        $credentials['instructions'] = $gateway['instructions'];

        $result = $handler->createPayment($payment, $credentials);

        audit_log('payment_initiated', 'payment', $payment['id'], [
            'gateway' => $gatewayCode,
            'amount'  => $amount,
            'fee'     => $fee,
            'tx'      => $txId
        ], $userId);

        return array_merge($result, [
            'payment' => $payment,
            'tx_id'   => $txId,
        ]);
    }

    /**
     * Idempotent completion of verified payment.
     */
    public static function completePayment(string $transactionId, array $gatewayResponse = []): bool
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            // Lock payment record
            $stmt = $pdo->prepare("SELECT * FROM payments WHERE transaction_id = ? FOR UPDATE");
            $stmt->execute([$transactionId]);
            $payment = $stmt->fetch();

            if (!$payment) {
                $pdo->rollBack();
                Logger::payment("Payment completion failed: TX {$transactionId} not found.");
                return false;
            }

            // IDEMPOTENCY CHECK: If already completed, do NOT credit wallet again
            if ($payment['status'] === 'completed') {
                $pdo->rollBack();
                Logger::payment("Payment {$transactionId} already completed. Duplicate processing ignored.");
                return true;
            }

            // Mark completed
            $upd = $pdo->prepare("UPDATE payments SET status = 'completed', gateway_response = ? WHERE id = ?");
            $upd->execute([json_encode($gatewayResponse), $payment['id']]);

            // Credit user wallet
            WalletManager::credit(
                (int)$payment['user_id'],
                (float)$payment['amount'],
                'deposit',
                $payment['transaction_id'],
                "Wallet deposit via " . ucfirst($payment['gateway'])
            );

            $pdo->commit();

            audit_log('payment_completed', 'payment', $payment['id'], [
                'tx'     => $transactionId,
                'amount' => $payment['amount']
            ], (int)$payment['user_id']);

            return true;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Logger::error("Error completing payment {$transactionId}: " . $e->getMessage());
            throw $e;
        }
    }
}
