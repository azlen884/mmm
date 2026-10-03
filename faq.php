<?php
/**
 * ApexSMM Frequently Asked Questions
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'FAQ | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="text-center mb-12">
        <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">Frequently Asked Questions</h1>
        <p class="text-sm text-slate-400 mt-2">Answers to common questions about orders, payments, refills, and provider APIs.</p>
    </div>

    <div class="space-y-4" x-data="{ active: null }">
        
        <!-- FAQ Item 1 -->
        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl overflow-hidden">
            <button @click="active = (active === 1 ? null : 1)" class="w-full px-6 py-5 text-left font-semibold text-white flex items-center justify-between hover:text-blue-400 transition-colors">
                <span>How fast are orders processed?</span>
                <span class="text-slate-500 font-mono text-xl" x-text="active === 1 ? '−' : '+'">+</span>
            </button>
            <div x-show="active === 1" x-cloak class="px-6 pb-6 text-sm text-slate-400 leading-relaxed border-t border-slate-800/60 pt-4">
                Orders are dispatched immediately through our automated provider routing engine. Depending on the service specifications, delivery begins within a few minutes up to the estimated start time shown on the service card.
            </div>
        </div>

        <!-- FAQ Item 2 -->
        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl overflow-hidden">
            <button @click="active = (active === 2 ? null : 2)" class="w-full px-6 py-5 text-left font-semibold text-white flex items-center justify-between hover:text-blue-400 transition-colors">
                <span>What is the Refill feature?</span>
                <span class="text-slate-500 font-mono text-xl" x-text="active === 2 ? '−' : '+'">+</span>
            </button>
            <div x-show="active === 2" x-cloak class="px-6 pb-6 text-sm text-slate-400 leading-relaxed border-t border-slate-800/60 pt-4">
                Services tagged with "Refill" include a guarantee window against organic social media drops. If your count decreases below the start count within the warranty period, you can trigger a free refill directly from your orders dashboard.
            </div>
        </div>

        <!-- FAQ Item 3 -->
        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl overflow-hidden">
            <button @click="active = (active === 3 ? null : 3)" class="w-full px-6 py-5 text-left font-semibold text-white flex items-center justify-between hover:text-blue-400 transition-colors">
                <span>What payment methods are supported?</span>
                <span class="text-slate-500 font-mono text-xl" x-text="active === 3 ? '−' : '+'">+</span>
            </button>
            <div x-show="active === 3" x-cloak class="px-6 pb-6 text-sm text-slate-400 leading-relaxed border-t border-slate-800/60 pt-4">
                We support instant online deposits via Stripe (Credit/Debit cards), PayPal, CoinPayments (USDT TRC20, Bitcoin, Ethereum), and manual Bank Wire Transfers. All active gateways are listed in the Add Funds section.
            </div>
        </div>

        <!-- FAQ Item 4 -->
        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl overflow-hidden">
            <button @click="active = (active === 4 ? null : 4)" class="w-full px-6 py-5 text-left font-semibold text-white flex items-center justify-between hover:text-blue-400 transition-colors">
                <span>Can I connect my own panel or bot via API?</span>
                <span class="text-slate-500 font-mono text-xl" x-text="active === 4 ? '−' : '+'">+</span>
            </button>
            <div x-show="active === 4" x-cloak class="px-6 pb-6 text-sm text-slate-400 leading-relaxed border-t border-slate-800/60 pt-4">
                Yes. Every registered user receives a unique API Key located in their Profile settings. We follow the standard SMM API v2 protocol for seamless integration with any SMM reseller software.
            </div>
        </div>

        <!-- FAQ Item 5 -->
        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl overflow-hidden">
            <button @click="active = (active === 5 ? null : 5)" class="w-full px-6 py-5 text-left font-semibold text-white flex items-center justify-between hover:text-blue-400 transition-colors">
                <span>What happens if an order is cancelled or fails?</span>
                <span class="text-slate-500 font-mono text-xl" x-text="active === 5 ? '−' : '+'">+</span>
            </button>
            <div x-show="active === 5" x-cloak class="px-6 pb-6 text-sm text-slate-400 leading-relaxed border-t border-slate-800/60 pt-4">
                If an order cannot be completed by upstream providers, the system automatically marks it as cancelled or partial and credits the remaining balance back into your account wallet instantly.
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
