<?php
/**
 * ApexSMM Admin Sidebar Navigation
 */
$adminNav = [
    'dashboard'   => ['label' => 'Dashboard',       'url' => '/admin/index.php',          'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
    'users'       => ['label' => 'Users',           'url' => '/admin/users.php',          'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
    'orders'      => ['label' => 'Orders',          'url' => '/admin/orders.php',         'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
    'services'    => ['label' => 'Services',        'url' => '/admin/services.php',       'icon' => 'M4 6h16M4 10h16M4 14h16M4 18h16'],
    'categories'  => ['label' => 'Categories',      'url' => '/admin/categories.php',     'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
    'providers'   => ['label' => 'Providers',       'url' => '/admin/providers.php',      'icon' => 'M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
    'payments'    => ['label' => 'Payments',        'url' => '/admin/payments.php',       'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
    'pay_settings'=> ['label' => 'Gateways',        'url' => '/admin/payment-settings.php','icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'],
    'transactions'=> ['label' => 'Ledger',          'url' => '/admin/transactions.php',   'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
    'tickets'     => ['label' => 'Tickets',         'url' => '/admin/tickets.php',        'icon' => 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z'],
    'announcements'=> ['label' => 'Announcements',  'url' => '/admin/announcements.php',  'icon' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z'],
    'reports'     => ['label' => 'Reports',         'url' => '/admin/reports.php',        'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
    'settings'    => ['label' => 'System Settings', 'url' => '/admin/settings.php',       'icon' => 'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4'],
    'logs'        => ['label' => 'Audit & Logs',    'url' => '/admin/logs.php',           'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
    'backups'     => ['label' => 'Database Backup', 'url' => '/admin/backups.php',        'icon' => 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4'],
];
?>

<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" 
       class="fixed inset-y-0 left-0 z-50 w-64 bg-[#0a0e1a] border-r border-slate-800/80 flex flex-col transition-transform duration-200 ease-in-out lg:translate-x-0">
    
    <!-- Admin Header -->
    <div class="h-18 flex items-center justify-between px-6 border-b border-slate-800/80">
        <a href="/admin/index.php" class="flex items-center space-x-3">
            <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-purple-600 to-indigo-600 flex items-center justify-center font-extrabold text-sm text-white shadow-md shadow-purple-500/30">
                ADM
            </div>
            <div>
                <span class="text-sm font-extrabold tracking-tight text-white"><?= e($siteName) ?></span>
                <span class="text-[10px] text-purple-400 font-semibold block uppercase tracking-wider">Control Panel</span>
            </div>
        </a>
        <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-white">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <!-- Navigation List -->
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <?php foreach ($adminNav as $key => $item): 
            $isActive = ($activeNav === $key);
        ?>
            <a href="<?= e($item['url']) ?>" 
               class="flex items-center space-x-3 px-3.5 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive ? 'bg-purple-600/15 border border-purple-500/30 text-purple-300' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/60' ?>">
                <svg class="w-4 h-4 shrink-0 <?= $isActive ? 'text-purple-400' : 'text-slate-500' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="<?= $item['icon'] ?>" />
                </svg>
                <span><?= e($item['label']) ?></span>
            </a>
        <?php endforeach; ?>

        <div class="pt-4 mt-4 border-t border-slate-800">
            <a href="/user/dashboard.php" class="flex items-center space-x-3 px-3.5 py-2 rounded-xl text-xs font-semibold text-blue-400 bg-blue-600/10 border border-blue-500/20 hover:bg-blue-600/20 transition-all">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
                <span>Switch to Client Portal</span>
            </a>
        </div>
    </nav>

    <!-- Sign Out Footer -->
    <div class="p-4 border-t border-slate-800/80">
        <a href="/logout.php" class="flex items-center space-x-3 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-400 hover:bg-rose-500/10 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            <span>Exit Admin</span>
        </a>
    </div>
</aside>
