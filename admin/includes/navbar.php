<?php
/**
 * ApexSMM Admin Navbar Component
 */
$maintenance = (get_setting('maintenance_mode', '0') === '1');
?>
<header class="h-18 bg-[#080c14]/80 backdrop-blur-xl border-b border-slate-800/80 sticky top-0 z-30 flex items-center justify-between px-4 sm:px-6 lg:px-8">
    
    <!-- Mobile Hamburger Toggle -->
    <div class="flex items-center space-x-3 lg:hidden">
        <button @click="sidebarOpen = true" class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 focus:outline-none">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        <span class="text-sm font-bold text-white"><?= e($siteName) ?> Staff</span>
    </div>

    <!-- Breadcrumb -->
    <div class="hidden lg:flex items-center space-x-2 text-xs">
        <span class="text-slate-400">Administration</span>
        <span class="text-slate-600">/</span>
        <span class="font-semibold text-purple-300"><?= e(ucwords(str_replace('_', ' ', $activeNav))) ?></span>
    </div>

    <!-- Right Controls -->
    <div class="flex items-center space-x-4">
        
        <?php if ($maintenance): ?>
            <span class="px-2.5 py-1 rounded-full bg-rose-500/15 border border-rose-500/30 text-rose-400 text-xs font-bold animate-pulse">
                MAINTENANCE MODE ACTIVE
            </span>
        <?php endif; ?>

        <!-- Admin Profile -->
        <div class="flex items-center space-x-3">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-purple-600 to-indigo-600 flex items-center justify-center font-bold text-xs text-white uppercase">
                A
            </div>
            <div class="hidden md:block text-left text-xs">
                <div class="font-bold text-white"><?= e($admin['username']) ?></div>
                <div class="text-[10px] text-purple-400 font-medium uppercase">Super Administrator</div>
            </div>
        </div>

    </div>
</header>
