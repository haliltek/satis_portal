<?php
// api/get_pricing_products.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../fonk.php';

$work_id = isset($_GET['work_id']) ? intval($_GET['work_id']) : 0;

if ($work_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz çalışma ID']);
    exit;
}

try {
    // Çalışma detaylarını çek
    $stmt = $db->prepare("SELECT U.*, UR.stokadi, UR.olcubirimi, UR.fiyat as guncel_liste_fiyati, UR.maliyet 
                         FROM ozel_fiyat_urunler U 
                         LEFT JOIN urunler UR ON U.stok_kodu = UR.stokkodu
                         WHERE U.calisma_id = ? ORDER BY U.id ASC");
    $stmt->bind_param("i", $work_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = [
            'id' => $row['id'],
            'stok_kodu' => $row['stok_kodu'],
            'urun_adi' => $row['urun_adi'], // Çalışmadaki adını kullan
            'liste_fiyati' => (float)$row['liste_fiyati'],
            'ozel_fiyat' => (float)$row['ozel_fiyat'],
            'iskonto_orani' => (float)$row['iskonto_orani'],
            'doviz' => $row['doviz'],
            'guncel_liste_fiyati' => (float)($row['guncel_liste_fiyati'] ?? 0),
            'maliyet' => (float)($row['maliyet'] ?? 0)
        ];
    }
    $stmt->close();

    echo json_encode([
        'success' => true,
        'products' => $products
    ]);

} catch (Exception $e) {
    error_log("get_pricing_products.php Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Sorgu hatası']);
}
