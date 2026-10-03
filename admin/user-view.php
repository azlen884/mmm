<?php
/**
 * ApexSMM Admin - User Inspector & Wallet Adjuster
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
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-400 mb-1">
                <a href="/admin/users.php" class="hover:text-white">&larr; Back to Users</a>
            </div>
            <h1 class="text-2xl font-bold text-white tracking-tight">User: <?= e($targetUser['username']) ?></h1>
        </div>
        <div>
            <?= status_badge($targetUser['status']) ?>
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

    <!-- User Meta Row -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800">
            <span class="text-[11px] text-slate-400 uppercase tracking-wider block">Wallet Balance</span>
            <span class="text-xl font-bold font-mono text-emerald-400 mt-1 block"><?= format_currency($targetUser['balance']) ?></span>
        </div>
        <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800">
            <span class="text-[11px] text-slate-400 uppercase tracking-wider block">Total Spent</span>
            <span class="text-xl font-bold font-mono text-white mt-1 block"><?= format_currency($targetUser['spent']) ?></span>
        </div>
        <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800">
            <span class="text-[11px] text-slate-400 uppercase tracking-wider block">Role</span>
            <span class="text-sm font-bold uppercase text-purple-400 mt-1 block"><?= e($targetUser['role']) ?></span>
        </div>
        <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800">
            <span class="text-[11px] text-slate-400 uppercase tracking-wider block">Joined</span>
            <span class="text-xs text-slate-300 mt-1 block"><?= format_date($targetUser['created_at']) ?></span>
        </div>
    </div>

    <!-- Balance Adjustment Card (Section 38 requirement) -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 sm:p-8 backdrop-blur-xl shadow-xl space-y-4">
        <h3 class="text-sm font-bold text-white uppercase tracking-wider">Manual Wallet Adjustment (Audited)</h3>
        <p class="text-xs text-slate-400">All manual additions or deductions create financial ledger records and immutable audit logs.</p>

        <form action="/admin/user-view.php?id=<?= (int)$targetUser['id'] ?>" method="POST" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="adjust_balance">

            <div>
                <label class="block text-[11px] font-semibold uppercase text-slate-400 mb-1">Adjustment Type</label>
                <select name="adjustment_type" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                    <option value="credit">Credit Balance (+)</option>
                    <option value="debit">Debit Balance (-)</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold uppercase text-slate-400 mb-1">Amount ($)</label>
                <input type="number" step="0.01" min="0.01" name="amount" required placeholder="0.00"
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
            </div>

            <div>
                <label class="block text-[11px] font-semibold uppercase text-slate-400 mb-1">Administrative Reason</label>
                <input type="text" name="reason" required placeholder="Reason for change..."
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full py-2.5 rounded-xl bg-purple-600 text-white font-semibold text-xs hover:bg-purple-500 shadow-md shadow-purple-500/20">
                    Apply Adjustment
                </button>
            </div>
        </form>
    </div>

    <!-- Status & Password Modals Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        
        <!-- Status & Role Form -->
        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 backdrop-blur-xl space-y-4">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider">Account Status & Role</h3>
            <form action="/admin/user-view.php?id=<?= (int)$targetUser['id'] ?>" method="POST" class="space-y-3">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_profile">

                <div>
                    <label class="block text-[11px] font-semibold uppercase text-slate-400 mb-1">Status</label>
                    <select name="status" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                        <option value="active" <?= $targetUser['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="suspended" <?= $targetUser['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                        <option value="banned" <?= $targetUser['status'] === 'banned' ? 'selected' : '' ?>>Banned</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold uppercase text-slate-400 mb-1">Role</label>
                    <select name="role" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                        <option value="user" <?= $targetUser['role'] === 'user' ? 'selected' : '' ?>>Regular User</option>
                        <option value="admin" <?= $targetUser['role'] === 'admin' ? 'selected' : '' ?>>Administrator</option>
                    </select>
                </div>

                <button type="submit" class="w-full py-2 rounded-xl bg-slate-800 text-xs font-semibold text-slate-200 hover:bg-slate-700">
                    Save Status Changes
                </button>
            </form>
        </div>

        <!-- Password Reset -->
        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 backdrop-blur-xl space-y-4">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider">Override Password</h3>
            <form action="/admin/user-view.php?id=<?= (int)$targetUser['id'] ?>" method="POST" class="space-y-3">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="reset_password">

                <div>
                    <label class="block text-[11px] font-semibold uppercase text-slate-400 mb-1">New Secure Password</label>
                    <input type="password" name="new_password" required placeholder="Min 8 characters"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                </div>

                <div class="pt-6">
                    <button type="submit" class="w-full py-2 rounded-xl bg-rose-600/15 border border-rose-500/30 text-rose-400 text-xs font-semibold hover:bg-rose-600 hover:text-white transition-all">
                        Reset Password
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
