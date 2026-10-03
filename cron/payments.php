<?php
/**
 * ApexSMM Cron Job: Payment Cleanup & Verification
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

// Expire pending payments older than 24 hours
$affected = Database::execute(
    "UPDATE payments SET status = 'cancelled' 
     WHERE status = 'pending' AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)"
);

$msg = "Cron: payments.php completed. Cancelled {$affected} stale pending invoices.";
Logger::info($msg);

if (!$isCli) {
    header('Content-Type: text/plain');
    echo $msg . "\n";
}
