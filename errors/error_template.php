<?php
/**
 * ApexSMM Error Layout Helper
 */
function render_error_page(int $code, string $title, string $message, ?string $detail = null)
{
    if (!headers_sent()) {
        http_response_code($code);
    }
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($code . ' - ' . $title) ?> | ApexSMM</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#0b0f19] text-slate-100 min-h-screen flex items-center justify-center p-4 selection:bg-blue-600 selection:text-white">
    <div class="fixed inset-0 pointer-events-none overflow-hidden -z-10">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-blue-600/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-purple-600/10 rounded-full blur-3xl"></div>
    </div>
    
    <div class="w-full max-w-lg bg-slate-900/80 border border-slate-800 rounded-3xl p-8 sm:p-12 text-center backdrop-blur-xl shadow-2xl shadow-black/50">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-blue-500/10 border border-blue-500/20 text-blue-400 mb-6">
            <span class="text-3xl font-extrabold tracking-tight"><?= htmlspecialchars((string)$code) ?></span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-bold text-white tracking-tight mb-3"><?= htmlspecialchars($title) ?></h1>
        <p class="text-slate-400 text-sm sm:text-base leading-relaxed mb-8">
            <?= htmlspecialchars($message) ?>
        </p>
        <?php if ($detail && (defined('APP_DEBUG') && APP_DEBUG === true)): ?>
            <div class="bg-slate-950/70 border border-slate-800 rounded-xl p-3 text-xs font-mono text-rose-300 text-left mb-6 overflow-x-auto">
                <?= htmlspecialchars($detail) ?>
            </div>
        <?php endif; ?>
        <div class="flex items-center justify-center gap-3">
            <a href="/" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm font-medium hover:from-blue-500 hover:to-indigo-500 transition-all shadow-lg shadow-blue-500/25">
                Back to Homepage
            </a>
            <a href="javascript:history.back()" class="px-5 py-2.5 rounded-xl bg-slate-800 border border-slate-700 text-slate-300 text-sm font-medium hover:bg-slate-700 hover:text-white transition-all">
                Go Back
            </a>
        </div>
    </div>
</body>
</html>
<?php
    exit;
}
