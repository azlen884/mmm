<?php
/**
 * ApexSMM Admin Navbar Component
 * White + Premium Purple Design System
 */
$maintenance = (get_setting('maintenance_mode', '0') === '1');
?>
<header class="h-16 bg-white/95 backdrop-blur-md border-b border-purple-100/80 sticky top-0 z-30 flex items-center justify-between px-4 sm:px-6 lg:px-8 shadow-xs">
    
    <!-- Mobile Hamburger Toggle -->
    <div class="flex items-center space-x-3 lg:hidden">
        <button @click="sidebarOpen = true" type="button" class="p-2 rounded-xl text-zinc-600 hover:text-purple-600 hover:bg-purple-50 transition-colors focus:outline-none" aria-label="Open Sidebar">
            <?= icon('bars-3', 'w-6 h-6') ?>
        </button>
        <span class="text-sm font-bold text-zinc-900"><?= e($siteName) ?> Staff</span>
    </div>

    <!-- Breadcrumb -->
    <div class="hidden lg:flex items-center space-x-2 text-xs">
        <span class="text-zinc-400 font-medium">Administration</span>
        <span class="text-zinc-300">/</span>
        <span class="font-semibold text-purple-700 bg-purple-50 px-2.5 py-0.5 rounded-md border border-purple-100">
            <?= e(ucwords(str_replace('_', ' ', $activeNav))) ?>
        </span>
    </div>

    <!-- Right Controls -->
    <div class="flex items-center space-x-3.5">
        
        <?php if ($maintenance): ?>
            <span class="px-2.5 py-1 rounded-full bg-rose-50 border border-rose-200 text-rose-700 text-xs font-bold inline-flex items-center space-x-1.5 animate-pulse">
                <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span>
                <span>Maintenance Mode</span>
            </span>
        <?php endif; ?>

        <!-- Switch to Client Portal -->
        <a href="/user/dashboard.php" class="hidden sm:inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-purple-50 border border-purple-200 text-purple-700 text-xs font-medium hover:bg-purple-100/80 transition-colors shadow-2xs">
            <?= icon('user', 'w-3.5 h-3.5 text-purple-600') ?>
            <span>User View</span>
        </a>

        <!-- Admin Profile -->
        <div class="flex items-center space-x-2.5 pl-2">
            <div class="w-8 h-8 rounded-xl bg-purple-600 flex items-center justify-center font-bold text-xs text-white uppercase shadow-xs">
                A
            </div>
            <div class="hidden md:block text-left text-xs">
                <div class="font-bold text-zinc-900"><?= e($admin['username']) ?></div>
                <div class="text-[10px] text-purple-600 font-medium uppercase tracking-wider">Super Administrator</div>
            </div>
        </div>

    </div>
</header>
