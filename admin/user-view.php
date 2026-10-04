<?php
/**
 * ApexSMM Admin - User Inspector & Wallet Adjuster
 * White + Premium Purple Design System
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/Users/UserManager.php';

AdminAuth::requireAdmin();
$admin = AdminAuth::user();
$userId = (int)($_GET['id'] ?? 0);

$targetUser = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
if (!$targetUser) {
    http_response_code(404);
    require __DIR__ . '/../errors/404.php';
    exit;
}

$error = null;
$success = null;

// Handle Balance Adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adjust_balance') {
    require_csrf();
    $type   = trim($_POST['adjustment_type'] ?? 'credit');
    $amount = (float)($_POST['amount'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');

    try {
        $res = UserManager::adminAdjustBalance($userId, $amount, $type, $reason, $admin['id']);
        $success = $res['message'];
        $targetUser = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
    } catch (\Throwable $e) {
        $error = $e->getMessage();
    }
}

// Handle Status & Role Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    require_csrf();
    $status = trim($_POST['status'] ?? 'active');
    $role   = trim($_POST['role'] ?? 'user');

    if (in_array($status, ['active', 'suspended', 'banned']) && in_array($role, ['user', 'admin'])) {
        Database::execute("UPDATE users SET status = ?, role = ? WHERE id = ?", [$status, $role, $userId]);
        audit_log('admin_updated_user', 'user', $userId, ['status' => $status, 'role' => $role], null, $admin['id']);
        $success = "User status and role updated.";
        $targetUser = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
    }
}

// Handle Password Reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    require_csrf();
    $newPass = trim($_POST['new_password'] ?? '');
    if (strlen($newPass) < 8) {
        $error = "Password must be at least 8 characters.";
    } else {
        $hashed = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
        Database::execute("UPDATE users SET password = ? WHERE id = ?", [$hashed, $userId]);
        audit_log('admin_reset_user_password', 'user', $userId, 'Admin reset user password', null, $admin['id']);
        $success = "User password successfully reset.";
    }
}

// User recent orders
$userOrders = Database::fetchAll("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 5", [$userId]);
// User recent transactions
$userTxs = Database::fetchAll("SELECT * FROM transactions WHERE user_id = ? ORDER BY id DESC LIMIT 5", [$userId]);

$activeNav = 'users';
$pageTitle = 'Manage User #' . $targetUser['id'] . ' | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between gsap-fade-in">
        <div>
            <div class="text-xs text-zinc-500 mb-1">
                <a href="/admin/users.php" class="inline-flex items-center space-x-1 text-purple-700 hover:text-purple-800 font-medium">
                    <?= icon('chevron-left', 'w-3.5 h-3.5') ?>
                    <span>Back to Users</span>
                </a>
            </div>
            <h1 class="text-2xl font-bold text-zinc-900 tracking-tight">User: <?= e($targetUser['username']) ?></h1>
        </div>
        <div>
            <?= status_badge($targetUser['status']) ?>
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

    <!-- User Meta Row -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 gsap-card">
        <div class="p-4 rounded-2xl bg-white border border-zinc-200/80 shadow-xs">
            <span class="text-[11px] font-semibold text-zinc-500 uppercase tracking-wider block">Wallet Balance</span>
            <span class="text-xl font-bold font-mono text-purple-700 mt-1 block tabular-nums"><?= format_currency($targetUser['balance']) ?></span>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-zinc-200/80 shadow-xs">
            <span class="text-[11px] font-semibold text-zinc-500 uppercase tracking-wider block">Total Spent</span>
            <span class="text-xl font-bold font-mono text-zinc-900 mt-1 block tabular-nums"><?= format_currency($targetUser['spent']) ?></span>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-zinc-200/80 shadow-xs">
            <span class="text-[11px] font-semibold text-zinc-500 uppercase tracking-wider block">Role</span>
            <span class="text-xs font-bold uppercase text-purple-700 bg-purple-50 border border-purple-200 px-2 py-0.5 rounded-md inline-block mt-1"><?= e($targetUser['role']) ?></span>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-zinc-200/80 shadow-xs">
            <span class="text-[11px] font-semibold text-zinc-500 uppercase tracking-wider block">Joined</span>
            <span class="text-xs font-semibold text-zinc-700 mt-1 block"><?= format_date($targetUser['created_at']) ?></span>
        </div>
    </div>

    <!-- Balance Adjustment Card (Section 38 requirement) -->
    <div class="bg-white border border-zinc-200/80 rounded-2xl p-6 sm:p-7 shadow-xs space-y-4 gsap-card">
        <h3 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Manual Wallet Adjustment (Audited)</h3>
        <p class="text-xs text-zinc-500">All manual additions or deductions create financial ledger records and immutable audit logs.</p>

        <form action="/admin/user-view.php?id=<?= (int)$targetUser['id'] ?>" method="POST" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="adjust_balance">

            <div>
                <label class="block text-[11px] font-semibold uppercase text-zinc-600 mb-1">Adjustment Type</label>
                <select name="adjustment_type" class="w-full bg-white border border-zinc-200 rounded-xl px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
                    <option value="credit">Credit Balance (+)</option>
                    <option value="debit">Debit Balance (-)</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold uppercase text-zinc-600 mb-1">Amount ($)</label>
                <input type="number" step="0.01" min="0.01" name="amount" required placeholder="0.00"
                    class="w-full bg-white border border-zinc-200 rounded-xl px-3 py-2 text-xs text-zinc-900 font-mono focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
            </div>

            <div>
                <label class="block text-[11px] font-semibold uppercase text-zinc-600 mb-1">Administrative Reason</label>
                <input type="text" name="reason" required placeholder="Reason for change..."
                    class="w-full bg-white border border-zinc-200 rounded-xl px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full inline-flex items-center justify-center space-x-1.5 py-2.5 rounded-xl bg-purple-600 text-white font-semibold text-xs hover:bg-purple-700 shadow-xs transition-colors">
                    <?= icon('check', 'w-3.5 h-3.5') ?>
                    <span>Apply Adjustment</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Status & Password Modals Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 gsap-card">
        
        <!-- Status & Role Form -->
        <div class="bg-white border border-zinc-200/80 rounded-2xl p-6 shadow-xs space-y-4">
            <h3 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Account Status & Role</h3>
            <form action="/admin/user-view.php?id=<?= (int)$targetUser['id'] ?>" method="POST" class="space-y-3">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_profile">

                <div>
                    <label class="block text-[11px] font-semibold uppercase text-zinc-600 mb-1">Status</label>
                    <select name="status" class="w-full bg-white border border-zinc-200 rounded-xl px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
                        <option value="active" <?= $targetUser['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="suspended" <?= $targetUser['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                        <option value="banned" <?= $targetUser['status'] === 'banned' ? 'selected' : '' ?>>Banned</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold uppercase text-zinc-600 mb-1">Role</label>
                    <select name="role" class="w-full bg-white border border-zinc-200 rounded-xl px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
                        <option value="user" <?= $targetUser['role'] === 'user' ? 'selected' : '' ?>>Regular User</option>
                        <option value="admin" <?= $targetUser['role'] === 'admin' ? 'selected' : '' ?>>Administrator</option>
                    </select>
                </div>

                <button type="submit" class="w-full py-2 rounded-xl bg-zinc-100 hover:bg-zinc-200 text-zinc-800 text-xs font-semibold transition-colors">
                    Save Status Changes
                </button>
            </form>
        </div>

        <!-- Password Reset -->
        <div class="bg-white border border-zinc-200/80 rounded-2xl p-6 shadow-xs space-y-4">
            <h3 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Override Password</h3>
            <form action="/admin/user-view.php?id=<?= (int)$targetUser['id'] ?>" method="POST" class="space-y-3">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="reset_password">

                <div>
                    <label class="block text-[11px] font-semibold uppercase text-zinc-600 mb-1">New Secure Password</label>
                    <input type="password" name="new_password" required placeholder="Min 8 characters"
                        class="w-full bg-white border border-zinc-200 rounded-xl px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
                </div>

                <div class="pt-6">
                    <button type="submit" class="w-full inline-flex items-center justify-center space-x-1.5 py-2 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 hover:bg-rose-100 text-xs font-semibold transition-colors shadow-2xs">
                        <?= icon('key', 'w-3.5 h-3.5 text-rose-600') ?>
                        <span>Reset Password</span>
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
