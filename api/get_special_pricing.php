<?php
// api/get_special_pricing.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../fonk.php';

global $db;

$workId = isset($_GET['work_id']) ? (int)$_GET['work_id'] : 0;

if ($workId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz çalışma ID']);
    exit;
}

try {
    // Çalışma bilgilerini al
    $stmt = $db->prepare("SELECT * FROM ozel_fiyat_calismalari WHERE id = ?");
    $stmt->bind_param("i", $workId);
    $stmt->execute();
    $work = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$work) {
        echo json_encode(['success' => false, 'message' => 'Çalışma bulunamadı']);
        exit;
    }

    // Ürünleri al
    $stmt = $db->prepare("SELECT * FROM ozel_fiyat_urunler WHERE calisma_id = ? ORDER BY olusturma_tarihi ASC");
    $stmt->bind_param("i", $workId);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode([
        'success' => true,
        'work' => $work,
        'products' => $products
    ]);

} catch (Exception $e) {
    error_log("get_special_pricing.php Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Veri alınırken hata oluştu']);
}
