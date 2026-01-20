<?php
// api/save_customer_commercials.php
require_once "../fonk.php";

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['yonetici_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek yöntemi.']);
    exit;
}

$sirket_id = filter_input(INPUT_POST, 'sirket_id', FILTER_VALIDATE_INT);
$ciro_hedefi = filter_input(INPUT_POST, 'ciro_hedefi', FILTER_VALIDATE_FLOAT);
$anlasilan_iskonto = filter_input(INPUT_POST, 'anlasilan_iskonto', FILTER_VALIDATE_FLOAT);
$ozel_risk_notu = isset($_POST['ozel_risk_notu']) ? trim($_POST['ozel_risk_notu']) : '';

if (!$sirket_id) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz şirket ID.']);
    exit;
}

try {
    // Veritabanını güncelle
    $sql = "UPDATE sirket SET 
            ciro_hedefi = ?, 
            anlasilan_iskonto = ?, 
            ozel_risk_notu = ?,
            manual_data_updated_at = NOW(),
            manual_data_updated_by = ?
            WHERE sirket_id = ?";
            
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        throw new Exception("Hazırlama hatası: " . $db->error);
    }

    $yonetici_id = $_SESSION['yonetici_id'];
    
    // floatval ile null kontrolü
    $ciro_hedefi = $ciro_hedefi !== false ? $ciro_hedefi : 0;
    $anlasilan_iskonto = $anlasilan_iskonto !== false ? $anlasilan_iskonto : 0;
    
    $stmt->bind_param("ddsii", $ciro_hedefi, $anlasilan_iskonto, $ozel_risk_notu, $yonetici_id, $sirket_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Ticari bilgiler başarıyla güncellendi.']);
    } else {
        throw new Exception("Güncelleme hatası: " . $stmt->error);
    }
    
    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
?>
