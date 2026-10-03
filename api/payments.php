<?php
/**
 * ApexSMM Payment Webhook & IPN Handler
 * Idempotent, cryptographically validated payment processing.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/logger.php';
require_once __DIR__ . '/../payments/payment-manager.php';
require_once __DIR__ . '/../app/Payments/PaymentService.php';

$gateway = trim($_GET['gateway'] ?? '');
if ($gateway === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing gateway identifier.']);
    exit;
}

$gwRecord = PaymentManager::getGateway($gateway);
if (!$gwRecord || $gwRecord['status'] !== 'active') {
    http_response_code(400);
    echo json_encode(['error' => 'Gateway is inactive or not found.']);
    exit;
}

$handler = PaymentManager::getHandler($gateway);
if (!$handler) {
    http_response_code(400);
    echo json_encode(['error' => 'Payment handler missing.']);
    exit;
}

$config = json_decode($gwRecord['credentials'] ?? '{}', true) ?: [];

try {
    $verification = $handler->verifyPayment($_POST, $config);

    if (!empty($verification['verified']) && !empty($verification['tx_id'])) {
        PaymentService::completePayment($verification['tx_id'], $_POST);
        echo json_encode(['status' => 'success', 'message' => 'Payment confirmed and credited.']);
        exit;
    } else {
        Logger::payment("Webhook verification failed for gateway {$gateway}", [
            'post'  => $_POST,
            'error' => $verification['error'] ?? 'Unverified'
        ]);
        http_response_code(400);
        echo json_encode(['status' => 'ignored', 'message' => $verification['error'] ?? 'Unverified payload.']);
        exit;
    }
} catch (\Throwable $e) {
    Logger::error("Exception in payment webhook ({$gateway}): " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal webhook handler error.']);
    exit;
}
