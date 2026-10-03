<?php
/**
 * ApexSMM Centralized CSRF Protection
 */

require_once __DIR__ . '/session.php';

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function validate_csrf(?string $token = null): bool
{
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    }

    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

function require_csrf()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validate_csrf()) {
            Logger::warning('CSRF validation failure', [
                'uri'    => $_SERVER['REQUEST_URI'] ?? '',
                'ip'     => $_SERVER['REMOTE_ADDR'] ?? '',
                'method' => $_SERVER['REQUEST_METHOD'] ?? '',
            ]);

            $isJson = (
                strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false ||
                strpos($_SERVER['REQUEST_URI'] ?? '', '/ajax/') !== false ||
                (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
                (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            );

            if ($isJson) {
                http_response_code(419);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'status'  => 'error',
                    'message' => 'CSRF verification failed. Please refresh the page and try again.'
                ]);
                exit;
            }

            http_response_code(419);
            $errorPage = __DIR__ . '/../errors/419.php';
            if (file_exists($errorPage)) {
                require $errorPage;
            } else {
                echo '<!DOCTYPE html><html><body style="background:#0f172a;color:#f8fafc;font-family:sans-serif;text-align:center;padding:50px;"><h1>419 - Page Expired</h1><p>CSRF Token Mismatch. Please go back and try again.</p></body></html>';
            }
            exit;
        }
    }
}
