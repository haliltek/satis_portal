<?php
// Test Manuel Kampanya API
$url = 'http://localhost/b2b-gemas-project-main/api/apply_manual_campaigns.php';

// Test 1: 10+ adet, <10K€, peşin ödeme
echo "=== TEST 1: 10+ adet + Peşin Ödeme ===\n";
$data = [
    'customerCode' => '120.01.E04',
    'isCashPayment' => true,
    'items' => [
        ['code' => '0211211S', 'quantity' => 5, 'price' => 204],
        ['code' => '0211212S', 'quantity' => 6, 'price' => 115]
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
curl_close($ch);

echo "Response:\n";
$json = json_decode($response, true);
print_r($json);

if (isset($json['discounts']['0211211S'])) {
    echo "\n0211211S İskonto: " . $json['discounts']['0211211S']['display'] . "\n";
    echo "Toplam: %" . $json['discounts']['0211211S']['total'] . "\n";
}

// Test 2: <10 adet (fallback)
echo "\n\n=== TEST 2: <10 adet (Fallback) ===\n";
$data['items'] = [
    ['code' => '0211211S', 'quantity' => 5, 'price' => 204]
];
$data['isCashPayment'] = false;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
curl_close($ch);

$json = json_decode($response, true);
if (isset($json['discounts']['0211211S'])) {
    echo "0211211S İskonto: " . $json['discounts']['0211211S']['display'] . "\n";
    echo "Beklenen: %45,00 (fallback)\n";
}

// Test 3: 10+ adet, ≥10K€, peşin
echo "\n\n=== TEST 3: 10+ adet + ≥10K€ + Peşin ===\n";
$data['items'] = [
    ['code' => '0211211S', 'quantity' => 50, 'price' => 204]  // 50 * 204 = 10.200€
];
$data['isCashPayment'] = true;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
curl_close($ch);

$json = json_decode($response, true);
if (isset($json['discounts']['0211211S'])) {
    echo "0211211S İskonto: " . $json['discounts']['0211211S']['display'] . "\n";
    echo "Beklenen: 52,94-5,00-10,00 (cascade)\n";
}
?>
