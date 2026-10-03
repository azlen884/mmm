<?php
/**
 * ApexSMM User Management Engine
 */

require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/logger.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../Wallet/WalletManager.php';

class UserManager
{
    public static function changePassword(int $userId, string $oldPassword, string $newPassword): array
    {
        $user = Database::fetchOne("SELECT password FROM users WHERE id = ?", [$userId]);
        if (!$user || !password_verify($oldPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect.'];
        }

        if (strlen($newPassword) < 8) {
            return ['success' => false, 'message' => 'New password must be at least 8 characters.'];
        }

        $hashed = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        Database::execute("UPDATE users SET password = ? WHERE id = ?", [$hashed, $userId]);

        audit_log('password_changed', 'user', $userId, 'User changed password', $userId);

        return ['success' => true, 'message' => 'Password updated successfully.'];
    }

    public static function regenerateApiKey(int $userId): string
    {
        $newKey = bin2hex(random_bytes(32));
        Database::execute("UPDATE users SET api_key = ? WHERE id = ?", [$newKey, $userId]);

        audit_log('api_key_regenerated', 'user', $userId, 'New API key generated', $userId);

        return $newKey;
    }

    public static function adminAdjustBalance(int $targetUserId, float $amount, string $actionType, string $reason, int $adminId): array
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Adjustment amount must be positive.');
        }

        $reason = trim($reason);
        if ($reason === '') {
            throw new \InvalidArgumentException('Please state a reason for this financial adjustment.');
        }

        if ($actionType === 'credit') {
            WalletManager::credit($targetUserId, $amount, 'manual_credit', 'ADJ-' . time(), $reason, $adminId);
            $msg = "Successfully credited " . format_currency($amount) . " to User #{$targetUserId}.";
        } elseif ($actionType === 'debit') {
            WalletManager::debit($targetUserId, $amount, 'manual_debit', 'ADJ-' . time(), $reason, $adminId);
            $msg = "Successfully debited " . format_currency($amount) . " from User #{$targetUserId}.";
        } else {
            throw new \InvalidArgumentException('Invalid adjustment type.');
        }

        return ['success' => true, 'message' => $msg];
    }
}
