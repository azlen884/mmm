<?php
/**
 * ApexSMM User - New Order Page
 * White + Premium Purple Design System
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

<div class="max-w-4xl mx-auto py-2 sm:py-4" 
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

    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-zinc-900 tracking-tight">Place New Order</h1>
        <p class="text-xs sm:text-sm text-zinc-500 mt-1">Select a verified service package, provide target URL, and submit for automated dispatch.</p>
    </div>

    <?php if ($error): ?>
        <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center space-x-3 shadow-xs">
            <div class="shrink-0 text-rose-600">
                <?= icon('exclamation-circle', 'w-5 h-5') ?>
            </div>
            <span class="font-medium text-xs sm:text-sm"><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Order Form Column -->
        <div class="lg:col-span-2">
            <div class="bg-white border border-purple-100 rounded-3xl p-6 sm:p-8 shadow-xs">
                
                <?php if (empty($categories) || empty($allServices)): ?>
                    <div class="py-12 text-center">
                        <div class="w-12 h-12 rounded-xl bg-purple-50 flex items-center justify-center mx-auto mb-3 text-purple-600">
                            <?= icon('inbox', 'w-6 h-6') ?>
                        </div>
                        <h4 class="text-base font-bold text-zinc-900 mb-1">No services available</h4>
                        <p class="text-xs text-zinc-500">The service catalog has no active items. Please check back later.</p>
                    </div>
                <?php else: ?>
                    <form action="/user/new-order.php" method="POST" class="space-y-5">
                        <?= csrf_field() ?>

                        <!-- Category Select -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-2">Category</label>
                            <select x-model="selectedCategory" class="w-full bg-white border border-zinc-200 rounded-xl px-4 py-2.5 text-sm text-zinc-900 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600 transition-colors">
                                <template x-for="cat in categories" :key="cat.id">
                                    <option :value="cat.id" x-text="cat.name"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Service Select -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-2">Service</label>
                            <select name="service_id" x-model="selectedServiceId" required class="w-full bg-white border border-zinc-200 rounded-xl px-4 py-2.5 text-sm text-zinc-900 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600 transition-colors">
                                <template x-for="s in currentServices" :key="s.id">
                                    <option :value="s.id" x-text="s.name + ' - $' + s.rate.toFixed(4) + ' / 1k'"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Target Link -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-2">Target Link / URL</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-zinc-400">
                                    <?= icon('link', 'w-4 h-4') ?>
                                </div>
                                <input type="text" name="link" x-model="link" required placeholder="https://instagram.com/username or video link"
                                    class="w-full bg-white border border-zinc-200 rounded-xl pl-10 pr-4 py-2.5 text-sm text-zinc-900 placeholder-zinc-400 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600 transition-colors">
                            </div>
                        </div>

                        <!-- Quantity -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-zinc-500">Quantity</label>
                                <span class="text-xs text-zinc-500" x-show="activeService">
                                    Min: <span class="font-mono text-zinc-700 font-semibold" x-text="activeService ? activeService.min : 0"></span> | 
                                    Max: <span class="font-mono text-zinc-700 font-semibold" x-text="activeService ? activeService.max : 0"></span>
                                </span>
                            </div>
                            <input type="number" name="quantity" x-model.number="quantity" required
                                :min="activeService ? activeService.min : 1"
                                :max="activeService ? activeService.max : 1000000"
                                placeholder="Enter order quantity..."
                                class="w-full bg-white border border-zinc-200 rounded-xl px-4 py-2.5 text-sm text-zinc-900 placeholder-zinc-400 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600 transition-colors font-mono">
                        </div>

                        <!-- Total Charge Display -->
                        <div class="p-4 rounded-2xl bg-purple-50/70 border border-purple-100 flex items-center justify-between">
                            <div>
                                <div class="text-xs text-zinc-500 font-medium">Estimated Charge:</div>
                                <div class="text-2xl font-black text-purple-700 font-mono">
                                    $ <span x-text="calculatedCharge">0.0000</span>
                                </div>
                            </div>
                            <div class="text-right text-xs text-zinc-500">
                                <div>Your Balance:</div>
                                <div class="font-mono font-bold text-zinc-900"><?= format_currency((float)$user['balance']) ?></div>
                            </div>
                        </div>

                        <button type="submit" class="w-full py-3.5 rounded-xl bg-purple-600 text-white font-bold text-sm shadow-xs shadow-purple-600/25 hover:bg-purple-700 transition-all flex items-center justify-center space-x-2 cursor-pointer">
                            <?= icon('plus', 'w-4 h-4') ?>
                            <span>Submit Order</span>
                        </button>
                    </form>
                <?php endif; ?>

            </div>
        </div>

        <!-- Service Information Card -->
        <div class="lg:col-span-1">
            <div class="bg-white border border-purple-100 rounded-3xl p-6 shadow-xs space-y-5">
                <h3 class="text-xs font-bold text-zinc-500 uppercase tracking-wider flex items-center space-x-2">
                    <?= icon('information-circle', 'w-4 h-4 text-purple-600') ?>
                    <span>Service Specifications</span>
                </h3>
                
                <template x-if="activeService">
                    <div class="space-y-4">
                        <div>
                            <div class="text-[11px] text-zinc-400 uppercase tracking-wider font-semibold mb-1">Service Title</div>
                            <div class="text-xs font-semibold text-zinc-900 leading-snug" x-text="activeService.name"></div>
                        </div>

                        <div class="grid grid-cols-2 gap-2.5">
                            <div class="p-3 rounded-xl bg-zinc-50 border border-zinc-100">
                                <div class="text-[10px] text-zinc-400 uppercase font-semibold">Rate / 1k</div>
                                <div class="text-xs font-mono font-bold text-purple-700" x-text="'$' + activeService.rate.toFixed(4)"></div>
                            </div>
                            <div class="p-3 rounded-xl bg-zinc-50 border border-zinc-100">
                                <div class="text-[10px] text-zinc-400 uppercase font-semibold">Min / Max</div>
                                <div class="text-xs font-mono font-bold text-zinc-700" x-text="activeService.min + ' / ' + activeService.max"></div>
                            </div>
                        </div>

                        <div>
                            <div class="text-[11px] text-zinc-400 uppercase tracking-wider font-semibold mb-2">Features</div>
                            <div class="flex flex-wrap gap-1.5">
                                <span class="px-2 py-0.5 text-[11px] rounded-lg font-semibold"
                                      :class="activeService.refill ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-zinc-100 text-zinc-400 border border-zinc-200'">
                                    Refill Guarantee
                                </span>
                                <span class="px-2 py-0.5 text-[11px] rounded-lg font-semibold"
                                      :class="activeService.cancel ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-zinc-100 text-zinc-400 border border-zinc-200'">
                                    Cancel Allowed
                                </span>
                                <span class="px-2 py-0.5 text-[11px] rounded-lg font-semibold"
                                      :class="activeService.dripfeed ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-zinc-100 text-zinc-400 border border-zinc-200'">
                                    Drip-feed
                                </span>
                            </div>
                        </div>

                        <div>
                            <div class="text-[11px] text-zinc-400 uppercase tracking-wider font-semibold mb-1">Description & Guidelines</div>
                            <div class="p-3 rounded-xl bg-zinc-50 border border-zinc-100 text-xs text-zinc-600 leading-relaxed max-h-48 overflow-y-auto"
                                 x-text="activeService.description || 'No special requirements noted for this service.'">
                            </div>
                        </div>
                    </div>
                </template>

                <template x-if="!activeService">
                    <p class="text-xs text-zinc-400">Select a category and service to preview parameters.</p>
                </template>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
