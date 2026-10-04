<?php
/**
 * ApexSMM Error Layout Helper
 * White + Premium Purple Design System
 */
function render_error_page(int $code, string $title, string $message, ?string $detail = null)
{
    if (!headers_sent()) {
        http_response_code($code);
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($code . ' - ' . $title) ?> | ApexSMM</title>
    <link rel="stylesheet" href="/dist/style.css">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="bg-[#FAF5FF] text-[#18181B] min-h-screen flex items-center justify-center p-4 selection:bg-[#7C3AED] selection:text-white font-sans antialiased">
    <div class="fixed inset-0 pointer-events-none overflow-hidden -z-10">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-[#7C3AED]/5 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-[#5B21B6]/5 rounded-full blur-3xl"></div>
    </div>
    
    <div class="w-full max-w-lg bg-white border border-[#E4E4E7] rounded-3xl p-8 sm:p-12 text-center shadow-xl shadow-purple-900/5">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-[#F3E8FF] border border-[#DDD6FE] text-[#7C3AED] mb-6">
            <span class="text-3xl font-extrabold tracking-tight"><?= htmlspecialchars((string)$code) ?></span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-bold text-[#18181B] tracking-tight mb-3"><?= htmlspecialchars($title) ?></h1>
        <p class="text-[#71717A] text-sm sm:text-base leading-relaxed mb-8">
            <?= htmlspecialchars($message) ?>
        </p>
        <?php if ($detail && (defined('APP_DEBUG') && APP_DEBUG === true)): ?>
            <div class="bg-red-50 border border-red-200 rounded-xl p-3 text-xs font-mono text-red-600 text-left mb-6 overflow-x-auto">
                <?= htmlspecialchars($detail) ?>
            </div>
        <?php endif; ?>
        <div class="flex items-center justify-center gap-3">
            <a href="/" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#7C3AED] hover:bg-[#6D28D9] text-white text-sm font-semibold transition-all shadow-sm hover:shadow-md">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
                Back to Homepage
            </a>
            <a href="javascript:history.back()" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-white border border-[#E4E4E7] text-[#18181B] text-sm font-medium hover:bg-[#FAF5FF] hover:border-[#DDD6FE] hover:text-[#7C3AED] transition-all">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                Go Back
            </a>
        </div>
    </div>
</body>
</html>
<?php
    exit;
}
