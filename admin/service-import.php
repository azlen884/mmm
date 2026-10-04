<?php
/**
 * ApexSMM Admin - Service Import Wizard
 * White + Premium Purple Design System
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/Providers/ProviderService.php';

AdminAuth::requireAdmin();

$providers = Database::fetchAll("SELECT * FROM providers WHERE status = 'active' ORDER BY name ASC");
$categories = Database::fetchAll("SELECT * FROM categories ORDER BY sort_order ASC, name ASC");

$error = null;
$importStats = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $providerId = (int)($_POST['provider_id'] ?? 0);
    $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $margin     = (float)($_POST['margin_percentage'] ?? 20);

    if (!$providerId) {
        $error = 'Please select a configured provider.';
    } else {
        try {
            $importStats = ProviderService::importServices($providerId, $categoryId, $margin);
            audit_log('admin_import_services', 'provider', $providerId, $importStats);
            flash_set('success', "Import completed! Imported: {$importStats['imported']}, Updated: {$importStats['updated']} from {$importStats['total']} total provider services.");
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$activeNav = 'services';
$pageTitle = 'Import Services | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-3xl mx-auto space-y-6">
    <div class="gsap-fade-in">
        <div class="text-xs text-zinc-500 mb-1">
            <a href="/admin/services.php" class="inline-flex items-center space-x-1 text-purple-700 hover:text-purple-800 font-medium">
                <?= icon('chevron-left', 'w-3.5 h-3.5') ?>
                <span>Back to Services</span>
            </a>
        </div>
        <h1 class="text-2xl font-bold text-zinc-900 tracking-tight">Bulk Import Services from Provider API</h1>
        <p class="text-xs text-zinc-500 mt-1">Automatically pull services, descriptions, and cost rates directly from your configured SMM providers.</p>
    </div>

    <?php if ($error): ?>
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center space-x-2">
            <?= icon('exclamation-circle', 'w-4 h-4 text-rose-600 shrink-0') ?>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white border border-zinc-200/80 rounded-2xl p-6 sm:p-7 shadow-xs gsap-card">
        <?php if (empty($providers)): ?>
            <div class="py-8 text-center text-xs text-zinc-500 space-y-3">
                <p>No active API providers configured yet.</p>
                <a href="/admin/provider-add.php" class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl bg-purple-600 text-white font-semibold shadow-xs">
                    <?= icon('plus', 'w-4 h-4') ?>
                    <span>Add Provider First</span>
                </a>
            </div>
        <?php else: ?>
            <form action="/admin/service-import.php" method="POST" class="space-y-4">
                <?= csrf_field() ?>

                <div>
                    <label class="block text-xs font-semibold uppercase text-zinc-600 mb-1.5">Select Provider</label>
                    <select name="provider_id" required class="w-full bg-white border border-zinc-200 rounded-xl px-4 py-2.5 text-xs text-zinc-900 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
                        <option value="">Choose an active provider...</option>
                        <?php foreach ($providers as $p): ?>
                            <option value="<?= (int)$p['id'] ?>">
                                <?= e($p['name']) ?> (<?= e($p['api_url']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-zinc-600 mb-1.5">Selling Markup / Profit Margin (%)</label>
                    <input type="number" step="1" min="0" max="1000" name="margin_percentage" value="25" required
                        class="w-full bg-white border border-zinc-200 rounded-xl px-4 py-2.5 text-xs text-zinc-900 font-mono focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
                    <span class="text-[11px] text-zinc-400 mt-1 block">Selling Price = Provider Rate &times; (1 + Margin / 100). E.g. 25% margin on $1.00 cost sets selling price to $1.25.</span>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-zinc-600 mb-1.5">Assign to Category</label>
                    <select name="category_id" class="w-full bg-white border border-zinc-200 rounded-xl px-4 py-2.5 text-xs text-zinc-900 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
                        <option value="">Auto-create categories using provider category names</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="pt-4 flex justify-end border-t border-zinc-100">
                    <button type="submit" class="inline-flex items-center space-x-1.5 px-6 py-2.5 rounded-xl bg-purple-600 text-white font-semibold text-xs hover:bg-purple-700 shadow-xs transition-colors">
                        <?= icon('arrow-down-tray', 'w-3.5 h-3.5') ?>
                        <span>Execute Service Import</span>
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
