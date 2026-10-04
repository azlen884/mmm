<?php
/**
 * ApexSMM Admin - Order Inspector & Status Adjuster
 * White + Premium Purple Design System
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
    <div class="flex items-center justify-between gsap-fade-in">
        <div>
            <div class="text-xs text-zinc-500 mb-1">
                <a href="/admin/orders.php" class="inline-flex items-center space-x-1 text-purple-700 hover:text-purple-800 font-medium">
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

    <?php if ($success): ?>
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center space-x-2">
            <?= icon('check-circle', 'w-4 h-4 text-emerald-600 shrink-0') ?>
            <span><?= e($success) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center space-x-2">
            <?= icon('exclamation-circle', 'w-4 h-4 text-rose-600 shrink-0') ?>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- Overview Card -->
    <div class="bg-white border border-zinc-200/80 rounded-2xl p-6 sm:p-7 shadow-xs space-y-5 gsap-card">
        <div>
            <span class="text-[11px] font-semibold text-zinc-500 uppercase tracking-wider">Service</span>
            <div class="text-base font-bold text-zinc-900 mt-0.5"><?= e($order['service_name'] ?? 'Manual Service') ?></div>
            <div class="text-xs text-zinc-500 mt-1">
                Client: <a href="/admin/user-view.php?id=<?= (int)$order['user_id'] ?>" class="text-purple-700 font-bold hover:underline"><?= e($order['username']) ?></a> (<?= e($order['email']) ?>)
            </div>
        </div>

        <div class="p-4 rounded-xl bg-zinc-50 border border-zinc-200">
            <span class="text-[11px] font-semibold text-zinc-500 uppercase tracking-wider block mb-1">Target URL</span>
            <a href="<?= e($order['link']) ?>" target="_blank" class="text-xs font-mono text-purple-700 hover:text-purple-800 break-all underline">
                <?= e($order['link']) ?>
            </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="p-3.5 rounded-xl bg-zinc-50 border border-zinc-200">
                <span class="text-[10px] font-semibold text-zinc-500 uppercase block">Quantity</span>
                <span class="text-sm font-mono font-bold text-zinc-900 mt-1 block"><?= number_format($order['quantity']) ?></span>
            </div>
            <div class="p-3.5 rounded-xl bg-zinc-50 border border-zinc-200">
                <span class="text-[10px] font-semibold text-zinc-500 uppercase block">User Charge</span>
                <span class="text-sm font-mono font-bold text-purple-700 mt-1 block"><?= format_currency($order['charge']) ?></span>
            </div>
            <div class="p-3.5 rounded-xl bg-zinc-50 border border-zinc-200">
                <span class="text-[10px] font-semibold text-zinc-500 uppercase block">Start Count</span>
                <span class="text-sm font-mono font-bold text-zinc-700 mt-1 block"><?= number_format($order['start_count']) ?></span>
            </div>
            <div class="p-3.5 rounded-xl bg-zinc-50 border border-zinc-200">
                <span class="text-[10px] font-semibold text-zinc-500 uppercase block">Remains</span>
                <span class="text-sm font-mono font-bold text-zinc-700 mt-1 block"><?= number_format($order['remains']) ?></span>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs text-zinc-500 pt-3 border-t border-zinc-100">
            <div>
                <span class="font-medium">Provider:</span>
                <span class="text-zinc-900 ml-1"><?= e($order['provider_name'] ?? 'Manual Processing') ?></span>
                <?php if ($order['provider_order_id']): ?>
                    <span class="font-mono text-purple-700 ml-1 font-semibold">[#<?= e($order['provider_order_id']) ?>]</span>
                <?php endif; ?>
            </div>
            <div>
                <span class="font-medium">Submitted:</span>
                <span class="text-zinc-700 ml-1"><?= format_date($order['created_at']) ?></span>
            </div>
        </div>

        <?php if (!empty($order['provider_response'])): ?>
            <div class="p-3 rounded-xl bg-zinc-50 border border-zinc-200 text-[11px] font-mono text-zinc-600 overflow-x-auto">
                <span class="text-zinc-500 uppercase block mb-1 font-bold">Provider Raw Response:</span>
                <?= e($order['provider_response']) ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Administrative Controls -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 gsap-card">
        
        <!-- Status Adjuster -->
        <div class="bg-white border border-zinc-200/80 rounded-2xl p-6 shadow-xs space-y-4">
            <h3 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Update Order Status</h3>

            <form action="/admin/order-view.php?id=<?= (int)$order['id'] ?>" method="POST" class="space-y-3">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_status">

                <div>
                    <label class="block text-[11px] font-semibold uppercase text-zinc-600 mb-1">Status</label>
                    <select name="status" class="w-full bg-white border border-zinc-200 rounded-xl px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
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
                        <label class="block text-[11px] font-semibold uppercase text-zinc-600 mb-1">Start Count</label>
                        <input type="number" name="start_count" value="<?= (int)$order['start_count'] ?>" class="w-full bg-white border border-zinc-200 rounded-xl px-3 py-2 text-xs text-zinc-900 font-mono focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold uppercase text-zinc-600 mb-1">Remains</label>
                        <input type="number" name="remains" value="<?= (int)$order['remains'] ?>" class="w-full bg-white border border-zinc-200 rounded-xl px-3 py-2 text-xs text-zinc-900 font-mono focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
                    </div>
                </div>

                <button type="submit" class="w-full inline-flex items-center justify-center space-x-1.5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs shadow-xs transition-colors">
                    <?= icon('check', 'w-3.5 h-3.5') ?>
                    <span>Apply Status Update</span>
                </button>
            </form>
        </div>

        <!-- Quick Actions & Refund -->
        <div class="bg-white border border-zinc-200/80 rounded-2xl p-6 shadow-xs space-y-4">
            <h3 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Financial & API Actions</h3>

            <div class="space-y-3">
                <form action="/admin/order-view.php?id=<?= (int)$order['id'] ?>" method="POST" onsubmit="return confirm('Refund full order charge to user wallet?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="refund_order">
                    <button type="submit" <?= $order['status'] === 'refunded' ? 'disabled' : '' ?> 
                            class="w-full inline-flex items-center justify-center space-x-1.5 py-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold hover:bg-rose-100 transition-all disabled:opacity-50 shadow-2xs">
                        <?= icon('arrow-path', 'w-3.5 h-3.5 text-rose-600') ?>
                        <span>Refund Full Amount (<?= format_currency($order['charge']) ?>)</span>
                    </button>
                </form>

                <?php if ($order['provider_id']): ?>
                    <form action="/admin/order-view.php?id=<?= (int)$order['id'] ?>" method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="redispatch">
                        <button type="submit" class="w-full inline-flex items-center justify-center space-x-1.5 py-2.5 rounded-xl bg-purple-50 border border-purple-200 text-purple-700 text-xs font-semibold hover:bg-purple-100/80 transition-all shadow-2xs">
                            <?= icon('bolt', 'w-3.5 h-3.5 text-purple-600') ?>
                            <span>Re-send to Provider API</span>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
