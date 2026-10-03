<?php
/**
 * ApexSMM PHP Built-in Server Router
 * Emulates Apache mod_rewrite & security rules when running via `php -S`
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $uri;

// Block direct access to sensitive paths
if (
    preg_match('#^\.env#', $uri) ||
    preg_match('#^/\.env#', $uri) ||
    preg_match('#^/\.git#', $uri) ||
    preg_match('#^/database/#', $uri) ||
    preg_match('#^/storage/logs/#', $uri) ||
    preg_match('#^/storage/cache/#', $uri) ||
    preg_match('#^/storage/backups/#', $uri)
) {
    http_response_code(403);
    require_once __DIR__ . '/errors/403.php';
    exit;
}

// Serve existing static files (css, js, images, etc.)
if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    // Prevent execution of uploaded PHP scripts
    if (strpos($uri, '/storage/uploads/') === 0 && preg_match('/\.php$/i', $file)) {
        http_response_code(403);
        require_once __DIR__ . '/errors/403.php';
        exit;
    }
    return false; // let built-in server serve static file
}

// Rewrite friendly URLs
$routes = [
    '/' => '/index.php',
    '/dashboard' => '/user/dashboard.php',
    '/services' => '/services.php',
    '/faq' => '/faq.php',
    '/how-it-works' => '/how-it-works.php',
    '/contact' => '/contact.php',
    '/login' => '/login.php',
    '/register' => '/register.php',
    '/logout' => '/logout.php',
    '/admin' => '/admin/index.php',
    '/api/v2' => '/api/user-api.php',
    '/api/v1' => '/api/user-api.php',
    '/api' => '/api/user-api.php',
];

if (isset($routes[$uri])) {
    require_once __DIR__ . $routes[$uri];
    exit;
}

// Check if direct PHP file exists (e.g. /user/orders.php or /admin/services.php)
if (file_exists($file . '.php')) {
    require_once $file . '.php';
    exit;
}

if (is_dir($file) && file_exists($file . '/index.php')) {
    require_once $file . '/index.php';
    exit;
}

if (file_exists($file)) {
    require_once $file;
    exit;
}

// 404 handler
http_response_code(404);
require_once __DIR__ . '/errors/404.php';
exit;
