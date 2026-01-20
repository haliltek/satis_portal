<?php
// Output buffering başlat - PHP hatalarının JSON'a karışmasını önle
ob_start();

// Hata raporlamasını kapat (JSON response için)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Stok kodu ile ürün arama endpoint'i
try {
    require_once "fonk.php";
    oturumkontrol();
} catch (Exception $e) {
    ob_end_clean(); // Buffer'ı temizle
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Başlangıç hatası: ' . $e->getMessage()]);
    exit;
}

// Buffer'ı temizle (varsa PHP hataları)
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');

$stokKodu = isset($_GET['code']) ? trim($_GET['code']) : '';
$pazarTipi = $_SESSION['pazar_tipi'] ?? 'yurtici';

if (empty($stokKodu)) {
    echo json_encode(['success' => false, 'message' => 'Stok kodu boş olamaz']);
    exit;
}

// Veritabanı bağlantısı - fonk.php'den gelen global $db kullan
global $db;

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı bağlantı hatası']);
    exit;
}

// Stok kodu ile ürün ara (case-insensitive ve trim ile)
// Pazar tipine göre fiyat alanını seç
$sql = "SELECT urun_id, stokkodu, stokadi, fiyat, export_fiyat, doviz, olcubirimi, LOGICALREF 
        FROM urunler 
        WHERE TRIM(UPPER(stokkodu)) = TRIM(UPPER(?))
        LIMIT 1";

$stmt = $db->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $db->error]);
    exit;
}

$stmt->bind_param("s", $stokKodu);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Sorgu hatası: ' . $stmt->error]);
    $stmt->close();
    exit;
}
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Ürün bulunamadı']);
    exit;
}

// Kampanya indirimini kontrol et
$campaignRate = 0.0;
try {
    if (file_exists("include/db_manager.php")) {
        require_once "include/db_manager.php";
        $dbManager = new DatabaseManager($db);
        $campaignRate = $dbManager->getCampaignDiscountForProduct((int)$product['LOGICALREF']) ?? 0.0;
    }
} catch (Exception $e) {
    // Kampanya indirimi yoksa devam et
    $campaignRate = 0.0;
}

// Pazar tipine göre fiyat seç
$isForeign = ($pazarTipi === 'yurtdisi');
$liste = $isForeign ? floatval($product['export_fiyat']) : floatval($product['fiyat']);
$unitPrice = $liste * (1 - $campaignRate / 100);

// Döviz ikonu
$dovizIkon = '';
switch($product['doviz']) {
    case 'EUR': $dovizIkon = '€'; break;
    case 'USD': $dovizIkon = '$'; break;
    case 'TL': $dovizIkon = '₺'; break;
    default: $dovizIkon = $product['doviz'];
}

// JSON response
$response = [
    'success' => true,
    'product' => [
        'id' => $product['urun_id'],
        'code' => $product['stokkodu'],
        'name' => $product['stokadi'],
        'list_price' => $liste,
        'unit_price' => $unitPrice,
        'discount_rate' => $campaignRate,
        'currency' => $product['doviz'],
        'currency_icon' => $dovizIkon,
        'unit' => $product['olcubirimi'],
        'logicalref' => $product['LOGICALREF']
    ]
];

// Buffer'ı temizle ve JSON döndür
ob_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;

