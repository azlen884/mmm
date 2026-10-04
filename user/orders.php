<?php
/**
 * ApexSMM User - Order History
 * White + Premium Purple Design System
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
            <h1 class="text-2xl font-bold text-zinc-900 tracking-tight">Order History</h1>
            <p class="text-xs text-zinc-500 mt-1">Real-time status updates and order records for your account.</p>
        </div>
        <a href="/user/new-order.php" class="inline-flex items-center space-x-1.5 px-4 py-2.5 rounded-xl bg-purple-600 text-white text-xs font-semibold hover:bg-purple-700 transition-all shadow-xs shadow-purple-600/25">
            <?= icon('plus', 'w-4 h-4') ?>
            <span>New Order</span>
        </a>
    </div>

    <!-- Status Tabs -->
    <div class="flex items-center space-x-1.5 overflow-x-auto pb-1 scrollbar-none border-b border-purple-100/80">
        <?php foreach ($statusTabs as $k => $label): 
            $isActive = ($status === $k);
        ?>
            <a href="/user/orders.php?status=<?= e($k) ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
               class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors <?= $isActive ? 'bg-purple-600 text-white shadow-xs shadow-purple-600/20' : 'text-zinc-600 hover:text-purple-700 hover:bg-purple-50' ?>">
                <?= e($label) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Search Form -->
    <div class="bg-white border border-purple-100 rounded-2xl p-3.5 shadow-xs">
        <form method="GET" action="/user/orders.php" class="flex items-center gap-2.5">
            <input type="hidden" name="status" value="<?= e($status) ?>">
            <div class="relative flex-grow">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-400">
                    <?= icon('search', 'w-4 h-4') ?>
                </div>
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by Order ID, link, or service name..."
                    class="w-full bg-white border border-zinc-200 rounded-xl pl-9 pr-4 py-2 text-xs text-zinc-900 placeholder-zinc-400 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600 transition-colors">
            </div>
            <button type="submit" class="px-4 py-2 rounded-xl bg-purple-600 text-white font-medium text-xs hover:bg-purple-700 transition-all shadow-xs shadow-purple-600/25 flex items-center space-x-1.5 cursor-pointer">
                <?= icon('search', 'w-3.5 h-3.5') ?>
                <span>Search</span>
            </button>
            <?php if ($search !== ''): ?>
                <a href="/user/orders.php?status=<?= e($status) ?>" class="px-3 py-2 rounded-xl bg-zinc-100 text-zinc-600 hover:bg-zinc-200 text-xs font-medium transition-colors">
                    Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Orders Table -->
    <?php if (!empty($orders)): ?>
        <div class="overflow-x-auto bg-white border border-purple-100 rounded-2xl shadow-xs">
            <table class="w-full text-left text-sm text-zinc-600">
                <thead class="bg-zinc-50/70 text-xs uppercase font-bold text-zinc-400 border-b border-zinc-100">
                    <tr>
                        <th class="px-4 py-3.5 w-16">ID</th>
                        <th class="px-4 py-3.5">Service</th>
                        <th class="px-4 py-3.5">Target Link</th>
                        <th class="px-4 py-3.5 text-center">Quantity</th>
                        <th class="px-4 py-3.5 text-right">Charge</th>
                        <th class="px-4 py-3.5 text-center">Start / Remains</th>
                        <th class="px-4 py-3.5 text-center">Status</th>
                        <th class="px-4 py-3.5">Date</th>
                        <th class="px-4 py-3.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <?php foreach ($orders as $o): ?>
                        <tr class="hover:bg-purple-50/30 transition-colors">
                            <td class="px-4 py-3.5 font-mono text-xs font-bold text-zinc-900">#<?= (int)$o['id'] ?></td>
                            <td class="px-4 py-3.5 font-medium text-zinc-900 max-w-xs truncate">
                                <?= e($o['service_name'] ?? 'Direct Service #' . $o['service_id']) ?>
                            </td>
                            <td class="px-4 py-3.5 font-mono text-xs text-zinc-500 max-w-xs truncate">
                                <a href="<?= e($o['link']) ?>" target="_blank" rel="noopener noreferrer" class="hover:text-purple-600 hover:underline">
                                    <?= e($o['link']) ?>
                                </a>
                            </td>
                            <td class="px-4 py-3.5 font-mono text-xs text-center text-zinc-700">
                                <?= number_format($o['quantity']) ?>
                            </td>
                            <td class="px-4 py-3.5 font-mono font-bold text-purple-700 text-right">
                                <?= format_currency((float)$o['charge']) ?>
                            </td>
                            <td class="px-4 py-3.5 text-xs font-mono text-center text-zinc-500">
                                <?= number_format($o['start_count']) ?> / <?= number_format($o['remains']) ?>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <?= status_badge($o['status']) ?>
                            </td>
                            <td class="px-4 py-3.5 text-xs text-zinc-400 whitespace-nowrap">
                                <?= format_date($o['created_at'], 'M d, H:i') ?>
                            </td>
                            <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                <a href="/user/order-details.php?id=<?= (int)$o['id'] ?>" class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-lg bg-purple-50 text-purple-700 border border-purple-200 text-xs font-semibold hover:bg-purple-100 transition-colors">
                                    <?= icon('eye', 'w-3 h-3') ?>
                                    <span>Details</span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="flex items-center justify-between pt-2 text-xs text-zinc-500">
                <div>Showing page <?= $page ?> of <?= $totalPages ?> (<?= number_format($totalCount) ?> orders total)</div>
                <div class="flex items-center space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="/user/orders.php?status=<?= e($status) ?>&page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                           class="inline-flex items-center space-x-1 px-3 py-1.5 rounded-lg bg-white border border-zinc-200 text-zinc-700 hover:bg-purple-50 hover:text-purple-700">
                            <?= icon('chevron-left', 'w-3 h-3') ?>
                            <span>Prev</span>
                        </a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="/user/orders.php?status=<?= e($status) ?>&page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                           class="inline-flex items-center space-x-1 px-3 py-1.5 rounded-lg bg-white border border-zinc-200 text-zinc-700 hover:bg-purple-50 hover:text-purple-700">
                            <span>Next</span>
                            <?= icon('chevron-right', 'w-3 h-3') ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="bg-white border border-purple-100 rounded-3xl p-16 text-center shadow-xs">
            <div class="w-14 h-14 rounded-2xl bg-purple-50 flex items-center justify-center mx-auto mb-3 text-purple-500">
                <?= icon('inbox', 'w-7 h-7') ?>
            </div>
            <h3 class="text-base font-bold text-zinc-900 mb-1">No orders found</h3>
            <p class="text-xs text-zinc-500 max-w-sm mx-auto mb-4">No matching records exist for this filter status.</p>
            <a href="/user/new-order.php" class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl bg-purple-600 text-white text-xs font-semibold hover:bg-purple-700 transition-colors shadow-xs shadow-purple-600/25">
                <?= icon('plus', 'w-3.5 h-3.5') ?>
                <span>Place New Order</span>
            </a>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
