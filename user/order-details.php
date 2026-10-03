<?php
/**
 * ApexSMM User - Order Details View
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
            <div class="flex items-center space-x-2 text-xs text-slate-400 mb-1">
                <a href="/user/orders.php" class="hover:text-white">&larr; Back to Orders</a>
            </div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Order #<?= (int)$order['id'] ?></h1>
        </div>
        <div>
            <?= status_badge($order['status']) ?>
        </div>
    </div>

    <!-- Details Card -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 sm:p-8 backdrop-blur-xl shadow-xl space-y-6">
        
        <div>
            <span class="text-xs text-slate-400 uppercase tracking-wider">Service Name</span>
            <div class="text-lg font-bold text-white mt-0.5"><?= e($order['service_name']) ?></div>
            <div class="text-xs text-slate-500 mt-0.5">Category: <?= e($order['category_name'] ?? 'General') ?></div>
        </div>

        <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-2">
            <span class="text-xs text-slate-400 uppercase tracking-wider block">Target Destination Link</span>
            <a href="<?= e($order['link']) ?>" target="_blank" rel="noopener noreferrer" 
               class="text-sm font-mono text-blue-400 hover:text-blue-300 break-all underline block">
                <?= e($order['link']) ?>
            </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="p-4 rounded-xl bg-slate-950 border border-slate-800">
                <span class="text-[11px] text-slate-400 uppercase tracking-wider block">Quantity</span>
                <span class="text-base font-bold font-mono text-white mt-1 block"><?= number_format($order['quantity']) ?></span>
            </div>
            <div class="p-4 rounded-xl bg-slate-950 border border-slate-800">
                <span class="text-[11px] text-slate-400 uppercase tracking-wider block">Charge</span>
                <span class="text-base font-bold font-mono text-emerald-400 mt-1 block"><?= format_currency($order['charge']) ?></span>
            </div>
            <div class="p-4 rounded-xl bg-slate-950 border border-slate-800">
                <span class="text-[11px] text-slate-400 uppercase tracking-wider block">Start Count</span>
                <span class="text-base font-bold font-mono text-slate-300 mt-1 block"><?= number_format($order['start_count']) ?></span>
            </div>
            <div class="p-4 rounded-xl bg-slate-950 border border-slate-800">
                <span class="text-[11px] text-slate-400 uppercase tracking-wider block">Remains</span>
                <span class="text-base font-bold font-mono text-slate-300 mt-1 block"><?= number_format($order['remains']) ?></span>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs text-slate-400 pt-2 border-t border-slate-800">
            <div>
                <span class="text-slate-500">Submitted:</span>
                <span class="text-slate-300 ml-1"><?= format_date($order['created_at']) ?></span>
            </div>
            <div>
                <span class="text-slate-500">Last Status Sync:</span>
                <span class="text-slate-300 ml-1"><?= format_date($order['updated_at']) ?></span>
            </div>
        </div>

        <?php if (!empty($order['error_message'])): ?>
            <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs">
                <span class="font-bold">Provider Status Note:</span> <?= e($order['error_message']) ?>
            </div>
        <?php endif; ?>

        <!-- Support Shortcut -->
        <div class="pt-4 border-t border-slate-800 flex items-center justify-between">
            <span class="text-xs text-slate-400">Need assistance with this order?</span>
            <a href="/user/tickets.php?ref_order=<?= (int)$order['id'] ?>" class="text-xs font-semibold text-blue-400 hover:text-blue-300">
                Open Support Ticket &rarr;
            </a>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
