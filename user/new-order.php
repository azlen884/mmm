<?php
/**
 * ApexSMM User - New Order Page
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/Services/ServiceManager.php';
require_once __DIR__ . '/../app/Orders/OrderManager.php';

Auth::requireLogin();
$user = Auth::user();

$categories = ServiceManager::getActiveCategories();
$allServices = ServiceManager::getServices(null, '', true);

// Group services by category for reactive Alpine.js dropdown
$servicesByCategory = [];
foreach ($allServices as $s) {
    $servicesByCategory[$s['category_id']][] = [
        'id'          => (int)$s['id'],
        'name'        => $s['name'],
        'rate'        => (float)$s['rate'],
        'min'         => (int)$s['min_quantity'],
        'max'         => (int)$s['max_quantity'],
        'refill'      => (bool)$s['refill'],
        'cancel'      => (bool)$s['cancel'],
        'dripfeed'    => (bool)$s['dripfeed'],
        'description' => $s['description'] ?? '',
    ];
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $serviceId = (int)($_POST['service_id'] ?? 0);
    $link      = trim($_POST['link'] ?? '');
    $quantity  = (int)($_POST['quantity'] ?? 0);

    try {
        $orderResult = OrderManager::createOrder($user['id'], $serviceId, $link, $quantity);
        flash_set('success', $orderResult['message']);
        header('Location: /user/orders.php');
        exit;
    } catch (\Throwable $e) {
        $error = $e->getMessage();
    }
}

$activeNav = 'new_order';
$pageTitle = 'Place New Order | ' . get_setting('site_name', 'ApexSMM');
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto py-4 sm:py-6" 
     x-data="{
         categories: <?= htmlspecialchars(json_encode($categories), ENT_QUOTES, 'UTF-8') ?>,
         servicesMap: <?= htmlspecialchars(json_encode($servicesByCategory), ENT_QUOTES, 'UTF-8') ?>,
         selectedCategory: '<?= !empty($categories) ? $categories[0]['id'] : '' ?>',
         selectedServiceId: '',
         quantity: '',
         link: '',

         get currentServices() {
             return this.servicesMap[this.selectedCategory] || [];
         },

         get activeService() {
             if (!this.selectedServiceId) return null;
             return this.currentServices.find(s => s.id == this.selectedServiceId) || null;
         },

         get calculatedCharge() {
             if (!this.activeService || !this.quantity || this.quantity <= 0) return '0.0000';
             const rate = this.activeService.rate;
             const total = (rate / 1000) * this.quantity;
             return total.toFixed(4);
         },

         init() {
             if (this.currentServices.length > 0) {
                 this.selectedServiceId = this.currentServices[0].id;
             }
             this.$watch('selectedCategory', () => {
                 const svcs = this.currentServices;
                 this.selectedServiceId = svcs.length > 0 ? svcs[0].id : '';
             });
         }
     }">

    <div class="mb-8">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Place New Order</h1>
        <p class="text-sm text-slate-400 mt-1">Select a verified service package, provide target URL, and submit for automated dispatch.</p>
    </div>

    <?php if ($error): ?>
        <div class="mb-6 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm flex items-center space-x-3 shadow-lg">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Order Form Column -->
        <div class="lg:col-span-2">
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 sm:p-8 backdrop-blur-xl shadow-xl">
                
                <?php if (empty($categories) || empty($allServices)): ?>
                    <div class="py-12 text-center">
                        <div class="w-12 h-12 rounded-xl bg-slate-800 flex items-center justify-center mx-auto mb-3 text-slate-400">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                        </div>
                        <h4 class="text-base font-bold text-white mb-1">No services available</h4>
                        <p class="text-xs text-slate-400">The service catalog has no active items. Please check back later.</p>
                    </div>
                <?php else: ?>
                    <form action="/user/new-order.php" method="POST" class="space-y-6">
                        <?= csrf_field() ?>

                        <!-- Category Select -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Category</label>
                            <select x-model="selectedCategory" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-blue-500 transition-colors">
                                <template x-for="cat in categories" :key="cat.id">
                                    <option :value="cat.id" x-text="cat.name"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Service Select -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Service</label>
                            <select name="service_id" x-model="selectedServiceId" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-blue-500 transition-colors">
                                <template x-for="s in currentServices" :key="s.id">
                                    <option :value="s.id" x-text="s.name + ' - $' + s.rate.toFixed(4) + ' / 1k'"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Target Link -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Target Link / URL</label>
                            <input type="text" name="link" x-model="link" required placeholder="https://instagram.com/username or video URL"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 transition-colors">
                        </div>

                        <!-- Quantity -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400">Quantity</label>
                                <span class="text-xs text-slate-500" x-show="activeService">
                                    Min: <span class="font-mono text-slate-300" x-text="activeService ? activeService.min : 0"></span> | 
                                    Max: <span class="font-mono text-slate-300" x-text="activeService ? activeService.max : 0"></span>
                                </span>
                            </div>
                            <input type="number" name="quantity" x-model.number="quantity" required
                                :min="activeService ? activeService.min : 1"
                                :max="activeService ? activeService.max : 1000000"
                                placeholder="Enter order quantity..."
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 transition-colors font-mono">
                        </div>

                        <!-- Total Charge Display -->
                        <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800/80 flex items-center justify-between">
                            <div>
                                <div class="text-xs text-slate-400">Total Charge:</div>
                                <div class="text-2xl font-black text-emerald-400 font-mono">
                                    $ <span x-text="calculatedCharge">0.0000</span>
                                </div>
                            </div>
                            <div class="text-right text-xs text-slate-400">
                                <div>Your Balance:</div>
                                <div class="font-mono font-bold text-white"><?= format_currency($user['balance']) ?></div>
                            </div>
                        </div>

                        <button type="submit" class="w-full py-4 rounded-xl bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 text-white font-bold text-base shadow-xl shadow-blue-500/25 hover:shadow-blue-500/40 hover:scale-[1.01] transition-all">
                            Submit Order &rarr;
                        </button>
                    </form>
                <?php endif; ?>

            </div>
        </div>

        <!-- Service Information Card -->
        <div class="lg:col-span-1">
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 backdrop-blur-xl space-y-6">
                <h3 class="text-sm font-bold text-slate-200 uppercase tracking-wider">Service Specifications</h3>
                
                <template x-if="activeService">
                    <div class="space-y-4">
                        <div>
                            <div class="text-xs text-slate-500 mb-1">Service Title</div>
                            <div class="text-sm font-semibold text-white" x-text="activeService.name"></div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
                                <div class="text-[11px] text-slate-500">Rate / 1k</div>
                                <div class="text-sm font-mono font-bold text-emerald-400" x-text="'$' + activeService.rate.toFixed(4)"></div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
                                <div class="text-[11px] text-slate-500">Min / Max</div>
                                <div class="text-sm font-mono font-bold text-slate-300" x-text="activeService.min + ' / ' + activeService.max"></div>
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-slate-500 mb-2">Enabled Features</div>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-2.5 py-1 text-xs rounded-lg font-medium"
                                      :class="activeService.refill ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-800 text-slate-500'">
                                    Refill Guarantee
                                </span>
                                <span class="px-2.5 py-1 text-xs rounded-lg font-medium"
                                      :class="activeService.cancel ? 'bg-blue-500/10 text-blue-400 border border-blue-500/20' : 'bg-slate-800 text-slate-500'">
                                    Cancel Allowed
                                </span>
                                <span class="px-2.5 py-1 text-xs rounded-lg font-medium"
                                      :class="activeService.dripfeed ? 'bg-purple-500/10 text-purple-400 border border-purple-500/20' : 'bg-slate-800 text-slate-500'">
                                    Drip-feed
                                </span>
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-slate-500 mb-1">Description & Guidelines</div>
                            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 text-xs text-slate-300 leading-relaxed max-h-48 overflow-y-auto"
                                 x-text="activeService.description || 'No special requirements noted for this service.'">
                            </div>
                        </div>
                    </div>
                </template>

                <template x-if="!activeService">
                    <p class="text-xs text-slate-500">Select a category and service to preview parameters.</p>
                </template>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
