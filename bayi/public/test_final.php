<?php
/**
 * Final Test - Login Sayfası ve Route Kontrolü
 * http://localhost/b2b-gemas-project-main/bayi/public/test_final.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "<h2>🔍 Final Test - Login Sayfası</h2>";
echo "<hr>";

// 1. Route kontrolü
echo "<h3>1. Route Kontrolü:</h3>";
$routes = Route::getRoutes();
$loginRoute = $routes->getByName('login');
if ($loginRoute) {
    echo "✅ Login route: " . $loginRoute->uri() . "<br>";
} else {
    echo "❌ Login route bulunamadı!<br>";
}

// Panel route kontrolü
$panelRoutes = [];
foreach ($routes as $route) {
    $uri = $route->uri();
    if (strpos($uri, 'panel') === 0) {
        $panelRoutes[] = $uri;
    }
}

if (empty($panelRoutes)) {
    echo "✅ Panel route'ları bulunamadı (iyi!)<br>";
} else {
    echo "❌ Panel route'ları hala aktif!<br>";
    foreach (array_slice($panelRoutes, 0, 5) as $route) {
        echo "  - $route<br>";
    }
}

// 2. Login sayfası testi
echo "<h3>2. Login Sayfası Testi:</h3>";
try {
    $request = Illuminate\Http\Request::create('/login', 'GET');
    $response = $app->handle($request);
    
    echo "Status Code: " . $response->getStatusCode() . "<br>";
    
    if ($response->getStatusCode() == 200) {
        echo "✅ Login sayfası başarıyla yüklendi!<br>";
        $content = $response->getContent();
        echo "Content Length: " . strlen($content) . " bytes<br>";
        
        if (strpos($content, 'Giriş Yap') !== false || strpos($content, 'email') !== false) {
            echo "✅ Login formu bulundu!<br>";
        }
    } else {
        echo "❌ Hata! Status: " . $response->getStatusCode() . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "<br>";
}

// 3. Root route testi
echo "<h3>3. Root Route Testi:</h3>";
try {
    $request = Illuminate\Http\Request::create('/', 'GET');
    $response = $app->handle($request);
    
    echo "Status Code: " . $response->getStatusCode() . "<br>";
    $location = $response->headers->get('Location');
    
    if ($response->getStatusCode() == 302 && $location) {
        echo "✅ Root route redirect yapıyor: " . htmlspecialchars($location) . "<br>";
        if (strpos($location, '/login') !== false) {
            echo "✅ Login sayfasına yönlendiriyor (doğru!)<br>";
        } else {
            echo "❌ Yanlış yere yönlendiriyor!<br>";
        }
    } else {
        echo "⚠️ Redirect yapılmıyor veya yanlış status code<br>";
    }
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>✅ Test Tamamlandı!</h3>";
echo "<p><strong>Login URL:</strong> <a href='/b2b-gemas-project-main/bayi/public/login' target='_blank'>http://localhost/b2b-gemas-project-main/bayi/public/login</a></p>";
echo "<p><strong>Root URL:</strong> <a href='/b2b-gemas-project-main/bayi/public/' target='_blank'>http://localhost/b2b-gemas-project-main/bayi/public/</a></p>";

