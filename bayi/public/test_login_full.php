<?php
/**
 * Login Sayfası Tam Test Scripti
 * http://localhost/b2b-gemas-project-main/bayi/public/test_login_full.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "<h2>🔍 Login Sayfası Tam Test</h2>";
echo "<hr>";

try {
    // 1. Route kontrolü
    echo "<h3>1. Route Kontrolü:</h3>";
    $routes = Route::getRoutes();
    $loginRoute = $routes->getByName('login');
    if ($loginRoute) {
        echo "✅ Login route bulundu: " . $loginRoute->uri() . "<br>";
        echo "   Controller: " . $loginRoute->getActionName() . "<br>";
        echo "   URL: " . url('login') . "<br>";
    } else {
        echo "❌ Login route bulunamadı!<br>";
    }
    
    // 2. View dosyaları kontrolü
    echo "<h3>2. View Dosyaları:</h3>";
    $viewPath = resource_path('views/auth/login.blade.php');
    $layoutPath = resource_path('views/panel/layouts/login.blade.php');
    
    if (file_exists($viewPath)) {
        echo "✅ Login view: $viewPath<br>";
    } else {
        echo "❌ Login view bulunamadı!<br>";
    }
    
    if (file_exists($layoutPath)) {
        echo "✅ Layout view: $layoutPath<br>";
    } else {
        echo "❌ Layout view bulunamadı!<br>";
    }
    
    // 3. Helper fonksiyonları
    echo "<h3>3. Helper Fonksiyonları:</h3>";
    try {
        $baslik = baslik();
        echo "✅ baslik(): " . htmlspecialchars($baslik) . "<br>";
    } catch (Exception $e) {
        echo "❌ baslik() hatası: " . $e->getMessage() . "<br>";
    }
    
    try {
        $logo = logo();
        echo "✅ logo(): " . htmlspecialchars($logo) . "<br>";
    } catch (Exception $e) {
        echo "❌ logo() hatası: " . $e->getMessage() . "<br>";
    }
    
    // 4. View render testi (session ile)
    echo "<h3>4. View Render Testi:</h3>";
    try {
        // Session başlat
        if (!session_id()) {
            session_start();
        }
        
        // Errors değişkenini başlat (Laravel'in yaptığı gibi)
        $errors = new Illuminate\Support\ViewErrorBag();
        
        $view = view('auth.login');
        $html = $view->render();
        echo "✅ View başarıyla render edildi! (" . strlen($html) . " karakter)<br>";
        echo "   İlk 200 karakter: " . htmlspecialchars(substr($html, 0, 200)) . "...<br>";
    } catch (Exception $e) {
        echo "❌ View render hatası: " . $e->getMessage() . "<br>";
        echo "   Dosya: " . $e->getFile() . "<br>";
        echo "   Satır: " . $e->getLine() . "<br>";
        echo "   Stack trace:<br><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
    
    // 5. Test kullanıcısı kontrolü
    echo "<h3>5. Test Kullanıcısı:</h3>";
    try {
        $user = DB::table('b2b_users')->where('email', 'test_bayi@gemas.com')->orWhere('username', 'test_bayi')->first();
        if ($user) {
            echo "✅ Test kullanıcısı bulundu:<br>";
            echo "   ID: " . $user->id . "<br>";
            echo "   Username: " . ($user->username ?? 'N/A') . "<br>";
            echo "   Email: " . ($user->email ?? 'N/A') . "<br>";
            echo "   Status: " . ($user->status ?? 'N/A') . "<br>";
            echo "   Role: " . ($user->role ?? 'N/A') . "<br>";
        } else {
            echo "❌ Test kullanıcısı bulunamadı!<br>";
            echo "   <a href='create_test_bayi.php'>Test kullanıcısı oluştur</a><br>";
        }
    } catch (Exception $e) {
        echo "❌ Kullanıcı kontrolü hatası: " . $e->getMessage() . "<br>";
    }
    
    // 6. Login işlemi testi
    echo "<h3>6. Login İşlemi Testi:</h3>";
    try {
        $testEmail = 'test_bayi@gemas.com';
        $testPassword = 'test123';
        
        $user = DB::table('b2b_users')
            ->where(function($query) use ($testEmail) {
                $query->where('email', $testEmail)
                      ->orWhere('username', $testEmail);
            })
            ->where('status', 1)
            ->first();
        
        if ($user) {
            // Şifre kontrolü
            if (Hash::check($testPassword, $user->password)) {
                echo "✅ Şifre doğru! Login başarılı olmalı.<br>";
            } else {
                echo "❌ Şifre yanlış!<br>";
            }
        } else {
            echo "❌ Kullanıcı bulunamadı veya aktif değil!<br>";
        }
    } catch (Exception $e) {
        echo "❌ Login testi hatası: " . $e->getMessage() . "<br>";
    }
    
    echo "<hr>";
    echo "<h3>✅ Test Tamamlandı!</h3>";
    echo "<p><strong>Login Sayfası:</strong> <a href='/b2b-gemas-project-main/bayi/public/login' target='_blank'>/b2b-gemas-project-main/bayi/public/login</a></p>";
    echo "<p><strong>Test Kullanıcısı:</strong></p>";
    echo "<ul>";
    echo "<li>Email/Username: test_bayi@gemas.com veya test_bayi</li>";
    echo "<li>Şifre: test123</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h3>❌ Genel Hata:</h3>";
    echo "<p><strong>Mesaj:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Dosya:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Satır:</strong> " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

