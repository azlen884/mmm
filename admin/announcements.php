<?php
/**
 * ApexSMM Admin - Announcements & Broadcasts
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/Notifications/NotificationManager.php';

AdminAuth::requireAdmin();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = trim($_POST['action'] ?? '');

    if ($action === 'broadcast') {
        $title   = trim($_POST['title'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $type    = trim($_POST['type'] ?? 'announcement');
        $link    = trim($_POST['link'] ?? '') ?: null;

        if ($title === '' || $message === '') {
            $error = 'Title and message are required.';
        } else {
            NotificationManager::broadcast($title, $message, $type, $link);
            audit_log('admin_broadcast_announcement', 'notification', null, ['title' => $title]);
            flash_set('success', 'Announcement published to all user dashboards.');
            header('Location: /admin/announcements.php');
            exit;
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            Database::execute("DELETE FROM notifications WHERE id = ?", [$id]);
            flash_set('info', 'Notification deleted.');
            header('Location: /admin/announcements.php');
            exit;
        }
    }
}

$announcements = Database::fetchAll(
    "SELECT * FROM notifications WHERE user_id IS NULL ORDER BY id DESC LIMIT 50"
);

$activeNav = 'announcements';
$pageTitle = 'Announcements | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-white tracking-tight">System Announcements</h1>
        <p class="text-xs text-slate-400 mt-1">Publish global news, service updates, and discount promotions to all client dashboards.</p>
    </div>

    <?php if ($error): ?>
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Broadcast Form -->
        <div class="lg:col-span-1">
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 backdrop-blur-xl shadow-xl space-y-4">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider">New Broadcast</h3>

                <form action="/admin/announcements.php" method="POST" class="space-y-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="broadcast">

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Title</label>
                        <input type="text" name="title" required placeholder="e.g. New YouTube Services Added"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-xs text-white">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Banner Type</label>
                        <select name="type" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                            <option value="announcement">Announcement (General)</option>
                            <option value="info">Information (Blue)</option>
                            <option value="success">Success / Promotion (Green)</option>
                            <option value="warning">Warning / Notice (Amber)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Message Content</label>
                        <textarea name="message" rows="4" required placeholder="Announcement message details..."
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-xs text-white"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Target Action Link (Optional)</label>
                        <input type="text" name="link" placeholder="/user/services.php"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-xs text-white font-mono">
                    </div>

                    <button type="submit" class="w-full py-2.5 rounded-xl bg-purple-600 text-white font-semibold text-xs hover:bg-purple-500 shadow-md shadow-purple-500/20">
                        Broadcast to Users &rarr;
                    </button>
                </form>
            </div>
        </div>

        <!-- Existing Broadcasts -->
        <div class="lg:col-span-2">
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 backdrop-blur-xl shadow-xl space-y-4">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-4">Active System Announcements</h3>

                <?php if (!empty($announcements)): ?>
                    <div class="space-y-3">
                        <?php foreach ($announcements as $a): ?>
                            <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 flex items-start justify-between gap-4">
                                <div class="space-y-1">
                                    <div class="flex items-center space-x-2">
                                        <span class="font-bold text-white text-xs"><?= e($a['title']) ?></span>
                                        <span class="px-2 py-0.2 rounded text-[10px] font-bold uppercase bg-purple-500/10 text-purple-400 border border-purple-500/20">
                                            <?= e($a['type']) ?>
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-400 leading-relaxed"><?= e($a['message']) ?></p>
                                    <span class="text-[10px] text-slate-500 font-mono block"><?= format_date($a['created_at']) ?></span>
                                </div>
                                <form action="/admin/announcements.php" method="POST" onsubmit="return confirm('Delete this announcement?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                                    <button type="submit" class="text-rose-400 hover:text-rose-300 text-xs font-semibold">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="py-12 text-center text-xs text-slate-500">
                        No system announcements currently published.
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
