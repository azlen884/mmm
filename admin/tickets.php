<?php
/**
 * ApexSMM Admin - Support Tickets Desk
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/Tickets/TicketManager.php';

AdminAuth::requireAdmin();
$admin = AdminAuth::user();

// Handle Staff Reply / Close
$selectedTicketId = (int)($_GET['view'] ?? 0);
$ticketDetail = null;
$messages = [];

if ($selectedTicketId) {
    $ticketDetail = Database::fetchOne(
        "SELECT t.*, u.username, u.email 
         FROM tickets t 
         JOIN users u ON t.user_id = u.id 
         WHERE t.id = ?",
        [$selectedTicketId]
    );

    if ($ticketDetail) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_csrf();
            $action = trim($_POST['action'] ?? '');

            if ($action === 'reply') {
                $replyMsg = trim($_POST['message'] ?? '');
                $file     = $_FILES['attachment'] ?? null;
                if ($replyMsg !== '') {
                    TicketManager::addReply($selectedTicketId, $admin['id'], $replyMsg, true, $file);
                    flash_set('success', 'Staff response dispatched.');
                    header('Location: /admin/tickets.php?view=' . $selectedTicketId);
                    exit;
                }
            }

            if ($action === 'close') {
                Database::execute("UPDATE tickets SET status = 'closed', updated_at = NOW() WHERE id = ?", [$selectedTicketId]);
                flash_set('info', 'Ticket closed.');
                header('Location: /admin/tickets.php?view=' . $selectedTicketId);
                exit;
            }

            if ($action === 'reopen') {
                Database::execute("UPDATE tickets SET status = 'open', updated_at = NOW() WHERE id = ?", [$selectedTicketId]);
                flash_set('info', 'Ticket reopened.');
                header('Location: /admin/tickets.php?view=' . $selectedTicketId);
                exit;
            }
        }

        $messages = Database::fetchAll(
            "SELECT tm.*, u.username, u.role 
             FROM ticket_messages tm 
             JOIN users u ON tm.user_id = u.id 
             WHERE tm.ticket_id = ? 
             ORDER BY tm.id ASC",
            [$selectedTicketId]
        );
    }
}

$status = trim($_GET['status'] ?? 'all');
$where = "WHERE 1=1";
$params = [];

if ($status !== 'all' && in_array($status, ['open', 'answered', 'customer_reply', 'closed'])) {
    $where .= " AND t.status = ?";
    $params[] = $status;
}

$tickets = Database::fetchAll(
    "SELECT t.*, u.username 
     FROM tickets t 
     JOIN users u ON t.user_id = u.id 
     {$where} 
     ORDER BY t.updated_at DESC, t.id DESC 
     LIMIT 50",
    $params
);

$activeNav = 'tickets';
$pageTitle = 'Support Tickets | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Support Ticket Management</h1>
            <p class="text-xs text-slate-400 mt-1">Direct inquiries submitted by platform customers.</p>
        </div>
    </div>

    <!-- Status Tabs -->
    <div class="flex items-center space-x-1 pb-2 border-b border-slate-800">
        <?php foreach (['all' => 'All Tickets', 'open' => 'Open', 'customer_reply' => 'Customer Replied', 'answered' => 'Answered', 'closed' => 'Closed'] as $k => $lbl): ?>
            <a href="/admin/tickets.php?status=<?= e($k) ?>" 
               class="px-3.5 py-1.5 rounded-xl text-xs font-semibold <?= $status === $k ? 'bg-purple-600 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">
                <?= e($lbl) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($ticketDetail): ?>
        <!-- Ticket Detail / Conversation View -->
        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 sm:p-8 backdrop-blur-xl shadow-xl space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-4">
                <div>
                    <div class="text-xs text-slate-400 mb-1">
                        <a href="/admin/tickets.php" class="hover:text-white">&larr; Back to Ticket List</a>
                    </div>
                    <h2 class="text-xl font-bold text-white">#<?= (int)$ticketDetail['id'] ?> - <?= e($ticketDetail['subject']) ?></h2>
                    <div class="text-xs text-slate-400 mt-1">
                        Client: <strong class="text-purple-400"><?= e($ticketDetail['username']) ?></strong> (<?= e($ticketDetail['email']) ?>) &bull; Category: <span class="uppercase"><?= e($ticketDetail['category']) ?></span>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <?= status_badge($ticketDetail['status']) ?>
                    <?php if ($ticketDetail['status'] !== 'closed'): ?>
                        <form action="/admin/tickets.php?view=<?= (int)$ticketDetail['id'] ?>" method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="close">
                            <button type="submit" class="px-3 py-1 rounded-lg bg-slate-800 text-slate-300 text-xs hover:bg-slate-700">Close</button>
                        </form>
                    <?php else: ?>
                        <form action="/admin/tickets.php?view=<?= (int)$ticketDetail['id'] ?>" method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="reopen">
                            <button type="submit" class="px-3 py-1 rounded-lg bg-slate-800 text-slate-300 text-xs hover:bg-slate-700">Reopen</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Messages Timeline -->
            <div class="space-y-4">
                <?php foreach ($messages as $msg): 
                    $isAdminMsg = (bool)$msg['is_admin'];
                ?>
                    <div class="p-5 rounded-2xl border <?= $isAdminMsg ? 'bg-purple-950/20 border-purple-500/30' : 'bg-slate-950 border-slate-800' ?> space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold <?= $isAdminMsg ? 'text-purple-400' : 'text-white' ?>">
                                <?= $isAdminMsg ? 'Staff Member' : e($msg['username']) ?>
                            </span>
                            <span class="text-slate-500"><?= format_date($msg['created_at']) ?></span>
                        </div>
                        <div class="text-xs text-slate-300 whitespace-pre-line leading-relaxed"><?= e($msg['message']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Staff Response Box -->
            <form action="/admin/tickets.php?view=<?= (int)$ticketDetail['id'] ?>" method="POST" enctype="multipart/form-data" class="space-y-3 pt-4 border-t border-slate-800">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="reply">

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Post Staff Reply</label>
                    <textarea name="message" rows="4" required placeholder="Type official response..."
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-purple-500"></textarea>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-purple-600 text-white font-semibold text-xs hover:bg-purple-500 shadow-md shadow-purple-500/20">
                        Dispatch Reply &rarr;
                    </button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <!-- Ticket Table View -->
        <?php if (!empty($tickets)): ?>
            <div class="overflow-x-auto bg-slate-900/60 border border-slate-800/80 rounded-2xl backdrop-blur-md shadow-xl">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="bg-slate-950/80 text-xs uppercase text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="px-4 py-3 w-14">ID</th>
                            <th class="px-4 py-3">Client</th>
                            <th class="px-4 py-3">Subject</th>
                            <th class="px-4 py-3">Category</th>
                            <th class="px-4 py-3 text-center">Priority</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3">Last Updated</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-xs">
                        <?php foreach ($tickets as $t): ?>
                            <tr class="hover:bg-slate-800/30">
                                <td class="px-4 py-3 font-mono text-slate-500">#<?= (int)$t['id'] ?></td>
                                <td class="px-4 py-3 font-bold text-white"><?= e($t['username']) ?></td>
                                <td class="px-4 py-3 text-slate-200 max-w-xs truncate"><?= e($t['subject']) ?></td>
                                <td class="px-4 py-3 uppercase text-slate-400"><?= e($t['category']) ?></td>
                                <td class="px-4 py-3 text-center uppercase font-bold text-[10px] text-slate-400"><?= e($t['priority']) ?></td>
                                <td class="px-4 py-3 text-center"><?= status_badge($t['status']) ?></td>
                                <td class="px-4 py-3 text-slate-400 whitespace-nowrap"><?= time_ago($t['updated_at']) ?></td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a href="/admin/tickets.php?view=<?= (int)$t['id'] ?>" class="text-purple-400 hover:text-purple-300 font-semibold">
                                        Answer &rarr;
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-16 text-center text-xs text-slate-500">
                No tickets found for this filter.
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
