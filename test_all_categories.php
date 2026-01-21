<?php
/**
 * Test Script for 10-Category Campaign System
 * Tests all categories including the new 5 additions
 */

require_once 'fonk.php';

// Test configuration
$testCustomer = '120.01.E04'; // ERTEK
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head>";
echo "<style>
body { font-family: Arial; margin: 20px; background: #f5f5f5; }
.test-section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
h2 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
h3 { color: #666; margin-top: 20px; }
.success { color: green; font-weight: bold; }
.fail { color: red; font-weight: bold; }
.info { color: #666; font-size: 0.9em; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
th { background: #f8f9fa; font-weight: bold; }
.cascade { background: #e7f3ff; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
</style></head><body>";

echo "<h1>🎯 10 Kategori Kampanya Sistemi Test Paketi</h1>";

/**
 * Test helper function
 */
function testCampaign($testName, $items, $customerCode, $isCashPayment, $expectedMin = null, $expectedMax = null) {
    echo "<div class='test-section'>";
    echo "<h3>📋 $testName</h3>";
    
    $payload = [
        'items' => $items,
        'customerCode' => $customerCode,
        'isCashPayment' => $isCashPayment
    ];
    
    echo "<div class='info'>";
    echo "Ödeme: <strong>" . ($isCashPayment ? '💵 NAKİT' : '💳 KREDİLİ') . "</strong><br>";
    echo "Müşteri: <strong>$customerCode</strong><br>";
    echo "Ürün Sayısı: <strong>" . count($items) . "</strong>";
    echo "</div>";
    
    // Show items
    echo "<table>";
    echo "<tr><th>Ürün Kodu</th><th>Miktar</th><th>Fiyat</th><th>Toplam</th></tr>";
    $totalAmount = 0;
    foreach ($items as $item) {
        $itemTotal = $item['quantity'] * $item['price'];
        $totalAmount += $itemTotal;
        echo "<tr>";
        echo "<td>{$item['code']}</td>";
        echo "<td>{$item['quantity']}</td>";
        echo "<td>" . number_format($item['price'], 2) . "€</td>";
        echo "<td>" . number_format($itemTotal, 2) . "€</td>";
        echo "</tr>";
    }
    echo "<tr style='font-weight:bold; background:#f8f9fa;'>";
    echo "<td colspan='3'>GENEL TOPLAM</td>";
    echo "<td>" . number_format($totalAmount, 2) . "€</td>";
    echo "</tr>";
    echo "</table>";
    
    // Call API
    $ch = curl_init('http://localhost/b2b-gemas-project-main/api/apply_manual_campaigns.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if ($httpCode == 200 && $result) {
        echo "<h4 style='color: #007bff;'>✓ API Sonucu:</h4>";
        echo "<table>";
        
        if (isset($result['items']) && is_array($result['items'])) {
            echo "<tr><th>Ürün Kodu</th><th>İskonto Oranı</th><th>İskonto Formülü</th><th>Kampanya</th></tr>";
            foreach ($result['items'] as $item) {
                echo "<tr>";
                echo "<td>{$item['code']}</td>";
                echo "<td><strong>" . number_format($item['discountRate'], 2) . "%</strong></td>";
                echo "<td><span class='cascade'>{$item['iskonto_formulu']}</span></td>";
                echo "<td class='info'>" . ($item['campaignName'] ?? 'Fallback') . "</td>";
                echo "</tr>";
            }
        }
        
        echo "</table>";
        
        // Check expected range
        if ($expectedMin !== null && $expectedMax !== null) {
            $actualDiscount = $result['items'][0]['discountRate'] ?? 0;
            if ($actualDiscount >= $expectedMin && $actualDiscount <= $expectedMax) {
                echo "<div class='success'>✓ Beklenen aralıkta: {$expectedMin}% - {$expectedMax}%</div>";
            } else {
                echo "<div class='fail'>✗ Aralık dışı! Beklenen: {$expectedMin}%-{$expectedMax}%, Gerçek: {$actualDiscount}%</div>";
            }
        }
        
    } else {
        echo "<div class='fail'>✗ API Hatası: HTTP $httpCode</div>";
        echo "<pre>$response</pre>";
    }
    
    echo "</div>";
}

// ====================
// TEST SUITE
// ====================

echo "<div class='test-section'>";
echo "<h2>🔵 Mevcut 5 Kategori Testleri</h2>";
echo "</div>";

// Test 1: POMPALAR - Minimum karşılanıyor
testCampaign(
    "Test 1: POMPALAR - Min Miktar Karşılanıyor (10+ adet)",
    [
        ['code' => '0111STRM50M', 'quantity' => 10, 'price' => 302],
        ['code' => '0111STRM75M', 'quantity' => 2, 'price' => 336]
    ],
    $testCustomer,
    true,
    50, // min expected
    60  // max expected
);

// Test 2: POMPALAR - Minimum karşılanmıyor (Fallback)
testCampaign(
    "Test 2: POMPALAR - Min Miktar Karşılanmıyor (Fallback)",
    [
        ['code' => '0111STRM50M', 'quantity' => 5, 'price' => 302]
    ],
    $testCustomer,
    true,
    50, // Fallback nakit
    51
);

// Test 3: FİLTRELER - Min tutar karşılanıyor
testCampaign(
    "Test 3: FİLTRELER - Min Tutar Karşılanıyor (1.500€+)",
    [
        ['code' => '0141S210M', 'quantity' => 5, 'price' => 400]
    ],
    $testCustomer,
    false, // kredili
    48,
    52
);

// Test 4: ÇOK YOLLU VANA
testCampaign(
    "Test 4: ÇOK YOLLU VANA - Min Tutar Karşılanıyor",
    [
        ['code' => '0145VANA6Y.11/2', 'quantity' => 20, 'price' => 100]
    ],
    $testCustomer,
    true,
    50,
    60
);

// Test 5: MERDİVEN
testCampaign(
    "Test 5: MERDİVEN - Fallback",
    [
        ['code' => '0152HAVUZM304', 'quantity' => 2, 'price' => 200]
    ],
    $testCustomer,
    false,
    45,
    46
);

echo "<div class='test-section'>";
echo "<h2>🟢 Yeni 5 Kategori Testleri</h2>";
echo "</div>";

// Test 6: KENAR EKİPMAN - Min miktar karşılanıyor
testCampaign(
    "Test 6: KENAR EKİPMAN - Min Miktar Karşılanıyor (500+ adet)",
    [
        ['code' => '0151IZGARA20.PVC', 'quantity' => 500, 'price' => 5],
        ['code' => '0151IZGARA24.PVC', 'quantity' => 100, 'price' => 6]
    ],
    $testCustomer,
    true,
    50,
    60
);

// Test 7: HAVUZİÇİ EKİPMAN
testCampaign(
    "Test 7: HAVUZİÇİ EKİPMAN - Min Tutar Karşılanıyor (1.500€+)",
    [
        ['code' => '0153BASAMAK.PASLANMAZ', 'quantity' => 30, 'price' => 60]
    ],
    $testCustomer,
    false,
    48,
    52
);

// Test 8: LEDLER
testCampaign(
    "Test 8: LEDLER - Min Tutar Karşılanıyor (1.500€+)",
    [
        ['code' => '0154LED.BEYAZ.P100', 'quantity' => 20, 'price' => 80]
    ],
    $testCustomer,
    true,
    50,
    60
);

// Test 9: TEMİZLİK EKİPMANLARI
testCampaign(
    "Test 9: TEMİZLİK EKİPMANLARI - Fallback",
    [
        ['code' => '0156ROBOTT5', 'quantity' => 1, 'price' => 800]
    ],
    $testCustomer,
    false,
    45,
    46
);

// Test 10: BORU
testCampaign(
    "Test 10: BORU - Min Tutar Karşılanıyor (2.000€+)",
    [
        ['code' => '0157BORU.PVC.50MM', 'quantity' => 100, 'price' => 25]
    ],
    $testCustomer,
    true,
    50,
    60
);

echo "<div class='test-section'>";
echo "<h2>🔴 Karma Testler</h2>";
echo "</div>";

// Test 11: Birden fazla kategori aynı anda
testCampaign(
    "Test 11: Karma Sepet - Çoklu Kategori",
    [
        ['code' => '0111STRM50M', 'quantity' => 10, 'price' => 302],  // POMPALAR
        ['code' => '0141S210M', 'quantity' => 5, 'price' => 400],     // FİLTRELER
        ['code' => '0154LED.BEYAZ.P100', 'quantity' => 25, 'price' => 80]  // LEDLER
    ],
    $testCustomer,
    true
);

// Test 12: Kampanya dışı ürün
testCampaign(
    "Test 12: Kampanya Dışı Ürün (Fallback)",
    [
        ['code' => 'DIGER.URUN.123', 'quantity' => 5, 'price' => 100]
    ],
    $testCustomer,
    false,
    45, // Kredili fallback
    46
);

echo "<div class='test-section' style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;'>";
echo "<h2 style='color: white; border-color: white;'>✅ TEST PAKETİ TAMAMLANDI</h2>";
echo "<p>Toplam 12 test senaryosu çalıştırıldı.</p>";
echo "<p><strong>Sonraki Adım:</strong> <a href='kampanyalar.php' style='color: #fff; text-decoration: underline;'>Kampanyalar sayfasını görüntüle</a></p>";
echo "</div>";

echo "</body></html>";
