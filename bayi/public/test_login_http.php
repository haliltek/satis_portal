<?php
/**
 * HTTP Login Test - Gerçek HTTP isteği simülasyonu
 * http://localhost/b2b-gemas-project-main/bayi/public/test_login_http.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "<h2>🌐 HTTP Login Sayfası Testi</h2>";
echo "<hr>";

try {
    // HTTP request simülasyonu
    $request = Illuminate\Http\Request::create('/login', 'GET');
    $response = $app->handle($request);
    
    echo "<h3>1. HTTP Response:</h3>";
    echo "Status Code: " . $response->getStatusCode() . "<br>";
    
    if ($response->getStatusCode() == 200) {
        echo "✅ Login sayfası başarıyla yüklendi!<br>";
        $content = $response->getContent();
        echo "Content Length: " . strlen($content) . " bytes<br>";
        
        // HTML içeriğini kontrol et
        if (strpos($content, 'Giriş Yap') !== false || strpos($content, 'login') !== false) {
            echo "✅ Login formu bulundu!<br>";
        } else {
            echo "⚠️ Login formu bulunamadı!<br>";
        }
        
        // İlk 500 karakteri göster
        echo "<h4>İlk 500 karakter:</h4>";
        echo "<pre>" . htmlspecialchars(substr($content, 0, 500)) . "...</pre>";
    } else {
        echo "❌ Login sayfası yüklenemedi! Status: " . $response->getStatusCode() . "<br>";
        echo "Content: " . htmlspecialchars($response->getContent()) . "<br>";
    }
    
    echo "<hr>";
    echo "<h3>2. Gerçek Login Testi:</h3>";
    
    // Test kullanıcısı ile login denemesi
    $credentials = [
        'email' => 'test_bayi@gemas.com',
        'password' => 'test123'
    ];
    
    $loginRequest = Illuminate\Http\Request::create('/login', 'POST', $credentials);
    $loginRequest->headers->set('X-CSRF-TOKEN', csrf_token());
    
    // Session başlat
    $session = $app->make('session');
    $session->start();
    
    // Login denemesi
    $auth = Auth::attempt($credentials);
    
    if ($auth) {
        echo "✅ Login başarılı!<br>";
        echo "Kullanıcı: " . Auth::user()->username . "<br>";
        echo "Email: " . Auth::user()->email . "<br>";
        Auth::logout();
    } else {
        echo "❌ Login başarısız!<br>";
    }
    
    echo "<hr>";
    echo "<h3>✅ Test Tamamlandı!</h3>";
    echo "<p><strong>Login Sayfası URL:</strong> <a href='/b2b-gemas-project-main/bayi/public/login' target='_blank'>http://localhost/b2b-gemas-project-main/bayi/public/login</a></p>";
    echo "<p><strong>Test Kullanıcısı:</strong></p>";
    echo "<ul>";
    echo "<li>Email: test_bayi@gemas.com</li>";
    echo "<li>Username: test_bayi</li>";
    echo "<li>Şifre: test123</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h3>❌ Hata:</h3>";
    echo "<p><strong>Mesaj:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Dosya:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Satır:</strong> " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

