            </main>

            <!-- Admin Footer -->
            <footer class="p-6 border-t border-slate-800/80 text-xs text-slate-500 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>&copy; <?= date('Y') ?> <?= e(get_setting('site_name', 'ApexSMM')) ?> Staff Console. Strictly Authenticated.</div>
                <div class="flex items-center space-x-4">
                    <span class="font-mono text-[11px] text-slate-600">PHP 8.2 &bull; MariaDB &bull; Tailwind 4</span>
                </div>
            </footer>
        </div>
    </div>
</body>
</html>
