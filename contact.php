<?php
/**
 * ApexSMM Contact & Support Directory
 * White + Premium Purple Design System
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
    <div class="text-center mb-12 gsap-fade-in">
        <h1 class="text-3xl sm:text-4xl font-extrabold text-zinc-900 tracking-tight">Contact Us</h1>
        <p class="text-xs text-zinc-500 mt-2">Have a question or require enterprise custom volume? We're here to assist.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 gsap-card">
        
        <div class="md:col-span-1 space-y-4">
            <div class="bg-white border border-zinc-200/80 rounded-2xl p-6 shadow-xs">
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center mb-3">
                    <?= icon('inbox', 'w-5 h-5') ?>
                </div>
                <div class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-1">Support Email</div>
                <div class="text-xs font-medium text-zinc-900 break-all"><?= e(get_setting('contact_email', 'support@apexsmm.com')) ?></div>
            </div>
            <div class="bg-white border border-zinc-200/80 rounded-2xl p-6 shadow-xs">
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center mb-3">
                    <?= icon('ticket', 'w-5 h-5') ?>
                </div>
                <div class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-1">Registered Users</div>
                <p class="text-xs text-zinc-500 mb-3 leading-relaxed">For order tracking, refill requests, or financial questions, please use internal support tickets for priority queue handling.</p>
                <a href="/user/tickets.php" class="inline-flex items-center space-x-1.5 text-xs font-semibold text-purple-600 hover:text-purple-700">
                    <span>Open Ticket Desk</span>
                    <?= icon('chevron-right', 'w-3.5 h-3.5') ?>
                </a>
            </div>
        </div>

        <div class="md:col-span-2">
            <div class="bg-white border border-zinc-200/80 rounded-2xl p-8 shadow-xs">
                
                <?php if ($success): ?>
                    <div class="p-6 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-center">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-2">
                            <?= icon('check', 'w-6 h-6') ?>
                        </div>
                        <h3 class="text-base font-bold text-zinc-900 mb-1">Inquiry Sent Successfully</h3>
                        <p class="text-xs text-zinc-600">Our customer team will respond to your email within 24 business hours.</p>
                    </div>
                <?php else: ?>
                    <?php if ($error): ?>
                        <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center space-x-2">
                            <?= icon('exclamation-circle', 'w-5 h-5 text-rose-600 shrink-0') ?>
                            <span><?= e($error) ?></span>
                        </div>
                    <?php endif; ?>

                    <form action="/contact.php" method="POST" class="space-y-4">
                        <?= csrf_field() ?>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 mb-1.5">Your Name</label>
                                <input type="text" name="name" required value="<?= e($_POST['name'] ?? '') ?>"
                                    class="w-full bg-white border border-zinc-200 rounded-xl px-4 py-2.5 text-xs text-zinc-900 placeholder-zinc-400 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 mb-1.5">Your Email</label>
                                <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"
                                    class="w-full bg-white border border-zinc-200 rounded-xl px-4 py-2.5 text-xs text-zinc-900 placeholder-zinc-400 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 mb-1.5">Subject</label>
                            <input type="text" name="subject" required value="<?= e($_POST['subject'] ?? '') ?>"
                                class="w-full bg-white border border-zinc-200 rounded-xl px-4 py-2.5 text-xs text-zinc-900 placeholder-zinc-400 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 mb-1.5">Message</label>
                            <textarea name="message" rows="5" required
                                class="w-full bg-white border border-zinc-200 rounded-xl px-4 py-2.5 text-xs text-zinc-900 placeholder-zinc-400 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600"><?= e($_POST['message'] ?? '') ?></textarea>
                        </div>

                        <button type="submit" class="w-full inline-flex items-center justify-center space-x-2 py-3 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs shadow-xs transition-all">
                            <?= icon('check', 'w-4 h-4') ?>
                            <span>Send Message</span>
                        </button>
                    </form>
                <?php endif; ?>

            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
