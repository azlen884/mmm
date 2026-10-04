<?php
/**
 * ApexSMM User - Services Catalog
 * White + Premium Purple Design System
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
            <h1 class="text-2xl font-bold text-zinc-900 tracking-tight">Services Directory</h1>
            <p class="text-xs text-zinc-500 mt-1">Live rates and ordering boundaries for all active services.</p>
        </div>
        <a href="/user/new-order.php" class="inline-flex items-center space-x-1.5 px-4 py-2.5 rounded-xl bg-purple-600 text-white text-xs font-semibold hover:bg-purple-700 transition-all shadow-xs shadow-purple-600/25">
            <?= icon('plus', 'w-4 h-4') ?>
            <span>Place Order</span>
        </a>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="bg-white border border-purple-100 rounded-2xl p-4 sm:p-5 shadow-xs">
        <form method="GET" action="/user/services.php" class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-1.5">Category</label>
                <select name="category" onchange="this.form.submit()" class="w-full bg-white border border-zinc-200 rounded-xl px-3.5 py-2.5 text-xs text-zinc-900 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600 transition-colors">
                    <option value="">All Categories (<?= count($services) ?>)</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>" <?= $categoryId === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?> (<?= (int)$cat['service_count'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="sm:col-span-2 flex items-end gap-2.5">
                <div class="relative flex-grow">
                    <label class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-1.5">Search Services</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-400">
                            <?= icon('search', 'w-4 h-4') ?>
                        </div>
                        <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by name or keyword..."
                            class="w-full bg-white border border-zinc-200 rounded-xl pl-9 pr-4 py-2 text-xs text-zinc-900 placeholder-zinc-400 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600 transition-colors">
                    </div>
                </div>
                <button type="submit" class="px-4 py-2 rounded-xl bg-purple-600 text-white font-semibold text-xs hover:bg-purple-700 transition-all shadow-xs shadow-purple-600/25 flex items-center space-x-1.5 cursor-pointer">
                    <?= icon('search', 'w-3.5 h-3.5') ?>
                    <span>Filter</span>
                </button>
                <?php if ($categoryId || $search !== ''): ?>
                    <a href="/user/services.php" class="px-3.5 py-2 rounded-xl bg-zinc-100 text-zinc-600 hover:bg-zinc-200 text-xs font-medium transition-colors">
                        Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Table -->
    <?php if (!empty($services)): ?>
        <div class="overflow-x-auto bg-white border border-purple-100 rounded-2xl shadow-xs">
            <table class="w-full text-left text-sm text-zinc-600">
                <thead class="bg-zinc-50/70 text-xs uppercase font-bold text-zinc-400 border-b border-zinc-100">
                    <tr>
                        <th class="px-4 py-3.5 w-16">ID</th>
                        <th class="px-4 py-3.5">Service</th>
                        <th class="px-4 py-3.5">Category</th>
                        <th class="px-4 py-3.5 text-right">Rate / 1k</th>
                        <th class="px-4 py-3.5 text-center">Min / Max</th>
                        <th class="px-4 py-3.5 text-center">Features</th>
                        <th class="px-4 py-3.5 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <?php foreach ($services as $s): ?>
                        <tr class="hover:bg-purple-50/30 transition-colors">
                            <td class="px-4 py-3.5 font-mono text-xs font-bold text-zinc-900">#<?= (int)$s['id'] ?></td>
                            <td class="px-4 py-3.5 font-medium text-zinc-900 max-w-sm">
                                <div><?= e($s['name']) ?></div>
                                <?php if (!empty($s['description'])): ?>
                                    <div class="text-xs text-zinc-400 mt-0.5 line-clamp-1"><?= e($s['description']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3.5 text-zinc-600 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-md bg-purple-50 text-xs font-medium text-purple-700 border border-purple-100">
                                    <?= e($s['category_name'] ?? 'General') ?>
                                </span>
                            </td>
                            <td class="px-4 py-3.5 font-mono font-bold text-purple-700 text-right whitespace-nowrap">
                                <?= format_currency((float)$s['rate']) ?>
                            </td>
                            <td class="px-4 py-3.5 text-xs font-mono text-center text-zinc-500 whitespace-nowrap">
                                <?= number_format($s['min_quantity']) ?> / <?= number_format($s['max_quantity']) ?>
                            </td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center space-x-1">
                                    <?php if ($s['refill']): ?>
                                        <span class="px-2 py-0.5 text-[11px] rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200 font-semibold">Refill</span>
                                    <?php endif; ?>
                                    <?php if ($s['cancel']): ?>
                                        <span class="px-2 py-0.5 text-[11px] rounded-md bg-blue-50 text-blue-700 border border-blue-200 font-semibold">Cancel</span>
                                    <?php endif; ?>
                                    <?php if ($s['dripfeed']): ?>
                                        <span class="px-2 py-0.5 text-[11px] rounded-md bg-purple-50 text-purple-700 border border-purple-200 font-semibold">Drip</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                <a href="/user/new-order.php" class="inline-flex items-center space-x-1 px-3 py-1.5 rounded-xl bg-purple-600 text-white text-xs font-semibold hover:bg-purple-700 transition-all shadow-xs shadow-purple-600/20">
                                    <?= icon('shopping-cart', 'w-3 h-3') ?>
                                    <span>Order</span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="bg-white border border-purple-100 rounded-3xl p-16 text-center shadow-xs">
            <div class="w-12 h-12 rounded-2xl bg-purple-50 flex items-center justify-center mx-auto mb-3 text-purple-500">
                <?= icon('inbox', 'w-6 h-6') ?>
            </div>
            <h3 class="text-base font-bold text-zinc-900 mb-1">No services found</h3>
            <p class="text-xs text-zinc-500">No active services match your current filter.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
