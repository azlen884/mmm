<?php
/**
 * ApexSMM User - Developer API Documentation
 * White + Premium Purple Design System
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireLogin();
$user = Auth::user();

$activeNav = 'api';
$pageTitle = 'Developer API | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto space-y-6">
    <div class="gsap-fade-in">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-zinc-900 tracking-tight">Developer API Documentation</h1>
        <p class="text-xs text-zinc-500 mt-1">Integrate automated social orders into your existing panels, bots, or software via standard SMM HTTP POST requests.</p>
    </div>

    <!-- API Overview Card -->
    <div class="bg-white border border-zinc-200/80 rounded-2xl p-6 sm:p-7 shadow-xs space-y-4 gsap-card">
        <h3 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Authentication & Endpoint</h3>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="p-4 rounded-xl bg-zinc-50 border border-zinc-200/80">
                <span class="text-[11px] font-semibold text-zinc-500 uppercase tracking-wider block mb-1">HTTP Method & URL</span>
                <span class="font-mono text-xs text-purple-700 font-bold block">POST <?= e(url('api/v2')) ?></span>
            </div>
            <div class="p-4 rounded-xl bg-zinc-50 border border-zinc-200/80">
                <span class="text-[11px] font-semibold text-zinc-500 uppercase tracking-wider block mb-1">Your Personal API Key</span>
                <span class="font-mono text-xs text-zinc-800 font-bold block truncate"><?= e($user['api_key'] ?? 'None') ?></span>
            </div>
        </div>
    </div>

    <!-- 1. Check Balance -->
    <div class="bg-white border border-zinc-200/80 rounded-2xl p-6 sm:p-7 shadow-xs space-y-4 gsap-card">
        <div class="flex items-center space-x-2">
            <span class="px-2.5 py-0.5 rounded-md bg-purple-50 text-purple-700 border border-purple-200 text-xs font-bold font-mono">POST</span>
            <h3 class="text-sm font-bold text-zinc-900">1. Check Account Balance</h3>
        </div>
        <p class="text-xs text-zinc-500">Retrieve your current wallet balance and standard currency.</p>

        <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4 font-mono text-xs text-zinc-300 overflow-x-auto">
<pre class="text-purple-300">curl -X POST <?= e(url('api/v2')) ?> \
  -d "key=<?= e($user['api_key']) ?>" \
  -d "action=balance"</pre>
        </div>

        <div class="text-xs font-semibold text-zinc-700 mt-2">Example JSON Response:</div>
        <div class="bg-zinc-50 border border-zinc-200 rounded-xl p-4 font-mono text-xs text-purple-900 overflow-x-auto">
<pre>{
  "balance": "<?= number_format((float)$user['balance'], 4, '.', '') ?>",
  "currency": "USD"
}</pre>
        </div>
    </div>

    <!-- 2. Services List -->
    <div class="bg-white border border-zinc-200/80 rounded-2xl p-6 sm:p-7 shadow-xs space-y-4 gsap-card">
        <div class="flex items-center space-x-2">
            <span class="px-2.5 py-0.5 rounded-md bg-purple-50 text-purple-700 border border-purple-200 text-xs font-bold font-mono">POST</span>
            <h3 class="text-sm font-bold text-zinc-900">2. Retrieve Services List</h3>
        </div>
        <p class="text-xs text-zinc-500">Fetch all active service packages, rates, and parameters.</p>

        <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4 font-mono text-xs text-zinc-300 overflow-x-auto">
<pre class="text-purple-300">curl -X POST <?= e(url('api/v2')) ?> \
  -d "key=<?= e($user['api_key']) ?>" \
  -d "action=services"</pre>
        </div>

        <div class="text-xs font-semibold text-zinc-700 mt-2">Example JSON Response:</div>
        <div class="bg-zinc-50 border border-zinc-200 rounded-xl p-4 font-mono text-xs text-purple-900 overflow-x-auto">
<pre>[
  {
    "service": 1,
    "name": "Instagram High Quality Followers",
    "type": "Default",
    "category": "Instagram",
    "rate": "1.2500",
    "min": 100,
    "max": 50000,
    "refill": true,
    "cancel": true
  }
]</pre>
        </div>
    </div>

    <!-- 3. Add Order -->
    <div class="bg-white border border-zinc-200/80 rounded-2xl p-6 sm:p-7 shadow-xs space-y-4 gsap-card">
        <div class="flex items-center space-x-2">
            <span class="px-2.5 py-0.5 rounded-md bg-purple-50 text-purple-700 border border-purple-200 text-xs font-bold font-mono">POST</span>
            <h3 class="text-sm font-bold text-zinc-900">3. Submit New Order</h3>
        </div>
        <p class="text-xs text-zinc-500">Place an automated order with destination link and quantity.</p>

        <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4 font-mono text-xs text-zinc-300 overflow-x-auto">
<pre class="text-purple-300">curl -X POST <?= e(url('api/v2')) ?> \
  -d "key=<?= e($user['api_key']) ?>" \
  -d "action=add" \
  -d "service=1" \
  -d "link=https://instagram.com/myusername" \
  -d "quantity=1000"</pre>
        </div>

        <div class="text-xs font-semibold text-zinc-700 mt-2">Example JSON Response:</div>
        <div class="bg-zinc-50 border border-zinc-200 rounded-xl p-4 font-mono text-xs text-purple-900 overflow-x-auto">
<pre>{
  "order": 1042
}</pre>
        </div>
    </div>

    <!-- 4. Check Order Status -->
    <div class="bg-white border border-zinc-200/80 rounded-2xl p-6 sm:p-7 shadow-xs space-y-4 gsap-card">
        <div class="flex items-center space-x-2">
            <span class="px-2.5 py-0.5 rounded-md bg-purple-50 text-purple-700 border border-purple-200 text-xs font-bold font-mono">POST</span>
            <h3 class="text-sm font-bold text-zinc-900">4. Check Order Status</h3>
        </div>
        <p class="text-xs text-zinc-500">Query execution status, start count, and remaining count.</p>

        <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4 font-mono text-xs text-zinc-300 overflow-x-auto">
<pre class="text-purple-300">curl -X POST <?= e(url('api/v2')) ?> \
  -d "key=<?= e($user['api_key']) ?>" \
  -d "action=status" \
  -d "order=1042"</pre>
        </div>

        <div class="text-xs font-semibold text-zinc-700 mt-2">Example JSON Response:</div>
        <div class="bg-zinc-50 border border-zinc-200 rounded-xl p-4 font-mono text-xs text-purple-900 overflow-x-auto">
<pre>{
  "charge": "1.2500",
  "start_count": "540",
  "status": "Completed",
  "remains": "0",
  "currency": "USD"
}</pre>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
