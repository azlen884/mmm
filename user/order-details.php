<?php
/**
 * ApexSMM User - Order Details View
 * White + Premium Purple Design System
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/permissions.php';

Auth::requireLogin();
$user = Auth::user();
$orderId = (int)($_GET['id'] ?? 0);

// IDOR Protection: strictly enforces that only the order owner (or admin) can view
$order = Permission::enforceOrderAccess($orderId);

$activeNav = 'orders';
$pageTitle = 'Order #' . $order['id'] . ' Details | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center space-x-1.5 text-xs text-zinc-500 mb-1">
                <a href="/user/orders.php" class="hover:text-purple-600 inline-flex items-center space-x-1">
                    <?= icon('chevron-left', 'w-3.5 h-3.5') ?>
                    <span>Back to Orders</span>
                </a>
            </div>
            <h1 class="text-2xl font-bold text-zinc-900 tracking-tight">Order #<?= (int)$order['id'] ?></h1>
        </div>
        <div>
            <?= status_badge($order['status']) ?>
        </div>
    </div>

    <!-- Details Card -->
    <div class="bg-white border border-purple-100 rounded-3xl p-6 sm:p-8 shadow-xs space-y-6">
        
        <div>
            <span class="text-[11px] font-bold text-zinc-400 uppercase tracking-wider">Service Name</span>
            <div class="text-lg font-bold text-zinc-900 mt-0.5"><?= e($order['service_name']) ?></div>
            <div class="text-xs text-zinc-500 mt-0.5">Category: <?= e($order['category_name'] ?? 'General') ?></div>
        </div>

        <div class="p-4 rounded-2xl bg-purple-50/50 border border-purple-100 space-y-1">
            <span class="text-[11px] font-bold text-zinc-400 uppercase tracking-wider block">Target Destination Link</span>
            <a href="<?= e($order['link']) ?>" target="_blank" rel="noopener noreferrer" 
               class="text-xs sm:text-sm font-mono text-purple-700 hover:text-purple-900 break-all underline block">
                <?= e($order['link']) ?>
            </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
            <div class="p-4 rounded-xl bg-zinc-50 border border-zinc-100">
                <span class="text-[10px] text-zinc-400 uppercase font-semibold block">Quantity</span>
                <span class="text-base font-bold font-mono text-zinc-900 mt-1 block"><?= number_format($order['quantity']) ?></span>
            </div>
            <div class="p-4 rounded-xl bg-zinc-50 border border-zinc-100">
                <span class="text-[10px] text-zinc-400 uppercase font-semibold block">Charge</span>
                <span class="text-base font-bold font-mono text-purple-700 mt-1 block"><?= format_currency((float)$order['charge']) ?></span>
            </div>
            <div class="p-4 rounded-xl bg-zinc-50 border border-zinc-100">
                <span class="text-[10px] text-zinc-400 uppercase font-semibold block">Start Count</span>
                <span class="text-base font-bold font-mono text-zinc-700 mt-1 block"><?= number_format($order['start_count']) ?></span>
            </div>
            <div class="p-4 rounded-xl bg-zinc-50 border border-zinc-100">
                <span class="text-[10px] text-zinc-400 uppercase font-semibold block">Remains</span>
                <span class="text-base font-bold font-mono text-zinc-700 mt-1 block"><?= number_format($order['remains']) ?></span>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs text-zinc-500 pt-2 border-t border-zinc-100">
            <div>
                <span class="font-medium text-zinc-400">Submitted:</span>
                <span class="text-zinc-700 ml-1"><?= format_date($order['created_at']) ?></span>
            </div>
            <div>
                <span class="font-medium text-zinc-400">Last Status Sync:</span>
                <span class="text-zinc-700 ml-1"><?= format_date($order['updated_at']) ?></span>
            </div>
        </div>

        <?php if (!empty($order['error_message'])): ?>
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs">
                <span class="font-bold">Provider Status Note:</span> <?= e($order['error_message']) ?>
            </div>
        <?php endif; ?>

        <!-- Support Shortcut -->
        <div class="pt-4 border-t border-zinc-100 flex items-center justify-between">
            <span class="text-xs text-zinc-500">Need assistance with this order?</span>
            <a href="/user/tickets.php?ref_order=<?= (int)$order['id'] ?>" class="text-xs font-semibold text-purple-600 hover:text-purple-700 inline-flex items-center space-x-1">
                <span>Open Support Ticket</span>
                <?= icon('chevron-right', 'w-3 h-3') ?>
            </a>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
