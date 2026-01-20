<?php
/**
 * Direkt Test - Login Sayfası
 * http://localhost/b2b-gemas-project-main/bayi/public/test_direct.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Direkt Login Test</h2>";
echo "<hr>";

try {
    require __DIR__.'/../vendor/autoload.php';
    echo "✅ Autoload OK<br>";
    
    $app = require_once __DIR__.'/../bootstrap/app.php';
    echo "✅ App bootstrap OK<br>";
    
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    echo "✅ Kernel bootstrap OK<br>";
    
    // Login route'unu test et
    $request = Illuminate\Http\Request::create('/login', 'GET');
    echo "✅ Request oluşturuldu: /login<br>";
    
    $response = $app->handle($request);
    echo "✅ Response alındı<br>";
    echo "Status Code: " . $response->getStatusCode() . "<br>";
    
    if ($response->getStatusCode() == 200) {
        echo "✅ Login sayfası başarıyla yüklendi!<br>";
        $content = $response->getContent();
        echo "Content Length: " . strlen($content) . " bytes<br>";
        
        // HTML içeriğini kontrol et
        if (strpos($content, 'Giriş Yap') !== false || strpos($content, 'login') !== false || strpos($content, 'email') !== false) {
            echo "✅ Login formu bulundu!<br>";
        }
        
        echo "<hr><h3>HTML Önizleme (İlk 1000 karakter):</h3>";
        echo "<pre>" . htmlspecialchars(substr($content, 0, 1000)) . "...</pre>";
    } else {
        echo "❌ Hata! Status: " . $response->getStatusCode() . "<br>";
        echo "Content: " . htmlspecialchars($response->getContent()) . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ HATA: " . $e->getMessage() . "<br>";
    echo "Dosya: " . $e->getFile() . "<br>";
    echo "Satır: " . $e->getLine() . "<br>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr>";
echo "<p><strong>Test URL:</strong> <a href='/b2b-gemas-project-main/bayi/public/login'>/b2b-gemas-project-main/bayi/public/login</a></p>";

