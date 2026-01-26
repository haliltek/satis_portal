<?php
/**
 * Kampanya Test Sayfası
 * 
 * Bu sayfa kampanyaları test etmek için otomatik olarak kampanya ürünlerini
 * sepete ekler ve koşulları sağlayacak miktarlarda yükler.
 */

session_start();
require_once 'fonk.php';
oturumkontrol();

// ERTEK müşterisini otomatik seç (Ana Bayi)
$_SESSION['musteri_id'] = '120.01.E04'; // ERTEK kodu
$_SESSION['pazar_tipi'] = 'yurtdisi';

// Kampanyaları ve ürünlerini çek
$config = require __DIR__ . '/config/config.php';
$dbConfig = $config['db'];
$db = new mysqli($dbConfig['host'], $dbConfig['user'], $dbConfig['pass'], $dbConfig['name'], $dbConfig['port']);
$db->set_charset("utf8mb4");

// Özel fiyatlı ürünleri kampanya_ozel_fiyatlar tablosundan çek
$productsQuery = "SELECT DISTINCT 
                    stok_kodu as product_code,
                    stok_adi as product_name,
                    yurtici_fiyat as list_price,
                    ozel_fiyat as special_price
                  FROM kampanya_ozel_fiyatlar
                  WHERE stok_kodu IS NOT NULL AND stok_kodu != '' AND ozel_fiyat > 0
                  ORDER BY RAND()
                  LIMIT 30";
$productsResult = $db->query($productsQuery);

$testProducts = [];
$campaignInfo = [];

if ($productsResult && $productsResult->num_rows > 0) {
    while ($product = $productsResult->fetch_assoc()) {
        // Her ürün için rastgele bir miktar (10-50 arası)
        $quantity = rand(10, 50);
        
        $testProducts[] = [
            'code' => $product['product_code'],
            'name' => $product['product_name'] ?? 'Ürün Adı Bulunamadı',
            'list_price' => $product['list_price'] ?? 0,
            'special_price' => $product['special_price'] ?? 0,
            'quantity' => $quantity,
            'campaign' => 'Özel Fiyat Kampanyası'
        ];
    }
    
    // Kampanya bilgisi
    $campaignInfo[] = [
        'name' => 'Özel Fiyat Kampanyası',
        'min_quantity' => 0,
        'min_amount' => 0,
        'product_count' => count($testProducts)
    ];
} else {
    // Tablo boşsa uyarı
    die("HATA: kampanya_ozel_fiyatlar tablosunda özel fiyatlı ürün bulunamadı! Lütfen önce özel fiyatları ekleyin.");
}

// Test ürünlerini session'a kaydet
$_SESSION['test_products'] = $testProducts;
$_SESSION['campaign_info'] = $campaignInfo;

// Ürünleri doğrudan cookie'ye ekle
$cookieIndex = 0;
foreach ($testProducts as $product) {
    setcookie("teklif[$cookieIndex][stokkodu]", $product['code'], time() + 3600, '/');
    setcookie("teklif[$cookieIndex][miktar]", $product['quantity'], time() + 3600, '/');
    $cookieIndex++;
}

// teklif-olustur.php'ye yönlendir
header('Location: teklif-olustur.php?test_mode=1');
exit;
?>
