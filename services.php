<?php
/**
 * ApexSMM Public Services Directory
 * White + Premium Purple Design System
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
    <div class="mb-8 text-center sm:text-left gsap-fade-in">
        <h1 class="text-3xl font-extrabold text-zinc-900 tracking-tight">Services & Pricing</h1>
        <p class="text-xs text-zinc-500 mt-1">Real-time catalog with automated dispatching and verified rates per 1,000 units.</p>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="bg-white border border-zinc-200/80 rounded-2xl p-4 sm:p-5 mb-8 shadow-xs gsap-card">
        <form method="GET" action="/services.php" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Category Filter -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 mb-1.5">Filter by Category</label>
                <select name="category" onchange="this.form.submit()" class="w-full bg-white border border-zinc-200 rounded-xl px-3.5 py-2.5 text-xs text-zinc-900 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
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
                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 mb-1.5">Search Services</label>
                    <div class="relative">
                        <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by service name or platform..."
                            class="w-full bg-white border border-zinc-200 rounded-xl px-3.5 py-2.5 text-xs text-zinc-900 placeholder-zinc-400 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
                    </div>
                </div>
                <button type="submit" class="inline-flex items-center space-x-1.5 px-5 py-2.5 rounded-xl bg-purple-600 text-white font-semibold text-xs hover:bg-purple-700 transition-colors shadow-xs">
                    <?= icon('search', 'w-4 h-4') ?>
                    <span>Search</span>
                </button>
                <?php if ($categoryId || $search !== ''): ?>
                    <a href="/services.php" class="inline-flex items-center space-x-1 px-4 py-2.5 rounded-xl bg-white border border-zinc-200 text-zinc-600 hover:text-zinc-900 hover:bg-zinc-50 text-xs font-medium transition-colors">
                        <?= icon('arrow-path', 'w-3.5 h-3.5') ?>
                        <span>Reset</span>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Services Table -->
    <?php if (!empty($services)): ?>
        <div class="overflow-x-auto bg-white border border-zinc-200/80 rounded-2xl shadow-xs gsap-card">
            <table class="w-full text-left text-sm text-zinc-700">
                <thead class="bg-zinc-50/80 text-xs font-semibold uppercase tracking-wider text-zinc-500 border-b border-zinc-200">
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
                <tbody class="divide-y divide-zinc-200/70">
                    <?php foreach ($services as $s): ?>
                        <tr class="hover:bg-purple-50/30 transition-colors">
                            <td class="px-5 py-4 font-mono text-xs text-zinc-400">#<?= (int)$s['id'] ?></td>
                            <td class="px-5 py-4 max-w-sm">
                                <div class="font-semibold text-zinc-900 text-xs"><?= e($s['name']) ?></div>
                                <?php if (!empty($s['description'])): ?>
                                    <div class="text-[11px] text-zinc-500 mt-0.5 line-clamp-1"><?= e($s['description']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-md bg-purple-50 border border-purple-100 text-xs font-medium text-purple-700">
                                    <?= e($s['category_name'] ?? 'General') ?>
                                </span>
                            </td>
                            <td class="px-5 py-4 font-mono font-bold text-purple-700 text-right whitespace-nowrap">
                                <?= format_currency($s['rate']) ?>
                            </td>
                            <td class="px-5 py-4 text-xs font-mono text-center text-zinc-500 whitespace-nowrap">
                                <?= number_format($s['min_quantity']) ?> / <?= number_format($s['max_quantity']) ?>
                            </td>
                            <td class="px-5 py-4 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center space-x-1 text-[11px]">
                                    <?php if ($s['refill']): ?>
                                        <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 font-medium">Refill</span>
                                    <?php endif; ?>
                                    <?php if ($s['cancel']): ?>
                                        <span class="px-2 py-0.5 rounded bg-purple-50 text-purple-700 border border-purple-200 font-medium">Cancel</span>
                                    <?php endif; ?>
                                    <?php if ($s['dripfeed']): ?>
                                        <span class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200 font-medium">Drip-feed</span>
                                    <?php endif; ?>
                                    <?php if (!$s['refill'] && !$s['cancel'] && !$s['dripfeed']): ?>
                                        <span class="text-zinc-400">·</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-center whitespace-nowrap">
                                <a href="/login.php" class="inline-flex items-center space-x-1 px-3 py-1.5 rounded-xl bg-purple-50 border border-purple-200 text-purple-700 text-xs font-semibold hover:bg-purple-600 hover:text-white transition-all shadow-2xs">
                                    <?= icon('shopping-cart', 'w-3.5 h-3.5') ?>
                                    <span>Place Order</span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="bg-white border border-zinc-200/80 rounded-2xl p-16 text-center shadow-xs">
            <div class="w-14 h-14 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center mx-auto mb-4">
                <?= icon('inbox', 'w-7 h-7') ?>
            </div>
            <h3 class="text-base font-bold text-zinc-900 mb-1">No services found</h3>
            <p class="text-xs text-zinc-500 max-w-sm mx-auto">There are currently no active services matching your selection criteria.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
