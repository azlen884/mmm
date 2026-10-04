    </main>

    <!-- Public Footer -->
    <footer class="bg-white border-t border-purple-100 mt-20 pt-16 pb-12 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-10 mb-12">
                <!-- Brand Info -->
                <div class="md:col-span-1 space-y-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 rounded-xl bg-purple-600 flex items-center justify-center font-extrabold text-base text-white shadow-sm shadow-purple-500/25">
                            A
                        </div>
                        <span class="text-lg font-extrabold tracking-tight text-zinc-900"><?= e(get_setting('site_name', 'ApexSMM')) ?><span class="text-purple-600">.</span></span>
                    </div>
                    <p class="text-xs text-zinc-500 leading-relaxed">
                        <?= e(get_setting('site_tagline', 'The high-speed automated social media marketing platform for agencies and content creators.')) ?>
                    </p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-xs font-bold text-zinc-900 uppercase tracking-wider mb-4">Quick Links</h4>
                    <ul class="space-y-2 text-xs text-zinc-600">
                        <li><a href="/" class="hover:text-purple-600 transition-colors">Home</a></li>
                        <li><a href="/services.php" class="hover:text-purple-600 transition-colors">Services List</a></li>
                        <li><a href="/how-it-works.php" class="hover:text-purple-600 transition-colors">How It Works</a></li>
                        <li><a href="/faq.php" class="hover:text-purple-600 transition-colors">Frequently Asked Questions</a></li>
                        <li><a href="/contact.php" class="hover:text-purple-600 transition-colors">Support & Contact</a></li>
                    </ul>
                </div>

                <!-- Account & Portals -->
                <div>
                    <h4 class="text-xs font-bold text-zinc-900 uppercase tracking-wider mb-4">Portals</h4>
                    <ul class="space-y-2 text-xs text-zinc-600">
                        <li><a href="/login.php" class="hover:text-purple-600 transition-colors">Client Sign In</a></li>
                        <li><a href="/register.php" class="hover:text-purple-600 transition-colors">Create Account</a></li>
                        <li><a href="/admin/login.php" class="hover:text-purple-600 transition-colors">Staff / Admin Portal</a></li>
                        <li><a href="/user/api.php" class="hover:text-purple-600 transition-colors">Developer API Docs</a></li>
                    </ul>
                </div>

                <!-- Platform Security -->
                <div class="space-y-3">
                    <h4 class="text-xs font-bold text-zinc-900 uppercase tracking-wider mb-4">Security & Trust</h4>
                    <p class="text-xs text-zinc-500 leading-relaxed">
                        Automated provider failover, encrypted credential handling, and transactional balance integrity.
                    </p>
                    <div class="flex items-center space-x-2 text-xs text-emerald-600 pt-1">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="font-medium">All Systems Operational</span>
                    </div>
                </div>
            </div>

            <div class="border-t border-purple-100 pt-8 flex flex-col sm:flex-row items-center justify-between text-xs text-zinc-400">
                <p>&copy; <?= date('Y') ?> <?= e(get_setting('site_name', 'ApexSMM')) ?>. All rights reserved.</p>
                <div class="flex items-center space-x-6 mt-4 sm:mt-0">
                    <a href="/faq.php" class="hover:text-purple-600 transition-colors">Privacy Policy</a>
                    <a href="/faq.php" class="hover:text-purple-600 transition-colors">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
