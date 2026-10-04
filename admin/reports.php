<?php
/**
 * ApexSMM Admin - Platform Analytics & Financial Reports
 * White + Premium Purple Design System
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
        <h1 class="text-2xl font-bold text-[#18181B] tracking-tight">Platform Reports & Performance</h1>
        <p class="text-xs text-[#71717A] mt-1">Aggregated operational metrics computed directly from verified database records.</p>
    </div>

    <!-- Financial Ledger Summary -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <div class="p-6 rounded-2xl bg-white border border-[#E4E4E7] shadow-sm">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <?= icon('banknotes', 'w-4 h-4') ?>
                </div>
                <span class="text-xs font-semibold text-[#71717A]">Verified Deposits</span>
            </div>
            <span class="text-2xl font-bold font-mono text-emerald-600 block"><?= format_currency($grossRevenue) ?></span>
        </div>

        <div class="p-6 rounded-2xl bg-white border border-[#E4E4E7] shadow-sm">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <?= icon('shopping-cart', 'w-4 h-4') ?>
                </div>
                <span class="text-xs font-semibold text-[#71717A]">Order Volume</span>
            </div>
            <span class="text-2xl font-bold font-mono text-[#18181B] block"><?= format_currency($totalOrderSpent) ?></span>
        </div>

        <div class="p-6 rounded-2xl bg-white border border-[#E4E4E7] shadow-sm">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <?= icon('arrow-path', 'w-4 h-4') ?>
                </div>
                <span class="text-xs font-semibold text-[#71717A]">Total Refunds</span>
            </div>
            <span class="text-2xl font-bold font-mono text-amber-600 block"><?= format_currency($totalRefunds) ?></span>
        </div>

        <div class="p-6 rounded-2xl bg-white border border-[#E4E4E7] shadow-sm">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 rounded-lg bg-[#F3E8FF] text-[#7C3AED] flex items-center justify-center">
                    <?= icon('wallet', 'w-4 h-4') ?>
                </div>
                <span class="text-xs font-semibold text-[#71717A]">User Balances</span>
            </div>
            <span class="text-2xl font-bold font-mono text-[#7C3AED] block"><?= format_currency($totalUserBal) ?></span>
        </div>
    </div>

    <!-- Top Performing Services -->
    <div class="bg-white border border-[#E4E4E7] rounded-2xl shadow-sm overflow-hidden">
        <div class="p-5 border-b border-[#F4F4F5] flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-[#FAF5FF] text-[#7C3AED] flex items-center justify-center">
                    <?= icon('fire', 'w-4 h-4') ?>
                </div>
                <h3 class="text-sm font-bold text-[#18181B]">Top Services By Order Volume</h3>
            </div>
        </div>

        <?php if (!empty($topServices)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-[#18181B]">
                    <thead class="text-xs uppercase text-[#71717A] bg-[#FAF5FF]/50 border-b border-[#E4E4E7]">
                        <tr>
                            <th class="py-3 px-5 font-semibold">Service</th>
                            <th class="py-3 px-5 font-semibold">Category</th>
                            <th class="py-3 px-5 text-center font-semibold">Orders Placed</th>
                            <th class="py-3 px-5 text-right font-semibold">Gross Charged</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#F4F4F5] text-xs">
                        <?php foreach ($topServices as $ts): ?>
                            <tr class="hover:bg-[#FAF5FF]/30 transition-colors">
                                <td class="py-3.5 px-5 font-bold text-[#18181B]"><?= e($ts['name']) ?></td>
                                <td class="py-3.5 px-5 text-[#71717A]"><?= e($ts['category_name'] ?? 'General') ?></td>
                                <td class="py-3.5 px-5 text-center font-mono font-bold text-[#7C3AED]"><?= number_format($ts['order_count']) ?></td>
                                <td class="py-3.5 px-5 text-right font-mono font-bold text-emerald-600"><?= format_currency($ts['gross_charge']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="py-16 text-center text-xs text-[#71717A] flex flex-col items-center justify-center gap-2">
                <div class="w-12 h-12 rounded-full bg-[#FAF5FF] flex items-center justify-center text-[#A1A1AA]">
                    <?= icon('chart-bar', 'w-6 h-6') ?>
                </div>
                <p class="font-medium text-[#18181B]">No service volume data yet</p>
                <p class="text-[11px]">Rankings will automatically calculate as clients place orders.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Monthly Order Statistics -->
    <div class="bg-white border border-[#E4E4E7] rounded-2xl shadow-sm overflow-hidden">
        <div class="p-5 border-b border-[#F4F4F5] flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-[#FAF5FF] text-[#7C3AED] flex items-center justify-center">
                    <?= icon('calendar', 'w-4 h-4') ?>
                </div>
                <h3 class="text-sm font-bold text-[#18181B]">Monthly Performance Breakdown</h3>
            </div>
        </div>

        <?php if (!empty($monthlyOrders)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-[#18181B]">
                    <thead class="text-xs uppercase text-[#71717A] bg-[#FAF5FF]/50 border-b border-[#E4E4E7]">
                        <tr>
                            <th class="py-3 px-5 font-semibold">Month</th>
                            <th class="py-3 px-5 text-center font-semibold">Orders Processed</th>
                            <th class="py-3 px-5 text-right font-semibold">Total Charged</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#F4F4F5] text-xs">
                        <?php foreach ($monthlyOrders as $mo): ?>
                            <tr class="hover:bg-[#FAF5FF]/30 transition-colors">
                                <td class="py-3.5 px-5 font-mono font-bold text-[#18181B]"><?= e($mo['order_month']) ?></td>
                                <td class="py-3.5 px-5 text-center font-mono font-medium"><?= number_format($mo['count']) ?></td>
                                <td class="py-3.5 px-5 text-right font-mono font-bold text-emerald-600"><?= format_currency($mo['revenue']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="py-16 text-center text-xs text-[#71717A] flex flex-col items-center justify-center gap-2">
                <div class="w-12 h-12 rounded-full bg-[#FAF5FF] flex items-center justify-center text-[#A1A1AA]">
                    <?= icon('calendar', 'w-6 h-6') ?>
                </div>
                <p class="font-medium text-[#18181B]">No monthly order history available</p>
                <p class="text-[11px]">Monthly aggregations will appear as order history builds up.</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
