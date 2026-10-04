<?php
/**
 * ApexSMM Admin - Payments & Invoices Manager
 * White + Premium Purple Design System
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/Payments/PaymentService.php';

AdminAuth::requireAdmin();
$admin = AdminAuth::user();

// Handle Manual Approve / Reject for Bank Transfer / Invoices
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = trim($_POST['action'] ?? '');
    $txId   = trim($_POST['tx_id'] ?? '');

    if ($action === 'approve' && $txId) {
        try {
            PaymentService::completePayment($txId, ['admin_manual_approved' => $admin['id']]);
            flash_set('success', "Payment {$txId} approved and funds credited.");
        } catch (\Throwable $e) {
            flash_set('error', $e->getMessage());
        }
        header('Location: /admin/payments.php');
        exit;
    }

    if ($action === 'reject' && $txId) {
        Database::execute("UPDATE payments SET status = 'failed', updated_at = NOW() WHERE transaction_id = ?", [$txId]);
        audit_log('admin_rejected_payment', 'payment', $txId, "Payment {$txId} rejected by admin", null, $admin['id']);
        flash_set('info', "Payment {$txId} marked as failed.");
        header('Location: /admin/payments.php');
        exit;
    }
}

$status = trim($_GET['status'] ?? 'all');
$where = "WHERE 1=1";
$params = [];

if ($status !== 'all' && in_array($status, ['pending', 'completed', 'failed', 'cancelled'])) {
    $where .= " AND p.status = ?";
    $params[] = $status;
}

$payments = Database::fetchAll(
    "SELECT p.*, u.username, u.email 
     FROM payments p 
     JOIN users u ON p.user_id = u.id 
     {$where} 
     ORDER BY p.id DESC 
     LIMIT 50",
    $params
);

$activeNav = 'payments';
$pageTitle = 'Payments Invoices | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 gsap-fade-in">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 tracking-tight">Payment Invoices</h1>
            <p class="text-xs text-zinc-500 mt-1">Real-time deposit ledger and manual approval queue.</p>
        </div>
        <a href="/admin/payment-settings.php" class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl bg-purple-600 text-white text-xs font-semibold hover:bg-purple-700 shadow-xs transition-colors">
            <?= icon('wallet', 'w-4 h-4') ?>
            <span>Configure Gateways</span>
        </a>
    </div>

    <!-- Status Tabs -->
    <div class="flex items-center space-x-1 pb-2 border-b border-zinc-200">
        <?php foreach (['all' => 'All Invoices', 'pending' => 'Pending Verification', 'completed' => 'Completed', 'failed' => 'Failed'] as $k => $label): ?>
            <a href="/admin/payments.php?status=<?= e($k) ?>" 
               class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors <?= $status === $k ? 'bg-purple-600 text-white shadow-2xs' : 'text-zinc-600 hover:text-purple-700 hover:bg-purple-50' ?>">
                <?= e($label) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($payments)): ?>
        <div class="overflow-x-auto bg-white border border-zinc-200/80 rounded-2xl shadow-xs gsap-card">
            <table class="w-full text-left text-sm text-zinc-700">
                <thead class="bg-zinc-50/80 text-xs font-semibold uppercase tracking-wider text-zinc-500 border-b border-zinc-200">
                    <tr>
                        <th class="px-4 py-3.5">Tx ID</th>
                        <th class="px-4 py-3.5">User</th>
                        <th class="px-4 py-3.5">Gateway</th>
                        <th class="px-4 py-3.5 text-right">Credit Amount</th>
                        <th class="px-4 py-3.5 text-right">Fee</th>
                        <th class="px-4 py-3.5 text-center">Status</th>
                        <th class="px-4 py-3.5">Created</th>
                        <th class="px-4 py-3.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200/70 text-xs">
                    <?php foreach ($payments as $p): ?>
                        <tr class="hover:bg-purple-50/30 transition-colors">
                            <td class="px-4 py-3.5 font-mono text-zinc-400"><?= e($p['transaction_id']) ?></td>
                            <td class="px-4 py-3.5 font-bold text-zinc-900"><?= e($p['username']) ?></td>
                            <td class="px-4 py-3.5 uppercase font-semibold text-zinc-700"><?= e($p['gateway']) ?></td>
                            <td class="px-4 py-3.5 font-mono font-bold text-purple-700 text-right"><?= format_currency($p['amount']) ?></td>
                            <td class="px-4 py-3.5 font-mono text-zinc-500 text-right"><?= format_currency($p['fee']) ?></td>
                            <td class="px-4 py-3.5 text-center"><?= status_badge($p['status']) ?></td>
                            <td class="px-4 py-3.5 text-zinc-500 whitespace-nowrap"><?= format_date($p['created_at']) ?></td>
                            <td class="px-4 py-3.5 text-right whitespace-nowrap space-x-1.5">
                                <?php if ($p['status'] === 'pending'): ?>
                                    <form action="/admin/payments.php" method="POST" class="inline-block" onsubmit="return confirm('Approve this deposit and credit user wallet?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="tx_id" value="<?= e($p['transaction_id']) ?>">
                                        <button type="submit" class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 font-bold hover:bg-emerald-100 transition-all shadow-2xs">
                                            <?= icon('check', 'w-3 h-3') ?>
                                            <span>Approve</span>
                                        </button>
                                    </form>
                                    <form action="/admin/payments.php" method="POST" class="inline-block" onsubmit="return confirm('Reject this deposit invoice?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="tx_id" value="<?= e($p['transaction_id']) ?>">
                                        <button type="submit" class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 font-bold hover:bg-rose-100 transition-all shadow-2xs">
                                            <?= icon('x-mark', 'w-3 h-3') ?>
                                            <span>Reject</span>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-zinc-400">·</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="bg-white border border-zinc-200/80 rounded-2xl p-16 text-center text-xs text-zinc-500 shadow-xs gsap-card">
            No payments found.
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
