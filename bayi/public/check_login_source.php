<?php
/**
 * Login Sayfası Kaynak Kontrolü
 * http://localhost/b2b-gemas-project-main/bayi/public/check_login_source.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/login', 'GET');
$response = $app->handle($request);
$content = $response->getContent();

echo "<h2>Login Sayfası Kaynak Analizi</h2>";
echo "<hr>";

// application.js kontrolü
if (strpos($content, 'application.js') !== false) {
    echo "❌ <strong>application.js bulundu!</strong><br>";
    preg_match_all('/application\.js[^"\']*/', $content, $matches);
    echo "Bulunan referanslar:<br>";
    foreach ($matches[0] as $match) {
        echo "- " . htmlspecialchars($match) . "<br>";
    }
} else {
    echo "✅ application.js bulunamadı (iyi!)<br>";
}

// app.min.js kontrolü
if (strpos($content, 'app.min.js') !== false) {
    echo "❌ <strong>app.min.js bulundu!</strong><br>";
    preg_match_all('/app\.min\.js[^"\']*/', $content, $matches);
    echo "Bulunan referanslar:<br>";
    foreach ($matches[0] as $match) {
        echo "- " . htmlspecialchars($match) . "<br>";
    }
} else {
    echo "✅ app.min.js bulunamadı (iyi!)<br>";
}

// Script tag'leri
echo "<hr><h3>Script Tag'leri:</h3>";
preg_match_all('/<script[^>]*src=["\']([^"\']+)["\'][^>]*>/i', $content, $scriptMatches);
if (!empty($scriptMatches[1])) {
    foreach ($scriptMatches[1] as $src) {
        echo "- " . htmlspecialchars($src) . "<br>";
    }
} else {
    echo "Script tag'i bulunamadı<br>";
}

// Link tag'leri (CSS)
echo "<hr><h3>CSS Link Tag'leri:</h3>";
preg_match_all('/<link[^>]*href=["\']([^"\']+)["\'][^>]*>/i', $content, $linkMatches);
if (!empty($linkMatches[1])) {
    foreach ($linkMatches[1] as $href) {
        echo "- " . htmlspecialchars($href) . "<br>";
    }
} else {
    echo "CSS link tag'i bulunamadı<br>";
}

echo "<hr><h3>HTML Kaynağı (İlk 2000 karakter):</h3>";
echo "<pre>" . htmlspecialchars(substr($content, 0, 2000)) . "...</pre>";

