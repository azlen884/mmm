<?php
/**
 * ApexSMM AJAX - Wallet Balance Query
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireLogin();
$user = Auth::user();

echo json_encode([
    'success' => true,
    'balance' => (float)$user['balance'],
    'formatted' => format_currency($user['balance']),
    'spent'   => (float)$user['spent'],
]);
