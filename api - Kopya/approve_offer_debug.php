<?php
// api/approve_offer_debug.php
// Debug versiyonu - tüm hataları göster

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== API Debug Başladı ===\n\n";

try {
    echo "1. Include files yükleniyor...\n";
    
    // Session başlat
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    echo "   ✓ Session başlatıldı\n";
    
    if (!file_exists(__DIR__ . '/../fonk.php')) {
        die("ERROR: fonk.php bulunamadı!\n");
    }
    require_once __DIR__ . '/../fonk.php';
    echo "   ✓ fonk.php yüklendi\n";
    
    if (!isset($db)) {
        die("ERROR: \$db değişkeni tanımlı değil!\n");
    }
    echo "   ✓ \$db bağlantısı var\n";
    
    if (!file_exists(__DIR__ . '/../services/LoggerService.php')) {
        die("ERROR: LoggerService.php bulunamadı!\n");
    }
    require_once __DIR__ . '/../services/LoggerService.php';
    echo "   ✓ LoggerService.php yüklendi\n";
    
    if (!file_exists(__DIR__ . '/../services/MailService.php')) {
        die("ERROR: MailService.php bulunamadı!\n");
    }
    require_once __DIR__ . '/../services/MailService.php';
    echo "   ✓ MailService.php yüklendi\n";
    
    if (!file_exists(__DIR__ . '/../services/PdfService.php')) {
        die("ERROR: PdfService.php bulunamadı!\n");
    }
    require_once __DIR__ . '/../services/PdfService.php';
    echo "   ✓ PdfService.php yüklendi\n";
    
    echo "\n2. Logger oluşturuluyor...\n";
    $logger = new LoggerService(__DIR__ . '/../logs/offer_approval.log');
    echo "   ✓ Logger oluşturuldu\n";
    
    echo "\n3. POST verisi okunuyor...\n";
    $input = json_decode(file_get_contents('php://input'), true);
    echo "   Input: " . print_r($input, true) . "\n";
    
    if (!isset($input['teklif_id'])) {
        die("ERROR: teklif_id yok!\n");
    }
    
    $teklifId = (int)$input['teklif_id'];
    echo "   ✓ Teklif ID: $teklifId\n";
    
    echo "\n4. Veritabanı sorgusu...\n";
    $stmt = $db->prepare("SELECT * FROM ogteklif2 WHERE id = ?");
    if (!$stmt) {
        die("ERROR: Prepare hatası: " . $db->error . "\n");
    }
    
    $stmt->bind_param('i', $teklifId);
    $stmt->execute();
    $result = $stmt->get_result();
    $teklif = $result->fetch_assoc();
    $stmt->close();
    
    if (!$teklif) {
        die("ERROR: Teklif bulunamadı (ID: $teklifId)\n");
    }
    
    echo "   ✓ Teklif bulundu: " . $teklif['teklifkodu'] . "\n";
    
    echo "\n5. Durum güncelleme...\n";
    $yeniDurum = 'Sipariş Onaylandı / Logoya Aktarım Bekliyor';
    $stmt = $db->prepare("UPDATE ogteklif2 SET durum = ?, statu = 'Onaylandı' WHERE id = ?");
    if (!$stmt) {
        die("ERROR: Update prepare hatası: " . $db->error . "\n");
    }
    
    $stmt->bind_param('si', $yeniDurum, $teklifId);
    if (!$stmt->execute()) {
        die("ERROR: Update execute hatası: " . $stmt->error . "\n");
    }
    $stmt->close();
    
    echo "   ✓ Durum güncellendi\n";
    
    echo "\n=== BAŞARILI ===\n";
    echo json_encode(['success' => true, 'message' => 'Test başarılı']);
    
} catch (Exception $e) {
    echo "\n=== HATA ===\n";
    echo "Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
