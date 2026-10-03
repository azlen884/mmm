<?php
/**
 * ApexSMM Main Configuration Loader
 * Reads .env securely and defines environment constants.
 */

// Function to parse .env file
function loadEnv($filePath)
{
    if (!file_exists($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }

        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            // Strip surrounding quotes
            if (
                (strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
                (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)
            ) {
                $value = substr($value, 1, -1);
            }

            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv("$name=$value");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Load .env
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    loadEnv($envPath);
}

// App Constants
define('APP_NAME', getenv('APP_NAME') ?: 'ApexSMM');
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_DEBUG', filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN));
define('APP_URL', rtrim(getenv('APP_URL') ?: 'http://localhost:3000', '/'));
define('APP_SECRET', getenv('APP_SECRET') ?: 'default_apexsmm_secret_token_change_me_in_env');
define('ENCRYPTION_KEY', getenv('ENCRYPTION_KEY') ?: 'default_encryption_key_change_in_env');

// Database Constants
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'smm_panel');
define('DB_USER', getenv('DB_USER') ?: 'smm_user');
define('DB_PASS', getenv('DB_PASS') ?: 'smm_secure_pass_2026');

define('SESSION_LIFETIME', (int)(getenv('SESSION_LIFETIME') ?: 7200));
define('CRON_KEY', getenv('CRON_KEY') ?: 'smm_cron_secret_token_2026_apex');

// Error reporting configuration
if (APP_ENV === 'development' || APP_DEBUG === true) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}

// Include Security & Error Handling
require_once __DIR__ . '/security.php';
