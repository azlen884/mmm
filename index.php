<?php
/**
 * ApexSMM Homepage & Landing Portal
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
<section class="relative pt-16 pb-20 md:pt-28 md:pb-32 overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        
        <!-- Live Badge -->
        <div class="inline-flex items-center space-x-2 px-3.5 py-1.5 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-semibold mb-8 animate-fade-in">
            <span class="w-2 h-2 rounded-full bg-blue-400 animate-pulse"></span>
            <span>Enterprise Automated SMM Engine</span>
        </div>

        <h1 class="text-4xl sm:text-6xl lg:text-7xl font-black text-white tracking-tight leading-[1.1] mb-6 max-w-4xl mx-auto">
            Scale Your Digital Presence <br>
            <span class="bg-clip-text text-transparent bg-gradient-to-r from-blue-400 via-indigo-400 to-purple-400">
                At Lightning Velocity
            </span>
        </h1>

        <p class="text-base sm:text-lg text-slate-400 max-w-2xl mx-auto mb-10 leading-relaxed">
            The high-performance SMM distribution hub. Automated provider order dispatching, non-stop real-time status tracking, and transactional wallet reliability.
        </p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="/register.php" class="w-full sm:w-auto px-8 py-3.5 rounded-2xl bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 text-white font-semibold text-base shadow-xl shadow-blue-600/30 hover:shadow-blue-600/50 hover:scale-[1.02] transition-all">
                Create Free Account
            </a>
            <a href="/services.php" class="w-full sm:w-auto px-8 py-3.5 rounded-2xl bg-slate-900 border border-slate-700/80 text-slate-200 font-semibold text-base hover:bg-slate-800 hover:text-white transition-all">
                Browse Live Services &rarr;
            </a>
        </div>

        <!-- Real Stats Bar (Sourced directly from MySQL) -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-20 max-w-5xl mx-auto">
            <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 backdrop-blur-md">
                <div class="text-3xl font-extrabold text-white mb-1"><?= number_format($stats['services_count']) ?></div>
                <div class="text-xs font-medium text-slate-400 uppercase tracking-wider">Active Services</div>
            </div>
            <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 backdrop-blur-md">
                <div class="text-3xl font-extrabold text-blue-400 mb-1"><?= number_format($stats['completed_orders']) ?></div>
                <div class="text-xs font-medium text-slate-400 uppercase tracking-wider">Orders Completed</div>
            </div>
            <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 backdrop-blur-md">
                <div class="text-3xl font-extrabold text-indigo-400 mb-1"><?= number_format($stats['active_users']) ?></div>
                <div class="text-xs font-medium text-slate-400 uppercase tracking-wider">Registered Clients</div>
            </div>
            <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 backdrop-blur-md">
                <div class="text-3xl font-extrabold text-purple-400 mb-1"><?= number_format($stats['categories_count']) ?></div>
                <div class="text-xs font-medium text-slate-400 uppercase tracking-wider">Active Categories</div>
            </div>
        </div>
    </div>
</section>

<!-- Features Grid -->
<section class="py-20 bg-slate-950/40 border-y border-slate-800/80">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16">
            <h2 class="text-xs font-semibold text-blue-400 uppercase tracking-widest mb-2">Engineered For Performance</h2>
            <p class="text-3xl font-bold text-white tracking-tight">Everything You Need For Social Dominance</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Feature 1 -->
            <div class="bg-slate-900/70 border border-slate-800/80 rounded-2xl p-8 hover:border-blue-500/40 transition-all duration-300">
                <div class="w-12 h-12 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center mb-6">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">Automated Order Routing</h3>
                <p class="text-sm text-slate-400 leading-relaxed">
                    Instantaneous API forwarding to upstream providers with zero manual latency.
                </p>
            </div>

            <!-- Feature 2 -->
            <div class="bg-slate-900/70 border border-slate-800/80 rounded-2xl p-8 hover:border-indigo-500/40 transition-all duration-300">
                <div class="w-12 h-12 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center mb-6">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">Transactional Security</h3>
                <p class="text-sm text-slate-400 leading-relaxed">
                    Atomic database balance adjustments with double-spend locks and row-level synchronization.
                </p>
            </div>

            <!-- Feature 3 -->
            <div class="bg-slate-900/70 border border-slate-800/80 rounded-2xl p-8 hover:border-purple-500/40 transition-all duration-300">
                <div class="w-12 h-12 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center mb-6">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">Full Reseller API</h3>
                <p class="text-sm text-slate-400 leading-relaxed">
                    Standardized JSON API protocol allowing developers to integrate orders directly from their systems.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Featured Services (Real MySQL records only) -->
<section class="py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-10">
            <div>
                <h2 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">Active Services</h2>
                <p class="text-sm text-slate-400 mt-1">Live rates and ordering limits updated directly from system catalog.</p>
            </div>
            <a href="/services.php" class="inline-flex items-center text-sm font-semibold text-blue-400 hover:text-blue-300 transition-colors">
                View all services &rarr;
            </a>
        </div>

        <?php if (!empty($featuredServices)): ?>
            <div class="overflow-x-auto bg-slate-900/60 border border-slate-800/80 rounded-2xl backdrop-blur-md">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="bg-slate-950/80 text-xs uppercase text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="px-6 py-4">ID</th>
                            <th class="px-6 py-4">Service</th>
                            <th class="px-6 py-4">Category</th>
                            <th class="px-6 py-4 text-right">Rate / 1k</th>
                            <th class="px-6 py-4 text-center">Min / Max</th>
                            <th class="px-6 py-4 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <?php foreach ($featuredServices as $s): ?>
                            <tr class="hover:bg-slate-800/30 transition-colors">
                                <td class="px-6 py-4 font-mono text-xs text-slate-400">#<?= (int)$s['id'] ?></td>
                                <td class="px-6 py-4 font-medium text-white"><?= e($s['name']) ?></td>
                                <td class="px-6 py-4 text-slate-400"><?= e($s['category_name'] ?? 'General') ?></td>
                                <td class="px-6 py-4 font-mono font-semibold text-emerald-400 text-right">
                                    <?= format_currency($s['rate']) ?>
                                </td>
                                <td class="px-6 py-4 text-xs text-center text-slate-400">
                                    <?= number_format($s['min_quantity']) ?> / <?= number_format($s['max_quantity']) ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <a href="/login.php" class="px-3 py-1.5 rounded-lg bg-blue-600/10 text-blue-400 border border-blue-500/20 text-xs font-semibold hover:bg-blue-600 hover:text-white transition-all">
                                        Order
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-12 text-center">
                <div class="w-12 h-12 rounded-xl bg-slate-800 flex items-center justify-center mx-auto mb-3 text-slate-400">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-white mb-1">No services available</h3>
                <p class="text-xs text-slate-400">Services will appear here once added or imported by administrator.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- CTA Banner -->
<section class="py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="relative rounded-3xl bg-gradient-to-r from-blue-900/50 via-indigo-900/40 to-purple-900/50 border border-blue-500/20 p-10 sm:p-16 text-center overflow-hidden backdrop-blur-xl">
            <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4 tracking-tight">Ready to Accelerate Growth?</h2>
            <p class="text-slate-300 max-w-xl mx-auto mb-8 text-sm sm:text-base leading-relaxed">
                Join our network of agencies, artists, and marketers using automated delivery.
            </p>
            <a href="/register.php" class="inline-flex items-center px-8 py-3.5 rounded-xl bg-white text-slate-900 font-bold hover:bg-slate-100 transition-all shadow-xl">
                Get Started Now &rarr;
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
