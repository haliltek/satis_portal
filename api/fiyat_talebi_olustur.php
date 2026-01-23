<?php
// api/fiyat_talebi_olustur.php - Fiyat talebi oluşturma

// Output buffering başlat
ob_start();

// Hata raporlamayı kapat (JSON bozulmaması için)
error_reporting(0);
ini_set('display_errors', 0);
// Loglama açık dursun
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_error.log');

header("Content-Type: application/json; charset=utf-8");

require_once "../fonk.php";

// Kullanıcı kontrolü
if (!isset($_SESSION['yonetici_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Oturum bulunamadı']);
    exit;
}

// POST verilerini al
$input = json_decode(file_get_contents('php://input'), true);

$urun_id = isset($input['urun_id']) ? intval($input['urun_id']) : 0;
$stokkodu = isset($input['stokkodu']) ? trim($input['stokkodu']) : '';
$stokadi = isset($input['stokadi']) ? trim($input['stokadi']) : '';
$talep_notu = isset($input['talep_notu']) ? trim($input['talep_notu']) : '';

// Validasyon
if ($urun_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz ürün ID']);
    exit;
}

if (empty($talep_notu)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Lütfen talep notunuzu yazınız']);
    exit;
}

try {
    // Kullanıcı bilgilerini al - Farklı kolon isimleri için esnek
    $yonetici_id = $_SESSION['yonetici_id'];
    
    // Önce hangi kolonların var olduğunu kontrol et
    $columns = $db->query("SHOW COLUMNS FROM yonetici")->fetch_all(MYSQLI_ASSOC);
    $columnNames = array_column($columns, 'Field');
    
    // Kullanıcı adı için olası kolon isimleri
    $nameColumn = null;
    $possibleNames = ['yonetici_adi', 'adsoyad', 'name', 'username', 'kullanici_adi'];
    foreach ($possibleNames as $col) {
        if (in_array($col, $columnNames)) {
            $nameColumn = $col;
            break;
        }
    }
    
    if (!$nameColumn) {
        // Hiçbir isim kolonu bulunamadı, ID kullan
        $talep_eden_adi = 'Kullanıcı #' . $yonetici_id;
    } else {
        $stmt = $db->prepare("SELECT $nameColumn FROM yonetici WHERE yonetici_id = ?");
        $stmt->bind_param("i", $yonetici_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        $talep_eden_adi = $user[$nameColumn] ?? 'Kullanıcı #' . $yonetici_id;
    }
    
    // Aynı ürün için bekleyen talep var mı kontrol et
    $stmt = $db->prepare("SELECT talep_id FROM fiyat_talepleri WHERE urun_id = ? AND talep_eden_id = ? AND durum = 'beklemede' LIMIT 1");
    $stmt->bind_param("ii", $urun_id, $yonetici_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();
    $stmt->close();
    
    if ($existing) {
        echo json_encode([
            'success' => false, 
            'message' => 'Daha önce talep oluşturulmuş, lütfen sonuçlanmasını bekleyin.',
            'talep_id' => $existing['talep_id']
        ]);
        exit;
    }
    
    // Yeni talep oluştur
    $stmt = $db->prepare("INSERT INTO fiyat_talepleri 
        (urun_id, stokkodu, stokadi, talep_eden_id, talep_eden_adi, talep_notu, talep_tarihi, durum) 
        VALUES (?, ?, ?, ?, ?, ?, NOW(), 'beklemede')");
    
    $stmt->bind_param("ississ", $urun_id, $stokkodu, $stokadi, $yonetici_id, $talep_eden_adi, $talep_notu);
    
    if ($stmt->execute()) {
        $talep_id = $stmt->insert_id;
        $stmt->close();
        
        // Mail gönder (opsiyonel - hata olsa bile talep kaydedildi)
        $mailSent = false;
        $mailError = null;
        
        // Mail Gönderme Aktif
        if (true) { 
            try {
                // Dosya varlık kontrolü
                $loggerPath = __DIR__ . '/../services/LoggerService.php';
                $mailServicePath = __DIR__ . '/../services/MailService.php';
                
                if (!file_exists($loggerPath) || !file_exists($mailServicePath)) {
                    throw new Exception('Mail servisleri bulunamadı');
                }
                
                require_once $loggerPath;
                require_once $mailServicePath;
                
                $logger = new LoggerService(__DIR__ . '/../logs/mail.log');
                
                // Mail ayarları (urunlerlogo_personel.php'den alındı)
                $mailHost = 'mail.gemas.com.tr';
                $mailPort = 465;
                $mailSecure = 'ssl';
                $mailUsername = 'fiyat@gemas.com.tr';
                $mailPassword = 'Test123Test321';

                
                $mailService = new MailService($mailHost, $mailPort, $mailSecure, $mailUsername, $mailPassword, $logger);
                
                // Mail içeriği
                $recipients = [
                    'haliltek@gemas.com.tr' => 'Halil Tek',
                    'merve@gemas.com.tr' => 'Merve'
                ];
                
                // Yöneticiye gönderilen mail başlığı
                $subject = '🔔 Ürün Fiyat Güncelleme Talebi';
                
                // Ürün sayfası linki
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'];
                $productLink = $baseUrl . '/urunlerlogo.php?search=' . urlencode($stokkodu);
                
                // Gönderici Adı
                $gondericiAdi = 'Gemaş Portal';

                $bodyHtml = '
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: #f8b500; color: #000; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                        .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
                        .info-box { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #f8b500; }
                        .info-row { margin: 8px 0; }
                        .label { font-weight: bold; color: #555; }
                        .value { color: #000; }
                        .button { display: inline-block; padding: 12px 30px; background: #f8b500; color: #000; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
                        .footer { text-align: center; padding: 15px; color: #777; font-size: 12px; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h2 style="margin: 0;">🔔 Yeni Fiyat Güncelleme Talebi</h2>
                        </div>
                        <div class="content">
                            <p>Merhaba,</p>
                            <p>Yeni bir fiyat güncelleme talebi oluşturuldu:</p>
                            
                            <div class="info-box">
                                <div class="info-row">
                                    <span class="label">📦 Stok Kodu:</span>
                                    <span class="value">' . htmlspecialchars($stokkodu) . '</span>
                                </div>
                                <div class="info-row">
                                    <span class="label">📝 Ürün Adı:</span>
                                    <span class="value">' . htmlspecialchars($stokadi) . '</span>
                                </div>
                                <div class="info-row">
                                    <span class="label">👤 Talep Eden:</span>
                                    <span class="value">' . htmlspecialchars($talep_eden_adi) . '</span>
                                </div>
                                <div class="info-row">
                                    <span class="label">📅 Tarih:</span>
                                    <span class="value">' . date('d.m.Y H:i') . '</span>
                                </div>
                                <div class="info-row">
                                    <span class="label">💬 Talep Notu:</span>
                                    <div style="background: #fff; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 3px;">
                                        ' . nl2br(htmlspecialchars($talep_notu)) . '
                                    </div>
                                </div>
                            </div>
                            
                            <div style="text-align: center;">
                                <a href="' . $productLink . '" class="button">
                                    🔍 İşlem İçin Tıklayınız
                                </a>
                            </div>
                            
                            <p style="font-size: 12px; color: #666; margin-top: 20px;">
                                <strong>Not:</strong> Butona tıkladığınızda ürün otomatik olarak aranmış şekilde açılacaktır.
                            </p>
                        </div>
                        <div class="footer">
                            <p>Bu mail otomatik olarak gönderilmiştir.</p>
                            <p>© ' . date('Y') . ' Gemas Pool Technology</p>
                        </div>
                    </div>
                </body>
                </html>';
                
                foreach ($recipients as $email => $name) {
                    $sent = $mailService->sendMail($email, $name, $subject, $bodyHtml, $gondericiAdi);
                    if ($sent) $mailSent = true;
                }

                
            } catch (Exception $e) {
                // Mail hatası - sadece logla, talep zaten kaydedildi
                $mailError = $e->getMessage();
                error_log("Fiyat talebi mail hatası: " . $mailError);
            }
        }
        
        // Buffer temizle ve JSON bas
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Fiyat talebiniz başarıyla oluşturuldu. Yönetici onayı bekleniyor.',
            'talep_id' => $talep_id,
            'mail_sent' => $mailSent,
            'mail_error' => $mailError
        ]);
    } else {
        throw new Exception('Talep oluşturulamadı: ' . $stmt->error);
    }
    
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Bir hata oluştu: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
