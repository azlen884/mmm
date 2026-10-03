<?php
/**
 * ApexSMM Admin - User Management
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

AdminAuth::requireAdmin();

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? 'all');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

$where = "WHERE 1=1";
$params = [];

if ($status !== 'all' && in_array($status, ['active', 'suspended', 'banned'])) {
    $where .= " AND status = ?";
    $params[] = $status;
}

if ($search !== '') {
    $where .= " AND (id = ? OR username LIKE ? OR email LIKE ?)";
    $params[] = is_numeric($search) ? (int)$search : 0;
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$total = (int)Database::fetchValue("SELECT COUNT(*) FROM users {$where}", $params);
$offset = ($page - 1) * $perPage;

$users = Database::fetchAll(
    "SELECT * FROM users {$where} ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}",
    $params
);

$totalPages = ceil($total / $perPage) ?: 1;

$activeNav = 'users';
$pageTitle = 'Manage Users | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">User Accounts</h1>
            <p class="text-xs text-slate-400 mt-1">Total registered: <?= number_format($total) ?> clients</p>
        </div>
    </div>

    <!-- Search & Filters -->
    <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-4 backdrop-blur-md">
        <form method="GET" action="/admin/users.php" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="sm:col-span-3">
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by user ID, username, or email..."
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-purple-500">
            </div>
            <div class="flex items-center space-x-2">
                <select name="status" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2.5 text-xs text-white focus:outline-none focus:border-purple-500">
                    <option value="all">All Statuses</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                    <option value="banned" <?= $status === 'banned' ? 'selected' : '' ?>>Banned</option>
                </select>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-purple-600 text-white font-semibold text-xs hover:bg-purple-500 transition-colors">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Users Table -->
    <?php if (!empty($users)): ?>
        <div class="overflow-x-auto bg-slate-900/60 border border-slate-800/80 rounded-2xl backdrop-blur-md shadow-xl">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-950/80 text-xs uppercase text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-5 py-4 w-16">ID</th>
                        <th class="px-5 py-4">Username</th>
                        <th class="px-5 py-4">Email</th>
                        <th class="px-5 py-4 text-center">Role</th>
                        <th class="px-5 py-4 text-right">Balance</th>
                        <th class="px-5 py-4 text-right">Spent</th>
                        <th class="px-5 py-4 text-center">Status</th>
                        <th class="px-5 py-4">Joined</th>
                        <th class="px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <?php foreach ($users as $u): ?>
                        <tr class="hover:bg-slate-800/30 transition-colors text-xs">
                            <td class="px-5 py-4 font-mono text-slate-400">#<?= (int)$u['id'] ?></td>
                            <td class="px-5 py-4 font-bold text-white"><?= e($u['username']) ?></td>
                            <td class="px-5 py-4 text-slate-300"><?= e($u['email']) ?></td>
                            <td class="px-5 py-4 text-center">
                                <span class="px-2 py-0.5 rounded text-[11px] font-semibold uppercase <?= $u['role'] === 'admin' ? 'bg-purple-500/10 text-purple-400 border border-purple-500/20' : 'bg-slate-800 text-slate-400' ?>">
                                    <?= e($u['role']) ?>
                                </span>
                            </td>
                            <td class="px-5 py-4 font-mono font-bold text-emerald-400 text-right">
                                <?= format_currency($u['balance']) ?>
                            </td>
                            <td class="px-5 py-4 font-mono text-slate-400 text-right">
                                <?= format_currency($u['spent']) ?>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <?= status_badge($u['status']) ?>
                            </td>
                            <td class="px-5 py-4 text-slate-400 whitespace-nowrap">
                                <?= format_date($u['created_at'], 'M d, Y') ?>
                            </td>
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <a href="/admin/user-view.php?id=<?= (int)$u['id'] ?>" class="px-3 py-1 rounded-lg bg-purple-600/10 text-purple-300 border border-purple-500/20 text-xs font-semibold hover:bg-purple-600 hover:text-white transition-all">
                                    Manage User &rarr;
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="flex items-center justify-between text-xs text-slate-400 pt-2">
                <div>Page <?= $page ?> of <?= $totalPages ?></div>
                <div class="flex space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="/admin/users.php?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>" class="px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-slate-300 hover:bg-slate-800">
                            &larr; Prev
                        </a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="/admin/users.php?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>" class="px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-slate-300 hover:bg-slate-800">
                            Next &rarr;
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-16 text-center text-xs text-slate-500">
            No user accounts found matching your query.
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
