<?php
/**
 * ApexSMM Admin Dashboard Header Layout
 * White + Premium Purple Design System
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
$hasCharts = $hasCharts ?? false;
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    
    <!-- Vite Compiled Assets (Tailwind 4 + Alpine.js + GSAP) -->
    <link rel="stylesheet" href="/assets/css/app.css">
    <script defer src="/assets/js/app.js"></script>
    
    <?php if ($hasCharts): ?>
        <!-- Modular Chart Asset (Loaded only on pages with charts) -->
        <script defer src="/assets/js/charts.js"></script>
    <?php endif; ?>
</head>
<body class="bg-[#f8fafc] text-zinc-900 h-full font-sans antialiased selection:bg-purple-600 selection:text-white" x-data="{ sidebarOpen: false }">

    <div class="min-h-full flex">
        
        <!-- Mobile Sidebar Backdrop with GSAP fade -->
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" 
             x-transition:enter="transition-opacity ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-40 bg-zinc-900/50 backdrop-blur-xs lg:hidden"></div>

        <!-- Sidebar Include -->
        <?php require_once __DIR__ . '/sidebar.php'; ?>

        <!-- Main Content Column -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden lg:pl-64">
            
            <!-- Top Navbar Include -->
            <?php require_once __DIR__ . '/navbar.php'; ?>

            <!-- Main Page Container with subtle GSAP entrance -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 gsap-fade-in">
                
                <!-- Flash Alerts with Heroicons -->
                <?php if (!empty($flashMessages)): ?>
                    <div class="mb-6 space-y-2">
                        <?php foreach ($flashMessages as $flash): 
                            $type = $flash['type'] ?? 'info';
                            $alertStyles = [
                                'success' => 'bg-emerald-50 border-emerald-200 text-emerald-900 ring-1 ring-emerald-500/20',
                                'error'   => 'bg-rose-50 border-rose-200 text-rose-900 ring-1 ring-rose-500/20',
                                'warning' => 'bg-amber-50 border-amber-200 text-amber-900 ring-1 ring-amber-500/20',
                                'info'    => 'bg-purple-50 border-purple-200 text-purple-900 ring-1 ring-purple-500/20',
                            ][$type] ?? 'bg-white border-zinc-200 text-zinc-800';

                            $iconName = [
                                'success' => 'check-circle',
                                'error'   => 'exclamation-circle',
                                'warning' => 'exclamation-triangle',
                                'info'    => 'information-circle',
                            ][$type] ?? 'information-circle';
                        ?>
                            <div class="p-4 rounded-xl border shadow-xs text-sm flex items-center justify-between <?= $alertStyles ?>" x-data="{ show: true }" x-show="show">
                                <div class="flex items-center space-x-3">
                                    <div class="shrink-0">
                                        <?= icon($iconName, 'w-5 h-5') ?>
                                    </div>
                                    <span class="font-medium text-xs sm:text-sm"><?= e($flash['message']) ?></span>
                                </div>
                                <button type="button" @click="show = false" class="p-1 rounded-lg hover:bg-black/5 text-current/60 hover:text-current">
                                    <?= icon('x-mark', 'w-4 h-4') ?>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
