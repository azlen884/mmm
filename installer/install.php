<?php
/**
 * ApexSMM Web Installer - Execution Engine
 */

if (file_exists(__DIR__ . '/../storage/installed.lock') && empty($_GET['force']) && empty($_POST['force'])) {
    header('Location: /login.php');
    exit;
}

$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost       = trim($_POST['db_host'] ?? '127.0.0.1');
    $dbPort       = trim($_POST['db_port'] ?? '3306');
    $dbName       = trim($_POST['db_name'] ?? '');
    $dbUser       = trim($_POST['db_user'] ?? '');
    $dbPass       = trim($_POST['db_pass'] ?? '');
    $cleanInstall = !empty($_POST['clean_install']);
    $adminUser    = trim($_POST['admin_username'] ?? 'admin');
    $adminMail    = trim($_POST['admin_email'] ?? 'admin@apexsmm.com');
    $adminPass    = trim($_POST['admin_password'] ?? '');
    $appUrl       = rtrim(trim($_POST['app_url'] ?? 'http://localhost:3000'), '/');

    try {
        if ($dbName === '') {
            throw new \Exception("Database name cannot be empty.");
        }
        if ($adminPass === '') {
            throw new \Exception("Administrator password cannot be empty.");
        }

        // 1. Establish PDO Connection
        $dsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
        $pdo = new \PDO($dsn, $dbUser, $dbPass, [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_EMULATE_PREPARES   => true,
            \PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
        ]);

        // Create database if not exists and select it
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$dbName}`");

        // 2. Handle Clean Install / Pre-existing tables
        if ($cleanInstall) {
            $pdo->exec("
                SET FOREIGN_KEY_CHECKS = 0;
                DROP TABLE IF EXISTS `rate_limits`;
                DROP TABLE IF EXISTS `audit_logs`;
                DROP TABLE IF EXISTS `ticket_messages`;
                DROP TABLE IF EXISTS `tickets`;
                DROP TABLE IF EXISTS `notifications`;
                DROP TABLE IF EXISTS `payments`;
                DROP TABLE IF EXISTS `payment_gateways`;
                DROP TABLE IF EXISTS `transactions`;
                DROP TABLE IF EXISTS `orders`;
                DROP TABLE IF EXISTS `services`;
                DROP TABLE IF EXISTS `providers`;
                DROP TABLE IF EXISTS `categories`;
                DROP TABLE IF EXISTS `users`;
                DROP TABLE IF EXISTS `settings`;
                SET FOREIGN_KEY_CHECKS = 1;
            ");
        } else {
            // Proactive table repair if payment_gateways already existed with older/different schema
            try {
                $hasPg = $pdo->query("SHOW TABLES LIKE 'payment_gateways'")->fetch();
                if ($hasPg) {
                    $cols = $pdo->query("SHOW COLUMNS FROM `payment_gateways`")->fetchAll(\PDO::FETCH_COLUMN);
                    if (!in_array('min_amount', $cols)) {
                        if (in_array('min', $cols)) {
                            $pdo->exec("ALTER TABLE `payment_gateways` CHANGE COLUMN `min` `min_amount` DECIMAL(10, 2) NOT NULL DEFAULT 5.00");
                        } else {
                            $pdo->exec("ALTER TABLE `payment_gateways` ADD COLUMN `min_amount` DECIMAL(10, 2) NOT NULL DEFAULT 5.00");
                        }
                    }
                    if (!in_array('max_amount', $cols)) {
                        if (in_array('max', $cols)) {
                            $pdo->exec("ALTER TABLE `payment_gateways` CHANGE COLUMN `max` `max_amount` DECIMAL(10, 2) NOT NULL DEFAULT 1000.00");
                        } else {
                            $pdo->exec("ALTER TABLE `payment_gateways` ADD COLUMN `max_amount` DECIMAL(10, 2) NOT NULL DEFAULT 1000.00");
                        }
                    }
                    if (!in_array('fee_percentage', $cols)) {
                        $pdo->exec("ALTER TABLE `payment_gateways` ADD COLUMN `fee_percentage` DECIMAL(5, 2) NOT NULL DEFAULT 0.00");
                    }
                    if (!in_array('fee_fixed', $cols)) {
                        $pdo->exec("ALTER TABLE `payment_gateways` ADD COLUMN `fee_fixed` DECIMAL(10, 2) NOT NULL DEFAULT 0.00");
                    }
                    if (!in_array('status', $cols)) {
                        $pdo->exec("ALTER TABLE `payment_gateways` ADD COLUMN `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'inactive'");
                    }
                    if (!in_array('credentials', $cols)) {
                        $pdo->exec("ALTER TABLE `payment_gateways` ADD COLUMN `credentials` TEXT NULL");
                    }
                    if (!in_array('instructions', $cols)) {
                        $pdo->exec("ALTER TABLE `payment_gateways` ADD COLUMN `instructions` TEXT NULL");
                    }
                }
            } catch (\Throwable $migrationError) {
                // Table doesn't exist or is clean, proceed to schema import
            }
        }

        // 3. Import Schema
        $schemaFile = __DIR__ . '/../database/schema.sql';
        if (!file_exists($schemaFile)) {
            throw new \Exception("Schema file not found at database/schema.sql");
        }

        $sql = file_get_contents($schemaFile);
        $pdo->exec($sql);

        // 4. Create / Update Admin User
        $hashedPassword = password_hash($adminPass, PASSWORD_BCRYPT, ['cost' => 12]);
        $apiKey = 'smm_admin_' . bin2hex(random_bytes(16));

        $stmt = $pdo->prepare(
            "INSERT INTO users (username, email, password, role, balance, spent, status, api_key) 
             VALUES (?, ?, ?, 'admin', 500.0000, 0.0000, 'active', ?)
             ON DUPLICATE KEY UPDATE 
                password = VALUES(password), 
                email = VALUES(email), 
                role = 'admin', 
                status = 'active'"
        );
        $stmt->execute([$adminUser, $adminMail, $hashedPassword, $apiKey]);

        // 5. Create Demo User for Instant Testing
        $demoPass = password_hash('DemoUser123!', PASSWORD_BCRYPT, ['cost' => 12]);
        $demoApiKey = 'smm_demo_' . bin2hex(random_bytes(16));
        $pdo->prepare(
            "INSERT INTO users (username, email, password, role, balance, spent, status, api_key)
             VALUES ('demo', 'demo@apexsmm.com', ?, 'user', 150.0000, 0.0000, 'active', ?)
             ON DUPLICATE KEY UPDATE password = VALUES(password), status = 'active'"
        )->execute([$demoPass, $demoApiKey]);

        // 6. Update Site Settings
        $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('app_url', ?) ON DUPLICATE KEY UPDATE setting_value = ?")
            ->execute([$appUrl, $appUrl]);

        // 7. Write Environment (.env) File
        $appSecret = bin2hex(random_bytes(32));
        $encKey    = bin2hex(random_bytes(16));
        $cronKey   = 'cron_' . bin2hex(random_bytes(16));

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

        // 8. Lock Installer on 100% Success
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
                ApexSMM has been initialized successfully. The database schema, administrator account, and payment gateways are ready.
            </p>
            <div class="bg-slate-950/70 border border-slate-800 rounded-xl p-4 text-left text-xs space-y-1.5 mb-6 font-mono text-slate-300">
                <div><span class="text-slate-500">Admin Username:</span> <?= htmlspecialchars($adminUser ?? 'admin') ?></div>
                <div><span class="text-slate-500">Admin Portal:</span> <a href="/admin/login.php" class="text-blue-400 hover:underline">/admin/login.php</a></div>
                <div><span class="text-slate-500">User Portal:</span> <a href="/login.php" class="text-blue-400 hover:underline">/login.php</a></div>
                <div><span class="text-slate-500">Demo User:</span> demo / DemoUser123! ($150 balance)</div>
            </div>
            <a href="/login.php" class="inline-flex items-center justify-center px-6 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium hover:from-blue-500 hover:to-indigo-500 transition-all shadow-lg shadow-blue-500/25">
                Go to Sign In &rarr;
            </a>
        <?php else: ?>
            <div class="w-16 h-16 rounded-full bg-rose-500/10 border border-rose-500/20 text-rose-400 flex items-center justify-center mx-auto mb-4 text-2xl font-bold">
                &cross;
            </div>
            <h2 class="text-2xl font-bold text-white mb-2">Installation Encountered an Error</h2>
            <div class="bg-rose-500/10 border border-rose-500/20 text-rose-300 rounded-xl p-4 text-sm mb-6 text-left break-words">
                <?= htmlspecialchars($error ?? 'An unexpected error occurred.') ?>
            </div>
            <a href="/installer/database.php?unlock=1" class="inline-flex items-center justify-center px-6 py-2.5 rounded-xl bg-slate-800 text-slate-200 font-medium hover:bg-slate-700 transition-all">
                &larr; Try Again
            </a>
        <?php endif; ?>
    </div>
</body>
</html>
