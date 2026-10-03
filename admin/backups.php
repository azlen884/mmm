<?php
/**
 * ApexSMM Admin - Database Backup Manager
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
            <h1 class="text-2xl font-bold text-white tracking-tight">Database Backups</h1>
            <p class="text-xs text-slate-400 mt-1">Export full MySQL snapshots for offline storage and disaster recovery.</p>
        </div>
        <form action="/admin/backups.php" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create_backup">
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-purple-600 text-white font-semibold text-xs hover:bg-purple-500 shadow-md shadow-purple-500/20">
                + Generate Full Backup Now
            </button>
        </form>
    </div>

    <?php if ($error): ?>
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 backdrop-blur-xl shadow-xl">
        <?php if (!empty($backupFiles)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="text-xs uppercase text-slate-400 border-b border-slate-800 pb-2">
                        <tr>
                            <th class="py-3 px-4">Backup Filename</th>
                            <th class="py-3 px-4">File Size</th>
                            <th class="py-3 px-4">Generated Date</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-xs font-mono">
                        <?php foreach ($backupFiles as $bf): ?>
                            <tr class="hover:bg-slate-800/30">
                                <td class="py-3 px-4 font-bold text-white"><?= e($bf['name']) ?></td>
                                <td class="py-3 px-4 text-emerald-400"><?= e($bf['size']) ?></td>
                                <td class="py-3 px-4 text-slate-400"><?= e($bf['time']) ?></td>
                                <td class="py-3 px-4 text-right space-x-3">
                                    <a href="/admin/backups.php?download=<?= urlencode($bf['name']) ?>" class="text-blue-400 hover:text-blue-300 font-sans font-semibold">
                                        Download
                                    </a>
                                    <form action="/admin/backups.php" method="POST" class="inline-block" onsubmit="return confirm('Permanently delete this backup archive?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_backup">
                                        <input type="hidden" name="filename" value="<?= e($bf['name']) ?>">
                                        <button type="submit" class="text-rose-400 hover:text-rose-300 font-sans font-semibold">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="py-12 text-center text-xs text-slate-500">
                No backup archives found. Click "Generate Full Backup Now" to create your first database snapshot.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
