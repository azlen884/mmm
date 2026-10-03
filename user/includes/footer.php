            </main>

            <!-- Dashboard Footer -->
            <footer class="p-6 border-t border-slate-800/80 text-xs text-slate-500 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>&copy; <?= date('Y') ?> <?= e(get_setting('site_name', 'ApexSMM')) ?>. Encrypted Session.</div>
                <div class="flex items-center space-x-4">
                    <a href="/user/api.php" class="hover:text-slate-400 transition-colors">API Documentation</a>
                    <a href="/user/tickets.php" class="hover:text-slate-400 transition-colors">Contact Support</a>
                </div>
            </footer>
        </div>
    </div>
</body>
</html>
