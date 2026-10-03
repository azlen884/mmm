<?php
/**
 * ApexSMM Public Services Directory
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/app/Services/ServiceManager.php';

$categoryId = !empty($_GET['category']) ? (int)$_GET['category'] : null;
$search = trim($_GET['search'] ?? '');

$categories = ServiceManager::getActiveCategories();
$services = ServiceManager::getServices($categoryId, $search, true);

$pageTitle = 'Services & Pricing | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Header -->
    <div class="mb-10 text-center sm:text-left">
        <h1 class="text-3xl font-extrabold text-white tracking-tight">Services & Pricing</h1>
        <p class="text-sm text-slate-400 mt-1">Real-time catalog with automated dispatching and verified rates per 1,000 units.</p>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-4 sm:p-5 mb-8 backdrop-blur-md">
        <form method="GET" action="/services.php" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Category Filter -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Filter by Category</label>
                <select name="category" onchange="this.form.submit()" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">
                    <option value="">All Categories (<?= count($services) ?> services)</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>" <?= $categoryId === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?> (<?= (int)$cat['service_count'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Search Field -->
            <div class="sm:col-span-2 flex items-end gap-3">
                <div class="flex-grow">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Search Services</label>
                    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by service name or platform..."
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500">
                </div>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-medium text-sm hover:bg-blue-500 transition-colors shadow-md shadow-blue-500/20">
                    Search
                </button>
                <?php if ($categoryId || $search !== ''): ?>
                    <a href="/services.php" class="px-4 py-2.5 rounded-xl bg-slate-800 text-slate-400 hover:text-white text-sm transition-colors">
                        Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Services Table -->
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
                        <th class="px-5 py-4 text-center">Order</th>
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
                                    <?php if (!$s['refill'] && !$s['cancel'] && !$s['dripfeed']): ?>
                                        <span class="text-xs text-slate-600">-</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-center whitespace-nowrap">
                                <a href="/login.php" class="px-3.5 py-1.5 rounded-xl bg-blue-600/15 border border-blue-500/30 text-blue-400 text-xs font-semibold hover:bg-blue-600 hover:text-white transition-all shadow-sm">
                                    Place Order
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-16 text-center">
            <div class="w-14 h-14 rounded-2xl bg-slate-800/80 border border-slate-700/60 flex items-center justify-center mx-auto mb-4 text-slate-400">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </div>
            <h3 class="text-lg font-bold text-white mb-1">No services found</h3>
            <p class="text-sm text-slate-400 max-w-sm mx-auto">There are currently no active services matching your selection criteria.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
