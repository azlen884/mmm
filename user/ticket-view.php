<?php
/**
 * ApexSMM User - Ticket Conversation Thread
 * White + Premium Purple Design System
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../app/Tickets/TicketManager.php';

Auth::requireLogin();
$user = Auth::user();
$ticketId = (int)($_GET['id'] ?? 0);

// IDOR Protection: strictly enforces user ownership (or admin privileges)
$ticket = Permission::enforceTicketAccess($ticketId);

$error = null;

// Handle New Reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reply') {
    require_csrf();
    $message = trim($_POST['message'] ?? '');
    $file    = $_FILES['attachment'] ?? null;

    try {
        TicketManager::addReply($ticketId, $user['id'], $message, false, $file);
        flash_set('success', 'Reply posted successfully.');
        header('Location: /user/ticket-view.php?id=' . $ticketId);
        exit;
    } catch (\Throwable $e) {
        $error = $e->getMessage();
    }
}

// Handle Close Ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'close') {
    require_csrf();
    Database::execute("UPDATE tickets SET status = 'closed', updated_at = NOW() WHERE id = ?", [$ticketId]);
    flash_set('info', 'Ticket has been marked as closed.');
    header('Location: /user/ticket-view.php?id=' . $ticketId);
    exit;
}

// Query all conversation messages
$messages = Database::fetchAll(
    "SELECT tm.*, u.username, u.role 
     FROM ticket_messages tm 
     JOIN users u ON tm.user_id = u.id 
     WHERE tm.ticket_id = ? 
     ORDER BY tm.id ASC",
    [$ticketId]
);

$activeNav = 'tickets';
$pageTitle = 'Ticket #' . $ticket['id'] . ' | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 gsap-fade-in">
        <div>
            <div class="flex items-center space-x-1.5 text-xs text-zinc-500 mb-1">
                <a href="/user/tickets.php" class="inline-flex items-center space-x-1 text-purple-700 hover:text-purple-800 font-medium">
                    <?= icon('chevron-left', 'w-3.5 h-3.5') ?>
                    <span>Back to Tickets</span>
                </a>
            </div>
            <h1 class="text-2xl font-bold text-zinc-900 tracking-tight"><?= e($ticket['subject']) ?></h1>
            <div class="flex items-center space-x-3 text-xs text-zinc-500 mt-1">
                <span>Category: <strong class="text-zinc-700 uppercase font-semibold"><?= e($ticket['category']) ?></strong></span>
                <span>&bull;</span>
                <span>Priority: <strong class="text-zinc-700 uppercase font-semibold"><?= e($ticket['priority']) ?></strong></span>
            </div>
        </div>
        <div class="flex items-center space-x-3">
            <?= status_badge($ticket['status']) ?>
            <?php if ($ticket['status'] !== 'closed'): ?>
                <form action="/user/ticket-view.php?id=<?= (int)$ticket['id'] ?>" method="POST" onsubmit="return confirm('Mark this ticket as closed?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="close">
                    <button type="submit" class="inline-flex items-center space-x-1 px-3 py-1.5 rounded-xl bg-white border border-zinc-200 text-zinc-600 hover:text-zinc-900 hover:bg-zinc-50 text-xs font-semibold shadow-2xs transition-colors">
                        <?= icon('x-mark', 'w-3.5 h-3.5 text-zinc-400') ?>
                        <span>Close Ticket</span>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center space-x-2">
            <?= icon('exclamation-circle', 'w-4 h-4 text-rose-600 shrink-0') ?>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- Message Timeline -->
    <div class="space-y-4 gsap-card">
        <?php foreach ($messages as $msg): 
            $isAdminMsg = (bool)$msg['is_admin'];
        ?>
            <div class="p-6 rounded-2xl border <?= $isAdminMsg ? 'bg-purple-50/40 border-purple-200/80 shadow-xs' : 'bg-white border-zinc-200/80 shadow-xs' ?> space-y-3">
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center space-x-2">
                        <div class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-[10px] <?= $isAdminMsg ? 'bg-purple-600 text-white' : 'bg-zinc-200 text-zinc-700' ?>">
                            <?= $isAdminMsg ? 'S' : substr($msg['username'], 0, 1) ?>
                        </div>
                        <span class="font-bold <?= $isAdminMsg ? 'text-purple-900' : 'text-zinc-900' ?>">
                            <?= $isAdminMsg ? 'Support Team' : e($msg['username']) ?>
                        </span>
                        <?php if ($isAdminMsg): ?>
                            <span class="px-2 py-0.5 rounded bg-purple-100 text-purple-700 border border-purple-200 text-[10px] font-semibold">Staff</span>
                        <?php endif; ?>
                    </div>
                    <span class="text-zinc-400 text-[11px]"><?= format_date($msg['created_at']) ?></span>
                </div>

                <div class="text-xs text-zinc-700 whitespace-pre-line leading-relaxed">
                    <?= e($msg['message']) ?>
                </div>

                <?php if (!empty($msg['attachment'])): ?>
                    <div class="pt-2 border-t border-zinc-100">
                        <a href="/storage/uploads/<?= e($msg['attachment']) ?>" target="_blank" class="inline-flex items-center space-x-1.5 text-xs text-purple-700 hover:text-purple-800 font-mono">
                            <?= icon('arrow-down-tray', 'w-4 h-4') ?>
                            <span>Attachment: <?= e($msg['attachment']) ?></span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Reply Form -->
    <?php if ($ticket['status'] !== 'closed'): ?>
        <div class="bg-white border border-zinc-200/80 rounded-2xl p-6 shadow-xs space-y-4 gsap-card">
            <h3 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Post Response</h3>
            
            <form action="/user/ticket-view.php?id=<?= (int)$ticket['id'] ?>" method="POST" enctype="multipart/form-data" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="reply">

                <div>
                    <textarea name="message" rows="4" required placeholder="Type your reply here..."
                        class="w-full bg-white border border-zinc-200 rounded-xl px-4 py-3 text-xs text-zinc-900 placeholder-zinc-400 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600"></textarea>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf,.txt,.zip"
                        class="text-xs text-zinc-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100">
                    <button type="submit" class="inline-flex items-center space-x-1.5 px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs shadow-xs transition-all">
                        <?= icon('check', 'w-3.5 h-3.5') ?>
                        <span>Send Reply</span>
                    </button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div class="p-6 rounded-2xl bg-zinc-50 border border-zinc-200 text-center text-xs text-zinc-500">
            This ticket has been marked as closed. You can open a new ticket from the support desk if you require further assistance.
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
