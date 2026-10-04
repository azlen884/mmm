<?php
/**
 * ApexSMM User Login Portal
 * White + Premium Purple Design System
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
    <div class="w-full max-w-md bg-white border border-zinc-200/80 rounded-3xl p-8 sm:p-10 shadow-sm gsap-card">
        
        <div class="text-center mb-8">
            <div class="w-12 h-12 rounded-2xl bg-purple-50 border border-purple-100 text-purple-600 flex items-center justify-center mx-auto mb-4 shadow-2xs">
                <?= icon('user', 'w-6 h-6') ?>
            </div>
            <h1 class="text-2xl font-black text-zinc-900 tracking-tight">Client Portal</h1>
            <p class="text-xs text-zinc-500 mt-1">Sign in to manage your orders and wallet balance</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center space-x-2.5">
                <?= icon('exclamation-circle', 'w-5 h-5 text-rose-600 shrink-0') ?>
                <span><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <form action="/login.php" method="POST" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 mb-1.5">Username or Email</label>
                <div class="relative">
                    <input type="text" name="login" value="<?= e($_POST['login'] ?? '') ?>" required autofocus
                        class="w-full bg-white border border-zinc-200 rounded-xl px-4 py-2.5 text-sm text-zinc-900 placeholder-zinc-400 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600 transition-colors"
                        placeholder="Enter your username or email">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 mb-1.5">Password</label>
                <input type="password" name="password" required
                    class="w-full bg-white border border-zinc-200 rounded-xl px-4 py-2.5 text-sm text-zinc-900 placeholder-zinc-400 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600 transition-colors"
                    placeholder="••••••••••••">
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full inline-flex items-center justify-center space-x-2 py-3 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-semibold text-sm shadow-sm shadow-purple-500/25 transition-all">
                    <?= icon('arrow-right-on-rectangle', 'w-4 h-4') ?>
                    <span>Sign In to Account</span>
                </button>
            </div>
        </form>

        <div class="mt-8 pt-6 border-t border-zinc-100 text-center text-xs text-zinc-500">
            Don't have an account? 
            <a href="/register.php" class="font-semibold text-purple-600 hover:text-purple-700 transition-colors ml-1">
                Sign up for free
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
