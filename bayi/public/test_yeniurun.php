<?php
/**
 * Test yeniurun endpoint
 * http://localhost/b2b-gemas-project-main/bayi/public/test_yeniurun.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Simüle edilmiş request
$request = new \Illuminate\Http\Request();
$controller = new \App\Http\Controllers\Front\HomeController();

// yeniurun metodunu çağır
ob_start();
try {
    $controller->yeniurun($request);
    $output = ob_get_clean();
    
    echo "<h2>✅ yeniurun Endpoint Testi</h2>";
    echo "<hr>";
    echo "<h3>Response:</h3>";
    echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto;'>";
    echo htmlspecialchars($output);
    echo "</pre>";
    
    // JSON parse test
    $json = json_decode($output, true);
    if ($json) {
        echo "<h3>✅ JSON Parse Başarılı</h3>";
        echo "<p>Records Total: " . ($json['recordsTotal'] ?? 'N/A') . "</p>";
        echo "<p>Records Filtered: " . ($json['recordsFiltered'] ?? 'N/A') . "</p>";
        echo "<p>Data Count: " . (isset($json['data']) ? count($json['data']) : 0) . "</p>";
        
        if (isset($json['data']) && count($json['data']) > 0) {
            echo "<h3>İlk Ürün:</h3>";
            echo "<pre>";
            print_r($json['data'][0]);
            echo "</pre>";
        }
    } else {
        echo "<h3>❌ JSON Parse Hatası</h3>";
        echo "<p>JSON Error: " . json_last_error_msg() . "</p>";
    }
} catch (\Exception $e) {
    ob_end_clean();
    echo "<h2>❌ Hata</h2>";
    echo "<pre style='background: #ffebee; padding: 15px; border-radius: 5px;'>";
    echo htmlspecialchars($e->getMessage());
    echo "\n\nStack Trace:\n";
    echo htmlspecialchars($e->getTraceAsString());
    echo "</pre>";
}

