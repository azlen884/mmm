<?php
require_once __DIR__ . '/error_template.php';
render_error_page(429, 'Too Many Requests', 'Rate limit exceeded. You have made too many requests in a short period. Please wait.');
