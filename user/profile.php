<?php
/**
 * ApexSMM User - Profile & Password Settings
 * White + Premium Purple Design System
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/Users/UserManager.php';

Auth::requireLogin();
$user = Auth::user();
$userId = (int)$user['id'];

$error = null;
$success = null;

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'password') {
    require_csrf();
    $current = trim($_POST['current_password'] ?? '');
    $new     = trim($_POST['new_password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');

    if ($new !== $confirm) {
        $error = 'New passwords do not match.';
    } else {
        $result = UserManager::changePassword($userId, $current, $new);
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

// Handle API Key Regenerate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'regenerate_api') {
    require_csrf();
    $newKey = UserManager::regenerateApiKey($userId);
    flash_set('success', 'New API Key generated successfully.');
    header('Location: /user/profile.php');
    exit;
}

$user = Auth::user(); // Reload fresh user data

$activeNav = 'profile';
$pageTitle = 'Account Settings | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-3xl mx-auto space-y-6">
    <div class="gsap-fade-in">
        <h1 class="text-2xl font-bold text-zinc-900 tracking-tight">Account Settings</h1>
        <p class="text-xs text-zinc-500 mt-1">Manage your credentials, security preferences, and developer API key.</p>
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

    <!-- User Profile Details -->
    <div class="bg-white border border-zinc-200/80 rounded-2xl p-6 sm:p-7 shadow-xs space-y-4 gsap-card">
        <h3 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Account Overview</h3>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="p-4 rounded-xl bg-zinc-50 border border-zinc-200/80">
                <span class="text-[11px] font-semibold text-zinc-500 uppercase tracking-wider block">Username</span>
                <span class="text-sm font-bold text-zinc-900 mt-1 block font-mono"><?= e($user['username']) ?></span>
            </div>
            <div class="p-4 rounded-xl bg-zinc-50 border border-zinc-200/80">
                <span class="text-[11px] font-semibold text-zinc-500 uppercase tracking-wider block">Email Address</span>
                <span class="text-sm font-semibold text-zinc-900 mt-1 block"><?= e($user['email']) ?></span>
            </div>
            <div class="p-4 rounded-xl bg-zinc-50 border border-zinc-200/80">
                <span class="text-[11px] font-semibold text-zinc-500 uppercase tracking-wider block">Account Role</span>
                <span class="text-xs font-bold uppercase text-purple-700 bg-purple-50 border border-purple-200 px-2 py-0.5 rounded-md inline-block mt-1"><?= e($user['role']) ?></span>
            </div>
            <div class="p-4 rounded-xl bg-zinc-50 border border-zinc-200/80">
                <span class="text-[11px] font-semibold text-zinc-500 uppercase tracking-wider block">Registered Date</span>
                <span class="text-xs font-semibold text-zinc-700 mt-1 block"><?= format_date($user['created_at']) ?></span>
            </div>
        </div>
    </div>

    <!-- API Key Section -->
    <div class="bg-white border border-zinc-200/80 rounded-2xl p-6 sm:p-7 shadow-xs space-y-4 gsap-card">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Reseller API Key</h3>
                <p class="text-xs text-zinc-500 mt-0.5">Use this token to authenticate programmatic orders via standard SMM protocol.</p>
            </div>
            <a href="/user/api.php" class="inline-flex items-center space-x-1 text-xs font-semibold text-purple-700 hover:text-purple-800">
                <span>API Docs</span>
                <?= icon('chevron-right', 'w-3.5 h-3.5') ?>
            </a>
        </div>

        <div class="p-3.5 rounded-xl bg-zinc-50 border border-zinc-200 flex items-center justify-between gap-4">
            <input type="password" value="<?= e($user['api_key'] ?? 'No API Key Generated') ?>" readonly id="apiKeyField"
                class="bg-transparent border-0 font-mono text-xs text-purple-700 font-semibold w-full focus:outline-none">
            <button type="button" onclick="
                const f = document.getElementById('apiKeyField');
                f.type = f.type === 'password' ? 'text' : 'password';
            " class="text-xs font-medium text-zinc-600 hover:text-purple-700 shrink-0 px-2.5 py-1 bg-white border border-zinc-200 rounded-lg shadow-2xs transition-colors">
                Show/Hide
            </button>
        </div>

        <form action="/user/profile.php" method="POST" onsubmit="return confirm('Regenerating will invalidate your current API key immediately. Continue?');">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="regenerate_api">
            <button type="submit" class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl bg-white border border-purple-200 text-purple-700 text-xs font-semibold hover:bg-purple-50 transition-colors shadow-2xs">
                <?= icon('arrow-path', 'w-3.5 h-3.5') ?>
                <span>Regenerate API Key</span>
            </button>
        </form>
    </div>

    <!-- Password Change Form -->
    <div class="bg-white border border-zinc-200/80 rounded-2xl p-6 sm:p-7 shadow-xs space-y-5 gsap-card">
        <h3 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Change Password</h3>

        <form action="/user/profile.php" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="password">

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 mb-1.5">Current Password</label>
                <input type="password" name="current_password" required placeholder="••••••••••••"
                    class="w-full bg-white border border-zinc-200 rounded-xl px-4 py-2.5 text-xs text-zinc-900 placeholder-zinc-400 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 mb-1.5">New Password</label>
                    <input type="password" name="new_password" required placeholder="At least 8 characters"
                        class="w-full bg-white border border-zinc-200 rounded-xl px-4 py-2.5 text-xs text-zinc-900 placeholder-zinc-400 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 mb-1.5">Confirm New Password</label>
                    <input type="password" name="confirm_password" required placeholder="Repeat new password"
                        class="w-full bg-white border border-zinc-200 rounded-xl px-4 py-2.5 text-xs text-zinc-900 placeholder-zinc-400 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
                </div>
            </div>

            <button type="submit" class="inline-flex items-center space-x-1.5 px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs shadow-xs transition-all">
                <?= icon('check', 'w-3.5 h-3.5') ?>
                <span>Update Password</span>
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
