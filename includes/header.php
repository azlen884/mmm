<?php
/**
 * ApexSMM Public Header Layout
 * White + Premium Purple Design System
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$pageTitle = $pageTitle ?? get_setting('site_name', 'ApexSMM') . ' - Premier Social Media Growth Platform';
$siteName  = get_setting('site_name', 'ApexSMM');
$currentUser = Auth::user();
$flashMessages = flash_get();
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e(get_setting('site_tagline', 'The high-speed automated social media marketing panel for creators and agencies.')) ?>">
    
    <!-- Vite Compiled Assets (Tailwind 4 + Alpine.js + GSAP) -->
    <link rel="stylesheet" href="/assets/css/app.css">
    <script defer src="/assets/js/app.js"></script>
</head>
<body class="bg-[#f8fafc] text-zinc-900 min-h-screen flex flex-col font-sans antialiased selection:bg-purple-600 selection:text-white" x-data="{ mobileNav: false }">

    <!-- Announcement Banner if configured -->
    <?php $announcement = get_setting('announcement'); if (!empty($announcement)): ?>
        <div class="bg-gradient-to-r from-purple-50 via-white to-purple-50 border-b border-purple-100 py-2 px-4 text-xs text-center text-purple-900 flex items-center justify-center space-x-2 shadow-2xs">
            <?= icon('sparkles', 'w-4 h-4 text-purple-600 shrink-0') ?>
            <span class="font-medium"><?= e($announcement) ?></span>
        </div>
    <?php endif; ?>

    <!-- Main Navigation Bar -->
    <nav class="sticky top-0 z-40 bg-white/90 backdrop-blur-md border-b border-purple-100/80 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                
                <!-- Logo -->
                <a href="/" class="flex items-center space-x-3 group">
                    <div class="w-9 h-9 rounded-xl bg-purple-600 flex items-center justify-center font-extrabold text-base text-white shadow-sm shadow-purple-500/25 group-hover:bg-purple-700 transition-colors">
                        A
                    </div>
                    <span class="text-lg font-extrabold tracking-tight text-zinc-900 group-hover:text-purple-600 transition-colors">
                        <?= e($siteName) ?><span class="text-purple-600">.</span>
                    </span>
                </a>

                <!-- Desktop Navigation Links -->
                <div class="hidden md:flex items-center space-x-1 lg:space-x-2 text-xs font-semibold text-zinc-600">
                    <a href="/" class="px-3 py-2 rounded-xl hover:text-purple-600 hover:bg-purple-50 transition-colors">Home</a>
                    <a href="/services.php" class="px-3 py-2 rounded-xl hover:text-purple-600 hover:bg-purple-50 transition-colors">Services</a>
                    <a href="/how-it-works.php" class="px-3 py-2 rounded-xl hover:text-purple-600 hover:bg-purple-50 transition-colors">How It Works</a>
                    <a href="/faq.php" class="px-3 py-2 rounded-xl hover:text-purple-600 hover:bg-purple-50 transition-colors">FAQ</a>
                    <a href="/contact.php" class="px-3 py-2 rounded-xl hover:text-purple-600 hover:bg-purple-50 transition-colors">Contact</a>
                </div>

                <!-- Auth Action Buttons -->
                <div class="hidden md:flex items-center space-x-3">
                    <?php if ($currentUser): ?>
                        <a href="/user/dashboard.php" class="inline-flex items-center space-x-2 px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold shadow-xs shadow-purple-600/25 transition-all">
                            <?= icon('chart-bar', 'w-4 h-4') ?>
                            <span>Dashboard</span>
                        </a>
                        <a href="/logout.php" class="text-xs font-medium text-zinc-500 hover:text-rose-600 px-2 py-1">
                            Sign Out
                        </a>
                    <?php else: ?>
                        <a href="/login.php" class="text-xs font-semibold text-zinc-700 hover:text-purple-600 px-3 py-2 rounded-xl hover:bg-purple-50 transition-colors">
                            Sign In
                        </a>
                        <a href="/register.php" class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold shadow-xs shadow-purple-600/25 transition-all">
                            <?= icon('plus', 'w-3.5 h-3.5') ?>
                            <span>Get Started</span>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Mobile Hamburger Button -->
                <div class="flex items-center md:hidden">
                    <button @click="mobileNav = !mobileNav" type="button" class="p-2 rounded-xl text-zinc-600 hover:text-purple-600 hover:bg-purple-50 transition-colors" aria-label="Toggle Mobile Menu">
                        <span x-show="!mobileNav"><?= icon('bars-3', 'w-6 h-6') ?></span>
                        <span x-show="mobileNav" x-cloak><?= icon('x-mark', 'w-6 h-6') ?></span>
                    </button>
                </div>

            </div>
        </div>

        <!-- Mobile Drawer -->
        <div x-show="mobileNav" x-cloak class="md:hidden border-t border-purple-100 bg-white px-4 pt-3 pb-6 space-y-2 shadow-lg">
            <a href="/" class="block px-3 py-2 rounded-xl text-sm font-medium text-zinc-700 hover:bg-purple-50 hover:text-purple-600">Home</a>
            <a href="/services.php" class="block px-3 py-2 rounded-xl text-sm font-medium text-zinc-700 hover:bg-purple-50 hover:text-purple-600">Services</a>
            <a href="/how-it-works.php" class="block px-3 py-2 rounded-xl text-sm font-medium text-zinc-700 hover:bg-purple-50 hover:text-purple-600">How It Works</a>
            <a href="/faq.php" class="block px-3 py-2 rounded-xl text-sm font-medium text-zinc-700 hover:bg-purple-50 hover:text-purple-600">FAQ</a>
            <a href="/contact.php" class="block px-3 py-2 rounded-xl text-sm font-medium text-zinc-700 hover:bg-purple-50 hover:text-purple-600">Contact</a>
            
            <div class="pt-4 border-t border-purple-100 flex flex-col space-y-2">
                <?php if ($currentUser): ?>
                    <a href="/user/dashboard.php" class="w-full text-center py-2.5 rounded-xl bg-purple-600 text-white font-semibold text-xs shadow-xs">
                        Open Client Dashboard
                    </a>
                <?php else: ?>
                    <a href="/login.php" class="w-full text-center py-2 rounded-xl border border-purple-200 text-purple-700 font-semibold text-xs hover:bg-purple-50">
                        Sign In
                    </a>
                    <a href="/register.php" class="w-full text-center py-2 rounded-xl bg-purple-600 text-white font-semibold text-xs shadow-xs">
                        Create Free Account
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
