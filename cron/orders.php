<?php
/**
 * ApexSMM Cron Job: Order Status Synchronization
 * Safe execution context validation & automated provider synchronization.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/logger.php';
require_once __DIR__ . '/../app/Providers/ProviderService.php';

// Execution context validation (CLI or authorized cron key)
$isCli = (php_sapi_name() === 'cli');
$providedKey = $_GET['key'] ?? '';

if (!$isCli && (!hash_equals(CRON_KEY, $providedKey) || empty(CRON_KEY))) {
    http_response_code(403);
    die("Forbidden: Invalid Cron Authentication Key\n");
}

$startTime = microtime(true);
Logger::info("Cron: orders.php started.");

// Fetch pending or processing orders with a provider ID
$orders = Database::fetchAll(
    "SELECT id FROM orders 
     WHERE status IN ('pending', 'processing', 'in_progress') 
       AND provider_id IS NOT NULL 
       AND provider_order_id IS NOT NULL 
     ORDER BY id ASC 
     LIMIT 50"
);

$synced = 0;
$failed = 0;

foreach ($orders as $o) {
    try {
        ProviderService::checkOrderStatus((int)$o['id']);
        $synced++;
    } catch (\Throwable $e) {
        $failed++;
        Logger::error("Cron Order Sync failed for Order #{$o['id']}: " . $e->getMessage());
    }
}

$elapsed = round(microtime(true) - $startTime, 3);
$msg = "Cron: orders.php finished in {$elapsed}s. Synced: {$synced}, Errors: {$failed}.";
Logger::info($msg);

if (!$isCli) {
    header('Content-Type: text/plain');
    echo $msg . "\n";
}
