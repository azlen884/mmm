<?php
/**
 * ApexSMM User Navbar Component
 */
?>
<header class="h-18 bg-[#0b0f19]/80 backdrop-blur-xl border-b border-slate-800/80 sticky top-0 z-30 flex items-center justify-between px-4 sm:px-6 lg:px-8">
    
    <!-- Mobile Hamburger Toggle -->
    <div class="flex items-center space-x-3 lg:hidden">
        <button @click="sidebarOpen = true" class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 focus:outline-none">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        <span class="text-base font-bold text-white"><?= e($siteName) ?></span>
    </div>

    <!-- Desktop Title / Breadcrumb -->
    <div class="hidden lg:flex items-center space-x-2 text-sm">
        <span class="text-slate-400">Client Portal</span>
        <span class="text-slate-600">/</span>
        <span class="font-medium text-slate-200"><?= e(ucwords(str_replace('_', ' ', $activeNav))) ?></span>
    </div>

    <!-- Right Controls -->
    <div class="flex items-center space-x-4">
        
        <!-- Quick Add Funds -->
        <a href="/user/add-funds.php" class="hidden sm:inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-semibold hover:bg-emerald-500/20 transition-all">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            <span>Deposit</span>
        </a>

        <!-- Balance Chip -->
        <div class="bg-slate-900 border border-slate-800/90 rounded-xl px-3 py-1.5 flex items-center space-x-2 text-xs">
            <span class="text-slate-400 font-medium">Balance:</span>
            <span class="font-mono font-bold text-emerald-400"><?= format_currency($user['balance']) ?></span>
        </div>

        <!-- Notification Bell -->
        <a href="/user/notifications.php" class="relative p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/60 transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <?php if ($unreadNotifs > 0): ?>
                <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-blue-500 ring-2 ring-[#0b0f19]"></span>
            <?php endif; ?>
        </a>

        <!-- User Profile Dropdown -->
        <div class="relative" x-data="{ userMenu: false }">
            <button @click="userMenu = !userMenu" class="flex items-center space-x-3 p-1.5 rounded-xl hover:bg-slate-800/60 transition-colors focus:outline-none">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center font-bold text-xs text-white uppercase">
                    <?= substr($user['username'], 0, 1) ?>
                </div>
                <span class="hidden md:block text-xs font-semibold text-slate-200"><?= e($user['username']) ?></span>
                <svg class="w-4 h-4 text-slate-500 hidden md:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <!-- Dropdown Menu -->
            <div x-show="userMenu" x-cloak @click.away="userMenu = false"
                 class="absolute right-0 mt-2 w-52 bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl py-2 z-50 text-sm">
                <div class="px-4 py-2 border-b border-slate-800">
                    <p class="text-xs text-slate-400">Signed in as</p>
                    <p class="text-xs font-bold text-white truncate"><?= e($user['email']) ?></p>
                </div>
                <a href="/user/profile.php" class="block px-4 py-2 text-slate-300 hover:bg-slate-800/60 hover:text-white transition-colors">
                    Account Profile
                </a>
                <a href="/user/api.php" class="block px-4 py-2 text-slate-300 hover:bg-slate-800/60 hover:text-white transition-colors">
                    Developer API Key
                </a>
                <div class="border-t border-slate-800 my-1"></div>
                <a href="/logout.php" class="block px-4 py-2 text-rose-400 hover:bg-rose-500/10 transition-colors">
                    Sign Out
                </a>
            </div>
        </div>

    </div>
</header>
