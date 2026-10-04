<?php
/**
 * ApexSMM Admin - System-wide Transaction Ledger
 * White + Premium Purple Design System
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

AdminAuth::requireAdmin();

$type   = trim($_GET['type'] ?? 'all');
$search = trim($_GET['search'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;

$where = "WHERE 1=1";
$params = [];

if ($type !== 'all' && in_array($type, ['deposit', 'order', 'refund', 'manual_credit', 'manual_debit'])) {
    $where .= " AND t.type = ?";
    $params[] = $type;
}

if ($search !== '') {
    $where .= " AND (u.username LIKE ? OR t.reference_id LIKE ? OR t.description LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$total = (int)Database::fetchValue(
    "SELECT COUNT(*) FROM transactions t JOIN users u ON t.user_id = u.id {$where}",
    $params
);
$offset = ($page - 1) * $perPage;

$transactions = Database::fetchAll(
    "SELECT t.*, u.username, u.email 
     FROM transactions t 
     JOIN users u ON t.user_id = u.id 
     {$where} 
     ORDER BY t.id DESC 
     LIMIT {$perPage} OFFSET {$offset}",
    $params
);

$totalPages = ceil($total / $perPage) ?: 1;

$activeNav = 'transactions';
$pageTitle = 'Transactions Ledger | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-[#18181B] tracking-tight">Financial Transactions Ledger</h1>
            <p class="text-xs text-[#71717A] mt-1"><?= number_format($total) ?> immutable audited ledger entries</p>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="bg-white border border-[#E4E4E7] rounded-2xl p-4 sm:p-5 shadow-sm">
        <form method="GET" action="/admin/transactions.php" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="sm:col-span-2">
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by username, reference, or description..."
                    class="w-full bg-[#FAF5FF]/30 border border-[#E4E4E7] rounded-xl px-3.5 py-2.5 text-xs text-[#18181B] focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20 focus:outline-none transition-all placeholder:text-[#A1A1AA]">
            </div>
            <div>
                <select name="type" class="w-full bg-[#FAF5FF]/30 border border-[#E4E4E7] rounded-xl px-3 py-2.5 text-xs text-[#18181B] focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20 focus:outline-none transition-all">
                    <option value="all">All Transaction Types</option>
                    <option value="deposit" <?= $type === 'deposit' ? 'selected' : '' ?>>Deposits</option>
                    <option value="order" <?= $type === 'order' ? 'selected' : '' ?>>Order Debits</option>
                    <option value="refund" <?= $type === 'refund' ? 'selected' : '' ?>>Refunds</option>
                    <option value="manual_credit" <?= $type === 'manual_credit' ? 'selected' : '' ?>>Manual Credits</option>
                    <option value="manual_debit" <?= $type === 'manual_debit' ? 'selected' : '' ?>>Manual Debits</option>
                </select>
            </div>
            <div>
                <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 py-2.5 rounded-xl bg-[#7C3AED] hover:bg-[#6D28D9] text-white font-semibold text-xs transition-all shadow-sm">
                    <?= icon('magnifying-glass', 'w-4 h-4') ?>
                    <span>Filter Records</span>
                </button>
            </div>
        </form>
    </div>

    <?php if (!empty($transactions)): ?>
        <div class="bg-white border border-[#E4E4E7] rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-[#18181B]">
                    <thead class="bg-[#FAF5FF]/50 text-xs uppercase text-[#71717A] border-b border-[#E4E4E7]">
                        <tr>
                            <th class="px-5 py-3.5 w-14 font-semibold">ID</th>
                            <th class="px-5 py-3.5 font-semibold">Client</th>
                            <th class="px-5 py-3.5 font-semibold">Type</th>
                            <th class="px-5 py-3.5 text-right font-semibold">Amount</th>
                            <th class="px-5 py-3.5 text-right font-semibold">Before</th>
                            <th class="px-5 py-3.5 text-right font-semibold">After</th>
                            <th class="px-5 py-3.5 font-semibold">Description / Ref</th>
                            <th class="px-5 py-3.5 text-right font-semibold">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#F4F4F5] text-xs">
                        <?php foreach ($transactions as $t): 
                            $isCredit = in_array($t['type'], ['deposit', 'refund', 'manual_credit']);
                        ?>
                            <tr class="hover:bg-[#FAF5FF]/30 transition-colors">
                                <td class="px-5 py-3.5 font-mono text-[#A1A1AA]">#<?= (int)$t['id'] ?></td>
                                <td class="px-5 py-3.5 font-bold text-[#18181B]">
                                    <a href="/admin/user-view.php?id=<?= (int)$t['user_id'] ?>" class="text-[#7C3AED] hover:underline">
                                        <?= e($t['username']) ?>
                                    </a>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase <?= $isCredit ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200' ?>">
                                        <?= e(str_replace('_', ' ', $t['type'])) ?>
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 font-mono font-bold text-right <?= $isCredit ? 'text-emerald-600' : 'text-red-600' ?>">
                                    <?= $isCredit ? '+' : '-' ?><?= format_currency($t['amount']) ?>
                                </td>
                                <td class="px-5 py-3.5 font-mono text-[#71717A] text-right"><?= format_currency($t['balance_before']) ?></td>
                                <td class="px-5 py-3.5 font-mono text-[#18181B] font-semibold text-right"><?= format_currency($t['balance_after']) ?></td>
                                <td class="px-5 py-3.5 text-[#18181B] max-w-xs truncate">
                                    <?= e($t['description']) ?>
                                    <?php if ($t['reference_id']): ?>
                                        <span class="text-[#71717A] font-mono text-[10px] ml-1 bg-[#FAF5FF] px-1.5 py-0.5 rounded border border-[#E4E4E7]">[<?= e($t['reference_id']) ?>]</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-3.5 text-[#71717A] text-right whitespace-nowrap font-mono"><?= format_date($t['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="flex items-center justify-between text-xs text-[#71717A] pt-2">
                <div>Page <?= $page ?> of <?= $totalPages ?> (Total: <?= number_format($total) ?>)</div>
                <div class="flex items-center gap-2">
                    <?php if ($page > 1): ?>
                        <a href="/admin/transactions.php?page=<?= $page - 1 ?>&type=<?= e($type) ?>&search=<?= urlencode($search) ?>" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-white border border-[#E4E4E7] text-[#18181B] hover:bg-[#FAF5FF] transition-all">
                            <?= icon('arrow-left', 'w-3.5 h-3.5') ?>
                            <span>Previous</span>
                        </a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="/admin/transactions.php?page=<?= $page + 1 ?>&type=<?= e($type) ?>&search=<?= urlencode($search) ?>" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-white border border-[#E4E4E7] text-[#18181B] hover:bg-[#FAF5FF] transition-all">
                            <span>Next</span>
                            <?= icon('arrow-right', 'w-3.5 h-3.5') ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="bg-white border border-[#E4E4E7] rounded-2xl p-16 text-center text-xs text-[#71717A] flex flex-col items-center justify-center gap-2">
            <div class="w-12 h-12 rounded-full bg-[#FAF5FF] flex items-center justify-center text-[#A1A1AA]">
                <?= icon('credit-card', 'w-6 h-6') ?>
            </div>
            <p class="font-medium text-[#18181B]">No transactions found</p>
            <p class="text-[11px]">There are no financial records matching your search or filter.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
