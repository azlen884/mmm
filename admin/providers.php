<?php
/**
 * ApexSMM Admin - API Providers Manager
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
            <h1 class="text-2xl font-bold text-white tracking-tight">API Providers</h1>
            <p class="text-xs text-slate-400 mt-1">Configure external wholesale SMM providers supporting the standard SMM protocol.</p>
        </div>
        <a href="/admin/provider-add.php" class="inline-flex items-center px-4 py-2 rounded-xl bg-purple-600 text-white text-xs font-semibold hover:bg-purple-500 shadow-md shadow-purple-500/20">
            + Add Provider
        </a>
    </div>

    <!-- Provider Cards Grid -->
    <?php if (!empty($providers)): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach ($providers as $p): ?>
                <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 backdrop-blur-xl shadow-xl flex flex-col justify-between space-y-4">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-base font-bold text-white"><?= e($p['name']) ?></span>
                            <?= status_badge($p['status']) ?>
                        </div>
                        <div class="text-xs font-mono text-slate-400 truncate mb-4"><?= e($p['api_url']) ?></div>

                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
                                <span class="text-[10px] text-slate-500 uppercase tracking-wider block">API Balance</span>
                                <span class="text-sm font-mono font-bold text-emerald-400 mt-0.5 block">
                                    <?= format_currency($p['balance']) ?> <?= e($p['currency']) ?>
                                </span>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
                                <span class="text-[10px] text-slate-500 uppercase tracking-wider block">Mapped Services</span>
                                <span class="text-sm font-mono font-bold text-white mt-0.5 block">
                                    <?= number_format($p['service_count']) ?>
                                </span>
                            </div>
                        </div>

                        <div class="text-[11px] text-slate-500 flex items-center justify-between">
                            <span>Key: <strong class="font-mono text-slate-400">••••••••<?= substr($p['api_key'], -4) ?></strong></span>
                            <span>Synced: <?= time_ago($p['last_sync_at']) ?></span>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-800 flex items-center justify-between gap-2">
                        <form action="/admin/providers.php" method="POST" class="inline-block">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="test">
                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-semibold hover:bg-emerald-500/20">
                                Test Balance
                            </button>
                        </form>

                        <div class="flex items-center space-x-2">
                            <a href="/admin/provider-edit.php?id=<?= (int)$p['id'] ?>" class="px-3 py-1.5 rounded-lg bg-slate-800 text-slate-300 text-xs font-semibold hover:bg-slate-700">
                                Edit
                            </a>
                            <form action="/admin/providers.php" method="POST" onsubmit="return confirm('Delete this provider?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                <button type="submit" class="px-3 py-1.5 rounded-lg bg-rose-500/10 text-rose-400 text-xs font-semibold hover:bg-rose-500/20">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-16 text-center text-xs text-slate-500">
            No API providers configured. Click "Add Provider" to connect your first upstream distributor.
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
