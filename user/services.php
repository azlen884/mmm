<?php
/**
 * ApexSMM User - Services Catalog
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/Services/ServiceManager.php';

Auth::requireLogin();

$categoryId = !empty($_GET['category']) ? (int)$_GET['category'] : null;
$search = trim($_GET['search'] ?? '');

$categories = ServiceManager::getActiveCategories();
$services = ServiceManager::getServices($categoryId, $search, true);

$activeNav = 'services';
$pageTitle = 'Services Directory | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Services Directory</h1>
            <p class="text-xs text-slate-400 mt-1">Live rates and ordering boundaries for all active services.</p>
        </div>
        <a href="/user/new-order.php" class="inline-flex items-center px-4 py-2 rounded-xl bg-blue-600 text-white text-xs font-semibold hover:bg-blue-500 transition-colors shadow-md shadow-blue-500/20">
            + Place Order
        </a>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-4 sm:p-5 backdrop-blur-md">
        <form method="GET" action="/user/services.php" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Category</label>
                <select name="category" onchange="this.form.submit()" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-blue-500">
                    <option value="">All Categories (<?= count($services) ?>)</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>" <?= $categoryId === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?> (<?= (int)$cat['service_count'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="sm:col-span-2 flex items-end gap-3">
                <div class="flex-grow">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Search</label>
                    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search services..."
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-blue-500">
                </div>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-medium text-xs hover:bg-blue-500 transition-colors shadow-md shadow-blue-500/20">
                    Filter
                </button>
                <?php if ($categoryId || $search !== ''): ?>
                    <a href="/user/services.php" class="px-4 py-2.5 rounded-xl bg-slate-800 text-slate-400 hover:text-white text-xs transition-colors">
                        Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Table -->
    <?php if (!empty($services)): ?>
        <div class="overflow-x-auto bg-slate-900/60 border border-slate-800/80 rounded-2xl backdrop-blur-md shadow-xl">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-950/80 text-xs uppercase text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-5 py-4 w-16">ID</th>
                        <th class="px-5 py-4">Service</th>
                        <th class="px-5 py-4">Category</th>
                        <th class="px-5 py-4 text-right">Rate / 1k</th>
                        <th class="px-5 py-4 text-center">Min / Max</th>
                        <th class="px-5 py-4 text-center">Features</th>
                        <th class="px-5 py-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <?php foreach ($services as $s): ?>
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <td class="px-5 py-4 font-mono text-xs text-slate-400">#<?= (int)$s['id'] ?></td>
                            <td class="px-5 py-4 font-medium text-white max-w-sm">
                                <div><?= e($s['name']) ?></div>
                                <?php if (!empty($s['description'])): ?>
                                    <div class="text-xs text-slate-500 mt-1 line-clamp-1"><?= e($s['description']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-4 text-slate-400 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-md bg-slate-800 text-xs text-slate-300">
                                    <?= e($s['category_name'] ?? 'General') ?>
                                </span>
                            </td>
                            <td class="px-5 py-4 font-mono font-semibold text-emerald-400 text-right whitespace-nowrap">
                                <?= format_currency($s['rate']) ?>
                            </td>
                            <td class="px-5 py-4 text-xs text-center text-slate-400 whitespace-nowrap">
                                <?= number_format($s['min_quantity']) ?> / <?= number_format($s['max_quantity']) ?>
                            </td>
                            <td class="px-5 py-4 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center space-x-1">
                                    <?php if ($s['refill']): ?>
                                        <span class="px-2 py-0.5 text-[11px] rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-medium">Refill</span>
                                    <?php endif; ?>
                                    <?php if ($s['cancel']): ?>
                                        <span class="px-2 py-0.5 text-[11px] rounded bg-blue-500/10 text-blue-400 border border-blue-500/20 font-medium">Cancel</span>
                                    <?php endif; ?>
                                    <?php if ($s['dripfeed']): ?>
                                        <span class="px-2 py-0.5 text-[11px] rounded bg-purple-500/10 text-purple-400 border border-purple-500/20 font-medium">Drip-feed</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-center whitespace-nowrap">
                                <a href="/user/new-order.php" class="px-3.5 py-1.5 rounded-xl bg-blue-600 text-white text-xs font-semibold hover:bg-blue-500 transition-all shadow-sm">
                                    Order
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-16 text-center">
            <h3 class="text-base font-bold text-white mb-1">No services available</h3>
            <p class="text-xs text-slate-400">There are no active services in this category.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
