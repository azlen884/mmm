<?php
/**
 * ApexSMM Admin Dashboard Overview
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

$activeNav = 'dashboard';
$pageTitle = 'Admin Dashboard | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">System Control Panel</h1>
            <p class="text-xs text-slate-400 mt-1">Live platform statistics aggregated directly from the central database.</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="/admin/service-import.php" class="px-4 py-2 rounded-xl bg-purple-600 text-white text-xs font-semibold hover:bg-purple-500 shadow-md shadow-purple-500/20">
                + Import Services
            </a>
            <a href="/admin/orders.php" class="px-4 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs font-semibold text-slate-300 hover:bg-slate-800">
                Manage Orders
            </a>
        </div>
    </div>

    <!-- Metrics Cards Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        
        <!-- Total Revenue -->
        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5 backdrop-blur-md">
            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Total Verified Revenue</div>
            <div class="text-2xl font-black text-emerald-400 font-mono"><?= format_currency($metrics['total_revenue']) ?></div>
            <div class="text-[11px] text-slate-500 mt-1"><?= $metrics['pending_payments'] ?> pending payments</div>
        </div>

        <!-- Total Orders -->
        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5 backdrop-blur-md">
            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Total Orders Placed</div>
            <div class="text-2xl font-black text-white font-mono"><?= number_format($metrics['total_orders']) ?></div>
            <div class="text-[11px] text-slate-500 mt-1"><?= $metrics['pending_orders'] ?> pending &bull; <?= $metrics['processing_orders'] ?> processing</div>
        </div>

        <!-- User Accounts -->
        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5 backdrop-blur-md">
            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Registered Users</div>
            <div class="text-2xl font-black text-purple-400 font-mono"><?= number_format($metrics['total_users']) ?></div>
            <div class="text-[11px] text-slate-500 mt-1"><?= format_currency($metrics['user_balances']) ?> stored in wallets</div>
        </div>

        <!-- Open Tickets -->
        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5 backdrop-blur-md">
            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Open Support Tickets</div>
            <div class="text-2xl font-black text-amber-400 font-mono"><?= number_format($metrics['open_tickets']) ?></div>
            <div class="text-[11px] text-slate-500 mt-1">Pending staff responses</div>
        </div>

    </div>

    <!-- Chart Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Revenue Trends (ApexCharts Real Data) -->
        <div class="lg:col-span-2 bg-slate-900/70 border border-slate-800 rounded-3xl p-6 backdrop-blur-xl">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">7-Day Revenue Volume</h3>
                    <p class="text-xs text-slate-400">Verified payment deposits received</p>
                </div>
            </div>

            <?php if ($hasRevenueData): ?>
                <div id="adminRevenueChart" class="w-full h-64"></div>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const options = {
                            series: [{
                                name: 'Gross Revenue ($)',
                                data: <?= json_encode($chartRevenues) ?>
                            }],
                            chart: {
                                type: 'bar',
                                height: 250,
                                background: 'transparent',
                                toolbar: { show: false }
                            },
                            colors: ['#a855f7'],
                            plotOptions: {
                                bar: {
                                    borderRadius: 6,
                                    columnWidth: '40%'
                                }
                            },
                            dataLabels: { enabled: false },
                            xaxis: {
                                categories: <?= json_encode($chartDates) ?>,
                                labels: { style: { colors: '#64748b', fontSize: '11px' } },
                                axisBorder: { show: false },
                                axisTicks: { show: false }
                            },
                            yaxis: {
                                labels: { 
                                    style: { colors: '#64748b', fontSize: '11px' },
                                    formatter: val => '$' + val.toFixed(2)
                                }
                            },
                            grid: { borderColor: '#1e293b', strokeDashArray: 3 },
                            theme: { mode: 'dark' },
                            tooltip: { theme: 'dark' }
                        };
                        const chart = new ApexCharts(document.querySelector("#adminRevenueChart"), options);
                        chart.render();
                    });
                </script>
            <?php else: ?>
                <div class="h-64 flex flex-col items-center justify-center text-center p-6 border border-dashed border-slate-800 rounded-2xl">
                    <svg class="w-8 h-8 text-slate-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm font-semibold text-slate-300">No revenue data in the past 7 days</p>
                    <p class="text-xs text-slate-500 mt-1">Confirmed client payments will chart here automatically.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick System Status -->
        <div class="bg-slate-900/70 border border-slate-800 rounded-3xl p-6 backdrop-blur-xl flex flex-col justify-between">
            <div>
                <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-4">Operations Status</h3>
                
                <div class="space-y-3 text-xs">
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-950 border border-slate-800">
                        <span class="text-slate-400">Database Engine</span>
                        <span class="font-mono text-emerald-400 font-bold">MySQL / MariaDB</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-950 border border-slate-800">
                        <span class="text-slate-400">Active Gateways</span>
                        <span class="font-mono text-white font-bold">
                            <?= (int)Database::fetchValue("SELECT COUNT(*) FROM payment_gateways WHERE status = 'active'") ?>
                        </span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-950 border border-slate-800">
                        <span class="text-slate-400">Connected Providers</span>
                        <span class="font-mono text-white font-bold">
                            <?= (int)Database::fetchValue("SELECT COUNT(*) FROM providers WHERE status = 'active'") ?>
                        </span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-950 border border-slate-800">
                        <span class="text-slate-400">Active Services</span>
                        <span class="font-mono text-white font-bold">
                            <?= (int)Database::fetchValue("SELECT COUNT(*) FROM services WHERE status = 'active'") ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="pt-6 border-t border-slate-800">
                <a href="/admin/settings.php" class="w-full block text-center py-2.5 rounded-xl bg-purple-600/15 border border-purple-500/30 text-purple-300 text-xs font-semibold hover:bg-purple-600 hover:text-white transition-all">
                    System Configuration &rarr;
                </a>
            </div>
        </div>

    </div>

    <!-- Recent Orders Table -->
    <div class="bg-slate-900/70 border border-slate-800 rounded-3xl p-6 backdrop-blur-xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider">Latest Platform Orders</h3>
            <a href="/admin/orders.php" class="text-xs font-semibold text-purple-400 hover:text-purple-300">View All &rarr;</a>
        </div>

        <?php if (!empty($recentOrders)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="text-xs uppercase text-slate-400 border-b border-slate-800 pb-2">
                        <tr>
                            <th class="py-3 px-4">Order ID</th>
                            <th class="py-3 px-4">User</th>
                            <th class="py-3 px-4">Service</th>
                            <th class="py-3 px-4">Charge</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <?php foreach ($recentOrders as $ro): ?>
                            <tr class="hover:bg-slate-800/30 text-xs">
                                <td class="py-3 px-4 font-mono text-slate-400">#<?= (int)$ro['id'] ?></td>
                                <td class="py-3 px-4 font-bold text-white"><?= e($ro['username']) ?></td>
                                <td class="py-3 px-4 text-slate-300 max-w-xs truncate"><?= e($ro['service_name'] ?? 'Direct Service') ?></td>
                                <td class="py-3 px-4 font-mono font-semibold text-emerald-400"><?= format_currency($ro['charge']) ?></td>
                                <td class="py-3 px-4 text-center"><?= status_badge($ro['status']) ?></td>
                                <td class="py-3 px-4 text-right">
                                    <a href="/admin/order-view.php?id=<?= (int)$ro['id'] ?>" class="text-purple-400 hover:text-purple-300 font-semibold">Inspect &rarr;</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="py-8 text-center text-xs text-slate-500 border border-dashed border-slate-800 rounded-2xl">
                No orders found on the system.
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
