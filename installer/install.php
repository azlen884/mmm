<?php
/**
 * ApexSMM Web Installer - Execution Engine
 */

if (file_exists(__DIR__ . '/../storage/installed.lock')) {
    header('Location: /login.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost   = trim($_POST['db_host'] ?? '127.0.0.1');
    $dbPort   = trim($_POST['db_port'] ?? '3306');
    $dbName   = trim($_POST['db_name'] ?? '');
    $dbUser   = trim($_POST['db_user'] ?? '');
    $dbPass   = trim($_POST['db_pass'] ?? '');
    $adminUser = trim($_POST['admin_username'] ?? 'admin');
    $adminMail = trim($_POST['admin_email'] ?? 'admin@apexsmm.com');
    $adminPass = trim($_POST['admin_password'] ?? '');
    $appUrl   = rtrim(trim($_POST['app_url'] ?? 'http://localhost:3000'), '/');

    try {
        // 1. Test PDO connection
        $dsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
        $pdo = new \PDO($dsn, $dbUser, $dbPass, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
        ]);

        // Create database if not exists
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$dbName}`");

        // 2. Import Schema
        $schemaFile = __DIR__ . '/../database/schema.sql';
        if (!file_exists($schemaFile)) {
            throw new \Exception("Schema file not found at database/schema.sql");
        }

        $sql = file_get_contents($schemaFile);
        $pdo->exec($sql);

        // 3. Create Admin User
        $hashedPassword = password_hash($adminPass, PASSWORD_BCRYPT, ['cost' => 12]);
        $apiKey = bin2hex(random_bytes(32));

        $stmt = $pdo->prepare(
            "INSERT INTO users (username, email, password, role, balance, spent, status, api_key) 
             VALUES (?, ?, ?, 'admin', 500.0000, 0.0000, 'active', ?)
             ON DUPLICATE KEY UPDATE password = VALUES(password), role = 'admin', status = 'active'"
        );
        $stmt->execute([$adminUser, $adminMail, $hashedPassword, $apiKey]);

        // 4. Update Site URL Setting
        $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('app_url', ?) ON DUPLICATE KEY UPDATE setting_value = ?")
            ->execute([$appUrl, $appUrl]);

        // 5. Generate / Update .env file
        $appSecret = bin2hex(random_bytes(32));
        $encKey = bin2hex(random_bytes(16));
        $cronKey = 'cron_' . bin2hex(random_bytes(16));

        $envContent = <<<EOT
APP_NAME=ApexSMM
APP_ENV=production
APP_DEBUG=false
APP_URL={$appUrl}
APP_SECRET={$appSecret}
ENCRYPTION_KEY={$encKey}

DB_HOST={$dbHost}
DB_PORT={$dbPort}
DB_NAME={$dbName}
DB_USER={$dbUser}
DB_PASS={$dbPass}

SESSION_LIFETIME=7200
CRON_KEY={$cronKey}
EOT;

        @file_put_contents(__DIR__ . '/../.env', $envContent);

        // 6. Lock Installer
        @file_put_contents(__DIR__ . '/../storage/installed.lock', date('Y-m-d H:i:s') . " - Installed successfully\n");

        $success = true;
    } catch (\Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ApexSMM Installation Status</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#0b0f19] text-slate-100 min-h-screen flex items-center justify-center p-4 antialiased selection:bg-blue-600 selection:text-white">
    <div class="w-full max-w-lg bg-slate-900/90 border border-slate-800 rounded-2xl shadow-2xl p-8 backdrop-blur-xl text-center">
        <?php if (!empty($success)): ?>
            <div class="w-16 h-16 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center mx-auto mb-4 text-2xl font-bold">
                &check;
            </div>
            <h2 class="text-2xl font-bold text-white mb-2">Installation Complete!</h2>
            <p class="text-sm text-slate-300 mb-6">
                ApexSMM has been initialized successfully. The installer has been permanently locked for security.
            </p>
            <div class="bg-slate-950/70 border border-slate-800 rounded-xl p-4 text-left text-xs space-y-1 mb-6 font-mono text-slate-300">
                <div><span class="text-slate-500">Admin Username:</span> <?= htmlspecialchars($adminUser ?? 'admin') ?></div>
                <div><span class="text-slate-500">Admin Portal:</span> /admin/login.php</div>
                <div><span class="text-slate-500">User Portal:</span> /login.php</div>
            </div>
            <a href="/login.php" class="inline-flex items-center justify-center px-6 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium hover:from-blue-500 hover:to-indigo-500 transition-all shadow-lg shadow-blue-500/25">
                Go to Sign In &rarr;
            </a>
        <?php else: ?>
            <div class="w-16 h-16 rounded-full bg-rose-500/10 border border-rose-500/20 text-rose-400 flex items-center justify-center mx-auto mb-4 text-2xl font-bold">
                &cross;
            </div>
            <h2 class="text-2xl font-bold text-white mb-2">Installation Encountered an Error</h2>
            <div class="bg-rose-500/10 border border-rose-500/20 text-rose-300 rounded-xl p-4 text-sm mb-6 text-left">
                <?= htmlspecialchars($error ?? 'An unexpected error occurred.') ?>
            </div>
            <a href="/installer/database.php" class="inline-flex items-center justify-center px-6 py-2.5 rounded-xl bg-slate-800 text-slate-200 font-medium hover:bg-slate-700 transition-all">
                &larr; Try Again
            </a>
        <?php endif; ?>
    </div>
</body>
</html>
