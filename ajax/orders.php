<?php
/**
 * ApexSMM AJAX - Orders Endpoint
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../app/Orders/OrderManager.php';

Auth::requireLogin();
$user = Auth::user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $serviceId = (int)($_POST['service_id'] ?? 0);
    $link      = trim($_POST['link'] ?? '');
    $quantity  = (int)($_POST['quantity'] ?? 0);

    try {
        $result = OrderManager::createOrder($user['id'], $serviceId, $link, $quantity);
        echo json_encode([
            'success'  => true,
            'order_id' => $result['order_id'],
            'charge'   => $result['charge'],
            'message'  => $result['message'],
        ]);
    } catch (\Throwable $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
        ]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
