<?php
/**
 * ApexSMM User - Wallet Transactions Ledger
 * White + Premium Purple Design System
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
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 gsap-fade-in">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 tracking-tight">Transaction History</h1>
            <p class="text-xs text-zinc-500 mt-1">Immutable ledger of wallet credits, debits, and order charges.</p>
        </div>
        <div class="flex items-center space-x-2">
            <span class="text-xs text-zinc-500 font-medium">Current Balance:</span>
            <span class="px-3 py-1.5 rounded-xl bg-purple-50 border border-purple-200 font-mono font-bold text-purple-700 text-sm shadow-2xs">
                <?= format_currency($user['balance']) ?>
            </span>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="flex items-center space-x-1.5 overflow-x-auto pb-2 border-b border-zinc-200">
        <?php 
        $types = ['all' => 'All Entries', 'deposit' => 'Deposits', 'order' => 'Order Debits', 'refund' => 'Refunds'];
        foreach ($types as $k => $label): 
            $isActive = ($type === $k);
        ?>
            <a href="/user/transactions.php?type=<?= e($k) ?>" 
               class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors <?= $isActive ? 'bg-purple-600 text-white shadow-2xs' : 'text-zinc-600 hover:text-purple-700 hover:bg-purple-50' ?>">
                <?= e($label) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($transactions)): ?>
        <div class="overflow-x-auto bg-white border border-zinc-200/80 rounded-2xl shadow-xs gsap-card">
            <table class="w-full text-left text-sm text-zinc-700">
                <thead class="bg-zinc-50/80 text-xs font-semibold uppercase tracking-wider text-zinc-500 border-b border-zinc-200">
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
                <tbody class="divide-y divide-zinc-200/70">
                    <?php foreach ($transactions as $tx): 
                        $isCredit = in_array($tx['type'], ['deposit', 'refund', 'manual_credit']);
                    ?>
                        <tr class="hover:bg-purple-50/30 transition-colors">
                            <td class="px-5 py-4 font-mono text-xs text-zinc-400">#<?= (int)$tx['id'] ?></td>
                            <td class="px-5 py-4">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium uppercase tracking-wider <?= $isCredit ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' ?>">
                                    <?= e(str_replace('_', ' ', $tx['type'])) ?>
                                </span>
                            </td>
                            <td class="px-5 py-4 font-mono font-bold text-right <?= $isCredit ? 'text-emerald-700' : 'text-rose-700' ?>">
                                <?= $isCredit ? '+' : '-' ?><?= format_currency($tx['amount']) ?>
                            </td>
                            <td class="px-5 py-4 font-mono text-xs text-zinc-500 text-right">
                                <?= format_currency($tx['balance_before']) ?>
                            </td>
                            <td class="px-5 py-4 font-mono text-xs font-bold text-zinc-900 text-right">
                                <?= format_currency($tx['balance_after']) ?>
                            </td>
                            <td class="px-5 py-4 text-xs text-zinc-700 max-w-sm truncate">
                                <?= e($tx['description']) ?>
                                <?php if (!empty($tx['reference_id'])): ?>
                                    <span class="text-zinc-400 ml-1 font-mono text-[11px]">[<?= e($tx['reference_id']) ?>]</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-4 text-xs text-zinc-500 text-right whitespace-nowrap">
                                <?= format_date($tx['created_at']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="flex items-center justify-between text-xs text-zinc-500 pt-2">
                <div>Page <?= $page ?> of <?= $totalPages ?> (<?= number_format($total) ?> records)</div>
                <div class="flex space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="/user/transactions.php?type=<?= e($type) ?>&page=<?= $page - 1 ?>" class="inline-flex items-center space-x-1 px-3 py-1.5 rounded-lg bg-white border border-zinc-200 text-zinc-700 hover:bg-zinc-50 shadow-2xs">
                            <?= icon('chevron-left', 'w-3.5 h-3.5') ?>
                            <span>Prev</span>
                        </a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="/user/transactions.php?type=<?= e($type) ?>&page=<?= $page + 1 ?>" class="inline-flex items-center space-x-1 px-3 py-1.5 rounded-lg bg-white border border-zinc-200 text-zinc-700 hover:bg-zinc-50 shadow-2xs">
                            <span>Next</span>
                            <?= icon('chevron-right', 'w-3.5 h-3.5') ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="bg-white border border-zinc-200/80 rounded-2xl p-16 text-center shadow-xs gsap-card">
            <div class="w-14 h-14 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center mx-auto mb-4">
                <?= icon('credit-card', 'w-7 h-7') ?>
            </div>
            <h3 class="text-base font-bold text-zinc-900 mb-1">No transactions yet</h3>
            <p class="text-xs text-zinc-500">Your financial transaction log is empty.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
