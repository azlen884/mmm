<?php
/**
 * ApexSMM Support Ticket Engine
 * Handles tickets, conversational replies, and secure attachment handling.
 */

require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/logger.php';
require_once __DIR__ . '/../../includes/functions.php';

class TicketManager
{
    /**
     * Handles safe attachment upload with strict extension & MIME verification.
     */
    public static function handleAttachment(array $file): ?string
    {
        if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        // Limit size to 5MB
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new \InvalidArgumentException('Attachment size must not exceed 5MB.');
        }

        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'txt', 'zip'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExts)) {
            throw new \InvalidArgumentException('Unsupported file type. Allowed: jpg, png, webp, pdf, txt, zip.');
        }

        // Validate MIME type with finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = [
            'image/jpeg', 'image/png', 'image/webp',
            'application/pdf', 'text/plain', 'application/zip', 'application/x-zip-compressed'
        ];

        if (!in_array($mime, $allowedMimes)) {
            throw new \InvalidArgumentException('Invalid file content detected.');
        }

        $uploadDir = __DIR__ . '/../../storage/uploads/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        // Generate non-guessable random filename
        $safeName = 'att_' . bin2hex(random_bytes(16)) . '.' . $ext;
        $target = $uploadDir . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new \Exception('Failed to store attachment.');
        }

        return $safeName;
    }

    public static function createTicket(int $userId, string $subject, string $category, string $priority, string $message, ?array $file = null): int
    {
        $subject = trim($subject);
        $message = trim($message);

        if ($subject === '' || $message === '') {
            throw new \InvalidArgumentException('Subject and message are required.');
        }

        $attachment = null;
        if ($file && !empty($file['tmp_name'])) {
            $attachment = self::handleAttachment($file);
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO tickets (user_id, subject, category, priority, status) VALUES (?, ?, ?, ?, 'open')"
            );
            $stmt->execute([$userId, $subject, $category, $priority]);
            $ticketId = (int)$pdo->lastInsertId();

            $msgStmt = $pdo->prepare(
                "INSERT INTO ticket_messages (ticket_id, user_id, is_admin, message, attachment) VALUES (?, ?, 0, ?, ?)"
            );
            $msgStmt->execute([$ticketId, $userId, $message, $attachment]);

            $pdo->commit();

            audit_log('ticket_created', 'ticket', $ticketId, "Ticket created: {$subject}", $userId);

            return $ticketId;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Logger::error("Failed to create ticket: " . $e->getMessage());
            throw $e;
        }
    }

    public static function addReply(int $ticketId, int $userId, string $message, bool $isAdmin = false, ?array $file = null): void
    {
        $message = trim($message);
        if ($message === '') {
            throw new \InvalidArgumentException('Reply message cannot be empty.');
        }

        $attachment = null;
        if ($file && !empty($file['tmp_name'])) {
            $attachment = self::handleAttachment($file);
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $msgStmt = $pdo->prepare(
                "INSERT INTO ticket_messages (ticket_id, user_id, is_admin, message, attachment) VALUES (?, ?, ?, ?, ?)"
            );
            $msgStmt->execute([$ticketId, $userId, $isAdmin ? 1 : 0, $message, $attachment]);

            // Update ticket status
            $newStatus = $isAdmin ? 'answered' : 'customer_reply';
            $pdo->prepare("UPDATE tickets SET status = ?, updated_at = NOW() WHERE id = ?")
                ->execute([$newStatus, $ticketId]);

            $pdo->commit();

            audit_log('ticket_reply', 'ticket', $ticketId, $isAdmin ? 'Admin reply' : 'User reply', $isAdmin ? null : $userId, $isAdmin ? $userId : null);
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Logger::error("Failed to add reply to ticket #{$ticketId}: " . $e->getMessage());
            throw $e;
        }
    }
}
