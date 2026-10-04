<?php
/**
 * ApexSMM Admin - System Logs & Audit Records
 * White + Premium Purple Design System
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
            <h1 class="text-2xl font-bold text-[#18181B] tracking-tight">System Logs & Audits</h1>
            <p class="text-xs text-[#71717A] mt-1">Review authenticated actions, API dispatches, database queries, and system events.</p>
        </div>
        <?php if (isset($fileMap[$tab])): ?>
            <form action="/admin/logs.php?tab=<?= e($tab) ?>" method="POST" onsubmit="return confirm('Clear this log file?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="clear_log">
                <input type="hidden" name="log_file" value="<?= e($fileMap[$tab]) ?>">
                <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-red-50 border border-red-200 text-xs font-semibold text-red-600 hover:bg-red-100 transition-all">
                    <?= icon('trash', 'w-3.5 h-3.5') ?>
                    <span>Clear <?= e($fileMap[$tab]) ?></span>
                </button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Tabs -->
    <div class="flex items-center gap-2 pb-2 border-b border-[#E4E4E7] overflow-x-auto">
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
               class="px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all <?= $isActive ? 'bg-[#7C3AED] text-white shadow-sm' : 'text-[#71717A] hover:text-[#18181B] hover:bg-[#FAF5FF]' ?>">
                <?= e($label) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Audit Tab -->
    <?php if ($tab === 'audit'): ?>
        <?php if (!empty($auditLogs)): ?>
            <div class="bg-white border border-[#E4E4E7] rounded-2xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-[#18181B]">
                        <thead class="bg-[#FAF5FF]/50 text-xs uppercase text-[#71717A] border-b border-[#E4E4E7]">
                            <tr>
                                <th class="px-5 py-3.5 w-14 font-semibold">ID</th>
                                <th class="px-5 py-3.5 font-semibold">Action</th>
                                <th class="px-5 py-3.5 font-semibold">Actor</th>
                                <th class="px-5 py-3.5 font-semibold">Target Entity</th>
                                <th class="px-5 py-3.5 font-semibold">IP Address</th>
                                <th class="px-5 py-3.5 font-semibold">Details</th>
                                <th class="px-5 py-3.5 text-right font-semibold">Timestamp</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#F4F4F5] text-xs">
                            <?php foreach ($auditLogs as $a): ?>
                                <tr class="hover:bg-[#FAF5FF]/30 transition-colors">
                                    <td class="px-5 py-3.5 font-mono text-[#A1A1AA]">#<?= (int)$a['id'] ?></td>
                                    <td class="px-5 py-3.5 font-bold text-[#18181B] font-mono">
                                        <span class="px-2 py-0.5 rounded bg-[#F3E8FF] text-[#7C3AED] border border-[#DDD6FE]"><?= e($a['action']) ?></span>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <?php if ($a['admin_name']): ?>
                                            <span class="text-[#7C3AED] font-semibold flex items-center gap-1">
                                                <?= icon('shield-check', 'w-3.5 h-3.5') ?>
                                                Admin: <?= e($a['admin_name']) ?>
                                            </span>
                                        <?php elseif ($a['user_name']): ?>
                                            <span class="text-blue-600 font-semibold flex items-center gap-1">
                                                <?= icon('user', 'w-3.5 h-3.5') ?>
                                                User: <?= e($a['user_name']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-[#71717A]">System</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-5 py-3.5 font-mono text-[#71717A]">
                                        <?= e($a['entity_type'] ?? '-') ?> <?= $a['entity_id'] ? '#' . e($a['entity_id']) : '' ?>
                                    </td>
                                    <td class="px-5 py-3.5 font-mono text-[#71717A]"><?= e($a['ip_address']) ?></td>
                                    <td class="px-5 py-3.5 text-[#71717A] max-w-xs truncate font-mono text-[11px]"><?= e($a['details']) ?></td>
                                    <td class="px-5 py-3.5 text-[#71717A] text-right whitespace-nowrap font-mono"><?= format_date($a['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-white border border-[#E4E4E7] rounded-2xl p-16 text-center text-xs text-[#71717A] flex flex-col items-center justify-center gap-2">
                <div class="w-12 h-12 rounded-full bg-[#FAF5FF] flex items-center justify-center text-[#A1A1AA]">
                    <?= icon('clipboard-document-list', 'w-6 h-6') ?>
                </div>
                <p class="font-medium text-[#18181B]">No audit records found</p>
                <p class="text-[11px]">Audit records will be recorded when users or admins perform actions.</p>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <!-- File Log View -->
        <div class="bg-white border border-[#E4E4E7] rounded-2xl p-6 shadow-sm space-y-3 font-mono text-xs">
            <?php if (!empty($fileLines)): ?>
                <?php foreach ($fileLines as $line): 
                    $decoded = json_decode($line, true);
                    $level = $decoded['level'] ?? 'LOG';
                    $isError = ($level === 'ERROR' || $level === 'CRITICAL');
                ?>
                    <div class="p-3.5 rounded-xl <?= $isError ? 'bg-red-50/60 border border-red-200' : 'bg-[#FAF5FF]/40 border border-[#E4E4E7]' ?> text-[#18181B] space-y-2">
                        <div class="flex items-center justify-between text-[11px]">
                            <div class="flex items-center space-x-2">
                                <span class="px-2 py-0.5 rounded-full font-bold uppercase <?= $isError ? 'bg-red-100 text-red-700' : 'bg-[#F3E8FF] text-[#7C3AED]' ?>">
                                    <?= e($level) ?>
                                </span>
                                <span class="text-[#18181B] font-semibold"><?= e($decoded['message'] ?? $line) ?></span>
                            </div>
                            <span class="text-[#71717A]"><?= e($decoded['timestamp'] ?? '') ?></span>
                        </div>
                        <?php if (!empty($decoded['context'])): ?>
                            <div class="text-[11px] text-[#52525B] bg-white border border-[#E4E4E7] p-2.5 rounded-lg overflow-x-auto">
                                <?= e(json_encode($decoded['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="py-16 text-center text-xs text-[#71717A] flex flex-col items-center justify-center gap-2">
                    <div class="w-12 h-12 rounded-full bg-[#FAF5FF] flex items-center justify-center text-[#A1A1AA]">
                        <?= icon('document-text', 'w-6 h-6') ?>
                    </div>
                    <p class="font-medium text-[#18181B]">Log file is empty</p>
                    <p class="text-[11px]">No log entries currently recorded for this channel.</p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
