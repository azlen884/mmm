<?php
/**
 * ApexSMM Admin - Database Backup Manager
 * White + Premium Purple Design System
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

AdminAuth::requireAdmin();

$backupDir = __DIR__ . '/../storage/backups/';
if (!is_dir($backupDir)) {
    @mkdir($backupDir, 0755, true);
}

$error = null;

// Handle Backup Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_backup') {
    require_csrf();

    $filename = 'smm_backup_' . date('Y-m-d_His') . '.sql';
    $targetPath = $backupDir . $filename;

    $host = DB_HOST;
    $port = DB_PORT;
    $name = DB_NAME;
    $user = DB_USER;
    $pass = DB_PASS;

    // Execute mysqldump
    $cmd = sprintf(
        'mysqldump --host=%s --port=%s --user=%s --password=%s %s > %s 2>&1',
        escapeshellarg($host),
        escapeshellarg($port),
        escapeshellarg($user),
        escapeshellarg($pass),
        escapeshellarg($name),
        escapeshellarg($targetPath)
    );

    exec($cmd, $output, $returnCode);

    if ($returnCode === 0 && file_exists($targetPath) && filesize($targetPath) > 0) {
        audit_log('admin_created_backup', 'backup', null, "Created backup: {$filename}");
        flash_set('success', "Database backup created successfully: {$filename}");
    } else {
        $error = 'Backup failed: ' . implode("\n", $output);
    }
    header('Location: /admin/backups.php');
    exit;
}

// Handle Delete Backup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_backup') {
    require_csrf();
    $file = basename(trim($_POST['filename'] ?? ''));
    if ($file && file_exists($backupDir . $file)) {
        @unlink($backupDir . $file);
        audit_log('admin_deleted_backup', 'backup', null, "Deleted backup: {$file}");
        flash_set('info', "Backup {$file} removed.");
    }
    header('Location: /admin/backups.php');
    exit;
}

// Handle Download
if (isset($_GET['download'])) {
    $file = basename(trim($_GET['download']));
    $target = $backupDir . $file;
    if (file_exists($target)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($target));
        readfile($target);
        exit;
    }
}

// List existing backups
$backupFiles = [];
$scan = scandir($backupDir);
foreach ($scan as $f) {
    if ($f !== '.' && $f !== '..' && $f !== '.gitkeep' && str_ends_with($f, '.sql')) {
        $fullPath = $backupDir . $f;
        $backupFiles[] = [
            'name' => $f,
            'size' => round(filesize($fullPath) / 1024, 2) . ' KB',
            'time' => date('Y-m-d H:i:s', filemtime($fullPath)),
        ];
    }
}

$activeNav = 'backups';
$pageTitle = 'Database Backups | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-[#18181B] tracking-tight">Database Backups</h1>
            <p class="text-xs text-[#71717A] mt-1">Export full MySQL snapshots for offline storage and disaster recovery.</p>
        </div>
        <form action="/admin/backups.php" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create_backup">
            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#7C3AED] hover:bg-[#6D28D9] text-white font-semibold text-xs transition-all shadow-sm">
                <?= icon('plus', 'w-4 h-4') ?>
                <span>Generate Full Backup Now</span>
            </button>
        </form>
    </div>

    <?php if ($error): ?>
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-center gap-2">
            <?= icon('exclamation-circle', 'w-4 h-4 text-red-500 flex-shrink-0') ?>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white border border-[#E4E4E7] rounded-2xl shadow-sm overflow-hidden">
        <div class="p-5 border-b border-[#F4F4F5] flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-[#F3E8FF] text-[#7C3AED] flex items-center justify-center">
                    <?= icon('server', 'w-4 h-4') ?>
                </div>
                <h3 class="text-sm font-bold text-[#18181B]">Snapshot Archives</h3>
            </div>
            <span class="text-xs text-[#71717A]"><?= count($backupFiles) ?> files stored</span>
        </div>

        <?php if (!empty($backupFiles)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-[#18181B]">
                    <thead class="text-xs uppercase text-[#71717A] bg-[#FAF5FF]/50 border-b border-[#E4E4E7]">
                        <tr>
                            <th class="py-3 px-5 font-semibold">Backup Filename</th>
                            <th class="py-3 px-5 font-semibold">File Size</th>
                            <th class="py-3 px-5 font-semibold">Generated Date</th>
                            <th class="py-3 px-5 text-right font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#F4F4F5] text-xs">
                        <?php foreach ($backupFiles as $bf): ?>
                            <tr class="hover:bg-[#FAF5FF]/30 transition-colors">
                                <td class="py-3 px-5 font-mono font-medium text-[#18181B]"><?= e($bf['name']) ?></td>
                                <td class="py-3 px-5 font-mono text-emerald-600 font-semibold"><?= e($bf['size']) ?></td>
                                <td class="py-3 px-5 text-[#71717A] font-mono"><?= e($bf['time']) ?></td>
                                <td class="py-3 px-5 text-right space-x-2">
                                    <a href="/admin/backups.php?download=<?= urlencode($bf['name']) ?>" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-[#FAF5FF] border border-[#DDD6FE] text-[#7C3AED] font-semibold text-xs hover:bg-[#F3E8FF] transition-all">
                                        <?= icon('arrow-down-tray', 'w-3.5 h-3.5') ?>
                                        <span>Download</span>
                                    </a>
                                    <form action="/admin/backups.php" method="POST" class="inline-block" onsubmit="return confirm('Permanently delete this backup archive?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_backup">
                                        <input type="hidden" name="filename" value="<?= e($bf['name']) ?>">
                                        <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-red-50 border border-red-200 text-red-600 font-semibold text-xs hover:bg-red-100 transition-all">
                                            <?= icon('trash', 'w-3.5 h-3.5') ?>
                                            <span>Delete</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="py-16 text-center text-xs text-[#71717A] flex flex-col items-center justify-center gap-2">
                <div class="w-12 h-12 rounded-full bg-[#FAF5FF] flex items-center justify-center text-[#A1A1AA]">
                    <?= icon('server', 'w-6 h-6') ?>
                </div>
                <p class="font-medium text-[#18181B]">No backup archives found</p>
                <p class="text-[11px]">Click "Generate Full Backup Now" above to create your first database snapshot.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
