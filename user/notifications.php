<?php
/**
 * ApexSMM User - Notifications Center
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
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Notification Center</h1>
            <p class="text-xs text-slate-400 mt-1">Real-time alerts regarding your orders, deposits, and system notices.</p>
        </div>
        <?php if (!empty($notifications)): ?>
            <form action="/user/notifications.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="mark_all">
                <button type="submit" class="px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">
                    Mark All as Read
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (!empty($notifications)): ?>
        <div class="space-y-3">
            <?php foreach ($notifications as $n): 
                $isRead = (bool)$n['is_read'];
            ?>
                <div class="p-5 rounded-2xl border <?= $isRead ? 'bg-slate-900/60 border-slate-800/80 text-slate-300' : 'bg-blue-950/20 border-blue-500/40 text-white' ?> backdrop-blur-md flex items-start space-x-4 transition-all">
                    <div class="w-8 h-8 rounded-xl shrink-0 flex items-center justify-center <?= $isRead ? 'bg-slate-800 text-slate-500' : 'bg-blue-600 text-white shadow-md shadow-blue-500/30' ?>">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </div>
                    <div class="flex-grow space-y-1">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-bold text-white"><?= e($n['title']) ?></h4>
                            <span class="text-[11px] text-slate-500 font-mono"><?= time_ago($n['created_at']) ?></span>
                        </div>
                        <p class="text-xs text-slate-400 leading-relaxed"><?= e($n['message']) ?></p>
                        <?php if (!empty($n['link'])): ?>
                            <div class="pt-2">
                                <a href="<?= e($n['link']) ?>" class="text-xs text-blue-400 hover:text-blue-300 font-semibold">
                                    View Link &rarr;
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-16 text-center">
            <div class="w-14 h-14 rounded-2xl bg-slate-800/80 border border-slate-700/60 flex items-center justify-center mx-auto mb-4 text-slate-400">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
            </div>
            <h3 class="text-base font-bold text-white mb-1">No notifications found</h3>
            <p class="text-xs text-slate-400 max-w-xs mx-auto">You're completely caught up. Updates on orders and payments will appear here.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
