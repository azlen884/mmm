<?php
/**
 * ApexSMM Admin - System Settings
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

AdminAuth::requireAdmin();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $settingsToUpdate = [
        'site_name'            => trim($_POST['site_name'] ?? 'ApexSMM'),
        'site_tagline'         => trim($_POST['site_tagline'] ?? ''),
        'contact_email'        => trim($_POST['contact_email'] ?? ''),
        'currency'             => trim($_POST['currency'] ?? 'USD'),
        'currency_symbol'      => trim($_POST['currency_symbol'] ?? '$'),
        'maintenance_mode'     => !empty($_POST['maintenance_mode']) ? '1' : '0',
        'registration_enabled' => !empty($_POST['registration_enabled']) ? '1' : '0',
        'min_deposit'          => number_format((float)($_POST['min_deposit'] ?? 5), 2, '.', ''),
        'max_deposit'          => number_format((float)($_POST['max_deposit'] ?? 1000), 2, '.', ''),
        'announcement'         => trim($_POST['announcement'] ?? ''),
    ];

    foreach ($settingsToUpdate as $key => $val) {
        set_setting($key, $val);
    }

    audit_log('admin_updated_settings', 'settings', null, 'Updated system settings');
    flash_set('success', 'System configurations saved successfully.');
    header('Location: /admin/settings.php');
    exit;
}

$activeNav = 'settings';
$pageTitle = 'System Settings | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-white tracking-tight">System Settings</h1>
        <p class="text-xs text-slate-400 mt-1">Configure global application parameters, currency, and access policies.</p>
    </div>

    <form action="/admin/settings.php" method="POST" class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 sm:p-8 backdrop-blur-xl shadow-xl space-y-6">
        <?= csrf_field() ?>

        <!-- Brand Identity -->
        <div>
            <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-3">General Identity</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Platform Name</label>
                    <input type="text" name="site_name" value="<?= e(get_setting('site_name', 'ApexSMM')) ?>" required
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Official Support Email</label>
                    <input type="email" name="contact_email" value="<?= e(get_setting('contact_email', 'support@apexsmm.com')) ?>" required
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white">
                </div>
            </div>
            <div class="mt-4">
                <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Tagline</label>
                <input type="text" name="site_tagline" value="<?= e(get_setting('site_tagline', 'The Premier Social Media Marketing Growth Platform')) ?>"
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white">
            </div>
        </div>

        <!-- Currency & Deposit Bounds -->
        <div class="pt-4 border-t border-slate-800">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-3">Currency & Deposits</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div>
                    <label class="block text-[11px] font-semibold uppercase text-slate-400 mb-1">Currency Code</label>
                    <input type="text" name="currency" value="<?= e(get_setting('currency', 'USD')) ?>" required
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold uppercase text-slate-400 mb-1">Symbol</label>
                    <input type="text" name="currency_symbol" value="<?= e(get_setting('currency_symbol', '$')) ?>" required
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold uppercase text-slate-400 mb-1">Min Deposit ($)</label>
                    <input type="number" step="0.01" name="min_deposit" value="<?= e(get_setting('min_deposit', '5.00')) ?>" required
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold uppercase text-slate-400 mb-1">Max Deposit ($)</label>
                    <input type="number" step="0.01" name="max_deposit" value="<?= e(get_setting('max_deposit', '1000.00')) ?>" required
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono">
                </div>
            </div>
        </div>

        <!-- Access Controls -->
        <div class="pt-4 border-t border-slate-800">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-3">Access & Maintenance</h3>
            
            <div class="space-y-3">
                <label class="flex items-center space-x-3 p-3 rounded-2xl bg-slate-950 border border-slate-800 cursor-pointer">
                    <input type="checkbox" name="maintenance_mode" value="1" <?= get_setting('maintenance_mode', '0') === '1' ? 'checked' : '' ?> class="rounded text-purple-600 focus:ring-0">
                    <div>
                        <span class="text-xs font-bold text-white block">Maintenance Mode</span>
                        <span class="text-[11px] text-slate-400 block">Blocks non-admin visitors and serves 503 maintenance page. Admin retains access.</span>
                    </div>
                </label>

                <label class="flex items-center space-x-3 p-3 rounded-2xl bg-slate-950 border border-slate-800 cursor-pointer">
                    <input type="checkbox" name="registration_enabled" value="1" <?= get_setting('registration_enabled', '1') === '1' ? 'checked' : '' ?> class="rounded text-purple-600 focus:ring-0">
                    <div>
                        <span class="text-xs font-bold text-white block">User Registration</span>
                        <span class="text-[11px] text-slate-400 block">Allow public visitors to create new client accounts.</span>
                    </div>
                </label>
            </div>
        </div>

        <!-- Global Header Announcement -->
        <div class="pt-4 border-t border-slate-800">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-1">Global Top Announcement Banner</h3>
            <p class="text-[11px] text-slate-500 mb-2">Leave blank to disable the top announcement bar across the website.</p>
            <input type="text" name="announcement" value="<?= e(get_setting('announcement', '')) ?>" placeholder="e.g. Server maintenance scheduled tonight at 02:00 UTC."
                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white">
        </div>

        <div class="pt-4 flex justify-end">
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-purple-600 text-white font-semibold text-xs hover:bg-purple-500 shadow-md shadow-purple-500/20">
                Save System Settings &rarr;
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
