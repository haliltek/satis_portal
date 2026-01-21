<?php
/**
 * Test Script: Kampanya Sistemini Test Et
 * 
 * Test senaryoları:
 * 1. POMPALAR - 10 adet, 10.000€+, Peşin → %52-5-10
 * 2. FİLTRELER - 10 adet, Vadeli → %52
 * 3. ÇOK YOLLU VANA - 50 adet (min 100 değil) → Fallback %45
 * 4. FİLTRE MEDYA - 5000 adet, 20.000€+, Peşin → %51-5-10
 * 5. Kampanyada olmayan ürün → Fallback %50.5 (peşin)
 */

require_once __DIR__ . "/fonk.php";

echo "<h1>Kampanya Sistemi Test</h1>";
echo "<style>
    body { font-family: 'Courier New', monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
    h1 { color: #4ec9b0; }
    h2 { color: #569cd6; border-bottom: 2px solid #569cd6; padding-bottom: 5px; margin-top: 30px; }
    .success { color: #4ec9b0; }
    .error { color: #f48771; }
    .info { color: #dcdcaa; }
    pre { background: #2d2d2d; padding: 15px; border-left: 3px solid #007acc; overflow-x: auto; }
    table { border-collapse: collapse; width: 100%; margin: 15px 0; }
    th, td { border: 1px solid #3e3e3e; padding: 8px; text-align: left; }
    th { background: #007acc; color: white; }
    tr:nth-child(even) { background: #2d2d2d; }
</style>";

// Test 1: POMPALAR - Tüm koşullar sağlandı
echo "<h2>TEST 1: POMPALAR (Tüm koşullar sağlandı)</h2>";
$test1 = [
    'items' => [
        ['code' => '0111STRM50M', 'quantity' => 5, 'price' => 302],
        ['code' => '0111STRM80M', 'quantity' => 5, 'price' => 316]
    ],
    'customerCode' => '120.01.E04',
    'isCashPayment' => true
];

$ch = curl_init('http://localhost/b2b-gemas-project-main/api/apply_manual_campaigns.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test1));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$result1 = curl_exec($ch);
curl_close($ch);

$data1 = json_decode($result1, true);
echo "<pre>";
echo "Toplam Miktar: 10 adet\n";
echo "Toplam Tutar: " . number_format((5*302) + (5*316), 2) . "€ (10.000€ üzeri ✓)\n";
echo "Ödeme: Peşin\n\n";
echo "<b>Beklenen:</b> %52.32-5-10 (Cascade)\n";
echo "<b>Sonuç:</b> ";
if ($data1['success']) {
    $discount = $data1['discounts']['0111STRM50M'] ?? null;
    if ($discount) {
        echo "<span class='success'>{$discount['display']} (Toplam: {$discount['total']}%)</span>\n";
        echo "Kampanyalar: " . implode(', ', $discount['campaigns']) . "\n";
    }
} else {
    echo "<span class='error'>HATA: {$data1['message']}</span>\n";
}
echo "\nLog:\n";
foreach ($data1['logs'] ?? [] as $log) {
    echo "$log\n";
}
echo "</pre>";

// Test 2: FİLTRELER - Sadece minimum miktar
echo "<h2>TEST 2: FİLTRELER (Sadece minimum miktar)</h2>";
$test2 = [
    'items' => [
        ['code' => '0211211S', 'quantity' => 10, 'price' => 204]
    ],
    'customerCode' => '120.01.E04',
    'isCashPayment' => false
];

$ch = curl_init('http://localhost/b2b-gemas-project-main/api/apply_manual_campaigns.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test2));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$result2 = curl_exec($ch);
curl_close($ch);

$data2 = json_decode($result2, true);
echo "<pre>";
echo "Toplam Miktar: 10 adet (✓)\n";
echo "Toplam Tutar: 2.040€ (10.000€ altı ✗)\n";
echo "Ödeme: Vadeli\n\n";
echo "<b>Beklenen:</b> %52.94 (Sadece ürün iskontosu)\n";
echo "<b>Sonuç:</b> ";
if ($data2['success']) {
    $discount = $data2['discounts']['0211211S'] ?? null;
    if ($discount) {
        echo "<span class='success'>{$discount['display']} (Toplam: {$discount['total']}%)</span>\n";
    }
} else {
    echo "<span class='error'>HATA: {$data2['message']}</span>\n";
}
echo "</pre>";

// Test 3: ÇOK YOLLU VANA - Minimum miktar sağlanmadı (Fallback)
echo "<h2>TEST 3: ÇOK YOLLU VANA (Fallback)</h2>";
$test3 = [
    'items' => [
        ['code' => '02400', 'quantity' => 50, 'price' => 43]
    ],
    'customerCode' => '120.01.E04',
    'isCashPayment' => false
];

$ch = curl_init('http://localhost/b2b-gemas-project-main/api/apply_manual_campaigns.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test3));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$result3 = curl_exec($ch);
curl_close($ch);

$data3 = json_decode($result3, true);
echo "<pre>";
echo "Toplam Miktar: 50 adet (Min 100 gerekli ✗)\n";
echo "Ödeme: Vadeli\n\n";
echo "<b>Beklenen:</b> %45 (Fallback Vadeli)\n";
echo "<b>Sonuç:</b> ";
if ($data3['success']) {
    $discount = $data3['discounts']['02400'] ?? null;
    if ($discount) {
        echo "<span class='success'>{$discount['display']} (Toplam: {$discount['total']}%)</span>\n";
    }
} else {
    echo "<span class='error'>HATA: {$data3['message']}</span>\n";
}
echo "</pre>";

// Test 4: FİLTRE MEDYA - Yüksek miktar
echo "<h2>TEST 4: FİLTRE MEDYA (Yüksek miktar)</h2>";
$test4 = [
    'items' => [
        ['code' => '02312', 'quantity' => 30000, 'price' => 0.62],
        ['code' => '02313', 'quantity' => 10000, 'price' => 0.62]
    ],
    'customerCode' => '120.01.E04',
    'isCashPayment' => true
];

$ch = curl_init('http://localhost/b2b-gemas-project-main/api/apply_manual_campaigns.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test4));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$result4 = curl_exec($ch);
curl_close($ch);

$data4 = json_decode($result4, true);
echo "<pre>";
echo "Toplam Miktar: 40.000 adet (Min 5000 ✓)\n";
echo "Toplam Tutar: " . number_format(40000 * 0.62, 2) . "€ (20.000€ üzeri ✓)\n";
echo "Ödeme: Peşin\n\n";
echo "<b>Beklenen:</b> %51.61-5-10\n";
echo "<b>Sonuç:</b> ";
if ($data4['success']) {
    $discount = $data4['discounts']['02312'] ?? null;
    if ($discount) {
        echo "<span class='success'>{$discount['display']} (Toplam: {$discount['total']}%)</span>\n";
    }
} else {
    echo "<span class='error'>HATA: {$data4['message']}</span>\n";
}
echo "</pre>";

// Test 5: Kampanyada olmayan ürün
echo "<h2>TEST 5: Kampanyada Olmayan Ürün (Genel Fallback)</h2>";
$test5 = [
    'items' => [
        ['code' => 'UNKNOWN123', 'quantity' => 10, 'price' => 100]
    ],
    'customerCode' => '120.01.E04',
    'isCashPayment' => true
];

$ch = curl_init('http://localhost/b2b-gemas-project-main/api/apply_manual_campaigns.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test5));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$result5 = curl_exec($ch);
curl_close($ch);

$data5 = json_decode($result5, true);
echo "<pre>";
echo "Ürün: UNKNOWN123 (Kampanyada yok)\n";
echo "Ödeme: Peşin\n\n";
echo "<b>Beklenen:</b> %50.5 (Genel Fallback Peşin)\n";
echo "<b>Sonuç:</b> ";
if ($data5['success']) {
    $discount = $data5['discounts']['UNKNOWN123'] ?? null;
    if ($discount) {
        echo "<span class='success'>{$discount['display']} (Toplam: {$discount['total']}%)</span>\n";
    }
} else {
    echo "<span class='error'>HATA: {$data5['message']}</span>\n";
}
echo "</pre>";

echo "<h2>TEST TAMAMLANDI</h2>";
echo "<p>Log dosyası: <code>api/campaign_debug.log</code></p>";
?>
