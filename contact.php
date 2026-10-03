<?php
/**
 * ApexSMM Contact & Support Directory
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';

$success = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || $email === '' || $subject === '' || $message === '') {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
    } else {
        // Log contact message to application log
        Logger::info("Public contact inquiry from {$email} [{$name}]: {$subject}", ['message' => $message]);
        $success = true;
    }
}

$pageTitle = 'Contact & Support | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="text-center mb-12">
        <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">Contact Us</h1>
        <p class="text-sm text-slate-400 mt-2">Have a question or require enterprise custom volume? We're here to assist.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        
        <div class="md:col-span-1 space-y-4">
            <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6">
                <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Support Email</div>
                <div class="text-sm font-medium text-white"><?= e(get_setting('contact_email', 'support@apexsmm.com')) ?></div>
            </div>
            <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6">
                <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Registered Users</div>
                <p class="text-xs text-slate-400 mb-3">For order tracking, refill requests, or financial questions, please use internal support tickets for priority queue handling.</p>
                <a href="/user/tickets.php" class="inline-flex text-xs font-semibold text-blue-400 hover:text-blue-300">
                    Open Ticket Desk &rarr;
                </a>
            </div>
        </div>

        <div class="md:col-span-2">
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-8 backdrop-blur-xl shadow-xl">
                
                <?php if ($success): ?>
                    <div class="p-6 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-center">
                        <svg class="w-10 h-10 mx-auto mb-2 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <h3 class="text-lg font-bold text-white mb-1">Inquiry Sent Successfully</h3>
                        <p class="text-xs text-slate-300">Our customer team will respond to your email within 24 business hours.</p>
                    </div>
                <?php else: ?>
                    <?php if ($error): ?>
                        <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs">
                            <?= e($error) ?>
                        </div>
                    <?php endif; ?>

                    <form action="/contact.php" method="POST" class="space-y-4">
                        <?= csrf_field() ?>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Your Name</label>
                                <input type="text" name="name" required value="<?= e($_POST['name'] ?? '') ?>"
                                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Your Email</label>
                                <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"
                                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Subject</label>
                            <input type="text" name="subject" required value="<?= e($_POST['subject'] ?? '') ?>"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Message</label>
                            <textarea name="message" rows="5" required
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500"><?= e($_POST['message'] ?? '') ?></textarea>
                        </div>

                        <button type="submit" class="w-full py-3 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold text-sm shadow-lg shadow-blue-500/25 hover:from-blue-500 hover:to-indigo-500 transition-all">
                            Send Message &rarr;
                        </button>
                    </form>
                <?php endif; ?>

            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
