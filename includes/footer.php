    </main>

    <!-- Public Footer -->
    <footer class="bg-slate-950/80 border-t border-slate-800/80 mt-20 pt-16 pb-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-10 mb-12">
                <!-- Brand Info -->
                <div class="md:col-span-1 space-y-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center font-bold text-lg text-white shadow-md shadow-blue-500/30">
                            A
                        </div>
                        <span class="text-xl font-bold tracking-tight text-white"><?= e(get_setting('site_name', 'ApexSMM')) ?></span>
                    </div>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        <?= e(get_setting('site_tagline', 'The high-speed automated social media marketing platform for agencies and content creators.')) ?>
                    </p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-xs font-semibold text-slate-300 uppercase tracking-wider mb-4">Quick Links</h4>
                    <ul class="space-y-2 text-sm text-slate-400">
                        <li><a href="/" class="hover:text-white transition-colors">Home</a></li>
                        <li><a href="/services.php" class="hover:text-white transition-colors">Services List</a></li>
                        <li><a href="/how-it-works.php" class="hover:text-white transition-colors">How It Works</a></li>
                        <li><a href="/faq.php" class="hover:text-white transition-colors">Frequently Asked Questions</a></li>
                        <li><a href="/contact.php" class="hover:text-white transition-colors">Support & Contact</a></li>
                    </ul>
                </div>

                <!-- Account & Portals -->
                <div>
                    <h4 class="text-xs font-semibold text-slate-300 uppercase tracking-wider mb-4">Portals</h4>
                    <ul class="space-y-2 text-sm text-slate-400">
                        <li><a href="/login.php" class="hover:text-white transition-colors">Client Sign In</a></li>
                        <li><a href="/register.php" class="hover:text-white transition-colors">Create Account</a></li>
                        <li><a href="/admin/login.php" class="hover:text-white transition-colors">Staff / Admin Portal</a></li>
                        <li><a href="/user/api.php" class="hover:text-white transition-colors">Developer API Docs</a></li>
                    </ul>
                </div>

                <!-- Platform Security -->
                <div class="space-y-3">
                    <h4 class="text-xs font-semibold text-slate-300 uppercase tracking-wider mb-4">Security & Trust</h4>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Automated provider failover, encrypted credential handling, and transactional balance integrity.
                    </p>
                    <div class="flex items-center space-x-2 text-xs text-emerald-400 pt-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>All Systems Operational</span>
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-800/80 pt-8 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500">
                <p>&copy; <?= date('Y') ?> <?= e(get_setting('site_name', 'ApexSMM')) ?>. All rights reserved.</p>
                <div class="flex items-center space-x-6 mt-4 sm:mt-0">
                    <a href="/faq.php" class="hover:text-slate-400 transition-colors">Privacy Policy</a>
                    <a href="/faq.php" class="hover:text-slate-400 transition-colors">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
