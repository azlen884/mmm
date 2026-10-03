<?php
/**
 * ApexSMM Admin - Orders Management
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

AdminAuth::requireAdmin();

$status  = trim($_GET['status'] ?? 'all');
$search  = trim($_GET['search'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

$where = "WHERE 1=1";
$params = [];

if ($status !== 'all' && in_array($status, ['pending', 'processing', 'in_progress', 'completed', 'partial', 'cancelled', 'refunded', 'failed'])) {
    $where .= " AND o.status = ?";
    $params[] = $status;
}

if ($search !== '') {
    $where .= " AND (o.id = ? OR u.username LIKE ? OR o.link LIKE ? OR o.provider_order_id = ?)";
    $params[] = is_numeric($search) ? (int)$search : 0;
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = $search;
}

$total = (int)Database::fetchValue(
    "SELECT COUNT(*) FROM orders o JOIN users u ON o.user_id = u.id {$where}",
    $params
);
$offset = ($page - 1) * $perPage;

$orders = Database::fetchAll(
    "SELECT o.*, u.username, s.name as service_name, p.name as provider_name 
     FROM orders o 
     JOIN users u ON o.user_id = u.id 
     LEFT JOIN services s ON o.service_id = s.id 
     LEFT JOIN providers p ON o.provider_id = p.id 
     {$where} 
     ORDER BY o.id DESC 
     LIMIT {$perPage} OFFSET {$offset}",
    $params
);

$totalPages = ceil($total / $perPage) ?: 1;

$statusTabs = [
    'all'         => 'All',
    'pending'     => 'Pending',
    'processing'  => 'Processing',
    'in_progress' => 'In Progress',
    'completed'   => 'Completed',
    'partial'     => 'Partial',
    'cancelled'   => 'Cancelled',
    'failed'      => 'Failed',
];

$activeNav = 'orders';
$pageTitle = 'Orders Management | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Order Management</h1>
            <p class="text-xs text-slate-400 mt-1"><?= number_format($total) ?> total orders recorded</p>
        </div>
    </div>

    <!-- Status Tabs -->
    <div class="flex items-center space-x-1 overflow-x-auto pb-2 border-b border-slate-800">
        <?php foreach ($statusTabs as $k => $label): 
            $isActive = ($status === $k);
        ?>
            <a href="/admin/orders.php?status=<?= e($k) ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
               class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors <?= $isActive ? 'bg-purple-600 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">
                <?= e($label) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Search Form -->
    <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-4 backdrop-blur-md">
        <form method="GET" action="/admin/orders.php" class="flex gap-3">
            <input type="hidden" name="status" value="<?= e($status) ?>">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by Order ID, username, target link, or provider order ID..."
                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-purple-500">
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-purple-600 text-white font-semibold text-xs hover:bg-purple-500">Search</button>
            <?php if ($search !== ''): ?>
                <a href="/admin/orders.php?status=<?= e($status) ?>" class="px-4 py-2.5 rounded-xl bg-slate-800 text-slate-400 hover:text-white text-xs">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Table -->
    <?php if (!empty($orders)): ?>
        <div class="overflow-x-auto bg-slate-900/60 border border-slate-800/80 rounded-2xl backdrop-blur-md shadow-xl">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-950/80 text-xs uppercase text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3 w-14">ID</th>
                        <th class="px-4 py-3">User</th>
                        <th class="px-4 py-3">Service</th>
                        <th class="px-4 py-3">Link</th>
                        <th class="px-4 py-3 text-center">Qty</th>
                        <th class="px-4 py-3 text-right">Charge</th>
                        <th class="px-4 py-3">Provider</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-xs">
                    <?php foreach ($orders as $o): ?>
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <td class="px-4 py-3 font-mono text-slate-400">#<?= (int)$o['id'] ?></td>
                            <td class="px-4 py-3 font-bold text-white"><?= e($o['username']) ?></td>
                            <td class="px-4 py-3 text-slate-300 max-w-xs truncate"><?= e($o['service_name'] ?? 'Custom') ?></td>
                            <td class="px-4 py-3 font-mono text-slate-400 max-w-xs truncate">
                                <a href="<?= e($o['link']) ?>" target="_blank" class="hover:text-purple-400 underline">
                                    <?= e($o['link']) ?>
                                </a>
                            </td>
                            <td class="px-4 py-3 font-mono text-center text-slate-300"><?= number_format($o['quantity']) ?></td>
                            <td class="px-4 py-3 font-mono font-bold text-emerald-400 text-right"><?= format_currency($o['charge']) ?></td>
                            <td class="px-4 py-3 text-slate-400 whitespace-nowrap">
                                <?= e($o['provider_name'] ?? 'Manual') ?>
                                <?php if ($o['provider_order_id']): ?>
                                    <span class="text-[10px] text-slate-500 font-mono block">Ext: #<?= e($o['provider_order_id']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-center"><?= status_badge($o['status']) ?></td>
                            <td class="px-4 py-3 text-slate-400 whitespace-nowrap"><?= format_date($o['created_at'], 'M d, H:i') ?></td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="/admin/order-view.php?id=<?= (int)$o['id'] ?>" class="px-3 py-1 rounded-lg bg-purple-600/10 text-purple-300 border border-purple-500/20 text-xs font-semibold hover:bg-purple-600 hover:text-white transition-all">
                                    Manage &rarr;
                                </a>
                            </td>
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
                        <a href="/admin/orders.php?status=<?= e($status) ?>&page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" class="px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-slate-300 hover:bg-slate-800">
                            &larr; Prev
                        </a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="/admin/orders.php?status=<?= e($status) ?>&page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" class="px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-slate-300 hover:bg-slate-800">
                            Next &rarr;
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-16 text-center text-xs text-slate-500">
            No orders found.
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
