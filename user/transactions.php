<?php
/**
 * ApexSMM User - Wallet Transactions Ledger
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireLogin();
$user = Auth::user();
$userId = (int)$user['id'];

$type = trim($_GET['type'] ?? 'all');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

$where = "WHERE user_id = ?";
$params = [$userId];

if ($type !== 'all' && in_array($type, ['deposit', 'order', 'refund', 'manual_credit', 'manual_debit'])) {
    $where .= " AND type = ?";
    $params[] = $type;
}

$total = (int)Database::fetchValue("SELECT COUNT(*) FROM transactions {$where}", $params);
$offset = ($page - 1) * $perPage;

$transactions = Database::fetchAll(
    "SELECT * FROM transactions {$where} ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}",
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
            <h1 class="text-2xl font-bold text-white tracking-tight">Transaction History</h1>
            <p class="text-xs text-slate-400 mt-1">Immutable ledger of wallet credits, debits, and order charges.</p>
        </div>
        <div class="flex items-center space-x-2">
            <span class="text-xs text-slate-400">Current Balance:</span>
            <span class="px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-800 font-mono font-bold text-emerald-400 text-sm">
                <?= format_currency($user['balance']) ?>
            </span>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="flex items-center space-x-2 overflow-x-auto pb-2 border-b border-slate-800">
        <?php 
        $types = ['all' => 'All Entries', 'deposit' => 'Deposits', 'order' => 'Order Debits', 'refund' => 'Refunds'];
        foreach ($types as $k => $label): 
            $isActive = ($type === $k);
        ?>
            <a href="/user/transactions.php?type=<?= e($k) ?>" 
               class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors <?= $isActive ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">
                <?= e($label) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($transactions)): ?>
        <div class="overflow-x-auto bg-slate-900/60 border border-slate-800/80 rounded-2xl backdrop-blur-md shadow-xl">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-950/80 text-xs uppercase text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-5 py-4 w-16">ID</th>
                        <th class="px-5 py-4">Type</th>
                        <th class="px-5 py-4 text-right">Amount</th>
                        <th class="px-5 py-4 text-right">Balance Before</th>
                        <th class="px-5 py-4 text-right">Balance After</th>
                        <th class="px-5 py-4">Description</th>
                        <th class="px-5 py-4 text-right">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <?php foreach ($transactions as $tx): 
                        $isCredit = in_array($tx['type'], ['deposit', 'refund', 'manual_credit']);
                    ?>
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <td class="px-5 py-4 font-mono text-xs text-slate-400">#<?= (int)$tx['id'] ?></td>
                            <td class="px-5 py-4">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium uppercase tracking-wider <?= $isCredit ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' ?>">
                                    <?= e(str_replace('_', ' ', $tx['type'])) ?>
                                </span>
                            </td>
                            <td class="px-5 py-4 font-mono font-bold text-right <?= $isCredit ? 'text-emerald-400' : 'text-rose-400' ?>">
                                <?= $isCredit ? '+' : '-' ?><?= format_currency($tx['amount']) ?>
                            </td>
                            <td class="px-5 py-4 font-mono text-xs text-slate-400 text-right">
                                <?= format_currency($tx['balance_before']) ?>
                            </td>
                            <td class="px-5 py-4 font-mono text-xs font-semibold text-white text-right">
                                <?= format_currency($tx['balance_after']) ?>
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-300 max-w-sm truncate">
                                <?= e($tx['description']) ?>
                                <?php if (!empty($tx['reference_id'])): ?>
                                    <span class="text-slate-500 ml-1 font-mono">[<?= e($tx['reference_id']) ?>]</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-400 text-right whitespace-nowrap">
                                <?= format_date($tx['created_at']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="flex items-center justify-between text-xs text-slate-400 pt-2">
                <div>Page <?= $page ?> of <?= $totalPages ?> (<?= number_format($total) ?> records)</div>
                <div class="flex space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="/user/transactions.php?type=<?= e($type) ?>&page=<?= $page - 1 ?>" class="px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-slate-300 hover:bg-slate-800">
                            &larr; Prev
                        </a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="/user/transactions.php?type=<?= e($type) ?>&page=<?= $page + 1 ?>" class="px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-slate-300 hover:bg-slate-800">
                            Next &rarr;
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-16 text-center">
            <h3 class="text-base font-bold text-white mb-1">No transactions yet</h3>
            <p class="text-xs text-slate-400">Your financial transaction log is empty.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
