<?php
// offer_detail.php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once "fonk.php";
include_once "include/url.php";

if (!isset($db) || !$db) {
    die("Veritabanı bağlantısı başarısız.");
}
// services/RevisionService.php içindeki RevisionService sınıfını yükle
require_once __DIR__ . '/services/OrderProcessService.php';
require_once __DIR__ . '/services/RevisionService.php';
require_once __DIR__ . '/services/LoggerService.php';
require_once __DIR__ . '/services/MailService.php';
require_once __DIR__ . '/services/PdfService.php';

use Services\RevisionService;
use Services\OrderProcessService;

// RevisionService örneği
$processService  = new OrderProcessService($db);
$revisionService = new RevisionService($db, $processService);
function sanitizeInput(string $data): string
{
    return htmlspecialchars(addslashes(trim($data)));
}
function fetchSingle($db, string $query, string $errorMessage, array $params = [], string $types = '')
{
    $stmt = mysqli_prepare($db, $query);
    if (!$stmt) {
        error_log("$errorMessage: " . mysqli_error($db));
        die("Bir hata oluştu, lütfen daha sonra tekrar deneyiniz.");
    }
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    if (!mysqli_stmt_execute($stmt)) {
        error_log("$errorMessage: " . mysqli_stmt_error($stmt));
        die("Bir hata oluştu, lütfen daha sonra tekrar deneyiniz.");
    }
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_array($result) : null;
    mysqli_stmt_close($stmt);
    return $row;
}
function fetchMultiple($db, string $query, string $errorMessage, array $params = [], string $types = '')
{
    $stmt = mysqli_prepare($db, $query);
    if (!$stmt) {
        error_log("$errorMessage: " . mysqli_error($db));
        die("Bir hata oluştu, lütfen daha sonra tekrar deneyiniz.");
    }
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    if (!mysqli_stmt_execute($stmt)) {
        error_log("$errorMessage: " . mysqli_stmt_error($stmt));
        die("Bir hata oluştu, lütfen daha sonra tekrar deneyiniz.");
    }
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    if ($result) {
        while ($row = mysqli_fetch_array($result)) {
            $rows[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
    return $rows;
}
$sirketim = (isset($sirketim) && is_array($sirketim)) ? $sirketim : [];
$gelenPersonelId = isset($_SESSION['yonetici_id']) ? (int)$_SESSION['yonetici_id'] : 0;
$teklifId     = isset($_GET['te']) ? (int)$_GET['te'] : 0;
$siparisStatu = isset($_GET['sta']) ? sanitizeInput($_GET['sta']) : '';
// $pageHeading değişkenini henüz $isForeignCustomer belirlenene kadar tutmayalım, sayfada dinamik kullanacağız
$onayTooltip  = ($siparisStatu === 'Sipariş') ? 'Siparişi onaylamak için tıklayın.' : 'Teklifi onaylamak için tıklayın.';
$redTooltip   = ($siparisStatu === 'Sipariş') ? 'Siparişi reddetmek için tıklayın.' : 'Teklifi reddetmek için tıklayın.';

if (empty($teklifId)) {
    error_log("Teklif ID bulunamadı.");
    die("Teklif bilgisi eksik.");
}


if (empty($gelenPersonelId)) {
    error_log("Oturumda yönetici ID bulunamadı.");
}

$teklifBilgi = fetchSingle(
    $db,
    "SELECT * FROM ogteklif2 WHERE id = ?",
    "Teklif sorgusu başarısız.",
    [$teklifId],
    'i'
);
if (!$teklifBilgi) {
    error_log("Teklif bulunamadı: ID = $teklifId");
    die("Teklif bulunamadı.");
}
$sirketArp = isset($teklifBilgi["sirket_arp_code"]) ? trim($teklifBilgi["sirket_arp_code"]) : '';

// Müşteri email adresini URL parametresinden al (mail gönderimi için kullanılacak)
$customerEmail = isset($_GET['email']) ? trim($_GET['email']) : '';
$customerName = isset($_GET['name']) ? trim($_GET['name']) : '';

// Eğer URL'de yoksa, teklif bilgisinden almayı dene
if (empty($customerEmail) && !empty($teklifBilgi['musteriadi'])) {
    $customerName = trim($teklifBilgi['musteriadi']);
}

// Müşterinin yurtdışı olup olmadığını kontrol et
$isForeignCustomer = false;
if (!empty($sirketArp)) {
    $musteriBilgi = fetchSingle(
        $db,
        "SELECT trading_grp FROM sirket WHERE s_arp_code = ?",
        "Müşteri sorgusu başarısız.",
        [ $sirketArp ],
        's'
    );
    if ($musteriBilgi) {
        $tradingGrp = strtolower($musteriBilgi['trading_grp'] ?? '');
        $isForeignCustomer = (strpos($tradingGrp, 'yd') !== false);
    }
}
// URL parametresi ile dil zorlama
// PDF oluşturma modu kontrolü
$isPdfMode = isset($_GET['pdf']) && $_GET['pdf'] == '1';
if (isset($_GET['lang']) && $_GET['lang'] === 'en') {
    $isForeignCustomer = true;
}

// Tahsilat planı görüntü bilgisi
$payPlanDisplay = '-';
// env() helper fonksiyonu yoksa $_ENV kullan
$firmNr = isset($_ENV['GEMPA_FIRM_NR']) ? (int)$_ENV['GEMPA_FIRM_NR'] : (isset($_ENV['FIRM_NR']) ? (int)$_ENV['FIRM_NR'] : 0);
if (!empty($teklifBilgi['paydefref'])) {
    $planRow = fetchSingle(
        $db,
        "SELECT code, definition FROM pay_plans WHERE logicalref = ? AND firmnr = ? LIMIT 1",
        "Ödeme planı sorgusu başarısız.",
        [ (int)$teklifBilgi['paydefref'], $firmNr ],
        'ii'
    );
    if ($planRow) {
        $payPlanDisplay = trim(
            ($planRow['code'] ?? '') . ' - ' . ($planRow['definition'] ?? '')
        );
    }
} elseif (!empty($sirketArp)) {
    $planRow = fetchSingle(
        $db,
        "SELECT payplan_code, payplan_def FROM sirket WHERE s_arp_code = ?",
        "Şirket ödeme planı sorgusu başarısız.",
        [ $sirketArp ],
        's'
    );
    if ($planRow) {
        $plan = trim(
            ($planRow['payplan_code'] ?? '') . ' - ' . ($planRow['payplan_def'] ?? '')
        );
        if ($plan !== '') {
            $payPlanDisplay = $plan;
        }
    }
}

// Teklif geçerlilik tarihi okunabilir olsun
$gecerlilikDisplay = '-';
if (!empty($teklifBilgi['teklifgecerlilik'])) {
    $ts = strtotime(str_replace('Saat', '', $teklifBilgi['teklifgecerlilik']));
    if ($ts !== false) {
        $gecerlilikDisplay = date('d.m.Y H:i', $ts);
    } else {
        $gecerlilikDisplay = $teklifBilgi['teklifgecerlilik'];
    }
}

$canRevise = $revisionService->canUserRevise($teklifId, $gelenPersonelId);
$currentDurum = $teklifBilgi["durum"];
$isEditable = in_array($currentDurum, [
    'Teklif Oluşturuldu / Gönderilecek',
    'Teklif Gönderildi / Onay Bekleniyor',
    'Yönetici Onayladı / Gönderilecek',
    'Teklife Revize Talep Edildi / İnceleme Bekliyor',
    'Teklif Revize Edildi / Onay Bekleniyor'
]) && ($canRevise || empty($gelenPersonelId));

// $dbManager kontrolü - fonk.php'de tanımlı olmalı
if (!isset($dbManager)) {
    // Eğer $dbManager yoksa, config'den oluştur
    if (!class_exists('Proje\\DatabaseManager')) {
        require_once __DIR__ . '/classes/DatabaseManager.php';
    }
    $config = require __DIR__ . '/config/config.php';
    $dbManager = new \Proje\DatabaseManager($config['db']);
}

$prep = $dbManager->resolvePreparer($teklifBilgi["hazirlayanid"] ?? "");
$personelProfil = ["adsoyad" => $prep["name"] ?? '', "eposta" => $prep["email"] ?? ''];
$hazirlayanKaynak = $prep["source"] ?? 'Bilinmiyor';

$contactMail  = 'bilgi@gemas.com';
$contactPhone = '+90 242 606 06 46';
if ($hazirlayanKaynak === 'Bayi') {
    $dealerId = (int)preg_replace('/\D+/', '', $teklifBilgi['hazirlayanid'] ?? '');
    if ($dealerId > 0) {
        $dealerRow = $dbManager->getB2bUserById($dealerId);
        if ($dealerRow) {
            $contactMail = $dealerRow['email'] ?? $contactMail;
            $cRow = $dbManager->getCompanyInfoById((int)($dealerRow['company_id'] ?? 0));
            if ($cRow) {
                $contactPhone = $cRow['s_telefonu'] ?? ($cRow['s_telefonu2'] ?? $contactPhone);
            }
        }
    }
}

$genelAyar = fetchSingle(
    $db,
    "SELECT * FROM ayarlar",
    "Genel ayar sorgusu başarısız."
);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['durum'])) {
    $seciliDurum = $_POST['durum'];
    $notlar      = trim($_POST['notlar'] ?? '');

    switch ($seciliDurum) {
        case 'Onayla':
        case 'Reddet':
            $yeniDurum = $seciliDurum === 'Onayla'
                ? 'Sipariş Onaylandı / Logoya Aktarım Bekliyor'
                : 'Teklif Reddedildi';
            if ($revisionService->changeStatus(
                $teklifId,
                $currentDurum,
                $yeniDurum,
                $gelenPersonelId,
                $notlar,
                $teklifBilgi['sirket_arp_code']
            )) {
                // Onay durumunda PDF oluştur ve mail gönder
                if ($seciliDurum === 'Onayla') {
                    try {
                        // Logger başlat
                        $logger = new LoggerService(__DIR__ . '/logs/offer_approval.log');
                        $logger->log("Onay işlemi - PDF ve mail gönderimi başlıyor → Teklif ID: {$teklifId}");
                        
                        // PDF oluştur (logo base64 olarak gömülü)
                        $pdfService = new PdfService($logger);
                        $pdfPath = $pdfService->createOfferPdf($teklifId, $db);
                        
                        if ($pdfPath && file_exists($pdfPath)) {
                            $logger->log("PDF oluşturuldu → {$pdfPath}");
                            
                            // Mail ayarlarını al
                            $mailHost = getenv('MAIL_HOST') ?: 'mail.gemas.com.tr';
                            $mailPort = getenv('MAIL_PORT') ?: 465;
                            $mailSecure = getenv('MAIL_SECURE') ?: 'ssl';
                            $mailUsername = getenv('MAIL_USERNAME') ?: 'satis@gemas.com.tr';
                            $mailPassword = getenv('MAIL_PASSWORD') ?: 'Halil12621262.';
                            
                            $mailService = new MailService($mailHost, $mailPort, $mailSecure, $mailUsername, $mailPassword, $logger);
                            
                            // Müşteriye mail gönder
                            if (!empty($customerEmail)) {
                                // Müşteri ülke bilgisini al (İngilizce mail için)
                                // Çoklu kriter ile yurtdışı müşteri tespiti
                                $isForeign = false;
                                $customerData = null;
                                
                                if (!empty($teklifBilgi['sirket_arp_code'])) {
                                    $stmt = $db->prepare("SELECT ulke, s_country_code, is_export, specode FROM sirket WHERE s_arp_code = ?");
                                    $stmt->bind_param("s", $teklifBilgi['sirket_arp_code']);
                                    $stmt->execute();
                                    $customerData = $stmt->get_result()->fetch_assoc();
                                }
                                
                                if ($customerData) {
                                    // 1. is_export flag kontrolü
                                    if (isset($customerData['is_export']) && $customerData['is_export'] == 1) {
                                        $isForeign = true;
                                    }
                                    
                                    // 2. SPECODE kontrolü
                                    if (!$isForeign && !empty($customerData['specode'])) {
                                        $specode = $customerData['specode'];
                                        if (stripos($specode, 'İhracat') !== false || stripos($specode, 'Ihracat') !== false || stripos($specode, 'EXPORT') !== false) {
                                            $isForeign = true;
                                        }
                                    }
                                    
                                    // 3. Ülke kodu kontrolü
                                    if (!$isForeign && !empty($customerData['s_country_code'])) {
                                        $countryCode = strtoupper(trim($customerData['s_country_code']));
                                        if ($countryCode !== 'TR' && $countryCode !== 'TUR' && $countryCode !== 'TURKEY') {
                                            $isForeign = true;
                                        }
                                    }
                                    
                                    // 4. Ülke adı kontrolü
                                    if (!$isForeign && !empty($customerData['ulke'])) {
                                        $ulke = strtoupper(trim($customerData['ulke']));
                                        if ($ulke !== 'TÜRKİYE' && $ulke !== 'TURKIYE' && $ulke !== 'TURKEY') {
                                            $isForeign = true;
                                        }
                                    }
                                }
                                
                                // Mail içeriği - dile göre
                                if ($isForeign) {
                                    $musteriSubject = "Quotation Approved – Ref. No: {$teklifBilgi['teklifkodu']}";
                                    $musteriBody = "
                                    <html>
                                    <head>
                                        <style>
                                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                                            .header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 20px; border-radius: 8px 8px 0 0; }
                                            .content { background: #f9fafb; padding: 20px; border-radius: 0 0 8px 8px; }
                                            .info { background: white; padding: 15px; border-left: 4px solid #10b981; margin: 15px 0; }
                                            .footer { margin-top: 20px; padding-top: 15px; border-top: 1px solid #e5e7eb; font-size: 12px; color: #6b7280; }
                                        </style>
                                    </head>
                                    <body>
                                        <div class='container'>
                                            <div class='header'>
                                                <h2>✅ Quotation Approved</h2>
                                            </div>
                                            <div class='content'>
                                                <p>Dear <strong>{$customerName}</strong>,</p>
                                                <p>Thank you for approving our quotation.</p>
                                                
                                                <div class='info'>
                                                    <strong>Reference No:</strong> {$teklifBilgi['teklifkodu']}<br>
                                                    <strong>Approval Date:</strong> " . date('d.m.Y H:i') . "
                                                </div>
                                                
                                                <p>Your quotation details have been sent as a PDF attachment.</p>
                                                <p>Our sales team will contact you shortly to proceed with your order.</p>
                                                
                                                <div class='footer'>
                                                    <p>This email has been sent automatically as part of our quotation management process.<br>
                                                    If you have any questions or require assistance, please contact us at <a href='mailto:satis@gemas.com.tr'>satis@gemas.com.tr</a>.</p>
                                                    <p><strong>Best regards,</strong><br>
                                                    Gemas Sales Team<br>
                                                    <a href='https://www.gemas.com.tr'>www.gemas.com.tr</a></p>
                                                </div>
                                            </div>
                                        </div>
                                    </body>
                                    </html>
                                    ";
                                } else {
                                    $musteriSubject = "Teklifiniz Onaylandı - #{$teklifBilgi['teklifkodu']}";
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
                                                    <strong>Teklif Kodu:</strong> {$teklifBilgi['teklifkodu']}<br>
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
                                }
                                
                                $attachments = [
                                    [
                                        'path' => $pdfPath,
                                        'name' => "Teklif_{$teklifBilgi['teklifkodu']}.pdf"
                                    ]
                                ];
                                
                                $mailService->sendMail(
                                    $customerEmail,
                                    $customerName,
                                    $musteriSubject,
                                    $musteriBody,
                                    'GEMAS',
                                    null,
                                    $attachments
                                );
                                
                                $logger->log("Müşteriye PDF ekli mail gönderildi → {$customerEmail}");
                            } else {
                                $logger->log("Müşteri email adresi bulunamadı, mail gönderilemedi", "WARNING");
                            }
                        } else {
                            $logger->log("PDF oluşturulamadı", "ERROR");
                        }
                    } catch (Exception $e) {
                        error_log("PDF/Mail hatası: " . $e->getMessage());
                    }
                }
                
                header("Location: offer_detail.php?te={$teklifId}&sta={$siparisStatu}");
                exit;
            } else {
                echo '<div class="alert alert-danger">Durum güncelleme başarısız.</div>';
            }
            break;

        case 'Revize Et':
            if (! $canRevise) {
                echo '<div class="alert alert-danger">Revize hakkınız kalmamıştır.</div>';
            } else {
                $yeniDurum = 'Teklife Revize Talep Edildi / İnceleme Bekliyor';
                if ($revisionService->changeStatus(
                    $teklifId,
                    $currentDurum,
                    $yeniDurum,
                    $gelenPersonelId,
                    $notlar,
                    $teklifBilgi['sirket_arp_code']
                )) {
                    header("Location: offer_detail.php?te={$teklifId}&sta={$siparisStatu}");
                    exit;
                } else {
                    echo '<div class="alert alert-danger">Revize talebi sırasında hata oluştu.</div>';
                }
            }
            break;
    }
}



// Döviz sembolü fonksiyonu
function getCurrencySymbol($cur)
{
    if ($cur === 'TL') return '₺';
    if ($cur === 'USD') return '$';
    if ($cur === 'EUR') return '€';
    return '';
}

// Tüm satırları (ürün ve indirim) çek
$satirlar = [];
$stmtSatir = mysqli_prepare($db, "SELECT * FROM ogteklifurun2 WHERE teklifid = ? ORDER BY id ASC");
if ($stmtSatir) {
    mysqli_stmt_bind_param($stmtSatir, 'i', $teklifId);
    mysqli_stmt_execute($stmtSatir);
    $res = mysqli_stmt_get_result($stmtSatir);
    while ($row = $res ? mysqli_fetch_assoc($res) : null) {
        if (!$row) break;
        if (empty(trim($row['birim'] ?? ''))) {
            $uRow = fetchSingle(
                $db,
                "SELECT olcubirimi FROM urunler WHERE stokkodu = ? LIMIT 1",
                "Ürün birim sorgusu başarısız.",
                [ $row['kod'] ],
                's'
            );
            if ($uRow) {
                $row['birim'] = $uRow['olcubirimi'];
            }
        }
        $satirlar[] = $row;
    }
    mysqli_stmt_close($stmtSatir);
}

// Sınıflandırma
$urunler = [];
$urunAltIndirimleri = [];
$genelIndirimler = [];

// Uzak veritabanı bağlantısı (Sadece 1 kez bağlan)
$translationDb = null;
$translationsMap = []; // Stok Kodu -> İngilizce İsim eşleşmesi

if ($isForeignCustomer) {
    // 1. Stok kodlarını topla
    $targetCodes = [];
    foreach ($satirlar as $r) {
        if ((int)$r['transaction_type'] === 0 && !empty($r['kod'])) {
            $targetCodes[] = trim($r['kod']);
        }
    }
    $targetCodes = array_unique($targetCodes);

    if (!empty($targetCodes)) {
        try {
            $hostname = "89.43.31.214";
            $username = "gemas_mehmet";
            $password = "2261686Me!";
            $dbname = "gemas_pool_technology";
            $port = 3306;
            $translationDb = new mysqli($hostname, $username, $password, $dbname, $port);
            
            if ($translationDb->connect_error) {
                error_log("Uzak DB Bağlantı Hatası: " . $translationDb->connect_error);
            } else {
                $translationDb->set_charset("utf8");
                
                // 2. Batch (Toplu) Sorgu Hazırla
                // WHERE UPPER(TRIM(m.stok_kodu)) IN (?, ?, ...) yapısı
                $placeholders = implode(',', array_fill(0, count($targetCodes), '?'));
                $types = str_repeat('s', count($targetCodes));
                
                $sql = "
                    SELECT m.stok_kodu, mt.ad, mt.name, mt.title, mt.baslik, mt.urun_adi, mt.malzeme_adi, mt.aciklama
                    FROM malzeme m
                    INNER JOIN malzeme_translations mt ON mt.malzeme_id = m.id
                    WHERE mt.locale = 'en' AND UPPER(TRIM(m.stok_kodu)) IN (
                        SELECT UPPER(TRIM(check_code)) FROM (VALUES " . implode(',', array_fill(0, count($targetCodes), 'ROW(?)')) . ") AS t(check_code)
                    )
                ";
                
                // MySQL sürümü eski olabilir, VALUES row constructor desteklemeyebilir.
                // Klasik IN (?) yöntemi daha güvenli:
                $sql = "
                    SELECT m.stok_kodu, mt.ad, mt.name, mt.title, mt.baslik, mt.urun_adi, mt.malzeme_adi, mt.aciklama
                    FROM malzeme m
                    INNER JOIN malzeme_translations mt ON mt.malzeme_id = m.id
                    WHERE mt.locale = 'en' AND m.stok_kodu IN ($placeholders)
                ";
                
                // Not: Stok kodu eşleşmesinde büyük/küçük harf duyarlılığı veya boşluklar sorun olabilir.
                // Basit IN kullanımı genellikle case-insensitive'dir (tablo collation'ına bağlı).
                
                $stmt = $translationDb->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param($types, ...$targetCodes);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    while ($transRow = $result->fetch_assoc()) {
                        $code = trim($transRow['stok_kodu']);
                        
                        $englishName = '';
                        $nameColumns = ['ad', 'name', 'title', 'baslik', 'urun_adi', 'malzeme_adi', 'aciklama'];
                        foreach ($nameColumns as $col) {
                            if (!empty($transRow[$col])) {
                                $englishName = trim($transRow[$col]);
                                break;
                            }
                        }
                        
                        if (!empty($englishName)) {
                            // Map'e ekle (Büyük harf anahtar ile daha güvenli olabilir ama direkt code kullanıyorum)
                            $translationsMap[$code] = $englishName;
                        }
                    }
                    $stmt->close();
                }
                $translationDb->close();
            }
        } catch (Exception $e) {
            error_log("Uzak DB Batch Sorgu Hatası: " . $e->getMessage());
        }
    }
}

foreach ($satirlar as $row) {
    if ((int)$row['transaction_type'] === 0) {
        // Yurtdışı müşteri için ürün ismini İngilizce'ye çevir (Cache'den)
        if ($isForeignCustomer && !empty($row['kod'])) {
            $codeKey = trim($row['kod']);
            if (isset($translationsMap[$codeKey])) {
                $row['adi'] = $translationsMap[$codeKey];
            }
        }
        $urunler[] = $row;
    } elseif ((int)$row['transaction_type'] === 2 && !empty($row['parent_internal_reference'])) {
        $urunAltIndirimleri[$row['parent_internal_reference']][] = $row;
    } elseif ((int)$row['transaction_type'] === 2 && empty($row['parent_internal_reference'])) {
        $genelIndirimler[] = $row;
    }
}

$sira = 1;

// Çeviri fonksiyonu
function t($key, $isForeign = false) {
    $translations = [
        'tr' => [
            'offer_details' => 'Teklif Detayları',
            'company_product_info' => 'Şirket ve Ürün Bilgileri',
            'company_name' => 'Şirket Adı',
            'prepared_by' => 'Hazırlayan',
            'customer_record' => 'Cari Kaydı',
            'email' => 'E-Posta Adresi',
            'offer_validity_date' => 'Teklif Geçerlilik Tarihi',
            'phone' => 'Telefon',
            'offer_process_date' => 'Teklif İşlem Tarihi',
            'offer_code' => 'Teklif Kodu',
            'delivery_place' => 'Teslim Yeri',
            'payment_plan' => 'Ödeme Planı',
            'extra_info_notes' => 'Ekstra Bilgi / Notlar',
            'product_service' => 'MAL/HİZMET',
            'quantity' => 'MİKTAR',
            'unit' => 'BİRİM',
            'list_price' => 'LİSTE FİYATI',
            'discount' => 'İSKONTO (%)',
            'net_price' => 'İSKONTOLU BİRİM FİYAT',
            'total' => 'TOPLAM',
            'vat_rate' => 'KDV',
            'vat_unit_price' => 'KDV\'Lİ BİRİM FİYAT',
            'grand_total' => 'GENEL TOPLAM',
            'offer_summary' => 'Teklif Özeti & Döviz Dönüşümleri',
            'main_unit' => 'Ana Birim',
            'vat_included' => 'KDV Dahil',
            'currency' => 'Para Birimi',
            'net' => 'Net',
            'vat' => 'KDV (20%)',
            'general' => 'Genel',
            'terms' => 'Teklif Şartları',
            'yes' => 'EVET',
            'no' => 'HAYIR',
            'action_options' => 'İşlem Seçenekleri',
            'current_status' => 'Mevcut Durum',
            'approve' => 'Onayla',
            'reject' => 'Reddet',
            'revise' => 'Revize Et',
            'revise_process' => 'Revize Süreci',
            'revise_note' => 'Revize Notunuz',
            'revise_placeholder' => 'Revize işlemi ile ilgili not ekleyin',
            'revise_update' => 'Revize Güncelle',
            'status_history' => 'Durum Geçmişi',
            'date' => 'Tarih',
            'old_status' => 'Eski Durum',
            'new_status' => 'Yeni Durum',
            'changed_by' => 'Değişikliği Yapan',
            'notes' => 'Notlar',
            'no_change_allowed' => 'Bu teklif üzerinde değişiklik yapılamaz.',
            'revise_right_exceeded' => 'Revize Hakkınız Doldu',
            'revise_tooltip' => 'Revize etmek için tıklayın.',
            'revise_complete_tooltip' => 'Revize işlemini tamamlamak için tıklayın.',
            'request_revise' => 'Revize',
        ],
        'en' => [
            'offer_details' => 'Offer Details',
            'company_product_info' => 'Company and Product Information',
            'company_name' => 'Company Name',
            'prepared_by' => 'Prepared By',
            'customer_record' => 'Customer Record',
            'email' => 'Email Address',
            'offer_validity_date' => 'Offer Validity Date',
            'phone' => 'Phone',
            'offer_process_date' => 'Offer Process Date',
            'offer_code' => 'Offer Code',
            'delivery_place' => 'Delivery Place',
            'payment_plan' => 'Payment Plan',
            'extra_info_notes' => 'Extra Information / Notes',
            'product_service' => 'PRODUCT/SERVICE',
            'quantity' => 'QUANTITY',
            'unit' => 'UNIT',
            'list_price' => 'LIST PRICE',
            'discount' => 'DISCOUNT (%)',
            'net_price' => 'NET UNIT PRICE',
            'total' => 'TOTAL',
            'vat_rate' => 'VAT',
            'vat_unit_price' => 'VAT UNIT PRICE',
            'grand_total' => 'GRAND TOTAL',
            'offer_summary' => 'Offer Summary & Currency Conversions',
            'main_unit' => 'Main Unit',
            'vat_included' => 'VAT Included',
            'currency' => 'Currency',
            'net' => 'Net',
            'vat' => 'VAT (20%)',
            'general' => 'General',
            'terms' => 'Terms & Conditions',
            'yes' => 'YES',
            'no' => 'NO',
            'action_options' => 'Action Options',
            'current_status' => 'Current Status',
            'approve' => 'Approve',
            'reject' => 'Reject',
            'revise' => 'Revise',
            'revise_process' => 'Revision Process',
            'revise_note' => 'Revision Note',
            'revise_placeholder' => 'Add a note about the revision',
            'revise_update' => 'Update Revision',
            'status_history' => 'Status History',
            'date' => 'Date',
            'old_status' => 'Old Status',
            'new_status' => 'New Status',
            'changed_by' => 'Changed By',
            'notes' => 'Notes',
            'no_change_allowed' => 'Changes continue as requested.',
            'revise_right_exceeded' => 'Revise Right Exceeded',
            'revise_tooltip' => 'Click to revise.',
            'revise_complete_tooltip' => 'Click to complete the revision.',
            'request_revise' => 'Request Revision',
        ]
    ];
    $lang = $isForeign ? 'en' : 'tr';
    return $translations[$lang][$key] ?? $key;
}
?>

<!doctype html>
<html lang="tr">

<head>
    <meta charset="utf-8" />
    <title><?= htmlspecialchars($genelAyar["title"] ?? "Teklif"); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?= htmlspecialchars($genelAyar["description"] ?? ""); ?>" name="description" />
    <meta content="<?= htmlspecialchars($genelAyar["keywords"] ?? ""); ?>" name="keywords" />
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link href="assets/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/app.min.css" id="app-style" rel="stylesheet" type="text/css" />
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Modern Invoice Styles */
        * {
            font-family: 'Inter', sans-serif;
        }
        
        /* Remove all top spacing */
        body {
            margin: 0 !important;
            padding: 0 !important;
        }
        
        #layout-wrapper,
        .main-content,
        .page-content,
        .container-fluid {
            margin-top: 0 !important;
            padding-top: 0 !important;
            min-height: auto !important; /* Prevent full height focus */
        }
        
        .page-content {
            padding-bottom: 1rem !important; /* Reduce bottom padding */
        }
        
        /* Invoice Header */
        .invoice-header {
            max-width: 1200px;
            margin: 1rem auto 0.5rem;
            padding: 0.5rem 2rem 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1px solid #e5e7eb;
            position: relative; /* Enable absolute positioning for children */
        }
        
        .header-left img {
            width: 80px;
            height: auto;
            margin-bottom: 0.5rem;
        }
        
        .header-left {
            color: #2563eb;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .header-right {
            text-align: right;
        }
        
        .header-stripes {
            display: flex;
            gap: 4px;
            justify-content: flex-end;
            margin-bottom: 0.5rem;
        }
        
        .header-stripes div {
            height: 4px;
            background: #2563eb;
        }
        
        .header-stripes div:nth-child(1) { width: 30px; }
        .header-stripes div:nth-child(2) { width: 20px; background: #60a5fa; }
        .header-stripes div:nth-child(3) { width: 15px; background: #93c5fd; }
        .header-stripes div:nth-child(4) { width: 10px; background: #bfdbfe; }
        
        .header-title {
            font-size: 1.75rem;
            font-weight: 600;
            color: #2563eb;
            margin: 0;
            line-height: 1;
        }
        
        .header-meta {
            margin-top: 0.75rem;
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .header-meta div {
            margin-bottom: 0.25rem;
        }
        
        /* SELLER/BUYER Section */
        .parties-section {
            max-width: 1200px;
            margin: 1.5rem auto;
            padding: 0 2rem;
        }
        
        .parties-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 1.5rem;
        }
        
        .party-box h3 {
            font-size: 0.75rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
        }
        
        .party-details {
            font-size: 0.75rem;
            color: #4b5563;
            line-height: 1.6;
        }
        
        .party-details div {
            margin-bottom: 0.25rem;
        }
        
        .party-details .company-name {
            font-weight: 600;
            color: #1f2937;
        }
        
        .separator-line {
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 1.5rem;
        }
        
        /* Modern Table */
        .modern-table {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .modern-table table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }
        
        .modern-table thead tr {
            background: #2563eb;
            color: white;
        }
        
        .modern-table thead th {
            padding: 0.75rem 0.5rem;
            font-weight: 600;
            font-size: 0.75rem;
            text-align: left;
        }
        
        .modern-table tbody tr:nth-child(even) {
            background: #f9fafb;
        }
        
        .modern-table tbody td {
            padding: 0.5rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        /* Toolbar */
        .modern-toolbar {
            max-width: 900px;
            margin: 1rem auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .toolbar-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            margin-right: 0.5rem;
        }
        
        .btn-print {
            background: #3b82f6;
            color: white;
        }
        
        .btn-pdf {
            background: #f59e0b;
            color: white;
        }
        a {
            text-decoration: none;
        }

        .page-topbar,
        .navbar {
            display: none !important;
        }

        .card {
            margin-bottom: 1rem;
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .table-responsive {
            margin-top: 1rem;
        }

        /* Logo ve QR Kod düzenlemeleri */
        .logo-container img {
            max-width: 20%;
            height: auto;
        }

        .qr-container img {
            max-width: 25%;
            height: auto;
        }

        /* Modern Tab Stili */
        .nav-tabs {
            border-bottom: 2px solid #ddd;
            margin-bottom: 1rem;
        }

        .nav-tabs .nav-link {
            border: none;
            background-color: #f7f7f7;
            color: #555;
            font-weight: 600;
            margin-right: 0.5rem;
            border-radius: 0.5rem 0.5rem 0 0;
            transition: background 0.3s ease;
        }

        .nav-tabs .nav-link:hover {
            background-color: #e9e9e9;
        }

        .nav-tabs .nav-link.active {
            background: linear-gradient(45deg, #007bff, #00c851);
            color: #fff;
        }

        .no-print {
            display: block;
        }

        /* Force small font for terms content including user-generated HTML */
        .terms-content, 
        .terms-content * {
            font-size: 0.75rem !important;
            line-height: 1.4 !important;
        }
        
        /* Smaller Proforma Title */
        .header-title {
            font-size: 1.5rem; /* Was h1 default */
            font-weight: 700;
            color: #2563eb;
            margin: 0 0 0.5rem 0;
            line-height: 1.2;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            /* Header adjustments */
            .invoice-header {
                flex-direction: column;
                padding: 1rem;
                gap: 1rem;
            }
            
            .header-right {
                flex-direction: column !important;
                gap: 1rem !important;
                width: 100%;
            }
            
            .header-left {
                text-align: center;
            }
            
            .header-left img {
                width: 60px;
            }
            
            .header-title {
                font-size: 1.5rem !important;
            }
            
            .header-meta {
                font-size: 0.75rem;
            }
            
            /* QR and buttons stack */
            .header-right > div:last-child {
                flex-direction: row !important;
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .header-right > div:last-child img {
                width: 80px !important;
                height: 80px !important;
            }
            
            .toolbar-btn {
                font-size: 0.75rem;
                padding: 0.4rem 0.8rem;
            }
            
            /* Tables */
            .modern-table,
            .parties-section,
            div[style*="max-width: 900px"] {
                padding: 0 1rem !important;
            }
            
            .modern-table table {
                font-size: 0.75rem !important;
            }
            
            .modern-table thead th,
            .modern-table tbody td {
                padding: 0.5rem 0.25rem !important;
            }
            
            /* Company info table */
            table[style*="border-collapse"] th,
            table[style*="border-collapse"] td {
                display: block;
                width: 100% !important;
                padding: 0.5rem !important;
            }
            
            table[style*="border-collapse"] tr {
                display: block;
                margin-bottom: 1rem;
                border: 1px solid #e5e7eb;
                border-radius: 4px;
            }
            
            /* Summary section */
            .modern-table h1 {
                font-size: 2rem !important;
            }
        }
        
        @media (max-width: 480px) {
            .header-title {
                font-size: 1.25rem !important;
            }
            
            .modern-table table {
                font-size: 0.65rem !important;
            }
            
            .toolbar-btn {
                width: 80px !important;
                font-size: 0.65rem;
            }
        }

        @media print {
            @page {
                size: A4;
                margin: 1.5cm 1cm;
            }
            
            @media print {
                @page {
                    margin: 0.5cm;
                    size: A4 portrait;
                }

                body {
                    background-color: #fff !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                    font-size: 10pt;
                    margin: 0 !important;
                    padding: 0 !important;
                }
                
                /* Hide non-printable elements */
                .no-print, 
                .nav-tabs, 
                .footer, 
                .page-topbar, 
                #durum, 
                .btn,
                .rightbar-overlay,
                .modal,
                .modern-toolbar,
                .vertical-menu,
                .navbar-header,
                .d-print-none {
                    display: none !important;
                }
                
                /* Layout Resets */
                #layout-wrapper,
                .main-content,
                .page-content,
                .container-fluid {
                    margin: 0 !important;
                    padding: 0 !important;
                    width: 100% !important;
                    max-width: 100% !important;
                    min-height: auto !important;
                    box-shadow: none !important;
                }

                /* Header styling - Using Table Layout for better print support */
                .invoice-header {
                    display: table !important;
                    width: 100% !important;
                    border-bottom: 2px solid #2563eb !important;
                    padding-bottom: 10px !important;
                    margin-bottom: 20px !important;
                }
                
                .header-left {
                    display: table-cell !important;
                    vertical-align: top !important;
                    width: 40% !important;
                    text-align: left !important;
                }
                
                .header-right {
                    display: table-cell !important;
                    vertical-align: top !important;
                    width: 60% !important;
                    text-align: right !important;
                }

                /* Override inline flex styles */
                .header-right {
                    flex-direction: row !important;
                    align-items: flex-start !important;
                }
                
                /* Layout for header right components to sit side-by-side */
                .header-right > div {
                    display: inline-block !important;
                    vertical-align: top !important;
                    text-align: right !important;
                }
                
                /* QR Code Positioning - CENTER IN PRINT */
                .header-actions {
                    display: block !important;
                    position: absolute !important;
                    top: 0 !important;
                    left: 50% !important;
                    transform: translateX(-50%) !important;
                    margin: 0 !important;
                }
                
                .header-actions img {
                    width: 80px !important;
                    height: 80px !important;
                }
                
                /* Keep Title & Meta aligned to right */
                .header-right > div:not(.header-actions) {
                    display: block !important; 
                    text-align: right !important;
                    width: 100% !important; 
                }
                
                /* Hide stripes in print since they might look odd without context or take space */
                .header-stripes {
                    display: none !important;
                }
                
                /* Force standard table display to fix fragmented layout */
                table {
                    display: table !important;
                    width: 100% !important;
                    border-collapse: collapse !important;
                }
                
                thead {
                    display: table-header-group !important;
                }
                
                tbody {
                    display: table-row-group !important;
                }
                
                tr {
                    display: table-row !important;
                    page-break-inside: avoid;
                }
                
                
                th, td {
                    display: table-cell !important;
                }


                /* Product table column widths for better print layout */
                .modern-table table th:nth-child(1),
                .modern-table table td:nth-child(1) {
                    width: 3% !important;
                    min-width: 25px !important;
                }
                
                .modern-table table th:nth-child(2),
                .modern-table table td:nth-child(2) {
                    width: 18% !important;
                    min-width: 120px !important;
                }
                
                .modern-table table th:nth-child(3),
                .modern-table table td:nth-child(3) {
                    width: 6% !important;
                    min-width: 40px !important;
                }
                
                .modern-table table th:nth-child(4),
                .modern-table table td:nth-child(4) {
                    width: 6% !important;
                    min-width: 40px !important;
                }
                
                .modern-table table th:nth-child(5),
                .modern-table table td:nth-child(5) {
                    width: 10% !important;
                    min-width: 65px !important;
                }
                
                .modern-table table th:nth-child(6),
                .modern-table table td:nth-child(6) {
                    width: 10% !important;
                    min-width: 70px !important;
                }
                
                .modern-table table th:nth-child(7),
                .modern-table table td:nth-child(7) {
                    width: 13% !important;
                    min-width: 90px !important;
                }
                
                .modern-table table th:nth-child(8),
                .modern-table table td:nth-child(8) {
                    width: 10% !important;
                    min-width: 65px !important;
                }
                
                .modern-table table th:nth-child(9),
                .modern-table table td:nth-child(9) {
                    width: 7% !important;
                    min-width: 45px !important;
                }
                
                .modern-table table th:nth-child(10),
                .modern-table table td:nth-child(10) {
                    width: 12% !important;
                    min-width: 80px !important;
                }

                /* Ensure text wraps in cells */
                .modern-table table td {
                    word-wrap: break-word !important;
                    word-break: break-word !important;
                    white-space: normal !important;
                    overflow: hidden !important;
                    font-size: 8pt !important;
                    padding: 3px 2px !important;
                }
                
                /* Allow headers to wrap if needed */
                .modern-table table th {
                    white-space: normal !important;
                    font-size: 7pt !important;
                    padding: 3px 2px !important;
                    line-height: 1.1 !important;
                    vertical-align: middle !important;
                }

                /* Modern table styling (Both Product List and Summary) */
                .modern-table {
                    margin: 1rem 0 !important;
                    width: 100% !important;
                    display: block !important; /* Wrapper is block */
                }
                
                .modern-table table {
                    width: 100% !important;
                }

                .modern-table thead tr {
                    background: #2563eb !important;
                    color: white !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                
                .modern-table th, 
                .modern-table td {
                    border: 1px solid #ccc !important;
                    padding: 8px !important;
                    vertical-align: middle !important;
                }
                
                /* Specific fix for Summary Table Headers */
                .modern-table th {
                    background-color: #2563eb !important;
                    color: white !important;
                    font-weight: bold !important;
                    white-space: nowrap !important; /* Prevent header wrapping */
                    text-align: center !important;
                }
                
                /* Fix alignment for summary table data */
                .modern-table td {
                    text-align: right !important;
                }
                
                .modern-table td:first-child,
                .modern-table th:first-child {
                    text-align: left !important;
                }
                
                /* Terms Container - Page Break Rules */
                .terms-container {
                    page-break-before: always !important;
                    page-break-inside: avoid !important;
                    break-before: always !important;
                    break-inside: avoid !important;
                    margin-top: 20px !important;
                    display: block !important;
                }
                
                /* Terms text size */
                .terms-content, .terms-content * {
                    font-size: 8pt !important;
                    line-height: 1.2 !important;
                }
                
                /* Optimize Summary Table Row Height */
                .modern-table td {
                    height: auto !important;
                    padding: 4px 8px !important; /* Reduce vertical padding */
                    line-height: 1.2 !important;
                }
                
                /* Ensure currency column doesn't wrap awkwardly */
                .modern-table td:first-child {
                    white-space: nowrap !important;
                    width: 1% !important; /* Shrink to fit content */
                }

                /* Card Resets */
                .card {
                    border: none !important;
                    box-shadow: none !important;
                    margin-bottom: 10px !important;
                    page-break-inside: avoid;
                }
                .card-body { padding: 0 !important; }
                .card-header {
                    background-color: transparent !important;
                    border-bottom: 1px solid #ddd !important;
                    padding: 5px 0 !important;
                    margin-bottom: 5px !important;
                }

                /* Container Widths */
                div[style*="max-width: 1200px"] {
                    max-width: 100% !important;
                    padding: 0 !important;
                    margin: 0.5rem 0 !important;
                }
            }
            .qr-container img { max-width: 80px !important; }

            /* Table Optimization */
            .table {
                width: 100% !important;
                border-collapse: collapse !important;
                margin-bottom: 0 !important;
                font-size: 10px !important;
            }
            .table th, .table td {
                padding: 3px 5px !important;
                border: 1px solid #ccc !important;
            }
            .table-light th { background-color: #f0f0f0 !important; color: #000 !important; }
            
            /* Badge & Colors Reset */
            .badge {
                border: 1px solid #000;
                color: #000 !important;
                background: transparent !important;
                padding: 1px 3px;
                font-weight: normal;
            }
            
            /* Specific Element Sizing */
            .display-4 { font-size: 20px !important; font-weight: bold !important; line-height: 1.2; }
            .mb-4 { margin-bottom: 10px !important; }
            .mt-3, .mt-4 { margin-top: 10px !important; }
            
            /* Hide URL printing */
            a[href]:after { content: none !important; }

            /* Terms & Conditions Specific Fix */
            .terms-content * {
                font-size: 10px !important;
                line-height: 1.2 !important;
                margin-top: 2px !important;
                margin-bottom: 2px !important;
            }
            .terms-content h1, .terms-content h2, .terms-content h3, .terms-content h4, .terms-content h5, .terms-content h6 {
                font-size: 11px !important;
                font-weight: bold !important;
            }
        }
    </style>
</head>

<body data-layout="horizontal" data-topbar="colored">
    <div id="layout-wrapper">
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <!-- Modern Professional Invoice Header -->
                    <div class="invoice-header">
                        <!-- Left Side: Logo and Company Info -->
                        <div class="header-left">
                            <img src="logogemas.png" alt="GEMAS">
                            <div>gemas.com.tr</div>
                            
                            <!-- Company Details -->
                            <div style="margin-top: 1rem; font-size: 0.75rem; color: #4b5563; line-height: 1.8;">
                                <div style="margin-bottom: 0.5rem;">
                                    <strong style="color: #1f2937;"><?= t('company_name', $isForeignCustomer); ?>:</strong><br>
                                    <?php
                                    if (empty($sirketArp)) {
                                        echo htmlspecialchars($teklifBilgi["musteriadi"]);
                                    } else {
                                        $musteriBilgi = fetchSingle(
                                            $db,
                                            "SELECT * FROM sirket WHERE s_arp_code = ?",
                                            "Müşteri sorgusu başarısız.",
                                            [ $sirketArp ],
                                            's'
                                        );
                                        echo htmlspecialchars($musteriBilgi["s_adi"] ?? '');
                                    }
                                    ?>
                                </div>
                                <div style="margin-bottom: 0.5rem;">
                                    <strong style="color: #1f2937;"><?= t('customer_record', $isForeignCustomer); ?>:</strong>
                                    <?= empty($teklifBilgi["sirket_arp_code"]) ? '<span style="color: #dc2626;">' . t('no', $isForeignCustomer) . '</span>' : '<span style="color: #16a34a;">' . t('yes', $isForeignCustomer) . '</span>'; ?>
                                </div>
                                <div style="margin-bottom: 0.5rem;">
                                    <strong style="color: #1f2937;"><?= t('delivery_place', $isForeignCustomer); ?>:</strong><br>
                                    <?= htmlspecialchars($teklifBilgi["teslimyer"]); ?>
                                </div>
                                <div>
                                    <strong style="color: #1f2937;"><?= t('payment_plan', $isForeignCustomer); ?>:</strong><br>
                                    <?= htmlspecialchars($payPlanDisplay); ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Side: Title and Meta -->
                        <div class="header-right" style="display: flex; gap: 2rem; align-items: flex-start;">
                            <div>
                                <!-- Blue Stripes -->
                                <div class="header-stripes">
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                </div>
                                
                                <!-- Title -->
                                <h1 class="header-title">Proforma</h1>
                                
                                <!-- Meta Information -->
                                <div style="margin-top: 1rem; font-size: 0.75rem; color: #4b5563; line-height: 1.8;">
                                    <div style="margin-bottom: 0.5rem;">
                                        <strong style="color: #1f2937;"><?= t('offer_code', $isForeignCustomer); ?>:</strong>
                                        <?= htmlspecialchars($teklifBilgi["teklifkodu"]); ?>
                                    </div>
                                    <div style="margin-bottom: 0.5rem;">
                                        <strong style="color: #1f2937;"><?= t('date', $isForeignCustomer); ?>:</strong>
                                        <?= $isForeignCustomer ? date('F d, Y') : date('d.m.Y'); ?>
                                    </div>
                                    <div style="margin-bottom: 0.5rem;">
                                        <strong style="color: #1f2937;"><?= t('prepared_by', $isForeignCustomer); ?>:</strong>
                                        <?= htmlspecialchars($personelProfil["adsoyad"] ?? ''); ?> 
                                        <small>(<?= htmlspecialchars($hazirlayanKaynak) ?>)</small>
                                    </div>
                                    <div style="margin-bottom: 0.5rem;">
                                        <strong style="color: #1f2937;"><?= t('offer_validity_date', $isForeignCustomer); ?>:</strong>
                                        <?= htmlspecialchars($gecerlilikDisplay); ?>
                                    </div>
                                    <div>
                                        <strong style="color: #1f2937;"><?= t('offer_process_date', $isForeignCustomer); ?>:</strong>
                                        <?= htmlspecialchars($teklifBilgi["tekliftarihi"]); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- QR Code and Buttons -->
                            <div class="header-actions" style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
                                <?php
                                function qrCode($icerik, $width = 130, $height = 130)
                                {
                                    $apiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=%dx%d&data=%s';
                                    return sprintf($apiUrl, $width, $height, urlencode($icerik));
                                }
                                if (!isset($url) || empty($url)) {
                                    $url = 'http://localhost/b2b-gemas-project-main';
                                }
                                $qrKodURL  = qrCode($url . '/offer_detail.php?te=' . $teklifId . '&sta=' . urlencode($siparisStatu), 100, 100);
                                ?>
                                <img src="<?= htmlspecialchars($qrKodURL) ?>" alt="QR Kod" style="width: 100px; height: 100px;">
                                
                                <div class="no-print" style="display: flex; flex-direction: column; gap: 0.5rem;">
                                    <button onclick="window.print()" class="toolbar-btn btn-print" style="width: 100px; margin: 0;">
                                        🖨️ <?= $isForeignCustomer ? 'Print' : 'Yazdır'; ?>
                                    </button>
                                    <button onclick="window.print()" class="toolbar-btn btn-pdf" style="width: 100px; margin: 0;">
                                        📥 PDF
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- İki Tab: Detaylar ve Durum Düzenleme -->
                    <div class="no-print" style="max-width: 1200px; margin: 0 auto; padding: 0 2rem;">
                        <ul class="nav nav-tabs" id="teklifTab" role="tablist" style="border-bottom: 2px solid #e5e7eb;">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="detay-tab" data-bs-toggle="tab" data-bs-target="#detay" type="button" role="tab" aria-controls="detay" aria-selected="true" style="font-weight: 600;"><?= t('offer_details', $isForeignCustomer); ?></button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="durum-tab" data-bs-toggle="tab" data-bs-target="#durum" type="button" role="tab" aria-controls="durum" aria-selected="false" style="font-weight: 600;"><?= t('revise_process', $isForeignCustomer); ?></button>
                            </li>
                        </ul>
                    </div>
                    <div class="tab-content" id="teklifTabContent" style="max-width: 1200px; margin: 0 auto; padding: 0;">
                        <!-- Tab 1: Detaylar -->
                        <div class="tab-pane fade show active" id="detay" role="tabpanel" aria-labelledby="detay-tab">
                            
                            <!-- Extra Notes (if exists) -->
                            <?php if (!empty($teklifBilgi["notes1"])): ?>
                            <div style="max-width: 1200px; margin: 2rem auto; padding: 0 2rem;">
                                <div style="background: #fffbeb; border-left: 4px solid #f59e0b; padding: 1rem; border-radius: 4px;">
                                    <h6 style="font-size: 0.875rem; font-weight: 600; color: #92400e; margin-bottom: 0.5rem;">
                                        <?= t('extra_info_notes', $isForeignCustomer); ?>
                                    </h6>
                                    <div style="font-size: 0.875rem; color: #78350f;">
                                        <?= nl2br(strip_tags($teklifBilgi["notes1"])); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Modern Product Table -->
                                    <div class="modern-table">
                                        <table>
                                        <?php
                                        // İskonto kontrolü: Herhangi bir üründe iskonto varsa sütun gösterilecek
                                        $hasDiscount = false;
                                        foreach ($urunler as $u) {
                                            if ((float)$u['iskonto'] > 0) {
                                                $hasDiscount = true;
                                                break;
                                            }
                                        }
                                        ?>
                                            <thead>
                                                <tr>
                                                    <th style="padding: 0.75rem 1rem; text-align: center; width: 40px;">#</th>
                                                    <th style="padding: 0.75rem 1rem;"><?= t('product_service', $isForeignCustomer); ?></th>
                                                    <th style="padding: 0.75rem 1rem; text-align: center;"><?= t('quantity', $isForeignCustomer); ?></th>
                                                    <th style="padding: 0.75rem 1rem; text-align: center;"><?= t('unit', $isForeignCustomer); ?></th>
                                                    <th style="padding: 0.75rem 1rem; text-align: right;"><?= t('list_price', $isForeignCustomer); ?></th>
                                                    <th style="padding: 0.75rem 1rem; text-align: center;"><?= t('discount', $isForeignCustomer); ?></th>
                                                    <th style="padding: 0.75rem 1rem; text-align: right;"><?= t('net_price', $isForeignCustomer); ?></th>
                                                    <th style="padding: 0.75rem 1rem; text-align: right;"><?= t('total', $isForeignCustomer); ?></th>
                                                    <th style="padding: 0.75rem 1rem; text-align: center;"><?= t('vat_rate', $isForeignCustomer); ?></th>
                                                    <th style="padding: 0.75rem 1rem; text-align: right;"><?= t('vat_unit_price', $isForeignCustomer); ?></th>
                                                    <th style="padding: 0.75rem 1rem; text-align: right;"><?= $isForeignCustomer ? 'TOTAL' : 'TOPLAM'; ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($urunler as $u): ?>
                                                    <?php
                                                    // Ana ürün hesapları
                                                    $liste = (float) $u['liste'];
                                                    $iskonto = (float) $u['iskonto'];
                                                    $miktar = (float) $u['miktar'];
                                                    $netBirim = $liste * (1 - $iskonto / 100);
                                                    $rowTutar = $miktar * $netBirim;
                                                    $currency = $u['doviz'];
                                                    $currencySymbol = getCurrencySymbol($currency);
                                                    ?>
                                                    <tr>
                                                        <td class="fw-bold" style="padding: 4px; text-align: center; vertical-align: middle;"><?= $sira++; ?></td>
                                                        <td style="padding: 4px; vertical-align: middle;">
                                                            <span class="fw-bold"><?= htmlspecialchars((string)($u['kod'] ?? '')) ?></span>
                                                            <br>
                                                            <small><?= htmlspecialchars((string)($u['adi'] ?? '')) ?></small>
                                                        </td>
                                                        <td style="padding: 4px; text-align: center; vertical-align: middle;"><?= rtrim(rtrim(number_format($miktar, 2, ',', '.'), '0'), ',') ?></td>
                                                        <td style="padding: 4px; text-align: center; vertical-align: middle;"><?= htmlspecialchars((string)($u['birim'] ?? '')) ?></td>
                                                        <td style="padding: 4px; text-align: right; vertical-align: middle;"><?= number_format($liste, 2, ',', '.') ?> <?= $currencySymbol ?></td>
                                                        <td style="padding: 2px; text-align: center; vertical-align: middle;">
                                                            <?php 
                                                            $displayDiscounts = [];
                                                            $netFactor = 1.0;
                                                            $rawFormula = $u['iskonto_formulu'] ?? '';
                                                            
                                                            if (!empty($rawFormula)) {
                                                                // Parse "50-10" or similar
                                                                $cleanF = str_replace([' ', '+'], '-', $rawFormula);
                                                                $parts = explode('-', $cleanF);
                                                                foreach($parts as $p) {
                                                                    $val = floatval(str_replace(',', '.', trim($p)));
                                                                    if($val > 0) {
                                                                        $displayDiscounts[] = $val;
                                                                        $netFactor *= (1 - $val/100);
                                                                    }
                                                                }
                                                            }
                                                            
                                                            // Fallback to single discount if no formula or parsing failed
                                                            if (empty($displayDiscounts) && $iskonto > 0) {
                                                                $displayDiscounts[] = $iskonto;
                                                                $netFactor = (1 - $iskonto/100);
                                                            } elseif (empty($displayDiscounts) && $iskonto <= 0) {
                                                                $netFactor = 1.0;
                                                            }
                                                            
                                                            // Recalculate Net Price based on formula
                                                            $netBirim = $liste * $netFactor;
                                                            $rowTutar = $miktar * $netBirim;
                                                            
                                                            if (!empty($displayDiscounts)): 
                                                                foreach ($displayDiscounts as $dVal):
                                                            ?>
                                                                <div style="
                                                                    background-color: #d4edda; 
                                                                    color: #155724;
                                                                    border: 1px solid #c3e6cb;
                                                                    border-radius: 3px;
                                                                    padding: 1px 4px;
                                                                    font-size: 10px;
                                                                    font-weight: 600;
                                                                    display: inline-block;
                                                                    margin: 1px;
                                                                ">
                                                                    %<?= rtrim(rtrim(number_format($dVal, 2, ',', '.'), '0'), ',') ?>
                                                                </div>
                                                            <?php endforeach;
                                                            else: ?>
                                                                <span style="color: #ccc;">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td style="padding: 4px; text-align: right; vertical-align: middle;"><?= number_format($netBirim, 2, ',', '.') ?> <?= $currencySymbol ?></td>
                                                        <td class="fw-bold" style="padding: 4px; text-align: right; vertical-align: middle;"><?= number_format($rowTutar, 2, ',', '.') ?> <?= $currencySymbol ?></td>
                                                        <td style="padding: 4px; text-align: center; vertical-align: middle;">%20</td>
                                                        <td style="padding: 4px; text-align: right; vertical-align: middle;"><?= number_format($netBirim * 1.20, 2, ',', '.') ?> <?= $currencySymbol ?></td>
                                                        <td class="fw-bold" style="padding: 4px; text-align: right; vertical-align: middle;"><?= number_format($rowTutar * 1.20, 2, ',', '.') ?> <?= $currencySymbol ?></td>
                                                    </tr>
                                                    <!-- ALT İNDİRİMLER -->
                                                    <?php if (!empty($urunAltIndirimleri[$u['internal_reference']])): ?>
                                                        <?php
                                                        // zincirleme alt indirimler için başlangıç net fiyatı:
                                                        $parentNet = $netBirim;
                                                        foreach ($urunAltIndirimleri[$u['internal_reference']] as $ind):
                                                            $altIsk = (float)$ind['iskonto'];
                                                            // bir önceki net fiyat üzerinden yeni indirimi uygula:
                                                            $parentNet *= (1 - $altIsk / 100);
                                                            // miktarla çarp, bu satırın tutarı:
                                                            $lineTotal = $miktar * $parentNet;
                                                        ?>
                                                            <tr style="background:#fcf8e3">
                                                                <td style="padding: 4px; vertical-align: middle;"></td>
                                                                <td style="padding: 4px; vertical-align: middle;">
                                                                    <span class="text-warning fw-bold">
                                                                        <i class="bi bi-percent"></i> Ek İndirim: <?= htmlspecialchars((string)($ind['kod'] ?? '')) ?>
                                                                    </span><br>
                                                                    <small><?= htmlspecialchars((string)($ind['adi'] ?? '')) ?></small>
                                                                </td>
                                                                <td style="padding: 4px; text-align: center; vertical-align: middle;"><?= rtrim(rtrim(number_format($miktar, 2, ',', '.'), '0'), ',') ?></td>
                                                                <td style="padding: 4px; text-align: center; vertical-align: middle;"><?= htmlspecialchars((string)($ind['birim'] ?? '')) ?></td>
                                                                <td style="padding: 4px; text-align: right; vertical-align: middle;"><?= number_format($parentNet, 2, ',', '.') ?> <?= $currencySymbol ?></td>
                                                                <td style="padding: 4px; text-align: center; vertical-align: middle;">
                                                                    <span class="badge bg-warning text-dark">%<?= rtrim(rtrim(number_format($altIsk, 2, ',', '.'), '0'), ',') ?></span>
                                                                </td>
                                                                <td style="padding: 4px; text-align: right; vertical-align: middle;"><?= number_format($parentNet, 2, ',', '.') ?> <?= $currencySymbol ?></td>
                                                                <td style="padding: 4px; text-align: right; vertical-align: middle;"><?= number_format($lineTotal, 2, ',', '.') ?> <?= $currencySymbol ?></td>
                                                                <td style="padding: 4px; text-align: center; vertical-align: middle;">%20</td>
                                                                <td style="padding: 4px; text-align: right; vertical-align: middle;"><?= number_format($parentNet * 1.20, 2, ',', '.') ?> <?= $currencySymbol ?></td>
                                                                <td style="padding: 4px; text-align: right; vertical-align: middle;"><?= number_format($lineTotal * 1.20, 2, ',', '.') ?> <?= $currencySymbol ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>

                                                <?php
                                                // 1) ÜRÜN TOPLAMI (transaction_type=0) ve ek indirimler uygulandıktan sonra:
                                                $sumEUR = $sumTL = $sumUSD = 0;
                                                foreach ($urunler as $u) {
                                                    $liste   = (float)$u['liste'];
                                                    $isk     = (float)$u['iskonto'];
                                                    $miktar  = (float)$u['miktar'];
                                                    
                                                    // İskonto formülünü parse et (görüntüleme ile aynı mantık)
                                                    $netFactor = 1.0;
                                                    $rawFormula = $u['iskonto_formulu'] ?? '';
                                                    
                                                    if (!empty($rawFormula)) {
                                                        $cleanF = str_replace([' ', '+'], '-', $rawFormula);
                                                        $parts = explode('-', $cleanF);
                                                        foreach($parts as $p) {
                                                            $val = floatval(str_replace(',', '.', trim($p)));
                                                            if($val > 0) {
                                                                $netFactor *= (1 - $val/100);
                                                            }
                                                        }
                                                    } elseif ($isk > 0) {
                                                        // Formül yoksa tek iskonto kullan
                                                        $netFactor = (1 - $isk / 100);
                                                    }
                                                    
                                                    $netBirim = $liste * $netFactor;

                                                    // alt indirimleri de uygula
                                                    if (! empty($urunAltIndirimleri[$u['internal_reference']])) {
                                                        foreach ($urunAltIndirimleri[$u['internal_reference']] as $ind) {
                                                            $netBirim *= (1 - ((float)$ind['iskonto']) / 100);
                                                        }
                                                    }

                                                    $tutar = $netBirim * $miktar;
                                                    switch ($u['doviz']) {
                                                        case 'EUR':
                                                            $sumEUR += $tutar;
                                                            break;
                                                        case 'TL':
                                                            $sumTL  += $tutar;
                                                            break;
                                                        case 'USD':
                                                            $sumUSD += $tutar;
                                                            break;
                                                    }
                                                }

                                                // işte doğru yerde tanımlanmış oluyor:
                                                $genelTotals = [
                                                    'EUR' => $sumEUR,
                                                    'TL'  => $sumTL,
                                                    'USD' => $sumUSD,
                                                ];
                                                ?>
                                                <!-- GENEL İNDİRİMLER -->
                                                <?php foreach ($genelIndirimler as $gi):
                                                    $cur    = $gi['doviz'];
                                                    $rate   = (float)$gi['iskonto'] / 100;
                                                    $before = $genelTotals[$cur];
                                                    $after  = $before * (1 - $rate);
                                                    $disc   = $before - $after;
                                                    $genelTotals[$cur] = $after;   // bir sonraki indirim için
                                                    $symbol = getCurrencySymbol($cur);
                                                ?>
                                                    <tr style="background:#d9edf7">
                                                        <td></td>
                                                        <td>
                                                            <span class="text-info fw-bold">
                                                                <i class="bi bi-gift"></i> Genel İndirim: <?= htmlspecialchars((string)($gi['kod'] ?? '')) ?>
                                                            </span><br>
                                                            <small><?= htmlspecialchars((string)($gi['adi'] ?? '')) ?></small>
                                                        </td>
                                                        <td><?= (int)$gi['miktar'] ?></td>
                                                        <td><?= htmlspecialchars((string)($gi['birim'] ?? '')) ?></td>
                                                        <td><?= number_format($before, 2, ',', '.'); ?> <?= $symbol ?></td>
                                                        <td><span class="badge bg-info">%<?= rtrim(rtrim(number_format($rate * 100, 2, ',', '.'), '0'), ',') ?></span></td>
                                                        <td><?= number_format($after, 2, ',', '.'); ?> <?= $symbol ?></td>
                                                        <td><?= number_format($disc, 2, ',', '.'); ?> <?= $symbol ?></td>
                                                        <td>-</td>
                                                        <td>-</td>
                                                        <td><?= number_format($disc * 1.20, 2, ',', '.'); ?> <?= $symbol ?></td>
                                                    </tr>
                                                <?php endforeach; ?>

                                                <?php 
                                                // GENEL İSKONTO (Tablo Gösterimi)
                                                // Veritabanından gelen genel_iskonto değerini al
                                                $genelIskontoOrani = (float)($teklifBilgi['genel_iskonto'] ?? 0);
                                                
                                                if ($genelIskontoOrani > 0):
                                                    // Her döviz cinsi için ayrı satır ekleyelim (eğer tutar varsa)
                                                    foreach ($genelTotals as $cur => $val):
                                                        if ($val <= 0) continue;
                                                        
                                                        $rate   = $genelIskontoOrani / 100;
                                                        $before = $val;
                                                        $after  = $before * (1 - $rate);
                                                        $disc   = $before - $after;
                                                        $genelTotals[$cur] = $after; // Zincirleme etkisi için güncelle
                                                        $symbol = getCurrencySymbol($cur);
                                                ?>
                                                    <tr style="background:#dff0d8">
                                                        <td style="padding: 4px; vertical-align: middle;"></td>
                                                        <td style="padding: 4px; vertical-align: middle;">
                                                            <span class="text-success fw-bold">
                                                                <i class="bi bi-percent"></i> Genel İskonto
                                                            </span>
                                                        </td>
                                                        <td style="padding: 4px; text-align: center; vertical-align: middle;">1</td>
                                                        <td style="padding: 4px; text-align: center; vertical-align: middle;">Adet</td>
                                                        <td style="padding: 4px; text-align: right; vertical-align: middle;"><?= number_format($before, 2, ',', '.'); ?> <?= $symbol ?></td>
                                                        <td style="padding: 4px; text-align: center; vertical-align: middle;"><span class="badge bg-success">%<?= number_format($genelIskontoOrani, 2, ',', '.') ?></span></td>
                                                        <td style="padding: 4px; text-align: right; vertical-align: middle;"><?= number_format($after, 2, ',', '.'); ?> <?= $symbol ?></td>
                                                        <td style="padding: 4px; text-align: right; vertical-align: middle;"><?= number_format($disc, 2, ',', '.'); ?> <?= $symbol ?></td>
                                                        <td style="padding: 4px; text-align: center; vertical-align: middle;">-</td>
                                                        <td style="padding: 4px; text-align: right; vertical-align: middle;">-</td>
                                                        <td style="padding: 4px; text-align: right; vertical-align: middle;"><?= number_format($disc * 1.20, 2, ',', '.'); ?> <?= $symbol ?></td>
                                                    </tr>
                                                <?php 
                                                    endforeach;
                                                endif; 
                                                ?>

                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Teklif Özeti & Döviz Dönüşümleri -->
                            <?php
                            // 1) ÜRÜN TOPLAMI (transaction_type=0): her ürün için önce liste* (1-iskonto) *ek indirimler* ile neti bul, sonra miktar
                            $sumEUR = 0;
                            foreach ($urunler as $u) {
                                // ana ürünün liste fiyatı ve ilk iskonto
                                $liste   = (float)$u['liste'];
                                $isk0    = (float)$u['iskonto'];
                                $miktar  = (float)$u['miktar'];
                                
                                // İskonto formülünü parse et (tablo ile aynı mantık)
                                $netFactor = 1.0;
                                $rawFormula = $u['iskonto_formulu'] ?? '';
                                
                                if (!empty($rawFormula)) {
                                    $cleanF = str_replace([' ', '+'], '-', $rawFormula);
                                    $parts = explode('-', $cleanF);
                                    foreach($parts as $p) {
                                        $val = floatval(str_replace(',', '.', trim($p)));
                                        if($val > 0) {
                                            $netFactor *= (1 - $val/100);
                                        }
                                    }
                                } elseif ($isk0 > 0) {
                                    // Formül yoksa tek iskonto kullan
                                    $netFactor = (1 - $isk0 / 100);
                                }

                                // varsa ek indirimleri çarp
                                if (!empty($urunAltIndirimleri[$u['internal_reference']])) {
                                    foreach ($urunAltIndirimleri[$u['internal_reference']] as $ind) {
                                        $netFactor *= (1 - ((float)$ind['iskonto']) / 100);
                                    }
                                }

                                // tuttu ve toplama ekle
                                $sumEUR += ($liste * $netFactor) * $miktar;
                            }

                            // 2) GENEL İNDİRİM: tüm üründen sonra uygulamak istersen, ayrı yüzde olarak çarpabilirsin
                            $genelPerc = 1.0;
                            foreach ($genelIndirimler as $gi) {
                                $genelPerc *= (1 - ((float)$gi['iskonto']) / 100);
                            }
                            
                            // Genel İskonto Oranını da genelPerc içine dahil et
                            $genelIskontoOrani = (float)($teklifBilgi['genel_iskonto'] ?? 0);
                            if ($genelIskontoOrani > 0) {
                                $genelPerc *= (1 - $genelIskontoOrani / 100);
                            }
                            $sumEUR *= $genelPerc;
                            $paymentDate = $teklifBilgi['fatura_tarihi']
                                ?? $teklifBilgi['irsaliye_tarihi']
                                ?? $teklifBilgi['kurtarih'];

                            // 3) Kurları al (null kontrolü ile)
                            $euroKurRaw = $teklifBilgi['eurokur'] ?? '0';
                            $dolarKurRaw = $teklifBilgi['dolarkur'] ?? '0';
                            $euroKur   = floatval(str_replace(',', '.', $euroKurRaw));
                            $dolarKur  = floatval(str_replace(',', '.', $dolarKurRaw));
                            $kurTarihi = $teklifBilgi['kurtarih'] ?? $paymentDate;
                            
                            // Kurlar sıfır ise varsayılan değerler kullan
                            if ($euroKur <= 0) {
                                $euroKur = 1.0; // Varsayılan Euro kuru
                            }
                            if ($dolarKur <= 0) {
                                $dolarKur = 1.0; // Varsayılan Dolar kuru
                            }

                            // 4) KDV ve toplamlar
                            $netEUR   = $sumEUR;
                            $kdvEUR   = $netEUR * 0.20;
                            $grossEUR = $netEUR + $kdvEUR;

                            $netTL    = $netEUR  * $euroKur;
                            $kdvTL    = $kdvEUR  * $euroKur;
                            $grossTL  = $grossEUR * $euroKur;

                            // 1€ kaç $ eder? (Sıfıra bölme kontrolü)
                            $eurToUsd = ($dolarKur > 0) ? $euroKur / $dolarKur : 1.0;
                            $netUSD   = $netEUR  * $eurToUsd;
                            $kdvUSD   = $kdvEUR  * $eurToUsd;
                            $grossUSD = $grossEUR * $eurToUsd;

                            // Döviz gösterimi seçeneğini al
                            $dovizGoster = isset($teklifBilgi['doviz_goster']) && !empty(trim($teklifBilgi['doviz_goster'])) ? trim($teklifBilgi['doviz_goster']) : 'TUMU';
                            
                            // Ana birimi belirle
                            $anaBirim = 'EUR';
                            $anaNet = $netEUR;
                            $anaKdv = $kdvEUR;
                            $anaGross = $grossEUR;
                            $anaSembol = '€';
                            
                            if ($dovizGoster === 'USD') {
                                $anaBirim = 'USD';
                                $anaNet = $netUSD;
                                $anaKdv = $kdvUSD;
                                $anaGross = $grossUSD;
                                $anaSembol = '$';
                            } elseif ($dovizGoster === 'TL') {
                                $anaBirim = 'TL';
                                $anaNet = $netTL;
                                $anaKdv = $kdvTL;
                                $anaGross = $grossTL;
                                $anaSembol = '₺';
                            }

                            ?>
                            <!-- Modern Offer Summary -->
                            <div id="offer-summary-section" class="modern-table" style="margin-top: 1rem;">
                                <div style="background: #f9fafb; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem;">
                                    <h5 style="font-size: 1rem; font-weight: 600; color: #1f2937; margin-bottom: 1rem;">
                                        <?= t('offer_summary', $isForeignCustomer); ?>
                                    </h5>
                                    
                                    <!-- Grand Total Display -->
                                    <div style="text-align: center; margin-bottom: 1.5rem; padding: 1rem; background: white; border-radius: 6px;">
                                        <h1 style="font-size: 1.75rem; font-weight: 700; color: #2563eb; margin: 0;">
                                            <?= number_format($anaGross, 2, ',', '.'); ?> <?= $anaSembol; ?>
                                        </h1>
                                        <p style="margin: 0.25rem 0 0 0; color: #6b7280; font-size: 0.75rem;">
                                            <?= t('main_unit', $isForeignCustomer); ?>: <?= $anaBirim; ?> (<?= t('vat_included', $isForeignCustomer); ?>)
                                        </p>
                                    </div>
                                    
                                    <!-- Currency Breakdown Table -->
                                    <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                                        <thead>
                                            <tr style="background: #2563eb; color: white;">
                                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.75rem;">
                                                    <?= t('currency', $isForeignCustomer); ?>
                                                </th>
                                                <th style="padding: 0.75rem; text-align: right; font-weight: 600; font-size: 0.75rem;">
                                                    <?= t('net', $isForeignCustomer); ?>
                                                </th>
                                                <th style="padding: 0.75rem; text-align: right; font-weight: 600; font-size: 0.75rem;">
                                                    <?= t('vat', $isForeignCustomer); ?>
                                                </th>
                                                <th style="padding: 0.75rem; text-align: right; font-weight: 600; font-size: 0.75rem;">
                                                    <?= $isForeignCustomer ? 'Grand Total' : 'Genel Toplam'; ?>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($dovizGoster === 'EUR'): ?>
                                            <tr style="background: white;">
                                                <td style="padding: 0.75rem; font-weight: 600;">EUR</td>
                                                <td style="padding: 0.75rem; text-align: right;"><?= number_format($netEUR,  2, ',', '.'); ?> €</td>
                                                <td style="padding: 0.75rem; text-align: right;"><?= number_format($kdvEUR,  2, ',', '.'); ?> €</td>
                                                <td style="padding: 0.75rem; text-align: right; font-weight: 600;"><?= number_format($grossEUR, 2, ',', '.'); ?> €</td>
                                            </tr>
                                            <?php elseif ($dovizGoster === 'TL'): ?>
                                            <tr style="background: white;">
                                                <td style="padding: 0.75rem; font-weight: 600;">TL</td>
                                                <td style="padding: 0.75rem; text-align: right;"><?= number_format($netTL,  2, ',', '.'); ?> ₺</td>
                                                <td style="padding: 0.75rem; text-align: right;"><?= number_format($kdvTL,  2, ',', '.'); ?> ₺</td>
                                                <td style="padding: 0.75rem; text-align: right; font-weight: 600;"><?= number_format($grossTL, 2, ',', '.'); ?> ₺</td>
                                            </tr>
                                            <?php elseif ($dovizGoster === 'USD'): ?>
                                            <tr style="background: white;">
                                                <td style="padding: 0.75rem; font-weight: 600;">USD</td>
                                                <td style="padding: 0.75rem; text-align: right;"><?= number_format($netUSD,  2, ',', '.'); ?> $</td>
                                                <td style="padding: 0.75rem; text-align: right;"><?= number_format($kdvUSD,  2, ',', '.'); ?> $</td>
                                                <td style="padding: 0.75rem; text-align: right; font-weight: 600;"><?= number_format($grossUSD, 2, ',', '.'); ?> $</td>
                                            </tr>
                                            <?php else: // TUMU ?>
                                            <tr style="background: white;">
                                                <td style="padding: 0.75rem; font-weight: 600;">EUR</td>
                                                <td style="padding: 0.75rem; text-align: right;"><?= number_format($netEUR,  2, ',', '.'); ?> €</td>
                                                <td style="padding: 0.75rem; text-align: right;"><?= number_format($kdvEUR,  2, ',', '.'); ?> €</td>
                                                <td style="padding: 0.75rem; text-align: right; font-weight: 600;"><?= number_format($grossEUR, 2, ',', '.'); ?> €</td>
                                            </tr>
                                            <tr style="background: #f9fafb;">
                                                <td style="padding: 0.75rem; font-weight: 600;">TL <small style="color: #9ca3af;">(≈)</small></td>
                                                <td style="padding: 0.75rem; text-align: right; color: #6b7280;"><?= number_format($netTL,  2, ',', '.'); ?> ₺</td>
                                                <td style="padding: 0.75rem; text-align: right; color: #6b7280;"><?= number_format($kdvTL,  2, ',', '.'); ?> ₺</td>
                                                <td style="padding: 0.75rem; text-align: right; color: #6b7280;"><?= number_format($grossTL, 2, ',', '.') ?> ₺</td>
                                            </tr>
                                            <tr style="background: white;">
                                                <td style="padding: 0.75rem; font-weight: 600;">USD <small style="color: #9ca3af;">(≈)</small></td>
                                                <td style="padding: 0.75rem; text-align: right; color: #6b7280;"><?= number_format($netUSD,  2, ',', '.'); ?> $</td>
                                                <td style="padding: 0.75rem; text-align: right; color: #6b7280;"><?= number_format($kdvUSD,  2, ',', '.'); ?> $</td>
                                                <td style="padding: 0.75rem; text-align: right; color: #6b7280;"><?= number_format($grossUSD, 2, ',', '.') ?> $</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <!-- /Modern Offer Summary -->

                            <?php
                            //  ─────────── “Sözleşme” Kartı ───────────
                            $sozId = isset($teklifBilgi['sozlesme_id']) && (int)$teklifBilgi['sozlesme_id'] > 0 ? (int)$teklifBilgi['sozlesme_id'] : 5;
                            $soz = fetchSingle(
                                $db,
                                "SELECT * FROM sozlesmeler WHERE sozlesme_id = ?",
                                "Sözleşme yüklenemedi.",
                                [ $sozId ],
                                'i'
                            );
                            if ($soz):
                            ?>
                                <!-- Terms Container with Page Break Protection -->
                                <div id="offer-terms-section" class="terms-container" style="max-width: 1200px; margin: 0.5rem auto; padding: 0 2rem;">
                                    <div style="background: #f9fafb; padding: 1rem; border-radius: 8px;">
                                        <h5 style="font-size: 1rem; font-weight: 600; color: #1f2937; margin-bottom: 0.5rem;"><?= htmlspecialchars($soz['sozlesmeadi']); ?></h5>
                                        <div class="terms-content" style="font-size: 0.75rem; color: #4b5563; line-height: 1.6;">
                                            <?= $soz['sozlesme_metin']; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php
                            endif;
                            ?>

                            <!-- İşlem Butonları -->
                            <?php if ($isEditable): ?>
                                <div style="max-width: 1200px; margin: 0.5rem auto 2rem; padding: 0 2rem;">
                                    <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded">
                                        <div>
                                            <h5 class="mb-1" style="font-size: 1rem; font-weight: 600;"><?= t('action_options', $isForeignCustomer); ?></h5>
                                            <p class="mb-0" style="font-size: 0.875rem;"><?= t('current_status', $isForeignCustomer); ?>: <strong><?= htmlspecialchars($currentDurum); ?></strong></p>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-success" onclick="approveOffer()" data-bs-toggle="tooltip" title="<?= htmlspecialchars($onayTooltip); ?>"><?= t('approve', $isForeignCustomer); ?></button>
                                            <form action="offer_detail.php?te=<?= urlencode($teklifId); ?>&sta=<?= urlencode($siparisStatu); ?>" method="POST" data-parsley-validate class="d-inline">
                                                <input type="hidden" name="durum" value="Reddet">
                                                <button type="submit" class="btn btn-danger" data-bs-toggle="tooltip" title="<?= htmlspecialchars($redTooltip); ?>"><?= t('reject', $isForeignCustomer); ?></button>
                                            </form>
                                            <?php if ($canRevise): ?>
                                                <button type="button" class="btn btn-warning" onclick="document.getElementById('durum-tab').click();" data-bs-toggle="tooltip" title="<?= t('revise_tooltip', $isForeignCustomer); ?>"><?= t('request_revise', $isForeignCustomer); ?></button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php elseif (!$isEditable): ?>
                                <div style="max-width: 1200px; margin: 0.5rem auto 2rem; padding: 0 2rem;">
                                    <div class="alert alert-secondary mb-0">
                                        <p class="mb-0"><?= t('no_change_allowed', $isForeignCustomer); ?> <?= t('current_status', $isForeignCustomer); ?>: <strong><?= htmlspecialchars($currentDurum); ?></strong></p>
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div>


                        <!-- Tab 2: Durum Düzenleme (Revize İşlemi) -->
                        <div class="tab-pane fade" id="durum" role="tabpanel" aria-labelledby="durum-tab">
                            <!-- Revize Form Section -->
                            <div style="max-width: 1200px; margin: 2rem auto; padding: 0 2rem;">
                                <div class="card">
                                    <div class="card-header" style="background: linear-gradient(45deg, #f59e0b, #d97706); color: white;">
                                        <h5 class="mb-0" style="font-weight: 600;"><?= t('revise_process', $isForeignCustomer); ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($canRevise): ?>
                                            <form action="offer_detail.php?te=<?= urlencode($teklifId); ?>&sta=<?= urlencode($siparisStatu); ?>" method="POST" data-parsley-validate>
                                                <input type="hidden" name="durum" value="Revize Et">
                                                <div class="mb-3">
                                                    <label for="notlar" class="form-label" style="font-weight: 600; color: #1f2937;"><?= t('revise_note', $isForeignCustomer); ?>:</label>
                                                    <textarea class="form-control" id="notlar" name="notlar" rows="4"
                                                        placeholder="<?= t('revise_placeholder', $isForeignCustomer); ?>"
                                                        data-parsley-minlength="10"
                                                        data-parsley-maxlength="300"
                                                        data-parsley-trigger="change"
                                                        style="border: 2px solid #e5e7eb; border-radius: 8px; padding: 0.75rem;"></textarea>
                                                    <small class="text-muted">Minimum 10, maksimum 300 karakter</small>
                                                </div>
                                                <div class="text-end">
                                                    <button type="submit" class="btn btn-warning btn-lg" data-bs-toggle="tooltip" title="<?= t('revise_complete_tooltip', $isForeignCustomer); ?>" style="padding: 0.75rem 2rem; font-weight: 600;">
                                                        <i class="bi bi-pencil-square me-2"></i><?= t('revise_update', $isForeignCustomer); ?>
                                                    </button>
                                                </div>
                                            </form>
                                        <?php else: ?>
                                            <div class="alert alert-warning" role="alert">
                                                <i class="bi bi-exclamation-triangle me-2"></i>
                                                <strong><?= t('revise_right_exceeded', $isForeignCustomer); ?></strong>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Status History Section -->
                            <div style="max-width: 1200px; margin: 2rem auto; padding: 0 2rem;">
                                <div class="card">
                                    <div class="card-header" style="background: linear-gradient(45deg, #2563eb, #1d4ed8); color: white;">
                                        <h5 class="mb-0" style="font-weight: 600;"><?= t('status_history', $isForeignCustomer); ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped table-hover">
                                                <thead style="background: #2563eb; color: white;">
                                                    <tr>
                                                        <th style="font-weight: 600;"><?= t('date', $isForeignCustomer); ?></th>
                                                        <th style="font-weight: 600;"><?= t('old_status', $isForeignCustomer); ?></th>
                                                        <th style="font-weight: 600;"><?= t('new_status', $isForeignCustomer); ?></th>
                                                        <th style="font-weight: 600;"><?= t('changed_by', $isForeignCustomer); ?></th>
                                                        <th style="font-weight: 600;"><?= t('notes', $isForeignCustomer); ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $historyRows = fetchMultiple(
                                                        $db,
                                                        "SELECT * FROM durum_gecisleri WHERE teklif_id = ? ORDER BY degistirme_tarihi DESC",
                                                        "Durum geçmişi sorgusu başarısız.",
                                                        [ $teklifId ],
                                                        'i'
                                                    );
                                                    if (!empty($historyRows)) {
                                                        foreach ($historyRows as $hSatir) {
                                                            $degistirenId = (int)$hSatir["degistiren_personel_id"];
                                                            $pAd = "Bilinmiyor";
                                                            if ($degistirenId > 0) {
                                                                $pBilgi = fetchSingle(
                                                                    $db,
                                                                    "SELECT adsoyad FROM yonetici WHERE yonetici_id = ?",
                                                                    "Yetkili sorgusu başarısız.",
                                                                    [ $degistirenId ],
                                                                    'i'
                                                                );
                                                                $pAd = $pBilgi["adsoyad"] ?? $pAd;
                                                            }
                                                            echo '<tr>';
                                                            echo '<td style="white-space: nowrap;">' . htmlspecialchars($hSatir["degistirme_tarihi"]) . '</td>';
                                                            echo '<td><span class="badge bg-secondary">' . htmlspecialchars($hSatir["eski_durum"]) . '</span></td>';
                                                            echo '<td><span class="badge bg-primary">' . htmlspecialchars($hSatir["yeni_durum"]) . '</span></td>';
                                                            echo '<td>' . htmlspecialchars($pAd) . '</td>';
                                                            echo '<td>' . htmlspecialchars($hSatir["notlar"]) . '</td>';
                                                            echo '</tr>';
                                                        }
                                                    } else {
                                                        echo '<tr><td colspan="5" class="text-center text-muted">Henüz durum değişikliği kaydı bulunmamaktadır.</td></tr>';
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php
                    $markaRows = fetchMultiple(
                        $db,
                        "SELECT * FROM markalar",
                        "Marka sorgusu başarısız."
                    );
                    if (!empty($markaRows)) {
                        foreach ($markaRows as $markaSatir) {
                    ?>
                            <div class="modal fade duzenle<?= htmlspecialchars($markaSatir["marka_id"]); ?>" tabindex="-1" role="dialog">
                                <!-- Modal içeriği -->
                            </div>
                            <div class="modal fade resim<?= htmlspecialchars($markaSatir["marka_id"]); ?>" tabindex="-1" role="dialog">
                                <!-- Modal içeriği -->
                            </div>
                    <?php
                        }
                    } else {
                        echo '<p>Marka bilgisi alınamadı.</p>';
                    }
                    ?>
                </div>
            </div>

            <!-- Onay Modal -->
            <div class="modal fade" id="approvalModal" tabindex="-1" aria-labelledby="approvalModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.15);">
                        <div class="modal-header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border-radius: 12px 12px 0 0; border: none;">
                            <h5 class="modal-title" id="approvalModalLabel">
                                <i class="fas fa-check-circle me-2"></i>
                                <span id="modalTitle"><?= $isForeignCustomer ? 'Confirm Offer' : 'Teklifi Onayla' ?></span>
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" style="padding: 2rem;">
                            <div class="alert alert-info" style="background: #dbeafe; border-left: 4px solid #3b82f6; border-radius: 8px;">
                                <p class="mb-0" id="modalMessage" style="font-size: 0.95rem; line-height: 1.6;">
                                    <?= $isForeignCustomer 
                                        ? 'You are approving this offer. After your approval, an offer summary will be sent to your email address.' 
                                        : 'Teklifi onaylıyorsunuz. Onayınız sonrasında mailinize teklif onay özeti gönderilecektir.' 
                                    ?>
                                </p>
                            </div>
                            
                            <form id="approvalForm">
                                <div class="mb-3">
                                    <label for="customerEmail" class="form-label" id="emailLabel">
                                        <?= $isForeignCustomer ? 'Email Address' : 'E-posta Adresi' ?>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="email" class="form-control" id="customerEmail" required 
                                           value="<?= htmlspecialchars($customerEmail) ?>"
                                           <?= !empty($customerEmail) ? 'readonly' : '' ?>
                                           placeholder="<?= $isForeignCustomer ? 'your@email.com' : 'ornek@email.com' ?>"
                                           style="border-radius: 8px; padding: 0.75rem; <?= !empty($customerEmail) ? 'background-color: #f3f4f6;' : '' ?>">
                                    <?php if (!empty($customerEmail)): ?>
                                    <small class="text-muted">
                                        <?= $isForeignCustomer ? 'Email address from your offer' : 'Teklifinizdeki e-posta adresi' ?>
                                    </small>
                                    <?php endif; ?>
                                </div>
                                <div class="mb-3">
                                    <label for="customerName" class="form-label" id="nameLabel">
                                        <?= $isForeignCustomer ? 'Full Name' : 'Ad Soyad' ?>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="customerName" required 
                                           value="<?= htmlspecialchars($customerName) ?>"
                                           placeholder="<?= $isForeignCustomer ? 'John Doe' : 'Adınız Soyadınız' ?>"
                                           style="border-radius: 8px; padding: 0.75rem;">
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer" style="border-top: 1px solid #e5e7eb; padding: 1rem 2rem;">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 8px;">
                                <?= $isForeignCustomer ? 'Cancel' : 'İptal' ?>
                            </button>
                            <button type="button" class="btn btn-success" onclick="submitApproval()" style="border-radius: 8px; padding: 0.5rem 1.5rem;">
                                <i class="fas fa-check me-2"></i>
                                <span id="confirmButton"><?= $isForeignCustomer ? 'Confirm Approval' : 'Onayı Tamamla' ?></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="footer mt-0">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6">© 2023 Gemas. Tüm Hakları Saklıdır.</div>
                        <div class="col-sm-6 text-end">
                            <script>
                                document.write(new Date().getFullYear())
                            </script> © Gemas.
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <div class="rightbar-overlay"></div>
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/parsleyjs/parsley.min.js"></script>
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Manual tab switching (Bootstrap tabs weren't working properly)
        document.addEventListener('DOMContentLoaded', function() {
            // Get tab buttons and panes
            const detayTab = document.getElementById('detay-tab');
            const durumTab = document.getElementById('durum-tab');
            const detayPane = document.getElementById('detay');
            const durumPane = document.getElementById('durum');

            // Get specific sections that are outside tab-pane
            const summarySection = document.getElementById('offer-summary-section');
            const termsSection = document.getElementById('offer-terms-section');

            if (!detayTab || !durumTab || !detayPane || !durumPane) {
                console.error('Tab elements not found');
                return;
            }

            console.log('Summary section:', summarySection);
            console.log('Terms section:', termsSection);

            // Function to switch tabs
            function switchTab(showTab, hideTab, showPane, hidePane) {
                // Update tab buttons
                showTab.classList.add('active');
                showTab.setAttribute('aria-selected', 'true');
                hideTab.classList.remove('active');
                hideTab.setAttribute('aria-selected', 'false');

                // Update tab panes
                showPane.classList.add('show', 'active');
                hidePane.classList.remove('show', 'active');

                // Manually hide/show summary and terms
                if (showPane.id === 'durum') {
                    // Hide summary and terms when showing Revize tab
                    if (summarySection) {
                        summarySection.style.display = 'none';
                        console.log('Hiding summary section');
                    }
                    if (termsSection) {
                        termsSection.style.display = 'none';
                        console.log('Hiding terms section');
                    }
                } else {
                    // Show summary and terms when showing Detay tab
                    if (summarySection) {
                        summarySection.style.display = '';
                        console.log('Showing summary section');
                    }
                    if (termsSection) {
                        termsSection.style.display = '';
                        console.log('Showing terms section');
                    }
                }
            }

            // Add click handlers
            detayTab.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Detay tab clicked');
                switchTab(detayTab, durumTab, detayPane, durumPane);
            });

            durumTab.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Durum tab clicked');
                switchTab(durumTab, detayTab, durumPane, detayPane);
            });

            console.log('Manual tab switching initialized');
        });

        // Helper function to switch to Durum tab
        function switchTabToDurum() {
            const durumTab = document.getElementById('durum-tab');
            if (durumTab) {
                durumTab.click();
            }
        }

        // Teklif onaylama - Modal aç
        function approveOffer() {
            const modal = new bootstrap.Modal(document.getElementById('approvalModal'));
            modal.show();
        }

        // Modal'dan onay gönder
        async function submitApproval() {
            const emailInput = document.getElementById('customerEmail');
            const nameInput = document.getElementById('customerName');
            const confirmBtn = event.target.closest('button');
            
            // Form validasyonu
            if (!emailInput.value || !emailInput.value.includes('@')) {
                emailInput.focus();
                emailInput.classList.add('is-invalid');
                return;
            }
            
            if (!nameInput.value || nameInput.value.trim() === '') {
                nameInput.focus();
                nameInput.classList.add('is-invalid');
                return;
            }

            // Loading göster
            const originalText = confirmBtn.innerHTML;
            confirmBtn.disabled = true;
            const isForeign = <?= $isForeignCustomer ? 'true' : 'false' ?>;
            confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>' + (isForeign ? 'Processing...' : 'İşleniyor...');

            try {
                const response = await fetch('api/approve_offer.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        teklif_id: <?= $teklifId ?>,
                        customer_email: emailInput.value,
                        customer_name: nameInput.value
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Form'u gizle, başarı mesajını göster
                    document.getElementById('approvalForm').style.display = 'none';
                    document.querySelector('.modal-footer').style.display = 'none';
                    document.querySelector('.alert-info').style.display = 'none';
                    
                    // Başarı mesajı
                    const modalBody = document.querySelector('#approvalModal .modal-body');
                    const successMsg = isForeign 
                        ? `<div class="alert alert-success" style="background: #d1fae5; border-left: 4px solid #10b981; border-radius: 8px; padding: 20px; text-align: center;">
                            <i class="fas fa-check-circle" style="font-size: 48px; color: #10b981; margin-bottom: 15px;"></i>
                            <h4 style="color: #065f46; margin-bottom: 10px;">Offer Approved Successfully!</h4>
                            <p style="color: #047857; margin-bottom: 15px;">A confirmation email and PDF have been sent to your email address.</p>
                            <p style="color: #047857;">Our sales team will contact you shortly.</p>
                            <button class="btn btn-success mt-3" onclick="window.location.reload()" style="border-radius: 8px; padding: 10px 30px;">
                                <i class="fas fa-redo me-2"></i>OK
                            </button>
                        </div>`
                        : `<div class="alert alert-success" style="background: #d1fae5; border-left: 4px solid #10b981; border-radius: 8px; padding: 20px; text-align: center;">
                            <i class="fas fa-check-circle" style="font-size: 48px; color: #10b981; margin-bottom: 15px;"></i>
                            <h4 style="color: #065f46; margin-bottom: 10px;">Teklif başarıyla onaylandı!</h4>
                            <p style="color: #047857; margin-bottom: 15px;">E-posta adresinize onay maili ve PDF gönderilmiştir.</p>
                            <p style="color: #047857;">Satış ekibimiz en kısa sürede sizinle iletişime geçecektir.</p>
                            <button class="btn btn-success mt-3" onclick="window.location.reload()" style="border-radius: 8px; padding: 10px 30px;">
                                <i class="fas fa-redo me-2"></i>Tamam
                            </button>
                        </div>`;
                    
                    modalBody.innerHTML = successMsg;
                    
                } else {
                    throw new Error(data.error || (isForeign ? 'Unknown error occurred' : 'Bilinmeyen bir hata oluştu'));
                }
            } catch (error) {
                console.error('Onay hatası:', error);
                const errorMsg = isForeign 
                    ? '❌ An error occurred during approval:\n' + error.message
                    : '❌ Onay işlemi sırasında bir hata oluştu:\n' + error.message;
                alert(errorMsg);
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = originalText;
            }
        }

        // Input validation - remove invalid class on input
        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.getElementById('customerEmail');
            const nameInput = document.getElementById('customerName');
            
            if (emailInput) {
                emailInput.addEventListener('input', function() {
                    this.classList.remove('is-invalid');
                });
            }
            
            if (nameInput) {
                nameInput.addEventListener('input', function() {
                    this.classList.remove('is-invalid');
                });
            }
        });
    </script>
</body>

</html>
