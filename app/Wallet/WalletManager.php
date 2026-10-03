<?php
/**
 * ApexSMM Financial & Wallet Engine
 * Atomic, transactional balance adjustments with row-level locking.
 */

require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/logger.php';

class WalletManager
{
    /**
     * Atomically credits a user's wallet with transaction log.
     */
    public static function credit(int $userId, float $amount, string $type, ?string $referenceId = null, string $description = '', ?int $adminId = null): bool
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Credit amount must be greater than zero.');
        }

        $pdo = Database::getConnection();
        $isNested = $pdo->inTransaction();

        if (!$isNested) {
            $pdo->beginTransaction();
        }

        try {
            // Row-level lock to prevent concurrent race conditions
            $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $currentBalance = $stmt->fetchColumn();

            if ($currentBalance === false) {
                throw new \Exception("User #{$userId} not found.");
            }

            $currentBalance = (float)$currentBalance;
            $newBalance = $currentBalance + $amount;

            // Update user balance
            $upd = $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
            $upd->execute([$newBalance, $userId]);

            // Insert transaction record
            $tx = $pdo->prepare(
                "INSERT INTO transactions (user_id, type, amount, balance_before, balance_after, reference_id, description, admin_id) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $tx->execute([$userId, $type, $amount, $currentBalance, $newBalance, $referenceId, $description, $adminId]);

            audit_log('wallet_credit', 'user', $userId, [
                'amount'     => $amount,
                'type'       => $type,
                'ref'        => $referenceId,
                'old_bal'    => $currentBalance,
                'new_bal'    => $newBalance,
                'desc'       => $description,
            ], $userId, $adminId);

            if (!$isNested) {
                $pdo->commit();
            }
            return true;
        } catch (\Throwable $e) {
            if (!$isNested && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Logger::error("Wallet credit failed for User #{$userId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Atomically debits a user's wallet with balance check and transaction log.
     */
    public static function debit(int $userId, float $amount, string $type, ?string $referenceId = null, string $description = '', ?int $adminId = null): bool
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Debit amount must be greater than zero.');
        }

        $pdo = Database::getConnection();
        $isNested = $pdo->inTransaction();

        if (!$isNested) {
            $pdo->beginTransaction();
        }

        try {
            // Row-level lock
            $stmt = $pdo->prepare("SELECT balance, spent FROM users WHERE id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user) {
                throw new \Exception("User #{$userId} not found.");
            }

            $currentBalance = (float)$user['balance'];
            $currentSpent   = (float)$user['spent'];

            if ($currentBalance < $amount) {
                throw new \Exception("Insufficient wallet funds. Current balance: " . format_currency($currentBalance));
            }

            $newBalance = $currentBalance - $amount;
            $newSpent   = $currentSpent + ($type === 'order' ? $amount : 0);

            // Update user balance & spent
            $upd = $pdo->prepare("UPDATE users SET balance = ?, spent = ? WHERE id = ?");
            $upd->execute([$newBalance, $newSpent, $userId]);

            // Insert transaction record
            $tx = $pdo->prepare(
                "INSERT INTO transactions (user_id, type, amount, balance_before, balance_after, reference_id, description, admin_id) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $tx->execute([$userId, $type, $amount, $currentBalance, $newBalance, $referenceId, $description, $adminId]);

            audit_log('wallet_debit', 'user', $userId, [
                'amount'     => $amount,
                'type'       => $type,
                'ref'        => $referenceId,
                'old_bal'    => $currentBalance,
                'new_bal'    => $newBalance,
                'desc'       => $description,
            ], $userId, $adminId);

            if (!$isNested) {
                $pdo->commit();
            }
            return true;
        } catch (\Throwable $e) {
            if (!$isNested && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Logger::error("Wallet debit failed for User #{$userId}: " . $e->getMessage());
            throw $e;
        }
    }
}
