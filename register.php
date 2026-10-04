<?php
/**
 * ApexSMM User Registration Portal
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

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['password_confirm'] ?? '');

    $validator = new Validator([
        'username'         => $username,
        'email'            => $email,
        'password'         => $password,
        'password_confirm' => $confirm,
    ]);

    $validator->required('username')->username('username')
              ->required('email')->email('email')
              ->required('password')->minLength('password', 8)
              ->matches('password_confirm', 'password', 'Passwords do not match.');

    if ($validator->isValid()) {
        $result = Auth::register($username, $email, $password);
        if ($result['success']) {
            flash_set('success', 'Welcome to ApexSMM! Your account has been initialized.');
            header('Location: /user/dashboard.php');
            exit;
        } else {
            $error = $result['message'];
        }
    } else {
        $error = $validator->getFirstError();
    }
}

$pageTitle = 'Create Account | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="min-h-[75vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="w-full max-w-md bg-white border border-zinc-200/80 rounded-3xl p-8 sm:p-10 shadow-sm gsap-card">
        
        <div class="text-center mb-8">
            <div class="w-12 h-12 rounded-2xl bg-purple-50 border border-purple-100 text-purple-600 flex items-center justify-center mx-auto mb-4 shadow-2xs">
                <?= icon('user', 'w-6 h-6') ?>
            </div>
            <h1 class="text-2xl font-black text-zinc-900 tracking-tight">Create Account</h1>
            <p class="text-xs text-zinc-500 mt-1">Get immediate access to high-velocity social growth</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center space-x-2.5">
                <?= icon('exclamation-circle', 'w-5 h-5 text-rose-600 shrink-0') ?>
                <span><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <form action="/register.php" method="POST" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 mb-1.5">Username</label>
                <input type="text" name="username" value="<?= e($_POST['username'] ?? '') ?>" required
                    class="w-full bg-white border border-zinc-200 rounded-xl px-4 py-2.5 text-sm text-zinc-900 placeholder-zinc-400 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600 transition-colors"
                    placeholder="Choose a username">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 mb-1.5">Email Address</label>
                <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required
                    class="w-full bg-white border border-zinc-200 rounded-xl px-4 py-2.5 text-sm text-zinc-900 placeholder-zinc-400 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600 transition-colors"
                    placeholder="name@example.com">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 mb-1.5">Password</label>
                <input type="password" name="password" required
                    class="w-full bg-white border border-zinc-200 rounded-xl px-4 py-2.5 text-sm text-zinc-900 placeholder-zinc-400 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600 transition-colors"
                    placeholder="At least 8 characters">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 mb-1.5">Confirm Password</label>
                <input type="password" name="password_confirm" required
                    class="w-full bg-white border border-zinc-200 rounded-xl px-4 py-2.5 text-sm text-zinc-900 placeholder-zinc-400 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600 transition-colors"
                    placeholder="Repeat your password">
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full inline-flex items-center justify-center space-x-2 py-3 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-semibold text-sm shadow-sm shadow-purple-500/25 transition-all">
                    <?= icon('plus-circle', 'w-4 h-4') ?>
                    <span>Register Account</span>
                </button>
            </div>
        </form>

        <div class="mt-8 pt-6 border-t border-zinc-100 text-center text-xs text-zinc-500">
            Already have an account? 
            <a href="/login.php" class="font-semibold text-purple-600 hover:text-purple-700 transition-colors ml-1">
                Sign in here
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
