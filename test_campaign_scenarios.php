<?php
/**
 * Kampanya Sistemi Test - Karışık Senaryolar
 */

$apiUrl = 'http://localhost/b2b-gemas-project-main/api/apply_manual_campaigns.php';

echo "=== KAMPANYA SİSTEMİ TEST ===\n\n";

// TEST 1: Koşulları SAĞLAYAN - Cascade İskonto Bekleniyor
echo "TEST 1: Koşulları Sağlayan (Min 10 adet + ≥5K€ + Peşin)\n";
echo "--------------------------------------------------------\n";
$test1 = [
    'customerCode' => '120.01.E04',
    'isCashPayment' => true,
    'paymentPlan' => '060',
    'items' => [
        ['code' => '0211211S', 'quantity' => 5, 'price' => 204.00],  // İlk grup
        ['code' => '0211212S', 'quantity' => 5, 'price' => 245.00],  // İlk grup
        ['code' => '021113T', 'quantity' => 55, 'price' => 406.00],  // İlk grup
    ]
];
$result1 = testAPI($apiUrl, $test1);
echo "\n✓ Beklenen: 52,94-5,00-10,00 cascade format\n";
echo "✓ Sonuç: " . ($result1['discounts']['0211211S']['display'] ?? 'HATA') . "\n\n";

// TEST 2: Koşulları SAĞLAMAYAN - Fallback Bekleniyor (Peşin)
echo "TEST 2: Koşulları Sağlamayan - Fallback (5 adet, Peşin, 060)\n";
echo "--------------------------------------------------------\n";
$test2 = [
    'customerCode' => '120.01.E04',
    'isCashPayment' => true,
    'paymentPlan' => '060',
    'items' => [
        ['code' => '0211211S', 'quantity' => 5, 'price' => 204.00],
    ]
];
$result2 = testAPI($apiUrl, $test2);
echo "\n✓ Beklenen: 50,50 (Fallback peşin)\n";
echo "✓ Sonuç: " . ($result2['discounts']['0211211S']['display'] ?? 'HATA') . "\n\n";

// TEST 3: Koşulları SAĞLAMAYAN - Fallback Vadeli
echo "TEST 3: Koşulları Sağlamayan - Fallback (5 adet, Vadeli, 060)\n";
echo "--------------------------------------------------------\n";
$test3 = [
    'customerCode' => '120.01.E04',
    'isCashPayment' => false,
    'paymentPlan' => '060',
    'items' => [
        ['code' => '0211212S', 'quantity' => 3, 'price' => 245.00],
    ]
];
$result3 = testAPI($apiUrl, $test3);
echo "\n✓ Beklenen: 45,00 (Fallback vadeli)\n";
echo "✓ Sonuç: " . ($result3['discounts']['0211212S']['display'] ?? 'HATA') . "\n\n";

// TEST 4: Pompa Grubu - 50+ Adet Miktar Bazlı Kural
echo "TEST 4: Pompa Grubu (≥50 adet miktar kuralı)\n";
echo "--------------------------------------------------------\n";
$test4 = [
    'customerCode' => '120.01.E04',
    'isCashPayment' => false,
    'paymentPlan' => '060',
    'items' => [
        ['code' => '0111STRM50M', 'quantity' => 30, 'price' => 144.00],
        ['code' => '0111STRM80M', 'quantity' => 25, 'price' => 150.00],
    ]
];
$result4 = testAPI($apiUrl, $test4);
echo "\n✓ Beklenen: 52,32-5,00 (Pompa iskontosu + miktar ≥50)\n";
echo "✓ Sonuç: " . ($result4['discounts']['0111STRM50M']['display'] ?? 'HATA') . "\n\n";

// TEST 5: Karışık Ürünler - Farklı Gruplar
echo "TEST 5: Karışık Gruplar (LED + Temizlik + Boru)\n";
echo "--------------------------------------------------------\n";
$test5 = [
    'customerCode' => '120.01.E04',
    'isCashPayment' => true,
    'paymentPlan' => '060',
    'items' => [
        ['code' => '051330EO', 'quantity' => 5, 'price' => 14.00],   // LED
        ['code' => '08211', 'quantity' => 3, 'price' => 3.32],       // Temizlik
        ['code' => '1390101', 'quantity' => 100, 'price' => 0.30],   // Boru
    ]
];
$result5 = testAPI($apiUrl, $test5);
echo "\n✓ Beklenen: Ürün iskontoları (koşullara göre cascade veya fallback)\n";
echo "✓ LED (051330EO): " . ($result5['discounts']['051330EO']['display'] ?? 'YOK') . "\n";
echo "✓ Temizlik (08211): " . ($result5['discounts']['08211']['display'] ?? 'YOK') . "\n";
echo "✓ Boru (1390101): " . ($result5['discounts']['1390101']['display'] ?? 'YOK') . "\n\n";

// TEST 6: Kampanyada OLMAYAN Ürün
echo "TEST 6: Kampanyada Olmayan Ürün\n";
echo "--------------------------------------------------------\n";
$test6 = [
    'customerCode' => '120.01.E04',
    'isCashPayment' => false,
    'paymentPlan' => '060',
    'items' => [
        ['code' => 'YANLISKOD123', 'quantity' => 100, 'price' => 500.00],
    ]
];
$result6 = testAPI($apiUrl, $test6);
echo "\n✓ Beklenen: Boş sonuç (ürün kampanyada değil)\n";
echo "✓ Sonuç: " . (empty($result6['discounts']) ? 'BOŞ ✓' : 'HATA - İskonto geldi!') . "\n\n";

echo "\n=== TEST TAMAMLANDI ===\n";

function testAPI($url, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if (!$result['success']) {
        echo "❌ HATA: " . ($result['message'] ?? 'Bilinmeyen hata') . "\n";
        return ['discounts' => []];
    }
    
    // Log'ları göster
    if (!empty($result['logs'])) {
        foreach ($result['logs'] as $log) {
            echo "  LOG: $log\n";
        }
    }
    
    return $result;
}
