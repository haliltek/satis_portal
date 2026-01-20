<?php
/**
 * Root Route Test
 * http://localhost/b2b-gemas-project-main/bayi/public/test_root.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "<h2>Root Route Test</h2>";
echo "<hr>";

// Root route testi
$request = Illuminate\Http\Request::create('/', 'GET');
$response = $app->handle($request);

echo "<h3>Response:</h3>";
echo "Status: " . $response->getStatusCode() . "<br>";
echo "Location Header: " . ($response->headers->get('Location') ?? 'Yok') . "<br>";
echo "Content Length: " . strlen($response->getContent()) . "<br>";

if ($response->getStatusCode() == 302) {
    echo "<p>✅ Redirect yapılıyor: " . $response->headers->get('Location') . "</p>";
} else {
    echo "<p>⚠️ Redirect yapılmıyor, direkt içerik gösteriliyor.</p>";
}

