<?php
/**
 * ApexSMM Public Header Layout
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
<html lang="en" class="dark scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e(get_setting('site_tagline', 'The high-speed automated social media marketing panel for creators and agencies.')) ?>">
    
    <!-- Vite Compiled Assets -->
    <link rel="stylesheet" href="/assets/css/app.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="/assets/js/app.js"></script>
</head>
<body class="bg-[#0b0f19] text-slate-100 min-h-screen flex flex-col font-sans antialiased selection:bg-blue-600 selection:text-white" x-data="{ mobileNav: false }">

    <!-- Ambient Glow Highlights -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden -z-10">
        <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[800px] h-[350px] bg-gradient-to-b from-blue-600/15 via-indigo-600/5 to-transparent rounded-full blur-3xl"></div>
        <div class="absolute top-[40%] -right-40 w-96 h-96 bg-purple-600/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-10 -left-40 w-96 h-96 bg-blue-600/10 rounded-full blur-3xl"></div>
    </div>

    <!-- Announcement Banner if configured -->
    <?php $announcement = get_setting('announcement'); if (!empty($announcement)): ?>
        <div class="bg-gradient-to-r from-blue-900/60 via-indigo-900/60 to-purple-900/60 border-b border-blue-500/20 py-2 px-4 text-xs text-center text-blue-200 flex items-center justify-center space-x-2">
            <svg class="w-4 h-4 text-blue-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
            </svg>
            <span><?= e($announcement) ?></span>
        </div>
    <?php endif; ?>

    <!-- Main Navigation Bar -->
    <nav class="sticky top-0 z-40 bg-[#0b0f19]/80 backdrop-blur-xl border-b border-slate-800/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-18">
                
                <!-- Logo -->
                <a href="/" class="flex items-center space-x-3 group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 via-indigo-500 to-purple-600 flex items-center justify-center font-black text-xl text-white shadow-lg shadow-blue-500/25 group-hover:shadow-blue-500/40 transition-all duration-300">
                        A
                    </div>
                    <span class="text-xl font-extrabold tracking-tight text-white group-hover:text-blue-400 transition-colors">
                        <?= e($siteName) ?><span class="text-blue-500">.</span>
                    </span>
                </a>

                <!-- Desktop Navigation Links -->
                <div class="hidden md:flex items-center space-x-1 lg:space-x-2 text-sm font-medium text-slate-300">
                    <a href="/" class="px-3 py-2 rounded-lg hover:text-white hover:bg-slate-800/60 transition-colors">Home</a>
                    <a href="/services.php" class="px-3 py-2 rounded-lg hover:text-white hover:bg-slate-800/60 transition-colors">Services</a>
                    <a href="/how-it-works.php" class="px-3 py-2 rounded-lg hover:text-white hover:bg-slate-800/60 transition-colors">How It Works</a>
                    <a href="/faq.php" class="px-3 py-2 rounded-lg hover:text-white hover:bg-slate-800/60 transition-colors">FAQ</a>
                    <a href="/contact.php" class="px-3 py-2 rounded-lg hover:text-white hover:bg-slate-800/60 transition-colors">Contact</a>
                </div>

                <!-- Auth Action Buttons -->
                <div class="hidden md:flex items-center space-x-3">
                    <?php if ($currentUser): ?>
                        <div class="flex items-center space-x-3">
                            <span class="text-xs text-slate-400 bg-slate-900 border border-slate-800 px-3 py-1.5 rounded-lg">
                                Balance: <strong class="text-emerald-400"><?= format_currency($currentUser['balance']) ?></strong>
                            </span>
                            <a href="/user/dashboard.php" class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl bg-blue-600 text-white text-sm font-medium hover:bg-blue-500 transition-all shadow-md shadow-blue-600/30">
                                <span>Dashboard</span>
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </a>
                        </div>
                    <?php else: ?>
                        <a href="/login.php" class="px-4 py-2 text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-800/60 rounded-xl transition-colors">
                            Sign In
                        </a>
                        <a href="/register.php" class="inline-flex items-center px-4 py-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm font-medium hover:from-blue-500 hover:to-indigo-500 transition-all shadow-lg shadow-blue-500/25">
                            Get Started
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Mobile Menu Button -->
                <div class="flex md:hidden">
                    <button @click="mobileNav = !mobileNav" class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 focus:outline-none">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path x-show="!mobileNav" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path x-show="mobileNav" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Drawer -->
        <div x-show="mobileNav" x-cloak class="md:hidden border-b border-slate-800 bg-[#0b0f19] px-4 pt-2 pb-6 space-y-3">
            <div class="flex flex-col space-y-2 text-sm font-medium text-slate-300">
                <a href="/" class="px-3 py-2 rounded-lg hover:text-white hover:bg-slate-800/60">Home</a>
                <a href="/services.php" class="px-3 py-2 rounded-lg hover:text-white hover:bg-slate-800/60">Services</a>
                <a href="/how-it-works.php" class="px-3 py-2 rounded-lg hover:text-white hover:bg-slate-800/60">How It Works</a>
                <a href="/faq.php" class="px-3 py-2 rounded-lg hover:text-white hover:bg-slate-800/60">FAQ</a>
                <a href="/contact.php" class="px-3 py-2 rounded-lg hover:text-white hover:bg-slate-800/60">Contact</a>
            </div>
            <div class="pt-4 border-t border-slate-800 flex flex-col space-y-2">
                <?php if ($currentUser): ?>
                    <a href="/user/dashboard.php" class="w-full text-center py-2.5 rounded-xl bg-blue-600 text-white text-sm font-semibold">
                        Go to Dashboard
                    </a>
                <?php else: ?>
                    <a href="/login.php" class="w-full text-center py-2 rounded-xl bg-slate-800 text-slate-200 text-sm font-medium">
                        Sign In
                    </a>
                    <a href="/register.php" class="w-full text-center py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm font-semibold">
                        Get Started
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Global Flash Alerts Container -->
    <?php if (!empty($flashMessages)): ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 space-y-2">
            <?php foreach ($flashMessages as $flash): 
                $alertColor = [
                    'success' => 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400',
                    'error'   => 'bg-rose-500/10 border-rose-500/30 text-rose-400',
                    'warning' => 'bg-amber-500/10 border-amber-500/30 text-amber-400',
                    'info'    => 'bg-blue-500/10 border-blue-500/30 text-blue-400',
                ][$flash['type']] ?? 'bg-slate-800 border-slate-700 text-slate-300';
            ?>
                <div class="p-4 rounded-xl border <?= $alertColor ?> text-sm flex items-center justify-between shadow-lg backdrop-blur-md">
                    <span><?= e($flash['message']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <!-- Main Body Container -->
    <main class="flex-grow">
