<?php
// Test cascade discount API
$url = 'http://localhost/b2b-gemas-project-main/api/get_campaign_discount.php';

$data = [
    'items' => [
        [
            'code' => '0211211S',
            'quantity' => 115
        ]
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

echo "HTTP Code: $httpCode\n";
echo "Response:\n";
echo $response;
echo "\n\nParsed:\n";
$json = json_decode($response, true);
print_r($json);

if (isset($json['discounts']['0211211S'])) {
    echo "\n\n=== 0211211S İskonto Detayı ===\n";
    print_r($json['discounts']['0211211S']);
}

if (isset($json['logs'])) {
    echo "\n\n=== LOGS ===\n";
    foreach ($json['logs'] as $log) {
        echo $log . "\n";
    }
}
?>
