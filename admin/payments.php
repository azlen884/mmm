<?php
/**
 * ApexSMM Admin - Payments & Invoices Manager
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
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Payment Invoices</h1>
            <p class="text-xs text-slate-400 mt-1">Real-time deposit ledger and manual approval queue.</p>
        </div>
        <a href="/admin/payment-settings.php" class="px-4 py-2 rounded-xl bg-purple-600 text-white text-xs font-semibold hover:bg-purple-500 shadow-md shadow-purple-500/20">
            Configure Gateways &rarr;
        </a>
    </div>

    <!-- Status Tabs -->
    <div class="flex items-center space-x-1 pb-2 border-b border-slate-800">
        <?php foreach (['all' => 'All Invoices', 'pending' => 'Pending Verification', 'completed' => 'Completed', 'failed' => 'Failed'] as $k => $label): ?>
            <a href="/admin/payments.php?status=<?= e($k) ?>" 
               class="px-3.5 py-1.5 rounded-xl text-xs font-semibold <?= $status === $k ? 'bg-purple-600 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">
                <?= e($label) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($payments)): ?>
        <div class="overflow-x-auto bg-slate-900/60 border border-slate-800/80 rounded-2xl backdrop-blur-md shadow-xl">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-950/80 text-xs uppercase text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3">Tx ID</th>
                        <th class="px-4 py-3">User</th>
                        <th class="px-4 py-3">Gateway</th>
                        <th class="px-4 py-3 text-right">Credit Amount</th>
                        <th class="px-4 py-3 text-right">Fee</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3">Created</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-xs">
                    <?php foreach ($payments as $p): ?>
                        <tr class="hover:bg-slate-800/30">
                            <td class="px-4 py-3 font-mono text-slate-400"><?= e($p['transaction_id']) ?></td>
                            <td class="px-4 py-3 font-bold text-white"><?= e($p['username']) ?></td>
                            <td class="px-4 py-3 uppercase font-semibold text-slate-300"><?= e($p['gateway']) ?></td>
                            <td class="px-4 py-3 font-mono font-bold text-emerald-400 text-right"><?= format_currency($p['amount']) ?></td>
                            <td class="px-4 py-3 font-mono text-slate-400 text-right"><?= format_currency($p['fee']) ?></td>
                            <td class="px-4 py-3 text-center"><?= status_badge($p['status']) ?></td>
                            <td class="px-4 py-3 text-slate-400 whitespace-nowrap"><?= format_date($p['created_at']) ?></td>
                            <td class="px-4 py-3 text-right whitespace-nowrap space-x-2">
                                <?php if ($p['status'] === 'pending'): ?>
                                    <form action="/admin/payments.php" method="POST" class="inline-block" onsubmit="return confirm('Approve this deposit and credit user wallet?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="tx_id" value="<?= e($p['transaction_id']) ?>">
                                        <button type="submit" class="px-2.5 py-1 rounded bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 font-bold hover:bg-emerald-500 hover:text-white transition-all">
                                            Approve
                                        </button>
                                    </form>
                                    <form action="/admin/payments.php" method="POST" class="inline-block" onsubmit="return confirm('Reject this deposit invoice?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="tx_id" value="<?= e($p['transaction_id']) ?>">
                                        <button type="submit" class="px-2.5 py-1 rounded bg-rose-500/15 border border-rose-500/30 text-rose-400 font-bold hover:bg-rose-500 hover:text-white transition-all">
                                            Reject
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-slate-600">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-16 text-center text-xs text-slate-500">
            No payments found.
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
