<?php
/**
 * ApexSMM Homepage & Landing Portal
 * White + Premium Purple Design System
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect logged-in user to dashboard as required by spec Section 19
if (Auth::check()) {
    header('Location: /user/dashboard.php');
    exit;
}

$pageTitle = get_setting('site_name', 'ApexSMM') . ' - Premier Social Media Growth Platform';

// Query real statistics from MySQL (NO fake data as strictly enforced by Section 2)
$stats = [
    'services_count'   => (int)Database::fetchValue("SELECT COUNT(*) FROM services WHERE status = 'active'"),
    'completed_orders' => (int)Database::fetchValue("SELECT COUNT(*) FROM orders WHERE status = 'completed'"),
    'active_users'     => (int)Database::fetchValue("SELECT COUNT(*) FROM users WHERE status = 'active'"),
    'categories_count' => (int)Database::fetchValue("SELECT COUNT(*) FROM categories WHERE status = 'active'"),
];

// Featured services directly from MySQL
$featuredServices = Database::fetchAll(
    "SELECT s.*, c.name as category_name 
     FROM services s 
     LEFT JOIN categories c ON s.category_id = c.id 
     WHERE s.status = 'active' 
     ORDER BY s.id ASC 
     LIMIT 6"
);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="relative pt-16 pb-20 md:pt-24 md:pb-28 overflow-hidden bg-gradient-to-b from-purple-50/50 via-white to-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10 gsap-fade-in">
        
        <!-- Live Pill/Badge -->
        <div class="inline-flex items-center space-x-2 px-3.5 py-1.5 rounded-full bg-purple-50 border border-purple-200/80 text-purple-700 text-xs font-semibold mb-8 shadow-xs">
            <span class="w-2 h-2 rounded-full bg-purple-600 animate-pulse"></span>
            <span>Enterprise Automated SMM Engine</span>
        </div>

        <h1 class="text-4xl sm:text-6xl lg:text-7xl font-black text-zinc-900 tracking-tight leading-[1.1] mb-6 max-w-4xl mx-auto">
            Scale Your Digital Reach <br>
            <span class="bg-clip-text text-transparent bg-gradient-to-r from-purple-700 via-purple-600 to-indigo-600">
                With Precision & Speed
            </span>
        </h1>

        <p class="text-base sm:text-lg text-zinc-600 max-w-2xl mx-auto mb-10 leading-relaxed">
            The high-performance SMM distribution hub. Automated provider order dispatching, non-stop real-time status tracking, and transactional wallet reliability.
        </p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-3.5 max-w-md mx-auto">
            <a href="/register.php" class="w-full sm:w-auto inline-flex items-center justify-center space-x-2 px-8 py-3.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-semibold text-sm shadow-sm shadow-purple-500/25 transition-all">
                <?= icon('plus', 'w-4 h-4') ?>
                <span>Create Free Account</span>
            </a>
            <a href="/services.php" class="w-full sm:w-auto inline-flex items-center justify-center space-x-2 px-7 py-3.5 rounded-xl bg-white border border-purple-200 text-purple-700 hover:bg-purple-50 font-semibold text-sm transition-all shadow-xs">
                <?= icon('eye', 'w-4 h-4 text-purple-600') ?>
                <span>Browse Live Services</span>
            </a>
        </div>

        <!-- Real Stats Bar (Sourced directly from MySQL) -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-16 max-w-5xl mx-auto">
            <div class="bg-white border border-zinc-200/80 rounded-2xl p-6 shadow-xs gsap-card">
                <div class="text-3xl font-extrabold text-zinc-900 mb-1 tabular-nums"><?= number_format($stats['services_count']) ?></div>
                <div class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Active Services</div>
            </div>
            <div class="bg-white border border-zinc-200/80 rounded-2xl p-6 shadow-xs gsap-card">
                <div class="text-3xl font-extrabold text-purple-700 mb-1 tabular-nums"><?= number_format($stats['completed_orders']) ?></div>
                <div class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Orders Completed</div>
            </div>
            <div class="bg-white border border-zinc-200/80 rounded-2xl p-6 shadow-xs gsap-card">
                <div class="text-3xl font-extrabold text-purple-600 mb-1 tabular-nums"><?= number_format($stats['active_users']) ?></div>
                <div class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Registered Clients</div>
            </div>
            <div class="bg-white border border-zinc-200/80 rounded-2xl p-6 shadow-xs gsap-card">
                <div class="text-3xl font-extrabold text-zinc-800 mb-1 tabular-nums"><?= number_format($stats['categories_count']) ?></div>
                <div class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Active Categories</div>
            </div>
        </div>
    </div>
</section>

<!-- Features Grid -->
<section class="py-20 bg-white border-y border-zinc-200/80">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16">
            <h2 class="text-xs font-bold text-purple-700 uppercase tracking-widest mb-2">Engineered For Performance</h2>
            <p class="text-3xl font-extrabold text-zinc-900 tracking-tight">Everything You Need For Social Dominance</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Feature 1 -->
            <div class="bg-zinc-50/60 border border-zinc-200/80 rounded-2xl p-8 hover:border-purple-300 hover:shadow-xs transition-all duration-200 gsap-card">
                <div class="w-12 h-12 rounded-xl bg-purple-50 border border-purple-200 text-purple-600 flex items-center justify-center mb-6">
                    <?= icon('bolt', 'w-6 h-6') ?>
                </div>
                <h3 class="text-lg font-bold text-zinc-900 mb-2">Automated Order Routing</h3>
                <p class="text-xs text-zinc-600 leading-relaxed">
                    Instantaneous API forwarding to upstream providers with zero manual latency.
                </p>
            </div>

            <!-- Feature 2 -->
            <div class="bg-zinc-50/60 border border-zinc-200/80 rounded-2xl p-8 hover:border-purple-300 hover:shadow-xs transition-all duration-200 gsap-card">
                <div class="w-12 h-12 rounded-xl bg-purple-50 border border-purple-200 text-purple-600 flex items-center justify-center mb-6">
                    <?= icon('shield-check', 'w-6 h-6') ?>
                </div>
                <h3 class="text-lg font-bold text-zinc-900 mb-2">Transactional Security</h3>
                <p class="text-xs text-zinc-600 leading-relaxed">
                    Atomic database balance adjustments with double-spend locks and row-level synchronization.
                </p>
            </div>

            <!-- Feature 3 -->
            <div class="bg-zinc-50/60 border border-zinc-200/80 rounded-2xl p-8 hover:border-purple-300 hover:shadow-xs transition-all duration-200 gsap-card">
                <div class="w-12 h-12 rounded-xl bg-purple-50 border border-purple-200 text-purple-600 flex items-center justify-center mb-6">
                    <?= icon('document-text', 'w-6 h-6') ?>
                </div>
                <h3 class="text-lg font-bold text-zinc-900 mb-2">Full Reseller API</h3>
                <p class="text-xs text-zinc-600 leading-relaxed">
                    Standardized JSON API protocol allowing developers to integrate orders directly from their systems.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Featured Services (Real MySQL records only) -->
<section class="py-20 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
            <div>
                <h2 class="text-2xl sm:text-3xl font-bold text-zinc-900 tracking-tight">Active Services</h2>
                <p class="text-xs text-zinc-500 mt-1">Live rates and ordering limits updated directly from system catalog.</p>
            </div>
            <a href="/services.php" class="inline-flex items-center space-x-1.5 text-xs font-semibold text-purple-700 hover:text-purple-800 transition-colors">
                <span>View all services</span>
                <?= icon('chevron-right', 'w-3.5 h-3.5') ?>
            </a>
        </div>

        <?php if (!empty($featuredServices)): ?>
            <div class="overflow-x-auto bg-white border border-zinc-200/80 rounded-2xl shadow-xs">
                <table class="w-full text-left text-sm text-zinc-700">
                    <thead class="bg-zinc-50/80 text-xs font-semibold uppercase tracking-wider text-zinc-500 border-b border-zinc-200">
                        <tr>
                            <th class="px-6 py-4">ID</th>
                            <th class="px-6 py-4">Service</th>
                            <th class="px-6 py-4">Category</th>
                            <th class="px-6 py-4 text-right">Rate / 1k</th>
                            <th class="px-6 py-4 text-center">Min / Max</th>
                            <th class="px-6 py-4 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200/70">
                        <?php foreach ($featuredServices as $s): ?>
                            <tr class="hover:bg-purple-50/40 transition-colors">
                                <td class="px-6 py-4 font-mono text-xs text-zinc-500">#<?= (int)$s['id'] ?></td>
                                <td class="px-6 py-4 font-semibold text-zinc-900"><?= e($s['name']) ?></td>
                                <td class="px-6 py-4 text-zinc-600 text-xs"><?= e($s['category_name'] ?? 'General') ?></td>
                                <td class="px-6 py-4 font-mono font-bold text-purple-700 text-right">
                                    <?= format_currency($s['rate']) ?>
                                </td>
                                <td class="px-6 py-4 text-xs text-center font-mono text-zinc-500">
                                    <?= number_format($s['min_quantity']) ?> / <?= number_format($s['max_quantity']) ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <a href="/login.php" class="inline-flex items-center space-x-1 px-3 py-1.5 rounded-lg bg-purple-50 border border-purple-200 text-purple-700 text-xs font-semibold hover:bg-purple-600 hover:text-white transition-all shadow-2xs">
                                        <?= icon('shopping-cart', 'w-3.5 h-3.5') ?>
                                        <span>Order</span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="bg-white border border-zinc-200/80 rounded-2xl p-12 text-center shadow-xs">
                <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center mx-auto mb-3">
                    <?= icon('inbox', 'w-6 h-6') ?>
                </div>
                <h3 class="text-base font-semibold text-zinc-900 mb-1">No services available</h3>
                <p class="text-xs text-zinc-500">Services will appear here once added or imported by administrator.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- CTA Banner -->
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="relative rounded-3xl bg-gradient-to-r from-purple-900 via-purple-800 to-indigo-900 text-white p-10 sm:p-14 text-center overflow-hidden shadow-lg shadow-purple-900/10">
            <h2 class="text-3xl sm:text-4xl font-extrabold mb-4 tracking-tight">Ready to Accelerate Growth?</h2>
            <p class="text-purple-100 max-w-xl mx-auto mb-8 text-sm sm:text-base leading-relaxed">
                Join our network of agencies, artists, and marketers using automated delivery.
            </p>
            <a href="/register.php" class="inline-flex items-center space-x-2 px-8 py-3.5 rounded-xl bg-white text-purple-900 font-bold hover:bg-purple-50 transition-all shadow-md">
                <?= icon('plus', 'w-4 h-4 text-purple-700') ?>
                <span>Get Started Now</span>
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
