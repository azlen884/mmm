<?php
/**
 * ApexSMM Admin - Services Management
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/Services/ServiceManager.php';

AdminAuth::requireAdmin();

// Handle Status Toggle or Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = trim($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'toggle' && $id) {
        $curr = Database::fetchValue("SELECT status FROM services WHERE id = ?", [$id]);
        $next = ($curr === 'active') ? 'inactive' : 'active';
        Database::execute("UPDATE services SET status = ? WHERE id = ?", [$next, $id]);
        audit_log('admin_toggle_service', 'service', $id, "Status toggled to {$next}");
        flash_set('success', 'Service status updated.');
        header('Location: /admin/services.php');
        exit;
    }

    if ($action === 'delete' && $id) {
        Database::execute("DELETE FROM services WHERE id = ?", [$id]);
        audit_log('admin_delete_service', 'service', $id, "Deleted service #{$id}");
        flash_set('info', 'Service deleted successfully.');
        header('Location: /admin/services.php');
        exit;
    }
}

$categoryId = !empty($_GET['category']) ? (int)$_GET['category'] : null;
$search = trim($_GET['search'] ?? '');
$services = ServiceManager::getServices($categoryId, $search, false);
$categories = Database::fetchAll("SELECT * FROM categories ORDER BY sort_order ASC, name ASC");

$activeNav = 'services';
$pageTitle = 'Manage Services | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Services Directory</h1>
            <p class="text-xs text-slate-400 mt-1"><?= count($services) ?> services registered in database</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="/admin/service-add.php" class="px-4 py-2 rounded-xl bg-purple-600 text-white text-xs font-semibold hover:bg-purple-500 shadow-md shadow-purple-500/20">
                + Add Service
            </a>
            <a href="/admin/service-import.php" class="px-4 py-2 rounded-xl bg-blue-600 text-white text-xs font-semibold hover:bg-blue-500 shadow-md shadow-blue-500/20">
                Import API Services &rarr;
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-4 backdrop-blur-md">
        <form method="GET" action="/admin/services.php" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <select name="category" onchange="this.form.submit()" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= $categoryId === (int)$c['id'] ? 'selected' : '' ?>>
                            <?= e($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sm:col-span-2 flex gap-2">
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by service name or description..."
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-purple-500">
                <button type="submit" class="px-4 py-2 rounded-xl bg-purple-600 text-white text-xs font-semibold">Search</button>
            </div>
        </form>
    </div>

    <!-- Services Table -->
    <?php if (!empty($services)): ?>
        <div class="overflow-x-auto bg-slate-900/60 border border-slate-800/80 rounded-2xl backdrop-blur-md shadow-xl">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-950/80 text-xs uppercase text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3 w-14">ID</th>
                        <th class="px-4 py-3">Service Name</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3">Provider</th>
                        <th class="px-4 py-3 text-right">Selling Rate</th>
                        <th class="px-4 py-3 text-right">Cost Rate</th>
                        <th class="px-4 py-3 text-center">Min / Max</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-xs">
                    <?php foreach ($services as $s): ?>
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <td class="px-4 py-3 font-mono text-slate-400">#<?= (int)$s['id'] ?></td>
                            <td class="px-4 py-3 font-medium text-white max-w-xs truncate">
                                <?= e($s['name']) ?>
                            </td>
                            <td class="px-4 py-3 text-slate-400 whitespace-nowrap"><?= e($s['category_name'] ?? 'General') ?></td>
                            <td class="px-4 py-3 text-slate-400 whitespace-nowrap">
                                <?= e($s['provider_name'] ?? 'Manual') ?>
                                <?php if ($s['provider_service_id']): ?>
                                    <span class="text-[10px] text-slate-500 font-mono">[#<?= e($s['provider_service_id']) ?>]</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 font-mono font-bold text-emerald-400 text-right">
                                <?= format_currency($s['rate']) ?>
                            </td>
                            <td class="px-4 py-3 font-mono text-slate-400 text-right">
                                <?= format_currency($s['provider_rate']) ?>
                            </td>
                            <td class="px-4 py-3 text-center text-slate-400 whitespace-nowrap">
                                <?= number_format($s['min_quantity']) ?> / <?= number_format($s['max_quantity']) ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <?= status_badge($s['status']) ?>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap space-x-2">
                                <a href="/admin/service-edit.php?id=<?= (int)$s['id'] ?>" class="text-blue-400 hover:text-blue-300 font-semibold">
                                    Edit
                                </a>
                                <form action="/admin/services.php" method="POST" class="inline-block">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                    <button type="submit" class="text-amber-400 hover:text-amber-300 font-semibold ml-2">
                                        <?= $s['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                </form>
                                <form action="/admin/services.php" method="POST" class="inline-block" onsubmit="return confirm('Delete this service?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                    <button type="submit" class="text-rose-400 hover:text-rose-300 font-semibold ml-2">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-16 text-center text-xs text-slate-500">
            No services found. Click "Add Service" or "Import API Services" to populate your catalog.
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
