<?php
/**
 * ApexSMM Admin - Payment Gateways Configuration
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

AdminAuth::requireAdmin();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $gatewayId = (int)($_POST['id'] ?? 0);
    $status    = trim($_POST['status'] ?? 'inactive');
    $min       = (float)($_POST['min_amount'] ?? 5);
    $max       = (float)($_POST['max_amount'] ?? 1000);
    $feePct    = (float)($_POST['fee_percentage'] ?? 0);
    $feeFixed  = (float)($_POST['fee_fixed'] ?? 0);
    $credsRaw  = trim($_POST['credentials'] ?? '');
    $instr     = trim($_POST['instructions'] ?? '');

    $current = Database::fetchOne("SELECT credentials FROM payment_gateways WHERE id = ?", [$gatewayId]);
    if ($current) {
        $finalCreds = $credsRaw !== '' ? $credsRaw : $current['credentials'];

        Database::execute(
            "UPDATE payment_gateways SET 
                status = ?, min_amount = ?, max_amount = ?, fee_percentage = ?, 
                fee_fixed = ?, credentials = ?, instructions = ? 
             WHERE id = ?",
            [$status, $min, $max, $feePct, $feeFixed, $finalCreds, $instr, $gatewayId]
        );

        audit_log('admin_configured_payment_gateway', 'gateway', $gatewayId, "Updated gateway #{$gatewayId}");
        flash_set('success', 'Gateway parameters updated successfully.');
        header('Location: /admin/payment-settings.php');
        exit;
    }
}

$gateways = Database::fetchAll("SELECT * FROM payment_gateways ORDER BY id ASC");

$activeNav = 'pay_settings';
$pageTitle = 'Payment Settings | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-white tracking-tight">Payment Gateways Configuration</h1>
        <p class="text-xs text-slate-400 mt-1">Configure merchant keys, processor fees, limits, and enable/disable checkout options.</p>
    </div>

    <div class="space-y-6">
        <?php foreach ($gateways as $gw): 
            $creds = json_decode($gw['credentials'] ?? '{}', true) ?: [];
        ?>
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 backdrop-blur-xl shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div class="flex items-center space-x-3">
                        <span class="text-base font-bold text-white"><?= e($gw['name']) ?></span>
                        <span class="text-xs font-mono uppercase text-slate-500">[<?= e($gw['code']) ?>]</span>
                    </div>
                    <?= status_badge($gw['status']) ?>
                </div>

                <form action="/admin/payment-settings.php" method="POST" class="space-y-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$gw['id'] ?>">

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-[11px] font-semibold uppercase text-slate-400 mb-1">Status</label>
                            <select name="status" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                                <option value="active" <?= $gw['status'] === 'active' ? 'selected' : '' ?>>Active (Enabled)</option>
                                <option value="inactive" <?= $gw['status'] === 'inactive' ? 'selected' : '' ?>>Inactive (Disabled)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold uppercase text-slate-400 mb-1">Min Deposit ($)</label>
                            <input type="number" step="0.01" name="min_amount" value="<?= (float)$gw['min_amount'] ?>" required
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold uppercase text-slate-400 mb-1">Max Deposit ($)</label>
                            <input type="number" step="0.01" name="max_amount" value="<?= (float)$gw['max_amount'] ?>" required
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold uppercase text-slate-400 mb-1">Fee Percentage (%)</label>
                            <input type="number" step="0.01" name="fee_percentage" value="<?= (float)$gw['fee_percentage'] ?>" required
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold uppercase text-slate-400 mb-1">Credentials (JSON Encrypted Key-Value)</label>
                        <textarea name="credentials" rows="3" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs font-mono text-purple-300"><?= e($gw['credentials']) ?></textarea>
                        <span class="text-[11px] text-slate-500 mt-1 block">Specify API credentials JSON (e.g. secret_key, publishable_key, client_id, etc.). Never exposed to public users.</span>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold uppercase text-slate-400 mb-1">Instructions (Optional)</label>
                        <input type="text" name="instructions" value="<?= e($gw['instructions'] ?? '') ?>" placeholder="Customer notes or bank instructions..."
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="px-5 py-2 rounded-xl bg-purple-600 text-white font-semibold text-xs hover:bg-purple-500 shadow-md shadow-purple-500/20">
                            Save Gateway &rarr;
                        </button>
                    </div>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
