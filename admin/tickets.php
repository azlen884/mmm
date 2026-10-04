<?php
/**
 * ApexSMM Admin - Support Tickets Desk
 * White + Premium Purple Design System
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
            <h1 class="text-2xl font-bold text-[#18181B] tracking-tight">Support Tickets Desk</h1>
            <p class="text-xs text-[#71717A] mt-1">Direct inquiries submitted by platform customers.</p>
        </div>
    </div>

    <!-- Status Tabs -->
    <div class="flex items-center gap-2 pb-2 border-b border-[#E4E4E7] overflow-x-auto">
        <?php foreach (['all' => 'All Tickets', 'open' => 'Open', 'customer_reply' => 'Customer Replied', 'answered' => 'Answered', 'closed' => 'Closed'] as $k => $lbl): ?>
            <a href="/admin/tickets.php?status=<?= e($k) ?>" 
               class="px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all <?= $status === $k ? 'bg-[#7C3AED] text-white shadow-sm' : 'text-[#71717A] hover:text-[#18181B] hover:bg-[#FAF5FF]' ?>">
                <?= e($lbl) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($ticketDetail): ?>
        <!-- Ticket Detail / Conversation View -->
        <div class="bg-white border border-[#E4E4E7] rounded-2xl p-6 sm:p-8 shadow-sm space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#F4F4F5] pb-5">
                <div>
                    <a href="/admin/tickets.php" class="inline-flex items-center gap-1 text-xs font-semibold text-[#7C3AED] hover:text-[#6D28D9] mb-2 transition-colors">
                        <?= icon('arrow-left', 'w-3.5 h-3.5') ?>
                        <span>Back to Ticket List</span>
                    </a>
                    <h2 class="text-xl font-bold text-[#18181B]">#<?= (int)$ticketDetail['id'] ?> - <?= e($ticketDetail['subject']) ?></h2>
                    <div class="text-xs text-[#71717A] mt-1">
                        Client: <strong class="text-[#7C3AED]"><?= e($ticketDetail['username']) ?></strong> (<?= e($ticketDetail['email']) ?>) &bull; Category: <span class="uppercase font-semibold text-[#18181B]"><?= e($ticketDetail['category']) ?></span>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <?= status_badge($ticketDetail['status']) ?>
                    <?php if ($ticketDetail['status'] !== 'closed'): ?>
                        <form action="/admin/tickets.php?view=<?= (int)$ticketDetail['id'] ?>" method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="close">
                            <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-red-50 text-red-600 text-xs font-semibold hover:bg-red-100 transition-all">Close Ticket</button>
                        </form>
                    <?php else: ?>
                        <form action="/admin/tickets.php?view=<?= (int)$ticketDetail['id'] ?>" method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="reopen">
                            <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-[#FAF5FF] border border-[#DDD6FE] text-[#7C3AED] text-xs font-semibold hover:bg-[#F3E8FF] transition-all">Reopen Ticket</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Messages Timeline -->
            <div class="space-y-4">
                <?php foreach ($messages as $msg): 
                    $isAdminMsg = (bool)$msg['is_admin'];
                ?>
                    <div class="p-5 rounded-2xl border <?= $isAdminMsg ? 'bg-[#FAF5FF]/70 border-[#DDD6FE]' : 'bg-[#F8FAFC] border-[#E4E4E7]' ?> space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold <?= $isAdminMsg ? 'text-[#7C3AED] flex items-center gap-1' : 'text-[#18181B]' ?>">
                                <?php if ($isAdminMsg): ?>
                                    <?= icon('shield-check', 'w-3.5 h-3.5') ?>
                                    Staff Representative
                                <?php else: ?>
                                    <?= e($msg['username']) ?> (Client)
                                <?php endif; ?>
                            </span>
                            <span class="text-[#71717A] text-[11px] font-mono"><?= format_date($msg['created_at']) ?></span>
                        </div>
                        <div class="text-xs text-[#18181B] whitespace-pre-line leading-relaxed"><?= e($msg['message']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Staff Response Box -->
            <form action="/admin/tickets.php?view=<?= (int)$ticketDetail['id'] ?>" method="POST" enctype="multipart/form-data" class="space-y-4 pt-4 border-t border-[#F4F4F5]">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="reply">

                <div>
                    <label class="block text-xs font-semibold text-[#18181B] mb-1.5">Official Staff Reply</label>
                    <textarea name="message" rows="4" required placeholder="Type official response to customer..."
                        class="w-full bg-[#FAF5FF]/30 border border-[#E4E4E7] rounded-xl px-3.5 py-2.5 text-xs text-[#18181B] focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20 focus:outline-none transition-all placeholder:text-[#A1A1AA]"></textarea>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-[#7C3AED] hover:bg-[#6D28D9] text-white font-semibold text-xs transition-all shadow-sm">
                        <?= icon('paper-airplane', 'w-4 h-4') ?>
                        <span>Dispatch Reply</span>
                    </button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <!-- Ticket Table View -->
        <?php if (!empty($tickets)): ?>
            <div class="bg-white border border-[#E4E4E7] rounded-2xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-[#18181B]">
                        <thead class="bg-[#FAF5FF]/50 text-xs uppercase text-[#71717A] border-b border-[#E4E4E7]">
                            <tr>
                                <th class="px-5 py-3.5 w-14 font-semibold">ID</th>
                                <th class="px-5 py-3.5 font-semibold">Client</th>
                                <th class="px-5 py-3.5 font-semibold">Subject</th>
                                <th class="px-5 py-3.5 font-semibold">Category</th>
                                <th class="px-5 py-3.5 text-center font-semibold">Priority</th>
                                <th class="px-5 py-3.5 text-center font-semibold">Status</th>
                                <th class="px-5 py-3.5 font-semibold">Last Updated</th>
                                <th class="px-5 py-3.5 text-right font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#F4F4F5] text-xs">
                            <?php foreach ($tickets as $t): ?>
                                <tr class="hover:bg-[#FAF5FF]/30 transition-colors">
                                    <td class="px-5 py-3.5 font-mono text-[#A1A1AA]">#<?= (int)$t['id'] ?></td>
                                    <td class="px-5 py-3.5 font-bold text-[#18181B]"><?= e($t['username']) ?></td>
                                    <td class="px-5 py-3.5 text-[#18181B] max-w-xs truncate font-medium"><?= e($t['subject']) ?></td>
                                    <td class="px-5 py-3.5 uppercase text-[#71717A] text-[11px]"><?= e($t['category']) ?></td>
                                    <td class="px-5 py-3.5 text-center uppercase font-bold text-[10px] text-[#71717A]"><?= e($t['priority']) ?></td>
                                    <td class="px-5 py-3.5 text-center"><?= status_badge($t['status']) ?></td>
                                    <td class="px-5 py-3.5 text-[#71717A] whitespace-nowrap"><?= time_ago($t['updated_at']) ?></td>
                                    <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                        <a href="/admin/tickets.php?view=<?= (int)$t['id'] ?>" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-[#FAF5FF] border border-[#DDD6FE] text-[#7C3AED] hover:bg-[#F3E8FF] font-semibold transition-all">
                                            <?= icon('chat-bubble-left-right', 'w-3.5 h-3.5') ?>
                                            <span>Respond</span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-white border border-[#E4E4E7] rounded-2xl p-16 text-center text-xs text-[#71717A] flex flex-col items-center justify-center gap-2">
                <div class="w-12 h-12 rounded-full bg-[#FAF5FF] flex items-center justify-center text-[#A1A1AA]">
                    <?= icon('ticket', 'w-6 h-6') ?>
                </div>
                <p class="font-medium text-[#18181B]">No support tickets found</p>
                <p class="text-[11px]">There are currently no tickets matching this status filter.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
