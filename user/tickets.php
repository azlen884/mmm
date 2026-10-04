<?php
/**
 * ApexSMM User - Support Tickets
 * White + Premium Purple Design System
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
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 gsap-fade-in">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 tracking-tight">Support Desk</h1>
            <p class="text-xs text-zinc-500 mt-1">Direct communication with customer assistance for order or payment inquiries.</p>
        </div>
        <button @click="openModal = true" class="inline-flex items-center space-x-1.5 px-4 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold shadow-xs transition-all">
            <?= icon('plus', 'w-4 h-4') ?>
            <span>Open New Ticket</span>
        </button>
    </div>

    <?php if ($error): ?>
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center space-x-2">
            <?= icon('exclamation-circle', 'w-4 h-4 text-rose-600 shrink-0') ?>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- Ticket List Table -->
    <?php if (!empty($tickets)): ?>
        <div class="overflow-x-auto bg-white border border-zinc-200/80 rounded-2xl shadow-xs gsap-card">
            <table class="w-full text-left text-sm text-zinc-700">
                <thead class="bg-zinc-50/80 text-xs font-semibold uppercase tracking-wider text-zinc-500 border-b border-zinc-200">
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
                <tbody class="divide-y divide-zinc-200/70">
                    <?php foreach ($tickets as $t): ?>
                        <tr class="hover:bg-purple-50/30 transition-colors">
                            <td class="px-5 py-4 font-mono text-xs text-zinc-400">#<?= (int)$t['id'] ?></td>
                            <td class="px-5 py-4 max-w-sm truncate">
                                <a href="/user/ticket-view.php?id=<?= (int)$t['id'] ?>" class="font-semibold text-zinc-900 text-xs hover:text-purple-600 transition-colors">
                                    <?= e($t['subject']) ?>
                                </a>
                            </td>
                            <td class="px-5 py-4 text-xs font-medium uppercase text-zinc-500">
                                <?= e($t['category']) ?>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="px-2 py-0.5 rounded text-[11px] font-semibold uppercase 
                                    <?= $t['priority'] === 'high' ? 'bg-rose-50 text-rose-700 border border-rose-200' : ($t['priority'] === 'medium' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-zinc-100 text-zinc-600') ?>">
                                    <?= e($t['priority']) ?>
                                </span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <?= status_badge($t['status']) ?>
                            </td>
                            <td class="px-5 py-4 text-xs text-zinc-500 whitespace-nowrap">
                                <?= time_ago($t['updated_at']) ?>
                            </td>
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <a href="/user/ticket-view.php?id=<?= (int)$t['id'] ?>" class="inline-flex items-center space-x-1 px-3 py-1 rounded-lg bg-purple-50 border border-purple-200 text-purple-700 text-xs font-semibold hover:bg-purple-600 hover:text-white transition-all shadow-2xs">
                                    <?= icon('eye', 'w-3.5 h-3.5') ?>
                                    <span>View Thread</span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="bg-white border border-zinc-200/80 rounded-2xl p-16 text-center shadow-xs gsap-card">
            <div class="w-14 h-14 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center mx-auto mb-4">
                <?= icon('ticket', 'w-7 h-7') ?>
            </div>
            <h3 class="text-base font-bold text-zinc-900 mb-1">No support tickets found</h3>
            <p class="text-xs text-zinc-500 max-w-sm mx-auto mb-4">Have an inquiry regarding an order or payment? Create a ticket to reach our staff.</p>
            <button @click="openModal = true" class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl bg-purple-600 text-white text-xs font-semibold hover:bg-purple-700 transition-colors shadow-xs">
                <?= icon('plus', 'w-4 h-4') ?>
                <span>Create Ticket</span>
            </button>
        </div>
    <?php endif; ?>

    <!-- New Ticket Modal -->
    <div x-show="openModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-zinc-900/40 backdrop-blur-xs" @click="openModal = false"></div>

        <div class="relative bg-white border border-zinc-200 rounded-3xl w-full max-w-lg p-6 sm:p-8 shadow-xl z-10 space-y-6">
            <div class="flex items-center justify-between border-b border-zinc-100 pb-4">
                <h3 class="text-base font-bold text-zinc-900">Open Support Ticket</h3>
                <button @click="openModal = false" class="text-zinc-400 hover:text-zinc-600 p-1 rounded-lg">
                    <?= icon('x-mark', 'w-5 h-5') ?>
                </button>
            </div>

            <form action="/user/tickets.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                <?= csrf_field() ?>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 mb-1.5">Category</label>
                        <select name="category" class="w-full bg-white border border-zinc-200 rounded-xl px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
                            <option value="order" <?= $refOrder ? 'selected' : '' ?>>Order Inquiry</option>
                            <option value="payment">Payment / Deposit</option>
                            <option value="service">Service Request</option>
                            <option value="bug">Report an Issue</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 mb-1.5">Priority</label>
                        <select name="priority" class="w-full bg-white border border-zinc-200 rounded-xl px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 mb-1.5">Subject</label>
                    <input type="text" name="subject" required 
                        value="<?= $refOrder ? 'Assistance regarding Order #' . $refOrder : '' ?>"
                        placeholder="Brief summary of request..."
                        class="w-full bg-white border border-zinc-200 rounded-xl px-3.5 py-2.5 text-xs text-zinc-900 placeholder-zinc-400 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 mb-1.5">Message</label>
                    <textarea name="message" rows="4" required placeholder="Describe your question or issue in detail..."
                        class="w-full bg-white border border-zinc-200 rounded-xl px-3.5 py-2.5 text-xs text-zinc-900 placeholder-zinc-400 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 mb-1.5">Attachment (Optional)</label>
                    <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf,.txt,.zip"
                        class="w-full text-xs text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100">
                    <span class="text-[11px] text-zinc-400 mt-1 block">Supported: jpg, png, pdf, txt, zip (Max: 5MB)</span>
                </div>

                <div class="pt-2 flex justify-end space-x-3">
                    <button type="button" @click="openModal = false" class="px-4 py-2 rounded-xl bg-white border border-zinc-200 text-zinc-600 text-xs font-semibold hover:bg-zinc-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="inline-flex items-center space-x-1.5 px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold shadow-xs transition-colors">
                        <?= icon('check', 'w-3.5 h-3.5') ?>
                        <span>Submit Ticket</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
