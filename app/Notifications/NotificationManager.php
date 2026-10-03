<?php
/**
 * ApexSMM Real Notification Engine
 */

require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/logger.php';

class NotificationManager
{
    public static function send(?int $userId, string $title, string $message, string $type = 'info', ?string $link = null): int
    {
        Database::execute(
            "INSERT INTO notifications (user_id, title, message, type, link, is_read) 
             VALUES (?, ?, ?, ?, ?, 0)",
            [$userId, $title, $message, $type, $link]
        );
        return (int)Database::lastInsertId();
    }

    public static function broadcast(string $title, string $message, string $type = 'announcement', ?string $link = null): int
    {
        return self::send(null, $title, $message, $type, $link);
    }

    public static function getUserNotifications(int $userId, bool $unreadOnly = false, int $limit = 30): array
    {
        $where = "WHERE (user_id = ? OR user_id IS NULL)";
        $params = [$userId];

        if ($unreadOnly) {
            $where .= " AND is_read = 0";
        }

        return Database::fetchAll(
            "SELECT * FROM notifications {$where} ORDER BY id DESC LIMIT {$limit}",
            $params
        );
    }

    public static function unreadCount(int $userId): int
    {
        return (int)Database::fetchValue(
            "SELECT COUNT(*) FROM notifications WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0",
            [$userId]
        );
    }

    public static function markAsRead(int $notifId, int $userId): void
    {
        Database::execute(
            "UPDATE notifications SET is_read = 1 WHERE id = ? AND (user_id = ? OR user_id IS NULL)",
            [$notifId, $userId]
        );
    }

    public static function markAllAsRead(int $userId): void
    {
        Database::execute(
            "UPDATE notifications SET is_read = 1 WHERE (user_id = ? OR user_id IS NULL)",
            [$userId]
        );
    }
}
