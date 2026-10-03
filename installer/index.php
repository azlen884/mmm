<?php
/**
 * ApexSMM Web Installer - Step 1: Welcome & Requirements Check
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
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ApexSMM Installation Wizard</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#0b0f19] text-slate-100 min-h-screen flex items-center justify-center p-4 antialiased selection:bg-blue-600 selection:text-white">
    <div class="w-full max-w-xl bg-slate-900/90 border border-slate-800 rounded-2xl shadow-2xl p-8 backdrop-blur-xl">
        <div class="flex items-center space-x-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center font-bold text-xl text-white shadow-lg shadow-blue-500/30">
                A
            </div>
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-white">ApexSMM Installer</h1>
                <p class="text-sm text-slate-400">Step 1: System Requirements & Environment</p>
            </div>
        </div>

        <div class="space-y-6">
            <!-- PHP Version -->
            <div class="bg-slate-950/60 border border-slate-800/80 rounded-xl p-4 flex items-center justify-between">
                <div>
                    <div class="font-medium text-slate-200">PHP Version (>= 8.0)</div>
                    <div class="text-xs text-slate-400">Current: <?= htmlspecialchars($phpVersion) ?></div>
                </div>
                <div>
                    <?php if ($phpOk): ?>
                        <span class="px-2.5 py-1 text-xs rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-medium">Passed</span>
                    <?php else: ?>
                        <span class="px-2.5 py-1 text-xs rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/20 font-medium">Upgrade Required</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Extensions -->
            <div class="bg-slate-950/60 border border-slate-800/80 rounded-xl p-4">
                <div class="font-medium text-slate-200 mb-3">Required PHP Extensions</div>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <?php foreach ($extensionStatus as $ext => $loaded): ?>
                        <div class="flex items-center justify-between p-2 rounded-lg bg-slate-900/50 border border-slate-800/50">
                            <span class="text-slate-300"><?= htmlspecialchars($ext) ?></span>
                            <?php if ($loaded): ?>
                                <span class="text-emerald-400 text-xs font-semibold">OK</span>
                            <?php else: ?>
                                <span class="text-rose-400 text-xs font-semibold">Missing</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Directory Permissions -->
            <div class="bg-slate-950/60 border border-slate-800/80 rounded-xl p-4">
                <div class="font-medium text-slate-200 mb-3">Writable Storage Directories</div>
                <div class="space-y-2 text-sm">
                    <?php foreach ($dirsToCheck as $dir => $writable): ?>
                        <div class="flex items-center justify-between p-2 rounded-lg bg-slate-900/50 border border-slate-800/50">
                            <span class="font-mono text-xs text-slate-300"><?= htmlspecialchars($dir) ?></span>
                            <?php if ($writable): ?>
                                <span class="text-emerald-400 text-xs font-semibold">Writable</span>
                            <?php else: ?>
                                <span class="text-rose-400 text-xs font-semibold">Permission Denied</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="pt-4 flex items-center justify-end">
                <?php if ($canProceed): ?>
                    <a href="/installer/database.php" class="inline-flex items-center justify-center px-6 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium hover:from-blue-500 hover:to-indigo-500 transition-all shadow-lg shadow-blue-500/25">
                        Continue to Database Setup &rarr;
                    </a>
                <?php else: ?>
                    <button disabled class="px-6 py-2.5 rounded-xl bg-slate-800 text-slate-500 font-medium cursor-not-allowed">
                        Fix requirements above to proceed
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
