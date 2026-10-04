<?php
/**
 * ApexSMM User Navbar Component
 * White + Premium Purple Design System with Interactive Notification Center
 */
?>
<header class="h-16 bg-white/95 backdrop-blur-md border-b border-purple-100/80 sticky top-0 z-30 flex items-center justify-between px-4 sm:px-6 lg:px-8 shadow-xs">
    
    <!-- Mobile Hamburger Toggle -->
    <div class="flex items-center space-x-3 lg:hidden">
        <button @click="sidebarOpen = true" type="button" class="p-2 rounded-xl text-zinc-600 hover:text-purple-600 hover:bg-purple-50 transition-colors focus:outline-none" aria-label="Open Sidebar">
            <?= icon('bars-3', 'w-6 h-6') ?>
        </button>
        <span class="text-base font-bold text-zinc-900"><?= e($siteName) ?></span>
    </div>

    <!-- Desktop Breadcrumb / Title -->
    <div class="hidden lg:flex items-center space-x-2 text-xs">
        <span class="text-zinc-400 font-medium">Portal</span>
        <span class="text-zinc-300">/</span>
        <span class="font-semibold text-purple-700 bg-purple-50 px-2.5 py-0.5 rounded-md border border-purple-100">
            <?= e(ucwords(str_replace('_', ' ', $activeNav))) ?>
        </span>
    </div>

    <!-- Right Controls -->
    <div class="flex items-center space-x-2.5 sm:space-x-3.5">
        
        <!-- Quick Add Funds -->
        <a href="/user/add-funds.php" class="hidden sm:inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-purple-50 border border-purple-200 text-purple-700 text-xs font-semibold hover:bg-purple-100/80 transition-all shadow-xs">
            <?= icon('wallet', 'w-4 h-4 text-purple-600') ?>
            <span>Add Funds</span>
        </a>

        <!-- Live Balance Chip -->
        <div class="bg-zinc-50 border border-zinc-200/80 rounded-xl px-3 py-1.5 flex items-center space-x-2 text-xs shadow-2xs">
            <span class="text-zinc-500 font-medium hidden xs:inline">Balance:</span>
            <span class="font-mono font-bold text-purple-700"><?= format_currency((float)$user['balance']) ?></span>
        </div>

        <!-- Notification Bell Dropdown -->
        <div class="relative" x-data="{ 
            notifOpen: false,
            unreadCount: <?= (int)$unreadNotifs ?>,
            markAllRead() {
                const token = document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '';
                fetch('/ajax/notifications.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'mark_all', csrf_token: token })
                }).then(res => res.json()).then(data => {
                    if (data.success) {
                        this.unreadCount = 0;
                        document.querySelectorAll('.notif-dot').forEach(el => el.remove());
                    }
                }).catch(() => {});
            }
        }">
            <button @click="notifOpen = !notifOpen" type="button" class="relative p-2 rounded-xl text-zinc-500 hover:text-purple-600 hover:bg-purple-50 transition-colors focus:outline-none" aria-label="Notifications">
                <?= icon('bell', 'w-5 h-5') ?>
                <template x-if="unreadCount > 0">
                    <span class="absolute top-1.5 right-1.5 flex h-4 min-w-4 px-1 items-center justify-center rounded-full bg-purple-600 text-white text-[10px] font-bold ring-2 ring-white" x-text="unreadCount > 9 ? '9+' : unreadCount"></span>
                </template>
            </button>

            <!-- Notification Dropdown Panel with GSAP-like smooth transitions -->
            <div x-show="notifOpen" x-cloak @click.away="notifOpen = false"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-1"
                 class="absolute right-0 mt-2 w-80 sm:w-96 bg-white border border-purple-100 rounded-2xl shadow-xl shadow-purple-900/5 py-2 z-50 text-sm">
                
                <div class="px-4 py-2.5 border-b border-zinc-100 flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="font-bold text-xs text-zinc-900 uppercase tracking-wider">Notifications</span>
                        <template x-if="unreadCount > 0">
                            <span class="px-1.5 py-0.5 text-[10px] rounded-full bg-purple-100 text-purple-700 font-bold" x-text="unreadCount + ' unread'"></span>
                        </template>
                    </div>
                    <button type="button" @click="markAllRead()" class="text-xs text-purple-600 hover:text-purple-700 font-medium transition-colors">
                        Mark all as read
                    </button>
                </div>

                <div class="max-h-80 overflow-y-auto divide-y divide-zinc-50">
                    <?php if (empty($recentNotifs)): ?>
                        <div class="py-8 px-4 text-center">
                            <div class="w-10 h-10 rounded-full bg-purple-50 text-purple-500 flex items-center justify-center mx-auto mb-2">
                                <?= icon('bell', 'w-5 h-5') ?>
                            </div>
                            <p class="text-xs font-medium text-zinc-700">No new notifications</p>
                            <p class="text-[11px] text-zinc-400 mt-0.5">You're all caught up with orders and account alerts.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentNotifs as $notif): 
                            $isUnread = empty($notif['is_read']);
                        ?>
                            <div class="p-3.5 hover:bg-purple-50/50 transition-colors flex items-start space-x-3 <?= $isUnread ? 'bg-purple-50/20' : '' ?>">
                                <div class="w-8 h-8 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center shrink-0 mt-0.5">
                                    <?= icon('bell', 'w-4 h-4') ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <p class="text-xs font-semibold text-zinc-900 truncate"><?= e($notif['title']) ?></p>
                                        <?php if ($isUnread): ?>
                                            <span class="notif-dot w-2 h-2 rounded-full bg-purple-600 shrink-0 ml-1.5"></span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-xs text-zinc-500 line-clamp-2 mt-0.5"><?= e($notif['message']) ?></p>
                                    <span class="text-[10px] text-zinc-400 block mt-1"><?= time_ago($notif['created_at']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="px-4 py-2 border-t border-zinc-100 text-center">
                    <a href="/user/notifications.php" class="text-xs font-semibold text-purple-600 hover:text-purple-700 inline-flex items-center space-x-1">
                        <span>View all notifications</span>
                        <?= icon('chevron-right', 'w-3 h-3') ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- User Profile Dropdown -->
        <div class="relative" x-data="{ userMenu: false }">
            <button @click="userMenu = !userMenu" type="button" class="flex items-center space-x-2.5 p-1 rounded-xl hover:bg-purple-50 transition-colors focus:outline-none">
                <div class="w-8 h-8 rounded-xl bg-purple-600 flex items-center justify-center font-bold text-xs text-white uppercase shadow-xs">
                    <?= substr($user['username'], 0, 1) ?>
                </div>
                <span class="hidden md:block text-xs font-semibold text-zinc-800"><?= e($user['username']) ?></span>
                <?= icon('chevron-down', 'w-3.5 h-3.5 text-zinc-400 hidden md:block') ?>
            </button>

            <!-- Dropdown Menu -->
            <div x-show="userMenu" x-cloak @click.away="userMenu = false"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="absolute right-0 mt-2 w-56 bg-white border border-purple-100 rounded-2xl shadow-xl shadow-purple-900/5 py-1.5 z-50 text-sm">
                
                <div class="px-4 py-2.5 border-b border-zinc-100">
                    <p class="text-[11px] text-zinc-400 uppercase tracking-wider font-medium">Signed in as</p>
                    <p class="text-xs font-bold text-zinc-900 truncate"><?= e($user['email']) ?></p>
                </div>

                <div class="p-1 space-y-0.5">
                    <a href="/user/profile.php" class="flex items-center space-x-2.5 px-3 py-2 rounded-xl text-xs font-medium text-zinc-700 hover:bg-purple-50 hover:text-purple-700 transition-colors">
                        <?= icon('user', 'w-4 h-4 text-zinc-400') ?>
                        <span>Account Profile</span>
                    </a>
                    <a href="/user/api.php" class="flex items-center space-x-2.5 px-3 py-2 rounded-xl text-xs font-medium text-zinc-700 hover:bg-purple-50 hover:text-purple-700 transition-colors">
                        <?= icon('key', 'w-4 h-4 text-zinc-400') ?>
                        <span>API Credentials</span>
                    </a>
                </div>

                <div class="border-t border-zinc-100 my-1"></div>

                <div class="p-1">
                    <a href="/logout.php" class="flex items-center space-x-2.5 px-3 py-2 rounded-xl text-xs font-medium text-rose-600 hover:bg-rose-50 transition-colors">
                        <?= icon('arrow-right-on-rectangle', 'w-4 h-4 text-rose-500') ?>
                        <span>Sign Out</span>
                    </a>
                </div>
            </div>
        </div>

    </div>
</header>
