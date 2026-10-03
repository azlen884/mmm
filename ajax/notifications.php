<?php
/**
 * ApexSMM AJAX - Real-time Notifications & Mark Read
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../app/Notifications/NotificationManager.php';

Auth::requireLogin();
$user = Auth::user();
$userId = (int)$user['id'];

$action = trim($_POST['action'] ?? $_GET['action'] ?? '');

if ($action === 'mark_read' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        NotificationManager::markAsRead($id, $userId);
    }
    echo json_encode(['success' => true]);
    exit;
}

$unread = NotificationManager::unreadCount($userId);
$notifs = NotificationManager::getUserNotifications($userId, true, 5);

echo json_encode([
    'success'      => true,
    'unread_count' => $unread,
    'notifications'=> $notifs,
]);
