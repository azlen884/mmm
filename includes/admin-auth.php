<?php
/**
 * ApexSMM Admin Authentication Guard
 */

require_once __DIR__ . '/auth.php';

class AdminAuth
{
    public static function check(): bool
    {
        return Auth::check() && (($_SESSION['user_role'] ?? '') === 'admin') && !empty($_SESSION['admin_id']);
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        return Auth::user();
    }

    public static function requireAdmin()
    {
        if (!self::check()) {
            $isJson = (
                strpos($_SERVER['REQUEST_URI'] ?? '', '/ajax/') !== false ||
                strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false
            );

            if ($isJson) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Access denied: Administrator privileges required.']);
                exit;
            }

            if (Auth::check()) {
                // Logged in as normal user trying to access admin
                http_response_code(403);
                require __DIR__ . '/../errors/403.php';
                exit;
            }

            $_SESSION['admin_intended_url'] = $_SERVER['REQUEST_URI'];
            header('Location: /admin/login.php');
            exit;
        }
    }

    public static function attempt(string $login, string $password): array
    {
        $result = Auth::attempt($login, $password);
        if (!$result['success']) {
            return $result;
        }

        $user = $result['user'];
        if ($user['role'] !== 'admin') {
            Auth::logout();
            return [
                'success' => false,
                'message' => 'Unauthorized. This portal is restricted to system administrators.'
            ];
        }

        $_SESSION['admin_id'] = (int)$user['id'];
        audit_log('admin_login', 'admin', $user['id'], 'Admin logged in to control panel', null, $user['id']);

        return ['success' => true, 'user' => $user];
    }
}
