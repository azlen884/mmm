<?php
/**
 * ApexSMM Admin - Add New Provider
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

    $name   = trim($_POST['name'] ?? '');
    $url    = trim($_POST['api_url'] ?? '');
    $key    = trim($_POST['api_key'] ?? '');
    $status = trim($_POST['status'] ?? 'active');

    if ($name === '' || $url === '' || $key === '') {
        $error = 'Provider name, API endpoint URL, and API key are required.';
    } else {
        Database::execute(
            "INSERT INTO providers (name, api_url, api_key, status, balance, currency) VALUES (?, ?, ?, ?, 0.0000, 'USD')",
            [$name, $url, $key, $status]
        );
        $newId = Database::lastInsertId();
        audit_log('admin_created_provider', 'provider', $newId, "Added provider: {$name}");
        flash_set('success', "Provider '{$name}' configured successfully.");
        header('Location: /admin/providers.php');
        exit;
    }
}

$activeNav = 'providers';
$pageTitle = 'Add Provider | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <a href="/admin/providers.php" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#7C3AED] hover:text-[#6D28D9] mb-2 transition-colors">
            <?= icon('arrow-left', 'w-3.5 h-3.5') ?>
            <span>Back to Providers</span>
        </a>
        <h1 class="text-2xl font-bold text-[#18181B] tracking-tight">Configure API Provider</h1>
        <p class="text-xs text-[#71717A] mt-1">Connect an external SMM provider to import services and route automated orders.</p>
    </div>

    <?php if ($error): ?>
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-center gap-2">
            <?= icon('exclamation-circle', 'w-4 h-4 text-red-500 flex-shrink-0') ?>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <form action="/admin/provider-add.php" method="POST" class="bg-white border border-[#E4E4E7] rounded-2xl p-6 sm:p-8 shadow-sm space-y-4">
        <?= csrf_field() ?>

        <div>
            <label class="block text-xs font-semibold text-[#18181B] mb-1.5">Provider Label / Name</label>
            <input type="text" name="name" required placeholder="e.g. SMMDistributor Global"
                class="w-full bg-[#FAF5FF]/30 border border-[#E4E4E7] rounded-xl px-3.5 py-2.5 text-xs text-[#18181B] focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20 focus:outline-none transition-all placeholder:text-[#A1A1AA]">
        </div>

        <div>
            <label class="block text-xs font-semibold text-[#18181B] mb-1.5">API Endpoint URL</label>
            <input type="url" name="api_url" required placeholder="https://providerdomain.com/api/v2"
                class="w-full bg-[#FAF5FF]/30 border border-[#E4E4E7] rounded-xl px-3.5 py-2.5 text-xs text-[#18181B] font-mono focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20 focus:outline-none transition-all placeholder:text-[#A1A1AA]">
            <span class="text-[11px] text-[#71717A] mt-1 block">Must point to standard SMM v2 POST endpoint.</span>
        </div>

        <div>
            <label class="block text-xs font-semibold text-[#18181B] mb-1.5">Provider Secret API Key</label>
            <input type="password" name="api_key" required placeholder="Paste secret API token"
                class="w-full bg-[#FAF5FF]/30 border border-[#E4E4E7] rounded-xl px-3.5 py-2.5 text-xs text-[#18181B] font-mono focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20 focus:outline-none transition-all placeholder:text-[#A1A1AA]">
        </div>

        <div>
            <label class="block text-xs font-semibold text-[#18181B] mb-1.5">Status</label>
            <select name="status" class="w-full bg-[#FAF5FF]/30 border border-[#E4E4E7] rounded-xl px-3 py-2.5 text-xs text-[#18181B] focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20 focus:outline-none transition-all">
                <option value="active">Active (Available for routing)</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>

        <div class="pt-4 flex justify-end items-center gap-3">
            <a href="/admin/providers.php" class="px-4 py-2.5 rounded-xl bg-white border border-[#E4E4E7] text-[#18181B] text-xs font-semibold hover:bg-[#FAF5FF] transition-all">Cancel</a>
            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-[#7C3AED] hover:bg-[#6D28D9] text-white font-semibold text-xs transition-all shadow-sm">
                <?= icon('check', 'w-4 h-4') ?>
                <span>Save & Connect</span>
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
