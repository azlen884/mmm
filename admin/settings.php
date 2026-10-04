<?php
/**
 * ApexSMM Admin - System Settings
 * White + Premium Purple Design System
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
        <h1 class="text-2xl font-bold text-[#18181B] tracking-tight">System Settings</h1>
        <p class="text-xs text-[#71717A] mt-1">Configure global application parameters, brand identity, and operational policies.</p>
    </div>

    <form action="/admin/settings.php" method="POST" class="bg-white border border-[#E4E4E7] rounded-2xl p-6 sm:p-8 shadow-sm space-y-6">
        <?= csrf_field() ?>

        <!-- Brand Identity -->
        <div>
            <div class="flex items-center gap-2 pb-3 border-b border-[#F4F4F5] mb-4">
                <div class="w-7 h-7 rounded-lg bg-[#F3E8FF] text-[#7C3AED] flex items-center justify-center">
                    <?= icon('cog-6-tooth', 'w-4 h-4') ?>
                </div>
                <h3 class="text-sm font-bold text-[#18181B]">Platform Identity</h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-[#18181B] mb-1.5">Platform Brand Name</label>
                    <input type="text" name="site_name" value="<?= e(get_setting('site_name', 'ApexSMM')) ?>" required
                        class="w-full bg-[#FAF5FF]/30 border border-[#E4E4E7] rounded-xl px-3.5 py-2.5 text-xs text-[#18181B] focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20 focus:outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#18181B] mb-1.5">Official Support Email</label>
                    <input type="email" name="contact_email" value="<?= e(get_setting('contact_email', 'support@apexsmm.com')) ?>" required
                        class="w-full bg-[#FAF5FF]/30 border border-[#E4E4E7] rounded-xl px-3.5 py-2.5 text-xs text-[#18181B] focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20 focus:outline-none transition-all">
                </div>
            </div>
            <div class="mt-4">
                <label class="block text-xs font-semibold text-[#18181B] mb-1.5">Platform Tagline</label>
                <input type="text" name="site_tagline" value="<?= e(get_setting('site_tagline', 'The Premier Social Media Marketing Growth Platform')) ?>"
                    class="w-full bg-[#FAF5FF]/30 border border-[#E4E4E7] rounded-xl px-3.5 py-2.5 text-xs text-[#18181B] focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20 focus:outline-none transition-all">
            </div>
        </div>

        <!-- Currency & Deposit Bounds -->
        <div class="pt-2">
            <div class="flex items-center gap-2 pb-3 border-b border-[#F4F4F5] mb-4">
                <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <?= icon('banknotes', 'w-4 h-4') ?>
                </div>
                <h3 class="text-sm font-bold text-[#18181B]">Currency & Deposit Bounds</h3>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-[#18181B] mb-1.5">Currency Code</label>
                    <input type="text" name="currency" value="<?= e(get_setting('currency', 'USD')) ?>" required
                        class="w-full bg-[#FAF5FF]/30 border border-[#E4E4E7] rounded-xl px-3.5 py-2.5 text-xs text-[#18181B] font-mono focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20 focus:outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#18181B] mb-1.5">Symbol</label>
                    <input type="text" name="currency_symbol" value="<?= e(get_setting('currency_symbol', '$')) ?>" required
                        class="w-full bg-[#FAF5FF]/30 border border-[#E4E4E7] rounded-xl px-3.5 py-2.5 text-xs text-[#18181B] font-mono focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20 focus:outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#18181B] mb-1.5">Min Deposit ($)</label>
                    <input type="number" step="0.01" name="min_deposit" value="<?= e(get_setting('min_deposit', '5.00')) ?>" required
                        class="w-full bg-[#FAF5FF]/30 border border-[#E4E4E7] rounded-xl px-3.5 py-2.5 text-xs text-[#18181B] font-mono focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20 focus:outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#18181B] mb-1.5">Max Deposit ($)</label>
                    <input type="number" step="0.01" name="max_deposit" value="<?= e(get_setting('max_deposit', '1000.00')) ?>" required
                        class="w-full bg-[#FAF5FF]/30 border border-[#E4E4E7] rounded-xl px-3.5 py-2.5 text-xs text-[#18181B] font-mono focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20 focus:outline-none transition-all">
                </div>
            </div>
        </div>

        <!-- Access Controls -->
        <div class="pt-2">
            <div class="flex items-center gap-2 pb-3 border-b border-[#F4F4F5] mb-4">
                <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <?= icon('shield-check', 'w-4 h-4') ?>
                </div>
                <h3 class="text-sm font-bold text-[#18181B]">Access Policies</h3>
            </div>
            
            <div class="space-y-3">
                <label class="flex items-start gap-3 p-3.5 rounded-xl bg-[#FAF5FF]/40 border border-[#E4E4E7] hover:border-[#DDD6FE] transition-all cursor-pointer">
                    <input type="checkbox" name="maintenance_mode" value="1" <?= get_setting('maintenance_mode', '0') === '1' ? 'checked' : '' ?> class="mt-0.5 rounded text-[#7C3AED] focus:ring-0">
                    <div>
                        <span class="text-xs font-bold text-[#18181B] block">Maintenance Mode</span>
                        <span class="text-[11px] text-[#71717A] block">Blocks non-admin visitors and serves 503 maintenance page. Admin keeps full access.</span>
                    </div>
                </label>

                <label class="flex items-start gap-3 p-3.5 rounded-xl bg-[#FAF5FF]/40 border border-[#E4E4E7] hover:border-[#DDD6FE] transition-all cursor-pointer">
                    <input type="checkbox" name="registration_enabled" value="1" <?= get_setting('registration_enabled', '1') === '1' ? 'checked' : '' ?> class="mt-0.5 rounded text-[#7C3AED] focus:ring-0">
                    <div>
                        <span class="text-xs font-bold text-[#18181B] block">Public User Registration</span>
                        <span class="text-[11px] text-[#71717A] block">Allow public visitors to register and create new client accounts.</span>
                    </div>
                </label>
            </div>
        </div>

        <!-- Global Header Announcement -->
        <div class="pt-2">
            <div class="flex items-center gap-2 pb-3 border-b border-[#F4F4F5] mb-4">
                <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <?= icon('megaphone', 'w-4 h-4') ?>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-[#18181B]">Top Announcement Bar</h3>
                    <p class="text-[11px] text-[#71717A]">Leave empty to disable the global site-wide announcement bar.</p>
                </div>
            </div>
            <input type="text" name="announcement" value="<?= e(get_setting('announcement', '')) ?>" placeholder="e.g. Scheduled gateway maintenance tonight at 02:00 UTC."
                class="w-full bg-[#FAF5FF]/30 border border-[#E4E4E7] rounded-xl px-3.5 py-2.5 text-xs text-[#18181B] focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20 focus:outline-none transition-all placeholder:text-[#A1A1AA]">
        </div>

        <div class="pt-4 flex justify-end">
            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-[#7C3AED] hover:bg-[#6D28D9] text-white font-semibold text-xs transition-all shadow-sm">
                <?= icon('check', 'w-4 h-4') ?>
                <span>Save System Settings</span>
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
