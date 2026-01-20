<?php
/**
 * Final Kampanya Testi - Doğru Koşullarla
 */

$apiUrl = 'http://localhost/b2b-gemas-project-main/api/apply_manual_campaigns.php';

echo "=== FİNAL KAMPANYA TESTİ ===\n\n";

// TEST 1: Filtreler (Min 10 adet ✓, ≥10K€ ✓)
echo "TEST 1: Filtreler - Koşulları Sağlıyor\n";
echo "----------------------------------------\n";
$test1 = [
    'customerCode' => '120.01.E04',
    'isCashPayment' => false,
    'paymentPlan' => '

060',
    'items' => [
        ['code' => '0211211S', 'quantity' => 5, 'price' => 204.00],   // 1020€
        ['code' => '021113T', 'quantity' => 30, 'price' => 406.00],  // 12180€
    ]
];
testScenario('Filtreler (35 adet, 13.2K€)', $test1, $apiUrl);

// TEST 2: Filtreler (Min 10 adet ✗)
echo "\nTEST 2: Filtreler - Sadece 5 Adet\n";
echo "----------------------------------------\n";
$test2 = [
    'customerCode' => '120.01.E04',
    'isCashPayment' => false,
    'paymentPlan' => '060',
    'items' => [
        ['code' => '0211211S', 'quantity' => 5, 'price' => 204.00],
    ]
];
testScenario('Filtre (5 adet, 1K€)', $test2, $apiUrl);

// TEST 3: Kuvars & Cam Medya (Min 5000 kg ✗)
echo "\nTEST 3: Kuvars - 1 kg\n";
echo "----------------------------------------\n";
$test3 = [
    'customerCode' => '120.01.E04',
    'isCashPayment' => false,
    'paymentPlan' => '060',
    'items' => [
        ['code' => '023412', 'quantity' => 1, 'price' => 0.76],
    ]
];
testScenario('Kuvars (1 kg)', $test3, $apiUrl);

echo "\n=== TEST TAMAMLANDI ===\n";

function testScenario($name, $data, $url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if ($result['success']) {
        echo "✅ $name:\n";
        foreach ($result['discounts'] as $code => $disc) {
            echo "  $code: " . $disc['display'] . " (Total: %".$disc['total'].")\n";
        }
    } else {
        echo "❌ HATA: " . ($result['message'] ?? 'Bilinmeyen') . "\n";
    }
}
