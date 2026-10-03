<?php
/**
 * ApexSMM User - Wallet Deposit & Add Funds
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../payments/payment-manager.php';
require_once __DIR__ . '/../app/Payments/PaymentService.php';

Auth::requireLogin();
$user = Auth::user();
$userId = (int)$user['id'];

// Query only active payment gateways (Section 23: Never show fake or unconfigured gateways)
$gateways = PaymentManager::getActiveGateways();

$error = null;
$notice = null;

// Handle return callbacks from payment redirects
if (isset($_GET['status']) && isset($_GET['tx'])) {
    $tx = trim($_GET['tx']);
    $payment = Database::fetchOne("SELECT * FROM payments WHERE transaction_id = ? AND user_id = ?", [$tx, $userId]);
    if ($payment) {
        if ($payment['status'] === 'completed') {
            $notice = "Payment confirmed! Your wallet has been credited with " . format_currency($payment['amount']) . ".";
        } elseif ($payment['status'] === 'pending') {
            $notice = "Your deposit invoice #{$tx} is awaiting provider confirmation. Your balance will update automatically upon verification.";
        } elseif ($payment['status'] === 'cancelled') {
            $error = "Payment was cancelled or expired.";
        }
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $gatewayCode = trim($_POST['gateway'] ?? '');
    $amount      = (float)($_POST['amount'] ?? 0);

    try {
        $result = PaymentService::createPayment($userId, $gatewayCode, $amount);
        if (!empty($result['redirect_url'])) {
            header('Location: ' . $result['redirect_url']);
            exit;
        } else {
            flash_set('success', 'Deposit order initialized.');
            header('Location: /user/add-funds.php?tx=' . urlencode($result['tx_id']));
            exit;
        }
    } catch (\Throwable $e) {
        $error = $e->getMessage();
    }
}

// User recent payment history
$recentPayments = Database::fetchAll(
    "SELECT * FROM payments WHERE user_id = ? ORDER BY id DESC LIMIT 5",
    [$userId]
);

$activeNav = 'add_funds';
$pageTitle = 'Add Funds | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto space-y-8"
     x-data="{
         gateways: <?= htmlspecialchars(json_encode($gateways), ENT_QUOTES, 'UTF-8') ?>,
         selectedCode: '<?= !empty($gateways) ? $gateways[0]['code'] : '' ?>',
         amount: '',

         get activeGateway() {
             return this.gateways.find(g => g.code === this.selectedCode) || null;
         },

         get calculatedFee() {
             if (!this.activeGateway || !this.amount || this.amount <= 0) return '0.00';
             const pct = parseFloat(this.activeGateway.fee_percentage) || 0;
             const fixed = parseFloat(this.activeGateway.fee_fixed) || 0;
             return ((this.amount * (pct / 100)) + fixed).toFixed(2);
         },

         get netPayable() {
             if (!this.amount || this.amount <= 0) return '0.00';
             return (parseFloat(this.amount) + parseFloat(this.calculatedFee)).toFixed(2);
         }
     }">

    <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Deposit Account Balance</h1>
        <p class="text-sm text-slate-400 mt-1">Recharge your wallet through our encrypted payment processors.</p>
    </div>

    <?php if ($notice): ?>
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm flex items-center space-x-3 shadow-lg">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span><?= e($notice) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm flex items-center space-x-3 shadow-lg">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Payment Deposit Form -->
        <div class="lg:col-span-2">
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 sm:p-8 backdrop-blur-xl shadow-xl">
                
                <?php if (empty($gateways)): ?>
                    <div class="py-12 text-center">
                        <div class="w-12 h-12 rounded-xl bg-slate-800 flex items-center justify-center mx-auto mb-3 text-slate-400">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                        <h4 class="text-base font-bold text-white mb-1">No payment gateways available</h4>
                        <p class="text-xs text-slate-400">The administrator has not activated any payment processors yet. Please check back shortly.</p>
                    </div>
                <?php else: ?>
                    <form action="/user/add-funds.php" method="POST" class="space-y-6">
                        <?= csrf_field() ?>

                        <!-- Gateway Selector -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Payment Method</label>
                            <div class="space-y-2">
                                <template x-for="g in gateways" :key="g.code">
                                    <label class="flex items-center justify-between p-4 rounded-2xl border cursor-pointer transition-all"
                                           :class="selectedCode === g.code ? 'bg-blue-600/10 border-blue-500/50 text-white' : 'bg-slate-950/60 border-slate-800 text-slate-300 hover:border-slate-700'">
                                        <div class="flex items-center space-x-3">
                                            <input type="radio" name="gateway" :value="g.code" x-model="selectedCode" class="text-blue-600 focus:ring-0">
                                            <span class="font-medium text-sm" x-text="g.name"></span>
                                        </div>
                                        <span class="text-xs font-mono text-slate-400" x-text="'$' + parseFloat(g.min_amount).toFixed(2) + ' - $' + parseFloat(g.max_amount).toFixed(2)"></span>
                                    </label>
                                </template>
                            </div>
                        </div>

                        <!-- Amount Input -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400">Deposit Amount (USD)</label>
                                <span class="text-xs text-slate-500" x-show="activeGateway">
                                    Min: $<span x-text="activeGateway ? parseFloat(activeGateway.min_amount).toFixed(2) : 0"></span> | 
                                    Max: $<span x-text="activeGateway ? parseFloat(activeGateway.max_amount).toFixed(2) : 0"></span>
                                </span>
                            </div>
                            <input type="number" step="0.01" name="amount" x-model.number="amount" required
                                :min="activeGateway ? activeGateway.min_amount : 1"
                                :max="activeGateway ? activeGateway.max_amount : 10000"
                                placeholder="Enter amount in USD..."
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 transition-colors font-mono">
                        </div>

                        <!-- Invoice Calculation Box -->
                        <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800/80 space-y-2 text-xs">
                            <div class="flex justify-between text-slate-400">
                                <span>Deposit Credit:</span>
                                <span class="font-mono text-white">$ <span x-text="amount ? parseFloat(amount).toFixed(2) : '0.00'"></span></span>
                            </div>
                            <div class="flex justify-between text-slate-400">
                                <span>Processor Fee:</span>
                                <span class="font-mono text-slate-300">$ <span x-text="calculatedFee">0.00</span></span>
                            </div>
                            <div class="border-t border-slate-800 pt-2 flex justify-between font-bold text-sm">
                                <span class="text-slate-200">Total Payable:</span>
                                <span class="font-mono text-emerald-400">$ <span x-text="netPayable">0.00</span></span>
                            </div>
                        </div>

                        <button type="submit" class="w-full py-4 rounded-xl bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 text-white font-bold text-base shadow-xl shadow-blue-500/25 hover:shadow-blue-500/40 hover:scale-[1.01] transition-all">
                            Proceed to Checkout &rarr;
                        </button>
                    </form>
                <?php endif; ?>

            </div>
        </div>

        <!-- Instructions Sidebar Card -->
        <div class="lg:col-span-1">
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 backdrop-blur-xl space-y-4">
                <h3 class="text-sm font-bold text-slate-200 uppercase tracking-wider">Gateway Instructions</h3>
                
                <template x-if="activeGateway && activeGateway.instructions">
                    <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 text-xs text-slate-300 leading-relaxed"
                         x-text="activeGateway.instructions">
                    </div>
                </template>

                <div class="space-y-3 text-xs text-slate-400 pt-2">
                    <div class="flex items-center space-x-2 text-emerald-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span>Verified 256-bit SSL Gateway Security</span>
                    </div>
                    <p>
                        Wallets are automatically credited upon cryptographically verified IPN or Webhook execution.
                    </p>
                </div>
            </div>
        </div>

    </div>

    <!-- Recent Payments History -->
    <div class="bg-slate-900/70 border border-slate-800 rounded-3xl p-6 backdrop-blur-xl">
        <h3 class="text-base font-bold text-white mb-4">Recent Deposit Invoices</h3>
        
        <?php if (!empty($recentPayments)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="text-xs uppercase text-slate-400 border-b border-slate-800 pb-3">
                        <tr>
                            <th class="py-3 px-4">Transaction ID</th>
                            <th class="py-3 px-4">Gateway</th>
                            <th class="py-3 px-4">Credit Amount</th>
                            <th class="py-3 px-4">Fee</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <?php foreach ($recentPayments as $p): ?>
                            <tr class="hover:bg-slate-800/30 transition-colors">
                                <td class="py-3 px-4 font-mono text-xs text-slate-300"><?= e($p['transaction_id']) ?></td>
                                <td class="py-3 px-4 uppercase text-xs font-semibold text-white"><?= e($p['gateway']) ?></td>
                                <td class="py-3 px-4 font-mono font-bold text-emerald-400"><?= format_currency($p['amount']) ?></td>
                                <td class="py-3 px-4 font-mono text-xs text-slate-400"><?= format_currency($p['fee']) ?></td>
                                <td class="py-3 px-4 text-center"><?= status_badge($p['status']) ?></td>
                                <td class="py-3 px-4 text-xs text-slate-400 text-right"><?= format_date($p['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="py-8 text-center border border-dashed border-slate-800 rounded-2xl text-xs text-slate-500">
                No deposit history found.
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
