<?php
/**
 * ApexSMM Administrator Login Portal
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/functions.php';

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
<html lang="en" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login | <?= e(get_setting('site_name', 'ApexSMM')) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#080c14] text-slate-100 min-h-screen flex items-center justify-center p-4 antialiased selection:bg-purple-600 selection:text-white">
    <div class="fixed inset-0 pointer-events-none overflow-hidden -z-10">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-purple-600/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-indigo-600/10 rounded-full blur-3xl"></div>
    </div>

    <div class="w-full max-w-md bg-slate-900/90 border border-slate-800 rounded-3xl p-8 sm:p-10 backdrop-blur-xl shadow-2xl shadow-black/80">
        <div class="text-center mb-8">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-purple-600 via-indigo-600 to-blue-600 flex items-center justify-center font-black text-xl text-white mx-auto mb-4 shadow-lg shadow-purple-500/30">
                A
            </div>
            <h1 class="text-2xl font-black text-white tracking-tight">Staff Authentication</h1>
            <p class="text-xs text-slate-400 mt-1">Restricted administrative access only</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs flex items-center space-x-2">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <form action="/admin/login.php" method="POST" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Administrator Username</label>
                <input type="text" name="login" value="<?= e($_POST['login'] ?? '') ?>" required autofocus
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-purple-500 transition-colors">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Password</label>
                <input type="password" name="password" required
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-purple-500 transition-colors">
            </div>

            <button type="submit" class="w-full py-3 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-bold text-xs shadow-lg shadow-purple-500/25 hover:from-purple-500 hover:to-indigo-500 transition-all">
                Authenticate Staff &rarr;
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-slate-800 text-center">
            <a href="/" class="text-xs text-slate-500 hover:text-slate-300 transition-colors">&larr; Return to Public Website</a>
        </div>
    </div>
</body>
</html>
