<?php
/**
 * ApexSMM Enterprise Logger
 * Secure, sanitized logging for application, database, API, provider, and payment events.
 */

class Logger
{
    private static $logDir = __DIR__ . '/../storage/logs/';

    private static function sanitize($data)
    {
        if (is_array($data)) {
            $cleaned = [];
            foreach ($data as $key => $value) {
                if (preg_match('/(pass|password|secret|key|token|cvv|card|authorization|cookie)/i', (string)$key)) {
                    $cleaned[$key] = '***REDACTED***';
                } elseif (is_array($value)) {
                    $cleaned[$key] = self::sanitize($value);
                } else {
                    $cleaned[$key] = $value;
                }
            }
            return $cleaned;
        }

        if (is_string($data)) {
            $data = preg_replace('/(password|secret|api_key|token)=([^\s&]+)/i', '$1=***REDACTED***', $data);
            $data = preg_replace('/"([^"]*(?:password|secret|key|token)[^"]*)":\s*"([^"]+)"/i', '"$1":"***REDACTED***"', $data);
        }
        return $data;
    }

    private static function write($file, $level, $message, $context = [])
    {
        try {
            if (!is_dir(self::$logDir)) {
                @mkdir(self::$logDir, 0755, true);
            }

            $userId = null;
            $adminId = null;
            if (session_status() === PHP_SESSION_ACTIVE) {
                $userId = $_SESSION['user_id'] ?? null;
                $adminId = $_SESSION['admin_id'] ?? null;
            }

            $entry = [
                'timestamp' => date('Y-m-d H:i:s'),
                'level'     => strtoupper($level),
                'uri'       => $_SERVER['REQUEST_URI'] ?? 'CLI',
                'method'    => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
                'ip'        => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'user_id'   => $userId,
                'admin_id'  => $adminId,
                'message'   => $message,
                'context'   => self::sanitize($context),
            ];

            $line = json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
            @file_put_contents(self::$logDir . $file, $line, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            // Fail silently to never break request
        }
    }

    public static function info($message, $context = [])
    {
        self::write('app.log', 'INFO', $message, $context);
    }

    public static function warning($message, $context = [])
    {
        self::write('app.log', 'WARNING', $message, $context);
    }

    public static function error($message, $context = [])
    {
        self::write('app.log', 'ERROR', $message, $context);
    }

    public static function db($message, $context = [])
    {
        self::write('database.log', 'DB_ERROR', $message, $context);
    }

    public static function api($message, $context = [])
    {
        self::write('api.log', 'API_LOG', $message, $context);
    }

    public static function provider($message, $context = [])
    {
        self::write('provider.log', 'PROVIDER_LOG', $message, $context);
    }

    public static function payment($message, $context = [])
    {
        self::write('payment.log', 'PAYMENT_LOG', $message, $context);
    }
}
