<?php
/**
 * ApexSMM Admin Sidebar Navigation
 * White + Premium Purple Design System with Heroicons
 */
$adminNav = [
    'dashboard'    => ['label' => 'Dashboard',       'url' => '/admin/index.php',           'icon' => 'chart-bar'],
    'users'        => ['label' => 'Users',           'url' => '/admin/users.php',           'icon' => 'users'],
    'orders'       => ['label' => 'Orders',          'url' => '/admin/orders.php',          'icon' => 'shopping-cart'],
    'services'     => ['label' => 'Services',        'url' => '/admin/services.php',        'icon' => 'bolt'],
    'categories'   => ['label' => 'Categories',      'url' => '/admin/categories.php',      'icon' => 'inbox'],
    'providers'    => ['label' => 'API Providers',   'url' => '/admin/providers.php',       'icon' => 'server'],
    'payments'     => ['label' => 'Payments',        'url' => '/admin/payments.php',        'icon' => 'credit-card'],
    'pay_settings' => ['label' => 'Gateways',        'url' => '/admin/payment-settings.php', 'icon' => 'wallet'],
    'transactions' => ['label' => 'Audit Ledger',    'url' => '/admin/transactions.php',    'icon' => 'currency-dollar'],
    'tickets'      => ['label' => 'Support Desk',    'url' => '/admin/tickets.php',         'icon' => 'ticket'],
    'announcements'=> ['label' => 'Announcements',   'url' => '/admin/announcements.php',   'icon' => 'bell'],
    'reports'      => ['label' => 'Reports & Stats', 'url' => '/admin/reports.php',         'icon' => 'arrow-trending-up'],
    'settings'     => ['label' => 'System Settings', 'url' => '/admin/settings.php',        'icon' => 'cog'],
    'logs'         => ['label' => 'System Logs',     'url' => '/admin/logs.php',            'icon' => 'document-text'],
    'backups'      => ['label' => 'Database Backup', 'url' => '/admin/backups.php',         'icon' => 'arrow-down-tray'],
];
?>

<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" 
       class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-purple-100/90 flex flex-col transition-transform duration-200 ease-in-out lg:translate-x-0 shadow-xs">
    
    <!-- Admin Header -->
    <div class="h-16 flex items-center justify-between px-6 border-b border-purple-100/80">
        <a href="/admin/index.php" class="flex items-center space-x-3 group">
            <div class="w-8 h-8 rounded-xl bg-purple-600 flex items-center justify-center font-extrabold text-xs text-white shadow-sm shadow-purple-500/25 group-hover:bg-purple-700 transition-colors">
                ADM
            </div>
            <div>
                <span class="text-sm font-extrabold tracking-tight text-zinc-900 group-hover:text-purple-600 transition-colors"><?= e($siteName) ?></span>
                <span class="text-[10px] text-purple-600 font-semibold block uppercase tracking-wider">Control Panel</span>
            </div>
        </a>
        <button @click="sidebarOpen = false" type="button" class="lg:hidden p-1.5 rounded-lg text-zinc-400 hover:text-zinc-700 hover:bg-zinc-100" aria-label="Close Sidebar">
            <?= icon('x-mark', 'w-5 h-5') ?>
        </button>
    </div>

    <!-- Navigation List -->
    <nav class="flex-1 px-3 py-3 space-y-0.5 overflow-y-auto">
        <?php foreach ($adminNav as $key => $item): 
            $isActive = ($activeNav === $key);
        ?>
            <a href="<?= e($item['url']) ?>" 
               class="flex items-center space-x-3 px-3 py-2 rounded-xl text-xs font-medium transition-all <?= $isActive ? 'bg-purple-600 text-white font-semibold shadow-xs shadow-purple-600/20' : 'text-zinc-600 hover:text-purple-700 hover:bg-purple-50/70' ?>">
                <?= icon($item['icon'], 'w-4 h-4 shrink-0 ' . ($isActive ? 'text-white' : 'text-zinc-400 group-hover:text-purple-600')) ?>
                <span><?= e($item['label']) ?></span>
            </a>
        <?php endforeach; ?>

        <div class="pt-3 mt-3 border-t border-purple-100/80">
            <a href="/user/dashboard.php" class="flex items-center space-x-3 px-3 py-2 rounded-xl text-xs font-medium text-purple-700 bg-purple-50/80 border border-purple-200/70 hover:bg-purple-100 transition-all">
                <?= icon('user', 'w-4 h-4 text-purple-600 shrink-0') ?>
                <span>Switch to Client Portal</span>
            </a>
        </div>
    </nav>

    <!-- Sign Out Footer -->
    <div class="p-3 border-t border-purple-100/80">
        <a href="/logout.php" class="flex items-center space-x-3 px-3 py-2 rounded-xl text-xs font-medium text-rose-600 hover:bg-rose-50 transition-colors">
            <?= icon('arrow-right-on-rectangle', 'w-4 h-4 text-rose-500') ?>
            <span>Sign Out</span>
        </a>
    </div>
</aside>
