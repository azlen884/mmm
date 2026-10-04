<?php
/**
 * ApexSMM Admin - Categories Management
 * White + Premium Purple Design System
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

AdminAuth::requireAdmin();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = trim($_POST['action'] ?? '');

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $sort = (int)($_POST['sort_order'] ?? 0);
        if ($name === '') {
            $error = 'Category name cannot be empty.';
        } else {
            Database::execute("INSERT INTO categories (name, sort_order, status) VALUES (?, ?, 'active')", [$name, $sort]);
            audit_log('admin_created_category', 'category', Database::lastInsertId(), "Created category: {$name}");
            flash_set('success', 'Category added.');
            header('Location: /admin/categories.php');
            exit;
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            Database::execute("DELETE FROM categories WHERE id = ?", [$id]);
            audit_log('admin_deleted_category', 'category', $id, "Deleted category #{$id}");
            flash_set('info', 'Category deleted.');
            header('Location: /admin/categories.php');
            exit;
        }
    }
}

$categories = Database::fetchAll(
    "SELECT c.*, COUNT(s.id) as service_count 
     FROM categories c 
     LEFT JOIN services s ON c.id = s.category_id 
     GROUP BY c.id 
     ORDER BY c.sort_order ASC, c.name ASC"
);

$activeNav = 'categories';
$pageTitle = 'Manage Categories | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">
    <div class="flex items-center justify-between gsap-fade-in">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 tracking-tight">Service Categories</h1>
            <p class="text-xs text-zinc-500 mt-1">Organize social platforms and package classifications.</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center space-x-2">
            <?= icon('exclamation-circle', 'w-4 h-4 text-rose-600 shrink-0') ?>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 gsap-card">
        
        <!-- Add Category Form -->
        <div class="lg:col-span-1">
            <div class="bg-white border border-zinc-200/80 rounded-2xl p-6 shadow-xs space-y-4">
                <h3 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">New Category</h3>

                <form action="/admin/categories.php" method="POST" class="space-y-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">

                    <div>
                        <label class="block text-xs font-semibold uppercase text-zinc-600 mb-1.5">Category Name</label>
                        <input type="text" name="name" required placeholder="e.g. Instagram Followers"
                            class="w-full bg-white border border-zinc-200 rounded-xl px-4 py-2.5 text-xs text-zinc-900 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-zinc-600 mb-1.5">Sort Order</label>
                        <input type="number" name="sort_order" value="0" required
                            class="w-full bg-white border border-zinc-200 rounded-xl px-4 py-2.5 text-xs text-zinc-900 font-mono focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
                    </div>

                    <button type="submit" class="w-full inline-flex items-center justify-center space-x-1.5 py-2.5 rounded-xl bg-purple-600 text-white font-semibold text-xs hover:bg-purple-700 shadow-xs transition-colors">
                        <?= icon('plus', 'w-3.5 h-3.5') ?>
                        <span>Create Category</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Categories List -->
        <div class="lg:col-span-2">
            <div class="bg-white border border-zinc-200/80 rounded-2xl p-6 shadow-xs">
                <?php if (!empty($categories)): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-zinc-700">
                            <thead class="text-xs uppercase font-semibold text-zinc-500 border-b border-zinc-200 pb-2">
                                <tr>
                                    <th class="py-3 px-4 w-12">Sort</th>
                                    <th class="py-3 px-4">Category Name</th>
                                    <th class="py-3 px-4 text-center">Services</th>
                                    <th class="py-3 px-4 text-center">Status</th>
                                    <th class="py-3 px-4 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200/70 text-xs">
                                <?php foreach ($categories as $cat): ?>
                                    <tr class="hover:bg-purple-50/30 transition-colors">
                                        <td class="py-3 px-4 font-mono text-zinc-400"><?= (int)$cat['sort_order'] ?></td>
                                        <td class="py-3 px-4 font-bold text-zinc-900"><?= e($cat['name']) ?></td>
                                        <td class="py-3 px-4 text-center font-mono text-zinc-600"><?= (int)$cat['service_count'] ?></td>
                                        <td class="py-3 px-4 text-center"><?= status_badge($cat['status']) ?></td>
                                        <td class="py-3 px-4 text-right">
                                            <form action="/admin/categories.php" method="POST" class="inline-block" onsubmit="return confirm('Delete this category?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
                                                <button type="submit" class="p-1 text-rose-600 hover:bg-rose-50 rounded-lg transition-colors inline-flex items-center" title="Delete">
                                                    <?= icon('trash', 'w-3.5 h-3.5') ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="py-8 text-center text-xs text-zinc-500">
                        No categories found. Use the form on the left to add one.
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
