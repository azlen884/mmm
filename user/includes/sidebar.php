<?php
/**
 * ApexSMM User Sidebar Component
 */
$navItems = [
    'dashboard'     => ['label' => 'Dashboard',     'url' => '/user/dashboard.php',     'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
    'new_order'     => ['label' => 'New Order',     'url' => '/user/new-order.php',     'icon' => 'M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z'],
    'services'      => ['label' => 'Services',      'url' => '/user/services.php',      'icon' => 'M4 6h16M4 10h16M4 14h16M4 18h16'],
    'orders'        => ['label' => 'Orders',        'url' => '/user/orders.php',        'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
    'add_funds'     => ['label' => 'Add Funds',     'url' => '/user/add-funds.php',     'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
    'transactions'  => ['label' => 'Transactions',  'url' => '/user/transactions.php',  'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
    'tickets'       => ['label' => 'Support Desk',  'url' => '/user/tickets.php',       'icon' => 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z'],
    'notifications' => ['label' => 'Notifications', 'url' => '/user/notifications.php', 'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'],
    'api'           => ['label' => 'API Docs',      'url' => '/user/api.php',           'icon' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4'],
    'profile'       => ['label' => 'Account Settings','url' => '/user/profile.php',     'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
];
?>

<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" 
       class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-950 border-r border-slate-800/80 flex flex-col transition-transform duration-200 ease-in-out lg:translate-x-0">
    
    <!-- Logo -->
    <div class="h-18 flex items-center justify-between px-6 border-b border-slate-800/80">
        <a href="/user/dashboard.php" class="flex items-center space-x-3">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-blue-600 via-indigo-500 to-purple-600 flex items-center justify-center font-extrabold text-lg text-white shadow-md shadow-blue-500/30">
                A
            </div>
            <span class="text-lg font-bold tracking-tight text-white"><?= e($siteName) ?><span class="text-blue-500">.</span></span>
        </a>
        <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-white">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <!-- User Balance Summary Widget -->
    <div class="p-4 mx-3 my-4 rounded-2xl bg-gradient-to-b from-slate-900 to-slate-900/60 border border-slate-800/80">
        <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Available Funds</div>
        <div class="text-2xl font-black text-emerald-400 font-mono"><?= format_currency($user['balance']) ?></div>
        <div class="mt-3 pt-3 border-t border-slate-800 flex items-center justify-between text-xs">
            <span class="text-slate-400">Total Spent:</span>
            <span class="font-mono text-slate-200"><?= format_currency($user['spent']) ?></span>
        </div>
    </div>

    <!-- Navigation List -->
    <nav class="flex-1 px-3 space-y-1 overflow-y-auto">
        <?php foreach ($navItems as $key => $item): 
            $isActive = ($activeNav === $key);
        ?>
            <a href="<?= e($item['url']) ?>" 
               class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all <?= $isActive ? 'bg-blue-600/15 border border-blue-500/30 text-blue-400' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/60' ?>">
                <svg class="w-5 h-5 shrink-0 <?= $isActive ? 'text-blue-400' : 'text-slate-500' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="<?= $item['icon'] ?>" />
                </svg>
                <span><?= e($item['label']) ?></span>
                <?php if ($key === 'notifications' && $unreadNotifs > 0): ?>
                    <span class="ml-auto px-2 py-0.5 text-[10px] rounded-full bg-blue-500/20 text-blue-400 font-bold border border-blue-500/30">
                        <?= $unreadNotifs ?>
                    </span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>

        <?php if (($user['role'] ?? '') === 'admin'): ?>
            <div class="pt-4 mt-4 border-t border-slate-800/80">
                <a href="/admin/index.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold text-purple-400 bg-purple-500/10 border border-purple-500/20 hover:bg-purple-500/20 transition-all">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                    </svg>
                    <span>Admin Control Panel</span>
                </a>
            </div>
        <?php endif; ?>
    </nav>

    <!-- Sign Out Footer -->
    <div class="p-4 border-t border-slate-800/80">
        <a href="/logout.php" class="flex items-center space-x-3 px-3.5 py-2 rounded-xl text-sm font-medium text-rose-400 hover:bg-rose-500/10 transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            <span>Sign Out</span>
        </a>
    </div>
</aside>
