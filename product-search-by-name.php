<?php
// Output buffering başlat - PHP hatalarının JSON'a karışmasını önle
ob_start();

// Hata raporlamasını kapat (JSON response için)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Ürün adı ile ürün arama endpoint'i
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

$productName = isset($_GET['name']) ? trim($_GET['name']) : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 1000; // Tüm ürünleri getir (limit çok yüksek)
$pazarTipi = $_SESSION['pazar_tipi'] ?? 'yurtici';

if (empty($productName) || strlen($productName) < 2) {
    echo json_encode(['success' => true, 'products' => []]);
    exit;
}

// Veritabanı bağlantısı - fonk.php'den gelen global $db kullan
global $db;

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı bağlantı hatası']);
    exit;
}

// Ürün adı veya stok kodu ile ürün ara (case-insensitive LIKE ile)
$searchTerm = '%' . strtoupper(trim($productName)) . '%';

$sql = "SELECT urun_id, stokkodu, stokadi, fiyat, export_fiyat, maliyet, doviz, olcubirimi, LOGICALREF 
        FROM urunler 
        WHERE (UPPER(TRIM(stokadi)) LIKE ? OR UPPER(TRIM(stokkodu)) LIKE ?)
        ORDER BY 
            CASE 
                WHEN UPPER(TRIM(stokadi)) LIKE ? THEN 1
                WHEN UPPER(TRIM(stokkodu)) LIKE ? THEN 2
                ELSE 3
            END,
            stokadi ASC
        LIMIT ?";

$stmt = $db->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $db->error]);
    exit;
}

// 5 parametre: stokadi LIKE, stokkodu LIKE, ORDER BY için stokadi, ORDER BY için stokkodu, LIMIT
$stmt->bind_param("ssssi", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Sorgu hatası: ' . $stmt->error]);
    $stmt->close();
    exit;
}
$result = $stmt->get_result();
$products = [];
$dbManager = null;

try {
    if (file_exists("include/db_manager.php")) {
        require_once "include/db_manager.php";
        $dbManager = new DatabaseManager($db);
    }
} catch (Exception $e) {
    // Kampanya indirimi yoksa devam et
}

while ($row = $result->fetch_assoc()) {
    // Pazar tipine göre fiyat seç
    $isForeign = ($pazarTipi === 'yurtdisi');
    $liste = $isForeign ? floatval($row['export_fiyat']) : floatval($row['fiyat']);
    $campaignRate = 0.0;
    if ($dbManager) {
        $campaignRate = $dbManager->getCampaignDiscountForProduct((int)$row['LOGICALREF']) ?? 0.0;
    }
    $unitPrice = $liste * (1 - $campaignRate / 100);
    
    // Döviz ikonu
    $dovizIkon = '';
    switch($row['doviz']) {
        case 'EUR': $dovizIkon = '€'; break;
        case 'USD': $dovizIkon = '$'; break;
        case 'TL': $dovizIkon = '₺'; break;
        default: $dovizIkon = $row['doviz'];
    }
    
    $products[] = [
        'id' => $row['urun_id'],
        'code' => $row['stokkodu'],
        'name' => $row['stokadi'],
        'list_price' => $liste,
        'unit_price' => $unitPrice,
        'discount_rate' => $campaignRate,
        'currency' => $row['doviz'],
        'currency_icon' => $dovizIkon,
        'unit' => $row['olcubirimi'],
        'logicalref' => $row['LOGICALREF'],
        'maliyet' => $row['maliyet']
    ];
}
$stmt->close();

echo json_encode([
    'success' => true,
    'products' => $products
], JSON_UNESCAPED_UNICODE);
?>

