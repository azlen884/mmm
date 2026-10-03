<?php
/**
 * ApexSMM Security & Global Error Handling
 * Prevents white/blank pages, sets security headers, and handles all errors/exceptions.
 */

require_once __DIR__ . '/logger.php';

// Global Security Headers
if (!headers_sent()) {
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: SAMEORIGIN");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
    }
}

// XSS Escaper Helper
function e($string)
{
    if ($string === null) {
        return '';
    }
    return htmlspecialchars((string)$string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Global Exception Handler
function globalExceptionHandler(\Throwable $e)
{
    $isDebug = defined('APP_DEBUG') && APP_DEBUG === true;
    
    // Log exception safely
    Logger::error('Uncaught Exception: ' . $e->getMessage(), [
        'file'  => $e->getFile(),
        'line'  => $e->getLine(),
        'trace' => $isDebug ? $e->getTraceAsString() : 'REDACTED',
    ]);

    // Check if request is AJAX or API
    $isJson = false;
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (
        strpos($uri, '/api/') !== false ||
        strpos($uri, '/ajax/') !== false ||
        (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
        (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    ) {
        $isJson = true;
    }

    if (!headers_sent()) {
        http_response_code(500);
    }

    if ($isJson) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode([
            'success' => false,
            'status'  => 'error',
            'message' => $isDebug ? $e->getMessage() : 'An internal error occurred. Please try again later.',
        ]);
        exit;
    }

    // Render 500 error page
    $errorMessage = $isDebug ? $e->getMessage() : 'An unexpected error occurred. Please try again later.';
    $errorFile = $isDebug ? $e->getFile() . ':' . $e->getLine() : '';
    
    $errorPage = __DIR__ . '/../errors/500.php';
    if (file_exists($errorPage)) {
        require $errorPage;
    } else {
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Error</title></head><body style="background:#0f172a;color:#f8fafc;font-family:sans-serif;padding:50px;text-align:center;"><h1>500 - Something went wrong</h1><p>' . e($errorMessage) . '</p></body></html>';
    }
    exit;
}

// Global PHP Error Handler
function globalErrorHandler($errno, $errstr, $errfile, $errline)
{
    if (!(error_reporting() & $errno)) {
        return false;
    }

    $severity = 'ERROR';
    if ($errno === E_WARNING || $errno === E_USER_WARNING) $severity = 'WARNING';
    if ($errno === E_NOTICE || $errno === E_USER_NOTICE) $severity = 'NOTICE';

    Logger::warning("PHP $severity: $errstr", [
        'file' => $errfile,
        'line' => $errline,
    ]);

    // In production or on fatal errors, convert to ErrorException
    if ($errno === E_USER_ERROR || $errno === E_RECOVERABLE_ERROR) {
        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    return true;
}

// Shutdown Handler to catch fatal PHP errors and prevent blank screens
function globalShutdownHandler()
{
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        Logger::error('Fatal Shutdown Error: ' . $error['message'], [
            'file' => $error['file'],
            'line' => $error['line'],
        ]);

        if (!headers_sent()) {
            http_response_code(500);
        }

        $isJson = (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false || strpos($_SERVER['REQUEST_URI'] ?? '', '/ajax/') !== false);
        if ($isJson) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode([
                'success' => false,
                'status'  => 'error',
                'message' => 'A fatal server error occurred.',
            ]);
            exit;
        }

        $errorPage = __DIR__ . '/../errors/500.php';
        if (file_exists($errorPage)) {
            require $errorPage;
        } else {
            echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Server Error</title></head><body style="background:#0f172a;color:#f8fafc;font-family:sans-serif;padding:50px;text-align:center;"><h1>500 - Server Error</h1><p>Something went wrong on our end.</p></body></html>';
        }
    }
}

// Register Handlers
set_exception_handler('globalExceptionHandler');
set_error_handler('globalErrorHandler');
register_shutdown_function('globalShutdownHandler');
