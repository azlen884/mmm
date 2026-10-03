<?php
require_once __DIR__ . '/error_template.php';
render_error_page(400, 'Bad Request', 'The request could not be understood by the server due to malformed syntax.');
