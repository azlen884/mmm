<?php
/**
 * ApexSMM User Authentication Engine
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/validation.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/logger.php';

class Auth
{
    public static function check(): bool
    {
        return !empty($_SESSION['user_id']) && !empty($_SESSION['auth_logged_in']);
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        static $cachedUser = null;
        if ($cachedUser === null) {
            $cachedUser = Database::fetchOne(
                "SELECT id, username, email, role, balance, spent, status, api_key, created_at FROM users WHERE id = ?",
                [$_SESSION['user_id']]
            );
            if (!$cachedUser || $cachedUser['status'] !== 'active') {
                self::logout();
                return null;
            }
        }
        return $cachedUser;
    }

    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function requireLogin()
    {
        if (!self::check()) {
            if (
                strpos($_SERVER['REQUEST_URI'] ?? '', '/ajax/') !== false ||
                strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false
            ) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login.']);
                exit;
            }
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
            header('Location: /login.php');
            exit;
        }

        // Check maintenance mode
        if (get_setting('maintenance_mode', '0') === '1' && ($_SESSION['user_role'] ?? '') !== 'admin') {
            http_response_code(503);
            require __DIR__ . '/../errors/503.php';
            exit;
        }
    }

    public static function requireGuest()
    {
        if (self::check()) {
            if (($_SESSION['user_role'] ?? '') === 'admin') {
                header('Location: /admin/index.php');
            } else {
                header('Location: /user/dashboard.php');
            }
            exit;
        }
    }

    public static function attempt(string $login, string $password): array
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        // Rate limit by IP & username (5 attempts per 15 minutes)
        if (!RateLimiter::check('login', $ip . '_' . strtolower($login), 5, 900)) {
            return [
                'success' => false,
                'message' => 'Too many login attempts. Please wait 15 minutes before trying again.'
            ];
        }

        $user = Database::fetchOne(
            "SELECT id, username, email, password, role, balance, status, failed_logins, lock_until FROM users WHERE username = ? OR email = ?",
            [$login, $login]
        );

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid credentials provided.'];
        }

        // Check temporary account lock
        if (!empty($user['lock_until']) && strtotime($user['lock_until']) > time()) {
            $remaining = ceil((strtotime($user['lock_until']) - time()) / 60);
            return ['success' => false, 'message' => "Account is temporarily locked. Try again in {$remaining} minutes."];
        }

        if ($user['status'] === 'banned') {
            return ['success' => false, 'message' => 'This account has been permanently suspended.'];
        }

        if ($user['status'] === 'suspended') {
            return ['success' => false, 'message' => 'This account is currently suspended. Please contact support.'];
        }

        // Verify password
        if (!password_verify($password, $user['password'])) {
            $failed = (int)$user['failed_logins'] + 1;
            $lockUntil = null;
            if ($failed >= 5) {
                $lockUntil = date('Y-m-d H:i:s', time() + 900); // 15 min lock
                audit_log('account_locked', 'user', $user['id'], 'Locked due to 5 consecutive failed logins', $user['id']);
            }
            Database::execute(
                "UPDATE users SET failed_logins = ?, lock_until = ? WHERE id = ?",
                [$failed, $lockUntil, $user['id']]
            );

            return ['success' => false, 'message' => 'Invalid credentials provided.'];
        }

        // Successful authentication
        // Reset failed logins
        Database::execute(
            "UPDATE users SET failed_logins = 0, lock_until = NULL WHERE id = ?",
            [$user['id']]
        );

        // Regenerate session ID for fixation protection
        session_regenerate_id(true);

        $_SESSION['auth_logged_in'] = true;
        $_SESSION['user_id']        = (int)$user['id'];
        $_SESSION['username']       = $user['username'];
        $_SESSION['user_email']     = $user['email'];
        $_SESSION['user_role']      = $user['role'];
        if ($user['role'] === 'admin') {
            $_SESSION['admin_id']   = (int)$user['id'];
        }

        RateLimiter::clear('login', $ip . '_' . strtolower($login));
        audit_log('user_login', 'user', $user['id'], 'User logged in successfully', $user['id']);

        return ['success' => true, 'user' => $user];
    }

    public static function register(string $username, string $email, string $password): array
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        // Check if registration is enabled
        if (get_setting('registration_enabled', '1') !== '1') {
            return ['success' => false, 'message' => 'Registration is currently disabled by administrator.'];
        }

        // Rate limit registrations: max 3 per hour per IP
        if (!RateLimiter::check('register', $ip, 3, 3600)) {
            return ['success' => false, 'message' => 'Too many accounts created from this IP. Please try again later.'];
        }

        // Check duplicates
        $exists = Database::fetchOne(
            "SELECT id FROM users WHERE username = ? OR email = ?",
            [$username, $email]
        );
        if ($exists) {
            return ['success' => false, 'message' => 'A user with that username or email already exists.'];
        }

        $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $apiKey = bin2hex(random_bytes(32));

        Database::execute(
            "INSERT INTO users (username, email, password, role, balance, spent, status, api_key) 
             VALUES (?, ?, ?, 'user', 0.0000, 0.0000, 'active', ?)",
            [$username, $email, $hashed, $apiKey]
        );

        $newUserId = (int)Database::lastInsertId();
        audit_log('user_registered', 'user', $newUserId, 'New account registered', $newUserId);

        // Auto login
        return self::attempt($username, $password);
    }

    public static function logout(): void
    {
        if (!empty($_SESSION['user_id'])) {
            audit_log('user_logout', 'user', $_SESSION['user_id'], 'User logged out');
        }

        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        @session_destroy();
    }
}
