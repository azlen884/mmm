<?php
/**
 * ApexSMM User - Order History
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/Orders/OrderManager.php';

Auth::requireLogin();
$user = Auth::user();
$userId = (int)$user['id'];

$status = trim($_GET['status'] ?? 'all');
$search = trim($_GET['search'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));

$ordersData = OrderManager::getUserOrders($userId, $status, $search, $page, 15);
$orders     = $ordersData['data'];
$totalPages = $ordersData['total_pages'];
$totalCount = $ordersData['total'];

$statusTabs = [
    'all'         => 'All',
    'pending'     => 'Pending',
    'processing'  => 'Processing',
    'in_progress' => 'In Progress',
    'completed'   => 'Completed',
    'partial'     => 'Partial',
    'cancelled'   => 'Cancelled',
    'refunded'    => 'Refunded',
    'failed'      => 'Failed',
];

$activeNav = 'orders';
$pageTitle = 'Orders History | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Order History</h1>
            <p class="text-xs text-slate-400 mt-1">Real-time status updates and order records for your account.</p>
        </div>
        <a href="/user/new-order.php" class="inline-flex items-center px-4 py-2 rounded-xl bg-blue-600 text-white text-xs font-semibold hover:bg-blue-500 transition-colors shadow-md shadow-blue-500/20">
            + New Order
        </a>
    </div>

    <!-- Status Tabs -->
    <div class="flex items-center space-x-1 overflow-x-auto pb-2 scrollbar-none border-b border-slate-800">
        <?php foreach ($statusTabs as $k => $label): 
            $isActive = ($status === $k);
        ?>
            <a href="/user/orders.php?status=<?= e($k) ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
               class="px-3.5 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors <?= $isActive ? 'bg-blue-600 text-white shadow-md shadow-blue-500/25' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">
                <?= e($label) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Search Form -->
    <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-4 backdrop-blur-md">
        <form method="GET" action="/user/orders.php" class="flex items-center gap-3">
            <input type="hidden" name="status" value="<?= e($status) ?>">
            <div class="flex-grow">
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by Order ID, link, or service name..."
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-blue-500">
            </div>
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-medium text-xs hover:bg-blue-500 transition-colors shadow-md shadow-blue-500/20">
                Search
            </button>
            <?php if ($search !== ''): ?>
                <a href="/user/orders.php?status=<?= e($status) ?>" class="px-4 py-2.5 rounded-xl bg-slate-800 text-slate-400 hover:text-white text-xs transition-colors">
                    Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Orders Table -->
    <?php if (!empty($orders)): ?>
        <div class="overflow-x-auto bg-slate-900/60 border border-slate-800/80 rounded-2xl backdrop-blur-md shadow-xl">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-950/80 text-xs uppercase text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-5 py-4 w-16">ID</th>
                        <th class="px-5 py-4">Service</th>
                        <th class="px-5 py-4">Target Link</th>
                        <th class="px-5 py-4 text-center">Quantity</th>
                        <th class="px-5 py-4 text-right">Charge</th>
                        <th class="px-5 py-4 text-center">Start / Remains</th>
                        <th class="px-5 py-4 text-center">Status</th>
                        <th class="px-5 py-4">Date</th>
                        <th class="px-5 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <?php foreach ($orders as $o): ?>
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <td class="px-5 py-4 font-mono text-xs text-slate-400">#<?= (int)$o['id'] ?></td>
                            <td class="px-5 py-4 font-medium text-white max-w-xs truncate">
                                <?= e($o['service_name'] ?? 'Custom Service #' . $o['service_id']) ?>
                            </td>
                            <td class="px-5 py-4 font-mono text-xs text-slate-400 max-w-xs truncate">
                                <a href="<?= e($o['link']) ?>" target="_blank" rel="noopener noreferrer" class="hover:text-blue-400 underline">
                                    <?= e($o['link']) ?>
                                </a>
                            </td>
                            <td class="px-5 py-4 font-mono text-xs text-center text-slate-300">
                                <?= number_format($o['quantity']) ?>
                            </td>
                            <td class="px-5 py-4 font-mono font-semibold text-emerald-400 text-right">
                                <?= format_currency($o['charge']) ?>
                            </td>
                            <td class="px-5 py-4 text-xs font-mono text-center text-slate-400">
                                <?= number_format($o['start_count']) ?> / <?= number_format($o['remains']) ?>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <?= status_badge($o['status']) ?>
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-400 whitespace-nowrap">
                                <?= format_date($o['created_at'], 'M d, H:i') ?>
                            </td>
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <a href="/user/order-details.php?id=<?= (int)$o['id'] ?>" class="px-3 py-1 rounded-lg bg-blue-600/10 text-blue-400 border border-blue-500/20 text-xs font-semibold hover:bg-blue-600 hover:text-white transition-all">
                                    Details
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="flex items-center justify-between pt-4 text-xs text-slate-400">
                <div>Showing page <?= $page ?> of <?= $totalPages ?> (<?= number_format($totalCount) ?> orders total)</div>
                <div class="flex items-center space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="/user/orders.php?status=<?= e($status) ?>&page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                           class="px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-slate-300 hover:bg-slate-800">
                            &larr; Prev
                        </a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="/user/orders.php?status=<?= e($status) ?>&page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                           class="px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-slate-300 hover:bg-slate-800">
                            Next &rarr;
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-16 text-center">
            <div class="w-14 h-14 rounded-2xl bg-slate-800/80 border border-slate-700/60 flex items-center justify-center mx-auto mb-4 text-slate-400">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            </div>
            <h3 class="text-base font-bold text-white mb-1">No orders found</h3>
            <p class="text-xs text-slate-400 max-w-sm mx-auto mb-4">No matching records exist for this filter.</p>
            <a href="/user/new-order.php" class="inline-flex items-center px-4 py-2 rounded-xl bg-blue-600 text-white text-xs font-semibold hover:bg-blue-500 transition-colors shadow-md shadow-blue-500/20">
                Place New Order &rarr;
            </a>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
