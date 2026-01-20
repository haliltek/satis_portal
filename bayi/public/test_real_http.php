<?php
/**
 * Gerçek HTTP İsteği Simülasyonu
 * http://localhost/b2b-gemas-project-main/bayi/public/test_real_http.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Gerçek HTTP header'larını simüle et
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/b2b-gemas-project-main/bayi/public/';
$_SERVER['SCRIPT_NAME'] = '/b2b-gemas-project-main/bayi/public/index.php';
$_SERVER['PHP_SELF'] = '/b2b-gemas-project-main/bayi/public/index.php';
$_SERVER['QUERY_STRING'] = '';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['SERVER_PORT'] = '80';
$_SERVER['HTTPS'] = '';

echo "<h2>🌐 Gerçek HTTP İsteği Testi</h2>";
echo "<hr>";

echo "<h3>1. Index.php Çalıştırma:</h3>";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "<br>";

// Laravel'i başlat
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Gerçek request oluştur
$request = Illuminate\Http\Request::create('/b2b-gemas-project-main/bayi/public/', 'GET');
echo "Request URI: " . $request->getRequestUri() . "<br>";
echo "Path Info: " . $request->getPathInfo() . "<br>";

try {
    $response = $app->handle($request);
    
    echo "<h3>2. Response:</h3>";
    echo "Status Code: " . $response->getStatusCode() . "<br>";
    
    if ($response->getStatusCode() == 302) {
        $location = $response->headers->get('Location');
        echo "⚠️ Redirect yapılıyor: " . htmlspecialchars($location) . "<br>";
        
        if (strpos($location, '/panel') !== false) {
            echo "❌ PANEL'E YÖNLENDİRİLİYOR! Bu sorunun kaynağı!<br>";
        }
    } elseif ($response->getStatusCode() == 200) {
        echo "✅ Sayfa gösteriliyor (doğru!)<br>";
        $content = $response->getContent();
        echo "Content Length: " . strlen($content) . " bytes<br>";
    }
    
    // Header'ları kontrol et
    echo "<h3>3. Response Headers:</h3>";
    foreach ($response->headers->all() as $key => $values) {
        echo "$key: " . implode(', ', $values) . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "<br>";
    echo "Dosya: " . $e->getFile() . "<br>";
    echo "Satır: " . $e->getLine() . "<br>";
}

echo "<hr>";
echo "<h3>4. Route Kontrolü:</h3>";
$routes = Route::getRoutes();
foreach ($routes as $route) {
    $uri = $route->uri();
    if ($uri === '' || $uri === '/') {
        echo "Root route: " . $route->getActionName() . "<br>";
    }
}

