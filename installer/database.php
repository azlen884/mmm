<?php
/**
 * ApexSMM Web Installer - Step 2: Database & Admin Configuration
 * White + Premium Purple Design System
 */

if (file_exists(__DIR__ . '/../storage/installed.lock') && empty($_GET['force']) && empty($_GET['unlock'])) {
    header('Location: /login.php');
    exit;
}

$error = null;
$success = null;

// Parse existing .env if present
$envPath = __DIR__ . '/../.env';
$envVars = [];
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $l) {
        $l = trim($l);
        if ($l === '' || strpos($l, '#') === 0) continue;
        if (strpos($l, '=') !== false) {
            list($k, $v) = explode('=', $l, 2);
            $envVars[trim($k)] = trim(trim($v), "\"'");
        }
    }
}

$dbHost  = $envVars['DB_HOST'] ?? (getenv('DB_HOST') ?: '127.0.0.1');
$dbPort  = $envVars['DB_PORT'] ?? (getenv('DB_PORT') ?: '3306');
$dbName  = $envVars['DB_NAME'] ?? (getenv('DB_NAME') ?: 'smm_panel');
$dbUser  = $envVars['DB_USER'] ?? (getenv('DB_USER') ?: 'smm_user');
$dbPass  = $envVars['DB_PASS'] ?? (getenv('DB_PASS') ?: 'smm_secure_pass_2026');
$siteUrl = $envVars['APP_URL'] ?? (getenv('APP_URL') ?: 'http://localhost:3000');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ApexSMM Installer - Database & Admin Setup</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="bg-[#f8fafc] text-zinc-900 min-h-screen flex items-center justify-center p-4 antialiased selection:bg-purple-600 selection:text-white">
    <div class="w-full max-w-xl bg-white border border-zinc-200/80 rounded-2xl shadow-sm p-8 my-8">
        <div class="flex items-center space-x-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-purple-600 flex items-center justify-center font-bold text-xl text-white shadow-xs">
                A
            </div>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-zinc-900">ApexSMM Installer</h1>
                <p class="text-xs text-zinc-500">Step 2: Database Connection & Administrator Credentials</p>
            </div>
        </div>

        <form action="/installer/install.php" method="POST" class="space-y-6">
            <div class="bg-zinc-50 border border-zinc-200/80 rounded-xl p-5 space-y-4">
                <h3 class="font-semibold text-zinc-900 text-xs tracking-wide uppercase">MySQL / MariaDB Connection</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-600 mb-1">Database Host</label>
                        <input type="text" name="db_host" value="<?= htmlspecialchars($dbHost) ?>" required class="w-full bg-white border border-zinc-200 rounded-lg px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-zinc-600 mb-1">Database Port</label>
                        <input type="text" name="db_port" value="<?= htmlspecialchars($dbPort) ?>" required class="w-full bg-white border border-zinc-200 rounded-lg px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-zinc-600 mb-1">Database Name</label>
                    <input type="text" name="db_name" value="<?= htmlspecialchars($dbName) ?>" required class="w-full bg-white border border-zinc-200 rounded-lg px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-600 mb-1">Database User</label>
                        <input type="text" name="db_user" value="<?= htmlspecialchars($dbUser) ?>" required class="w-full bg-white border border-zinc-200 rounded-lg px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-zinc-600 mb-1">Database Password</label>
                        <input type="password" name="db_pass" value="<?= htmlspecialchars($dbPass) ?>" class="w-full bg-white border border-zinc-200 rounded-lg px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
                    </div>
                </div>

                <div class="pt-2">
                    <label class="flex items-center space-x-2 text-xs text-zinc-700 cursor-pointer select-none">
                        <input type="checkbox" name="clean_install" value="1" checked class="w-4 h-4 rounded border-zinc-300 text-purple-600 focus:ring-purple-500">
                        <span>Clean Installation (Drop & recreate tables to prevent schema/column conflicts)</span>
                    </label>
                </div>
            </div>

            <div class="bg-zinc-50 border border-zinc-200/80 rounded-xl p-5 space-y-4">
                <h3 class="font-semibold text-zinc-900 text-xs tracking-wide uppercase">Primary Administrator Account</h3>
                <div>
                    <label class="block text-xs font-semibold text-zinc-600 mb-1">Admin Username</label>
                    <input type="text" name="admin_username" value="admin" required class="w-full bg-white border border-zinc-200 rounded-lg px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-zinc-600 mb-1">Admin Email</label>
                    <input type="email" name="admin_email" value="admin@apexsmm.com" required class="w-full bg-white border border-zinc-200 rounded-lg px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-zinc-600 mb-1">Admin Password</label>
                    <input type="password" name="admin_password" value="Admin@Apex2026!" required class="w-full bg-white border border-zinc-200 rounded-lg px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-zinc-600 mb-1">Application URL</label>
                    <input type="url" name="app_url" value="<?= htmlspecialchars($siteUrl) ?>" required class="w-full bg-white border border-zinc-200 rounded-lg px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
                </div>
            </div>

            <div class="flex items-center justify-between pt-2">
                <a href="/installer/index.php" class="text-xs text-zinc-500 hover:text-zinc-900 font-medium">&larr; Back to Requirements</a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs transition-all shadow-xs cursor-pointer">
                    Execute Installation
                </button>
            </div>
        </form>
    </div>
</body>
</html>
