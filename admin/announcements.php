<?php
/**
 * ApexSMM Admin - Announcements & Broadcasts
 * White + Premium Purple Design System
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
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-[#18181B] tracking-tight">System Announcements</h1>
            <p class="text-xs text-[#71717A] mt-1">Publish global news, service updates, and discount promotions to all client dashboards.</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-center gap-2">
            <?= icon('exclamation-circle', 'w-4 h-4 text-red-500 flex-shrink-0') ?>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Broadcast Form -->
        <div class="lg:col-span-1">
            <div class="bg-white border border-[#E4E4E7] rounded-2xl p-6 shadow-sm space-y-4">
                <div class="flex items-center gap-2 pb-3 border-b border-[#F4F4F5]">
                    <div class="w-8 h-8 rounded-lg bg-[#F3E8FF] text-[#7C3AED] flex items-center justify-center">
                        <?= icon('megaphone', 'w-4 h-4') ?>
                    </div>
                    <h3 class="text-sm font-bold text-[#18181B]">New Broadcast</h3>
                </div>

                <form action="/admin/announcements.php" method="POST" class="space-y-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="broadcast">

                    <div>
                        <label class="block text-xs font-semibold text-[#18181B] mb-1.5">Announcement Title</label>
                        <input type="text" name="title" required placeholder="e.g. New YouTube Services Added"
                            class="w-full bg-[#FAF5FF]/30 border border-[#E4E4E7] rounded-xl px-3.5 py-2.5 text-xs text-[#18181B] focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20 focus:outline-none transition-all placeholder:text-[#A1A1AA]">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#18181B] mb-1.5">Banner Type</label>
                        <select name="type" class="w-full bg-[#FAF5FF]/30 border border-[#E4E4E7] rounded-xl px-3 py-2.5 text-xs text-[#18181B] focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20 focus:outline-none transition-all">
                            <option value="announcement">Announcement (General Purple)</option>
                            <option value="info">Information (Blue)</option>
                            <option value="success">Success / Promotion (Green)</option>
                            <option value="warning">Warning / Notice (Amber)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#18181B] mb-1.5">Message Content</label>
                        <textarea name="message" rows="4" required placeholder="Write message details for clients..."
                            class="w-full bg-[#FAF5FF]/30 border border-[#E4E4E7] rounded-xl px-3.5 py-2.5 text-xs text-[#18181B] focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20 focus:outline-none transition-all placeholder:text-[#A1A1AA]"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#18181B] mb-1.5">Target Action Link (Optional)</label>
                        <input type="text" name="link" placeholder="/user/services.php"
                            class="w-full bg-[#FAF5FF]/30 border border-[#E4E4E7] rounded-xl px-3.5 py-2.5 text-xs text-[#18181B] font-mono focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20 focus:outline-none transition-all placeholder:text-[#A1A1AA]">
                    </div>

                    <button type="submit" class="w-full py-2.5 rounded-xl bg-[#7C3AED] hover:bg-[#6D28D9] text-white font-semibold text-xs transition-all shadow-sm flex items-center justify-center gap-2">
                        <?= icon('paper-airplane', 'w-4 h-4') ?>
                        <span>Broadcast to Users</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Existing Broadcasts -->
        <div class="lg:col-span-2">
            <div class="bg-white border border-[#E4E4E7] rounded-2xl p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-[#F4F4F5]">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-[#FAF5FF] text-[#7C3AED] flex items-center justify-center">
                            <?= icon('bell', 'w-4 h-4') ?>
                        </div>
                        <h3 class="text-sm font-bold text-[#18181B]">Active System Announcements</h3>
                    </div>
                    <span class="text-xs text-[#71717A]"><?= count($announcements) ?> posted</span>
                </div>

                <?php if (!empty($announcements)): ?>
                    <div class="space-y-3">
                        <?php foreach ($announcements as $a): ?>
                            <div class="p-4 rounded-xl bg-[#FAF5FF]/50 border border-[#E4E4E7] hover:border-[#DDD6FE] transition-all flex items-start justify-between gap-4">
                                <div class="space-y-1.5">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-[#18181B] text-xs"><?= e($a['title']) ?></span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-[#F3E8FF] text-[#7C3AED] border border-[#DDD6FE]">
                                            <?= e($a['type']) ?>
                                        </span>
                                    </div>
                                    <p class="text-xs text-[#71717A] leading-relaxed"><?= e($a['message']) ?></p>
                                    <?php if (!empty($a['link'])): ?>
                                        <div class="text-[11px] text-[#7C3AED] font-medium flex items-center gap-1">
                                            <?= icon('arrow-top-right-on-square', 'w-3 h-3') ?>
                                            <span>Link: <?= e($a['link']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <span class="text-[10px] text-[#A1A1AA] font-mono block"><?= format_date($a['created_at']) ?></span>
                                </div>
                                <form action="/admin/announcements.php" method="POST" onsubmit="return confirm('Delete this announcement?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                                    <button type="submit" class="p-1.5 rounded-lg text-red-500 hover:text-red-700 hover:bg-red-50 transition-all" title="Delete">
                                        <?= icon('trash', 'w-4 h-4') ?>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="py-12 text-center text-xs text-[#71717A] flex flex-col items-center justify-center gap-2">
                        <div class="w-12 h-12 rounded-full bg-[#FAF5FF] flex items-center justify-center text-[#A1A1AA]">
                            <?= icon('bell-slash', 'w-6 h-6') ?>
                        </div>
                        <p class="font-medium text-[#18181B]">No announcements published</p>
                        <p class="text-[11px]">Use the broadcast form on the left to post updates to your clients.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
