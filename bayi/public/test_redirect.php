<?php
/**
 * Redirect Test - Sonsuz Döngü Kontrolü
 * http://localhost/b2b-gemas-project-main/bayi/public/test_redirect.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "<h2>🔍 Redirect Test - Sonsuz Döngü Kontrolü</h2>";
echo "<hr>";

// Root route testi
echo "<h3>1. Root Route (/):</h3>";
try {
    $request = Illuminate\Http\Request::create('/', 'GET');
    $response = $app->handle($request);
    
    echo "Status Code: " . $response->getStatusCode() . "<br>";
    $location = $response->headers->get('Location');
    
    if ($response->getStatusCode() == 302 && $location) {
        echo "✅ Redirect yapılıyor: " . htmlspecialchars($location) . "<br>";
        
        if (strpos($location, '/login') !== false) {
            echo "✅ Login sayfasına yönlendiriyor (doğru!)<br>";
        } elseif (strpos($location, '/home') !== false) {
            echo "✅ Home sayfasına yönlendiriyor (giriş yapmış kullanıcı için doğru!)<br>";
        } else {
            echo "⚠️ Beklenmeyen yere yönlendiriyor!<br>";
        }
    } else {
        echo "⚠️ Redirect yapılmıyor veya yanlış status code<br>";
    }
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "<br>";
}

// Login route testi
echo "<h3>2. Login Route (/login):</h3>";
try {
    $request = Illuminate\Http\Request::create('/login', 'GET');
    $response = $app->handle($request);
    
    echo "Status Code: " . $response->getStatusCode() . "<br>";
    
    if ($response->getStatusCode() == 200) {
        echo "✅ Login sayfası gösteriliyor (doğru!)<br>";
    } elseif ($response->getStatusCode() == 302) {
        $location = $response->headers->get('Location');
        echo "⚠️ Login sayfası redirect yapıyor: " . htmlspecialchars($location) . "<br>";
        echo "❌ Bu sonsuz döngüye sebep olabilir!<br>";
    } else {
        echo "❌ Beklenmeyen status code<br>";
    }
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "<br>";
}

// HOME constant kontrolü
echo "<h3>3. HOME Constant:</h3>";
echo "HOME: " . \App\Providers\RouteServiceProvider::HOME . "<br>";
echo "ANASAYFA: " . \App\Providers\RouteServiceProvider::ANASAYFA . "<br>";

echo "<hr>";
echo "<h3>✅ Test Tamamlandı!</h3>";
echo "<p><strong>Login URL:</strong> <a href='/b2b-gemas-project-main/bayi/public/login' target='_blank'>http://localhost/b2b-gemas-project-main/bayi/public/login</a></p>";
echo "<p><strong>Root URL:</strong> <a href='/b2b-gemas-project-main/bayi/public/' target='_blank'>http://localhost/b2b-gemas-project-main/bayi/public/</a></p>";

