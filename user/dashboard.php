<?php
/**
 * ApexSMM User Dashboard
 * White + Premium Purple Design System
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

// Recent Orders (Real records from MySQL)
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

$hasCharts = true; // Load ApexCharts only on this page
$activeNav = 'dashboard';
$pageTitle = 'Dashboard | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">
    
    <!-- Welcome Header Banner -->
    <div class="rounded-3xl bg-gradient-to-r from-purple-50 via-white to-purple-50/50 border border-purple-100 p-6 sm:p-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-xs">
        <div>
            <div class="text-xs font-bold text-purple-700 uppercase tracking-wider mb-1 flex items-center space-x-1.5">
                <?= icon('sparkles', 'w-4 h-4 text-purple-600') ?>
                <span>Account Overview</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-zinc-900 tracking-tight">
                Welcome back, <span class="text-purple-700"><?= e($user['username']) ?></span>
            </h1>
            <p class="text-xs sm:text-sm text-zinc-500 mt-1">
                Your wallet has <strong class="text-emerald-600 font-mono"><?= format_currency((float)$user['balance']) ?></strong> available for automated order placement.
            </p>
        </div>
        <div class="flex items-center space-x-3 shrink-0">
            <a href="/user/new-order.php" class="inline-flex items-center space-x-1.5 px-4 sm:px-5 py-2.5 rounded-xl bg-purple-600 text-white font-semibold text-xs sm:text-sm hover:bg-purple-700 transition-all shadow-xs shadow-purple-600/25">
                <?= icon('plus', 'w-4 h-4') ?>
                <span>New Order</span>
            </a>
            <a href="/user/add-funds.php" class="inline-flex items-center space-x-1.5 px-4 sm:px-5 py-2.5 rounded-xl bg-white border border-purple-200 text-purple-700 font-semibold text-xs sm:text-sm hover:bg-purple-50 transition-all shadow-2xs">
                <?= icon('wallet', 'w-4 h-4 text-purple-600') ?>
                <span>Add Funds</span>
            </a>
        </div>
    </div>

    <!-- Metrics Cards Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
        
        <!-- Balance Card -->
        <div class="bg-white border border-purple-100/90 rounded-2xl p-5 shadow-xs hover:border-purple-200 transition-all gsap-card">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Account Balance</span>
                <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                    <?= icon('wallet', 'w-5 h-5') ?>
                </div>
            </div>
            <div class="text-2xl font-black text-zinc-900 font-mono tracking-tight"><?= format_currency((float)$user['balance']) ?></div>
            <div class="text-[11px] text-zinc-400 mt-1 font-medium">Available spending funds</div>
        </div>

        <!-- Total Orders -->
        <div class="bg-white border border-purple-100/90 rounded-2xl p-5 shadow-xs hover:border-purple-200 transition-all gsap-card">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Total Orders</span>
                <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                    <?= icon('shopping-cart', 'w-5 h-5') ?>
                </div>
            </div>
            <div class="text-2xl font-black text-zinc-900 font-mono tracking-tight"><?= number_format($orderCounts['total']) ?></div>
            <div class="text-[11px] text-emerald-600 mt-1 font-medium"><?= number_format($orderCounts['completed']) ?> completed successfully</div>
        </div>

        <!-- In Progress -->
        <div class="bg-white border border-purple-100/90 rounded-2xl p-5 shadow-xs hover:border-purple-200 transition-all gsap-card">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Active Orders</span>
                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <?= icon('clock', 'w-5 h-5') ?>
                </div>
            </div>
            <div class="text-2xl font-black text-amber-600 font-mono tracking-tight"><?= number_format($orderCounts['pending'] + $orderCounts['processing']) ?></div>
            <div class="text-[11px] text-zinc-400 mt-1 font-medium"><?= number_format($orderCounts['pending']) ?> pending, <?= number_format($orderCounts['processing']) ?> processing</div>
        </div>

        <!-- Lifetime Spent -->
        <div class="bg-white border border-purple-100/90 rounded-2xl p-5 shadow-xs hover:border-purple-200 transition-all gsap-card">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Lifetime Spent</span>
                <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                    <?= icon('credit-card', 'w-5 h-5') ?>
                </div>
            </div>
            <div class="text-2xl font-black text-purple-700 font-mono tracking-tight"><?= format_currency((float)$user['spent']) ?></div>
            <div class="text-[11px] text-zinc-400 mt-1 font-medium">All completed purchases</div>
        </div>

    </div>

    <!-- Chart & Order Activity Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- 7-Day Order Volume (ApexCharts using real data) -->
        <div class="lg:col-span-2 bg-white border border-purple-100 rounded-3xl p-6 shadow-xs">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-base font-bold text-zinc-900">7-Day Order Volume</h3>
                    <p class="text-xs text-zinc-500">Real order activity over the last 7 calendar days</p>
                </div>
                <div class="px-2.5 py-1 rounded-full bg-purple-50 text-purple-700 text-xs font-semibold border border-purple-100">
                    Live Telemetry
                </div>
            </div>

            <?php if ($hasChartData): ?>
                <div id="userOrderChart" class="w-full h-64"></div>
                <script>
                    window.addEventListener('DOMContentLoaded', () => {
                        const initChart = () => {
                            if (typeof ApexCharts === 'undefined') return;
                            const options = {
                                series: [{
                                    name: 'Orders Placed',
                                    data: <?= json_encode($chartCounts) ?>
                                }],
                                chart: {
                                    type: 'area',
                                    height: 250,
                                    background: 'transparent',
                                    toolbar: { show: false },
                                    fontFamily: 'inherit'
                                },
                                colors: ['#7c3aed'],
                                fill: {
                                    type: 'gradient',
                                    gradient: {
                                        shadeIntensity: 1,
                                        opacityFrom: 0.35,
                                        opacityTo: 0.05,
                                        stops: [0, 95]
                                    }
                                },
                                dataLabels: { enabled: false },
                                stroke: { curve: 'smooth', width: 2.5 },
                                xaxis: {
                                    categories: <?= json_encode($chartDates) ?>,
                                    labels: { style: { colors: '#71717a', fontSize: '11px', fontWeight: 500 } },
                                    axisBorder: { show: false },
                                    axisTicks: { show: false }
                                },
                                yaxis: {
                                    labels: { style: { colors: '#71717a', fontSize: '11px', fontWeight: 500 } }
                                },
                                grid: {
                                    borderColor: '#f4f4f5',
                                    strokeDashArray: 3
                                },
                                theme: { mode: 'light' },
                                tooltip: { theme: 'light' }
                            };
                            const chart = new ApexCharts(document.querySelector("#userOrderChart"), options);
                            chart.render();
                        };
                        if (typeof ApexCharts !== 'undefined') {
                            initChart();
                        } else {
                            window.addEventListener('apexcharts-ready', initChart);
                        }
                    });
                </script>
            <?php else: ?>
                <div class="h-64 flex flex-col items-center justify-center text-center p-6 border border-dashed border-purple-100 rounded-2xl bg-purple-50/20">
                    <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center mb-2">
                        <?= icon('chart-bar', 'w-5 h-5') ?>
                    </div>
                    <p class="text-sm font-semibold text-zinc-800">No order activity yet</p>
                    <p class="text-xs text-zinc-500 mt-0.5">Place your first order to begin tracking 7-day velocity metrics.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Order Status Distribution -->
        <div class="bg-white border border-purple-100 rounded-3xl p-6 shadow-xs flex flex-col justify-between">
            <div>
                <h3 class="text-base font-bold text-zinc-900 mb-1">Status Distribution</h3>
                <p class="text-xs text-zinc-500 mb-5">Current status across all submitted orders</p>
                
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-zinc-100 text-xs">
                        <div class="flex items-center space-x-2.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <span class="text-zinc-700 font-medium">Completed</span>
                        </div>
                        <span class="font-mono font-bold text-zinc-900"><?= number_format($orderCounts['completed']) ?></span>
                    </div>

                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-zinc-100 text-xs">
                        <div class="flex items-center space-x-2.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span>
                            <span class="text-zinc-700 font-medium">Processing / In Progress</span>
                        </div>
                        <span class="font-mono font-bold text-zinc-900"><?= number_format($orderCounts['processing']) ?></span>
                    </div>

                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-zinc-100 text-xs">
                        <div class="flex items-center space-x-2.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                            <span class="text-zinc-700 font-medium">Pending Dispatch</span>
                        </div>
                        <span class="font-mono font-bold text-zinc-900"><?= number_format($orderCounts['pending']) ?></span>
                    </div>

                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-zinc-100 text-xs">
                        <div class="flex items-center space-x-2.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                            <span class="text-zinc-700 font-medium">Cancelled / Refunded</span>
                        </div>
                        <span class="font-mono font-bold text-zinc-900"><?= number_format($orderCounts['cancelled']) ?></span>
                    </div>
                </div>
            </div>

            <div class="pt-5 mt-5 border-t border-zinc-100">
                <a href="/user/orders.php" class="w-full inline-flex items-center justify-center space-x-2 py-2.5 rounded-xl bg-purple-50 border border-purple-200 text-xs font-semibold text-purple-700 hover:bg-purple-100 transition-colors">
                    <span>View Complete Order History</span>
                    <?= icon('chevron-right', 'w-3.5 h-3.5') ?>
                </a>
            </div>
        </div>

    </div>

    <!-- Recent Orders Table -->
    <div class="bg-white border border-purple-100 rounded-3xl p-6 shadow-xs">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="text-base font-bold text-zinc-900">Recent Orders</h3>
                <p class="text-xs text-zinc-500">Latest social media marketing submissions</p>
            </div>
            <a href="/user/orders.php" class="text-xs font-semibold text-purple-600 hover:text-purple-700 inline-flex items-center space-x-1">
                <span>View all</span>
                <?= icon('chevron-right', 'w-3 h-3') ?>
            </a>
        </div>

        <?php if (!empty($recentOrders)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-zinc-600">
                    <thead class="text-xs uppercase font-bold text-zinc-400 bg-zinc-50/70 border-b border-zinc-100">
                        <tr>
                            <th class="py-3 px-4 rounded-l-xl">Order ID</th>
                            <th class="py-3 px-4">Service</th>
                            <th class="py-3 px-4">Quantity</th>
                            <th class="py-3 px-4">Charge</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4 text-right rounded-r-xl">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        <?php foreach ($recentOrders as $ro): ?>
                            <tr class="hover:bg-purple-50/30 transition-colors">
                                <td class="py-3.5 px-4 font-mono text-xs font-bold text-zinc-900">#<?= (int)$ro['id'] ?></td>
                                <td class="py-3.5 px-4 font-medium text-zinc-900 max-w-xs truncate">
                                    <?= e($ro['service_name'] ?? 'Direct Service #' . $ro['service_id']) ?>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-xs text-zinc-700"><?= number_format($ro['quantity']) ?></td>
                                <td class="py-3.5 px-4 font-mono text-xs font-bold text-purple-700"><?= format_currency((float)$ro['charge']) ?></td>
                                <td class="py-3.5 px-4 text-center"><?= status_badge($ro['status']) ?></td>
                                <td class="py-3.5 px-4 text-xs text-zinc-400"><?= time_ago($ro['created_at']) ?></td>
                                <td class="py-3.5 px-4 text-right">
                                    <a href="/user/order-details.php?id=<?= (int)$ro['id'] ?>" class="inline-flex items-center space-x-1 text-xs text-purple-600 hover:text-purple-700 font-semibold px-2.5 py-1 rounded-lg hover:bg-purple-50 transition-colors">
                                        <?= icon('eye', 'w-3.5 h-3.5') ?>
                                        <span>View</span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="py-12 text-center border border-dashed border-purple-100 rounded-2xl bg-purple-50/10">
                <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-500 flex items-center justify-center mx-auto mb-3">
                    <?= icon('shopping-cart', 'w-6 h-6') ?>
                </div>
                <h4 class="text-sm font-bold text-zinc-900 mb-1">No orders yet</h4>
                <p class="text-xs text-zinc-500 mb-4">Create your first social media growth order to get started.</p>
                <a href="/user/new-order.php" class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl bg-purple-600 text-white text-xs font-semibold hover:bg-purple-700 transition-colors shadow-xs shadow-purple-600/25">
                    <?= icon('plus', 'w-3.5 h-3.5') ?>
                    <span>Place First Order</span>
                </a>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
