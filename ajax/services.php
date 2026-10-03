<?php
/**
 * ApexSMM AJAX - Services Filter & Price Check
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../app/Services/ServiceManager.php';

$categoryId = !empty($_GET['category']) ? (int)$_GET['category'] : null;
$search     = trim($_GET['search'] ?? '');

try {
    $services = ServiceManager::getServices($categoryId, $search, true);
    echo json_encode(['success' => true, 'services' => $services]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to query services.']);
}
