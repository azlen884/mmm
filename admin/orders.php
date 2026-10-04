<?php
/**
 * ApexSMM Admin - Orders Management
 * White + Premium Purple Design System
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
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 gsap-fade-in">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 tracking-tight">Order Management</h1>
            <p class="text-xs text-zinc-500 mt-1"><?= number_format($total) ?> total orders recorded</p>
        </div>
    </div>

    <!-- Status Tabs -->
    <div class="flex items-center space-x-1 overflow-x-auto pb-2 border-b border-zinc-200">
        <?php foreach ($statusTabs as $k => $label): 
            $isActive = ($status === $k);
        ?>
            <a href="/admin/orders.php?status=<?= e($k) ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
               class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors <?= $isActive ? 'bg-purple-600 text-white shadow-2xs' : 'text-zinc-600 hover:text-purple-700 hover:bg-purple-50' ?>">
                <?= e($label) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Search Form -->
    <div class="bg-white border border-zinc-200/80 rounded-2xl p-4 shadow-xs gsap-card">
        <form method="GET" action="/admin/orders.php" class="flex gap-3">
            <input type="hidden" name="status" value="<?= e($status) ?>">
            <div class="relative flex-grow">
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by Order ID, username, target link, or provider order ID..."
                    class="w-full bg-white border border-zinc-200 rounded-xl px-4 py-2 text-xs text-zinc-900 placeholder-zinc-400 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
            </div>
            <button type="submit" class="inline-flex items-center space-x-1.5 px-5 py-2 rounded-xl bg-purple-600 text-white font-semibold text-xs hover:bg-purple-700 transition-colors shadow-xs">
                <?= icon('search', 'w-3.5 h-3.5') ?>
                <span>Search</span>
            </button>
            <?php if ($search !== ''): ?>
                <a href="/admin/orders.php?status=<?= e($status) ?>" class="inline-flex items-center space-x-1 px-4 py-2 rounded-xl bg-white border border-zinc-200 text-zinc-600 hover:text-zinc-900 hover:bg-zinc-50 text-xs font-medium transition-colors">
                    <?= icon('arrow-path', 'w-3.5 h-3.5') ?>
                    <span>Clear</span>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Table -->
    <?php if (!empty($orders)): ?>
        <div class="overflow-x-auto bg-white border border-zinc-200/80 rounded-2xl shadow-xs gsap-card">
            <table class="w-full text-left text-sm text-zinc-700">
                <thead class="bg-zinc-50/80 text-xs font-semibold uppercase tracking-wider text-zinc-500 border-b border-zinc-200">
                    <tr>
                        <th class="px-4 py-3.5 w-14">ID</th>
                        <th class="px-4 py-3.5">User</th>
                        <th class="px-4 py-3.5">Service</th>
                        <th class="px-4 py-3.5">Link</th>
                        <th class="px-4 py-3.5 text-center">Qty</th>
                        <th class="px-4 py-3.5 text-right">Charge</th>
                        <th class="px-4 py-3.5">Provider</th>
                        <th class="px-4 py-3.5 text-center">Status</th>
                        <th class="px-4 py-3.5">Date</th>
                        <th class="px-4 py-3.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200/70 text-xs">
                    <?php foreach ($orders as $o): ?>
                        <tr class="hover:bg-purple-50/30 transition-colors">
                            <td class="px-4 py-3.5 font-mono text-zinc-400">#<?= (int)$o['id'] ?></td>
                            <td class="px-4 py-3.5 font-bold text-zinc-900"><?= e($o['username']) ?></td>
                            <td class="px-4 py-3.5 text-zinc-600 max-w-xs truncate"><?= e($o['service_name'] ?? 'Custom') ?></td>
                            <td class="px-4 py-3.5 font-mono text-purple-700 max-w-xs truncate">
                                <a href="<?= e($o['link']) ?>" target="_blank" class="hover:underline">
                                    <?= e($o['link']) ?>
                                </a>
                            </td>
                            <td class="px-4 py-3.5 font-mono text-center text-zinc-700"><?= number_format($o['quantity']) ?></td>
                            <td class="px-4 py-3.5 font-mono font-bold text-purple-700 text-right"><?= format_currency($o['charge']) ?></td>
                            <td class="px-4 py-3.5 text-zinc-600 whitespace-nowrap">
                                <span class="font-medium"><?= e($o['provider_name'] ?? 'Manual') ?></span>
                                <?php if ($o['provider_order_id']): ?>
                                    <span class="text-[10px] text-zinc-400 font-mono block">Ext: #<?= e($o['provider_order_id']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3.5 text-center"><?= status_badge($o['status']) ?></td>
                            <td class="px-4 py-3.5 text-zinc-500 whitespace-nowrap"><?= format_date($o['created_at'], 'M d, H:i') ?></td>
                            <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                <a href="/admin/order-view.php?id=<?= (int)$o['id'] ?>" class="inline-flex items-center space-x-1 px-3 py-1 rounded-lg bg-purple-50 border border-purple-200 text-purple-700 text-xs font-semibold hover:bg-purple-600 hover:text-white transition-all shadow-2xs">
                                    <?= icon('pencil', 'w-3 h-3') ?>
                                    <span>Manage</span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="flex items-center justify-between text-xs text-zinc-500 pt-2">
                <div>Page <?= $page ?> of <?= $totalPages ?></div>
                <div class="flex space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="/admin/orders.php?status=<?= e($status) ?>&page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" class="inline-flex items-center space-x-1 px-3 py-1.5 rounded-lg bg-white border border-zinc-200 text-zinc-700 hover:bg-zinc-50 shadow-2xs">
                            <?= icon('chevron-left', 'w-3.5 h-3.5') ?>
                            <span>Prev</span>
                        </a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="/admin/orders.php?status=<?= e($status) ?>&page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" class="inline-flex items-center space-x-1 px-3 py-1.5 rounded-lg bg-white border border-zinc-200 text-zinc-700 hover:bg-zinc-50 shadow-2xs">
                            <span>Next</span>
                            <?= icon('chevron-right', 'w-3.5 h-3.5') ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="bg-white border border-zinc-200/80 rounded-2xl p-16 text-center text-xs text-zinc-500 shadow-xs gsap-card">
            No orders found matching criteria.
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
