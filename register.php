<?php
/**
 * ApexSMM User Registration Portal
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
    <div class="w-full max-w-md bg-slate-900/80 border border-slate-800 rounded-3xl p-8 sm:p-10 backdrop-blur-xl shadow-2xl shadow-black/50">
        
        <div class="text-center mb-8">
            <h1 class="text-2xl font-black text-white tracking-tight">Create Account</h1>
            <p class="text-sm text-slate-400 mt-1">Get immediate access to high-velocity social growth</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm flex items-center space-x-2">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <form action="/register.php" method="POST" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Username</label>
                <input type="text" name="username" value="<?= e($_POST['username'] ?? '') ?>" required
                    class="w-full bg-slate-950/70 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 transition-colors"
                    placeholder="Choose a username">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Email Address</label>
                <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required
                    class="w-full bg-slate-950/70 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 transition-colors"
                    placeholder="name@example.com">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Password</label>
                <input type="password" name="password" required
                    class="w-full bg-slate-950/70 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 transition-colors"
                    placeholder="At least 8 characters">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Confirm Password</label>
                <input type="password" name="password_confirm" required
                    class="w-full bg-slate-950/70 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 transition-colors"
                    placeholder="Repeat your password">
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 text-white font-semibold text-sm shadow-lg shadow-blue-500/25 hover:shadow-blue-500/40 hover:scale-[1.01] transition-all">
                    Register Account &rarr;
                </button>
            </div>
        </form>

        <div class="mt-8 pt-6 border-t border-slate-800/80 text-center text-sm text-slate-400">
            Already have an account? 
            <a href="/login.php" class="font-semibold text-blue-400 hover:text-blue-300 transition-colors">
                Sign in here
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
