<?php
/**
 * ApexSMM Admin - Edit Provider
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

AdminAuth::requireAdmin();

$id = (int)($_GET['id'] ?? 0);
$provider = Database::fetchOne("SELECT * FROM providers WHERE id = ?", [$id]);

if (!$provider) {
    http_response_code(404);
    require __DIR__ . '/../errors/404.php';
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $name   = trim($_POST['name'] ?? '');
    $url    = trim($_POST['api_url'] ?? '');
    $key    = trim($_POST['api_key'] ?? '');
    $status = trim($_POST['status'] ?? 'active');

    if ($name === '' || $url === '') {
        $error = 'Provider name and API endpoint URL are required.';
    } else {
        // If key left empty, keep existing key
        $finalKey = ($key !== '') ? $key : $provider['api_key'];

        Database::execute(
            "UPDATE providers SET name = ?, api_url = ?, api_key = ?, status = ? WHERE id = ?",
            [$name, $url, $finalKey, $status, $id]
        );

        audit_log('admin_edited_provider', 'provider', $id, "Updated provider: {$name}");
        flash_set('success', "Provider #{$id} updated.");
        header('Location: /admin/providers.php');
        exit;
    }
}

$activeNav = 'providers';
$pageTitle = 'Edit Provider #' . $provider['id'] . ' | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <div class="text-xs text-slate-400 mb-1">
            <a href="/admin/providers.php" class="hover:text-white">&larr; Back to Providers</a>
        </div>
        <h1 class="text-2xl font-bold text-white tracking-tight">Edit Provider: <?= e($provider['name']) ?></h1>
    </div>

    <?php if ($error): ?>
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form action="/admin/provider-edit.php?id=<?= (int)$provider['id'] ?>" method="POST" class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 sm:p-8 backdrop-blur-xl shadow-xl space-y-4">
        <?= csrf_field() ?>

        <div>
            <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Provider Label / Name</label>
            <input type="text" name="name" value="<?= e($provider['name']) ?>" required
                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white">
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">API Endpoint URL</label>
            <input type="url" name="api_url" value="<?= e($provider['api_url']) ?>" required
                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white font-mono">
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Update API Key (Leave blank to keep existing)</label>
            <input type="password" name="api_key" placeholder="••••••••••••••••••••••••"
                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white font-mono">
            <span class="text-[11px] text-slate-500 mt-1 block">Current Key ends with: <strong class="font-mono text-slate-400">••••<?= substr($provider['api_key'], -4) ?></strong></span>
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Status</label>
            <select name="status" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                <option value="active" <?= $provider['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $provider['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>

        <div class="pt-4 flex justify-end space-x-3">
            <a href="/admin/providers.php" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 text-xs font-semibold">Cancel</a>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-purple-600 text-white font-semibold text-xs hover:bg-purple-500 shadow-md shadow-purple-500/20">
                Update Provider
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
