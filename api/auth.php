<?php
/**
 * ApexSMM API Key Verification Endpoint
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

$apiKey = trim($_POST['key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '');
if ($apiKey === '') {
    http_response_code(401);
    echo json_encode(['error' => 'API key required.']);
    exit;
}

$user = Database::fetchOne(
    "SELECT id, username, email, balance, status FROM users WHERE api_key = ?",
    [$apiKey]
);

if (!$user || $user['status'] !== 'active') {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid or inactive API credentials.']);
    exit;
}

echo json_encode([
    'valid'    => true,
    'username' => $user['username'],
    'balance'  => number_format((float)$user['balance'], 4, '.', ''),
    'currency' => get_setting('currency', 'USD'),
]);
