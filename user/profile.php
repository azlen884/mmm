<?php
/**
 * ApexSMM User - Profile & Password Settings
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

<div class="max-w-3xl mx-auto space-y-8">
    <div>
        <h1 class="text-2xl font-bold text-white tracking-tight">Account Settings</h1>
        <p class="text-xs text-slate-400 mt-1">Manage your credentials, security preferences, and developer API key.</p>
    </div>

    <?php if ($success): ?>
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs flex items-center space-x-2">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span><?= e($success) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs flex items-center space-x-2">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- User Profile Details -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 sm:p-8 backdrop-blur-xl shadow-xl space-y-4">
        <h3 class="text-sm font-bold text-white uppercase tracking-wider">Account Overview</h3>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800">
                <span class="text-[11px] text-slate-500 uppercase tracking-wider block">Username</span>
                <span class="text-sm font-bold text-white mt-1 block font-mono"><?= e($user['username']) ?></span>
            </div>
            <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800">
                <span class="text-[11px] text-slate-500 uppercase tracking-wider block">Email Address</span>
                <span class="text-sm font-bold text-white mt-1 block"><?= e($user['email']) ?></span>
            </div>
            <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800">
                <span class="text-[11px] text-slate-500 uppercase tracking-wider block">Account Role</span>
                <span class="text-sm font-bold text-white mt-1 block uppercase text-blue-400"><?= e($user['role']) ?></span>
            </div>
            <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800">
                <span class="text-[11px] text-slate-500 uppercase tracking-wider block">Registered Date</span>
                <span class="text-sm font-bold text-slate-300 mt-1 block"><?= format_date($user['created_at']) ?></span>
            </div>
        </div>
    </div>

    <!-- API Key Section -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 sm:p-8 backdrop-blur-xl shadow-xl space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-white uppercase tracking-wider">Reseller API Key</h3>
                <p class="text-xs text-slate-400 mt-0.5">Use this token to authenticate programmatic orders via standard SMM protocol.</p>
            </div>
            <a href="/user/api.php" class="text-xs font-semibold text-blue-400 hover:text-blue-300">
                View API Documentation &rarr;
            </a>
        </div>

        <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 flex items-center justify-between gap-4">
            <input type="password" value="<?= e($user['api_key'] ?? 'No API Key Generated') ?>" readonly id="apiKeyField"
                class="bg-transparent border-0 font-mono text-xs text-emerald-400 w-full focus:outline-none">
            <button onclick="
                const f = document.getElementById('apiKeyField');
                f.type = f.type === 'password' ? 'text' : 'password';
            " class="text-xs text-slate-400 hover:text-white shrink-0 px-2.5 py-1 bg-slate-800 rounded-lg">
                Show/Hide
            </button>
        </div>

        <form action="/user/profile.php" method="POST" onsubmit="return confirm('Regenerating will invalidate your current API key immediately. Continue?');">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="regenerate_api">
            <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 text-xs font-semibold text-slate-300 hover:bg-slate-700 hover:text-white border border-slate-700 transition-colors">
                Regenerate API Key
            </button>
        </form>
    </div>

    <!-- Password Change Form -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 sm:p-8 backdrop-blur-xl shadow-xl space-y-6">
        <h3 class="text-sm font-bold text-white uppercase tracking-wider">Change Password</h3>

        <form action="/user/profile.php" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="password">

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Current Password</label>
                <input type="password" name="current_password" required placeholder="••••••••••••"
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-blue-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">New Password</label>
                    <input type="password" name="new_password" required placeholder="At least 8 characters"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Confirm New Password</label>
                    <input type="password" name="confirm_password" required placeholder="Repeat new password"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-blue-500">
                </div>
            </div>

            <button type="submit" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold text-xs shadow-md shadow-blue-500/20 hover:from-blue-500 hover:to-indigo-500 transition-all">
                Update Password
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
