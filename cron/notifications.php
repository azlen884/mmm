<?php
/**
 * ApexSMM Cron Job: Notification Purge
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/logger.php';

$isCli = (php_sapi_name() === 'cli');
$providedKey = $_GET['key'] ?? '';

if (!$isCli && (!hash_equals(CRON_KEY, $providedKey) || empty(CRON_KEY))) {
    http_response_code(403);
    die("Forbidden\n");
}

// Purge read notifications older than 30 days
$deleted = Database::execute(
    "DELETE FROM notifications WHERE is_read = 1 AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
);

$msg = "Cron: notifications.php completed. Purged {$deleted} stale notifications.";
Logger::info($msg);

if (!$isCli) {
    header('Content-Type: text/plain');
    echo $msg . "\n";
}
