<?php
/**
 * ApexSMM Admin - Platform Analytics & Financial Reports
 * Pure SQL aggregated reporting (Zero fabricated values).
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

AdminAuth::requireAdmin();

// Financial Summary
$grossRevenue   = (float)Database::fetchValue("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed'");
$totalOrderSpent= (float)Database::fetchValue("SELECT COALESCE(SUM(charge), 0) FROM orders WHERE status NOT IN ('cancelled', 'refunded', 'failed')");
$totalRefunds   = (float)Database::fetchValue("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type = 'refund'");
$totalUserBal   = (float)Database::fetchValue("SELECT COALESCE(SUM(balance), 0) FROM users");

// Top 5 Services by Order Count
$topServices = Database::fetchAll(
    "SELECT s.id, s.name, c.name as category_name, COUNT(o.id) as order_count, SUM(o.charge) as gross_charge 
     FROM orders o 
     JOIN services s ON o.service_id = s.id 
     LEFT JOIN categories c ON s.category_id = c.id 
     GROUP BY s.id 
     ORDER BY order_count DESC 
     LIMIT 5"
);

// Monthly Order Aggregation (Past 6 Months)
$monthlyOrders = Database::fetchAll(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') as order_month, COUNT(*) as count, SUM(charge) as revenue 
     FROM orders 
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) 
     GROUP BY order_month 
     ORDER BY order_month DESC"
);

$activeNav = 'reports';
$pageTitle = 'Platform Reports | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-8">
    <div>
        <h1 class="text-2xl font-bold text-white tracking-tight">Platform Reports & Performance</h1>
        <p class="text-xs text-slate-400 mt-1">Aggregated operational reports computed directly from MySQL database tables.</p>
    </div>

    <!-- Financial Ledger Summary -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800">
            <span class="text-[11px] text-slate-400 uppercase tracking-wider block">Gross Verified Deposits</span>
            <span class="text-2xl font-black font-mono text-emerald-400 mt-1 block"><?= format_currency($grossRevenue) ?></span>
        </div>
        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800">
            <span class="text-[11px] text-slate-400 uppercase tracking-wider block">Delivered Order Volume</span>
            <span class="text-2xl font-black font-mono text-blue-400 mt-1 block"><?= format_currency($totalOrderSpent) ?></span>
        </div>
        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800">
            <span class="text-[11px] text-slate-400 uppercase tracking-wider block">Total Refunds Issued</span>
            <span class="text-2xl font-black font-mono text-amber-400 mt-1 block"><?= format_currency($totalRefunds) ?></span>
        </div>
        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800">
            <span class="text-[11px] text-slate-400 uppercase tracking-wider block">Unspent User Balances</span>
            <span class="text-2xl font-black font-mono text-purple-400 mt-1 block"><?= format_currency($totalUserBal) ?></span>
        </div>
    </div>

    <!-- Top Performing Services -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 backdrop-blur-xl shadow-xl space-y-4">
        <h3 class="text-sm font-bold text-white uppercase tracking-wider">Top 5 Services By Order Volume</h3>

        <?php if (!empty($topServices)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="text-xs uppercase text-slate-400 border-b border-slate-800 pb-2">
                        <tr>
                            <th class="py-3 px-4">Service</th>
                            <th class="py-3 px-4">Category</th>
                            <th class="py-3 px-4 text-center">Orders Placed</th>
                            <th class="py-3 px-4 text-right">Gross Charged</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-xs">
                        <?php foreach ($topServices as $ts): ?>
                            <tr class="hover:bg-slate-800/30">
                                <td class="py-3 px-4 font-bold text-white"><?= e($ts['name']) ?></td>
                                <td class="py-3 px-4 text-slate-400"><?= e($ts['category_name'] ?? 'General') ?></td>
                                <td class="py-3 px-4 text-center font-mono font-bold text-purple-400"><?= number_format($ts['order_count']) ?></td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-emerald-400"><?= format_currency($ts['gross_charge']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="py-8 text-center text-xs text-slate-500">
                No orders completed yet. Top services report will populate once orders are executed.
            </div>
        <?php endif; ?>
    </div>

    <!-- Monthly Order Statistics -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 backdrop-blur-xl shadow-xl space-y-4">
        <h3 class="text-sm font-bold text-white uppercase tracking-wider">Monthly Performance Breakdown</h3>

        <?php if (!empty($monthlyOrders)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="text-xs uppercase text-slate-400 border-b border-slate-800 pb-2">
                        <tr>
                            <th class="py-3 px-4">Month</th>
                            <th class="py-3 px-4 text-center">Orders Processed</th>
                            <th class="py-3 px-4 text-right">Total Charged</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-xs">
                        <?php foreach ($monthlyOrders as $mo): ?>
                            <tr class="hover:bg-slate-800/30">
                                <td class="py-3 px-4 font-mono font-bold text-white"><?= e($mo['order_month']) ?></td>
                                <td class="py-3 px-4 text-center font-mono"><?= number_format($mo['count']) ?></td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-emerald-400"><?= format_currency($mo['revenue']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="py-8 text-center text-xs text-slate-500">
                No monthly order history available.
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
