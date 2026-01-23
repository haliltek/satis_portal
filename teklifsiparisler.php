<?php
include "fonk.php";
oturumkontrol();
$userType = $_SESSION['user_type'] ?? '';
if ($userType === 'Bayi') {
    header('Location: dealer_orders.php');
    exit;
}

$flashMessage = null;
if (isset($_SESSION['flash'])) {
    $flashMessage = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

$config = require __DIR__ . '/config/config.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Hata raporlamasını yapılandırma
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log'); // __DIR__ kullanarak aynı dizin altındaki error.log'a yazıyoruz.
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

/**
 * Özel hata işleyicisi: Uyarılar ve diğer hataları error.log'a yazalım
 */
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    $date = date('Y-m-d H:i:s');
    $message = "[$date] [ERROR] ($errno): $errstr in $errfile on line $errline" . PHP_EOL;
    error_log($message, 3, __DIR__ . '/error.log');
    // Hata raporlama için false döndürerek PHP'nin dahili hata işleyicisinin devreye girmesini engeller
    return true;
});

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null) {
        $date = date('Y-m-d H:i:s');
        $message = "[$date] [SHUTDOWN] ({$error['type']}): {$error['message']} in {$error['file']} on line {$error['line']}" . PHP_EOL;
        error_log($message, 3, __DIR__ . '/error.log');
    }
});

// Durum kategorileri bir yerde tanımlı olsun ki hem filtreler hem de
// tablo sınıflandırması aynı listeleri kullansın.
const SALES_ACTION_STATUSES = [
    'Sipariş Oluşturuldu / Gönderilecek',
    'Sipariş Onaylandı / Logoya Aktarım Bekliyor',
    'Teklif Oluşturuldu / Gönderilecek',
    'Teklife Revize Talep Edildi / İnceleme Bekliyor',
    'Yönetici Onayı Bekliyor',
    'Yönetici Onayı Bekleniyor'
];

const CLIENT_ACTION_STATUSES = [
    'Sipariş Logoya Aktarıldı / Ödeme Bekleniyor',
    'Sipariş Logoya Aktarıldı / Ödemesi Bekleniyor',
    'Teklif Gönderildi / Onay Bekleniyor',
    'Teklif Revize Edildi / Onay Bekleniyor'
];
// -----------------------------------------------------------------------------
// 1) ORTAK LOG FONKSİYONU
// -----------------------------------------------------------------------------

function logYonetim($db, $islem, $personel, $tarih, $durum)
{
    $stmt = $db->prepare("INSERT INTO log_yonetim(islem, personel, tarih, durum) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("ssis", $islem, $personel, $tarih, $durum);
        return $stmt->execute();
    }
    return false;
}
/**
 * customErrorLog()
 * Hata mesajını hem error.log hem debug.log dosyalarına yazar.
 */
function customErrorLog($message)
{
    $errorLog = __DIR__ . '/error.log';
    $debugLog = __DIR__ . '/debug.log';
    $date = date('Y-m-d H:i:s');
    $formattedMessage = "[$date] " . $message . "\n";
    error_log($formattedMessage, 3, $errorLog);
    error_log($formattedMessage, 3, $debugLog);
}

/**
 * ogteklif2 kaydından müşteri adını çözer
 */
function resolveCompanyName(mysqli $db, array $row): string
{
    $sirketArp = trim($row['sirket_arp_code'] ?? '');
    if ($sirketArp !== '') {
        $stmt = $db->prepare("SELECT s_adi FROM sirket WHERE s_arp_code=? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $sirketArp);
            $stmt->execute();
            $sir = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($sir) {
                $name = trim($sir['s_adi'] ?? '');
                if ($name !== '') {
                    return $name;
                }
            }
        }
    }
    $name = trim($row['musteriadi'] ?? '');
    return $name;
}
// -----------------------------------------------------------------------------
// 2) GEREKLİ DB İŞLEMLERİNİ YAPAN FONKSİYONLAR
// -----------------------------------------------------------------------------

function departmanAtama($db, $icerikid, $departman, $yoneticiId, $adsoyad, $zaman)
{
    $stmt = $db->prepare("UPDATE ogteklif2 SET atama = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $departman, $icerikid);
        $sonuc = $stmt->execute();

        if ($sonuc) {
            logYonetim($db, 'Siparişe Departman Ataması', $yoneticiId, $zaman, 'Başarılı');
            $_SESSION['flash'] = [
                'type'    => 'success',
                'message' => 'Sayın ' . $adsoyad . ' <br> Departman Başarıyla Kaydedilmiştir.'
            ];
        } else {
            logYonetim($db, 'Departman Güncelleme', $yoneticiId, $zaman, 'Başarısız');
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Sayın ' . $adsoyad . ' <br> Departman Malesef Kaydedilemedi.'
            ];
        }

        $stmt->close();
        $returnUrl = $_SERVER['HTTP_REFERER'] ?? 'teklifsiparisler.php';
        header('Location: ' . $returnUrl);
        exit;
    } else {
        // Hazırlama Hatası
        logYonetim($db, 'Departman Güncelleme Hazırlama Hatası', $yoneticiId, $zaman, 'Başarısız');
        $_SESSION['flash'] = [
            'type'    => 'danger',
            'message' => 'Departman güncellenirken bir hata oluştu.'
        ];
        $returnUrl = $_SERVER['HTTP_REFERER'] ?? 'teklifsiparisler.php';
        header('Location: ' . $returnUrl);
        exit;
    }
}

function guncelleDurumStatuOdeme($db, $icerikid, $durum, $statu, $odemenot, $yoneticiId, $adsoyad, $zaman)
{
    // Önce eski durumu ve sirket_arp_code bilgisini çekelim
    $stmt = $db->prepare("SELECT durum, sirket_arp_code FROM ogteklif2 WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $icerikid);
        $stmt->execute();
        $result = $stmt->get_result();
        $okunan = $result->fetch_assoc();
        $eskiDurum = isset($okunan['durum']) ? $okunan['durum'] : '';
        $s_arp_code = isset($okunan['sirket_arp_code']) ? $okunan['sirket_arp_code'] : '';
        $stmt->close();
        customErrorLog("Eski Durum: $eskiDurum, Yeni Durum: $durum");
    } else {
        logYonetim($db, 'Durum Güncelleme Sorgu Hazırlama Hatası', $yoneticiId, $zaman, 'Başarısız');
        $_SESSION['flash'] = [
            'type'    => 'danger',
            'message' => 'Durum güncellenirken bir hata oluştu.'
        ];
        header('Location: teklifsiparisler.php');
        exit;
    }

    // Durum, statu ve odemetipi güncellemesi
    $stmt = $db->prepare("UPDATE ogteklif2 SET durum = ?, statu = ?, odemetipi = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("sssi", $durum, $statu, $odemenot, $icerikid);
        $duzenleme = $stmt->execute();
        if (!$duzenleme) {
            customErrorLog("Durum güncelleme UPDATE sorgusu hatası: " . $stmt->error);
        }
        $stmt->close();

        if ($duzenleme) {
            // Eğer durum değiştiyse (veya log kaydını her zaman eklemek isterseniz bu koşulu kaldırın)
            if ($eskiDurum != $durum) {
                $stmt_insert = $db->prepare("INSERT INTO durum_gecisleri (teklif_id, s_arp_code, eski_durum, yeni_durum, degistiren_personel_id, notlar) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt_insert) {
                    $notlar = "Statü: $statu";
                    $stmt_insert->bind_param("isssis", $icerikid, $s_arp_code, $eskiDurum, $durum, $yoneticiId, $notlar);
                    if (!$stmt_insert->execute()) {
                        customErrorLog("Durum geçişleri INSERT sorgusu hatası: " . $stmt_insert->error);
                    } else {
                        customErrorLog("Durum geçişleri başarıyla kaydedildi.");
                    }
                    $stmt_insert->close();
                } else {
                    customErrorLog("Durum geçişleri sorgu hazırlama hatası: " . $db->error);
                }
            }
            logYonetim($db, 'Siparişe Durum - Statü Ataması', $yoneticiId, $zaman, 'Başarılı');
            $_SESSION['flash'] = [
                'type'    => 'success',
                'message' => 'Sayın ' . $adsoyad . '<br> Durum - Statü Başarıyla Kaydedilmiştir.'
            ];
            $returnUrl = $_SERVER['HTTP_REFERER'] ?? 'teklifsiparisler.php';
            header('Location: ' . $returnUrl);
            exit;
        } else {
            customErrorLog("Durum güncelleme UPDATE sorgusu hatası: " . $db->error);
            logYonetim($db, 'Durum - Statü Güncelleme', $yoneticiId, $zaman, 'Başarısız');
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Sayın ' . $adsoyad . '<br> Durum - Statü Malesef Kaydedilemedi.'
            ];
            $returnUrl = $_SERVER['HTTP_REFERER'] ?? 'teklifsiparisler.php';
            header('Location: ' . $returnUrl);
            exit;
        }
    } else {
        customErrorLog("Durum güncelleme UPDATE sorgusu hazırlanırken hata: " . $db->error);
        logYonetim($db, 'Durum Güncelleme Sorgu Hazırlama Hatası', $yoneticiId, $zaman, 'Başarısız');
        $_SESSION['flash'] = [
            'type'    => 'danger',
            'message' => 'Durum güncellenirken bir hata oluştu.'
        ];
        $returnUrl = $_SERVER['HTTP_REFERER'] ?? 'teklifsiparisler.php';
        header('Location: ' . $returnUrl);
        exit;
    }
}

function gonderEposta($db, $icerikid, $eposta, $metin, $notu, $url)
{
    // E-posta adresini doğrulama
    if (!filter_var($eposta, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['flash'] = [
            'type'    => 'danger',
            'message' => 'Geçersiz E-posta Adresi.'
        ];
        header('Location: teklifsiparisler.php');
        exit;
    }

    // Veritabanından ilgili teklif kaydını çekelim: teklif numarası ve müşteri adı soyadı (musteriadi)
    $sorgu = $db->prepare("SELECT teklifkodu, musteriadi, kime, hazirlayanid, sirket_arp_code FROM ogteklif2 WHERE id = ?");
    $sorgu->bind_param("i", $icerikid);
    $sorgu->execute();
    $result = $sorgu->get_result();
    $row = $result->fetch_assoc();
    $teklifNo = $row['teklifkodu'] ?? 'Bilinmeyen';
    $adiSoyadi = resolveCompanyName($db, $row);
    $hazirlayan = (int)($row['hazirlayanid'] ?? 0);
    $sirketArpCode = $row['sirket_arp_code'] ?? '';
    $sorgu->close();

    // Yurtdışı kontrolü (120.02 veya 320.02 ile başlayanlar)
    $isForeign = false;
    if (strpos($sirketArpCode, '120.02') === 0 || strpos($sirketArpCode, '320.02') === 0) {
        $isForeign = true;
    }
    // Kullanıcı filtresi zorlaması (hidden inputtan gelen)
    if (isset($_REQUEST['force_english']) && $_REQUEST['force_english'] == '1') {
        $isForeign = true;
    }

    // Teklif mailleri için özel satis@gemas.com.tr kullan
    // Diğer işlemler için veritabanındaki fiyat@gemas.com.tr kullanılmaya devam edecek
    $yoneticiAd = $isForeign ? 'Gemas Sales Team' : 'Gemas Satış Ekibi';
    $yoneticiMail = 'satis@gemas.com.tr';
    $yoneticiSmtp = 'mail.gemas.com.tr';
    $yoneticiPort = 465;
    $yoneticiPass = 'Halil12621262.';
    
    // Hazırlayan kişi bilgilerini ek bilgi için alalım
    $yoneticiUnvan = '';
    $yoneticiTel = '';
    $yon = $db->prepare("SELECT unvan, telefon FROM yonetici WHERE yonetici_id=?");
    if ($yon) {
        $yon->bind_param('i', $hazirlayan);
        $yon->execute();
        $resYon = $yon->get_result();
        $yRow = $resYon->fetch_assoc();
        $yoneticiUnvan = $yRow['unvan'] ?? '';
        $yoneticiTel = $yRow['telefon'] ?? '';
        $yon->close();
    }



    $templateFile = $isForeign ? "mail_templates/mail_template_en.html" : "mail_templates/mail_template.html";
    if (file_exists($templateFile)) {
        $template = file_get_contents($templateFile);
    } else {
        // Fallback to default if EN template missing
        $template = file_get_contents("mail_templates/mail_template.html");
    }

    $template = str_replace('{{ADI_SOYADI}}',         htmlspecialchars($adiSoyadi), $template);
    $template = str_replace('{{METIN}}',              isset($metin) ? htmlspecialchars($metin) : '', $template);
    $template = str_replace('{{NOTU}}',               isset($notu) ? htmlspecialchars($notu) : '', $template);
    $template = str_replace('{{URL}}',                isset($url) ? htmlspecialchars($url) : '', $template);
    $template = str_replace('{{YONETICI_UNVAN}}',     htmlspecialchars($yoneticiUnvan), $template);
    $template = str_replace('{{YONETICI_TELEFON}}',   htmlspecialchars($yoneticiTel ?? ''), $template);
    $template = str_replace('{{YONETICI_MAILPOSTA}}',  htmlspecialchars($yoneticiMail), $template);
    $template = str_replace('{{TEKLIF_NUMARASI}}',    htmlspecialchars($teklifNo), $template);

    // PHPMailer Ayarları ve mail gönderimi
    $mail = new PHPMailer(true);
    try {
        $mail->IsSMTP();
        $mail->SMTPAuth = true;

        $mail->Host     = $yoneticiSmtp;
        $mail->Port     = $yoneticiPort;
        $mail->Username = $yoneticiMail;
        $mail->Password = $yoneticiPass;

        $mail->SMTPDebug = 2; // Debug modu açık - detaylı hata göster
        $mail->SMTPAutoTLS = true;
        $mail->SMTPSecure = 'ssl';

        $mail->SetFrom($mail->Username, $yoneticiAd);
        $mail->AddAddress($eposta, $adiSoyadi);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $isForeign ? 'Regarding Your Proposal' : 'Teklifiniz Hk.';
        $mail->MsgHTML($template);

        if ($mail->Send()) {
            $_SESSION['flash'] = [
                'type'    => 'success',
                'message' => 'Teklifiniz Kullanıcıya Başarıyla İletilmiştir.'
            ];
            $stmt = $db->prepare("UPDATE ogteklif2 SET durum='Teklif Gönderildi / Onay Bekleniyor' WHERE id=?");
            if ($stmt) { $stmt->bind_param('i', $icerikid); $stmt->execute(); $stmt->close(); }
            header('Location: teklifsiparisler.php');
            exit;
        } else {
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Mail gönderilirken bir hata oluştu: ' . $mail->ErrorInfo
            ];
            header('Location: teklifsiparisler.php');
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['flash'] = [
            'type'    => 'danger',
            'message' => 'Mesaj gönderilemedi. Hata: ' . $mail->ErrorInfo
        ];
        header('Location: teklifsiparisler.php');
        exit;
    }
}

/**
 * Email şablonundaki metne benzer bir WhatsApp mesajı oluşturur
 */
function getWhatsappMessage(mysqli $db, int $teklifId): string
{
    include "include/url.php";
    $stmt = $db->prepare("SELECT teklifkodu, musteriadi, kime, hazirlayanid, sirket_arp_code FROM ogteklif2 WHERE id=?");
    $stmt->bind_param('i', $teklifId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $adiSoyadi = resolveCompanyName($db, $row);
    $teklifNo  = $row['teklifkodu'] ?? '';
    $hazirlayan = (int)($row['hazirlayanid'] ?? 0);

    $yonMail = '';
    if ($hazirlayan) {
        $s = $db->prepare("SELECT mailposta FROM yonetici WHERE yonetici_id=?");
        $s->bind_param('i', $hazirlayan);
        $s->execute();
        $yonRow = $s->get_result()->fetch_assoc();
        $yonMail = $yonRow['mailposta'] ?? '';
        $s->close();
    }

    $urlTeklif = $url . '/offer_detail.php?te=' . $teklifId . '&sta=Teklif';

    return 'Sayın ' . $adiSoyadi . ', ' . $teklifNo .
        ' numaralı teklifinizi onaylamak, reddetmek veya revize etmek için ' .
        $urlTeklif . ' adresini ziyaret edebilirsiniz. Sorularınız için ' . $yonMail . '.';
}

// -----------------------------------------------------------------------------
// 3) POST İSTEKLERİNİ YAKALAYAN İŞLEM FONKSİYONU
// -----------------------------------------------------------------------------

function handleRequests($db, $adsoyad, $yonetici_id_sabit, $zaman, $config)
{
    // 1) Departman atama (duzenleme)
    if (isset($_POST['duzenleme'])) {
        $departman = isset($_POST["departman"]) ? trim($_POST["departman"]) : '';
        $icerikid  = isset($_POST["icerikid"]) ? (int)$_POST["icerikid"] : 0;
        departmanAtama($db, $icerikid, $departman, $yonetici_id_sabit, $adsoyad, $zaman);
    }
    // 2) Durum - Statü - Ödeme (duzenleme2)
    else if (isset($_POST['duzenleme2'])) {
        $durum     = isset($_POST["durum"]) ? trim($_POST["durum"]) : '';
        $statu     = isset($_POST["statu"]) ? trim($_POST["statu"]) : '';
        $odemenot  = isset($_POST["odemenot"]) ? trim($_POST["odemenot"]) : '';
        $icerikid  = isset($_POST["icerikid"]) ? (int)$_POST["icerikid"] : 0;
        guncelleDurumStatuOdeme($db, $icerikid, $durum, $statu, $odemenot, $yonetici_id_sabit, $adsoyad, $zaman);
    }
    // 3) Ajax durum güncelleme
    else if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
        $status = trim($_POST['status'] ?? '');
        $id     = (int)($_POST['id'] ?? 0);
        header('Content-Type: application/json');
        if ($id && $status) {
            $stmt = $db->prepare('UPDATE ogteklif2 SET durum=? WHERE id=?');
            if ($stmt) {
                $stmt->bind_param('si', $status, $id);
                $ok = $stmt->execute();
                $stmt->close();
                echo json_encode([
                    'success' => $ok,
                    'badge'   => getStatusBadgeClass($status)
                ]);
            } else {
                echo json_encode(['success' => false]);
            }
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    }
    // New Action: Check Badge (read-only)
    else if (isset($_POST['action']) && $_POST['action'] === 'get_badge') {
        $status = trim($_POST['status'] ?? '');
        header('Content-Type: application/json');
        echo json_encode(['badge' => getStatusBadgeClass($status)]);
        exit;
    }
    // 4) Ajax assignment update
    else if (isset($_POST['action']) && $_POST['action'] === 'update_assigned') {
        $assigned = trim($_POST['assigned'] ?? '');
        $id       = (int)($_POST['id'] ?? 0);
        header('Content-Type: application/json');
        if ($id) {
            $stmt = $db->prepare('UPDATE ogteklif2 SET atama=? WHERE id=?');
            if ($stmt) {
                $stmt->bind_param('si', $assigned, $id);
                $ok = $stmt->execute();
                $stmt->close();
                echo json_encode(['success' => $ok]);
            } else {
                echo json_encode(['success' => false]);
            }
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    }
    // 5) Müşteriye mail gönderme (gondereposta)
    else if (isset($_POST['gondereposta'])) {
        $metin    = isset($_POST["metin"]) ? trim($_POST["metin"]) : '';
        $eposta   = isset($_POST["eposta"]) ? trim($_POST["eposta"]) : '';
        $notu     = isset($_POST["notu"]) ? trim($_POST["notu"]) : '';
        $url      = isset($_POST["url"]) ? trim($_POST["url"]) : '';
        $icerikid = isset($_POST["icerikid"]) ? (int)$_POST["icerikid"] : 0;

        gonderEposta($db, $icerikid, $eposta, $metin, $notu, $url);
    }
    // 4) Müşteriye WhatsApp mesajı gönderme
    else if (isset($_POST['gonderwhatsapp'])) {
        $phone    = preg_replace('/[^0-9]/', '', $_POST['phone'] ?? '');
        $message  = $_POST['wmessage'] ?? '';
        $icerikid = isset($_POST['icerikid']) ? (int)$_POST['icerikid'] : 0;

        if ($phone && $message && $icerikid) {
            $stmt = $db->prepare("UPDATE ogteklif2 SET durum='Teklif Gönderildi / Onay Bekleniyor' WHERE id=?");
            if ($stmt) {
                $stmt->bind_param('i', $icerikid);
                $stmt->execute();
                $stmt->close();
            }
            $wa = 'https://wa.me/9' . $phone . '?text=' . urlencode($message);
            header("Location: $wa");
            exit;
        } else {
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Eksik veri'
            ];
            header('Location: teklifsiparisler.php');
            exit;
        }
    }
}

// -----------------------------------------------------------------------------
// 4) TABLO VERİLERİNİ ÇEKEN FONKSİYONLAR
// -----------------------------------------------------------------------------
/**
 * Tabloda gösterilecek teklifleri çeker
 */
function getTekliflerForTable($db, $filterStatus = [], $tradingFilter = '', $bayiFilter = false, $userId = null, $userType = '')
{
    $baseSql = "
        SELECT t.*,
               COALESCE(SUM(CASE WHEN u.doviz='TL'  THEN u.tutar END),0)  AS sum_tl,
               COALESCE(SUM(CASE WHEN u.doviz='EUR' THEN u.tutar END),0) AS sum_eur,
               COALESCE(SUM(CASE WHEN u.doviz='USD' THEN u.tutar END),0) AS sum_usd,
               s.s_adi,
               s.yetkili,
               p.p_cep,
               t.tur
        FROM ogteklif2 t
        LEFT JOIN ogteklifurun2 u ON u.teklifid = t.id
        LEFT JOIN sirket s        ON s.s_arp_code = t.sirket_arp_code
        LEFT JOIN personel p      ON p.personel_id = s.yetkili
        WHERE t.tekliftarihi IS NOT NULL";

    // Yetki Kontrolü: Yönetici değilse sadece kendi oluşturduklarını görsün
    // Not: 'Yönetici' dışındaki tüm roller (Personel, Satış vb.) kısıtlanacak
    if ($userType !== 'Yönetici' && $userType !== 'Bayi' && $userId) {
        $baseSql .= " AND t.hazirlayanid = " . (int)$userId;
    }

    // Bayi filtresi: Sadece bayi siparişlerini göster
    if ($bayiFilter) {
        $baseSql .= " AND t.tur = 'bayi_siparis'";
    } else {
        switch ($tradingFilter) {
            case 'yurtdisi':
                $baseSql .= " AND s.trading_grp LIKE '%yd%'";
                break;
            case 'yurtici':
                // Yurtiçi: trading_grp yurtdışı değilse VEYA NULL ise VEYA bayi_siparis ise
                $baseSql .= " AND (s.trading_grp NOT LIKE '%yd%' OR s.trading_grp IS NULL OR t.tur = 'bayi_siparis')";
                break;
        }
    }

    $params = [];
    $types  = '';
    if (!empty($filterStatus)) {
        $placeholders = implode(',', array_fill(0, count($filterStatus), '?'));
        $baseSql .= " AND t.durum IN ($placeholders)";
        $types  .= str_repeat('s', count($filterStatus));
        $params = $filterStatus;
    }

    $sql = $baseSql . " GROUP BY t.id ORDER BY 
        CASE 
            WHEN t.durum IN ('Yönetici Onayı Bekliyor', 'Yönetici Onayı Bekleniyor') THEN 1
            WHEN t.durum = 'Teklif Oluşturuldu / Gönderilecek' THEN 2
            WHEN t.durum = 'Sipariş Onaylandı / Logoya Aktarım Bekliyor' THEN 4
            ELSE 3
        END ASC,
        t.tekliftarihi DESC, t.id DESC";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        die('SQL Sorgu Hatası: ' . mysqli_error($db));
    }

    $rows = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function getTekliflerForModals($db, $tradingFilter = '', $bayiFilter = false, $userId = null, $userType = '')
{
    $sql = "
        SELECT t.*, s.s_adi, s.yetkili, p.p_cep, p.p_adi, p.p_soyadi, p.p_eposta
        FROM ogteklif2 t
        LEFT JOIN sirket s   ON s.s_arp_code = t.sirket_arp_code
        LEFT JOIN personel p ON p.personel_id = s.yetkili
        WHERE t.tekliftarihi IS NOT NULL"; // WHERE 1=1 yerine bunu ekleyip devam edelim

    // Yetki Kontrolü
    if ($userType !== 'Yönetici' && $userType !== 'Bayi' && $userId) {
        $sql .= " AND t.hazirlayanid = " . (int)$userId;
    }

    // Bayi filtresi: Sadece bayi siparişlerini göster
    if ($bayiFilter) {
        $sql .= " AND t.tur = 'bayi_siparis'";
    } else {
        switch ($tradingFilter) {
            case 'yurtdisi':
                $sql .= " AND s.trading_grp LIKE '%yd%'";
                break;
            case 'yurtici':
                $sql .= " AND (s.trading_grp NOT LIKE '%yd%' OR s.trading_grp IS NULL)";
                break;
            default:
                // Normalde hepsi
                break;
        }
    }

    $sql .= " ORDER BY t.tekliftarihi DESC, t.id DESC";

    $res = mysqli_query($db, $sql);
    $rows = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $rows[] = $row;
        }
    } else {
        die("SQL Sorgu Hatası: " . mysqli_error($db));
    }
    return $rows;
}

/**
 * Durum değerine göre rozet sınıfı döndürür
 */
function getStatusBadgeClass(string $status): string
{
    switch ($status) {
        // Yeşil - Tamamlananlar
        case 'Sipariş Ödeme Alındı / Tamamlandı':
            return 'badge-status-success';
        case 'Sipariş Onaylandı / Logoya Aktarım Bekliyor':
            return 'badge-status-approved';
            
        // Kırmızı - İptal/Red
        case 'Teklif Reddedildi':
            return 'badge-status-rejected';
        case 'Sipariş İptal Edildi':
            return 'badge-status-cancelled';
            
        // Turuncu - Oluşturuldu/Gönderilecek
        case 'Sipariş Oluşturuldu / Gönderilecek':
            return 'badge-status-order-created';
        case 'Teklif Oluşturuldu / Gönderilecek':
            return 'badge-status-quote-created';
            
        // Mavi - Ödeme Bekleniyor
        case 'Sipariş Logoya Aktarıldı / Ödeme Bekleniyor':
        case 'Sipariş Logoya Aktarıldı / Ödemesi Bekleniyor':
            return 'badge-status-payment-waiting';
            
        // Mor - Onay Bekleniyor
        case 'Teklif Gönderildi / Onay Bekleniyor':
            return 'badge-status-approval-waiting';
        case 'Teklif Revize Edildi / Onay Bekleniyor':
            return 'badge-status-revision-approval';
            
        // Kahverengi - Revize Talep
        case 'Teklife Revize Talep Edildi / İnceleme Bekliyor':
            return 'badge-status-revision-request';

        // Özel - Yönetici Onayı (Mor/Lila)
        case 'Yönetici Onayı Bekliyor':
        case 'Yönetici Onayı Bekleniyor':
            return 'badge-status-approval-waiting';
        
        case 'Yönetici Onayladı / Gönderilecek':
            return 'badge-status-approved';
        
        case 'Yönetici Tarafından Red':
            return 'badge-status-rejected';
            
        // Gri - Beklemede
        case 'Beklemede':
            return 'badge-status-pending';
    }

    // Varsayılan
    if (in_array($status, SALES_ACTION_STATUSES, true)) {
        return 'badge-status-warning status-revision';
    }

    $clientExtras = ['Sipariş Onay Bekliyor', 'Teklif Onay Bekleniyor', 'Sipariş Ödemesi Bekleniyor'];
    if (in_array($status, CLIENT_ACTION_STATUSES, true) || in_array($status, $clientExtras, true)) {
        return 'badge-status-warning';
    }

    return 'badge-status-secondary';
}

/**
 * Durum değerine göre ikon sınıfı döndürür
 */
function getStatusIconClass(string $status): string
{
    $status = mb_strtolower($status);
    if (strpos($status, 'tamam') !== false || strpos($status, 'onaylandı') !== false) {
        return 'fa-check';
    }
    if (strpos($status, 'bekle') !== false) {
        return 'fa-clock';
    }
    if (strpos($status, 'iptal') !== false || strpos($status, 'reddedildi') !== false) {
        return 'fa-times';
    }
    if (strpos($status, 'revize') !== false) {
        return 'fa-pencil-alt';
    }
    if (strpos($status, 'onayı bekliyor') !== false || strpos($status, 'onayı bekleniyor') !== false) {
        return 'fa-user-clock'; // Yönetici onayı için ikon
    }
    if (strpos($status, 'yönetici onayladı') !== false) {
        return 'fa-user-check';
    }
    if (strpos($status, 'yönetici tarafından red') !== false) {
        return 'fa-user-times';
    }
    return 'fa-info-circle';
}

/**
 * Durum değerine göre işlem kategorisi döndürür
 */
function getStatusCategory(string $status): string
{
    if (in_array($status, SALES_ACTION_STATUSES, true)) {
        return 'sales';
    }
    if (in_array($status, CLIENT_ACTION_STATUSES, true)) {
        return 'client';
    }
    return 'none';
}

// -----------------------------------------------------------------------------
// 6) POST İŞLEMLERİNİ ÖNCE ÇALIŞTIRALIM
// -----------------------------------------------------------------------------

handleRequests($db, $adsoyad, $yonetici_id_sabit, $zaman, $config);

// -----------------------------------------------------------------------------
// 7) LİSTELEME VE MODALLAR İÇİN VERİLERİ ÇEKELİM
// -----------------------------------------------------------------------------
$filterStatus = [];
if (isset($_GET['status'])) {
    if (is_array($_GET['status'])) {
        $filterStatus = array_map('trim', $_GET['status']);
    } else {
        $filterStatus = array_filter(array_map('trim', explode(',', $_GET['status'])));
    }
}
$filterDate   = isset($_GET['date']) ? trim($_GET['date']) : '';
$bayiFilter = isset($_GET['bayi_filter']) && $_GET['bayi_filter'] == '1';
$tradingFilter = $_GET['trading_filter'] ?? '';
// Varsayılan olarak her zaman "Yurtiçi" seçili olsun
if ($tradingFilter === '' && !$bayiFilter) {
    $tradingFilter = 'yurtici';
}
// Aktif kullanıcı ID'sini al
$currentUserId = $_SESSION['yonetici_id'] ?? 0;

$tekliflerForTable  = getTekliflerForTable($db, $filterStatus, $tradingFilter, $bayiFilter, $currentUserId, $userType);
$tekliflerForModals = getTekliflerForModals($db, $tradingFilter, $bayiFilter, $currentUserId, $userType);

$maxTotalValue = 0;
foreach ($tekliflerForTable as $t) {
    $dolarkuru = is_numeric($t['dolarkur'] ?? null) ? (float)$t['dolarkur'] : 0;
    $eurokuru  = is_numeric($t['eurokur']  ?? null) ? (float)$t['eurokur']  : 0;
    $tller     = is_numeric($t['sum_tl']   ?? null) ? (float)$t['sum_tl']   : 0;
    $eurolar   = is_numeric($t['sum_eur']  ?? null) ? (float)$t['sum_eur']  : 0;
    $dolarlar  = is_numeric($t['sum_usd']  ?? null) ? (float)$t['sum_usd']  : 0;
    $tops      = $tller + $eurolar * $eurokuru + $dolarlar * $dolarkuru;
    if ($tops > $maxTotalValue) {
        $maxTotalValue = $tops;
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title><?php echo htmlspecialchars($sistemayar["title"] ?? ''); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo htmlspecialchars($sistemayar["description"] ?? ''); ?>" name="description" />
    <meta content="<?php echo htmlspecialchars($sistemayar["keywords"] ?? ''); ?>" name="keywords" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <!-- Bootstrap Css -->
    <style>
        /* Durum Badge Renkleri - Her durum için farklı renk */
        .badge-status-success {
            background-color: #28a745 !important;
            color: white !important;
        }
        .badge-status-approved {
            background-color: #20c997 !important;
            color: white !important;
        }
        .badge-status-rejected {
            background-color: #dc3545 !important;
            color: white !important;
        }
        .badge-status-cancelled {
            background-color: #e74c3c !important;
            color: white !important;
        }
        .badge-status-order-created {
            background-color: #fd7e14 !important;
            color: white !important;
        }
        .badge-status-quote-created {
            background-color: #ff9800 !important;
            color: white !important;
        }
        .badge-status-payment-waiting {
            background-color: #0d6efd !important;
            color: white !important;
        }
        .badge-status-approval-waiting {
            background-color: #6f42c1 !important;
            color: white !important;
        }
        .badge-status-revision-approval {
            background-color: #9b59b6 !important;
            color: white !important;
        }
        .badge-status-revision-request {
            background-color: #795548 !important;
            color: white !important;
        }
        .badge-status-pending {
            background-color: #6c757d !important;
            color: white !important;
        }
        .badge-status-warning {
            background-color: #ffc107 !important;
            color: #000 !important;
        }
        .badge-status-secondary {
            background-color: #adb5bd !important;
            color: white !important;
        }
        
        /* Bayi siparişleri için özel stil */
        .bayi-order-row {
            background-color: #e8f4f8 !important;
            border-left: 4px solid #3498db !important;
        }
        .bayi-order-row:hover {
            background-color: #d4ebf2 !important;
        }
        /* Bayi siparişleri için satır rengi - daha belirgin */
        table#datatable tbody tr.bayi-order-row {
            background-color: #e8f4f8 !important;
            border-left: 4px solid #3498db !important;
        }
        table#datatable tbody tr.bayi-order-row:hover {
            background-color: #d4ebf2 !important;
        }
        table#datatable tbody tr.bayi-order-row td {
            background-color: #e8f4f8 !important;
        }
        table#datatable tbody tr.bayi-order-row:hover td {
            background-color: #d4ebf2 !important;
        }
        .row-sales-action {
            background-color: #fff3cd !important;
        }
        .row-client-action {
            background-color: #d1ecf1 !important;
        }
        /* Teklif Verilen sütunu maksimum 15 karakter */
        .col-teklif_verilen {
            max-width: 150px !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
        }
        .col-teklif_verilen span {
            display: inline-block;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
    <link href="assets/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="assets/css/app.min.css" id="app-style" rel="stylesheet" type="text/css" />
    <link href="assets/css/custom.css" rel="stylesheet" type="text/css" />
    <!-- DataTables CSS -->
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <style type="text/css">
        a {
            text-decoration: none;
        }

        .altbos {
            margin-bottom: 2%;
            margin: 1%;
        }

        .numara {
            font-size: 25px;
            font-weight: 700;
        }

        /* DataTables sıralama ikonlarının görünürlüğünü artırmak için */
        th.sorting::after,
        th.sorting_desc::after,
        th.sorting_asc::after {
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            display: inline-block;
            margin-left: 8px;
            content: "\f0dc";
            /* varsayılan sıralama ikonu */
        }

        th.sorting_desc::after {
            content: "\f0d7";
            /* aşağı ok ikonu */
        }

        th.sorting_asc::after {
            content: "\f0d8";
            /* yukarı ok ikonu */
        }

        #datatable thead th {
            position: sticky;
            top: 0;
            background: #fff;
            z-index: 5;
        }

        #datatable td.durum-text {
            white-space: normal;
            line-height: 1.2;
            max-width: 120px;
        }

        #datatable th.col-prepared_by,
        #datatable td.col-prepared_by {
            background: #fff;
        }

        #datatable th.col-assigned,
        #datatable td.col-assigned {
            background: #fff;
        }

        #datatable th.col-logo_no_durum,
        #datatable td.col-logo_no_durum {
            background: #fff;
            width: 40px; /* roughly half the previous width */
            word-break: break-word;
            white-space: normal; /* allow vertical growth */
        }

        #datatable th.col-details,
        #datatable td.col-details {
            width: 30px;
        }

        #datatable th.col-durum,
        #datatable td.col-durum {
            width: 32px; /* reduce horizontal space */
            word-break: break-word;
            white-space: normal;
        }

        /* Not sütunu yoruma alındı */
        /* #datatable th.col-notes,
        #datatable td.col-notes {
            background: #fff;
        } */

        td.dtr-control {
            cursor: pointer;
            text-align: center;
        }
        td.dtr-control::before {
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            content: "\f078"; /* fa-chevron-down */
            pointer-events: none;
        }
        tr.shown td.dtr-control::before {
            content: "\f077"; /* fa-chevron-up */
        }

        #loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            z-index: 1050;
        }
        #loading-overlay.hidden { display: none; }
    </style>
</head>

<body data-layout="horizontal" data-topbar="colored">
    <div id="loading-overlay">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
    <!-- Begin page -->
    <div id="layout-wrapper">
        <header id="page-topbar">
            <?php
            include "menuler/ustmenu.php";
            include "menuler/solmenu.php";
            // if (isset($tanimlar) && $tanimlar == 'Hayır') {
            //     echo '<script language="javascript">window.location="anasayfa.php";</script>';
            //     die();
            // }
            ?>
        </header>

        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-body">
                                    <?php if ($flashMessage): ?>
                                        <div class="alert alert-<?= htmlspecialchars($flashMessage['type']) ?>" role="alert">
                                            <?= $flashMessage['message'] ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h4 class="card-title mb-0">
                                            <?php if ($bayiFilter): ?>
                                                Bayi Siparişleri
                                            <?php else: ?>
                                                SÜREÇLERİ DEVAM EDEN VE Teklif Birimi Tarafından Verilen Teklifler
                                            <?php endif; ?>
                                        </h4>
                                        <?php if (!$bayiFilter): ?>
                                            <?php
                                            // Trading filter'a göre teklif oluştur sayfasını belirle
                                            $teklifOlusturUrl = 'teklif-olustur.php';
                                            if ($tradingFilter === 'yurtdisi') {
                                                $teklifOlusturUrl = 'teklif-olustur.php?pazar_tipi=yurtdisi';
                                            } elseif ($tradingFilter === 'yurtici') {
                                                $teklifOlusturUrl = 'teklif-olustur.php?pazar_tipi=yurtici';
                                            }
                                            ?>
                                            <a href="<?= htmlspecialchars($teklifOlusturUrl) ?>" class="btn btn-primary">
                                                <i class="bx bx-plus-circle me-1"></i> Teklif Oluştur
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <?php
                                    $statusList = [];
                                    $res = mysqli_query($db, "SELECT surec FROM siparissureci ORDER BY id");
                                    if ($res) {
                                        while ($r = mysqli_fetch_assoc($res)) {
                                            $statusList[] = $r['surec'];
                                        }
                                    }
                                    // Ek: bayi tarafından oluşturulan siparişler de farklı durumlarda
                                    // olabilir. Bu siparişlerdeki benzersiz durumları da listeye ekleyelim.
                                    $extraRes = mysqli_query($db, "SELECT DISTINCT durum FROM ogteklif2 WHERE durum <> ''");
                                    if ($extraRes) {
                                        while ($e = mysqli_fetch_assoc($extraRes)) {
                                            if (!in_array($e['durum'], $statusList)) {
                                                $statusList[] = $e['durum'];
                                            }
                                        }
                                    }
                                    $dealerInit = 'Sipariş Oluşturuldu / Gönderilecek';
                                    if (!in_array($dealerInit, $statusList)) {
                                        $statusList[] = $dealerInit;
                                    }
                                    sort($statusList);

                                    $salesActionStatuses  = array_values(array_intersect(SALES_ACTION_STATUSES, $statusList));
                                    $clientActionStatuses = array_values(array_intersect(CLIENT_ACTION_STATUSES, $statusList));
                                    $otherStatuses = array_values(array_diff($statusList, $salesActionStatuses, $clientActionStatuses));

                                    $departmanList = [];
                                    $depRes = mysqli_query($db, "SELECT departman FROM departmanlar");
                                    if ($depRes) {
                                        while ($d = mysqli_fetch_assoc($depRes)) {
                                            $departmanList[] = $d['departman'];
                                        }
                                    }

                                    $preparedByList = [];
                                    $prepRes = mysqli_query($db, "SELECT DISTINCT hazirlayanid FROM ogteklif2 WHERE hazirlayanid <> ''");
                                    if ($prepRes) {
                                        while ($p = mysqli_fetch_assoc($prepRes)) {
                                            $info = $dbManager->resolvePreparer($p['hazirlayanid']);
                                            if ($info['name'] !== '' && !in_array($info['name'], $preparedByList)) {
                                                $preparedByList[] = $info['name'];
                                            }
                                        }
                                    }
                                    sort($preparedByList);
                                    ?>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <?php if (!$bayiFilter): ?>
                                            <div class="d-flex align-items-center gap-2">
                                                <label for="tradingFilter" class="mb-0 fw-bold" style="font-size: 14px;">
                                                    <i class="bx bx-world me-1"></i> Pazar Tipi:
                                                </label>
                                                <select id="tradingFilter" class="form-select form-select-sm" style="width: 150px;">
                                                    <option value="">Tümü</option>
                                                    <option value="yurtici" <?= $tradingFilter === 'yurtici' ? 'selected' : '' ?>>Yurtiçi</option>
                                                    <option value="yurtdisi" <?= $tradingFilter === 'yurtdisi' ? 'selected' : '' ?>>Yurtdışı</option>
                                                </select>
                                            </div>
                                        <?php else: ?>
                                            <div></div>
                                        <?php endif; ?>
                                        <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#filterModal">
                                            <i class="fas fa-filter me-1"></i> Filtreler
                                        </button>
                                    </div>

                                    <div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-xl modal-dialog-scrollable">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="filterModalLabel"><i class="fas fa-filter me-1"></i> Filtreler</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form id="filterPanel">
                                                        <div class="row g-3">
                                                            <div class="col-12">
                                                                <fieldset class="modal-fieldset">
                                                                    <legend>Durum</legend>
                                                                    <div class="mb-1 fw-bold text-danger">Satışçının İşlem Yapması Gerekenler</div>
<?php $i=0; foreach ($salesActionStatuses as $st) { $checked = in_array($st, $filterStatus) ? 'checked' : ''; ?>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input status-check" type="checkbox" id="status_<?= $i ?>" value="<?= htmlspecialchars($st, ENT_QUOTES) ?>" <?= $checked ?> />
                                                                    <label class="form-check-label" for="status_<?= $i ?>"><?= htmlspecialchars($st) ?></label>
                                                                </div>
<?php $i++; } ?>
                                                                <div class="mb-1 fw-bold text-primary mt-2">Müşterinin İşlem Yapması Gerekenler</div>
<?php foreach ($clientActionStatuses as $st) { $checked = in_array($st, $filterStatus) ? 'checked' : ''; ?>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input status-check" type="checkbox" id="status_<?= $i ?>" value="<?= htmlspecialchars($st, ENT_QUOTES) ?>" <?= $checked ?> />
                                                                    <label class="form-check-label" for="status_<?= $i ?>"><?= htmlspecialchars($st) ?></label>
                                                                </div>
<?php $i++; } ?>
                                                                <div class="mb-1 fw-bold text-muted mt-2">Diğer</div>
<?php foreach ($otherStatuses as $st) { $checked = in_array($st, $filterStatus) ? 'checked' : ''; ?>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input status-check" type="checkbox" id="status_<?= $i ?>" value="<?= htmlspecialchars($st, ENT_QUOTES) ?>" <?= $checked ?> />
                                                                    <label class="form-check-label" for="status_<?= $i ?>"><?= htmlspecialchars($st) ?></label>
                                                                </div>
<?php $i++; } ?>
                                                                </fieldset>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label for="dateFilter" class="form-label mb-1">Tarih</label>
                                                                <select id="dateFilter" class="form-select form-select-sm">
                                                                    <option value="">Tüm Tarihler</option>
                                                                    <option value="today">Bugün</option>
                                                                    <option value="7">Son 7 Gün</option>
                                                                    <option value="30">Son 30 Gün</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label for="preparedFilter" class="form-label mb-1">Hazırlayan</label>
                                                                <select id="preparedFilter" class="form-select form-select-sm">
                                                                    <option value="">Tüm Hazırlayanlar</option>
<?php foreach ($preparedByList as $p) { ?>
                                                                    <option value="<?= htmlspecialchars($p, ENT_QUOTES) ?>"><?= htmlspecialchars($p) ?></option>
<?php } ?>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label for="assignedFilter" class="form-label mb-1">Atanan</label>
                                                                <select id="assignedFilter" class="form-select form-select-sm">
                                                                    <option value="">Tüm Departmanlar</option>
<?php foreach ($departmanList as $dep) { ?>
                                                                    <option value="<?= htmlspecialchars($dep, ENT_QUOTES) ?>"><?= htmlspecialchars($dep) ?></option>
<?php } ?>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label for="clientFilter" class="form-label mb-1">Teklif Verilen</label>
                                                                <input type="text" id="clientFilter" class="form-control form-control-sm" placeholder="Müşteri adı">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label mb-1">Toplam Tutar Aralığı (TL)</label>
                                                                <div class="row g-2">
                                                                    <div class="col">
                                                                        <input type="number" step="0.01" id="minTotalFilter" class="form-control form-control-sm total-range-input" placeholder="Min">
                                                                    </div>
                                                                    <div class="col">
                                                                        <input type="number" step="0.01" id="maxTotalFilter" class="form-control form-control-sm total-range-input" placeholder="Max">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                <div class="d-flex justify-content-end mt-3">
                                                    <button type="button" class="btn btn-light me-2" id="resetFilters">Filtreleri Temizle</button>
                                                    <button type="button" class="btn btn-primary" id="applyFilters">Uygula</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                                    <div class="table-responsive">
                                        <?php
                                        $tableColumns = [
                                            // ['key' => 'details',       'title' => '',                         'class' => 'col-details',       'name' => 'details'],
                                            ['key' => 'onay_teklif',   'title' => 'Onay Bilgisi / Teklif No', 'class' => 'col-onay_teklif',   'name' => 'onay_teklif'],
                                            ['key' => 'logo_no_durum', 'title' => 'Logo No / Durum',          'class' => 'col-logo_no_durum', 'name' => 'logo_no_durum'],
                                            ['key' => 'durum',         'title' => 'Durum Kodu',              'class' => 'col-durum',         'name' => 'durum'],
                                            ['key' => 'prepared_by',   'title' => 'Hazırlayan',              'class' => 'col-prepared_by',   'name' => 'prepared_by'],
                                            // Atanan sütunu yoruma alındı
                                            // ['key' => 'assigned',      'title' => 'Atanan',                  'class' => 'col-assigned',      'name' => 'assigned'],
                                            // Not sütunu yoruma alındı
                                            // ['key' => 'notes',         'title' => 'Not',                     'class' => 'col-notes',          'name' => 'notes'],
                                            [
                                                'key'   => 'teklif_verilen',
                                                'title' => 'Teklif Verilen',
                                                'class' => 'col-teklif_verilen',
                                                'name'  => 'teklif_verilen'
                                            ],
                                            ['key' => 'teklif_tarihi', 'title' => 'Teklif Tarihi',           'class' => 'col-teklif_tarihi', 'name' => 'teklif_tarihi'],
                                            ['key' => 'genel_toplam',  'title' => 'Genel Toplam (€)',    'class' => 'col-genel_toplam',  'name' => 'genel_toplam'],
                                            ['key' => 'actions',       'title' => 'İşlem',            'class' => 'col-actions',       'name' => 'actions']
                                        ];
                                        ?>
                                        <table id="datatable" class="table table-bordered table-responsive nowrap table-custom"
                                            style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                            <thead>
                                                <tr>
                                                    <?php foreach ($tableColumns as $col): ?>
                                                        <?php
                                                        $class = $col['class'] ?? '';
                                                        $thAttr = $col['th_attr'] ?? '';
                                                        ?>
                                                        <th class="<?= $class ?>" <?= $thAttr ?>><?= $col['title'] ?></th>
                                                    <?php endforeach; ?>
                                                </tr>
                                            </thead>
                                            <tbody class="yazilar">
                                                <?php if (empty($tekliflerForTable)): ?>
                                                    <!-- DataTables handles empty state automatically -->
                                                <?php else: ?>
                                                    <?php
                                                    // Güncel Euro kurunu bir kez çek (bayi siparişleri için)
                                                    $guncelEuroKuru = 0;
                                                    $kurSorgu = mysqli_query($db, "SELECT eurosatis FROM dovizkuru ORDER BY tarih DESC LIMIT 1");
                                                    if ($kurRow = mysqli_fetch_assoc($kurSorgu)) {
                                                        $guncelEuroKuru = floatval(str_replace(',', '.', $kurRow['eurosatis'] ?? '0'));
                                                    }
                                                    ?>
                                                    <?php foreach ($tekliflerForTable as $dev2): ?>
                                                        <?php
                                                        // --- 1) Teklif ID ---
                                                        $teklifid = isset($dev2['id']) ? (int)$dev2['id'] : 0;

                                                        // --- 2) Döviz kurları ---
                                                        $dolarkuru = is_numeric($dev2['dolarkur'] ?? null) ? (float)$dev2['dolarkur'] : 0;
                                                        $eurokuru  = is_numeric($dev2['eurokur']  ?? null) ? (float)$dev2['eurokur']  : 0;

                                                        // --- 3) Satır toplamlarını TL'ye çevir ---
                                                        $tller   = is_numeric($dev2['sum_tl']   ?? null) ? (float)$dev2['sum_tl']   : 0;
                                                        $eurolar = is_numeric($dev2['sum_eur']  ?? null) ? (float)$dev2['sum_eur']  : 0;
                                                        $dolarlar = is_numeric($dev2['sum_usd'] ?? null) ? (float)$dev2['sum_usd'] : 0;
                                                        $tops    = $tller + $eurolar * $eurokuru + $dolarlar * $dolarkuru;
                                                        
                                                        // Bayi siparişleri için geneltoplam kolonunu kullan (eğer sum_tl, sum_eur, sum_usd yoksa)
                                                        if ($bayiSiparisiMi && $tops == 0) {
                                                            $tops = is_numeric($dev2['geneltoplam'] ?? null) ? (float)$dev2['geneltoplam'] : 0;
                                                        }

                                                        // --- 4) Durum ---

                                                        // --- 5) Hazırlayan ---
                                                          // Bayi siparişleri için hazirlayanid kontrolü
                                                          if ($bayiSiparisiMi) {
                                                              // Bayi siparişi için hazirlayanid'yi direkt kullan
                                                              $hazirlayanId = $dev2["hazirlayanid"] ?? "";
                                                              $prepInfo = $dbManager->resolvePreparer($hazirlayanId);
                                                              // Eğer resolvePreparer bayi bulamazsa, musteriadi'den al
                                                              if (empty($prepInfo["name"]) || $prepInfo["source"] !== "Bayi") {
                                                                  // b2b_users tablosundan direkt çek
                                                                  $hazirlayanIdNum = (int)preg_replace('/\D+/', '', $hazirlayanId);
                                                                  if ($hazirlayanIdNum > 0) {
                                                                      $b2bUser = $dbManager->getB2bUserById($hazirlayanIdNum);
                                                                      if ($b2bUser) {
                                                                          $prepInfo["name"] = $b2bUser['username'] ?? 'Bilinmeyen';
                                                                          $prepInfo["source"] = 'Bayi';
                                                                      }
                                                                  }
                                                              }
                                                          } else {
                                                              $prepInfo = $dbManager->resolvePreparer($dev2["hazirlayanid"] ?? "");
                                                          }
                                                          $hazirlayan = htmlspecialchars($prepInfo["name"] ?: "Bilinmeyen");
                                                          $hazirlayanKaynak = $prepInfo["source"];

                                                        // --- 6) Müşteri ve "Cari Mi?" ---
                                                        $sirketArp = trim($dev2['sirket_arp_code'] ?? '');
                                                        $turKolonu = trim($dev2['tur'] ?? ''); // Bayi siparişi kontrolü için
                                                        $bayiSiparisiMi = ($turKolonu === 'bayi_siparis');
                                                        
                                                        if ($bayiSiparisiMi) {
                                                            // Bayi siparişleri için musteriadi kolonunu kullan
                                                            $musteriAdi = htmlspecialchars($dev2['musteriadi'] ?? 'Bilinmeyen');
                                                            $musteriTel = trim($dev2['projeadi'] ?? '');
                                                            $cariMi     = '<b style="color:orange">BAYİ</b>';
                                                            // Bayi siparişi işareti ekle
                                                            $musteriAdi .= ' <span class="badge bg-info" style="font-size: 10px;" title="B2B Bayi Panelinden Oluşturuldu">🛒 BAYİ</span>';
                                                        } elseif ($sirketArp === '') {
                                                            $musteriAdi = htmlspecialchars($dev2['musteriadi'] ?? 'Bilinmeyen');
                                                            $musteriTel = trim($dev2['projeadi'] ?? '');
                                                            $cariMi     = '<b style="color:red">HAYIR</b>';
                                                        } else {
                                                            $musteriAdi = htmlspecialchars($dev2['s_adi'] ?? 'Bilinmeyen');
                                                            $musteriTel = trim($dev2['p_cep'] ?? '');
                                                            $cariMi     = '<b style="color:green">EVET</b>';
                                                        }
                                                        // Always allow opening the WhatsApp modal so missing
                                                        // phone numbers can be entered manually
                                                        $waAttr = 'data-bs-toggle="modal" data-bs-target=".whatsapp' . $dev2['id'] . '"';
                                                        $rowDurum = isset($dev2['durum']) ? $dev2['durum'] : "";
                                                        $cat = getStatusCategory($rowDurum);
                                                        $rowClass = '';
                                                        if ($cat === 'sales') {
                                                            $rowClass = 'row-sales-action';
                                                        } elseif ($cat === 'client') {
                                                            $rowClass = 'row-client-action';
                                                        }
                                                        
                                                        // Bayi siparişi için özel stil (her zaman ekle)
                                                        if ($bayiSiparisiMi) {
                                                            $rowClass .= ' bayi-order-row';
                                                        }
                                                        ?>
                                                        <tr data-id="<?= $dev2['id']; ?>" class="<?= $rowClass ?>">
                                                            <?php foreach ($tableColumns as $col): ?>
                                                                <?php
                                                                $tdAttr = $col['td_attr'] ?? '';
                                                                $class  = $col['class'] ?? '';
                                                                $cell   = '';
                                                                switch ($col['key']) {
                                                                    case 'details':
                                                                        $cell = '';
                                                                        break;
                                                                    case 'logo_no_durum':
                                                                        ob_start();
                                                                        ?>
                                                                        <?php if (!empty($dev2['number'])): ?>
                                                                            <strong><?= htmlspecialchars($dev2['number']) ?></strong>
                                                                        <?php else: ?>
                                                                            <span class="badge bg-secondary">Beklemede</span>
                                                                        <?php endif; ?>

                                                                        <?php if (!empty($dev2['internal_reference'])): ?>
                                                                            <br><small class="badge bg-success">Aktarıldı (<?= htmlspecialchars($dev2['internal_reference']) ?>)</small>
                                                                        <?php else: ?>
                                                                            <br><small class="badge bg-secondary">Beklemede</small>
                                                                        <?php endif; ?>
                                                                        <?php
                                                                        $cell = ob_get_clean();
                                                                        break;
                                                                    case 'onay_teklif':
                                                                        ob_start();
                                                                        $orderStatus = (int)($dev2['order_status'] ?? 0);
                                                                        $turKolonu = trim($dev2['tur'] ?? '');
                                                                        $bayiSiparisiMi = ($turKolonu === 'bayi_siparis');
                                                                        switch ($orderStatus) {
                                                                            case 4:
                                                                                $statusLabel = 'Sevkedilebilir';
                                                                                break;
                                                                            case 1:
                                                                                // Bayi siparişi ise "Bayi Siparişi", değilse "Öneri" göster
                                                                                $statusLabel = $bayiSiparisiMi ? 'Bayi Siparişi' : 'Öneri';
                                                                                break;
                                                                            case 2:
                                                                                $statusLabel = 'Sevkedilemez';
                                                                                break;
                                                                            case 0:
                                                                                $statusLabel = 'Beklemede';
                                                                                break;
                                                                            default:
                                                                                $statusLabel = '-';
                                                                        }
                                                                        ?>
                                                                        <span><?= htmlspecialchars($statusLabel) ?></span><br>
                                                                        <small><?= htmlspecialchars($dev2['teklifkodu'] ?? '') ?></small>
                                                                        <?php
                                                                        $cell = ob_get_clean();
                                                                        break;
                                                                    // Not case'i yoruma alındı
                                                                    // case 'notes':
                                                                    //     $cell = nl2br(htmlspecialchars($dev2['notes1'] ?? ''));
                                                                    //     break;
                                                                    case 'prepared_by':
                                                                        $cell = htmlspecialchars($hazirlayan) . '<br><small style="font-size:9px;">(' . htmlspecialchars($hazirlayanKaynak) . ')</small>';
                                                                        break;
                                                                    // Atanan case'i yoruma alındı
                                                                    // case 'assigned':
                                                                    //     $assigned = isset($dev2['atama']) ? $dev2['atama'] : '';
                                                                    //     ob_start();
                                                                    //     ?>
                                                                    //     <div class="dropdown assigned-dropdown" data-bs-boundary="viewport" data-bs-container="body">
                                                                    //         <button class="btn btn-sm dropdown-toggle assigned-btn btn-outline-secondary" data-teklif-id="<?= $dev2['id']; ?>" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport" data-bs-container="body" aria-expanded="false">
                                                                    //             <?= ($assigned !== '') ? htmlspecialchars($assigned) : 'Belirtilmemiş' ?>
                                                                    //         </button>
                                                                    //         <ul class="dropdown-menu">
                                                                    //             <?php foreach ($departmanList as $dep): ?>
                                                                    //                 <li><a class="dropdown-item assigned-option" data-assigned="<?= htmlspecialchars($dep) ?>" href="#"><?= htmlspecialchars($dep) ?></a></li>
                                                                    //             <?php endforeach; ?>
                                                                    //         </ul>
                                                                    //     </div>
                                                                    //     <?php
                                                                    //     $cell = ob_get_clean();
                                                                    //     break;
                                                                    case 'durum':
                                                                        $badgeClass = getStatusBadgeClass($rowDurum);
                                                                        $iconClass  = getStatusIconClass($rowDurum);
                                                                        
                                                                        // Yönetici onayı bekleyen tekliflerde, yönetici değilse dropdown'ı engelle
                                                                        $isPendingApproval = ($rowDurum === 'Yönetici Onayı Bekleniyor' || $rowDurum === 'Yönetici Onayı Bekliyor');
                                                                        $canChangeStatus = ($userType === 'Yönetici' || !$isPendingApproval);
                                                                        
                                                                        ob_start();
                                                                        if ($userType !== 'Bayi' && $canChangeStatus) {
                                                                        ?>
                                                                        <div class="dropdown status-dropdown" id="status-container-<?= $dev2['id'] ?>" data-bs-boundary="viewport" data-bs-container="body">
                                                                            <button class="btn btn-sm dropdown-toggle status-btn <?= $badgeClass ?>" id="status-btn-<?= $dev2['id'] ?>" data-teklif-id="<?= $dev2['id']; ?>" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport" data-bs-container="body" aria-expanded="false">
                                                                                <i class="fas <?= $iconClass ?> me-1"></i><span class="status-text"><?= ($rowDurum !== "") ? htmlspecialchars($rowDurum) : "Belirtilmemiş" ?></span>
                                                                            </button>
                                                                            <ul class="dropdown-menu">
                                                                                <?php foreach ($statusList as $st): ?>
                                                                                    <li><a class="dropdown-item status-option" data-status="<?= htmlspecialchars($st) ?>" href="#"><i class="fas <?= getStatusIconClass($st) ?> me-1"></i><?= htmlspecialchars($st) ?></a></li>
                                                                                <?php endforeach; ?>
                                                                            </ul>
                                                                        </div>
                                                                        <?php
                                                                        } else {
                                                                            // Değiştirilemez durum (Bayi veya Yetkisiz Personel)
                                                                            echo '<span class="badge ' . $badgeClass . '" id="status-badge-readonly-' . $dev2['id'] . '" style="font-size:12px; padding:8px 12px; display:inline-block;"><i class="fas ' . $iconClass . ' me-1"></i><span class="status-text">' . (($rowDurum !== "") ? htmlspecialchars($rowDurum) : "Belirtilmemiş") . '</span></span>';
                                                                        }
                                                                        $cell = ob_get_clean();
                                                                        break;
                                                                    case 'teklif_no':
                                                                        $cell = htmlspecialchars($dev2['teklifkodu'] ?? '');
                                                                        break;
                                                                    case 'teklif_verilen':
                                                                        // Bayi siparişleri için de müşteri adını göster
                                                                        $text = htmlspecialchars($musteriAdi ?? '');
                                                                        // Maksimum 15 karakter göster, geri kalanı ...
                                                                        $displayText = mb_strlen($text) > 15 ? mb_substr($text, 0, 15) . '...' : $text;
                                                                        $cell = '<span data-order="' . $text . '" title="' . htmlspecialchars($text) . '">' . $displayText . '</span>';
                                                                        break;
                                                                    case 'teklif_tarihi':
                                                                        $rawDate    = $dev2['tekliftarihi'] ?? '';
                                                                        $displayDate = '-';
                                                                        $orderDate   = '';
                                                                        if (!empty($rawDate)) {
                                                                            $ts = strtotime($rawDate);
                                                                            if ($ts) {
                                                                                $displayDate = date('d.m.Y', $ts);
                                                                                $orderDate   = date('Y-m-d', $ts);
                                                                            }
                                                                        }
                                                                        $cell = '<span data-order="' . $orderDate . '">' . htmlspecialchars($displayDate) . '</span>';
                                                                        break;
                                                                    case 'genel_toplam':
                                                                        // Tüm siparişler için aynı format: Euro cinsinden göster (KDV dahil) + TL fiyatı
                                                                        if ($bayiSiparisiMi) {
                                                                            // Bayi siparişi için geneltoplam kolonunu kullan (TL cinsinden, Euro'ya çevir)
                                                                            $genelToplamTL = is_numeric($dev2['geneltoplam'] ?? null) ? (float)$dev2['geneltoplam'] : 0;
                                                                            
                                                                            // Euro kurunu al - önce siparişten, yoksa güncel kuru kullan
                                                                            $bayiEuroKuru = $eurokuru;
                                                                            if ($bayiEuroKuru <= 0) {
                                                                                // Güncel Euro kurunu kullan (döngü dışında çekildi)
                                                                                $bayiEuroKuru = $guncelEuroKuru;
                                                                            }
                                                                            
                                                                            // Her zaman Euro ve TL'yi birlikte göster
                                                                            if ($genelToplamTL > 0 && $bayiEuroKuru > 0) {
                                                                                $genelToplamEUR = $genelToplamTL / $bayiEuroKuru;
                                                                                $display = '<strong style="color:#0056b3; font-weight:bold;">' . number_format($genelToplamEUR, 2, ',', '.') . ' €</strong>';
                                                                                $display .= '<br><small style="color:#666;">' . number_format($genelToplamTL, 2, ',', '.') . ' ₺</small>';
                                                                            } elseif ($genelToplamTL > 0) {
                                                                                // Euro kuru yoksa sadece TL göster
                                                                                $display = '<strong style="color:#0056b3; font-weight:bold;">' . number_format($genelToplamTL, 2, ',', '.') . ' ₺</strong>';
                                                                            } else {
                                                                                $display = '-';
                                                                            }
                                                                            $orderVal = ($genelToplamTL > 0 && $bayiEuroKuru > 0) ? number_format($genelToplamEUR, 2, '.', '') : ($genelToplamTL > 0 ? number_format($genelToplamTL, 2, '.', '') : 0);
                                                                        } else {
                                                                            // Yurtdışı ve yurtiçi siparişler için Euro cinsinden göster (KDV dahil) + TL fiyatı
                                                                            // $tops zaten TL cinsinden, Euro'ya çevir
                                                                            $genelToplamTL = $tops * 1.20; // KDV dahil TL
                                                                            $genelToplamEUR = $eurokuru > 0 ? $genelToplamTL / $eurokuru : 0; // TL'yi Euro'ya çevir
                                                                            if ($genelToplamEUR > 0) {
                                                                                $display = '<strong style="color:#0056b3; font-weight:bold;">' . number_format($genelToplamEUR, 2, ',', '.') . ' €</strong>';
                                                                                $display .= '<br><small style="color:#666;">' . number_format($genelToplamTL, 2, ',', '.') . ' ₺</small>';
                                                                            } else {
                                                                                $display = $genelToplamTL > 0 ? number_format($genelToplamTL, 2, ',', '.') . ' ₺' : '-';
                                                                            }
                                                                            $orderVal = $genelToplamEUR > 0 ? number_format($genelToplamEUR, 2, '.', '') : ($genelToplamTL > 0 ? number_format($genelToplamTL, 2, '.', '') : 0);
                                                                        }
                                                                        $cell = '<span data-order="' . $orderVal . '">' . $display . '</span>';
                                                                        break;
                                                                    case 'actions':
                                                                        $sta = (strpos($rowDurum, 'Sipariş') === 0) ? 'Sipariş' : 'Teklif';
                                                                        ob_start();
                                                                        ?>
                                                                        <div class="btn-group" role="group">
                                                                            <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target=".gonder<?= $dev2['id']; ?>"><i class="fas fa-envelope"></i></button>
                                                                            <button type="button" class="btn btn-outline-success btn-sm" <?= $waAttr ?>><i class="fab fa-whatsapp"></i></button>
                                                                            <button type="button" class="btn btn-outline-dark btn-sm" data-bs-toggle="modal" data-bs-target=".info<?= $dev2['id']; ?>"><i class="fas fa-info-circle"></i></button>
                                                                            <a target="_blank" class="btn btn-outline-primary btn-sm" href="offer_detail.php?te=<?= urlencode($dev2['id']); ?>&sta=<?= $sta; ?>"><i class="fas fa-eye"></i></a>
                                                                            <a target="_blank" class="btn btn-outline-secondary btn-sm" href="teklifsiparisler-duzenle.php?te=<?= urlencode($dev2['id']); ?>&sta=<?= $sta; ?>"><i class="fas fa-edit"></i></a>
                                                                        </div>
                                                                        <?php
                                                                        $cell = ob_get_clean();
                                                                        break;
                                                                    default:
                                                                        $cell = '';
                                                                }
                                                                ?>
                                                                <td class="<?= $class ?>" <?= $tdAttr ?><?php
                                                                    if ($col['key'] === 'durum') {
                                                                        echo ' data-status="' . htmlspecialchars($rowDurum) . '"';
                                                                    }
                                                                    if ($col['key'] === 'assigned') {
                                                                        echo ' data-assigned="' . htmlspecialchars($assigned) . '"';
                                                                    }
                                                                    if ($col['key'] === 'prepared_by') {
                                                                        echo ' data-prepared="' . htmlspecialchars($hazirlayan) . '"';
                                                                    }
                                                                ?>><?= $cell ?></td>
                                                            <?php endforeach; ?>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                </div> <!-- Card-Body Bitişi -->
                            </div> <!-- Card -->
                        </div> <!-- col-lg-12 -->
                    </div> <!-- row -->
                </div> <!-- container-fluid -->
            </div>
            <!-- End Page-content -->
            <?php include "menuler/footer.php"; ?>
        </div>
        <!-- end main content-->
    </div>
    <!-- END layout-wrapper -->

    <?php
    // -------------------------------------------------------------------------
    // Modal: DEPARTMAN ATAMA (yanı "yenikategori")
    // Tek seferde verileri çektik, $tekliflerForModals üzerinden dönüyoruz.
    // -------------------------------------------------------------------------
    foreach ($tekliflerForModals as $markalar) {
        $departmanlar = [];
        $kontrolKullaniciAdi32 = mysqli_query($db, "SELECT * FROM departmanlar");
        if ($kontrolKullaniciAdi32) {
            while ($departman = mysqli_fetch_array($kontrolKullaniciAdi32)) {
                $departmanlar[] = htmlspecialchars($departman["departman"]);
            }
        }
    ?>
        <div class="modal fade yenikategori<?php echo htmlspecialchars($markalar["id"] ?? ''); ?>" tabindex="-1" role="dialog"
            aria-labelledby="myLargeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <b><?php echo htmlspecialchars($markalar["teklifkodu"] ?? ''); ?></b>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="post" action="teklifsiparisler.php" class="needs-validation assign-form" novalidate>
                        <input type="hidden" name="action" value="update_assigned">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Hangi Departmana Atansın?</label>
                                        <select name="departman" class="form-control" required>
                                            <option value="" disabled>-- Departman Seçiniz --</option>
                                            <?php
                                            foreach ($departmanlar as $departmanSecenek) {
                                                $selected = ($markalar["atama"] == $departmanSecenek) ? 'selected' : '';
                                                echo "<option value='{$departmanSecenek}' {$selected}>{$departmanSecenek}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <input type="hidden" name="icerikid" value="<?php echo htmlspecialchars($markalar["id"] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Vazgeçtim, Kapat</button>
                            <button type="submit" class="btn btn-success">Düzenleyin!</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php } ?>

    <?php
    // -------------------------------------------------------------------------
    // Modal: SİPARİŞ NEREDE (yanı "sonislem")
    // -------------------------------------------------------------------------
    foreach ($tekliflerForModals as $teklif) {
        $siparishazir      = isset($teklif["siparishazir"]) ? $teklif["siparishazir"] : 'Hayır';
        $faturaolustu      = isset($teklif["faturaolustu"]) ? $teklif["faturaolustu"] : 'Hayır';
        $satinalmayagonder = isset($teklif["satinalmayagonder"]) ? $teklif["satinalmayagonder"] : 'Hayır';
        $satinalmanotu     = isset($teklif["satinalmanotu"]) ? $teklif["satinalmanotu"] : '';
        $eksikmalzeme      = isset($teklif["eksikmalzeme"]) ? $teklif["eksikmalzeme"] : 'Hayır';
        $depodabeklemede   = isset($teklif["depodabeklemede"]) ? $teklif["depodabeklemede"] : 'Hayır';
        $aracayuklendi     = isset($teklif["aracayuklendi"]) ? $teklif["aracayuklendi"] : 'Hayır';
        $aractasevkiyatta  = isset($teklif["aractasevkiyatta"]) ? $teklif["aractasevkiyatta"] : 'Hayır';
        $islemtamamlandi   = isset($teklif["islemtamamlandi"]) ? $teklif["islemtamamlandi"] : 'Hayır';
    ?>
        <div class="modal fade sonislem<?php echo htmlspecialchars($teklif["id"] ?? ''); ?>" tabindex="-1" role="dialog"
            aria-labelledby="myLargeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <b><?php echo htmlspecialchars($teklif["teklifkodu"] ?? ''); ?></b> Son Atanan:
                            <?php echo htmlspecialchars($teklif["atama"] ?? ''); ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <!-- Aşamaların Görünürlüğünü Sağlama -->
                            <div class="col-md-3 altbos"
                                style="border:3px solid <?php echo ($siparishazir == 'Evet') ? 'green' : 'red'; ?>;">
                                <h3 class="numara">1. <small>Aşama</small></h3>
                                <img src="images/siparis.png" style="width:100%; height:100px">
                                <center><b>Sipariş Hazır Mı?</b></center>
                            </div>
                            <div class="col-md-3 altbos"
                                style="border:3px solid <?php echo ($faturaolustu == 'Evet') ? 'green' : 'red'; ?>;">
                                <h3 class="numara">2. <small>Aşama</small></h3>
                                <img src="images/fatura.png" style="width:100%; height:100px">
                                <center><b>Fatura / İrsaliye Hazır Mı?</b></center>
                            </div>
                            <?php if ($satinalmayagonder == 'Evet') { ?>
                                <div class="col-md-3 altbos"
                                    style="border:3px solid <?php echo ($satinalmayagonder == 'Evet') ? 'green' : 'red'; ?>;">
                                    <h3 class="numara">3. <small>Aşama</small></h3>
                                    <img src="images/satinalma.png" style="width:100%; height:100px">
                                    <center><b>Satınalmaya Gönderildi</b></center>
                                </div>
                                <div class="col-md-3 altbos"
                                    style="border:3px solid <?php echo ($eksikmalzeme == 'Evet') ? 'green' : 'red'; ?>;">
                                    <h3 class="numara">4. <small>Aşama</small></h3>
                                    <img src="images/eksikmalzeme.png" style="width:100%; height:100px">
                                    <center><b>Eksik Malzeme Bekleniyor</b></center>
                                </div>
                            <?php } ?>
                            <div class="col-md-3 altbos"
                                style="border:3px solid <?php echo ($depodabeklemede == 'Evet') ? 'green' : 'red'; ?>;">
                                <h3 class="numara">5. <small>Aşama</small></h3>
                                <img src="images/beklemede.png" style="width:100%; height:100px">
                                <center><b>Depoda Beklemeye Alındı</b></center>
                            </div>
                            <div class="col-md-3 altbos"
                                style="border:3px solid <?php echo ($aracayuklendi == 'Evet') ? 'green' : 'red'; ?>;">
                                <h3 class="numara">6. <small>Aşama</small></h3>
                                <img src="images/aracayuklendi.png" style="width:100%; height:100px">
                                <center><b>Araca Yüklendi</b></center>
                            </div>
                            <div class="col-md-3 altbos"
                                style="border:3px solid <?php echo ($aractasevkiyatta == 'Evet') ? 'green' : 'red'; ?>;">
                                <h3 class="numara">7. <small>Aşama</small></h3>
                                <img src="images/sevkiyatta.png" style="width:100%; height:100px">
                                <center><b>Araç Sevkiyatta</b></center>
                            </div>
                            <div class="col-md-3 altbos"
                                style="border:3px solid <?php echo ($islemtamamlandi == 'Evet') ? 'green' : 'red'; ?>;">
                                <h3 class="numara">8. <small>Aşama</small></h3>
                                <img src="images/teslimedildi.png" style="width:100%; height:100px">
                                <center><b>İşlem Tamamlandı</b></center>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                            Anladım, Kapat
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>

    <?php
    // -------------------------------------------------------------------------
    // Modal: DURUM - STATÜ - ÖDEME (yanı "durum")
    // -------------------------------------------------------------------------
    foreach ($tekliflerForModals as $markalar) {
        // Güvenli dizi erişimi
        $durum = isset($markalar["durum"]) ? htmlspecialchars($markalar["durum"]) : '';
        $statu = isset($markalar["statu"]) ? htmlspecialchars($markalar["statu"]) : '';
        $odemetipi = isset($markalar["odemetipi"]) ? htmlspecialchars($markalar["odemetipi"]) : '';
    ?>
        <div class="modal fade durum<?php echo htmlspecialchars($markalar["id"] ?? ''); ?>" tabindex="-1" role="dialog"
            aria-labelledby="myLargeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <b><?php echo htmlspecialchars($markalar["teklifkodu"] ?? ''); ?></b><br>
                            <b>Durumu: <?php echo $durum; ?></b><br>
                            <b>Statu: <?php echo $statu; ?></b>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="post" action="teklifsiparisler.php" class="needs-validation status-form" novalidate>
                        <input type="hidden" name="action" value="update_status">
                        <div class="modal-body">
                            <div class="row">
                                <!-- Durum Seçimi -->
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">İşlem Durumu Nedir?</label>
                                        <select name="durum" class="form-control" required>
                                            <option value="" disabled>-- Durum Seçiniz --</option>
                                            <?php
                                            $durumsor = mysqli_query($db, "SELECT * FROM siparissureci");
                                            if ($durumsor) {
                                                while ($durumlar = mysqli_fetch_array($durumsor)) {
                                                    $durumValue = isset($durumlar["surec"]) ? htmlspecialchars($durumlar["surec"]) : '';
                                                    $selected = ($durumlar["surec"] == $markalar["durum"]) ? 'selected' : '';
                                                    echo "<option value='" . $durumValue . "' " . $selected . ">" . $durumValue . "</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <!-- Statü -->
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">İşlem Statüsü Nedir?</label>
                                        <textarea class="form-control" name="statu" style="min-height:80px;"
                                            placeholder="İşlemin Nedenini Açıklayınız"><?php echo htmlspecialchars($statu); ?></textarea>
                                    </div>
                                </div>
                                <!-- Ödeme Türü -->
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Ödeme Türü Nedir?</label>
                                        <select name="odemenot" class="form-control" required>
                                            <option value="" disabled>-- Ödeme Seçiniz --</option>
                                            <?php
                                            $odemeSecenekleri = [
                                                "Bilinmiyor",
                                                "Peşin Ödeme",
                                                "Kredi Kartı Ödeme",
                                                "30 Gün Vade",
                                                "60 Gün Vade",
                                                "90 Gün Vade",
                                                "%50 Peşinat ile Satış",
                                                "Hak Edişe Göre Ödeme",
                                                "7 Gün İçerisinde",
                                                "15 Gün İçerisinde"
                                            ];
                                            foreach ($odemeSecenekleri as $odeme) {
                                                $odemeSafe = htmlspecialchars($odeme);
                                                $selected = ($markalar["odemetipi"] == $odeme) ? 'selected' : '';
                                                echo "<option value='{$odemeSafe}' {$selected}>{$odemeSafe}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <input type="hidden" name="icerikid" value="<?php echo htmlspecialchars($markalar["id"] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Vazgeçtim, Kapat</button>
                            <button type="submit" class="btn btn-success">Düzenleyin!</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php } ?>

    <?php
    // -------------------------------------------------------------------------
    // Modal: MÜŞTERİYE E-POSTA GÖNDERME (yanı "gonder")
    // -------------------------------------------------------------------------
    foreach ($tekliflerForModals as $markalar) {
        $sirketArp = isset($markalar["sirket_arp_code"]) ? trim($markalar["sirket_arp_code"]) : '';
        $epostaVarsayilan = '';
        if ($sirketArp !== '') {
            $epostaVarsayilan = isset($markalar['p_eposta']) ? htmlspecialchars($markalar['p_eposta']) : '';
        }
        $musteriId = $markalar["musteriid"] ?? '';
        if ($sirketArp === '') {
            $kimehazir = $markalar["musteriadi"] ?? '';
            $cep       = trim($markalar["projeadi"] ?? '');
        } else {
            $kimehazir = trim(($markalar['p_adi'] ?? '') . ' ' . ($markalar['p_soyadi'] ?? ''));
            $cep       = trim($markalar['p_cep'] ?? '');
            if (empty($epostaVarsayilan)) {
                $epostaVarsayilan = isset($markalar['p_eposta']) ? htmlspecialchars($markalar['p_eposta']) : '';
            }
        }
        $tekkod = isset($markalar["teklifkodu"]) ? htmlspecialchars($markalar["teklifkodu"]) : 'Bilinmeyen';
        $durr = isset($markalar["durum"]) ? htmlspecialchars($markalar["durum"]) : '';

        // Yurtdışı/İngilizce Kontrolü
        $isForeignGen = false;
        if (isset($_GET['trading_filter']) && $_GET['trading_filter'] == 'yurtdisi') {
            $isForeignGen = true;
        } else if (strpos($sirketArp, '120.02') === 0 || strpos($sirketArp, '320.02') === 0) {
             $isForeignGen = true;
        }

        if ($isForeignGen) {
            $metin = 'The current status of your offer is “' . $durr . '”. You may also review your offer by using the following link:';
        } else {
            $metin2 = ' numaralı teklifinizin durumu ' . $durr . ' olarak belirtilmiştir. Teklifi incelemek için aşağıdaki url adresini kullanabilirsiniz.';
            $metin  = $kimehazir . ' ' . $metin2;
        }

        include "include/url.php";
        $gonderUrl  = isset($url) ? $url . '/offer_detail.php?te=' . urlencode($markalar["id"]) . '&sta=Teklif' : '#';
        if ($isForeignGen) {
             $gonderUrl .= '&lang=en';
        }
    ?>
        <div class="modal fade gonder<?php echo htmlspecialchars($markalar["id"] ?? ''); ?>" tabindex="-1" role="dialog"
            aria-labelledby="myLargeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <b><?php echo htmlspecialchars($markalar["teklifkodu"] ?? ''); ?></b><br>
                            <b>Durumu: <?php echo $durum; ?></b> /
                            <b>Statu: <?php echo $statu; ?></b><br>
                            Müşteriye Teklifi Mail Olarak İletin.
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="post" action="teklifsiparisler.php" class="needs-validation" novalidate>
                        <div class="modal-body">
                            <div class="row">
                                <!-- Mail Adresi -->
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Mail Adresi?</label>
                                        <input class="form-control" name="eposta"
                                            placeholder="Müşteri Mail Adresi Nedir?"
                                            value="<?php echo $epostaVarsayilan; ?>" required />
                                    </div>
                                </div>
                                <!-- Not Alanı -->
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Eklemek İstediğiniz Not Var Mı?</label>
                                        <textarea name="notu" class="form-control" style="min-height:80px;"></textarea>
                                    </div>
                                </div>

                                <input type="hidden" name="url" value="<?php echo htmlspecialchars($gonderUrl); ?>">
                                <input type="hidden" name="metin" value="<?php echo htmlspecialchars($metin); ?>">
                                <input type="hidden" name="icerikid" value="<?php echo htmlspecialchars($markalar["id"] ?? ''); ?>">
                                <?php if(isset($_GET['trading_filter']) && $_GET['trading_filter'] == 'yurtdisi') { ?>
                                    <input type="hidden" name="force_english" value="1">
                                <?php } ?>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                                Vazgeçtim, Kapat
                            </button>
                            <button type="submit" name="gondereposta" class="btn btn-success">
                                Gönderin!
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php } ?>

    <?php
    foreach ($tekliflerForModals as $markalar) {
        $sirketArp = isset($markalar["sirket_arp_code"]) ? trim($markalar["sirket_arp_code"]) : '';
        if ($sirketArp === '') {
            $kimehazir = $markalar["musteriadi"] ?? '';
            $cep       = trim($markalar["projeadi"] ?? '');
        } else {
            $kimehazir = trim(($markalar['p_adi'] ?? '') . ' ' . ($markalar['p_soyadi'] ?? ''));
            $cep       = trim($markalar['p_cep'] ?? '');
        }
        $tekkod = $markalar["teklifkodu"] ?? '';
        include "include/url.php";
        $mesaj  = getWhatsappMessage($db, (int)$markalar['id']);
    ?>
        <div class="modal fade whatsapp<?php echo $markalar['id']; ?>" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><b><?php echo htmlspecialchars($markalar['teklifkodu']); ?></b> WhatsApp Mesajı</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="post" action="teklifsiparisler.php" class="needs-validation" novalidate>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Telefon</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($cep); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mesaj</label>
                                <textarea name="wmessage" class="form-control" rows="4" required><?php echo htmlspecialchars($mesaj); ?></textarea>
                            </div>
                            <input type="hidden" name="icerikid" value="<?php echo htmlspecialchars($markalar['id']); ?>">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Vazgeçtim, Kapat</button>
                            <button type="submit" name="gonderwhatsapp" class="btn btn-success">Gönderin!</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php } ?>

    <?php
    // -------------------------------------------------------------------------
    // Modal: MÜŞTERİ BİLGİLERİ (info)
    // -------------------------------------------------------------------------
    foreach ($tekliflerForModals as $info) {
        $sirketArp = isset($info["sirket_arp_code"]) ? trim($info["sirket_arp_code"]) : '';
        if ($sirketArp === '') {
            $musteriTel = trim($info["projeadi"] ?? '');
            $cariMiText = 'HAYIR';
        } else {
            $musteriTel = trim($info['p_cep'] ?? '');
            $cariMiText = 'EVET';
        }
    ?>
        <div class="modal fade info<?= $info['id']; ?>" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Müşteri Bilgileri</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Müşteri Telefon:</strong> <?= htmlspecialchars($musteriTel ?: '-') ?></p>
                        <p><strong>Cari Mi?:</strong> <?= htmlspecialchars($cariMiText) ?></p>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>

    <!-- Right bar overlay-->
    <div class="rightbar-overlay"></div>
    <!-- JAVASCRIPT -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/metismenu/metisMenu.min.js"></script>
    <script src="assets/libs/simplebar/simplebar.min.js"></script>
    <script src="assets/libs/node-waves/waves.min.js"></script>
    <script src="assets/libs/waypoints/lib/jquery.waypoints.min.js"></script>
    <script src="assets/libs/jquery.counterup/jquery.counterup.min.js"></script>
    <!-- apexcharts -->
    <script src="assets/libs/apexcharts/apexcharts.min.js"></script>
    <script src="assets/js/pages/dashboard.init.js"></script>
    <!-- App js -->
    <script src="assets/js/app.js"></script>
    <!-- DataTables -->
    <script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="assets/libs/datatables.net-buttons/js/buttons.html5.min.js"></script>
    <script src="assets/libs/datatables.net-buttons/js/buttons.print.min.js"></script>
    <script src="assets/libs/datatables.net-buttons/js/buttons.colVis.min.js"></script>
    <script src="assets/libs/jszip/jszip.min.js"></script>
    <script src="assets/libs/pdfmake/build/pdfmake.min.js"></script>
    <script src="assets/libs/pdfmake/build/vfs_fonts.js"></script>
    <script src="assets/libs/moment/min/moment.min.js"></script>
    <script src="assets/libs/datatables.net-plugins/sorting/datetime-moment.js"></script>
    <script src="assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>
    <script src="assets/libs/parsleyjs/parsley.min.js"></script>

    <!-- DataTables Başlangıç Ayarları -->
    <script>
        $(document).ready(function() {
            // DEBUG: Check column counts
            var thCount = $('#datatable thead tr:first th').length;
            var tdCount = $('#datatable tbody tr:first td').length;
            console.log("Header Columns: " + thCount + ", Body Columns: " + tdCount);
            
            if (tdCount > 0 && thCount !== tdCount) {
                alert("TABLO HATASI TESPİT EDİLDİ:\nBaşlık Sütun Sayısı: " + thCount + "\nSatır Sütun Sayısı: " + tdCount + "\n\nLütfen bu bilgiyi yöneticiye iletiniz.");
            }

            // Ensure modals are not constrained by scrollable containers
            $(document).on('show.bs.modal', '.modal', function () {
                $(this).appendTo('body');
            });
            $.fn.dataTable.moment('DD.MM.YYYY');
            var durumIdx          = $('#datatable thead th.col-durum').index();
            var assignedIdx       = $('#datatable thead th.col-assigned').index();
            var teklifVerilenIdx  = $('#datatable thead th.col-teklif_verilen').index();
            var tarihIdx          = $('#datatable thead th.col-teklif_tarihi').index();
            var genelIdx          = $('#datatable thead th.col-genel_toplam').index();
            try {
                if (tarihIdx === -1) tarihIdx = 0;
                
                // Index safety checks
                if (teklifVerilenIdx === -1) console.warn("Teklif Verilen column not found");
                if (genelIdx === -1) console.warn("Genel Toplam column not found");

                var table = $('#datatable').DataTable({
                    order: [[ tarihIdx, 'desc' ]],
                    columns: [
    <?php foreach ($tableColumns as $col): ?>
                        { name: '<?= $col['name'] ?>' },
    <?php endforeach; ?>
                    ],
                    responsive: false,
                    scrollX: true,
                    autoWidth: true,
                    dom: "<'row mb-2'<'col-md-8'f><'col-md-4 text-end'B>>" +
                         "<'row'<'col-12'tr>>" +
                         "<'row'<'col-sm-5'i><'col-sm-7'p>>",
                    buttons: [
                        { extend: 'colvis', text: 'Sütunlar' },
                        { extend: 'excel', text: '<i class="fa fa-file-excel"></i> Excel' },
                        { extend: 'print', text: '<i class="fa fa-print"></i> Yazdır' }
                    ],
                    columnDefs: [
                        // { targets: 0, className: 'dtr-control', orderable: false, searchable: false, visible: false },
                        { targets: ['notes:name'], visible: false },
                        { targets: (teklifVerilenIdx > -1 ? teklifVerilenIdx : 0), type: 'string', orderable: true, visible: true },
                        { targets: tarihIdx, type: 'datetime-moment', orderable: true, visible: true },
                        { targets: (genelIdx > -1 ? genelIdx : 0), type: 'num', orderable: true, visible: true },
                        { orderable: true, targets: '_all' },
                        // Tüm sütunların görünür olduğundan emin ol
                        { targets: '_all', visible: true }
                    ],
                    language: {
                        search: "",
                        lengthMenu: "_MENU_ kayıt göster",
                        info: "_TOTAL_ kayıttan _START_ - _END_ arası gösteriliyor",
                        infoEmpty: "Kayıt yok",
                        emptyTable: "Tabloda veri yok",
                        paginate: {
                            first: "İlk",
                            previous: "Önceki",
                            next: "Sonraki",
                            last: "Son"
                        }
                    }
                    // footer filters removed
                });
                // Reveal the table and cloned header after DataTables init
                $('#datatable, #datatable_wrapper table').css('visibility', 'visible');
            } catch (err) {
                console.error("DataTable Init Error:", err);
                alert("Tablo yüklenirken bir hata oluştu: " + err.message);
            } finally {
                $('#loading-overlay').addClass('hidden');
            }
            
            // Fail-safe to remove overlay in case of weird errors
            setTimeout(function() {
                $('#loading-overlay').addClass('hidden');
            }, 2000);

            $('.dataTables_filter').addClass('d-flex align-items-center');
            $('.dataTables_filter label').addClass('position-relative w-100 mb-0');
            $('.dataTables_filter label').contents().filter(function(){return this.nodeType===3;}).remove();
            $('.dataTables_filter input').addClass('form-control form-control-sm ps-4').attr('placeholder','Ara');
            $('.dataTables_filter label').prepend('<span class="fas fa-search position-absolute top-50 start-0 translate-middle-y text-muted ms-2"></span>');

            // Ensure dropdowns are appended to body so they aren't clipped
            function initDropdowns() {
                $('.status-dropdown .status-btn, .assigned-dropdown .assigned-btn').each(function(){
                    new bootstrap.Dropdown(this, { boundary: 'viewport', container: 'body' });
                });
            }
            initDropdowns();
            table.on('draw.dt', initDropdowns);

            $('#tradingFilter').on('change', function(){
                var params = new URLSearchParams(window.location.search);
                var val = $(this).val();
                // Bayi filtresini temizle (çünkü trading filter kullanılıyor)
                params.delete('bayi_filter');
                if (val) {
                    params.set('trading_filter', val);
                } else {
                    params.delete('trading_filter');
                }
                window.location.search = params.toString();
            });

            var durIdx = durumIdx;
            var assgnIdx = assignedIdx;
            var currentStatusFilters = <?php echo json_encode($filterStatus); ?>;
            var currentDateFilter   = <?php echo json_encode($filterDate); ?>;
            var currentPreparedFilter = '';
            var currentAssignedFilter = '';
            var currentClientFilter = '';
            var currentMinTotal = '';
            var currentMaxTotal = '';

            $('#dateFilter').val(currentDateFilter);

            var minTotalInput = $('#minTotalFilter');
            var maxTotalInput = $('#maxTotalFilter');

            function readFilters(){
                currentStatusFilters  = $('#filterPanel .status-check:checked').map(function(){ return this.value; }).get();
                currentDateFilter     = $('#dateFilter').val() || '';
                currentPreparedFilter = $('#preparedFilter').val() || '';
                currentAssignedFilter = $('#assignedFilter').val() || '';
                currentClientFilter   = $('#clientFilter').val().toLowerCase();
                currentMinTotal = minTotalInput.val();
                currentMaxTotal = maxTotalInput.val();
            }

            var filterModalEl = document.getElementById('filterModal');
            var filterModal = bootstrap.Modal.getOrCreateInstance(filterModalEl);

            $('#applyFilters').on('click', function(){
                readFilters();
                table.draw();
                filterModal.hide();
            });

            $('#resetFilters').on('click', function(){
                $('#filterPanel input[type="checkbox"]').prop('checked', false);
                $('#filterPanel select').val('');
                $('#clientFilter').val('');
                minTotalInput.val('');
                maxTotalInput.val('');
                currentStatusFilters = [];
                currentDateFilter = '';
                currentPreparedFilter = '';
                currentAssignedFilter = '';
                currentClientFilter = '';
                currentMinTotal = '';
                currentMaxTotal = '';
                table.draw();
                filterModal.hide();
            });

            var dateFilterFunc = function(settings,data,index){
                if(!currentDateFilter) return true;
                var cellDate = $(table.row(index).node()).find('td.col-teklif_tarihi [data-order]').data('order');
                if(!cellDate) return false;
                var d = new Date(cellDate);
                var today = new Date();
                if(currentDateFilter === 'today') return d.toDateString() === today.toDateString();
                var diff = (today - d) / 86400000;
                if(currentDateFilter === '7') return diff <= 7;
                if(currentDateFilter === '30') return diff <= 30;
                return true;
            };

            var statusFilterFunc = function(settings,data,index){
                if(!currentStatusFilters || currentStatusFilters.length === 0) return true;
                var s = $(table.row(index).node()).find('td.col-durum').data('status');
                return currentStatusFilters.indexOf(s) !== -1;
            };

            var preparedFilterFunc = function(settings,data,index){
                if(!currentPreparedFilter) return true;
                var val = $(table.row(index).node()).find('td.col-prepared_by').data('prepared');
                if(typeof val === 'undefined') {
                    val = $(table.row(index).node()).find('td.col-prepared_by').text().trim();
                }
                return val === currentPreparedFilter;
            };

            var assignedFilterFunc2 = function(settings,data,index){
                if(!currentAssignedFilter) return true;
                var val = $(table.row(index).node()).find('td.col-assigned').data('assigned');
                if(typeof val === 'undefined') val = $(table.row(index).node()).find('td.col-assigned').text().trim();
                return val === currentAssignedFilter;
            };

            var clientFilterFunc = function(settings,data,index){
                if(!currentClientFilter) return true;
                var val = $(table.row(index).node()).find('td.col-teklif_verilen').text().toLowerCase();
                return val.indexOf(currentClientFilter) !== -1;
            };

            var totalFilterFunc = function(settings,data,index){
                if(!currentMinTotal && !currentMaxTotal) return true;
                var val = $(table.row(index).node()).find('td.col-genel_toplam span').data('order');
                val = parseFloat(val);
                if(isNaN(val)) return false;
                if(currentMinTotal && val < parseFloat(currentMinTotal)) return false;
                if(currentMaxTotal && val > parseFloat(currentMaxTotal)) return false;
                return true;
            };

            $.fn.dataTable.ext.search.push(function(settings,data,index){
                return dateFilterFunc(settings,data,index) &&
                       statusFilterFunc(settings,data,index) &&
                       preparedFilterFunc(settings,data,index) &&
                       assignedFilterFunc2(settings,data,index) &&
                       clientFilterFunc(settings,data,index) &&
                       totalFilterFunc(settings,data,index);
            });

            readFilters();
            table.draw();
            
            // Ensure DataTable rows can trigger Bootstrap modals
            $(document).on('click', '[data-bs-toggle="modal"]', function () {
                var target = $(this).data('bs-target');
                if (target) {
                    var modalEl = document.querySelector(target);
                    if (modalEl) {
                        var instance = bootstrap.Modal.getOrCreateInstance(modalEl);
                        instance.show();
                    }
                }
            });

            // Ajax status update
            $(document).on('click', '.status-option', function(e){
                e.preventDefault();
                var status = $(this).data('status');
                var dropdown = $(this).closest('.dropdown');
                var button = dropdown.find('.status-btn');
                var id = button.data('teklif-id');
                $.post('teklifsiparisler.php', {action:'update_status', id:id, status:status}, function(resp){
                    if(resp && resp.success){
                        button.text(status);
                        var classes = button.attr('class').split(/\s+/).filter(function(c){return c.indexOf('badge-status-') === -1 && c !== 'status-revision';});
                        button.attr('class', classes.join(' ') + ' ' + resp.badge);
                        button.closest('td').attr('data-status', status);

                        // Sync DataTables data so filters work correctly
                        var cell = table.cell(button.closest('td'));
                        cell.data(button.closest('td').html());
                        var row = table.row(button.closest('tr'));
                        row.invalidate();
                        table.draw(false);

                        // Close the dropdown after update
                        var dd = bootstrap.Dropdown.getOrCreateInstance(button[0]);
                        dd.hide();
                    } else {
                        alert('Durum g\u0308n\u0308cellenemedi');
                    }
                }, 'json');
            });

            // Ajax assigned update
            $(document).on('click', '.assigned-option', function(e){
                e.preventDefault();
                var assigned = $(this).data('assigned');
                var dropdown = $(this).closest('.dropdown');
                var button = dropdown.find('.assigned-btn');
                var id = button.data('teklif-id');
                $.post('teklifsiparisler.php', {action:'update_assigned', id:id, assigned:assigned}, function(resp){
                    if(resp && resp.success){
                        button.text(assigned);
                        button.closest('td').attr('data-assigned', assigned);
                        var cell = table.cell(button.closest('td'));
                        cell.data(button.closest('td').html());
                        table.row(button.closest('tr')).invalidate();
                        table.draw(false);
                        var dd = bootstrap.Dropdown.getOrCreateInstance(button[0]);
                        dd.hide();
                    } else {
                        alert('Atama g\u0308n\u0308cellenemedi');
                    }
                }, 'json');
            });

            // Ajax form submit for assignment modal
            $(document).on('submit', '.assign-form', function(e){
                e.preventDefault();
                var form = $(this);
                $.post('teklifsiparisler.php', form.serialize(), function(resp){
                    if(resp && resp.success){
                        var id = form.find('input[name="icerikid"]').val();
                        var dept = form.find('select[name="departman"]').val();
                        var row = $('tr[data-id="'+id+'"]');
                        table.row(row).invalidate().draw(false);
                        bootstrap.Modal.getInstance(form.closest('.modal')[0]).hide();
                    } else {
                        alert('Atama g\u0308n\u0308cellenemedi');
                    }
                }, 'json');
            });

            // Ajax form submit for status modal
            $(document).on('submit', '.status-form', function(e){
                e.preventDefault();
                var form = $(this);
                $.post('teklifsiparisler.php', form.serialize(), function(resp){
                    if(resp && resp.success){
                        var id = form.find('input[name="icerikid"]').val();
                        var status = form.find('select[name="durum"]').val();
                        var row = $('tr[data-id="'+id+'"]');
                        var button = row.find('button.status-btn');
                        if(button.length){
                            button.text(status);
                            var classes = button.attr('class').split(/\s+/).filter(function(c){return c.indexOf('badge-status-')===-1 && c !== 'status-revision';});
                            button.attr('class', classes.join(' ') + ' ' + resp.badge);
                        } else {
                            row.find('td.col-durum').text(status);
                        }
                        row.find('td.col-durum').attr('data-status', status);
                        var cell = table.cell(row.find('td.col-durum'));
                        cell.data(row.find('td.col-durum').html());
                        table.row(row).invalidate().draw(false);
                        bootstrap.Modal.getInstance(form.closest('.modal')[0]).hide();
                    } else {
                        alert('Durum g\u0308n\u0308cellenemedi');
                    }
                }, 'json');
            });

        });
    </script>
    <script>
    // Real-time Status Update Script for List Page
    document.addEventListener('offerStatusUpdate', function(e) {
        if(e.detail && e.detail.id && e.detail.status) {
            const id = e.detail.id;
            const newStatus = e.detail.status;
            
            // 1. Update Dropdown Button (if exists)
            const btn = document.getElementById('status-btn-' + id);
            if(btn) {
                const textSpan = btn.querySelector('.status-text');
                if(textSpan) textSpan.innerText = newStatus;
                
                 // Just ask for the badge class, don't update DB
                 $.post('teklifsiparisler.php', { action: 'get_badge', status: newStatus }, function(resp) {
                     if(resp && resp.badge) {
                        // Remove old badge classes
                        var classes = btn.className.split(/\s+/).filter(function(c){return c.indexOf('badge-status-')===-1 && c !== 'status-revision';});
                        btn.className = classes.join(' ') + ' ' + resp.badge;
                     }
                 }, 'json');
            }

            // 2. Update Read-only Badge (if exists)
            const badge = document.getElementById('status-badge-readonly-' + id);
            if(badge) {
                const textSpan = badge.querySelector('.status-text');
                if(textSpan) textSpan.innerText = newStatus;
                
                 $.post('teklifsiparisler.php', { action: 'get_badge', status: newStatus }, function(resp) {
                     if(resp && resp.badge) {
                        var classes = badge.className.split(/\s+/).filter(function(c){return c.indexOf('badge-status-')===-1 && c !== 'status-revision';});
                        badge.className = classes.join(' ') + ' ' + resp.badge;
                     }
                 }, 'json');
            }
        }
    });
    </script>
    <?php include "menuler/footer.php"; ?>
</body>
</html>
