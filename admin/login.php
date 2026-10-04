<?php
/**
 * ApexSMM Administrator Login Portal
 * White + Premium Purple Design System
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';

if (AdminAuth::check()) {
    header('Location: /admin/index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $login    = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $validator = new Validator(['login' => $login, 'password' => $password]);
    $validator->required('login')->required('password');

    if ($validator->isValid()) {
        $result = AdminAuth::attempt($login, $password);
        if ($result['success']) {
            $dest = $_SESSION['admin_intended_url'] ?? '/admin/index.php';
            unset($_SESSION['admin_intended_url']);
            header('Location: ' . $dest);
            exit;
        } else {
            $error = $result['message'];
        }
    } else {
        $error = $validator->getFirstError();
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login | <?= e(get_setting('site_name', 'ApexSMM')) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <script defer src="/assets/js/app.js"></script>
</head>
<body class="bg-[#f8fafc] text-zinc-900 min-h-screen flex items-center justify-center p-4 antialiased selection:bg-purple-600 selection:text-white">
    <div class="w-full max-w-md bg-white border border-zinc-200/80 rounded-3xl p-8 sm:p-10 shadow-sm gsap-card">
        <div class="text-center mb-8">
            <div class="w-12 h-12 rounded-2xl bg-purple-50 border border-purple-100 text-purple-700 flex items-center justify-center mx-auto mb-4 shadow-2xs font-extrabold text-base">
                A
            </div>
            <h1 class="text-2xl font-black text-zinc-900 tracking-tight">Staff Authentication</h1>
            <p class="text-xs text-zinc-500 mt-1">Restricted administrative access only</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center space-x-2">
                <?= icon('exclamation-circle', 'w-4 h-4 text-rose-600 shrink-0') ?>
                <span><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <form action="/admin/login.php" method="POST" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 mb-1.5">Administrator Username</label>
                <input type="text" name="login" value="<?= e($_POST['login'] ?? '') ?>" required autofocus
                    class="w-full bg-white border border-zinc-200 rounded-xl px-4 py-2.5 text-xs text-zinc-900 placeholder-zinc-400 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600 transition-colors">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 mb-1.5">Password</label>
                <input type="password" name="password" required
                    class="w-full bg-white border border-zinc-200 rounded-xl px-4 py-2.5 text-xs text-zinc-900 placeholder-zinc-400 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600 transition-colors">
            </div>

            <button type="submit" class="w-full inline-flex items-center justify-center space-x-2 py-3 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs shadow-xs transition-all">
                <?= icon('arrow-right-on-rectangle', 'w-4 h-4') ?>
                <span>Authenticate Staff</span>
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-zinc-100 text-center">
            <a href="/" class="inline-flex items-center space-x-1 text-xs text-zinc-500 hover:text-purple-700 transition-colors">
                <?= icon('chevron-left', 'w-3.5 h-3.5') ?>
                <span>Return to Public Website</span>
            </a>
        </div>
    </div>
</body>
</html>
