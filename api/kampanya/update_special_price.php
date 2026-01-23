<?php
// api/kampanya/update_special_price.php
// Özel fiyat güncelleme endpoint'i

header('Content-Type: application/json; charset=utf-8');

session_start();
require_once __DIR__ . '/../../include/vt.php';

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
    
    $id = intval($_POST['id'] ?? 0);
    $ozel_fiyat = floatval($_POST['ozel_fiyat'] ?? 0);
    
    if ($id <= 0 || $ozel_fiyat <= 0) {
        throw new Exception('Geçersiz parametreler');
    }
    
    $stmt = $pdo->prepare("
        UPDATE kampanya_ozel_fiyatlar 
        SET ozel_fiyat = ?, updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([$ozel_fiyat, $id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Özel fiyat güncellendi',
        'new_price' => number_format($ozel_fiyat, 2, ',', '.')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
?>
