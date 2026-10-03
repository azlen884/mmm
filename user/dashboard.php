<?php
/**
 * ApexSMM User Dashboard
 * Real-time order metrics, wallet statistics, and actual database analytics.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireLogin();
$user = Auth::user();
$userId = (int)$user['id'];

// Real Order Statistics from MySQL
$orderCounts = [
    'total'       => (int)Database::fetchValue("SELECT COUNT(*) FROM orders WHERE user_id = ?", [$userId]),
    'completed'   => (int)Database::fetchValue("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = 'completed'", [$userId]),
    'pending'     => (int)Database::fetchValue("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = 'pending'", [$userId]),
    'processing'  => (int)Database::fetchValue("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status IN ('processing', 'in_progress')", [$userId]),
    'cancelled'   => (int)Database::fetchValue("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status IN ('cancelled', 'refunded')", [$userId]),
    'failed'      => (int)Database::fetchValue("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = 'failed'", [$userId]),
];

// Recent Orders (Real data only)
$recentOrders = Database::fetchAll(
    "SELECT o.*, s.name as service_name 
     FROM orders o 
     LEFT JOIN services s ON o.service_id = s.id 
     WHERE o.user_id = ? 
     ORDER BY o.id DESC 
     LIMIT 5",
    [$userId]
);

// Real 7-day Historical Order Metrics for ApexCharts
$chartRows = Database::fetchAll(
    "SELECT DATE(created_at) as order_date, COUNT(*) as count, SUM(charge) as total_charge 
     FROM orders 
     WHERE user_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) 
     GROUP BY DATE(created_at) 
     ORDER BY order_date ASC",
    [$userId]
);

$chartDates = [];
$chartCounts = [];
$chartMap = [];
foreach ($chartRows as $cr) {
    $chartMap[$cr['order_date']] = (int)$cr['count'];
}

// Generate continuous 7 days
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $chartDates[] = date('M d', strtotime($d));
    $chartCounts[] = $chartMap[$d] ?? 0;
}

$hasChartData = array_sum($chartCounts) > 0;

$activeNav = 'dashboard';
$pageTitle = 'Dashboard | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-8">
    
    <!-- Welcome Header Banner -->
    <div class="rounded-3xl bg-gradient-to-r from-blue-900/40 via-indigo-900/30 to-purple-900/40 border border-blue-500/20 p-6 sm:p-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 backdrop-blur-xl">
        <div>
            <div class="text-xs font-semibold text-blue-400 uppercase tracking-widest mb-1">Account Overview</div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                Welcome back, <span class="text-blue-400"><?= e($user['username']) ?></span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-300 mt-1">
                Your wallet has <strong class="text-emerald-400 font-mono"><?= format_currency($user['balance']) ?></strong> available for automated order placement.
            </p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="/user/new-order.php" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold text-sm hover:from-blue-500 hover:to-indigo-500 transition-all shadow-lg shadow-blue-500/25">
                + New Order
            </a>
            <a href="/user/add-funds.php" class="px-5 py-2.5 rounded-xl bg-slate-900 border border-slate-700/80 text-slate-200 font-semibold text-sm hover:bg-slate-800 transition-all">
                Add Balance
            </a>
        </div>
    </div>

    <!-- Metrics Cards Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        
        <!-- Balance -->
        <div class="bg-slate-900/70 border border-slate-800/90 rounded-2xl p-5 backdrop-blur-md">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Account Balance</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-black text-emerald-400 font-mono"><?= format_currency($user['balance']) ?></div>
            <div class="text-[11px] text-slate-500 mt-1">Available spending funds</div>
        </div>

        <!-- Total Orders -->
        <div class="bg-slate-900/70 border border-slate-800/90 rounded-2xl p-5 backdrop-blur-md">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Orders</span>
                <div class="w-8 h-8 rounded-lg bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-black text-white font-mono"><?= number_format($orderCounts['total']) ?></div>
            <div class="text-[11px] text-slate-500 mt-1"><?= number_format($orderCounts['completed']) ?> completed</div>
        </div>

        <!-- Pending / Processing -->
        <div class="bg-slate-900/70 border border-slate-800/90 rounded-2xl p-5 backdrop-blur-md">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">In Progress</span>
                <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-black text-amber-400 font-mono"><?= number_format($orderCounts['pending'] + $orderCounts['processing']) ?></div>
            <div class="text-[11px] text-slate-500 mt-1"><?= number_format($orderCounts['pending']) ?> pending dispatch</div>
        </div>

        <!-- Total Spent -->
        <div class="bg-slate-900/70 border border-slate-800/90 rounded-2xl p-5 backdrop-blur-md">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Lifetime Spent</span>
                <div class="w-8 h-8 rounded-lg bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-black text-purple-400 font-mono"><?= format_currency($user['spent']) ?></div>
            <div class="text-[11px] text-slate-500 mt-1">Completed purchases</div>
        </div>

    </div>

    <!-- Chart & Order Activity Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- 7-Day Order Volume (ApexCharts using real data) -->
        <div class="lg:col-span-2 bg-slate-900/70 border border-slate-800 rounded-3xl p-6 backdrop-blur-xl">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-base font-bold text-white">7-Day Order Volume</h3>
                    <p class="text-xs text-slate-400">Order activity over the last 7 calendar days</p>
                </div>
            </div>

            <?php if ($hasChartData): ?>
                <div id="userOrderChart" class="w-full h-64"></div>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const options = {
                            series: [{
                                name: 'Orders Placed',
                                data: <?= json_encode($chartCounts) ?>
                            }],
                            chart: {
                                type: 'area',
                                height: 260,
                                background: 'transparent',
                                toolbar: { show: false }
                            },
                            colors: ['#3b82f6'],
                            fill: {
                                type: 'gradient',
                                gradient: {
                                    shadeIntensity: 1,
                                    opacityFrom: 0.45,
                                    opacityTo: 0.05,
                                    stops: [20, 100]
                                }
                            },
                            dataLabels: { enabled: false },
                            stroke: { curve: 'smooth', width: 2 },
                            xaxis: {
                                categories: <?= json_encode($chartDates) ?>,
                                labels: { style: { colors: '#64748b', fontSize: '11px' } },
                                axisBorder: { show: false },
                                axisTicks: { show: false }
                            },
                            yaxis: {
                                labels: { style: { colors: '#64748b', fontSize: '11px' } }
                            },
                            grid: {
                                borderColor: '#1e293b',
                                strokeDashArray: 3
                            },
                            theme: { mode: 'dark' },
                            tooltip: { theme: 'dark' }
                        };
                        const chart = new ApexCharts(document.querySelector("#userOrderChart"), options);
                        chart.render();
                    });
                </script>
            <?php else: ?>
                <div class="h-64 flex flex-col items-center justify-center text-center p-6 border border-dashed border-slate-800 rounded-2xl">
                    <svg class="w-8 h-8 text-slate-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                    </svg>
                    <p class="text-sm font-semibold text-slate-300">No chart data for the past 7 days</p>
                    <p class="text-xs text-slate-500 mt-1">Once you place orders, real volume graphs will display here.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Status Breakdown -->
        <div class="bg-slate-900/70 border border-slate-800 rounded-3xl p-6 backdrop-blur-xl flex flex-col justify-between">
            <div>
                <h3 class="text-base font-bold text-white mb-1">Order Status Distribution</h3>
                <p class="text-xs text-slate-400 mb-6">Current breakdown of all lifetime orders</p>
                
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-950/60 border border-slate-800/60 text-xs">
                        <div class="flex items-center space-x-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                            <span class="text-slate-300 font-medium">Completed</span>
                        </div>
                        <span class="font-mono font-bold text-white"><?= number_format($orderCounts['completed']) ?></span>
                    </div>

                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-950/60 border border-slate-800/60 text-xs">
                        <div class="flex items-center space-x-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-400"></span>
                            <span class="text-slate-300 font-medium">Processing</span>
                        </div>
                        <span class="font-mono font-bold text-white"><?= number_format($orderCounts['processing']) ?></span>
                    </div>

                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-950/60 border border-slate-800/60 text-xs">
                        <div class="flex items-center space-x-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                            <span class="text-slate-300 font-medium">Pending</span>
                        </div>
                        <span class="font-mono font-bold text-white"><?= number_format($orderCounts['pending']) ?></span>
                    </div>

                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-950/60 border border-slate-800/60 text-xs">
                        <div class="flex items-center space-x-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span>
                            <span class="text-slate-300 font-medium">Cancelled / Refunded</span>
                        </div>
                        <span class="font-mono font-bold text-white"><?= number_format($orderCounts['cancelled']) ?></span>
                    </div>
                </div>
            </div>

            <div class="pt-6 mt-6 border-t border-slate-800">
                <a href="/user/orders.php" class="w-full block text-center py-2.5 rounded-xl bg-slate-800 text-xs font-semibold text-slate-300 hover:text-white hover:bg-slate-700 transition-colors">
                    View Complete Order History &rarr;
                </a>
            </div>
        </div>

    </div>

    <!-- Recent Orders (Real records from MySQL) -->
    <div class="bg-slate-900/70 border border-slate-800 rounded-3xl p-6 backdrop-blur-xl">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-base font-bold text-white">Recent Orders</h3>
                <p class="text-xs text-slate-400">Latest social media marketing submissions</p>
            </div>
            <a href="/user/orders.php" class="text-xs font-semibold text-blue-400 hover:text-blue-300 transition-colors">
                View all &rarr;
            </a>
        </div>

        <?php if (!empty($recentOrders)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="text-xs uppercase text-slate-400 border-b border-slate-800 pb-3">
                        <tr>
                            <th class="py-3 px-4">Order ID</th>
                            <th class="py-3 px-4">Service</th>
                            <th class="py-3 px-4">Quantity</th>
                            <th class="py-3 px-4">Charge</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4 text-right">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <?php foreach ($recentOrders as $ro): ?>
                            <tr class="hover:bg-slate-800/30 transition-colors">
                                <td class="py-3 px-4 font-mono text-xs text-slate-400">#<?= (int)$ro['id'] ?></td>
                                <td class="py-3 px-4 font-medium text-white max-w-xs truncate">
                                    <?= e($ro['service_name'] ?? 'Direct Service #' . $ro['service_id']) ?>
                                </td>
                                <td class="py-3 px-4 font-mono text-xs text-slate-300"><?= number_format($ro['quantity']) ?></td>
                                <td class="py-3 px-4 font-mono text-xs font-semibold text-emerald-400"><?= format_currency($ro['charge']) ?></td>
                                <td class="py-3 px-4 text-center"><?= status_badge($ro['status']) ?></td>
                                <td class="py-3 px-4 text-xs text-slate-400"><?= time_ago($ro['created_at']) ?></td>
                                <td class="py-3 px-4 text-right">
                                    <a href="/user/order-details.php?id=<?= (int)$ro['id'] ?>" class="text-xs text-blue-400 hover:text-blue-300 font-semibold">
                                        View &rarr;
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="py-12 text-center border border-dashed border-slate-800 rounded-2xl">
                <div class="w-12 h-12 rounded-xl bg-slate-800/80 flex items-center justify-center mx-auto mb-3 text-slate-500">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </div>
                <h4 class="text-sm font-semibold text-white mb-1">No orders found</h4>
                <p class="text-xs text-slate-500 mb-4">You have not submitted any orders yet.</p>
                <a href="/user/new-order.php" class="inline-flex items-center px-4 py-2 rounded-xl bg-blue-600 text-white text-xs font-semibold hover:bg-blue-500 transition-colors shadow-md shadow-blue-500/20">
                    Place First Order &rarr;
                </a>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
