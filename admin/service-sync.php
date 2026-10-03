<?php
/**
 * ApexSMM Admin - Service Synchronization Manager
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../providers/provider-manager.php';

AdminAuth::requireAdmin();

$providers = Database::fetchAll("SELECT * FROM providers WHERE status = 'active' ORDER BY name ASC");
$syncResult = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $providerId = (int)($_POST['provider_id'] ?? 0);

    if (!$providerId) {
        $error = 'Please select a provider to synchronize.';
    } else {
        try {
            $adapter = ProviderManager::getAdapter($providerId);
            $external = $adapter->getServices();

            $externalMap = [];
            foreach ($external as $ex) {
                if (!empty($ex['service'])) {
                    $externalMap[(string)$ex['service']] = $ex;
                }
            }

            $currentServices = Database::fetchAll(
                "SELECT id, provider_service_id, provider_rate, provider_status FROM services WHERE provider_id = ?",
                [$providerId]
            );

            $rateChanged = 0;
            $statusChanged = 0;

            foreach ($currentServices as $cs) {
                $extId = $cs['provider_service_id'];
                if (isset($externalMap[$extId])) {
                    $ex = $externalMap[$extId];
                    $newRate = (float)($ex['rate'] ?? 0);

                    // Update ONLY provider parameters; preserve admin selling rates!
                    Database::execute(
                        "UPDATE services SET 
                            provider_rate = ?, 
                            provider_min = ?, 
                            provider_max = ?, 
                            provider_status = 'active' 
                         WHERE id = ?",
                        [$newRate, (int)($ex['min'] ?? 10), (int)($ex['max'] ?? 10000), $cs['id']]
                    );
                    if (abs($newRate - (float)$cs['provider_rate']) > 0.0001) {
                        $rateChanged++;
                    }
                } else {
                    // Service disappeared from upstream provider
                    Database::execute("UPDATE services SET provider_status = 'inactive' WHERE id = ?", [$cs['id']]);
                    $statusChanged++;
                }
            }

            Database::execute("UPDATE providers SET last_sync_at = NOW() WHERE id = ?", [$providerId]);
            audit_log('admin_synced_services', 'provider', $providerId, ['rate_changes' => $rateChanged, 'disabled' => $statusChanged]);

            flash_set('success', "Provider sync complete! Rate changes detected: {$rateChanged}. Deactivated: {$statusChanged}. Admin selling prices were safely preserved.");
            header('Location: /admin/service-sync.php');
            exit;
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$activeNav = 'services';
$pageTitle = 'Sync Services | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <div class="text-xs text-slate-400 mb-1">
            <a href="/admin/services.php" class="hover:text-white">&larr; Back to Services</a>
        </div>
        <h1 class="text-2xl font-bold text-white tracking-tight">Synchronize Provider Services</h1>
        <p class="text-xs text-slate-400 mt-1">Updates upstream costs, boundaries, and availability without altering your customized retail markups.</p>
    </div>

    <?php if ($error): ?>
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 sm:p-8 backdrop-blur-xl shadow-xl">
        <form action="/admin/service-sync.php" method="POST" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Select Active Provider</label>
                <select name="provider_id" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white">
                    <option value="">Choose provider...</option>
                    <?php foreach ($providers as $p): ?>
                        <option value="<?= (int)$p['id'] ?>">
                            <?= e($p['name']) ?> (Last synced: <?= time_ago($p['last_sync_at']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 text-xs text-slate-400 space-y-2">
                <div class="text-white font-semibold">Strict Separation Guarantee:</div>
                <p>Provider Rate, Provider Min, and Provider Status will be refreshed from upstream API. Your customized Selling Rates and Active retail states will remain 100% intact.</p>
            </div>

            <div class="pt-2 flex justify-end">
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-purple-600 text-white font-semibold text-xs hover:bg-purple-500 shadow-md shadow-purple-500/20">
                    Run Sync &rarr;
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
