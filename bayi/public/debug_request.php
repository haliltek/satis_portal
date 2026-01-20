<?php
/**
 * Request Debug - Gerçek HTTP Request Bilgileri
 * http://localhost/b2b-gemas-project-main/bayi/public/debug_request.php
 */

echo "<h2>🔍 Request Debug Bilgileri</h2>";
echo "<hr>";

echo "<h3>1. SERVER Değişkenleri:</h3>";
echo "<pre>";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'YOK') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'YOK') . "\n";
echo "PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'YOK') . "\n";
echo "QUERY_STRING: " . ($_SERVER['QUERY_STRING'] ?? 'YOK') . "\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'YOK') . "\n";
echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'YOK') . "\n";
echo "</pre>";

echo "<h3>2. Laravel Request Test:</h3>";
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::capture();
echo "<pre>";
echo "Request URI: " . $request->getRequestUri() . "\n";
echo "Path Info: " . $request->getPathInfo() . "\n";
echo "Base Path: " . $request->getBasePath() . "\n";
echo "Root: " . $request->root() . "\n";
echo "</pre>";

echo "<h3>3. Route Test:</h3>";
try {
    $response = $app->handle($request);
    echo "Status Code: " . $response->getStatusCode() . "<br>";
    
    if ($response->getStatusCode() == 302) {
        $location = $response->headers->get('Location');
        echo "Redirect Location: " . htmlspecialchars($location) . "<br>";
    }
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "<br>";
}

