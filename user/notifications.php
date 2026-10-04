<?php
/**
 * ApexSMM User - Notifications Center
 * White + Premium Purple Design System
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/Notifications/NotificationManager.php';

Auth::requireLogin();
$user = Auth::user();
$userId = (int)$user['id'];

// Mark all as read action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_all') {
    require_csrf();
    NotificationManager::markAllAsRead($userId);
    flash_set('info', 'All notifications marked as read.');
    header('Location: /user/notifications.php');
    exit;
}

$notifications = NotificationManager::getUserNotifications($userId, false, 50);

$activeNav = 'notifications';
$pageTitle = 'Notifications | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between gsap-fade-in">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 tracking-tight">Notification Center</h1>
            <p class="text-xs text-zinc-500 mt-1">Real-time alerts regarding your orders, deposits, and system notices.</p>
        </div>
        <?php if (!empty($notifications)): ?>
            <form action="/user/notifications.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="mark_all">
                <button type="submit" class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-white border border-purple-200 text-xs font-semibold text-purple-700 hover:bg-purple-50 transition-colors shadow-2xs">
                    <?= icon('check', 'w-3.5 h-3.5') ?>
                    <span>Mark All Read</span>
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (!empty($notifications)): ?>
        <div class="space-y-3 gsap-card">
            <?php foreach ($notifications as $n): 
                $isRead = (bool)$n['is_read'];
            ?>
                <div class="p-5 rounded-2xl border <?= $isRead ? 'bg-white border-zinc-200/80 text-zinc-700 shadow-2xs' : 'bg-purple-50/50 border-purple-200 text-zinc-900 shadow-xs' ?> flex items-start space-x-4 transition-all">
                    <div class="w-8 h-8 rounded-xl shrink-0 flex items-center justify-center <?= $isRead ? 'bg-zinc-100 text-zinc-400' : 'bg-purple-600 text-white shadow-xs' ?>">
                        <?= icon('bell', 'w-4 h-4') ?>
                    </div>
                    <div class="flex-grow space-y-1">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-bold text-zinc-900"><?= e($n['title']) ?></h4>
                            <span class="text-[11px] text-zinc-400 font-mono"><?= time_ago($n['created_at']) ?></span>
                        </div>
                        <p class="text-xs text-zinc-600 leading-relaxed"><?= e($n['message']) ?></p>
                        <?php if (!empty($n['link'])): ?>
                            <div class="pt-2">
                                <a href="<?= e($n['link']) ?>" class="inline-flex items-center space-x-1 text-xs text-purple-700 hover:text-purple-800 font-semibold">
                                    <span>View Details</span>
                                    <?= icon('chevron-right', 'w-3 h-3') ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="bg-white border border-zinc-200/80 rounded-2xl p-16 text-center shadow-xs gsap-card">
            <div class="w-14 h-14 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center mx-auto mb-4">
                <?= icon('bell', 'w-7 h-7') ?>
            </div>
            <h3 class="text-base font-bold text-zinc-900 mb-1">No new notifications</h3>
            <p class="text-xs text-zinc-500 max-w-xs mx-auto">You're completely caught up. Updates on orders and payments will appear here.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
