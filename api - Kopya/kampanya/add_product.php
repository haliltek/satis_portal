<?php
// api/kampanya/add_product.php
// Yeni ürün ekleme endpoint'i

header('Content-Type: application/json; charset=utf-8');

session_start();
require_once __DIR__ . '/../../include/vt.php';
require_once __DIR__ . '/../../fonk.php';

// Yetki kontrolü
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Yönetici') {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host={$sql_details['host']};dbname={$sql_details['db']};charset=utf8mb4",
        $sql_details['user'],
        $sql_details['pass'],
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
    
    $stok_kodu = trim($_POST['stok_kodu'] ?? '');
    $ozel_fiyat = floatval($_POST['ozel_fiyat'] ?? 0);
    $kategori = trim($_POST['kategori'] ?? '');
    
    if (empty($stok_kodu) || $ozel_fiyat <= 0) {
        throw new Exception('Stok kodu ve özel fiyat gerekli');
    }
    
    // urunler tablosundan ürün bilgilerini çek
    $stmt = $pdo->prepare("
        SELECT stokkodu, stokadi, fiyat, export_fiyat, logicalref 
        FROM urunler 
        WHERE stokkodu = ? OR stokkodu = ?
        LIMIT 1
    ");
    $stmt->execute([$stok_kodu, ltrim($stok_kodu, '0')]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception('Ürün Logo sisteminde bulunamadı');
    }
    
    // Kampanya tablosuna ekle
    $stmt = $pdo->prepare("
        INSERT INTO kampanya_ozel_fiyatlar 
        (stok_kodu, stok_adi, yurtici_fiyat, ihracat_fiyat, ozel_fiyat, kategori, logicalref) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        ozel_fiyat = VALUES(ozel_fiyat),
        kategori = VALUES(kategori),
        updated_at = NOW()
    ");
    
    $stmt->execute([
        $product['stokkodu'],
        $product['stokadi'],
        $product['fiyat'],
        $product['export_fiyat'],
        $ozel_fiyat,
        $kategori,
        $product['logicalref']
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Ürün kampanyaya eklendi',
        'product' => [
            'code' => $product['stokkodu'],
            'name' => $product['stokadi'],
            'domestic_price' => $product['fiyat'],
            'export_price' => $product['export_fiyat'],
            'special_price' => $ozel_fiyat
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
?>
