<?php
/**
 * ApexSMM User - Wallet Deposit & Add Funds
 * White + Premium Purple Design System
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

// Query only active payment gateways
$gateways = PaymentManager::getActiveGateways();

$error = null;
$notice = null;

// Handle return callbacks from payment redirects
if (isset($_GET['status']) && isset($_GET['tx'])) {
    $tx = trim($_GET['tx']);
    $payment = Database::fetchOne("SELECT * FROM payments WHERE transaction_id = ? AND user_id = ?", [$tx, $userId]);
    if ($payment) {
        if ($payment['status'] === 'completed') {
            $notice = "Payment confirmed! Your wallet has been credited with " . format_currency((float)$payment['amount']) . ".";
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

<div class="max-w-4xl mx-auto space-y-6"
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
        <h1 class="text-2xl sm:text-3xl font-extrabold text-zinc-900 tracking-tight">Deposit Funds</h1>
        <p class="text-xs sm:text-sm text-zinc-500 mt-1">Recharge your wallet through our encrypted payment processors.</p>
    </div>

    <?php if ($notice): ?>
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm flex items-center space-x-3 shadow-xs">
            <div class="shrink-0 text-emerald-600">
                <?= icon('check-circle', 'w-5 h-5') ?>
            </div>
            <span><?= e($notice) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm flex items-center space-x-3 shadow-xs">
            <div class="shrink-0 text-rose-600">
                <?= icon('exclamation-circle', 'w-5 h-5') ?>
            </div>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Payment Deposit Form -->
        <div class="lg:col-span-2">
            <div class="bg-white border border-purple-100 rounded-3xl p-6 sm:p-8 shadow-xs">
                
                <?php if (empty($gateways)): ?>
                    <div class="py-12 text-center">
                        <div class="w-12 h-12 rounded-xl bg-purple-50 flex items-center justify-center mx-auto mb-3 text-purple-600">
                            <?= icon('wallet', 'w-6 h-6') ?>
                        </div>
                        <h4 class="text-base font-bold text-zinc-900 mb-1">No payment gateways available</h4>
                        <p class="text-xs text-zinc-500">The administrator has not activated any payment processors yet. Please check back shortly.</p>
                    </div>
                <?php else: ?>
                    <form action="/user/add-funds.php" method="POST" class="space-y-5">
                        <?= csrf_field() ?>

                        <!-- Gateway Selector -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-2.5">Payment Method</label>
                            <div class="space-y-2">
                                <template x-for="g in gateways" :key="g.code">
                                    <label class="flex items-center justify-between p-3.5 rounded-2xl border cursor-pointer transition-all select-none"
                                           :class="selectedCode === g.code ? 'bg-purple-50/70 border-purple-300 text-purple-900 ring-1 ring-purple-600/30' : 'bg-white border-zinc-200 text-zinc-700 hover:border-purple-200'">
                                        <div class="flex items-center space-x-3">
                                            <input type="radio" name="gateway" :value="g.code" x-model="selectedCode" class="text-purple-600 focus:ring-0">
                                            <span class="font-semibold text-xs sm:text-sm" x-text="g.name"></span>
                                        </div>
                                        <span class="text-xs font-mono text-zinc-400 font-medium" x-text="'$' + parseFloat(g.min_amount).toFixed(2) + ' - $' + parseFloat(g.max_amount).toFixed(2)"></span>
                                    </label>
                                </template>
                            </div>
                        </div>

                        <!-- Amount Input -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-zinc-500">Deposit Amount (USD)</label>
                                <span class="text-xs text-zinc-500" x-show="activeGateway">
                                    Min: $<span class="font-semibold text-zinc-700 font-mono" x-text="activeGateway ? parseFloat(activeGateway.min_amount).toFixed(2) : 0"></span> | 
                                    Max: $<span class="font-semibold text-zinc-700 font-mono" x-text="activeGateway ? parseFloat(activeGateway.max_amount).toFixed(2) : 0"></span>
                                </span>
                            </div>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-zinc-400">
                                    <span class="font-bold text-sm">$</span>
                                </div>
                                <input type="number" step="0.01" name="amount" x-model.number="amount" required
                                    :min="activeGateway ? activeGateway.min_amount : 1"
                                    :max="activeGateway ? activeGateway.max_amount : 10000"
                                    placeholder="0.00"
                                    class="w-full bg-white border border-zinc-200 rounded-xl pl-8 pr-4 py-2.5 text-sm text-zinc-900 placeholder-zinc-400 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600 transition-colors font-mono">
                            </div>
                        </div>

                        <!-- Invoice Calculation Box -->
                        <div class="p-4 rounded-2xl bg-purple-50/70 border border-purple-100 space-y-2 text-xs">
                            <div class="flex justify-between text-zinc-600">
                                <span>Deposit Credit:</span>
                                <span class="font-mono font-semibold text-zinc-900">$ <span x-text="amount ? parseFloat(amount).toFixed(2) : '0.00'"></span></span>
                            </div>
                            <div class="flex justify-between text-zinc-600">
                                <span>Processor Fee:</span>
                                <span class="font-mono text-zinc-700">$ <span x-text="calculatedFee">0.00</span></span>
                            </div>
                            <div class="border-t border-purple-200/60 pt-2 flex justify-between font-bold text-sm">
                                <span class="text-zinc-900">Total Payable:</span>
                                <span class="font-mono text-purple-700">$ <span x-text="netPayable">0.00</span></span>
                            </div>
                        </div>

                        <button type="submit" class="w-full py-3.5 rounded-xl bg-purple-600 text-white font-bold text-sm shadow-xs shadow-purple-600/25 hover:bg-purple-700 transition-all flex items-center justify-center space-x-2 cursor-pointer">
                            <?= icon('credit-card', 'w-4 h-4') ?>
                            <span>Proceed to Checkout</span>
                        </button>
                    </form>
                <?php endif; ?>

            </div>
        </div>

        <!-- Instructions Sidebar Card -->
        <div class="lg:col-span-1">
            <div class="bg-white border border-purple-100 rounded-3xl p-6 shadow-xs space-y-4">
                <h3 class="text-xs font-bold text-zinc-500 uppercase tracking-wider flex items-center space-x-1.5">
                    <?= icon('information-circle', 'w-4 h-4 text-purple-600') ?>
                    <span>Gateway Instructions</span>
                </h3>
                
                <template x-if="activeGateway && activeGateway.instructions">
                    <div class="p-4 rounded-2xl bg-purple-50/50 border border-purple-100 text-xs text-zinc-600 leading-relaxed"
                         x-text="activeGateway.instructions">
                    </div>
                </template>

                <div class="space-y-3 text-xs text-zinc-500 pt-2">
                    <div class="flex items-center space-x-2 text-emerald-700 font-semibold">
                        <?= icon('shield-check', 'w-4 h-4 text-emerald-600') ?>
                        <span>256-bit Encrypted Checkout</span>
                    </div>
                    <p class="leading-relaxed">
                        Wallets are automatically credited upon cryptographically verified IPN or Webhook execution.
                    </p>
                </div>
            </div>
        </div>

    </div>

    <!-- Recent Payments History -->
    <div class="bg-white border border-purple-100 rounded-3xl p-6 shadow-xs">
        <h3 class="text-base font-bold text-zinc-900 mb-4">Recent Deposit Invoices</h3>
        
        <?php if (!empty($recentPayments)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-zinc-600">
                    <thead class="bg-zinc-50/70 text-xs uppercase font-bold text-zinc-400 border-b border-zinc-100">
                        <tr>
                            <th class="py-3 px-4">Transaction ID</th>
                            <th class="py-3 px-4">Gateway</th>
                            <th class="py-3 px-4">Credit Amount</th>
                            <th class="py-3 px-4">Fee</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        <?php foreach ($recentPayments as $p): ?>
                            <tr class="hover:bg-purple-50/30 transition-colors">
                                <td class="py-3 px-4 font-mono text-xs font-bold text-zinc-900"><?= e($p['transaction_id']) ?></td>
                                <td class="py-3 px-4 uppercase text-xs font-semibold text-purple-700"><?= e($p['gateway']) ?></td>
                                <td class="py-3 px-4 font-mono font-bold text-purple-700"><?= format_currency((float)$p['amount']) ?></td>
                                <td class="py-3 px-4 font-mono text-xs text-zinc-500"><?= format_currency((float)$p['fee']) ?></td>
                                <td class="py-3 px-4 text-center"><?= status_badge($p['status']) ?></td>
                                <td class="py-3 px-4 text-xs text-zinc-400 text-right"><?= format_date($p['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="py-8 text-center border border-dashed border-purple-100 rounded-2xl text-xs text-zinc-400">
                No deposit history found.
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
