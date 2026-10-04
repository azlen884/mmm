<?php
/**
 * ApexSMM Admin Dashboard Overview
 * White + Premium Purple Design System
 * Real MySQL statistics, operational health, and real revenue charts.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

AdminAuth::requireAdmin();

// Real System Metrics from MySQL
$metrics = [
    'total_users'      => (int)Database::fetchValue("SELECT COUNT(*) FROM users"),
    'active_users'     => (int)Database::fetchValue("SELECT COUNT(*) FROM users WHERE status = 'active'"),
    'total_orders'     => (int)Database::fetchValue("SELECT COUNT(*) FROM orders"),
    'pending_orders'   => (int)Database::fetchValue("SELECT COUNT(*) FROM orders WHERE status = 'pending'"),
    'processing_orders'=> (int)Database::fetchValue("SELECT COUNT(*) FROM orders WHERE status IN ('processing', 'in_progress')"),
    'completed_orders' => (int)Database::fetchValue("SELECT COUNT(*) FROM orders WHERE status = 'completed'"),
    'failed_orders'    => (int)Database::fetchValue("SELECT COUNT(*) FROM orders WHERE status = 'failed'"),
    'total_revenue'    => (float)Database::fetchValue("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed'"),
    'user_balances'    => (float)Database::fetchValue("SELECT COALESCE(SUM(balance), 0) FROM users"),
    'pending_payments' => (int)Database::fetchValue("SELECT COUNT(*) FROM payments WHERE status = 'pending'"),
    'open_tickets'     => (int)Database::fetchValue("SELECT COUNT(*) FROM tickets WHERE status IN ('open', 'customer_reply')"),
];

// Recent Orders (Real data only)
$recentOrders = Database::fetchAll(
    "SELECT o.*, u.username, s.name as service_name 
     FROM orders o 
     JOIN users u ON o.user_id = u.id 
     LEFT JOIN services s ON o.service_id = s.id 
     ORDER BY o.id DESC 
     LIMIT 5"
);

// Recent Users (Real data only)
$recentUsers = Database::fetchAll(
    "SELECT id, username, email, balance, status, created_at 
     FROM users 
     ORDER BY id DESC 
     LIMIT 5"
);

// Real 7-day daily revenue from completed payments
$revenueRows = Database::fetchAll(
    "SELECT DATE(created_at) as pay_date, SUM(amount) as total 
     FROM payments 
     WHERE status = 'completed' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) 
     GROUP BY DATE(created_at) 
     ORDER BY pay_date ASC"
);

$chartMap = [];
foreach ($revenueRows as $rr) {
    $chartMap[$rr['pay_date']] = (float)$rr['total'];
}

$chartDates = [];
$chartRevenues = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $chartDates[] = date('M d', strtotime($d));
    $chartRevenues[] = $chartMap[$d] ?? 0.00;
}

$hasRevenueData = array_sum($chartRevenues) > 0;
$hasCharts = $hasRevenueData; // Only load ApexCharts asset if chart renders

$activeNav = 'dashboard';
$pageTitle = 'Admin Dashboard | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 gsap-fade-in">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 tracking-tight">System Control Panel</h1>
            <p class="text-xs text-zinc-500 mt-1">Live platform statistics aggregated directly from the central database.</p>
        </div>
        <div class="flex items-center space-x-2.5">
            <a href="/admin/service-import.php" class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl bg-purple-600 text-white text-xs font-semibold hover:bg-purple-700 shadow-xs transition-colors">
                <?= icon('plus', 'w-4 h-4') ?>
                <span>Import Services</span>
            </a>
            <a href="/admin/orders.php" class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl bg-white border border-purple-200 text-purple-700 text-xs font-semibold hover:bg-purple-50 shadow-2xs transition-colors">
                <?= icon('shopping-cart', 'w-4 h-4 text-purple-600') ?>
                <span>Manage Orders</span>
            </a>
        </div>
    </div>

    <!-- Metrics Cards Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Total Revenue -->
        <div class="bg-white border border-zinc-200/80 rounded-2xl p-5 shadow-xs gsap-card">
            <div class="text-[11px] font-semibold text-zinc-500 uppercase tracking-wider mb-1.5">Verified Revenue</div>
            <div class="text-2xl font-extrabold text-emerald-700 font-mono tabular-nums"><?= format_currency($metrics['total_revenue']) ?></div>
            <div class="text-[11px] text-zinc-400 mt-1"><?= $metrics['pending_payments'] ?> pending deposits</div>
        </div>

        <!-- Total Orders -->
        <div class="bg-white border border-zinc-200/80 rounded-2xl p-5 shadow-xs gsap-card">
            <div class="text-[11px] font-semibold text-zinc-500 uppercase tracking-wider mb-1.5">Orders Placed</div>
            <div class="text-2xl font-extrabold text-zinc-900 font-mono tabular-nums"><?= number_format($metrics['total_orders']) ?></div>
            <div class="text-[11px] text-zinc-400 mt-1"><?= $metrics['pending_orders'] ?> pending &bull; <?= $metrics['processing_orders'] ?> in progress</div>
        </div>

        <!-- User Accounts -->
        <div class="bg-white border border-zinc-200/80 rounded-2xl p-5 shadow-xs gsap-card">
            <div class="text-[11px] font-semibold text-zinc-500 uppercase tracking-wider mb-1.5">Registered Users</div>
            <div class="text-2xl font-extrabold text-purple-700 font-mono tabular-nums"><?= number_format($metrics['total_users']) ?></div>
            <div class="text-[11px] text-zinc-400 mt-1"><?= format_currency($metrics['user_balances']) ?> in user wallets</div>
        </div>

        <!-- Open Tickets -->
        <div class="bg-white border border-zinc-200/80 rounded-2xl p-5 shadow-xs gsap-card">
            <div class="text-[11px] font-semibold text-zinc-500 uppercase tracking-wider mb-1.5">Open Support Tickets</div>
            <div class="text-2xl font-extrabold text-amber-700 font-mono tabular-nums"><?= number_format($metrics['open_tickets']) ?></div>
            <div class="text-[11px] text-zinc-400 mt-1">Pending staff responses</div>
        </div>

    </div>

    <!-- Chart Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Revenue Trends (ApexCharts Real Data) -->
        <div class="lg:col-span-2 bg-white border border-zinc-200/80 rounded-2xl p-6 shadow-xs gsap-card">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">7-Day Revenue Volume</h3>
                    <p class="text-xs text-zinc-500">Verified payment deposits received</p>
                </div>
            </div>

            <?php if ($hasRevenueData): ?>
                <div id="adminRevenueChart" class="w-full h-64"></div>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        if (typeof ApexCharts === 'undefined') return;
                        const options = {
                            series: [{
                                name: 'Gross Revenue ($)',
                                data: <?= json_encode($chartRevenues) ?>
                            }],
                            chart: {
                                type: 'bar',
                                height: 240,
                                background: 'transparent',
                                toolbar: { show: false },
                                animations: { enabled: !window.matchMedia('(prefers-reduced-motion: reduce)').matches }
                            },
                            colors: ['#7C3AED'],
                            plotOptions: {
                                bar: {
                                    borderRadius: 6,
                                    columnWidth: '35%'
                                }
                            },
                            dataLabels: { enabled: false },
                            xaxis: {
                                categories: <?= json_encode($chartDates) ?>,
                                labels: { style: { colors: '#71717A', fontSize: '11px', fontFamily: 'inherit' } },
                                axisBorder: { show: false },
                                axisTicks: { show: false }
                            },
                            yaxis: {
                                labels: { 
                                    style: { colors: '#71717A', fontSize: '11px', fontFamily: 'inherit' },
                                    formatter: val => '$' + Number(val).toFixed(2)
                                }
                            },
                            grid: { borderColor: '#E4E4E7', strokeDashArray: 3 },
                            theme: { mode: 'light' },
                            tooltip: { theme: 'light' }
                        };
                        const chart = new ApexCharts(document.querySelector("#adminRevenueChart"), options);
                        chart.render();
                    });
                </script>
            <?php else: ?>
                <div class="h-60 flex flex-col items-center justify-center text-center p-6 border border-dashed border-zinc-200 rounded-xl">
                    <?= icon('currency-dollar', 'w-8 h-8 text-zinc-400 mb-2') ?>
                    <p class="text-xs font-semibold text-zinc-700">No revenue records in the past 7 days</p>
                    <p class="text-[11px] text-zinc-400 mt-0.5">Confirmed client payments will chart here automatically.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick System Status -->
        <div class="bg-white border border-zinc-200/80 rounded-2xl p-6 shadow-xs flex flex-col justify-between gsap-card">
            <div>
                <h3 class="text-xs font-bold text-zinc-900 uppercase tracking-wider mb-4">Operations Status</h3>
                
                <div class="space-y-2.5 text-xs">
                    <div class="flex items-center justify-between p-3 rounded-xl bg-zinc-50 border border-zinc-200/80">
                        <span class="text-zinc-600 font-medium">Database Engine</span>
                        <span class="font-mono text-emerald-700 font-bold">MariaDB / MySQL</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-xl bg-zinc-50 border border-zinc-200/80">
                        <span class="text-zinc-600 font-medium">Active Gateways</span>
                        <span class="font-mono text-purple-700 font-bold">
                            <?= (int)Database::fetchValue("SELECT COUNT(*) FROM payment_gateways WHERE status = 'active'") ?>
                        </span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-xl bg-zinc-50 border border-zinc-200/80">
                        <span class="text-zinc-600 font-medium">Connected Providers</span>
                        <span class="font-mono text-purple-700 font-bold">
                            <?= (int)Database::fetchValue("SELECT COUNT(*) FROM providers WHERE status = 'active'") ?>
                        </span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-xl bg-zinc-50 border border-zinc-200/80">
                        <span class="text-zinc-600 font-medium">Active Services</span>
                        <span class="font-mono text-purple-700 font-bold">
                            <?= (int)Database::fetchValue("SELECT COUNT(*) FROM services WHERE status = 'active'") ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="pt-5 mt-4 border-t border-zinc-100">
                <a href="/admin/settings.php" class="w-full inline-flex items-center justify-center space-x-1.5 py-2.5 rounded-xl bg-purple-50 border border-purple-200 text-purple-700 text-xs font-semibold hover:bg-purple-100/80 transition-colors shadow-2xs">
                    <?= icon('cog', 'w-3.5 h-3.5') ?>
                    <span>System Configuration</span>
                </a>
            </div>
        </div>

    </div>

    <!-- Recent Orders Table -->
    <div class="bg-white border border-zinc-200/80 rounded-2xl p-6 shadow-xs gsap-card">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Latest Platform Orders</h3>
            <a href="/admin/orders.php" class="inline-flex items-center space-x-1 text-xs font-semibold text-purple-700 hover:text-purple-800">
                <span>View All</span>
                <?= icon('chevron-right', 'w-3.5 h-3.5') ?>
            </a>
        </div>

        <?php if (!empty($recentOrders)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-zinc-700">
                    <thead class="text-xs font-semibold uppercase tracking-wider text-zinc-500 border-b border-zinc-200 pb-2">
                        <tr>
                            <th class="py-3 px-4">Order ID</th>
                            <th class="py-3 px-4">User</th>
                            <th class="py-3 px-4">Service</th>
                            <th class="py-3 px-4">Charge</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200/70">
                        <?php foreach ($recentOrders as $ro): ?>
                            <tr class="hover:bg-purple-50/30 text-xs transition-colors">
                                <td class="py-3 px-4 font-mono text-zinc-500">#<?= (int)$ro['id'] ?></td>
                                <td class="py-3 px-4 font-bold text-zinc-900"><?= e($ro['username']) ?></td>
                                <td class="py-3 px-4 text-zinc-600 max-w-xs truncate"><?= e($ro['service_name'] ?? 'Direct Service') ?></td>
                                <td class="py-3 px-4 font-mono font-semibold text-purple-700"><?= format_currency($ro['charge']) ?></td>
                                <td class="py-3 px-4 text-center"><?= status_badge($ro['status']) ?></td>
                                <td class="py-3 px-4 text-right">
                                    <a href="/admin/order-view.php?id=<?= (int)$ro['id'] ?>" class="inline-flex items-center space-x-1 text-purple-700 hover:text-purple-800 font-semibold">
                                        <?= icon('eye', 'w-3.5 h-3.5') ?>
                                        <span>Inspect</span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="py-8 text-center text-xs text-zinc-500 border border-dashed border-zinc-200 rounded-xl">
                No orders found on the system.
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
