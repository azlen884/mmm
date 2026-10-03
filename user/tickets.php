<?php
/**
 * ApexSMM User - Support Tickets
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/Tickets/TicketManager.php';

Auth::requireLogin();
$user = Auth::user();
$userId = (int)$user['id'];

$error = null;
$showNewModal = isset($_GET['new']) || isset($_GET['ref_order']);
$refOrder = isset($_GET['ref_order']) ? (int)$_GET['ref_order'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $subject  = trim($_POST['subject'] ?? '');
    $category = trim($_POST['category'] ?? 'order');
    $priority = trim($_POST['priority'] ?? 'medium');
    $message  = trim($_POST['message'] ?? '');
    $file     = $_FILES['attachment'] ?? null;

    try {
        $ticketId = TicketManager::createTicket($userId, $subject, $category, $priority, $message, $file);
        flash_set('success', "Ticket #{$ticketId} opened. Our team will review shortly.");
        header('Location: /user/ticket-view.php?id=' . $ticketId);
        exit;
    } catch (\Throwable $e) {
        $error = $e->getMessage();
        $showNewModal = true;
    }
}

$tickets = Database::fetchAll(
    "SELECT * FROM tickets WHERE user_id = ? ORDER BY updated_at DESC, id DESC",
    [$userId]
);

$activeNav = 'tickets';
$pageTitle = 'Support Tickets | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6" x-data="{ openModal: <?= $showNewModal ? 'true' : 'false' ?> }">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Support Desk</h1>
            <p class="text-xs text-slate-400 mt-1">Direct communication with customer assistance for order or payment inquiries.</p>
        </div>
        <button @click="openModal = true" class="inline-flex items-center px-4 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-xs font-semibold hover:from-blue-500 hover:to-indigo-500 transition-all shadow-md shadow-blue-500/20">
            + Open New Ticket
        </button>
    </div>

    <?php if ($error): ?>
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <!-- Ticket List Table -->
    <?php if (!empty($tickets)): ?>
        <div class="overflow-x-auto bg-slate-900/60 border border-slate-800/80 rounded-2xl backdrop-blur-md shadow-xl">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-950/80 text-xs uppercase text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-5 py-4 w-16">ID</th>
                        <th class="px-5 py-4">Subject</th>
                        <th class="px-5 py-4">Category</th>
                        <th class="px-5 py-4 text-center">Priority</th>
                        <th class="px-5 py-4 text-center">Status</th>
                        <th class="px-5 py-4">Last Updated</th>
                        <th class="px-5 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <?php foreach ($tickets as $t): ?>
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <td class="px-5 py-4 font-mono text-xs text-slate-400">#<?= (int)$t['id'] ?></td>
                            <td class="px-5 py-4 font-medium text-white max-w-sm truncate">
                                <a href="/user/ticket-view.php?id=<?= (int)$t['id'] ?>" class="hover:text-blue-400">
                                    <?= e($t['subject']) ?>
                                </a>
                            </td>
                            <td class="px-5 py-4 text-xs uppercase text-slate-400">
                                <?= e($t['category']) ?>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="px-2 py-0.5 rounded text-[11px] font-semibold uppercase 
                                    <?= $t['priority'] === 'high' ? 'bg-rose-500/10 text-rose-400' : ($t['priority'] === 'medium' ? 'bg-amber-500/10 text-amber-400' : 'bg-slate-800 text-slate-400') ?>">
                                    <?= e($t['priority']) ?>
                                </span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <?= status_badge($t['status']) ?>
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-400 whitespace-nowrap">
                                <?= time_ago($t['updated_at']) ?>
                            </td>
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <a href="/user/ticket-view.php?id=<?= (int)$t['id'] ?>" class="px-3 py-1 rounded-lg bg-blue-600/10 text-blue-400 border border-blue-500/20 text-xs font-semibold hover:bg-blue-600 hover:text-white transition-all">
                                    View Thread &rarr;
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-16 text-center">
            <div class="w-14 h-14 rounded-2xl bg-slate-800/80 border border-slate-700/60 flex items-center justify-center mx-auto mb-4 text-slate-400">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
            <h3 class="text-base font-bold text-white mb-1">No support tickets found</h3>
            <p class="text-xs text-slate-400 max-w-sm mx-auto mb-4">Have an inquiry regarding an order or payment? Create a ticket to reach our staff.</p>
            <button @click="openModal = true" class="px-4 py-2 rounded-xl bg-blue-600 text-white text-xs font-semibold hover:bg-blue-500 transition-colors">
                Create Ticket
            </button>
        </div>
    <?php endif; ?>

    <!-- New Ticket Modal -->
    <div x-show="openModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" @click="openModal = false"></div>

        <div class="relative bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-lg p-6 sm:p-8 shadow-2xl z-10 space-y-6">
            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                <h3 class="text-lg font-bold text-white">Open Support Ticket</h3>
                <button @click="openModal = false" class="text-slate-400 hover:text-white">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form action="/user/tickets.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                <?= csrf_field() ?>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Category</label>
                        <select name="category" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500">
                            <option value="order" <?= $refOrder ? 'selected' : '' ?>>Order Inquiry</option>
                            <option value="payment">Payment / Deposit</option>
                            <option value="service">Service Request</option>
                            <option value="bug">Report an Issue</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Priority</label>
                        <select name="priority" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Subject</label>
                    <input type="text" name="subject" required 
                        value="<?= $refOrder ? 'Assistance regarding Order #' . $refOrder : '' ?>"
                        placeholder="Brief summary of request..."
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Message</label>
                    <textarea name="message" rows="4" required placeholder="Describe your question or issue in detail..."
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-blue-500"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Attachment (Optional)</label>
                    <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf,.txt,.zip"
                        class="w-full text-xs text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-slate-300 hover:file:bg-slate-700">
                    <span class="text-[11px] text-slate-500 mt-1 block">Supported: jpg, png, pdf, txt, zip (Max: 5MB)</span>
                </div>

                <div class="pt-2 flex justify-end space-x-3">
                    <button type="button" @click="openModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 text-xs font-semibold hover:bg-slate-700">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 text-white text-xs font-semibold hover:bg-blue-500 shadow-md shadow-blue-500/20">
                        Submit Ticket
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
