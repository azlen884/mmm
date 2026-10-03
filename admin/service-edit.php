<?php
/**
 * ApexSMM Admin - Edit Service
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

AdminAuth::requireAdmin();

$id = (int)($_GET['id'] ?? 0);
$service = Database::fetchOne("SELECT * FROM services WHERE id = ?", [$id]);

if (!$service) {
    http_response_code(404);
    require __DIR__ . '/../errors/404.php';
    exit;
}

$categories = Database::fetchAll("SELECT * FROM categories ORDER BY sort_order ASC, name ASC");
$providers  = Database::fetchAll("SELECT * FROM providers ORDER BY name ASC");

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $name        = trim($_POST['name'] ?? '');
    $categoryId  = (int)($_POST['category_id'] ?? 0);
    $providerId  = !empty($_POST['provider_id']) ? (int)$_POST['provider_id'] : null;
    $provSvcId   = trim($_POST['provider_service_id'] ?? '') ?: null;
    $rate        = (float)($_POST['rate'] ?? 0);
    $provRate    = (float)($_POST['provider_rate'] ?? 0);
    $min         = (int)($_POST['min_quantity'] ?? 10);
    $max         = (int)($_POST['max_quantity'] ?? 10000);
    $refill      = !empty($_POST['refill']) ? 1 : 0;
    $cancel      = !empty($_POST['cancel']) ? 1 : 0;
    $dripfeed    = !empty($_POST['dripfeed']) ? 1 : 0;
    $description = trim($_POST['description'] ?? '');
    $status      = trim($_POST['status'] ?? 'active');

    if ($name === '' || !$categoryId || $rate <= 0) {
        $error = 'Service name, category, and selling rate are required.';
    } else {
        Database::execute(
            "UPDATE services SET 
                name = ?, category_id = ?, provider_id = ?, provider_service_id = ?,
                rate = ?, provider_rate = ?, min_quantity = ?, max_quantity = ?,
                refill = ?, cancel = ?, dripfeed = ?, description = ?, status = ?
             WHERE id = ?",
            [
                $name, $categoryId, $providerId, $provSvcId,
                $rate, $provRate, $min, $max,
                $refill, $cancel, $dripfeed, $description, $status,
                $id
            ]
        );

        audit_log('admin_edited_service', 'service', $id, "Updated service parameters for #{$id}");
        flash_set('success', "Service #{$id} updated successfully.");
        header('Location: /admin/services.php');
        exit;
    }
}

$activeNav = 'services';
$pageTitle = 'Edit Service #' . $service['id'] . ' | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <div class="text-xs text-slate-400 mb-1">
            <a href="/admin/services.php" class="hover:text-white">&larr; Back to Services</a>
        </div>
        <h1 class="text-2xl font-bold text-white tracking-tight">Edit Service #<?= (int)$service['id'] ?></h1>
    </div>

    <?php if ($error): ?>
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form action="/admin/service-edit.php?id=<?= (int)$service['id'] ?>" method="POST" class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 sm:p-8 backdrop-blur-xl space-y-4">
        <?= csrf_field() ?>

        <div>
            <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Service Name</label>
            <input type="text" name="name" value="<?= e($service['name']) ?>" required
                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Category</label>
                <select name="category_id" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= (int)$service['category_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                            <?= e($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Provider Linkage</label>
                <select name="provider_id" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                    <option value="">None (Manual Processing)</option>
                    <?php foreach ($providers as $p): ?>
                        <option value="<?= (int)$p['id'] ?>" <?= (int)$service['provider_id'] === (int)$p['id'] ? 'selected' : '' ?>>
                            <?= e($p['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Provider Service ID</label>
                <input type="text" name="provider_service_id" value="<?= e($service['provider_service_id'] ?? '') ?>"
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Selling Rate / 1k ($)</label>
                <input type="number" step="0.0001" min="0.0001" name="rate" value="<?= (float)$service['rate'] ?>" required
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Provider Cost / 1k ($)</label>
                <input type="number" step="0.0001" min="0" name="provider_rate" value="<?= (float)$service['provider_rate'] ?>"
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Min Quantity</label>
                <input type="number" name="min_quantity" value="<?= (int)$service['min_quantity'] ?>" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Max Quantity</label>
                <input type="number" name="max_quantity" value="<?= (int)$service['max_quantity'] ?>" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono">
            </div>
        </div>

        <div class="flex items-center space-x-6 py-2">
            <label class="flex items-center space-x-2 text-xs text-slate-300 cursor-pointer">
                <input type="checkbox" name="refill" value="1" <?= $service['refill'] ? 'checked' : '' ?> class="rounded text-purple-600">
                <span>Refill Guarantee</span>
            </label>
            <label class="flex items-center space-x-2 text-xs text-slate-300 cursor-pointer">
                <input type="checkbox" name="cancel" value="1" <?= $service['cancel'] ? 'checked' : '' ?> class="rounded text-purple-600">
                <span>Cancel Allowed</span>
            </label>
            <label class="flex items-center space-x-2 text-xs text-slate-300 cursor-pointer">
                <input type="checkbox" name="dripfeed" value="1" <?= $service['dripfeed'] ? 'checked' : '' ?> class="rounded text-purple-600">
                <span>Drip-feed</span>
            </label>
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Description / Guidelines</label>
            <textarea name="description" rows="3" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"><?= e($service['description'] ?? '') ?></textarea>
        </div>

        <div class="pt-4 flex justify-end space-x-3">
            <a href="/admin/services.php" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 text-xs font-semibold">Cancel</a>
            <button type="submit" class="px-6 py-2 rounded-xl bg-purple-600 text-white text-xs font-semibold hover:bg-purple-500 shadow-md shadow-purple-500/20">
                Update Service
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
