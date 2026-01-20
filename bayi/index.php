<?php

/**
 * Laravel Bootstrap - Redirect to public folder
 * This file allows access to Laravel application without /public/ in URL
 */

// Change to public directory
$publicPath = __DIR__ . '/public';

// Check if public folder exists
if (!is_dir($publicPath)) {
    http_response_code(500);
    die('Public folder not found!');
}

// Change working directory to public
chdir($publicPath);

// Include the Laravel bootstrap file
require $publicPath . '/index.php';

