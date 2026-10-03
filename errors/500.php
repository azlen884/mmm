<?php
require_once __DIR__ . '/error_template.php';
$msg = $errorMessage ?? 'We encountered an internal issue and could not complete your request. Please try again shortly.';
$det = $errorFile ?? null;
render_error_page(500, 'Something went wrong', $msg, $det);
