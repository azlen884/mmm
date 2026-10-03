<?php
require_once __DIR__ . '/error_template.php';
render_error_page(405, 'Method Not Allowed', 'The HTTP verb used for this request is not allowed for this route.');
