<?php
/**
 * ApexSMM AJAX - Tickets Quick Count & Status
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

Auth::requireLogin();
$user = Auth::user();

$openCount = (int)Database::fetchValue(
    "SELECT COUNT(*) FROM tickets WHERE user_id = ? AND status IN ('open', 'customer_reply')",
    [$user['id']]
);

echo json_encode(['success' => true, 'open_tickets' => $openCount]);
