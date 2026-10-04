<?php
/**
 * ApexSMM Admin - Service Synchronization Manager
 * White + Premium Purple Design System
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
    <div class="gsap-fade-in">
        <div class="text-xs text-zinc-500 mb-1">
            <a href="/admin/services.php" class="inline-flex items-center space-x-1 text-purple-700 hover:text-purple-800 font-medium">
                <?= icon('chevron-left', 'w-3.5 h-3.5') ?>
                <span>Back to Services</span>
            </a>
        </div>
        <h1 class="text-2xl font-bold text-zinc-900 tracking-tight">Synchronize Provider Services</h1>
        <p class="text-xs text-zinc-500 mt-1">Updates upstream costs, boundaries, and availability without altering your customized retail markups.</p>
    </div>

    <?php if ($error): ?>
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center space-x-2">
            <?= icon('exclamation-circle', 'w-4 h-4 text-rose-600 shrink-0') ?>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white border border-zinc-200/80 rounded-2xl p-6 sm:p-7 shadow-xs gsap-card">
        <form action="/admin/service-sync.php" method="POST" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-semibold uppercase text-zinc-600 mb-1.5">Select Active Provider</label>
                <select name="provider_id" required class="w-full bg-white border border-zinc-200 rounded-xl px-4 py-2.5 text-xs text-zinc-900 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
                    <option value="">Choose provider...</option>
                    <?php foreach ($providers as $p): ?>
                        <option value="<?= (int)$p['id'] ?>">
                            <?= e($p['name']) ?> (Last Sync: <?= $p['last_sync_at'] ? time_ago($p['last_sync_at']) : 'Never' ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="p-4 rounded-xl bg-purple-50/50 border border-purple-100 text-xs text-purple-900 space-y-1">
                <span class="font-bold block">Safe Sync Policy:</span>
                <p class="text-zinc-600 leading-relaxed">
                    This synchronization updates the provider cost and boundary requirements in your database. It will NEVER overwrite your retail selling prices or custom descriptions.
                </p>
            </div>

            <div class="pt-4 flex justify-end border-t border-zinc-100">
                <button type="submit" class="inline-flex items-center space-x-1.5 px-6 py-2.5 rounded-xl bg-purple-600 text-white font-semibold text-xs hover:bg-purple-700 shadow-xs transition-colors">
                    <?= icon('arrow-path', 'w-3.5 h-3.5') ?>
                    <span>Start Synchronization</span>
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
