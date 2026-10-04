<?php
/**
 * ApexSMM Admin - API Providers Manager
 * White + Premium Purple Design System
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../providers/provider-manager.php';

AdminAuth::requireAdmin();

$error = null;

// Handle Connection Test & Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = trim($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'test' && $id) {
        try {
            $res = ProviderManager::testConnection($id);
            flash_set('success', "Connection successful! Provider Balance: " . number_format($res['balance'], 2) . " " . e($res['currency']));
        } catch (\Throwable $e) {
            flash_set('error', "Connection failed: " . $e->getMessage());
        }
        header('Location: /admin/providers.php');
        exit;
    }

    if ($action === 'delete' && $id) {
        Database::execute("DELETE FROM providers WHERE id = ?", [$id]);
        Database::execute("UPDATE services SET provider_id = NULL WHERE provider_id = ?", [$id]);
        audit_log('admin_deleted_provider', 'provider', $id, "Deleted provider #{$id}");
        flash_set('info', 'Provider removed.');
        header('Location: /admin/providers.php');
        exit;
    }
}

$providers = Database::fetchAll(
    "SELECT p.*, COUNT(s.id) as service_count 
     FROM providers p 
     LEFT JOIN services s ON p.id = s.provider_id 
     GROUP BY p.id 
     ORDER BY p.id ASC"
);

$activeNav = 'providers';
$pageTitle = 'API Providers | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-[#18181B] tracking-tight">API Providers</h1>
            <p class="text-xs text-[#71717A] mt-1">Configure external wholesale SMM providers supporting the standard SMM API protocol.</p>
        </div>
        <a href="/admin/provider-add.php" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#7C3AED] hover:bg-[#6D28D9] text-white text-xs font-semibold shadow-sm transition-all">
            <?= icon('plus', 'w-4 h-4') ?>
            <span>Add Provider</span>
        </a>
    </div>

    <!-- Provider Cards Grid -->
    <?php if (!empty($providers)): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach ($providers as $p): ?>
                <div class="bg-white border border-[#E4E4E7] rounded-2xl p-6 shadow-sm flex flex-col justify-between space-y-5 hover:border-[#DDD6FE] transition-all">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-[#F3E8FF] text-[#7C3AED] flex items-center justify-center font-bold">
                                    <?= icon('server', 'w-5 h-5') ?>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-[#18181B]"><?= e($p['name']) ?></h3>
                                    <span class="text-[10px] text-[#A1A1AA] font-mono">ID #<?= (int)$p['id'] ?></span>
                                </div>
                            </div>
                            <?= status_badge($p['status']) ?>
                        </div>

                        <div class="text-xs font-mono text-[#71717A] bg-[#FAF5FF]/50 p-2.5 rounded-xl border border-[#E4E4E7] truncate mb-4">
                            <?= e($p['api_url']) ?>
                        </div>

                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <div class="p-3.5 rounded-xl bg-[#FAF5FF]/40 border border-[#E4E4E7]">
                                <span class="text-[10px] text-[#71717A] uppercase font-semibold tracking-wider block">API Balance</span>
                                <span class="text-base font-mono font-bold text-emerald-600 mt-1 block">
                                    <?= format_currency($p['balance']) ?> <?= e($p['currency']) ?>
                                </span>
                            </div>
                            <div class="p-3.5 rounded-xl bg-[#FAF5FF]/40 border border-[#E4E4E7]">
                                <span class="text-[10px] text-[#71717A] uppercase font-semibold tracking-wider block">Mapped Services</span>
                                <span class="text-base font-mono font-bold text-[#18181B] mt-1 block">
                                    <?= number_format($p['service_count']) ?>
                                </span>
                            </div>
                        </div>

                        <div class="text-[11px] text-[#71717A] flex items-center justify-between">
                            <span>Key: <strong class="font-mono text-[#18181B]">••••••••<?= substr($p['api_key'], -4) ?></strong></span>
                            <span>Synced: <span class="font-medium text-[#18181B]"><?= time_ago($p['last_sync_at']) ?></span></span>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-[#F4F4F5] flex items-center justify-between gap-2">
                        <form action="/admin/providers.php" method="POST" class="inline-block">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="test">
                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-semibold hover:bg-emerald-100 transition-all">
                                <?= icon('arrow-path', 'w-3.5 h-3.5') ?>
                                <span>Check Balance</span>
                            </button>
                        </form>

                        <div class="flex items-center space-x-2">
                            <a href="/admin/service-sync.php?provider_id=<?= (int)$p['id'] ?>" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-[#FAF5FF] border border-[#DDD6FE] text-[#7C3AED] text-xs font-semibold hover:bg-[#F3E8FF] transition-all">
                                <?= icon('arrow-path', 'w-3.5 h-3.5') ?>
                                <span>Sync</span>
                            </a>
                            <a href="/admin/provider-edit.php?id=<?= (int)$p['id'] ?>" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-[#F4F4F5] text-[#18181B] text-xs font-semibold hover:bg-[#E4E4E7] transition-all">
                                <?= icon('pencil-square', 'w-3.5 h-3.5') ?>
                                <span>Edit</span>
                            </a>
                            <form action="/admin/providers.php" method="POST" onsubmit="return confirm('Delete this provider?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                <button type="submit" class="p-1.5 rounded-lg bg-red-50 text-red-600 text-xs font-semibold hover:bg-red-100 transition-all" title="Delete">
                                    <?= icon('trash', 'w-4 h-4') ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="bg-white border border-[#E4E4E7] rounded-2xl p-16 text-center text-xs text-[#71717A] flex flex-col items-center justify-center gap-2">
            <div class="w-12 h-12 rounded-full bg-[#FAF5FF] flex items-center justify-center text-[#A1A1AA]">
                <?= icon('server', 'w-6 h-6') ?>
            </div>
            <p class="font-medium text-[#18181B]">No API providers configured</p>
            <p class="text-[11px]">Click "Add Provider" above to connect your first upstream wholesale distributor.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
