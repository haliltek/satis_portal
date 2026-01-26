<?php
// api/approve_offer.php
// Müşteri teklifi onayladığında çağrılır

// Output buffering başlat - herhangi bir çıktıyı yakala
ob_start();

// Hataları gizle - sadece JSON döndür
error_reporting(0);
ini_set('display_errors', 0);

// Session başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Veritabanı bağlantısı
require_once __DIR__ . '/../fonk.php';

if (!isset($db) || !$db) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Veritabanı bağlantısı başarısız']);
    exit;
}

require_once __DIR__ . '/../services/LoggerService.php';
require_once __DIR__ . '/../services/MailService.php';
require_once __DIR__ . '/../services/PdfService.php';

// Tüm önceki output'u temizle
ob_end_clean();

// Şimdi JSON header gönder
header('Content-Type: application/json; charset=utf-8');

// Logger başlat
$logger = new LoggerService(__DIR__ . '/../logs/offer_approval.log');

try {
    // POST verilerini al
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['teklif_id'])) {
        throw new Exception('Teklif ID gerekli');
    }

    $teklifId = (int)$input['teklif_id'];
    $customerEmail = $input['customer_email'] ?? '';
    $customerName = $input['customer_name'] ?? '';

    $logger->log("Onay işlemi başladı → Teklif ID: {$teklifId}");

    // Teklif bilgilerini al (ogteklif2 tablosundan)
    $stmt = $db->prepare("SELECT * FROM ogteklif2 WHERE id = ?");
    $stmt->bind_param('i', $teklifId);
    $stmt->execute();
    $result = $stmt->get_result();
    $teklif = $result->fetch_assoc();
    $stmt->close();

    if (!$teklif) {
        throw new Exception('Teklif bulunamadı');
    }

    // Durum kontrolü
    if ($teklif['durum'] === 'Sipariş Onaylandı / Logoya Aktarım Bekliyor') {
        throw new Exception('Bu teklif zaten onaylanmış');
    }

    // Durumu güncelle
    $yeniDurum = 'Sipariş Onaylandı / Logoya Aktarım Bekliyor';
    $onayTarihi = date('Y-m-d H:i:s');
    
    $stmt = $db->prepare("UPDATE ogteklif2 SET durum = ?, statu = 'Onaylandı' WHERE id = ?");
    $stmt->bind_param('si', $yeniDurum, $teklifId);
    
    if (!$stmt->execute()) {
        throw new Exception('Durum güncellenemedi: ' . $stmt->error);
    }
    $stmt->close();

    $logger->log("Durum güncellendi → {$yeniDurum}");

    // PDF oluştur
    $pdfService = new PdfService($logger);
    $pdfPath = $pdfService->createOfferPdf($teklifId, $db);
    
    if (!$pdfPath) {
        $logger->log("PDF oluşturulamadı, ancak onay işlemi devam ediyor", "WARNING");
    } else {
        $logger->log("PDF oluşturuldu → {$pdfPath}");
    }

    // Mail ayarlarını al (.env veya config'den)
    $mailHost = getenv('MAIL_HOST') ?: 'mail.gemas.com.tr';
    $mailPort = getenv('MAIL_PORT') ?: 465;
    $mailSecure = getenv('MAIL_SECURE') ?: 'ssl';
    $mailUsername = getenv('MAIL_USERNAME') ?: 'satis@gemas.com.tr';
    $mailPassword = getenv('MAIL_PASSWORD') ?: 'Halil12621262.';

    $mailService = new MailService($mailHost, $mailPort, $mailSecure, $mailUsername, $mailPassword, $logger);

    // Satışçı bilgilerini al (hazirlayanid'den)
    $satisciEmail = '';
    $satisciAd = '';
    
    if (!empty($teklif['hazirlayanid'])) {
        $stmt = $db->prepare("SELECT mailposta, adsoyad FROM yonetici WHERE yonetici_id = ?");
        $stmt->bind_param('i', $teklif['hazirlayanid']);
        $stmt->execute();
        $result = $stmt->get_result();
        $personel = $result->fetch_assoc();
        $stmt->close();
        
        if ($personel) {
            $satisciEmail = $personel['mailposta'];
            $satisciAd = $personel['adsoyad'];
        }
    }

    // 1) Satışçıya mail gönder
    if (!empty($satisciEmail)) {
        $satisciSubject = "Teklif Onaylandı - #{$teklif['teklifkodu']}";
        $satisciBody = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: white; padding: 20px; border-radius: 8px 8px 0 0; }
                .content { background: #f9fafb; padding: 20px; border-radius: 0 0 8px 8px; }
                .button { display: inline-block; background: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin-top: 15px; }
                .info { background: white; padding: 15px; border-left: 4px solid #2563eb; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>✅ Teklif Onaylandı!</h2>
                </div>
                <div class='content'>
                    <p>Merhaba <strong>{$satisciAd}</strong>,</p>
                    <p>Müşteriniz teklifinizi onayladı!</p>
                    
                    <div class='info'>
                        <strong>Teklif Kodu:</strong> {$teklif['teklifkodu']}<br>
                        <strong>Müşteri:</strong> {$customerName}<br>
                        <strong>Onay Tarihi:</strong> " . date('d.m.Y H:i') . "
                    </div>
                    
                    <p>Teklif detaylarını görmek için:</p>
                    <a href='{$baseUrl}/offer_detail.php?te={$teklifId}&sta=Onaylandı' class='button'>Teklifi Görüntüle</a>
                    
                    <p style='margin-top: 20px; font-size: 12px; color: #6b7280;'>
                        Bu otomatik bir bildirimdir. Lütfen yanıtlamayın.
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";

        $attachments = [];
        if ($pdfPath && file_exists($pdfPath)) {
            $attachments[] = [
                'path' => $pdfPath,
                'name' => "Teklif_{$teklif['teklifkodu']}.pdf"
            ];
        }

        $mailService->sendMail(
            $satisciEmail,
            $satisciAd,
            $satisciSubject,
            $satisciBody,
            'Gemas Teklif Sistemi',
            null,
            $attachments
        );

        $logger->log("Satışçıya mail gönderildi → {$satisciEmail}");
    }

    // 2) Müşteriye onay maili gönder
    if (!empty($customerEmail)) {
        $musteriSubject = "Teklifiniz Onaylandı - #{$teklif['teklifkodu']}";
        $musteriBody = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 20px; border-radius: 8px 8px 0 0; }
                .content { background: #f9fafb; padding: 20px; border-radius: 0 0 8px 8px; }
                .info { background: white; padding: 15px; border-left: 4px solid #10b981; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>✅ Onayınız Alındı!</h2>
                </div>
                <div class='content'>
                    <p>Sayın <strong>{$customerName}</strong>,</p>
                    <p>Teklifimizi onayladığınız için teşekkür ederiz.</p>
                    
                    <div class='info'>
                        <strong>Teklif Kodu:</strong> {$teklif['teklifkodu']}<br>
                        <strong>Onay Tarihi:</strong> " . date('d.m.Y H:i') . "
                    </div>
                    
                    <p>Teklif detaylarınız ekte PDF olarak gönderilmiştir.</p>
                    <p>Satış ekibimiz en kısa sürede sizinle iletişime geçecektir.</p>
                    
                    <p style='margin-top: 20px;'>
                        <strong>GEMAS</strong><br>
                        gemas.com.tr
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";

        $attachments = [];
        if ($pdfPath && file_exists($pdfPath)) {
            $attachments[] = [
                'path' => $pdfPath,
                'name' => "Teklif_{$teklif['teklifkodu']}.pdf"
            ];
        }

        $mailService->sendMail(
            $customerEmail,
            $customerName,
            $musteriSubject,
            $musteriBody,
            'GEMAS',
            null,
            $attachments
        );

        $logger->log("Müşteriye mail gönderildi → {$customerEmail}");
    }

    // PDF dosyasını sil (opsiyonel - mail gönderildikten sonra)
    // if ($pdfPath && file_exists($pdfPath)) {
    //     unlink($pdfPath);
    // }

    echo json_encode([
        'success' => true,
        'message' => 'Teklif başarıyla onaylandı ve mailler gönderildi',
        'teklif_id' => $teklifId,
        'yeni_durum' => $yeniDurum
    ]);

} catch (Exception $e) {
    $logger->log("Hata: " . $e->getMessage(), "ERROR");
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
