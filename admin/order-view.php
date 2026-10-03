<?php
/**
 * ApexSMM Admin - Order Inspector & Status Adjuster
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/Wallet/WalletManager.php';
require_once __DIR__ . '/../app/Providers/ProviderService.php';

AdminAuth::requireAdmin();
$admin = AdminAuth::user();
$orderId = (int)($_GET['id'] ?? 0);

$order = Database::fetchOne(
    "SELECT o.*, u.username, u.email, s.name as service_name, p.name as provider_name 
     FROM orders o 
     JOIN users u ON o.user_id = u.id 
     LEFT JOIN services s ON o.service_id = s.id 
     LEFT JOIN providers p ON o.provider_id = p.id 
     WHERE o.id = ?",
    [$orderId]
);

if (!$order) {
    http_response_code(404);
    require __DIR__ . '/../errors/404.php';
    exit;
}

$error = null;
$success = null;

// Handle Status Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    require_csrf();
    $newStatus = trim($_POST['status'] ?? '');
    $startCount= (int)($_POST['start_count'] ?? 0);
    $remains   = (int)($_POST['remains'] ?? 0);

    if (in_array($newStatus, ['pending', 'processing', 'in_progress', 'completed', 'partial', 'cancelled', 'refunded', 'failed'])) {
        Database::execute(
            "UPDATE orders SET status = ?, start_count = ?, remains = ?, updated_at = NOW() WHERE id = ?",
            [$newStatus, $startCount, $remains, $orderId]
        );
        audit_log('admin_updated_order_status', 'order', $orderId, ['status' => $newStatus, 'start' => $startCount, 'remains' => $remains], null, $admin['id']);
        $success = "Order status updated to " . ucfirst(str_replace('_', ' ', $newStatus));
        $order = Database::fetchOne("SELECT o.*, u.username, u.email, s.name as service_name, p.name as provider_name FROM orders o JOIN users u ON o.user_id = u.id LEFT JOIN services s ON o.service_id = s.id LEFT JOIN providers p ON o.provider_id = p.id WHERE o.id = ?", [$orderId]);
    }
}

// Handle Full Refund
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'refund_order') {
    require_csrf();
    if ($order['status'] === 'refunded') {
        $error = "Order has already been refunded.";
    } else {
        try {
            WalletManager::credit(
                (int)$order['user_id'],
                (float)$order['charge'],
                'refund',
                'ORD-' . $orderId,
                "Admin refund for Order #{$orderId}",
                $admin['id']
            );
            Database::execute("UPDATE orders SET status = 'refunded', updated_at = NOW() WHERE id = ?", [$orderId]);
            $success = "Successfully refunded " . format_currency($order['charge']) . " to user wallet.";
            $order = Database::fetchOne("SELECT o.*, u.username, u.email, s.name as service_name, p.name as provider_name FROM orders o JOIN users u ON o.user_id = u.id LEFT JOIN services s ON o.service_id = s.id LEFT JOIN providers p ON o.provider_id = p.id WHERE o.id = ?", [$orderId]);
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

// Handle Dispatch to Provider
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'redispatch') {
    require_csrf();
    $dispatched = ProviderService::submitOrder($orderId);
    if ($dispatched) {
        $success = "Order dispatched to provider successfully.";
    } else {
        $error = "Provider dispatch failed. Check system logs for details.";
    }
    $order = Database::fetchOne("SELECT o.*, u.username, u.email, s.name as service_name, p.name as provider_name FROM orders o JOIN users u ON o.user_id = u.id LEFT JOIN services s ON o.service_id = s.id LEFT JOIN providers p ON o.provider_id = p.id WHERE o.id = ?", [$orderId]);
}

$activeNav = 'orders';
$pageTitle = 'Order #' . $order['id'] . ' | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <div class="text-xs text-slate-400 mb-1">
                <a href="/admin/orders.php" class="hover:text-white">&larr; Back to Orders</a>
            </div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Order #<?= (int)$order['id'] ?></h1>
        </div>
        <div>
            <?= status_badge($order['status']) ?>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs">
            <?= e($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <!-- Overview Card -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 sm:p-8 backdrop-blur-xl shadow-xl space-y-5">
        <div>
            <span class="text-xs text-slate-400 uppercase tracking-wider">Service</span>
            <div class="text-base font-bold text-white mt-0.5"><?= e($order['service_name'] ?? 'Manual Service') ?></div>
            <div class="text-xs text-slate-500 mt-1">
                Client: <a href="/admin/user-view.php?id=<?= (int)$order['user_id'] ?>" class="text-purple-400 font-bold hover:underline"><?= e($order['username']) ?></a> (<?= e($order['email']) ?>)
            </div>
        </div>

        <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800">
            <span class="text-xs text-slate-400 uppercase tracking-wider block mb-1">Target URL</span>
            <a href="<?= e($order['link']) ?>" target="_blank" class="text-xs font-mono text-blue-400 hover:text-blue-300 break-all underline">
                <?= e($order['link']) ?>
            </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
                <span class="text-[10px] text-slate-500 uppercase block">Quantity</span>
                <span class="text-sm font-mono font-bold text-white mt-1 block"><?= number_format($order['quantity']) ?></span>
            </div>
            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
                <span class="text-[10px] text-slate-500 uppercase block">User Charge</span>
                <span class="text-sm font-mono font-bold text-emerald-400 mt-1 block"><?= format_currency($order['charge']) ?></span>
            </div>
            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
                <span class="text-[10px] text-slate-500 uppercase block">Start Count</span>
                <span class="text-sm font-mono font-bold text-slate-300 mt-1 block"><?= number_format($order['start_count']) ?></span>
            </div>
            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
                <span class="text-[10px] text-slate-500 uppercase block">Remains</span>
                <span class="text-sm font-mono font-bold text-slate-300 mt-1 block"><?= number_format($order['remains']) ?></span>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs text-slate-400 pt-2 border-t border-slate-800">
            <div>
                <span class="text-slate-500">Provider:</span>
                <span class="text-slate-200 ml-1"><?= e($order['provider_name'] ?? 'Manual Processing') ?></span>
                <?php if ($order['provider_order_id']): ?>
                    <span class="font-mono text-purple-400 ml-1">[#<?= e($order['provider_order_id']) ?>]</span>
                <?php endif; ?>
            </div>
            <div>
                <span class="text-slate-500">Submitted:</span>
                <span class="text-slate-200 ml-1"><?= format_date($order['created_at']) ?></span>
            </div>
        </div>

        <?php if (!empty($order['provider_response'])): ?>
            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 text-[11px] font-mono text-slate-400 overflow-x-auto">
                <span class="text-slate-500 uppercase block mb-1">Provider Raw Response:</span>
                <?= e($order['provider_response']) ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Administrative Controls -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        
        <!-- Status Adjuster -->
        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 backdrop-blur-xl space-y-4">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider">Update Order Status</h3>

            <form action="/admin/order-view.php?id=<?= (int)$order['id'] ?>" method="POST" class="space-y-3">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_status">

                <div>
                    <label class="block text-[11px] font-semibold uppercase text-slate-400 mb-1">Status</label>
                    <select name="status" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                        <?php 
                        $allStatuses = ['pending', 'processing', 'in_progress', 'completed', 'partial', 'cancelled', 'refunded', 'failed'];
                        foreach ($allStatuses as $st):
                        ?>
                            <option value="<?= $st ?>" <?= $order['status'] === $st ? 'selected' : '' ?>>
                                <?= ucfirst(str_replace('_', ' ', $st)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-semibold uppercase text-slate-400 mb-1">Start Count</label>
                        <input type="number" name="start_count" value="<?= (int)$order['start_count'] ?>" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold uppercase text-slate-400 mb-1">Remains</label>
                        <input type="number" name="remains" value="<?= (int)$order['remains'] ?>" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono">
                    </div>
                </div>

                <button type="submit" class="w-full py-2 rounded-xl bg-purple-600 text-white font-semibold text-xs hover:bg-purple-500 shadow-md shadow-purple-500/20">
                    Apply Status Update
                </button>
            </form>
        </div>

        <!-- Quick Actions & Refund -->
        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 backdrop-blur-xl space-y-4">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider">Financial & API Actions</h3>

            <div class="space-y-3">
                <form action="/admin/order-view.php?id=<?= (int)$order['id'] ?>" method="POST" onsubmit="return confirm('Refund full order charge to user wallet?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="refund_order">
                    <button type="submit" <?= $order['status'] === 'refunded' ? 'disabled' : '' ?> 
                            class="w-full py-2.5 rounded-xl bg-rose-600/15 border border-rose-500/30 text-rose-400 text-xs font-semibold hover:bg-rose-600 hover:text-white transition-all disabled:opacity-50">
                        Refund Full Amount (<?= format_currency($order['charge']) ?>)
                    </button>
                </form>

                <?php if ($order['provider_id']): ?>
                    <form action="/admin/order-view.php?id=<?= (int)$order['id'] ?>" method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="redispatch">
                        <button type="submit" class="w-full py-2.5 rounded-xl bg-blue-600/15 border border-blue-500/30 text-blue-400 text-xs font-semibold hover:bg-blue-600 hover:text-white transition-all">
                            Re-send to Provider API
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
