<?php
/**
 * ApexSMM User - Ticket Conversation Thread
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
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-400 mb-1">
                <a href="/user/tickets.php" class="hover:text-white">&larr; Back to Tickets</a>
            </div>
            <h1 class="text-2xl font-bold text-white tracking-tight"><?= e($ticket['subject']) ?></h1>
            <div class="flex items-center space-x-3 text-xs text-slate-400 mt-1">
                <span>Category: <strong class="text-slate-300 uppercase"><?= e($ticket['category']) ?></strong></span>
                <span>&bull;</span>
                <span>Priority: <strong class="text-slate-300 uppercase"><?= e($ticket['priority']) ?></strong></span>
            </div>
        </div>
        <div class="flex items-center space-x-3">
            <?= status_badge($ticket['status']) ?>
            <?php if ($ticket['status'] !== 'closed'): ?>
                <form action="/user/ticket-view.php?id=<?= (int)$ticket['id'] ?>" method="POST" onsubmit="return confirm('Mark this ticket as closed?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="close">
                    <button type="submit" class="px-3 py-1 rounded-lg bg-slate-800 text-slate-400 hover:text-white text-xs border border-slate-700">
                        Close Ticket
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <!-- Message Timeline -->
    <div class="space-y-4">
        <?php foreach ($messages as $msg): 
            $isAdminMsg = (bool)$msg['is_admin'];
        ?>
            <div class="p-6 rounded-3xl border <?= $isAdminMsg ? 'bg-blue-950/20 border-blue-500/30' : 'bg-slate-900/80 border-slate-800' ?> backdrop-blur-xl space-y-3">
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center space-x-2">
                        <div class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-[10px] <?= $isAdminMsg ? 'bg-blue-600 text-white' : 'bg-slate-700 text-slate-300' ?>">
                            <?= $isAdminMsg ? 'S' : substr($msg['username'], 0, 1) ?>
                        </div>
                        <span class="font-bold <?= $isAdminMsg ? 'text-blue-400' : 'text-white' ?>">
                            <?= $isAdminMsg ? 'Support Team' : e($msg['username']) ?>
                        </span>
                        <?php if ($isAdminMsg): ?>
                            <span class="px-2 py-0.2 rounded bg-blue-500/10 text-blue-400 border border-blue-500/20 text-[10px] font-semibold">Staff</span>
                        <?php endif; ?>
                    </div>
                    <span class="text-slate-500"><?= format_date($msg['created_at']) ?></span>
                </div>

                <div class="text-sm text-slate-300 whitespace-pre-line leading-relaxed">
                    <?= e($msg['message']) ?>
                </div>

                <?php if (!empty($msg['attachment'])): ?>
                    <div class="pt-2 border-t border-slate-800/80">
                        <a href="/storage/uploads/<?= e($msg['attachment']) ?>" target="_blank" class="inline-flex items-center space-x-1.5 text-xs text-blue-400 hover:text-blue-300 font-mono">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                            </svg>
                            <span>Attachment: <?= e($msg['attachment']) ?></span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Reply Form -->
    <?php if ($ticket['status'] !== 'closed'): ?>
        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 backdrop-blur-xl shadow-xl space-y-4">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider">Post Response</h3>
            
            <form action="/user/ticket-view.php?id=<?= (int)$ticket['id'] ?>" method="POST" enctype="multipart/form-data" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="reply">

                <div>
                    <textarea name="message" rows="4" required placeholder="Type your reply here..."
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500"></textarea>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf,.txt,.zip"
                        class="text-xs text-slate-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-slate-300 hover:file:bg-slate-700">
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold text-xs hover:from-blue-500 hover:to-indigo-500 shadow-md shadow-blue-500/25">
                        Send Reply &rarr;
                    </button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div class="p-6 rounded-2xl bg-slate-950 border border-slate-800 text-center text-xs text-slate-500">
            This ticket has been marked as closed. You can open a new ticket from the support desk if you require further assistance.
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
