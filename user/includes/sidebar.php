<?php
/**
 * ApexSMM User Sidebar Component
 * White + Premium Purple Design System with Heroicons
 */
$navItems = [
    'dashboard'     => ['label' => 'Dashboard',       'url' => '/user/dashboard.php',     'icon' => 'chart-bar'],
    'new_order'     => ['label' => 'New Order',       'url' => '/user/new-order.php',     'icon' => 'plus-circle'],
    'services'      => ['label' => 'Services Catalog','url' => '/user/services.php',      'icon' => 'bolt'],
    'orders'        => ['label' => 'Orders',          'url' => '/user/orders.php',        'icon' => 'shopping-cart'],
    'add_funds'     => ['label' => 'Add Funds',       'url' => '/user/add-funds.php',     'icon' => 'wallet'],
    'transactions'  => ['label' => 'Transactions',    'url' => '/user/transactions.php',  'icon' => 'credit-card'],
    'tickets'       => ['label' => 'Support Desk',    'url' => '/user/tickets.php',       'icon' => 'ticket'],
    'notifications' => ['label' => 'Notifications',   'url' => '/user/notifications.php', 'icon' => 'bell'],
    'api'           => ['label' => 'API Docs',        'url' => '/user/api.php',           'icon' => 'key'],
    'profile'       => ['label' => 'Account Settings','url' => '/user/profile.php',       'icon' => 'user'],
];
?>

<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" 
       class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-purple-100/90 flex flex-col transition-transform duration-200 ease-in-out lg:translate-x-0 shadow-xs">
    
    <!-- Logo & Close Button -->
    <div class="h-16 flex items-center justify-between px-6 border-b border-purple-100/80">
        <a href="/user/dashboard.php" class="flex items-center space-x-3 group">
            <div class="w-9 h-9 rounded-xl bg-purple-600 flex items-center justify-center font-extrabold text-base text-white shadow-sm shadow-purple-500/25 group-hover:bg-purple-700 transition-colors">
                A
            </div>
            <span class="text-base font-extrabold tracking-tight text-zinc-900 group-hover:text-purple-600 transition-colors">
                <?= e($siteName) ?><span class="text-purple-600">.</span>
            </span>
        </a>
        <button @click="sidebarOpen = false" type="button" class="lg:hidden p-1.5 rounded-lg text-zinc-400 hover:text-zinc-700 hover:bg-zinc-100" aria-label="Close Sidebar">
            <?= icon('x-mark', 'w-5 h-5') ?>
        </button>
    </div>

    <!-- Balance Summary Card -->
    <div class="p-3.5 mx-3 my-3.5 rounded-2xl bg-gradient-to-br from-purple-50/80 to-white border border-purple-100 shadow-2xs">
        <div class="flex items-center justify-between mb-1">
            <span class="text-[11px] font-semibold text-purple-700 uppercase tracking-wider">Available Balance</span>
            <a href="/user/add-funds.php" class="text-purple-600 hover:text-purple-800 text-[11px] font-semibold inline-flex items-center space-x-0.5">
                <span>+ Deposit</span>
            </a>
        </div>
        <div class="text-2xl font-black text-zinc-900 font-mono tracking-tight"><?= format_currency((float)$user['balance']) ?></div>
        <div class="mt-2.5 pt-2.5 border-t border-purple-100/80 flex items-center justify-between text-xs">
            <span class="text-zinc-500">Total Spent:</span>
            <span class="font-mono font-medium text-zinc-700"><?= format_currency((float)$user['spent']) ?></span>
        </div>
    </div>

    <!-- Navigation List -->
    <nav class="flex-1 px-3 space-y-0.5 overflow-y-auto">
        <?php foreach ($navItems as $key => $item): 
            $isActive = ($activeNav === $key);
        ?>
            <a href="<?= e($item['url']) ?>" 
               class="flex items-center space-x-3 px-3 py-2 rounded-xl text-xs font-medium transition-all <?= $isActive ? 'bg-purple-600 text-white font-semibold shadow-xs shadow-purple-600/20' : 'text-zinc-600 hover:text-purple-700 hover:bg-purple-50/70' ?>">
                <?= icon($item['icon'], 'w-4 h-4 shrink-0 ' . ($isActive ? 'text-white' : 'text-zinc-400 group-hover:text-purple-600')) ?>
                <span><?= e($item['label']) ?></span>
                <?php if ($key === 'notifications' && $unreadNotifs > 0): ?>
                    <span class="ml-auto px-1.5 py-0.5 text-[10px] rounded-full font-bold <?= $isActive ? 'bg-white text-purple-700' : 'bg-purple-100 text-purple-700' ?>">
                        <?= $unreadNotifs ?>
                    </span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>

        <?php if (($user['role'] ?? '') === 'admin'): ?>
            <div class="pt-3 mt-3 border-t border-purple-100/80">
                <a href="/admin/index.php" class="flex items-center space-x-3 px-3 py-2 rounded-xl text-xs font-semibold text-purple-700 bg-purple-50/80 border border-purple-200/70 hover:bg-purple-100 transition-all">
                    <?= icon('shield-check', 'w-4 h-4 text-purple-600 shrink-0') ?>
                    <span>Admin Control Panel</span>
                </a>
            </div>
        <?php endif; ?>
    </nav>

    <!-- Sign Out Footer -->
    <div class="p-3 border-t border-purple-100/80">
        <a href="/logout.php" class="flex items-center space-x-3 px-3 py-2 rounded-xl text-xs font-medium text-rose-600 hover:bg-rose-50 transition-colors">
            <?= icon('arrow-right-on-rectangle', 'w-4 h-4 text-rose-500') ?>
            <span>Sign Out</span>
        </a>
    </div>
</aside>
