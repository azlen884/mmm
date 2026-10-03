<?php
/**
 * ApexSMM How It Works Page
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'How It Works | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="text-center max-w-3xl mx-auto mb-16">
        <h1 class="text-3xl sm:text-5xl font-black text-white tracking-tight mb-4">Simple 4-Step Process</h1>
        <p class="text-base text-slate-400">From account registration to instant delivery, our automated workflow is designed for speed and reliability.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-16">
        
        <!-- Step 1 -->
        <div class="bg-slate-900/70 border border-slate-800 rounded-3xl p-8 relative overflow-hidden">
            <span class="text-7xl font-black text-slate-800/40 absolute -top-2 right-4 select-none">01</span>
            <div class="w-12 h-12 rounded-2xl bg-blue-600/10 border border-blue-500/20 text-blue-400 flex items-center justify-center font-bold text-lg mb-6">
                1
            </div>
            <h3 class="text-xl font-bold text-white mb-2">Create Account</h3>
            <p class="text-sm text-slate-400 leading-relaxed">
                Sign up in under 30 seconds with just a username and email. No invasive personal verification required to get started.
            </p>
        </div>

        <!-- Step 2 -->
        <div class="bg-slate-900/70 border border-slate-800 rounded-3xl p-8 relative overflow-hidden">
            <span class="text-7xl font-black text-slate-800/40 absolute -top-2 right-4 select-none">02</span>
            <div class="w-12 h-12 rounded-2xl bg-indigo-600/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center font-bold text-lg mb-6">
                2
            </div>
            <h3 class="text-xl font-bold text-white mb-2">Deposit Funds</h3>
            <p class="text-sm text-slate-400 leading-relaxed">
                Add balance safely using Stripe cards, PayPal, Crypto (USDT, BTC), or manual bank transfer. Wallet reflects confirmed deposits instantly.
            </p>
        </div>

        <!-- Step 3 -->
        <div class="bg-slate-900/70 border border-slate-800 rounded-3xl p-8 relative overflow-hidden">
            <span class="text-7xl font-black text-slate-800/40 absolute -top-2 right-4 select-none">03</span>
            <div class="w-12 h-12 rounded-2xl bg-purple-600/10 border border-purple-500/20 text-purple-400 flex items-center justify-center font-bold text-lg mb-6">
                3
            </div>
            <h3 class="text-xl font-bold text-white mb-2">Select Service & Order</h3>
            <p class="text-sm text-slate-400 leading-relaxed">
                Choose the desired category and target package. Enter your social media link and quantity. System calculates cost automatically.
            </p>
        </div>

        <!-- Step 4 -->
        <div class="bg-slate-900/70 border border-slate-800 rounded-3xl p-8 relative overflow-hidden">
            <span class="text-7xl font-black text-slate-800/40 absolute -top-2 right-4 select-none">04</span>
            <div class="w-12 h-12 rounded-2xl bg-emerald-600/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-lg mb-6">
                4
            </div>
            <h3 class="text-xl font-bold text-white mb-2">Automated Execution</h3>
            <p class="text-sm text-slate-400 leading-relaxed">
                Our high-speed scheduler routes your order to designated providers. Watch delivery progress live from your order dashboard.
            </p>
        </div>

    </div>

    <div class="text-center">
        <a href="/register.php" class="inline-flex items-center px-8 py-4 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold text-base shadow-xl shadow-blue-500/25 hover:shadow-blue-500/40 hover:scale-[1.02] transition-all">
            Start Growing Today &rarr;
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
