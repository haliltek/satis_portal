<?php
/**
 * Düzeltilmiş Kampanya Testi
 */

$apiUrl = 'http://localhost/b2b-gemas-project-main/api/apply_manual_campaigns.php';

echo "=== YENİ KAMPANYA YAPISI TEST ===\n\n";

// TEST: Karışık ürünler - Her grup kendi koşulunu kontrol etmeli
echo "TEST: Karışık Ürünler (Farklı Kampanyalar)\n";
echo "--------------------------------------------------------\n";
$test = [
    'customerCode' => '120.01.E04',
    'isCashPayment' => false,
    'paymentPlan' => '060',
    'items' => [
        // Kampanya 1: İlk grup (Min 10 adet, ≥10K€)
        ['code' => '0211211S', 'quantity' => 5, 'price' => 204.00],  // 1020€
        ['code' => '0211212S', 'quantity' => 5, 'price' => 245.00],  // 1225€
        ['code' => '021113T', 'quantity' => 25, 'price' => 406.00], // 10150€
        // Toplam Kampanya 1: 35 adet, 12.395€ → Min 10 ✓, Min 10K€ ✓
        
        // Kampanya 5: LED (Min 1500€)
        ['code' => '051330EO', 'quantity' => 1, 'price' => 14.00],   // 14€
        // Toplam Kampanya 5: 1 adet, 14€ → Min 1500€ ✗ → FALLBACK
        
        // Kampanya 6: Boru (Min 2000€)
        ['code' => '1391003', 'quantity' => 1, 'price' => 1.62],     // 1.62€
        // Toplam Kampanya 6: 1 adet, 1.62€ → Min 2000€ ✗ → FALLBACK
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
    echo "\n✅ API BAŞARILI\n\n";
    
    // İlk 3 ürün
    echo "Kampanya 1 (İlk Grup - Min 10 adet + ≥10K€):\n";
    echo "  0211211S: " . ($result['discounts']['0211211S']['display'] ?? 'YOK') . "\n";
    echo "  0211212S: " . ($result['discounts']['0211212S']['display'] ?? 'YOK') . "\n";
    echo "  021113T: " . ($result['discounts']['021113T']['display'] ?? 'YOK') . "\n";
    echo "  ✓ Beklenen: Cascade (52,94-5,00 veya benzeri)\n\n";
    
    echo "Kampanya 5 (LED - Min 1500€):\n";
    echo "  051330EO: " . ($result['discounts']['051330EO']['display'] ?? 'YOK') . "\n";
    echo "  ✓ Beklenen: Fallback (45,00 vadeli)\n\n";
    
    echo "Kampanya 6 (Boru - Min 2000€):\n";
    echo "  1391003: " . ($result['discounts']['1391003']['display'] ?? 'YOK') . "\n";
    echo "  ✓ Beklenen: Fallback (45,00 vadeli)\n\n";
    
    // Log
    echo "LOG:\n";
    foreach ($result['logs'] as $log) {
        echo "  - $log\n";
    }
} else {
    echo "❌ HATA: " . ($result['message'] ?? 'Bilinmeyen') . "\n";
}

echo "\n=== TEST TAMAMLANDI ===\n";
