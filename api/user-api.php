<?php
/**
 * ApexSMM Standard User SMM API Endpoint
 * Accepts standard POST parameters (key, action, service, link, quantity, order, orders)
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/logger.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../app/Orders/OrderManager.php';
require_once __DIR__ . '/../app/Services/ServiceManager.php';

function json_err(string $message, int $code = 400)
{
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_err('Method Not Allowed. Use HTTP POST.', 405);
}

$apiKey = trim($_POST['key'] ?? '');
if ($apiKey === '') {
    json_err('API key is missing.', 401);
}

// Authenticate API key against MySQL
$user = Database::fetchOne(
    "SELECT id, username, email, balance, status FROM users WHERE api_key = ?",
    [$apiKey]
);

if (!$user) {
    json_err('Invalid API key provided.', 401);
}

if ($user['status'] !== 'active') {
    json_err('This account is suspended.', 403);
}

$userId = (int)$user['id'];

// Rate Limit: 120 API requests per minute per user
if (!RateLimiter::check('user_api', 'uid_' . $userId, 120, 60)) {
    json_err('Rate limit exceeded. Maximum 120 requests per minute.', 429);
}

$action = strtolower(trim($_POST['action'] ?? ''));

switch ($action) {
    case 'balance':
        echo json_encode([
            'balance'  => number_format((float)$user['balance'], 4, '.', ''),
            'currency' => get_setting('currency', 'USD'),
        ]);
        exit;

    case 'services':
        $services = Database::fetchAll(
            "SELECT s.id as service, s.name, s.type, c.name as category, 
                    s.rate, s.min_quantity as min, s.max_quantity as max,
                    s.refill, s.cancel, s.dripfeed
             FROM services s 
             LEFT JOIN categories c ON s.category_id = c.id 
             WHERE s.status = 'active' 
             ORDER BY c.sort_order ASC, s.id ASC"
        );

        $formatted = [];
        foreach ($services as $s) {
            $formatted[] = [
                'service'  => (int)$s['service'],
                'name'     => $s['name'],
                'type'     => $s['type'],
                'category' => $s['category'] ?? 'General',
                'rate'     => number_format((float)$s['rate'], 4, '.', ''),
                'min'      => (int)$s['min'],
                'max'      => (int)$s['max'],
                'refill'   => (bool)$s['refill'],
                'cancel'   => (bool)$s['cancel'],
                'dripfeed' => (bool)$s['dripfeed'],
            ];
        }

        echo json_encode($formatted, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;

    case 'add':
        $serviceId = (int)($_POST['service'] ?? 0);
        $link      = trim($_POST['link'] ?? '');
        $quantity  = (int)($_POST['quantity'] ?? 0);

        if (!$serviceId || !$link || !$quantity) {
            json_err('Missing required parameters: service, link, quantity.');
        }

        try {
            $orderRes = OrderManager::createOrder($userId, $serviceId, $link, $quantity);
            echo json_encode(['order' => (int)$orderRes['order_id']]);
            exit;
        } catch (\Throwable $e) {
            json_err($e->getMessage(), 400);
        }

    case 'status':
        if (!empty($_POST['orders'])) {
            // Multi status query
            $orderIds = array_map('intval', explode(',', $_POST['orders']));
            $orderIds = array_slice($orderIds, 0, 100); // max 100 orders
            if (empty($orderIds)) {
                json_err('No order IDs provided.');
            }

            $inPlaceholders = implode(',', array_fill(0, count($orderIds), '?'));
            $params = array_merge([$userId], $orderIds);
            $rows = Database::fetchAll(
                "SELECT id, charge, start_count, status, remains FROM orders WHERE user_id = ? AND id IN ($inPlaceholders)",
                $params
            );

            $result = [];
            $currency = get_setting('currency', 'USD');
            foreach ($rows as $r) {
                $result[(string)$r['id']] = [
                    'charge'      => number_format((float)$r['charge'], 4, '.', ''),
                    'start_count' => (string)$r['start_count'],
                    'status'      => ucfirst(str_replace('_', ' ', $r['status'])),
                    'remains'     => (string)$r['remains'],
                    'currency'    => $currency,
                ];
            }
            echo json_encode($result);
            exit;
        }

        $orderId = (int)($_POST['order'] ?? 0);
        if (!$orderId) {
            json_err('Order parameter is required.');
        }

        $order = Database::fetchOne(
            "SELECT charge, start_count, status, remains FROM orders WHERE id = ? AND user_id = ?",
            [$orderId, $userId]
        );

        if (!$order) {
            json_err('Order not found.', 404);
        }

        echo json_encode([
            'charge'      => number_format((float)$order['charge'], 4, '.', ''),
            'start_count' => (string)$order['start_count'],
            'status'      => ucfirst(str_replace('_', ' ', $order['status'])),
            'remains'     => (string)$order['remains'],
            'currency'    => get_setting('currency', 'USD'),
        ]);
        exit;

    default:
        json_err('Invalid or unsupported action.', 400);
}
