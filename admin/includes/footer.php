            </main>

            <!-- Admin Footer -->
            <footer class="p-6 border-t border-purple-100/80 bg-white/60 text-xs text-zinc-500 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>&copy; <?= date('Y') ?> <?= e(get_setting('site_name', 'ApexSMM')) ?>. Administrative Console.</div>
                <div class="flex items-center space-x-4">
                    <span class="text-zinc-400">Engine: PHP <?= PHP_VERSION ?></span>
                    <a href="/admin/logs.php" class="hover:text-purple-600 transition-colors">Logs</a>
                    <a href="/admin/settings.php" class="hover:text-purple-600 transition-colors">Settings</a>
                </div>
            </footer>
        </div>
    </div>
</body>
</html>
