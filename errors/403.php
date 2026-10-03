<?php
require_once __DIR__ . '/error_template.php';
render_error_page(403, 'Access Forbidden', 'You do not have administrative permission to view this resource.');
