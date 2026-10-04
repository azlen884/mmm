<?php
/**
 * ApexSMM Helper Functions
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/icons.php';

function get_setting(string $key, string $default = ''): string
{
    static $settingsCache = null;
    if ($settingsCache === null) {
        $settingsCache = [];
        try {
            $rows = Database::fetchAll("SELECT setting_key, setting_value FROM settings");
            foreach ($rows as $r) {
                $settingsCache[$r['setting_key']] = $r['setting_value'];
            }
        } catch (\Throwable $e) {
            // DB might be down or not installed yet
        }
    }
    return $settingsCache[$key] ?? $default;
}

function set_setting(string $key, ?string $value): void
{
    try {
        Database::execute(
            "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
            [$key, $value]
        );
    } catch (\Throwable $e) {
        Logger::error("Failed to update setting {$key}: " . $e->getMessage());
    }
}

function format_currency($amount, bool $showSymbol = true): string
{
    $formatted = number_format((float)$amount, 4);
    if ($showSymbol) {
        $sym = get_setting('currency_symbol', '$');
        return $sym . $formatted;
    }
    return $formatted;
}

function format_date(?string $datetime, string $format = 'M d, Y H:i'): string
{
    if (!$datetime) return 'N/A';
    return date($format, strtotime($datetime));
}

function time_ago(?string $datetime): string
{
    if (!$datetime) return 'Never';
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 2592000) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', $time);
}

function audit_log(string $action, ?string $entityType = null, $entityId = null, $details = null, ?int $userId = null, ?int $adminId = null): void
{
    try {
        if ($userId === null && isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
        }
        if ($adminId === null && isset($_SESSION['admin_id'])) {
            $adminId = $_SESSION['admin_id'];
        }

        $detailsStr = is_array($details) ? json_encode($details) : (string)$details;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $agent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 250);

        Database::execute(
            "INSERT INTO audit_logs (user_id, admin_id, action, entity_type, entity_id, ip_address, user_agent, details) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$userId, $adminId, $action, $entityType, (string)$entityId, $ip, $agent, $detailsStr]
        );
    } catch (\Throwable $e) {
        Logger::error("Audit log failed: " . $e->getMessage());
    }
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash_messages'][] = [
        'type'    => $type, // success, error, warning, info
        'message' => $message
    ];
}

function flash_get(): array
{
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

function url(string $path = ''): string
{
    $base = APP_URL;
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function status_badge(string $status): string
{
    $status = strtolower($status);
    $map = [
        'completed'      => 'bg-emerald-50 text-emerald-700 border border-emerald-200/80',
        'active'         => 'bg-emerald-50 text-emerald-700 border border-emerald-200/80',
        'success'        => 'bg-emerald-50 text-emerald-700 border border-emerald-200/80',
        'processing'     => 'bg-purple-50 text-purple-700 border border-purple-200/80',
        'in_progress'    => 'bg-purple-50 text-purple-700 border border-purple-200/80',
        'pending'        => 'bg-amber-50 text-amber-700 border border-amber-200/80',
        'open'           => 'bg-amber-50 text-amber-700 border border-amber-200/80',
        'customer_reply' => 'bg-blue-50 text-blue-700 border border-blue-200/80',
        'answered'       => 'bg-purple-50 text-purple-700 border border-purple-200/80',
        'partial'        => 'bg-purple-50 text-purple-700 border border-purple-200/80',
        'cancelled'      => 'bg-rose-50 text-rose-700 border border-rose-200/80',
        'canceled'       => 'bg-rose-50 text-rose-700 border border-rose-200/80',
        'refunded'       => 'bg-teal-50 text-teal-700 border border-teal-200/80',
        'failed'         => 'bg-rose-50 text-rose-700 border border-rose-200/80',
        'closed'         => 'bg-zinc-100 text-zinc-600 border border-zinc-200',
        'inactive'       => 'bg-zinc-100 text-zinc-600 border border-zinc-200',
        'banned'         => 'bg-rose-50 text-rose-700 border border-rose-200/80',
        'suspended'      => 'bg-amber-50 text-amber-700 border border-amber-200/80',
    ];

    $classes = $map[$status] ?? 'bg-zinc-100 text-zinc-600 border border-zinc-200';
    $label = ucwords(str_replace('_', ' ', $status));

    return sprintf(
        '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold %s">%s</span>',
        $classes,
        htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
    );
}
