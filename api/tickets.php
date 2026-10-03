<?php
/**
 * ApexSMM User Tickets API
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../app/Tickets/TicketManager.php';

$apiKey = trim($_POST['key'] ?? '');
if (!$apiKey) {
    http_response_code(401);
    echo json_encode(['error' => 'API key required.']);
    exit;
}

$user = Database::fetchOne("SELECT id FROM users WHERE api_key = ? AND status = 'active'", [$apiKey]);
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid API key.']);
    exit;
}

$tickets = Database::fetchAll(
    "SELECT id, subject, category, priority, status, created_at, updated_at 
     FROM tickets WHERE user_id = ? ORDER BY id DESC",
    [$user['id']]
);

echo json_encode($tickets, JSON_PRETTY_PRINT);
