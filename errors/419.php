<?php
require_once __DIR__ . '/error_template.php';
render_error_page(419, 'Page Expired', 'The security token has expired or is invalid. Please refresh the page and try again.');
