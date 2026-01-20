<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @package  Laravel
 * @author   Taylor Otwell <taylor@laravel.com>
 */

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| our application. We just need to utilize it! We'll simply require it
| into the script here so that we don't have to worry about manual
| loading any of our classes later on. It feels great to relax.
|
*/

require __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Turn On The Lights
|--------------------------------------------------------------------------
|
| We need to illuminate PHP development, so let us turn on the lights.
| This bootstraps the framework and gets it ready for use, then it
| will load up this application so that we can run it and send
| the responses back to the browser and delight our users.
|
*/

// Subdirectory desteği için APP_URL'i ayarla
$basePath = '/b2b-gemas-project-main/bayi/public';
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$appUrl = $protocol . '://' . $host . $basePath;

// APP_URL environment variable'ını set et (eğer .env'de yoksa)
if (!getenv('APP_URL')) {
    putenv("APP_URL={$appUrl}");
    $_ENV['APP_URL'] = $appUrl;
    $_SERVER['APP_URL'] = $appUrl;
}

// ASSET_URL environment variable'ını set et (asset() helper için)
if (!getenv('ASSET_URL')) {
    putenv("ASSET_URL={$appUrl}");
    $_ENV['ASSET_URL'] = $appUrl;
    $_SERVER['ASSET_URL'] = $appUrl;
}

$app = require_once __DIR__.'/../bootstrap/app.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request
| through the kernel, and send the associated response back to
| the client's browser allowing them to enjoy the creative
| and wonderful application we have prepared for them.
|
*/

// Request URI'yi düzelt - subdirectory desteği için
$basePath = '/b2b-gemas-project-main/bayi/public';
if (isset($_SERVER['REQUEST_URI'])) {
    $requestUri = $_SERVER['REQUEST_URI'];
    
    // Eğer REQUEST_URI basePath ile başlıyorsa, onu kaldır
    if (strpos($requestUri, $basePath) === 0) {
        $pathInfo = substr($requestUri, strlen($basePath));
        if (empty($pathInfo) || $pathInfo === '/') {
            $pathInfo = '/';
        }
        // Query string'i koru
        $queryString = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
        $_SERVER['REQUEST_URI'] = $pathInfo . $queryString;
    }
    
    // SCRIPT_NAME'i ayarla
    $_SERVER['SCRIPT_NAME'] = $basePath . '/index.php';
    
    // PHP_SELF'i de düzelt
    if (isset($_SERVER['PHP_SELF']) && strpos($_SERVER['PHP_SELF'], $basePath) === 0) {
        $_SERVER['PHP_SELF'] = $basePath . '/index.php';
    }
}

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

$kernel->terminate($request, $response);
