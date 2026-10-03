<?php
/**
 * ApexSMM Web Installer - Step 2: Database & Admin Configuration
 */

if (file_exists(__DIR__ . '/../storage/installed.lock')) {
    header('Location: /login.php');
    exit;
}

$error = null;
$success = null;

// Pre-fill from current .env if available
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_NAME') ?: 'smm_panel';
$dbUser = getenv('DB_USER') ?: 'smm_user';
$dbPass = getenv('DB_PASS') ?: 'smm_secure_pass_2026';
$siteUrl = getenv('APP_URL') ?: 'http://localhost:3000';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ApexSMM Installer - Database & Admin Setup</title>
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
                <p class="text-sm text-slate-400">Step 2: Database Connection & Administrator Credentials</p>
            </div>
        </div>

        <form action="/installer/install.php" method="POST" class="space-y-6">
            <div class="bg-slate-950/60 border border-slate-800/80 rounded-xl p-5 space-y-4">
                <h3 class="font-semibold text-slate-200 text-sm tracking-wide uppercase">MySQL / MariaDB Connection</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1">Database Host</label>
                        <input type="text" name="db_host" value="<?= htmlspecialchars($dbHost) ?>" required class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1">Database Port</label>
                        <input type="text" name="db_port" value="<?= htmlspecialchars($dbPort) ?>" required class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Database Name</label>
                    <input type="text" name="db_name" value="<?= htmlspecialchars($dbName) ?>" required class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1">Database User</label>
                        <input type="text" name="db_user" value="<?= htmlspecialchars($dbUser) ?>" required class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1">Database Password</label>
                        <input type="password" name="db_pass" value="<?= htmlspecialchars($dbPass) ?>" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
                    </div>
                </div>
            </div>

            <div class="bg-slate-950/60 border border-slate-800/80 rounded-xl p-5 space-y-4">
                <h3 class="font-semibold text-slate-200 text-sm tracking-wide uppercase">Primary Administrator Account</h3>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Admin Username</label>
                    <input type="text" name="admin_username" value="admin" required class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Admin Email</label>
                    <input type="email" name="admin_email" value="admin@apexsmm.com" required class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Admin Password</label>
                    <input type="password" name="admin_password" value="Admin@Apex2026!" required class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Application URL</label>
                    <input type="url" name="app_url" value="<?= htmlspecialchars($siteUrl) ?>" required class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
                </div>
            </div>

            <div class="flex items-center justify-between pt-2">
                <a href="/installer/index.php" class="text-xs text-slate-400 hover:text-white">&larr; Back to Requirements</a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium hover:from-blue-500 hover:to-indigo-500 transition-all shadow-lg shadow-blue-500/25">
                    Execute Installation &rarr;
                </button>
            </div>
        </form>
    </div>
</body>
</html>
