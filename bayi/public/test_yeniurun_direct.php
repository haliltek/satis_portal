<?php
/**
 * Test yeniurun endpoint directly
 * http://localhost/b2b-gemas-project-main/bayi/public/test_yeniurun_direct.php
 */

header('Content-Type: application/json; charset=utf-8');

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/b2b-gemas-project-main/bayi/public/yeniurun');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIE, 'laravel_session=' . ($_COOKIE['laravel_session'] ?? ''));
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h2>🔍 yeniurun Endpoint Testi (Direct HTTP)</h2>";
echo "<hr>";
echo "<h3>HTTP Status Code: " . $httpCode . "</h3>";
echo "<h3>Response:</h3>";
echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; max-height: 500px; overflow-y: auto;'>";
echo htmlspecialchars($response);
echo "</pre>";

// JSON parse test
$json = json_decode($response, true);
if ($json) {
    echo "<h3>✅ JSON Parse Başarılı</h3>";
    echo "<p>Draw: " . ($json['draw'] ?? 'N/A') . "</p>";
    echo "<p>Records Total: " . ($json['recordsTotal'] ?? 'N/A') . "</p>";
    echo "<p>Records Filtered: " . ($json['recordsFiltered'] ?? 'N/A') . "</p>";
    echo "<p>Data Count: " . (isset($json['data']) ? count($json['data']) : 0) . "</p>";
    
    if (isset($json['data']) && count($json['data']) > 0) {
        echo "<h3>İlk Ürün:</h3>";
        echo "<pre>";
        print_r($json['data'][0]);
        echo "</pre>";
    } else {
        echo "<h3>⚠️ Data boş!</h3>";
    }
} else {
    echo "<h3>❌ JSON Parse Hatası</h3>";
    echo "<p>JSON Error: " . json_last_error_msg() . "</p>";
    echo "<p>Response Length: " . strlen($response) . " bytes</p>";
}

