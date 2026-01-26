<?php
// api/delete_special_pricing.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../fonk.php';

global $db;

$workId = isset($_POST['work_id']) ? (int)$_POST['work_id'] : 0;

if ($workId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz çalışma ID']);
    exit;
}

try {
    // CASCADE ile ürünler de silinecek
    $stmt = $db->prepare("DELETE FROM ozel_fiyat_calismalari WHERE id = ?");
    $stmt->bind_param("i", $workId);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($affected > 0) {
        echo json_encode(['success' => true, 'message' => 'Çalışma silindi']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Çalışma bulunamadı']);
    }

} catch (Exception $e) {
    error_log("delete_special_pricing.php Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Silme sırasında hata oluştu']);
}
