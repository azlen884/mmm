<?php
/**
 * ApexSMM Admin - System Logs & Audit Records
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

AdminAuth::requireAdmin();

$tab = trim($_GET['tab'] ?? 'audit');

// Handle Clear File Log Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clear_log') {
    require_csrf();
    $targetLog = trim($_POST['log_file'] ?? '');
    $allowedFiles = ['app.log', 'database.log', 'api.log', 'provider.log', 'payment.log'];
    if (in_array($targetLog, $allowedFiles)) {
        @file_put_contents(__DIR__ . '/../storage/logs/' . $targetLog, '');
        audit_log('admin_cleared_log', 'log', null, "Cleared log: {$targetLog}");
        flash_set('info', "Log file {$targetLog} was cleared.");
        header('Location: /admin/logs.php?tab=' . urlencode($tab));
        exit;
    }
}

// Fetch Audit Records
$auditLogs = [];
if ($tab === 'audit') {
    $auditLogs = Database::fetchAll(
        "SELECT a.*, u.username as user_name, adm.username as admin_name 
         FROM audit_logs a 
         LEFT JOIN users u ON a.user_id = u.id 
         LEFT JOIN users adm ON a.admin_id = adm.id 
         ORDER BY a.id DESC 
         LIMIT 60"
    );
}

// Fetch File-based Log lines safely
$fileLines = [];
$fileMap = [
    'app'      => 'app.log',
    'database' => 'database.log',
    'api'      => 'api.log',
    'provider' => 'provider.log',
    'payment'  => 'payment.log',
];

if (isset($fileMap[$tab])) {
    $filePath = __DIR__ . '/../storage/logs/' . $fileMap[$tab];
    if (file_exists($filePath)) {
        $raw = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!empty($raw)) {
            $raw = array_reverse($raw);
            $fileLines = array_slice($raw, 0, 80);
        }
    }
}

$activeNav = 'logs';
$pageTitle = 'Logs & Audits | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">System Logs & Audits</h1>
            <p class="text-xs text-slate-400 mt-1">Review authenticated actions, API dispatches, database queries, and system events.</p>
        </div>
        <?php if (isset($fileMap[$tab])): ?>
            <form action="/admin/logs.php?tab=<?= e($tab) ?>" method="POST" onsubmit="return confirm('Clear this log file?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="clear_log">
                <input type="hidden" name="log_file" value="<?= e($fileMap[$tab]) ?>">
                <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-slate-900 border border-slate-800 text-xs font-semibold text-rose-400 hover:bg-slate-800">
                    Clear <?= e($fileMap[$tab]) ?>
                </button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Tabs -->
    <div class="flex items-center space-x-1 pb-2 border-b border-slate-800 overflow-x-auto">
        <?php 
        $tabs = [
            'audit'    => 'Audit Trail',
            'app'      => 'Application Log',
            'provider' => 'Provider API Log',
            'payment'  => 'Payment IPN Log',
            'database' => 'Database SQL Log',
            'api'      => 'User API Log',
        ];
        foreach ($tabs as $k => $label): 
            $isActive = ($tab === $k);
        ?>
            <a href="/admin/logs.php?tab=<?= e($k) ?>" 
               class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors <?= $isActive ? 'bg-purple-600 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">
                <?= e($label) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Audit Tab -->
    <?php if ($tab === 'audit'): ?>
        <?php if (!empty($auditLogs)): ?>
            <div class="overflow-x-auto bg-slate-900/60 border border-slate-800/80 rounded-2xl backdrop-blur-md shadow-xl">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="bg-slate-950/80 text-xs uppercase text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="px-4 py-3 w-14">ID</th>
                            <th class="px-4 py-3">Action</th>
                            <th class="px-4 py-3">Actor</th>
                            <th class="px-4 py-3">Target Entity</th>
                            <th class="px-4 py-3">IP Address</th>
                            <th class="px-4 py-3">Details</th>
                            <th class="px-4 py-3 text-right">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-xs">
                        <?php foreach ($auditLogs as $a): ?>
                            <tr class="hover:bg-slate-800/30">
                                <td class="px-4 py-3 font-mono text-slate-500">#<?= (int)$a['id'] ?></td>
                                <td class="px-4 py-3 font-bold text-white font-mono"><?= e($a['action']) ?></td>
                                <td class="px-4 py-3 text-slate-300">
                                    <?php if ($a['admin_name']): ?>
                                        <span class="text-purple-400 font-bold">Admin: <?= e($a['admin_name']) ?></span>
                                    <?php elseif ($a['user_name']): ?>
                                        <span class="text-blue-400 font-bold">User: <?= e($a['user_name']) ?></span>
                                    <?php else: ?>
                                        <span class="text-slate-500">System</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 font-mono text-slate-400">
                                    <?= e($a['entity_type'] ?? '-') ?> <?= $a['entity_id'] ? '#' . e($a['entity_id']) : '' ?>
                                </td>
                                <td class="px-4 py-3 font-mono text-slate-400"><?= e($a['ip_address']) ?></td>
                                <td class="px-4 py-3 text-slate-300 max-w-xs truncate font-mono text-[11px]"><?= e($a['details']) ?></td>
                                <td class="px-4 py-3 text-slate-400 text-right whitespace-nowrap"><?= format_date($a['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-16 text-center text-xs text-slate-500">
                No audit log records found in database.
            </div>
        <?php endif; ?>
    <?php else: ?>
        <!-- File Log View -->
        <div class="bg-slate-950 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-2 font-mono text-xs">
            <?php if (!empty($fileLines)): ?>
                <?php foreach ($fileLines as $line): 
                    $decoded = json_decode($line, true);
                ?>
                    <div class="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800/80 text-slate-300 space-y-1">
                        <div class="flex items-center justify-between text-[11px]">
                            <div class="flex items-center space-x-2">
                                <span class="px-2 py-0.2 rounded font-bold uppercase <?= ($decoded['level'] ?? '') === 'ERROR' ? 'bg-rose-500/20 text-rose-400' : 'bg-blue-500/20 text-blue-400' ?>">
                                    <?= e($decoded['level'] ?? 'LOG') ?>
                                </span>
                                <span class="text-white font-semibold"><?= e($decoded['message'] ?? $line) ?></span>
                            </div>
                            <span class="text-slate-500"><?= e($decoded['timestamp'] ?? '') ?></span>
                        </div>
                        <?php if (!empty($decoded['context'])): ?>
                            <div class="text-[11px] text-slate-400 bg-slate-950 p-2 rounded-lg overflow-x-auto">
                                <?= e(json_encode($decoded['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="py-12 text-center text-slate-500">
                    Log file is currently empty or does not exist yet.
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
