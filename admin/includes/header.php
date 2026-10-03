<?php
/**
 * ApexSMM Admin Dashboard Header Layout
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/admin-auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/csrf.php';

AdminAuth::requireAdmin();

$admin = AdminAuth::user();
$siteName = get_setting('site_name', 'ApexSMM');
$pageTitle = $pageTitle ?? 'Admin Control Center | ' . $siteName;
$activeNav = $activeNav ?? 'dashboard';
$flashMessages = flash_get();
?>
<!DOCTYPE html>
<html lang="en" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    
    <!-- Vite Compiled Assets (Tailwind 4 + Alpine.js + GSAP + ApexCharts) -->
    <link rel="stylesheet" href="/assets/css/app.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="/assets/js/app.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>
<body class="bg-[#080c14] text-slate-100 h-full antialiased selection:bg-purple-600 selection:text-white" x-data="{ sidebarOpen: false }">

    <div class="min-h-full flex">
        
        <!-- Mobile Sidebar Backdrop -->
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" 
             class="fixed inset-0 z-40 bg-black/80 backdrop-blur-sm lg:hidden transition-opacity"></div>

        <!-- Sidebar Include -->
        <?php require_once __DIR__ . '/sidebar.php'; ?>

        <!-- Main Content Column -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden lg:pl-64">
            
            <!-- Top Navbar Include -->
            <?php require_once __DIR__ . '/navbar.php'; ?>

            <!-- Main Page Container -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                
                <!-- Flash Alerts -->
                <?php if (!empty($flashMessages)): ?>
                    <div class="mb-6 space-y-2">
                        <?php foreach ($flashMessages as $flash): 
                            $alertColor = [
                                'success' => 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400',
                                'error'   => 'bg-rose-500/10 border-rose-500/30 text-rose-400',
                                'warning' => 'bg-amber-500/10 border-amber-500/30 text-amber-400',
                                'info'    => 'bg-purple-500/10 border-purple-500/30 text-purple-400',
                            ][$flash['type']] ?? 'bg-slate-800 border-slate-700 text-slate-300';
                        ?>
                            <div class="p-4 rounded-xl border <?= $alertColor ?> text-sm flex items-center justify-between shadow-lg backdrop-blur-md">
                                <span><?= e($flash['message']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
