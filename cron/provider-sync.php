<?php
/**
 * ApexSMM Cron Job: Provider Service & Balance Sync
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/logger.php';
require_once __DIR__ . '/../providers/provider-manager.php';

$isCli = (php_sapi_name() === 'cli');
$providedKey = $_GET['key'] ?? '';

if (!$isCli && (!hash_equals(CRON_KEY, $providedKey) || empty(CRON_KEY))) {
    http_response_code(403);
    die("Forbidden\n");
}

Logger::info("Cron: provider-sync.php started.");

$providers = Database::fetchAll("SELECT id, name FROM providers WHERE status = 'active'");
$count = 0;

foreach ($providers as $p) {
    try {
        ProviderManager::testConnection((int)$p['id']);
        $count++;
    } catch (\Throwable $e) {
        Logger::error("Cron: Failed syncing provider #{$p['id']} [{$p['name']}]: " . $e->getMessage());
    }
}

$msg = "Cron: provider-sync.php completed. Processed {$count} providers.";
Logger::info($msg);

if (!$isCli) {
    header('Content-Type: text/plain');
    echo $msg . "\n";
}
