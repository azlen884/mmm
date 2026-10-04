<?php
/**
 * ApexSMM Web Installer - Step 1: Welcome & Requirements Check
 * White + Premium Purple Design System
 */

if (file_exists(__DIR__ . '/../storage/installed.lock') && empty($_GET['force']) && empty($_GET['unlock'])) {
    header('Location: /login.php');
    exit;
}

$phpVersion = PHP_VERSION;
$phpOk = version_compare($phpVersion, '8.0.0', '>=');

$requiredExtensions = ['pdo', 'pdo_mysql', 'curl', 'mbstring', 'json', 'xml'];
$extensionStatus = [];
$allExtsOk = true;

foreach ($requiredExtensions as $ext) {
    $loaded = extension_loaded($ext);
    $extensionStatus[$ext] = $loaded;
    if (!$loaded) $allExtsOk = false;
}

$dirsToCheck = [
    '../storage/logs'    => is_writable(__DIR__ . '/../storage/logs'),
    '../storage/cache'   => is_writable(__DIR__ . '/../storage/cache'),
    '../storage/uploads' => is_writable(__DIR__ . '/../storage/uploads'),
    '../storage/backups' => is_writable(__DIR__ . '/../storage/backups'),
];
$allDirsOk = !in_array(false, $dirsToCheck, true);
$canProceed = $phpOk && $allExtsOk && $allDirsOk;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ApexSMM Installation Wizard</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="bg-[#f8fafc] text-zinc-900 min-h-screen flex items-center justify-center p-4 antialiased selection:bg-purple-600 selection:text-white">
    <div class="w-full max-w-xl bg-white border border-zinc-200/80 rounded-2xl shadow-sm p-8">
        <div class="flex items-center space-x-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-purple-600 flex items-center justify-center font-bold text-xl text-white shadow-xs">
                A
            </div>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-zinc-900">ApexSMM Installer</h1>
                <p class="text-xs text-zinc-500">Step 1: System Requirements & Environment</p>
            </div>
        </div>

        <div class="space-y-4">
            <!-- PHP Version -->
            <div class="bg-zinc-50 border border-zinc-200/80 rounded-xl p-4 flex items-center justify-between">
                <div>
                    <div class="font-semibold text-xs text-zinc-900">PHP Version (>= 8.0)</div>
                    <div class="text-xs text-zinc-500">Current: <?= htmlspecialchars($phpVersion) ?></div>
                </div>
                <div>
                    <?php if ($phpOk): ?>
                        <span class="px-2.5 py-1 text-xs rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 font-medium">Passed</span>
                    <?php else: ?>
                        <span class="px-2.5 py-1 text-xs rounded-full bg-rose-50 text-rose-700 border border-rose-200 font-medium">Upgrade Required</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Extensions -->
            <div class="bg-zinc-50 border border-zinc-200/80 rounded-xl p-4">
                <div class="font-semibold text-xs text-zinc-900 mb-3">Required PHP Extensions</div>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <?php foreach ($extensionStatus as $ext => $loaded): ?>
                        <div class="flex items-center justify-between p-2 rounded-lg bg-white border border-zinc-200">
                            <span class="text-zinc-700 font-medium"><?= htmlspecialchars($ext) ?></span>
                            <?php if ($loaded): ?>
                                <span class="text-emerald-700 font-semibold">OK</span>
                            <?php else: ?>
                                <span class="text-rose-700 font-semibold">Missing</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Directory Permissions -->
            <div class="bg-zinc-50 border border-zinc-200/80 rounded-xl p-4">
                <div class="font-semibold text-xs text-zinc-900 mb-3">Writable Storage Directories</div>
                <div class="space-y-2 text-xs">
                    <?php foreach ($dirsToCheck as $dir => $writable): ?>
                        <div class="flex items-center justify-between p-2 rounded-lg bg-white border border-zinc-200">
                            <span class="font-mono text-xs text-zinc-600"><?= htmlspecialchars($dir) ?></span>
                            <?php if ($writable): ?>
                                <span class="text-emerald-700 font-semibold">Writable</span>
                            <?php else: ?>
                                <span class="text-rose-700 font-semibold">Permission Denied</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="pt-4 flex items-center justify-end">
                <?php if ($canProceed): ?>
                    <a href="/installer/database.php" class="inline-flex items-center justify-center px-6 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold shadow-xs transition-all">
                        Continue to Database Setup
                    </a>
                <?php else: ?>
                    <button disabled class="px-6 py-2.5 rounded-xl bg-zinc-100 text-zinc-400 text-xs font-medium cursor-not-allowed">
                        Fix requirements above to proceed
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
