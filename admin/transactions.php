<?php
/**
 * ApexSMM Admin - System-wide Transaction Ledger
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
    <div>
        <h1 class="text-2xl font-bold text-white tracking-tight">System Financial Ledger</h1>
        <p class="text-xs text-slate-400 mt-1"><?= number_format($total) ?> immutable financial records</p>
    </div>

    <!-- Filter Form -->
    <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-4 backdrop-blur-md">
        <form method="GET" action="/admin/transactions.php" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="sm:col-span-2">
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by username, reference, or description..."
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-purple-500">
            </div>
            <div>
                <select name="type" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2.5 text-xs text-white focus:outline-none focus:border-purple-500">
                    <option value="all">All Types</option>
                    <option value="deposit" <?= $type === 'deposit' ? 'selected' : '' ?>>Deposits</option>
                    <option value="order" <?= $type === 'order' ? 'selected' : '' ?>>Order Debits</option>
                    <option value="refund" <?= $type === 'refund' ? 'selected' : '' ?>>Refunds</option>
                    <option value="manual_credit" <?= $type === 'manual_credit' ? 'selected' : '' ?>>Manual Credits</option>
                    <option value="manual_debit" <?= $type === 'manual_debit' ? 'selected' : '' ?>>Manual Debits</option>
                </select>
            </div>
            <div>
                <button type="submit" class="w-full py-2.5 rounded-xl bg-purple-600 text-white font-semibold text-xs hover:bg-purple-500">
                    Filter Records
                </button>
            </div>
        </form>
    </div>

    <?php if (!empty($transactions)): ?>
        <div class="overflow-x-auto bg-slate-900/60 border border-slate-800/80 rounded-2xl backdrop-blur-md shadow-xl">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-950/80 text-xs uppercase text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3 w-14">ID</th>
                        <th class="px-4 py-3">Client</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                        <th class="px-4 py-3 text-right">Before</th>
                        <th class="px-4 py-3 text-right">After</th>
                        <th class="px-4 py-3">Description / Ref</th>
                        <th class="px-4 py-3 text-right">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-xs">
                    <?php foreach ($transactions as $t): 
                        $isCredit = in_array($t['type'], ['deposit', 'refund', 'manual_credit']);
                    ?>
                        <tr class="hover:bg-slate-800/30">
                            <td class="px-4 py-3 font-mono text-slate-500">#<?= (int)$t['id'] ?></td>
                            <td class="px-4 py-3 font-bold text-white">
                                <a href="/admin/user-view.php?id=<?= (int)$t['user_id'] ?>" class="text-purple-400 hover:underline">
                                    <?= e($t['username']) ?>
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $isCredit ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' ?>">
                                    <?= e(str_replace('_', ' ', $t['type'])) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 font-mono font-bold text-right <?= $isCredit ? 'text-emerald-400' : 'text-rose-400' ?>">
                                <?= $isCredit ? '+' : '-' ?><?= format_currency($t['amount']) ?>
                            </td>
                            <td class="px-4 py-3 font-mono text-slate-400 text-right"><?= format_currency($t['balance_before']) ?></td>
                            <td class="px-4 py-3 font-mono text-white text-right"><?= format_currency($t['balance_after']) ?></td>
                            <td class="px-4 py-3 text-slate-300 max-w-xs truncate">
                                <?= e($t['description']) ?>
                                <?php if ($t['reference_id']): ?>
                                    <span class="text-slate-500 font-mono text-[10px] ml-1">[<?= e($t['reference_id']) ?>]</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-slate-400 text-right whitespace-nowrap"><?= format_date($t['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="flex items-center justify-between text-xs text-slate-400 pt-2">
                <div>Page <?= $page ?> of <?= $totalPages ?></div>
                <div class="flex space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="/admin/transactions.php?page=<?= $page - 1 ?>&type=<?= e($type) ?>&search=<?= urlencode($search) ?>" class="px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-slate-300 hover:bg-slate-800">
                            &larr; Prev
                        </a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="/admin/transactions.php?page=<?= $page + 1 ?>&type=<?= e($type) ?>&search=<?= urlencode($search) ?>" class="px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-slate-300 hover:bg-slate-800">
                            Next &rarr;
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-16 text-center text-xs text-slate-500">
            No transactions found.
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
