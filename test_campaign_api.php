<?php
// Kampanya API testi
header('Content-Type: text/html; charset=utf-8');

echo "<h2>Kampanya API Test</h2>";

$url = 'http://localhost/b2b-gemas-project-main/api/get_campaign_discount.php';
$data = [
    'items' => [
        ['code' => '0211211S', 'quantity' => 10]
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
echo "<p><strong>Response:</strong></p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

$json = json_decode($response, true);
if ($json) {
    echo "<h3>Parsed JSON:</h3>";
    echo "<pre>" . print_r($json, true) . "</pre>";
    
    if (isset($json['success']) && $json['success']) {
        echo "<p style='color: green;'>✓ API çalışıyor!</p>";
        if (!empty($json['discounts'])) {
            echo "<p style='color: green;'>✓ Kampanya bulundu ve iskonto hesaplandı!</p>";
            foreach ($json['discounts'] as $code => $discount) {
                echo "<p>Ürün: $code → İskonto: %$discount</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠ Kampanya bulunamadı veya koşul sağlanmadı</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ API hatası: " . ($json['message'] ?? 'Bilinmeyen hata') . "</p>";
    }
}
?>
