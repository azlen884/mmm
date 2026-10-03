#!/bin/bash
# Start MariaDB service if not active
service mariadb status >/dev/null 2>&1 || service mariadb start 2>/dev/null || true

# Start PHP built-in web server with root router on port 3000
echo "Starting ApexSMM PHP Web Server on 0.0.0.0:3000..."
exec php -S 0.0.0.0:3000 router.php
