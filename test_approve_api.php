<?php
// test_approve_api.php - API'yi test et ve hataları göster

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>API Test</h2>";

// Test verisi
$data = [
    'teklif_id' => 59,
    'customer_email' => 'haliltek@gemas.com.tr',
    'customer_name' => 'ERTEK YAPI VE MAK.END.EKIPMANLARI SAN.TIC.LTD.STI.'
];

echo "<h3>Gönderilen Veri:</h3>";
echo "<pre>" . print_r($data, true) . "</pre>";

// Production API'yi çağır
$url = 'http://localhost/b2b-gemas-project-main/api/approve_offer.php';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<h3>HTTP Status Code:</h3>";
echo "<p><strong>" . $httpCode . "</strong></p>";

if ($error) {
    echo "<h3>cURL Error:</h3>";
    echo "<p style='color: red;'>" . $error . "</p>";
}

echo "<h3>Response:</h3>";
echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
echo htmlspecialchars($response);
echo "</pre>";

// JSON decode dene
echo "<h3>JSON Decode:</h3>";
$decoded = json_decode($response, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "<pre>" . print_r($decoded, true) . "</pre>";
} else {
    echo "<p style='color: red;'>JSON Error: " . json_last_error_msg() . "</p>";
}
