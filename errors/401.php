<?php
require_once __DIR__ . '/error_template.php';
render_error_page(401, 'Unauthorized', 'You must authenticate before accessing this protected resource.');
