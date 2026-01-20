<?php
/**
 * Hata Ayıklama Testi - 09511E
 */

$apiUrl = 'http://localhost/b2b-gemas-project-main/api/apply_manual_campaigns.php';

echo "=== HATA AYIKLAMA TESTİ (09511E) ===\n\n";

$test = [
    'customerCode' => '120.01.E04',
    'isCashPayment' => false,
    'paymentPlan' => '060',
    'items' => [
        // Kampanya 5: LED & Ekipman (09511E)
        // Miktar: 5000, Fiyat: 5.50
        ['code' => '09511E', 'quantity' => 5000, 'price' => 5.50],
        
        // Diğer ürünler (bağlam için)
        ['code' => '023412', 'quantity' => 6000, 'price' => 0.76],
        ['code' => '1391003', 'quantity' => 1, 'price' => 1.62],
    ]
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

if ($result['success']) {
    foreach ($result['discounts'] as $code => $disc) {
        echo "Ürün: $code\n";
        echo "  Ekran: " . $disc['display'] . "\n";
        echo "  Kampanyalar: " . implode(', ', $disc['campaigns']) . "\n";
        echo "-------------------\n";
    }
    
    echo "\nLOGLAR:\n";
    foreach ($result['logs'] as $log) {
        if (strpos($log, 'Ertek - LED') !== false || strpos($log, '09511E') !== false) {
             echo "  $log\n";
        }
        // Kampanya 5 ile ilgili logları göster
        if (strpos($log, 'Kampanya: Ertek - LED') !== false) {
            $showNext = true;
            echo "  $log\n";
        } elseif (isset($showNext) && $showNext) {
            echo "  $log\n";
            if (strpos($log, 'Fallback') !== false) $showNext = false;
        }
    }
} else {
    echo "HATA: " . $result['message'];
}
