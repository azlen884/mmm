<?php
/**
 * ApexSMM User Login Portal
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/functions.php';

Auth::requireGuest();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $login    = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $validator = new Validator(['login' => $login, 'password' => $password]);
    $validator->required('login', 'Please enter your username or email.')
              ->required('password', 'Please enter your password.');

    if ($validator->isValid()) {
        $result = Auth::attempt($login, $password);
        if ($result['success']) {
            $redirect = $_SESSION['intended_url'] ?? '/user/dashboard.php';
            unset($_SESSION['intended_url']);
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = $result['message'];
        }
    } else {
        $error = $validator->getFirstError();
    }
}

$pageTitle = 'Sign In | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="min-h-[75vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="w-full max-w-md bg-slate-900/80 border border-slate-800 rounded-3xl p-8 sm:p-10 backdrop-blur-xl shadow-2xl shadow-black/50">
        
        <div class="text-center mb-8">
            <h1 class="text-2xl font-black text-white tracking-tight">Client Portal</h1>
            <p class="text-sm text-slate-400 mt-1">Sign in to manage your orders and wallet balance</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm flex items-center space-x-2">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <form action="/login.php" method="POST" class="space-y-5">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Username or Email</label>
                <input type="text" name="login" value="<?= e($_POST['login'] ?? '') ?>" required autofocus
                    class="w-full bg-slate-950/70 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 transition-colors"
                    placeholder="Enter your username or email">
            </div>

            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400">Password</label>
                </div>
                <input type="password" name="password" required
                    class="w-full bg-slate-950/70 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 transition-colors"
                    placeholder="••••••••••••">
            </div>

            <button type="submit" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 text-white font-semibold text-sm shadow-lg shadow-blue-500/25 hover:shadow-blue-500/40 hover:scale-[1.01] transition-all">
                Sign In to Account &rarr;
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-slate-800/80 text-center text-sm text-slate-400">
            Don't have an account? 
            <a href="/register.php" class="font-semibold text-blue-400 hover:text-blue-300 transition-colors">
                Sign up for free
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
