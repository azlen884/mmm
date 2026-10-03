<?php
/**
 * ApexSMM Public Services API Endpoint
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../app/Services/ServiceManager.php';

$categoryId = !empty($_GET['category']) ? (int)$_GET['category'] : null;
$search = trim($_GET['search'] ?? '');

$services = ServiceManager::getServices($categoryId, $search, true);

$formatted = [];
foreach ($services as $s) {
    $formatted[] = [
        'id'          => (int)$s['id'],
        'name'        => $s['name'],
        'category'    => $s['category_name'] ?? 'General',
        'rate'        => number_format((float)$s['rate'], 4, '.', ''),
        'min'         => (int)$s['min_quantity'],
        'max'         => (int)$s['max_quantity'],
        'refill'      => (bool)$s['refill'],
        'cancel'      => (bool)$s['cancel'],
        'dripfeed'    => (bool)$s['dripfeed'],
        'description' => $s['description'],
    ];
}

echo json_encode($formatted, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
