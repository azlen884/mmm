<?php
/**
 * ApexSMM Admin - Services Management
 * White + Premium Purple Design System
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
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 gsap-fade-in">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 tracking-tight">Services Directory</h1>
            <p class="text-xs text-zinc-500 mt-1"><?= count($services) ?> services registered in catalog</p>
        </div>
        <div class="flex items-center space-x-2.5">
            <a href="/admin/service-add.php" class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl bg-purple-600 text-white text-xs font-semibold hover:bg-purple-700 shadow-xs transition-colors">
                <?= icon('plus', 'w-4 h-4') ?>
                <span>Add Service</span>
            </a>
            <a href="/admin/service-import.php" class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl bg-white border border-purple-200 text-purple-700 text-xs font-semibold hover:bg-purple-50 shadow-2xs transition-colors">
                <?= icon('arrow-down-tray', 'w-4 h-4 text-purple-600') ?>
                <span>Import API Services</span>
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white border border-zinc-200/80 rounded-2xl p-4 shadow-xs gsap-card">
        <form method="GET" action="/admin/services.php" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <select name="category" onchange="this.form.submit()" class="w-full bg-white border border-zinc-200 rounded-xl px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
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
                    class="w-full bg-white border border-zinc-200 rounded-xl px-4 py-2 text-xs text-zinc-900 placeholder-zinc-400 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
                <button type="submit" class="inline-flex items-center space-x-1 px-4 py-2 rounded-xl bg-purple-600 text-white text-xs font-semibold hover:bg-purple-700 shadow-xs">
                    <?= icon('search', 'w-3.5 h-3.5') ?>
                    <span>Search</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Services Table -->
    <?php if (!empty($services)): ?>
        <div class="overflow-x-auto bg-white border border-zinc-200/80 rounded-2xl shadow-xs gsap-card">
            <table class="w-full text-left text-sm text-zinc-700">
                <thead class="bg-zinc-50/80 text-xs font-semibold uppercase tracking-wider text-zinc-500 border-b border-zinc-200">
                    <tr>
                        <th class="px-4 py-3.5 w-14">ID</th>
                        <th class="px-4 py-3.5">Service Name</th>
                        <th class="px-4 py-3.5">Category</th>
                        <th class="px-4 py-3.5">Provider</th>
                        <th class="px-4 py-3.5 text-right">Selling Rate</th>
                        <th class="px-4 py-3.5 text-right">Cost Rate</th>
                        <th class="px-4 py-3.5 text-center">Min / Max</th>
                        <th class="px-4 py-3.5 text-center">Status</th>
                        <th class="px-4 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200/70 text-xs">
                    <?php foreach ($services as $s): ?>
                        <tr class="hover:bg-purple-50/30 transition-colors">
                            <td class="px-4 py-3.5 font-mono text-zinc-400">#<?= (int)$s['id'] ?></td>
                            <td class="px-4 py-3.5 font-semibold text-zinc-900 max-w-xs truncate">
                                <?= e($s['name']) ?>
                            </td>
                            <td class="px-4 py-3.5 text-zinc-600 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-md bg-purple-50 text-purple-700 border border-purple-100 text-[11px] font-medium">
                                    <?= e($s['category_name'] ?? 'General') ?>
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-zinc-600 whitespace-nowrap">
                                <span class="font-medium"><?= e($s['provider_name'] ?? 'Manual') ?></span>
                                <?php if ($s['provider_service_id']): ?>
                                    <span class="text-[10px] text-zinc-400 font-mono">[#<?= e($s['provider_service_id']) ?>]</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3.5 font-mono font-bold text-purple-700 text-right">
                                <?= format_currency($s['rate']) ?>
                            </td>
                            <td class="px-4 py-3.5 font-mono text-zinc-500 text-right">
                                <?= format_currency($s['provider_rate']) ?>
                            </td>
                            <td class="px-4 py-3.5 text-center text-zinc-500 font-mono whitespace-nowrap">
                                <?= number_format($s['min_quantity']) ?> / <?= number_format($s['max_quantity']) ?>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <?= status_badge($s['status']) ?>
                            </td>
                            <td class="px-4 py-3.5 text-right whitespace-nowrap space-x-1.5">
                                <a href="/admin/service-edit.php?id=<?= (int)$s['id'] ?>" class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-lg bg-purple-50 border border-purple-200 text-purple-700 hover:bg-purple-100 text-xs font-semibold shadow-2xs">
                                    <?= icon('pencil', 'w-3 h-3') ?>
                                    <span>Edit</span>
                                </a>
                                <form action="/admin/services.php" method="POST" class="inline-block">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                    <button type="submit" class="px-2.5 py-1 rounded-lg bg-zinc-100 hover:bg-zinc-200 text-zinc-700 text-xs font-semibold transition-colors">
                                        <?= $s['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                </form>
                                <form action="/admin/services.php" method="POST" class="inline-block" onsubmit="return confirm('Delete this service?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                    <button type="submit" class="p-1 rounded-lg text-rose-600 hover:bg-rose-50 transition-colors inline-flex items-center">
                                        <?= icon('trash', 'w-3.5 h-3.5') ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="bg-white border border-zinc-200/80 rounded-2xl p-16 text-center text-xs text-zinc-500 shadow-xs gsap-card">
            No services found. Click "Add Service" or "Import API Services" to populate your catalog.
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
