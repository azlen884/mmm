<?php
/**
 * ApexSMM How It Works Page
 * White + Premium Purple Design System
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'How It Works | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="text-center max-w-3xl mx-auto mb-16 gsap-fade-in">
        <h1 class="text-3xl sm:text-5xl font-black text-zinc-900 tracking-tight mb-3">Simple 4-Step Process</h1>
        <p class="text-xs sm:text-sm text-zinc-500">From account registration to instant delivery, our automated workflow is designed for speed and reliability.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-16 gsap-card">
        
        <!-- Step 1 -->
        <div class="bg-white border border-zinc-200/80 rounded-3xl p-8 relative overflow-hidden shadow-xs hover:border-purple-300 transition-all">
            <span class="text-7xl font-black text-purple-100/60 absolute -top-2 right-4 select-none">01</span>
            <div class="w-12 h-12 rounded-2xl bg-purple-50 border border-purple-200 text-purple-700 flex items-center justify-center font-bold text-base mb-6 shadow-2xs">
                1
            </div>
            <h3 class="text-lg font-bold text-zinc-900 mb-2">Create Account</h3>
            <p class="text-xs text-zinc-600 leading-relaxed">
                Sign up in under 30 seconds with just a username and email. No invasive personal verification required to get started.
            </p>
        </div>

        <!-- Step 2 -->
        <div class="bg-white border border-zinc-200/80 rounded-3xl p-8 relative overflow-hidden shadow-xs hover:border-purple-300 transition-all">
            <span class="text-7xl font-black text-purple-100/60 absolute -top-2 right-4 select-none">02</span>
            <div class="w-12 h-12 rounded-2xl bg-purple-50 border border-purple-200 text-purple-700 flex items-center justify-center font-bold text-base mb-6 shadow-2xs">
                2
            </div>
            <h3 class="text-lg font-bold text-zinc-900 mb-2">Deposit Funds</h3>
            <p class="text-xs text-zinc-600 leading-relaxed">
                Add balance safely using Stripe cards, PayPal, Crypto (USDT, BTC), or manual bank transfer. Wallet reflects confirmed deposits instantly.
            </p>
        </div>

        <!-- Step 3 -->
        <div class="bg-white border border-zinc-200/80 rounded-3xl p-8 relative overflow-hidden shadow-xs hover:border-purple-300 transition-all">
            <span class="text-7xl font-black text-purple-100/60 absolute -top-2 right-4 select-none">03</span>
            <div class="w-12 h-12 rounded-2xl bg-purple-50 border border-purple-200 text-purple-700 flex items-center justify-center font-bold text-base mb-6 shadow-2xs">
                3
            </div>
            <h3 class="text-lg font-bold text-zinc-900 mb-2">Select Service & Order</h3>
            <p class="text-xs text-zinc-600 leading-relaxed">
                Choose the desired category and target package. Enter your social media link and quantity. System calculates cost automatically.
            </p>
        </div>

        <!-- Step 4 -->
        <div class="bg-white border border-zinc-200/80 rounded-3xl p-8 relative overflow-hidden shadow-xs hover:border-purple-300 transition-all">
            <span class="text-7xl font-black text-purple-100/60 absolute -top-2 right-4 select-none">04</span>
            <div class="w-12 h-12 rounded-2xl bg-purple-50 border border-purple-200 text-purple-700 flex items-center justify-center font-bold text-base mb-6 shadow-2xs">
                4
            </div>
            <h3 class="text-lg font-bold text-zinc-900 mb-2">Automated Execution</h3>
            <p class="text-xs text-zinc-600 leading-relaxed">
                Our high-speed scheduler routes your order to designated providers. Watch delivery progress live from your order dashboard.
            </p>
        </div>

    </div>

    <div class="text-center">
        <a href="/register.php" class="inline-flex items-center space-x-2 px-8 py-3.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-semibold text-sm shadow-sm shadow-purple-500/25 transition-all">
            <?= icon('plus', 'w-4 h-4') ?>
            <span>Start Growing Today</span>
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
