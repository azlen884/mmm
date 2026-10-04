            </main>

            <!-- Dashboard Footer -->
            <footer class="p-6 border-t border-purple-100/80 bg-white/60 text-xs text-zinc-500 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>&copy; <?= date('Y') ?> <?= e(get_setting('site_name', 'ApexSMM')) ?>. All rights reserved.</div>
                <div class="flex items-center space-x-4">
                    <a href="/user/api.php" class="hover:text-purple-600 transition-colors">API Documentation</a>
                    <a href="/user/tickets.php" class="hover:text-purple-600 transition-colors">Support Desk</a>
                    <a href="/services.php" class="hover:text-purple-600 transition-colors">Services</a>
                </div>
            </footer>
        </div>
    </div>
</body>
</html>
