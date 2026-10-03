<?php
/**
 * ApexSMM Permission & IDOR Safeguards
 * Enforces ownership checks to strictly prevent Horizontal Privilege Escalation.
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';

class Permission
{
    public static function canAccessOrder(int $userId, int $orderId, bool $isAdmin = false): bool
    {
        if ($isAdmin) return true;
        $order = Database::fetchOne("SELECT user_id FROM orders WHERE id = ?", [$orderId]);
        return $order && (int)$order['user_id'] === $userId;
    }

    public static function canAccessTicket(int $userId, int $ticketId, bool $isAdmin = false): bool
    {
        if ($isAdmin) return true;
        $ticket = Database::fetchOne("SELECT user_id FROM tickets WHERE id = ?", [$ticketId]);
        return $ticket && (int)$ticket['user_id'] === $userId;
    }

    public static function canAccessPayment(int $userId, int $paymentId, bool $isAdmin = false): bool
    {
        if ($isAdmin) return true;
        $payment = Database::fetchOne("SELECT user_id FROM payments WHERE id = ?", [$paymentId]);
        return $payment && (int)$payment['user_id'] === $userId;
    }

    public static function enforceOrderAccess(int $orderId): array
    {
        $isAdmin = AdminAuth::check();
        $userId = Auth::id() ?: 0;

        $order = Database::fetchOne(
            "SELECT o.*, s.name as service_name, c.name as category_name 
             FROM orders o 
             LEFT JOIN services s ON o.service_id = s.id 
             LEFT JOIN categories c ON s.category_id = c.id 
             WHERE o.id = ?",
            [$orderId]
        );

        if (!$order) {
            http_response_code(404);
            require __DIR__ . '/../errors/404.php';
            exit;
        }

        if (!$isAdmin && (int)$order['user_id'] !== $userId) {
            Logger::warning("IDOR attempt on order #{$orderId} by user #{$userId}");
            http_response_code(403);
            require __DIR__ . '/../errors/403.php';
            exit;
        }

        return $order;
    }

    public static function enforceTicketAccess(int $ticketId): array
    {
        $isAdmin = AdminAuth::check();
        $userId = Auth::id() ?: 0;

        $ticket = Database::fetchOne(
            "SELECT t.*, u.username, u.email 
             FROM tickets t 
             JOIN users u ON t.user_id = u.id 
             WHERE t.id = ?",
            [$ticketId]
        );

        if (!$ticket) {
            http_response_code(404);
            require __DIR__ . '/../errors/404.php';
            exit;
        }

        if (!$isAdmin && (int)$ticket['user_id'] !== $userId) {
            Logger::warning("IDOR attempt on ticket #{$ticketId} by user #{$userId}");
            http_response_code(403);
            require __DIR__ . '/../errors/403.php';
            exit;
        }

        return $ticket;
    }
}
