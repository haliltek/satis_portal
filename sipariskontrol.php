<?php
// sipariskontrol.php
$logFile = __DIR__ . '/debug.log';
// Manuel loglama fonksiyonu
function writeLog($msg)
{
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, $timestamp . " " . $msg . "\n", FILE_APPEND);
}

include "fonk.php";
require_once __DIR__ . '/services/OrderProcessService.php';
oturumkontrol();
$orderProcessService = new Services\OrderProcessService($db);

// Ekstra bilgiyi session'dan al (geri dönüldüğünde)
$ekstra_bilgi = $_SESSION['form_ekstra_bilgi'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ekstra_bilgi'])) {
    $ekstra_bilgi = xss(addslashes($_POST['ekstra_bilgi']));
    $_SESSION['form_ekstra_bilgi'] = $ekstra_bilgi; // Session'a kaydet
}

// Fonksiyon: Virgülleri noktaya çevirir (örn. 1,23 => 1.23)
function convert($data)
{
    $data = (string)$data;
    return (strpos($data, ",") !== false) ? str_replace(",", ".", $data) : $data;
}

$userType = $_SESSION['user_type'] ?? '';
$tu = $_GET["t"] ?? 'teklif';
if ($userType === 'Bayi') {
    $tu = 'siparis';
}
$dealerCompany = null;
if ($userType === 'Bayi') {
    $cid = (int)($_SESSION['dealer_company_id'] ?? 0);
    if ($cid) {
        $st = $db->prepare('SELECT sirket_id, s_adi, s_arp_code FROM sirket WHERE sirket_id = ?');
        $st->bind_param('i', $cid);
        $st->execute();
        $dealerCompany = $st->get_result()->fetch_assoc();
        $st->close();
    }
}
// Kullanıcı ve işlem bilgilerini alıyoruz
$gelenid = xss(addslashes($_SESSION['yonetici_id']));
$personelsorgu = mysqli_query($db, "SELECT * FROM personel WHERE personel_id='$gelenid'");
$personelprofil = mysqli_fetch_array($personelsorgu);

$stmt = $db->prepare("SELECT iskonto_max, satis_tipi FROM yonetici WHERE yonetici_id = ?");
$stmt->bind_param('i', $gelenid);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$iskonto_max = $row ? floatval($row['iskonto_max']) : 0.0;
$salesType = strtolower($row['satis_tipi'] ?? '');
$discountDisabled = ($iskonto_max <= 0);

if ($tu === 'siparis') {
    $islemi = 'Sipariş';
    $durumu = 'Sipariş Oluşturuldu / Gönderilecek';
    $statusu = 'Siparişiniz oluşturuldu. Lütfen kontrol ediniz.';
} else {
    $islemi = 'Teklif';
    $durumu = 'Teklif Oluşturuldu / Gönderilecek';
    $statusu = 'Teklifiniz oluşturuldu. Lütfen kontrol edip müşteriye gönderiniz.';
}
$turum = 'urun';
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="utf-8" />
    <title><?php echo $sistemayar["title"]; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo $sistemayar["description"]; ?>" />
    <meta name="keywords" content="<?php echo $sistemayar["keywords"]; ?>" />
    <link rel="shortcut icon" href="assets/images/favicon.ico">

    <!-- CSS Dosyaları -->
    <link href="assets/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/app.min.css" id="app-style" rel="stylesheet" type="text/css" />

    <!-- DataTables CSS -->
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />

    <script src="//cdn.ckeditor.com/4.18.0/full/ckeditor.js"></script>
    <link href="assets/css/custom.css" rel="stylesheet" />
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --light-bg: #f8f9fa;
            --border-color: #e9ecef;
        }
        body {
            background-color: #f4f6f8;
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: #333;
        }
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 1.5rem;
            border-radius: 0.5rem 0.5rem 0 0 !important;
        }
        .section-title {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 0.5rem;
        }
        .info-label {
            font-weight: 600;
            color: #555;
            width: 140px;
            display: inline-block;
        }
        .info-value {
            color: #333;
        }
        .table thead th {
            background-color: var(--light-bg);
            border-bottom: 2px solid var(--border-color);
            color: #495057;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
        }
        .total-card {
            background-color: #fff;
            border-radius: 0.5rem;
            overflow: hidden;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        .total-row:last-child {
            border-bottom: none;
        }
        .total-row.grand-total {
            background-color: var(--primary-color);
            color: #fff;
            font-size: 1.1rem;
            font-weight: bold;
        }
        .btn-action {
            padding: 0.6rem 1.5rem;
            font-weight: 500;
            border-radius: 0.4rem;
            transition: all 0.2s;
        }
        .btn-action:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
    </style>
    <script>
        // DataTable başlatma
        document.addEventListener("DOMContentLoaded", function() {
            if (document.getElementById('example')) {
                $('#example').dataTable({
                    "pageLength": 200
                });
            }
        });
    </script>
</head>

<body>
    <!-- ========== MAIN CONTENT ========== -->
    <main id="content" role="main">
        <div class="main-container">
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <div class="card bordergizle">
                        <div class="card-body">
                            <?php
                            // Form gönderimi kontrolü
                            if (isset($_POST['kayitet'])) {
                                $companyId = ($userType === 'Bayi')
                                    ? (int)($dealerCompany['sirket_id'] ?? 0)
                                    : (int)($_POST['musteri'] ?? 0);
                                $companyForeign = null;
                                $tradingGrpForRedirect = ''; // Yönlendirme için trading_grp bilgisini sakla
                                if ($companyId) {
                                    $stChk = $db->prepare("SELECT trading_grp FROM sirket WHERE sirket_id = ?");
                                    $stChk->bind_param('i', $companyId);
                                    $stChk->execute();
                                    $grpRow = $stChk->get_result()->fetch_assoc();
                                    $stChk->close();
                                    $grp = strtolower($grpRow['trading_grp'] ?? '');
                                    $tradingGrpForRedirect = $grp; // Yönlendirme için sakla
                                    if ($grp !== '') {
                                        $companyForeign = strpos($grp, 'yd') !== false;
                                    }
                                }
                                $salesForeign = strpos($salesType, 'dışı') !== false;
                                if ($companyForeign !== null && $companyForeign !== $salesForeign) {
                                    echo '<div class="alert alert-danger">Yetkisiz şirket tipi seçimi.</div>';
                                } else {
                                try {
                                    // Post verilerini sanitize edip değişkenlere aktarıyoruz
                                    // 1) Gelen alt toplamları ve dövizleri toplayalım
                                    $tlToplam = $usdToplam = $eurToplam = 0.0;
                                    foreach ($_POST['tutar'] as $urunId => $tutarStr) {
                                        // str_replace ile gerekirse virgülü noktaya çevir
                                        $tutar = floatval(str_replace(',', '.', $tutarStr));
                                        $doviz = $_POST['doviz'][$urunId] ?? 'TL';
                                        if ($doviz === 'TL') {
                                            $tlToplam += $tutar;
                                        } elseif ($doviz === 'USD') {
                                            $usdToplam += $tutar;
                                        } elseif ($doviz === 'EUR') {
                                            $eurToplam += $tutar;
                                        }
                                    }

                                    // 2) Kurları veritabanından çekiyoruz
                                    $kurbag = mysqli_query($db, "
                                        SELECT dolarsatis, eurosatis, tarih 
                                        FROM dovizkuru 
                                        ORDER BY tarih DESC 
                                        LIMIT 1
                                    ");
                                    if (!$kurrow = mysqli_fetch_assoc($kurbag)) {
                                        throw new Exception("Kur tablosundan veri alınamadı!");
                                    }
                                    $dolarkur = floatval(str_replace(',', '.', $kurrow['dolarsatis']));
                                    $eurokur = floatval(str_replace(',', '.', $kurrow['eurosatis']));
                                    $kurtarih = $kurrow['tarih'];

                                    // 3) Dövizleri TL'ye çevir
                                    $eurInTL = $eurToplam * $eurokur;
                                    $usdInTL = $usdToplam * $dolarkur;

                                    // 4) Genel TL toplama ve KDV
                                    $genelIskonto = isset($_POST['genel_iskonto']) ? (float)$_POST['genel_iskonto'] : 0.0;
                                    $genelTL = $tlToplam + $eurInTL + $usdInTL;
                                    
                                    // Genel İskonto Uygula
                                    if ($genelIskonto > 0) {
                                        $iskontoTutari = $genelTL * ($genelIskonto / 100);
                                        $genelTL -= $iskontoTutari;
                                    }

                                    $kdv = $genelTL * 0.20;
                                    $genelToplam = $genelTL + $kdv;

                                    // 5) Değişkenleri hazırla
                                    $tltutar = $tlToplam;
                                    $dolartutar = $usdToplam;
                                    $eurotutar = $eurToplam;
                                    $toplamtutar = $genelTL;

                                    // POST verilerini güvenli hale getir
                                    $hazirlayanid = xss(addslashes($_POST["hazirlayanid"] ?? ''));
                                    if ($userType === 'Bayi' && $dealerCompany) {
                                        $musteriid = (string)$dealerCompany['sirket_id'];
                                        $musteriadi = $dealerCompany['s_adi'];
                                        $sirketid = (string)$dealerCompany['sirket_id'];
                                        $teklifsiparis = 'Sipariş';
                                        $kime = 'Müşteriye';
                                    } else {
                                        $musteriid = xss(addslashes($_POST["musteriid"] ?? ''));
                                        $musteriadi = xss(addslashes($_POST["musteriadi"] ?? ''));
                                        $kime = xss(addslashes($_POST["kime"] ?? ''));
                                        // Admin panelinden oluşturulan siparişler için sirketid = musteriid olmalı (bayi panelinde görünmesi için)
                                        $sirketid = xss(addslashes($_POST["sirket_id"] ?? $_POST["musteriid"] ?? ''));
                                        $teklifsiparis = xss(addslashes($_POST["teklifsiparis"] ?? ''));
                                    }
                                    $projeadi = xss(addslashes($_POST["projeadi"] ?? ''));
                                    $tekliftarihi = xss(addslashes($_POST["tekliftarihi"] ?? ''));
                                    $teklifkodu = xss(addslashes($_POST["teklifkodu"] ?? ''));
                                    $teklifsartid = xss(addslashes($_POST["teklifsartid"] ?? ''));
                                    $payment_code = xss(addslashes($_POST["odemeturu"] ?? ''));
                                    $paydefref = (int)($_POST["paydefref"] ?? 0);
                                    $payplan_def = xss(addslashes($_POST["payplan_def"] ?? ''));
                                    $odemeturu = $payment_code;
                                    if (!empty($payplan_def)) {
                                        $odemeturu .= ' - ' . $payplan_def;
                                    }
                                    $teklifgecerlilik = xss(addslashes($_POST["teklifgecerlilik"] ?? ''));
                                    $teslimyer = xss(addslashes($_POST["teslimyer"] ?? ''));
                                    $ekstraBilgi = xss(addslashes($_POST["ekstra_bilgi"] ?? ''));
                                    $sozlesmeId = isset($_POST["sozlesme_id"]) ? (int)$_POST["sozlesme_id"] : 5;
                                    $sozlesmeMetinEdited = xss(addslashes($_POST["sozlesme_metin_edited"] ?? ''));
                                    $belgeNo = xss(addslashes($_POST["belgeno"] ?? '')); // Belge No Al
                                    
                                    // Eğer sözleşme metni düzenlenmişse, ekstra bilgiye ekle
                                    if (!empty($sozlesmeMetinEdited)) {
                                        if (!empty($ekstraBilgi)) {
                                            $ekstraBilgi = $ekstraBilgi . "\n\n--- Sözleşme Metni ---\n" . $sozlesmeMetinEdited;
                                        } else {
                                            $ekstraBilgi = "--- Sözleşme Metni ---\n" . $sozlesmeMetinEdited;
                                        }
                                    }
                                    $dovizGoster = isset($_POST["doviz_goster"]) && !empty(trim($_POST["doviz_goster"])) ? trim(xss(addslashes($_POST["doviz_goster"]))) : 'TUMU';
                                    $orderStatus = 1;

                                    // Özel Teklif Kontrolü
                                    $isSpecialOffer = isset($_POST['is_special_offer']) && $_POST['is_special_offer'] == '1' ? 1 : 0;
                                    $approvalStatus = 'none';
                                    
                                    if ($isSpecialOffer) {
                                        $durumu = 'Yönetici Onayı Bekleniyor';
                                        $statusu = 'Teklif özel onay sürecindedir. Yönetici onayı bekleniyor.';
                                        $approvalStatus = 'pending';
                                        // $orderStatus farklı bir durum kodu olabilir, şimdilik 1 bırakıyoruz veya
                                        // Onay bekleyenler için farklı bir kod kullanılabilir (örn: 5)
                                    }

                                    // Şirket ARP kodunu al
                                    $lookupId = (int)$musteriid;
                                    $sirketArpCode = '';
                                    // Müşteri ID'si 786 ise (Cari Yok), ARP kodunu boş bırak
                                    if ($lookupId !== 786 && $lookupId > 0) {
                                        $sirketSorgu = mysqli_query($db, "SELECT s_arp_code FROM sirket WHERE sirket_id='$lookupId'");
                                        if ($sirketData = mysqli_fetch_array($sirketSorgu)) {
                                            $sirketArpCode = $sirketData['s_arp_code'];
                                        } else {
                                            throw new Exception("Şirket bilgileri alınamadı!" . mysqli_error($db) . " - " . $lookupId);
                                        }
                                    }

                                    // Kullanıcının son kullandığı Logo başlık tercihlerini çek
                                    $prefStmt = mysqli_prepare(
                                        $db,
                                        'SELECT pref_auxil_code, pref_division, pref_department, pref_source_wh, pref_factory, pref_salesmanref FROM yonetici WHERE yonetici_id = ?'
                                    );
                                    mysqli_stmt_bind_param($prefStmt, 'i', $gelenid);
                                    mysqli_stmt_execute($prefStmt);
                                    $prefRes   = mysqli_stmt_get_result($prefStmt);
                                    $prefRow   = $prefRes ? mysqli_fetch_assoc($prefRes) : [];
                                    mysqli_stmt_close($prefStmt);

                                    $auxil_code = xss(addslashes($prefRow['pref_auxil_code'] ?? ''));
                                    $division    = (int)($prefRow['pref_division'] ?? 0);
                                    $department  = (int)($prefRow['pref_department'] ?? 0);
                                    $source_wh   = (int)($prefRow['pref_source_wh'] ?? 0);
                                    $factory     = (int)($prefRow['pref_factory'] ?? 0);
                                    $salesmanref = (int)($prefRow['pref_salesmanref'] ?? 0);

                                    // TL ve dolar tutarlarını hesapla
                                    if ($tlToplam <= 0) {
                                        $tltutar = $eurToplam * $eurokur;
                                    }
                                    if ($usdToplam <= 0) {
                                        $dolartutar = ($eurToplam * $eurokur) / $dolarkur;
                                    }

                                    // Ana teklif kaydını oluştur
                                    $query = "INSERT INTO ogteklif2 (
                                        musteriadi, teklifsiparis, hazirlayanid, musteriid, kime, projeadi,
                                        tekliftarihi, teklifkodu, teklifsartid, odemeturu, sirketid, sirket_arp_code,
                                        tltutar, dolartutar, eurotutar, toplamtutar, kdv, geneltoplam, kurtarih,
                                        eurokur, dolarkur, tur, teklifgecerlilik, teslimyer,
                                        durum, statu, notes1, order_status, sozlesme_id, doviz_goster,
                                        auxil_code, auth_code, division, department, source_wh, factory, salesmanref,
                                        is_special_offer, approval_status, genel_iskonto, doc_number, payment_code, paydefref
                                    ) VALUES (
                                        '$musteriadi', '$teklifsiparis', '$hazirlayanid', '$musteriid', '$kime', '$projeadi',
                                        '$tekliftarihi', '$teklifkodu', '$teklifsartid', '$odemeturu', '$sirketid', '$sirketArpCode',
                                        '$tltutar', '$dolartutar', '$eurotutar', '$toplamtutar', '$kdv', '$genelToplam', '$kurtarih',
                                        '$eurokur', '$dolarkur', '$turum', '$teklifgecerlilik', '$teslimyer',
                                        '$durumu', '$statusu', '$ekstraBilgi', '$orderStatus', '$sozlesmeId', '$dovizGoster',
                                        '$auxil_code', 'GMP', '$division', '$department', '$source_wh', '$factory', '$salesmanref',
                                        '$isSpecialOffer', '$approvalStatus', '$genelIskonto', '$belgeNo', '$payment_code', '$paydefref'
                                    )";

                                    if (!$teklifkayit = mysqli_query($db, $query)) {
                                        throw new Exception("Teklif kaydı oluşturulamadı: " . mysqli_error($db));
                                    }

                                    $teklifsonkayitid = mysqli_insert_id($db);

                                    // İlk durum kaydı
                                    $initStmt = mysqli_prepare(
                                        $db,
                                        'INSERT INTO durum_gecisleri (teklif_id, s_arp_code, eski_durum, yeni_durum, degistiren_personel_id, notlar) VALUES (?, ?, ?, ?, ?, ?)'
                                    );
                                    if ($initStmt) {
                                        $empty = '';
                                        mysqli_stmt_bind_param(
                                            $initStmt,
                                            'isssis',
                                            $teklifsonkayitid,
                                            $sirketArpCode,
                                            $empty,
                                            $durumu,
                                            $gelenid,
                                            $statusu
                                        );
                                        mysqli_stmt_execute($initStmt);
                                        mysqli_stmt_close($initStmt);
                                    }
                                    $orderProcessService->record($teklifsonkayitid, $sirketArpCode, $durumu, $statusu, $gelenid);

                                    // Onay için ürün listesi biriktirme
                                    $approvalProducts = [];

                                    // Ürünleri kaydet
                                    foreach ($_COOKIE['teklif'] as $fihrists => $val) {
                                        $urunSorgu = mysqli_query($db, "SELECT * FROM urunler WHERE urun_id='$fihrists'");
                                        while ($ogs = mysqli_fetch_array($urunSorgu)) {
                                            $urun_id = $ogs["urun_id"];
                                            $kod = xss(addslashes($_POST["kod"][$urun_id] ?? ''));
                                            $adi = xss(addslashes($_POST["adi"][$urun_id] ?? ''));
                                            $aciklama = xss(addslashes($_POST["aciklama"][$urun_id] ?? ''));
                                            $miktar = xss(addslashes($_POST["miktar"][$urun_id] ?? '0'));
                                            
                                            // Liste fiyatını kontrol et - fiyat yoksa atla
                                            $liste = xss(addslashes($_POST["liste"][$urun_id] ?? '0'));
                                            $listeFloat = floatval(str_replace(',', '.', $liste));
                                            if ($listeFloat <= 0) {
                                                // Fiyatı olmayan ürünü atla
                                                continue;
                                            }
                                            
                                            $iskonto = $_POST["iskontoyolla"][$urun_id] ?? 0;
                                            $camp = null; // Initialize variable
                                            $campRate = $dbManager->getCampaignDiscountForProduct((int)$ogs["LOGICALREF"]);
                                            if ($campRate !== null) {
                                                $iskonto = $campRate;
                                            } elseif ($discountDisabled) {
                                                $iskonto = 0;
                                            } else {
                                                $iskonto = min((float)$iskonto, $iskonto_max);
                                            }
                                            $iskonto = xss(addslashes($iskonto));
                                            $iskontoFormulu = xss(addslashes($_POST["iskonto_formulu"][$urun_id] ?? ''));
                                            $birim = xss(addslashes($_POST["birim"][$urun_id] ?? ''));
                                            $doviz = xss(addslashes($_POST["doviz"][$urun_id] ?? ''));
                                            $nettutar = xss(addslashes($_POST["nettutar"][$urun_id] ?? '0'));
                                            $tutar = xss(addslashes($_POST["tutar"][$urun_id] ?? '0'));
                                            $internalRef = $ogs["LOGICALREF"];

                                            $urunInsert = "INSERT INTO ogteklifurun2 
                                                (teklifid, kod, adi, aciklama, miktar, birim, liste, doviz, iskonto, iskonto_formulu, nettutar, tutar, product_internal_ref) 
                                                VALUES 
                                                ('$teklifsonkayitid', '$kod', '$adi', '$aciklama', '$miktar', '$birim', '$liste', '$doviz', '$iskonto', '$iskontoFormulu', '$nettutar', '$tutar', '$internalRef')";
                                            
                                            if (!mysqli_query($db, $urunInsert)) {
                                                throw new Exception("Ürün kaydı oluşturulamadı: " . mysqli_error($db));
                                            }

                                            // Onay için ürün verisi ekle
                                            $approvalProducts[] = [
                                                "ad" => $adi,
                                                "adet" => (int)$miktar,
                                                "fiyat" => (float)str_replace(',', '.', $tutar)
                                            ];
                                        }
                                    }

                                    // Yönetici Onayına Gönder (Eğer Özel Teklif Seçildiyse)
                                    if ($isSpecialOffer) {
                                        // Temsilci (Hazırlayan) ismini belirle
                                        $temsilciAdi = $yoneticisorgula["adsoyad"] ?? ($personelprofil["adsoyad"] ?? "Bilinmeyen Temsilci");

                                        $approvalData = [
                                            "yonetici_tel" => $sistemayar['whatsapp_approval_phone'] ?? "905525287286", // Ayarlardan çek
                                            "cari"         => $musteriadi,
                                            "toplam"       => number_format((float)$genelToplam, 2, '.', ''), // 2 ondalık basamak
                                            "teklif_id"    => (int)$teklifsonkayitid,
                                            "temsilci"     => $temsilciAdi,
                                            "urunler"      => $approvalProducts
                                        ];

                                        // URL dinamik belirlensin veya sabit
                                        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                                        $domainName = $_SERVER['HTTP_HOST'];
                                        $baseDir = dirname($_SERVER['PHP_SELF']); // /b2b-gemas-project-main
                                        $apiUrl = $protocol . $domainName . $baseDir . "/api/teklif/onay-gonder.php";

                                        $ch = curl_init($apiUrl);
                                        curl_setopt($ch, CURLOPT_POST, 1);
                                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($approvalData));
                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
                                        // SSL doğrulamasını localde devre dışı bırakmak gerekebilir
                                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                                        
                                        $apiResponse = curl_exec($ch);
                                        file_put_contents('n8n_capture.txt', "API Resp: " . $apiResponse . "\n", FILE_APPEND);
                                        if (curl_errno($ch)) {
                                            writeLog("Approval API cURL Error: " . curl_error($ch));
                                        } else {
                                            writeLog("Approval API Response: " . $apiResponse);
                                            
                                            writeLog("Approval API Response: " . $apiResponse);
                                            
                                            // Save n8n message ID if available
                                            $respJson = json_decode($apiResponse, true);
                                            $foundId = '';
                                            
                                            // Generic recursive search for key.id or keyId
                                            // Evolution API usually returns { key: { id: "..." } }
                                            if ($respJson) {
                                                $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($respJson));
                                                foreach ($iterator as $key => $value) {
                                                    if ($key === 'id' && strlen($value) > 18 && strpos($value, '-') === false) {
                                                        // Likely a WhatsApp Message ID (e.g. 3EB0...)
                                                        $foundId = $value;
                                                        break;
                                                    }
                                                    if ($key === 'keyId') {
                                                        $foundId = $value;
                                                        break;
                                                    }
                                                }
                                                // Specific check for key.id structure if iterator missed it (unlikely but safe)
                                                if (empty($foundId)) {
                                                    if (isset($respJson['key']['id'])) $foundId = $respJson['key']['id'];
                                                    elseif (isset($respJson['data']['key']['id'])) $foundId = $respJson['data']['key']['id'];
                                                }
                                            }
                                            
                                            if (!empty($foundId)) {
                                                $updInst = $db->prepare("UPDATE ogteklif2 SET n8n_instance_id = ? WHERE id = ?");
                                                $updInst->bind_param("si", $foundId, $teklifsonkayitid);
                                                $updInst->execute();
                                                $updInst->close();
                                                writeLog("n8n Message ID Saved: " . $foundId);
                                            }
                                        }
                                        curl_close($ch);
                                    }

                                    writeLog($islemi . ' başarıyla kaydedildi: ' . $teklifsonkayitid);
                                    echo '<div class="alert alert-success" role="alert" style="font-size:13px;">' . $islemi . ' Başarıyla Oluşturulmuştur. Lütfen Bekleyiniz...</div>';
                                    $target = ($tu === 'siparis') ? 'siparisler.php' : 'teklifsiparisler.php';
                                    
                                    // Trading filter parametresini ekle (yurtiçi/yurtdışı)
                                    // trading_grp bilgisini kontrol et
                                    if ($target === 'teklifsiparisler.php' && $companyId && $companyId !== 786) {
                                        // trading_grp bilgisini tekrar çek (güvenli olması için)
                                        $redirectStmt = $db->prepare("SELECT trading_grp FROM sirket WHERE sirket_id = ?");
                                        $redirectStmt->bind_param('i', $companyId);
                                        $redirectStmt->execute();
                                        $redirectGrpRow = $redirectStmt->get_result()->fetch_assoc();
                                        $redirectStmt->close();
                                        
                                        $redirectGrp = strtolower($redirectGrpRow['trading_grp'] ?? '');
                                        if ($redirectGrp !== '' && strpos($redirectGrp, 'yd') !== false) {
                                            $tradingFilter = 'yurtdisi';
                                        } else {
                                            $tradingFilter = 'yurtici';
                                        }
                                        $target .= '?trading_filter=' . urlencode($tradingFilter);
                                    }
                                    
                                    header('Location: ' . $target);
                                    exit;

                                } catch (Exception $e) {
                                    writeLog("HATA: " . $e->getMessage());
                                    echo '<div class="alert alert-danger" role="alert" style="font-size:13px;">İşlem sırasında bir hata oluştu: ' . $e->getMessage() . '</div>';
                                }
                                }
                            }
                            ?>

                            <form method="post" action="sipariskontrol.php?t=<?php echo htmlspecialchars($tu); ?>">
                                <input type="hidden" name="ekstra_bilgi" value="<?php echo htmlspecialchars($ekstra_bilgi); ?>">
                                <input type="hidden" name="teklifgecerlilik" value="<?php echo htmlspecialchars($_POST['teklifgecerlilik'] ?? ''); ?>">
                                <input type="hidden" name="teklifsartid" value="<?php echo htmlspecialchars($_POST['teklifsartid'] ?? ''); ?>">
                                <input type="hidden" name="odemeturu" value="<?php echo htmlspecialchars($_POST['odemeturu'] ?? ''); ?>">
                                <input type="hidden" name="teslimyer" value="<?php echo htmlspecialchars($_POST['teslimyer'] ?? ''); ?>">
                                <input type="hidden" name="sozlesme_id" value="<?php echo htmlspecialchars($_POST['sozlesme_id'] ?? '5'); ?>">
                                <input type="hidden" name="doviz_goster" value="<?php echo htmlspecialchars($_POST['doviz_goster'] ?? 'TUMU'); ?>">
                                <input type="hidden" name="is_special_offer" value="<?php echo isset($_POST['is_special_offer']) ? htmlspecialchars($_POST['is_special_offer']) : '0'; ?>">
                                <input type="hidden" name="hazirlayanid" value="<?php echo htmlspecialchars($_POST['hazirlayanid'] ?? ''); ?>">
                                <input type="hidden" name="genel_iskonto" value="<?php echo isset($_POST['genel_iskonto']) ? htmlspecialchars($_POST['genel_iskonto']) : '0'; ?>">
                                <input type="hidden" name="belgeno" value="<?php echo htmlspecialchars($_POST['belgeno'] ?? ''); ?>">
                                <!-- Başlık ve Butonlar -->
                                <div class="d-flex justify-content-between align-items-center mb-3" style="padding: 16px; background: #0d6efd; border-radius: 8px;">
                                    <div>
                                        <h5 class="mb-0" style="color: white; font-size: 16px; font-weight: 600;">
                                            Teklifi müşteri göndermeden önceki kontrol sayfasıdır.
                                        </h5>
                                        <small style="color: rgba(255,255,255,0.8); font-size: 11px;"><?php echo date('d.m.Y H:i'); ?></small>
                                    </div>
                                    <div>
                                        <!-- Geri Dön butonu -->
                                        <?php
                                        $referrerUrl = $_SESSION['form_referrer_url'] ?? '';
                                        // Referrer URL yoksa veya geçersizse, t parametresine göre varsayılan sayfayı belirle
                                        if (empty($referrerUrl)) {
                                            $referrerUrl = ($tu === 'siparis') ? 'siparis-olustur.php' : 'teklif-olustur.php';
                                        }
                                        
                                        // URL'den query string'i temizle ve sadece base URL'i al
                                        $parsedUrl = parse_url($referrerUrl);
                                        $backUrl = $parsedUrl['path'] ?? (($tu === 'siparis') ? 'siparis-olustur.php' : 'teklif-olustur.php');
                                        
                                        // Modal'ı açık tutmak için parametre ekle
                                        $backUrl .= (strpos($backUrl, '?') !== false ? '&' : '?') . 'modal=open';
                                        ?>
                                        <a href="<?php echo htmlspecialchars($backUrl); ?>" class="btn btn-outline-light btn-sm" style="margin-right: 8px; font-size: 12px;">
                                            ← Geri
                                        </a>
                                        <!-- Onayla butonu -->
                                        <button type="submit" name="kayitet" class="btn btn-light btn-sm" style="font-size: 12px; font-weight: 600; color: #0d6efd;">
                                            ✓ Onayla ve Kaydet
                                        </button>
                                    </div>
                                </div>

                                <!-- Modern Grid Layout for Company Info -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="card h-100">
                                            <div class="card-header py-2" style="background: #f8f9fa;">
                                                <h6 class="mb-0" style="font-size: 13px; font-weight: 600;">🏢 Şirket Bilgileri</h6>
                                            </div>
                                            <div class="card-body py-2 px-3">
                                                <?php
                                                $musteris = $_POST["musteri"] ?? ($dealerCompany['sirket_id'] ?? '');
                                                $musteriTelefon = ''; // Varsayılan değer
                                                if ($musteris == '786') {
                                                    $kimehazir = $_POST["sirketbilgi"] ?? '';
                                                    $musteriTelefon = $_POST["projeadi"] ?? ''; // Cari telefon alanından al
                                                } elseif ($musteris !== '') {
                                                    $musteribag = mysqli_query($db, "SELECT * FROM sirket WHERE sirket_id='$musteris'");
                                                    $musteribilgi = mysqli_fetch_array($musteribag);
                                                    $kimehazir = $musteribilgi["s_adi"] ?? '';
                                                    $musteriTelefon = $musteribilgi["s_telefonu"] ?? '';
                                                } else {
                                                    $kimehazir = '';
                                                    $musteriTelefon = '';
                                                }
                                                ?>
                                                <input type="hidden" name="musteriid" value="<?php echo $musteris; ?>">
                                                <input type="hidden" name="sirket_id" value="<?php echo $musteris; ?>">
                                                <input type="hidden" name="musteriadi" value="<?php echo $kimehazir; ?>">
                                                <input type="hidden" name="hazirlayanid" value="<?php echo $yoneticisorgula["yonetici_id"]; ?>">
                                                <input type="hidden" name="kime" value="<?php echo ($musteris == '786') ? "Carisiz Müşteriye" : "Müşteriye"; ?>">
                                                <input type="hidden" name="projeadi" value="<?php echo $_POST["projeadi"] ? $_POST["projeadi"] : $musteriTelefon; ?>">
                                                <input type="hidden" name="tekliftarihi" value="<?php echo date("Y-m-d H:i"); ?>">
                                                <input type="hidden" name="teklifkodu" value="<?php echo $_POST["teklifno"] ?? ''; ?>">

                                                <div style="font-size: 11px; margin-bottom: 6px;">
                                                    <span style="color: #666; font-weight: 500;">Şirket:</span>
                                                    <span style="font-weight: 600; color: #000;"><?php echo $kimehazir; ?></span>
                                                </div>
                                                <div style="font-size: 11px; margin-bottom: 6px;">
                                                    <span style="color: #666; font-weight: 500;">Telefon:</span>
                                                    <span><?php echo $_POST["projeadi"] ? $_POST["projeadi"] : $musteriTelefon; ?></span>
                                                </div>
                                                <div style="font-size: 11px;">
                                                    <span style="color: #666; font-weight: 500;">E-Posta:</span>
                                                    <span style="font-size: 10px;">
                                                        <?php
                                                        if ($musteris !== '786' && $musteris !== '') {
                                                            $q1 = mysqli_query($db, "SELECT yetkili FROM sirket WHERE sirket_id='" . mysqli_real_escape_string($db, $musteris) . "'");
                                                            if ($row1 = mysqli_fetch_assoc($q1)) {
                                                                $yetkiliId = $row1["yetkili"] ?? "";
                                                                if ($yetkiliId !== "") {
                                                                    $q2 = mysqli_query($db, "SELECT p_eposta FROM personel WHERE personel_id='" . mysqli_real_escape_string($db, $yetkiliId) . "'");
                                                                    if (($row2 = mysqli_fetch_assoc($q2)) !== null && !empty($row2['p_eposta'])) {
                                                                        echo htmlspecialchars($row2['p_eposta']);
                                                                    } else {
                                                                        echo '<span style="color: #999;">-</span>';
                                                                    }
                                                                } else {
                                                                    echo '<span style="color: #999;">-</span>';
                                                                }
                                                            } else {
                                                                echo '<span style="color: #999;">-</span>';
                                                            }
                                                        } else {
                                                            echo '<span style="color: #999;">-</span>';
                                                        }
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card h-100">
                                            <div class="card-header py-2" style="background: #f8f9fa;">
                                                <h6 class="mb-0" style="font-size: 13px; font-weight: 600;">📋 İşlem Detayları</h6>
                                            </div>
                                            <div class="card-body py-2 px-3">
                                                <div style="font-size: 11px; margin-bottom: 6px;">
                                                    <span style="color: #666; font-weight: 500;">Hazırlayan:</span>
                                                    <span style="font-weight: 600;"><?php echo $yoneticisorgula["adsoyad"]; ?></span>
                                                    <small style="color: #999; font-size: 10px;">(<?php echo $userType === 'Bayi' ? 'Bayi' : 'Gemas'; ?>)</small>
                                                </div>
                                                <div style="font-size: 11px; margin-bottom: 6px;">
                                                    <span style="color: #666; font-weight: 500;">Ödeme:</span>
                                                    <span><?php echo htmlspecialchars($_POST['odemeturu'] ?? '-'); ?></span>
                                                </div>
                                                <div style="font-size: 11px; margin-bottom: 6px;">
                                                    <span style="color: #666; font-weight: 500;">Tarih:</span>
                                                    <span><?php echo date("d.m.Y H:i"); ?></span>
                                                </div>
                                                <div style="font-size: 11px;">
                                                    <span style="color: #666; font-weight: 500;"><?php echo $islemi; ?> No:</span>
                                                    <span style="font-weight: 700; color: #dc3545;"><?php echo $_POST["teklifno"] ?? ''; ?></span>
                                                </div>
                                                <div style="font-size: 11px;">
                                                    <span style="color: #666; font-weight: 500;">Belge No:</span>
                                                    <span style="font-weight: 600;"><?php echo htmlspecialchars($_POST["belgeno"] ?? '-'); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <br><br>
                                <p>
                                    <?php
                                    // Müşteri bilgilerini al ve yurtdışı kontrolü yap
                                    $musteriAdi = '';
                                    $isForeignCustomer = false;
                                    
                                    if ($musteris == '786') {
                                        $musteriAdi = $_POST["sirketbilgi"] ?? '';
                                        // Session'dan pazar tipini kontrol et
                                        $pazarTipi = $_SESSION['pazar_tipi'] ?? 'yurtici';
                                        $isForeignCustomer = ($pazarTipi === 'yurtdisi');
                                    } else {
                                        $musteribag = mysqli_query($db, "SELECT s_adi, trading_grp FROM sirket WHERE sirket_id='$musteris'");
                                        if ($musteribilgi = mysqli_fetch_array($musteribag)) {
                                            $musteriAdi = $musteribilgi["s_adi"] ?? '';
                                            $tradingGrp = strtolower($musteribilgi["trading_grp"] ?? '');
                                            $isForeignCustomer = (strpos($tradingGrp, 'yd') !== false);
                                        }
                                    }
                                    
                                    // Metinleri belirle
                                    if ($isForeignCustomer) {
                                        // İngilizce metinler
                                        $greeting = "Dear";
                                        $message1 = "The commercial terms and conditions for the service you requested are presented below.";
                                        $message2 = "We hope that our " . ($islemi === 'Sipariş' ? 'Order' : 'Offer') . " will be favorably received, and we wish you success in your business.";
                                        $closing = "Best regards.";
                                    } else {
                                        // Türkçe metinler
                                        $greeting = "Sayın";
                                        $message1 = "Talep ettiğiniz hizmete ait ticari koşulları içeren " . $islemi . " bilgileri aşağıda sunulmuştur.";
                                        $message2 = $islemi . "'imizin olumlu karşılanmasını ümit eder, işlerinizde başarılar dileriz.";
                                        $closing = "Saygılarımızla.";
                                    }
                                    ?>
                                    <?php echo $greeting; ?> <strong><?php echo htmlspecialchars($musteriAdi); ?></strong>,
                                    <?php echo $message1; ?>
                                    <?php echo $message2; ?>
                                    <br><br>
                                    <?php echo $closing; ?>
                                </p>

                                <!-- ERP Kompakt Ürün Tablosu -->
                                <div class="card">
                                    <div class="card-header py-2" style="background: #f8f9fa;">
                                        <h6 class="mb-0" style="font-size: 13px; font-weight: 600;">🛒 Ürün Listesi</h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table mb-0" style="font-size: 11px;">
                                                <thead style="background: #f8f9fa;">
                                                    <tr>
                                                        <th style="padding: 4px 8px; font-size: 10px; font-weight: 600;">Stok Kodu</th>
                                                        <th style="padding: 4px 8px; font-size: 10px; font-weight: 600;">Stok Adı</th>
                                                        <th style="padding: 4px 8px; text-align: center; font-size: 10px; font-weight: 600;">Miktar</th>
                                                        <th style="padding: 4px 8px; text-align: center; font-size: 10px; font-weight: 600;">Birim</th>
                                                        <th style="padding: 4px 8px; text-align: right; font-size: 10px; font-weight: 600;">Liste Fiyatı</th>
                                                        <th style="padding: 4px 8px; text-align: center; font-size: 10px; font-weight: 600;">İskonto</th>
                                                        <th style="padding: 4px 8px; text-align: right; font-size: 10px; font-weight: 600;">Net Fiyat</th>
                                                        <th style="padding: 4px 8px; text-align: right; font-size: 10px; font-weight: 600;">Tutar</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $dovizkurbag = mysqli_query($db, "SELECT * FROM dovizkuru");
                                                    $dovizkuru = mysqli_fetch_array($dovizkurbag);
                                                    $dolarkurr = number_format($dovizkuru["dolarsatis"], 2, ',', '.');
                                                    $eurokurr = number_format($dovizkuru["eurosatis"], 2, ',', '.');
                                                    $dolarkur = convert($dolarkurr);
                                                    $eurokur = convert($eurokurr);
                                                    $usd_toplam = $tl_toplam = $eur_toplam = 0;
                                                    $brut_usd_toplam = $brut_tl_toplam = $brut_eur_toplam = 0;

                                                    $miktarisiList  = $_POST['miktarisi']  ?? [];
                                                    $fiyatsiList    = $_POST['fiyatsi']    ?? [];
                                                    $iskontosiList  = $_POST['iskontosi']  ?? [];
                                                    $birimList      = $_POST['olcubirimi'] ?? [];

                                                    foreach ($_COOKIE['teklif'] as $fihrists => $val) {
                                                        $teklifbag = mysqli_query($db, "SELECT *, LOGICALREF FROM urunler WHERE urun_id='$fihrists'");
                                                        while ($fihrist = mysqli_fetch_array($teklifbag)) {
                                                            $fihid = $fihrist["urun_id"];
                                                            $camp = null; // Initialize variable
                                                            $miktarsim = $miktarisiList[$fihid] ?? 0;
                                                            $fiyatsi   = convert($fiyatsiList[$fihid] ?? 0);
                                                            
                                                            // İskonto Parsing ve Hesaplama
                                                            $rawDiscount = $iskontosiList[$fihid] ?? '0';
                                                            $validDiscounts = [];
                                                            $netFiyatCarpan = 1.0;
                                                            
                                                            // NOT: Veritabanından tekrar kampanya sorgulamıyoruz. 
                                                            // Teklif oluşturma ekranında hesaplanan ve POST ile gelen değeri esas alıyoruz.
                                                            
                                                            if ($discountDisabled) {
                                                                $rawDiscount = '0';
                                                            }
                                                            
                                                            // Ayraçları temizle ve parçala ( - veya + )
                                                            // JS tarafında genelde virgül kullanılıyor veya tire. Teklif-olustur'da 50,00-10,00 formatı vardı.
                                                            // Önce float casting yapmadan string olarak işle
                                                            $cleanDiscount = str_replace([' ', '+'], '-', $rawDiscount); 
                                                            $parts = explode('-', $cleanDiscount);
                                                            
                                                            foreach ($parts as $part) {
                                                                $val = floatval(str_replace(',', '.', trim($part)));
                                                                if ($val > 0) {
                                                                    $validDiscounts[] = $val;
                                                                    $netFiyatCarpan *= (1 - ($val / 100));
                                                                }
                                                            }
                                                            
                                                            // Eğer hiç geçerli iskonto yoksa
                                                            if (empty($validDiscounts) && floatval(str_replace(',', '.', $rawDiscount)) > 0) {
                                                                 $val = floatval(str_replace(',', '.', $rawDiscount));
                                                                 $validDiscounts[] = $val;
                                                                 $netFiyatCarpan = (1 - ($val / 100));
                                                            }

                                                            // Max İskonto Kontrolü (Efektif oran üzerinden)
                                                            $efektifBirimIskonto = (1 - $netFiyatCarpan) * 100;
                                                            
                                                            // Eğer kullanıcı max iskontoyu aşıyorsa (ve kampanya değilse)
                                                            // NOT: Eğer validDiscounts birden fazla ise (ör: 50-10), bunu bir kampanya veya özel bir durum olarak kabul ediyoruz
                                                            // ve max iskonto kontrolüne takılmamasını sağlıyoruz (veya ekranda olduğu gibi gösteriyoruz).
                                                            $isCascade = (count($validDiscounts) > 1);
                                                            
                                                            if ($camp === null && !$isCascade && !$discountDisabled && $iskonto_max > 0 && $efektifBirimIskonto > $iskonto_max + 0.01) {
                                                                // Max iskontoya sabitle
                                                                $netFiyatCarpan = (1 - ($iskonto_max / 100));
                                                                $efektifBirimIskonto = $iskonto_max;
                                                                $validDiscounts = [$iskonto_max]; // Detayları ezmek zorunda kalıyoruz
                                                            }

                                                            $aiskontom = $efektifBirimIskonto; // DB kaydı için toplam oran
                                                            
                                                            $aolcubirimi = $birimList[$fihid] ?? '';
                                                            $brut_tutar = $miktarsim * $fiyatsi; 
                                                            $aiskyaz   = $fiyatsi * $netFiyatCarpan; // Net Birim Fiyat
                                                            $son_tutar = $miktarsim * $aiskyaz;

                                                            // Brüt toplamları hesapla
                                                            switch ($fihrist['doviz']) {
                                                                case 'USD': $brut_usd_toplam += $brut_tutar; break;
                                                                case 'TL':  $brut_tl_toplam += $brut_tutar;  break;
                                                                case 'EUR': $brut_eur_toplam += $brut_tutar; break;
                                                            }

                                                            // Net toplamları hesapla
                                                            switch ($fihrist['doviz']) {
                                                                case 'USD': $usd_toplam += $son_tutar; break;
                                                                case 'TL':  $tl_toplam += $son_tutar;  break;
                                                                case 'EUR': $eur_toplam += $son_tutar; break;
                                                            }
                                                            $dovizSembol = ($fihrist["doviz"] == 'TL') ? "₺" : (($fihrist["doviz"] == 'USD') ? "$" : "€");
                                                    ?>
                                                            <tr style="border-bottom: 1px solid #e9ecef;">
                                                                <!-- Gizli inputlar -->
                                                                <input type="hidden" name="kod[<?php echo $fihrist["urun_id"]; ?>]" value="<?php echo $fihrist["stokkodu"]; ?>">
                                                                <input type="hidden" name="adi[<?php echo $fihrist["urun_id"]; ?>]" value="<?php echo $fihrist["stokadi"]; ?>">
                                                                <input type="hidden" name="miktar[<?php echo $fihrist["urun_id"]; ?>]" value="<?php echo $miktarsim; ?>">
                                                                <input type="hidden" name="birim[<?php echo $fihrist["urun_id"]; ?>]" value="<?php echo $birimList[$fihid] ?? '' ?>">
                                                                <input type="hidden" name="liste[<?php echo $fihrist["urun_id"]; ?>]" value="<?php echo $fiyatsi; ?>">
                                                                <input type="hidden" name="doviz[<?php echo $fihrist["urun_id"]; ?>]" value="<?php echo $fihrist["doviz"]; ?>">
                                                                <!-- Veritabanına efektif toplam iskontoyu gönderiyoruz, çünkü DB yapısı muhtemelen decimal -->
                                                                <input type="hidden" name="iskontoyolla[<?php echo $fihrist["urun_id"]; ?>]" value="<?php echo $aiskontom; ?>">
                                                                <input type="hidden" name="iskonto_formulu[<?php echo $fihrist["urun_id"]; ?>]" value="<?php echo htmlspecialchars($cleanDiscount); ?>">
                                                                <input type="hidden" name="nettutar[<?php echo $fihrist["urun_id"]; ?>]" value="<?php echo $aiskyaz; ?>">
                                                                <input type="hidden" name="tutar[<?php echo $fihrist["urun_id"]; ?>]" value="<?php echo $son_tutar; ?>">

                                                                <!-- Görünen hücreler - Logo ERP Tarzı -->
                                                                <td style="padding: 4px; font-size: 11px; font-weight: 600; vertical-align: middle;"><?php echo $fihrist["stokkodu"]; ?></td>
                                                                <td style="padding: 4px; font-size: 11px; vertical-align: middle;"><?php echo $fihrist["stokadi"]; ?></td>
                                                                <td style="padding: 4px; text-align: center; font-size: 11px; vertical-align: middle;"><?php echo $miktarsim; ?></td>
                                                                <td style="padding: 4px; text-align: center; font-size: 10px; color: #666; vertical-align: middle;"><?php echo $birimList[$fihid] ?? '-' ; ?></td>
                                                                <td style="padding: 4px; text-align: right; font-size: 11px; vertical-align: middle;">
                                                                    <?php echo number_format($fiyatsi, 2, ',', '.') . ' ' . $dovizSembol; ?>
                                                                </td>
                                                                
                                                                <!-- İSKONTO GÖSTERİMİ (LOGO ERP STYLE) -->
                                                                <td style="padding: 2px; text-align: center; vertical-align: middle;">
                                                                    <?php if(!empty($validDiscounts)): ?>
                                                                        <div style="display: flex; gap: 2px; justify-content: center; flex-wrap: wrap;">
                                                                            <?php foreach($validDiscounts as $index => $disc): ?>
                                                                                <div style="
                                                                                    background-color: <?php echo $index === 0 ? '#e8f0fe' : '#fff3cd'; ?>; 
                                                                                    color: <?php echo $index === 0 ? '#1967d2' : '#856404'; ?>;
                                                                                    border: 1px solid <?php echo $index === 0 ? '#b3d7ff' : '#ffeeba'; ?>;
                                                                                    border-radius: 3px;
                                                                                    padding: 1px 4px;
                                                                                    font-size: 10px;
                                                                                    font-weight: 500;
                                                                                    min-width: 35px;
                                                                                ">
                                                                                    %<?php echo number_format($disc, 2, ',', '.'); ?>
                                                                                </div>
                                                                            <?php endforeach; ?>
                                                                        </div>
                                                                    <?php else: ?>
                                                                         <span style="color: #ccc;">-</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                
                                                                <td style="padding: 4px; text-align: right; font-size: 11px; font-weight: 600; vertical-align: middle;">
                                                                    <?php echo number_format($aiskyaz, 2, ',', '.') . ' ' . $dovizSembol; ?>
                                                                </td>
                                                                <td style="padding: 4px; text-align: right; font-size: 11px; vertical-align: middle;">
                                                                    <div style="font-weight: 700; color: #444;">
                                                                        <?php echo $dovizSembol . ' ' . number_format($son_tutar, 2, ',', '.'); ?>
                                                                    </div>
                                                                    <?php if ($fihrist["doviz"] != 'TL'): ?>
                                                                        <div style="font-size: 9px; color: #999;">
                                                                            <?php 
                                                                            if ($fihrist["doviz"] == 'USD') {
                                                                                echo number_format($son_tutar * $dolarkur, 2, ',', '.') . ' ₺';
                                                                            } else {
                                                                                echo number_format($son_tutar * $eurokur, 2, ',', '.') . ' ₺';
                                                                            }
                                                                            ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                    <?php
                                                        }
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Detaylı Özet Tablosu -->
                                <?php
                                // Kur bilgileri
                                // Genel İskonto Oranını Al
                                $genelIskontoOrani = isset($_POST['genel_iskonto']) ? (float)$_POST['genel_iskonto'] : 0;
                                
                                // İskonto varsa toplamları güncelle
                                if ($genelIskontoOrani > 0) {
                                    $tl_toplam  -= $tl_toplam * ($genelIskontoOrani / 100);
                                    $usd_toplam -= $usd_toplam * ($genelIskontoOrani / 100);
                                    $eur_toplam -= $eur_toplam * ($genelIskontoOrani / 100);
                                }

                                // Kur bilgileri
                                $he = 0;
                                $dolarfiyat = $usd_toplam * $dolarkur;
                                $eurofiyatm = $eur_toplam * $eurokur;
                                $he = $tl_toplam + $dolarfiyat + $eurofiyatm;
                                $kdvtop = ($he * 20) / 100;
                                $gentop = $he + $kdvtop;
                                $kdv_rate = 0.20;
                                $kdv_eur    = $eur_toplam * $kdv_rate;
                                $gentop_eur = $eur_toplam + $kdv_eur;
                                $eur_to_usd_rate = $eurokur / $dolarkur;

                                // Sütunlar için TL ve USD karşılıklarını hesaplıyoruz
                                $subt_tl  = $eur_toplam * $eurokur;
                                $subt_usd = $eur_toplam * $eur_to_usd_rate;

                                $vat_tl   = $kdv_eur * $eurokur;
                                $vat_usd  = $kdv_eur * $eur_to_usd_rate;

                                $grand_tl   = $gentop_eur * $eurokur;    // aynı zamanda $gentop (TL) ile aynı sonuç
                                $grand_usd  = $gentop_eur * $eur_to_usd_rate;

                                // Brüt toplamlar için TL ve USD karşılıkları
                                $brut_tl_toplam_hesap = $brut_tl_toplam + ($brut_usd_toplam * $dolarkur) + ($brut_eur_toplam * $eurokur);
                                $brut_usd_toplam_hesap = $brut_tl_toplam / $dolarkur + $brut_usd_toplam + ($brut_eur_toplam * $eur_to_usd_rate);
                                $brut_eur_toplam_hesap = $brut_tl_toplam / $eurokur + ($brut_usd_toplam / $eur_to_usd_rate) + $brut_eur_toplam;

                                // İndirim tutarları
                                $indirim_tl = $brut_tl_toplam_hesap - ($tl_toplam + $dolarfiyat + $eurofiyatm);
                                $indirim_usd = $brut_usd_toplam_hesap - ($usd_toplam + ($tl_toplam / $dolarkur) + ($eur_toplam * $eur_to_usd_rate));
                                $indirim_eur = $brut_eur_toplam_hesap - $eur_toplam;
                                ?>
                                
                                <div class="card mb-3" style="border: 1px solid #dee2e6; border-radius: 6px;">
                                    <div class="card-body p-3">
                                        <table class="table table-bordered mb-2" style="font-size: 11px; margin-bottom: 0;">
                                            <thead style="background: #f8f9fa;">
                                                <tr>
                                                    <th style="padding: 6px 8px; font-size: 11px; font-weight: 600; text-align: left; width: 40%;">Açıklama</th>
                                                    <th style="padding: 6px 8px; font-size: 11px; font-weight: 600; text-align: right; width: 20%;">TL</th>
                                                    <th style="padding: 6px 8px; font-size: 11px; font-weight: 600; text-align: right; width: 20%;">€</th>
                                                    <th style="padding: 6px 8px; font-size: 11px; font-weight: 600; text-align: right; width: 20%;">$</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td style="padding: 6px 8px;">Brüt Toplam (İndirimsiz)</td>
                                                    <td style="padding: 6px 8px; text-align: right; font-weight: 600;"><?= number_format($brut_tl_toplam_hesap, 2, ',', '.') ?> TL</td>
                                                    <td style="padding: 6px 8px; text-align: right; font-weight: 600;"><?= number_format($brut_eur_toplam_hesap, 2, ',', '.') ?> €</td>
                                                    <td style="padding: 6px 8px; text-align: right; font-weight: 600;"><?= number_format($brut_usd_toplam_hesap, 2, ',', '.') ?> $</td>
                                                </tr>
                                                <tr style="background: #fff3cd;">
                                                    <td style="padding: 6px 8px;">
                                                        İndirim Tutarı 
                                                        <?php if ($genelIskontoOrani > 0): ?>
                                                            <span style="font-size: 10px; font-weight: bold; color: #dc3545;">
                                                                ( %<?= number_format($genelIskontoOrani, 2, ',', '.') ?> )
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td style="padding: 6px 8px; text-align: right; color: #dc3545; font-weight: 600;">- <?= number_format($indirim_tl, 2, ',', '.') ?> TL</td>
                                                    <td style="padding: 6px 8px; text-align: right; color: #dc3545; font-weight: 600;">- <?= number_format($indirim_eur, 2, ',', '.') ?> €</td>
                                                    <td style="padding: 6px 8px; text-align: right; color: #dc3545; font-weight: 600;">- <?= number_format($indirim_usd, 2, ',', '.') ?> $</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 6px 8px;">Net Toplam (KDV Hariç)</td>
                                                    <td style="padding: 6px 8px; text-align: right; font-weight: 600;"><?= number_format($he, 2, ',', '.') ?> TL</td>
                                                    <td style="padding: 6px 8px; text-align: right; font-weight: 600;"><?= number_format($eur_toplam, 2, ',', '.') ?> €</td>
                                                    <td style="padding: 6px 8px; text-align: right; font-weight: 600;"><?= number_format($usd_toplam + ($tl_toplam / $dolarkur) + ($eur_toplam * $eur_to_usd_rate), 2, ',', '.') ?> $</td>
                                                </tr>
                                                <tr style="background: #e7f3ff;">
                                                    <td style="padding: 6px 8px; font-weight: 600;">Genel Toplam (KDV Hariç)</td>
                                                    <td style="padding: 6px 8px; text-align: right; font-weight: 700; color: #0d6efd;"><?= number_format($he, 2, ',', '.') ?> TL</td>
                                                    <td style="padding: 6px 8px; text-align: right; font-weight: 700; color: #0d6efd;"><?= number_format($eur_toplam, 2, ',', '.') ?> €</td>
                                                    <td style="padding: 6px 8px; text-align: right; font-weight: 700; color: #0d6efd;"><?= number_format($usd_toplam + ($tl_toplam / $dolarkur) + ($eur_toplam * $eur_to_usd_rate), 2, ',', '.') ?> $</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 6px 8px;">KDV (<?= $kdv_rate * 100 ?>%)</td>
                                                    <td style="padding: 6px 8px; text-align: right; font-weight: 600;"><?= number_format($kdvtop, 2, ',', '.') ?> TL</td>
                                                    <td style="padding: 6px 8px; text-align: right; font-weight: 600;"><?= number_format($kdv_eur, 2, ',', '.') ?> €</td>
                                                    <td style="padding: 6px 8px; text-align: right; font-weight: 600;"><?= number_format($vat_usd, 2, ',', '.') ?> $</td>
                                                </tr>
                                                <tr style="background: #d4edda; border-top: 2px solid #28a745;">
                                                    <td style="padding: 8px; font-weight: 700; font-size: 12px;">Genel Toplam (KDV Dahil)</td>
                                                    <td style="padding: 8px; text-align: right; font-weight: 700; font-size: 12px; color: #28a745;"><?= number_format($gentop, 2, ',', '.') ?> TL</td>
                                                    <td style="padding: 8px; text-align: right; font-weight: 700; font-size: 12px; color: #28a745;"><?= number_format($gentop_eur, 2, ',', '.') ?> €</td>
                                                    <td style="padding: 8px; text-align: right; font-weight: 700; font-size: 12px; color: #28a745;"><?= number_format($grand_usd, 2, ',', '.') ?> $</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <div style="text-align: center; margin-top: 8px; padding-top: 8px; border-top: 1px solid #dee2e6;">
                                            <small style="font-size: 10px; color: #666;">
                                                <?= date('d.m.Y H:i') ?> tarihli <strong>Garanti BBVA</strong> kurları dikkate alınmıştır.
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Toplamlar tablosu -->

                                <!-- Modern Total Summary -->
                                <div class="row justify-content-end mt-4">
                                    <div class="col-md-6 col-lg-5">
                                        <div class="card total-card shadow-sm border-0">
                                            <div class="card-header py-2" style="background: #0d6efd;">
                                                <h6 class="mb-0 text-white" style="font-size: 14px; font-weight: 600;">💰 Fiyat Özeti</h6>
                                                <small style="color: rgba(255,255,255,0.7); font-size: 10px;">(Ana Para Birimi: €)</small>
                                            </div>
                                            <div class="card-body p-0">
                                                <div style="padding: 8px 16px; border-bottom: 1px solid #e9ecef; display: flex; justify-content: space-between; align-items: center;">
                                                    <span style="font-size: 12px; color: #666;">Ara Toplam</span>
                                                    <span style="font-size: 13px; font-weight: 600;"><?= number_format($eur_toplam, 2, ',', '.') ?> €</span>
                                                </div>
                                                <div style="padding: 8px 16px; border-bottom: 1px solid #e9ecef; display: flex; justify-content: space-between; align-items: center;">
                                                    <span style="font-size: 12px; color: #666;">KDV (<?= $kdv_rate * 100 ?>%)</span>
                                                    <span style="font-size: 13px; font-weight: 600;"><?= number_format($kdv_eur, 2, ',', '.') ?> €</span>
                                                </div>
                                                <div style="padding: 12px 16px; background: #f8f9fa; display: flex; justify-content: space-between; align-items: center;">
                                                    <span style="font-size: 13px; font-weight: 700; color: #0d6efd;">Genel Toplam</span>
                                                    <span style="font-size: 18px; font-weight: 700; color: #0d6efd;"><?= number_format($gentop_eur, 2, ',', '.') ?> €</span>
                                                </div>
                                                
                                                <div style="padding: 12px 16px; background: #fff; border-top: 1px solid #dee2e6;">
                                                    <div class="row g-2">
                                                        <div class="col-6">
                                                            <div style="padding: 8px; border: 1px solid #e9ecef; border-radius: 6px; background: #f8f9fa; text-align: center;">
                                                                <div style="font-size: 9px; color: #999; margin-bottom: 4px;">₺ TL Karşılığı</div>
                                                                <div style="font-size: 13px; font-weight: 700; color: #000;"><?= number_format($grand_tl, 2, ',', '.') ?> ₺</div>
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div style="padding: 8px; border: 1px solid #e9ecef; border-radius: 6px; background: #f8f9fa; text-align: center;">
                                                                <div style="font-size: 9px; color: #999; margin-bottom: 4px;">$ USD Karşılığı</div>
                                                                <div style="font-size: 13px; font-weight: 700; color: #000;"><?= number_format($grand_usd, 2, ',', '.') ?> $</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-footer py-2" style="background: #f8f9fa; border-top: 1px solid #dee2e6;">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span style="font-size: 10px; color: #666;">📅 <?= htmlspecialchars($dovizkuru["tarih"]) ?></span>
                                                    <div style="font-size: 11px; font-weight: 600; color: #333;">
                                                        <span style="background: #e7f3ff; padding: 2px 8px; border-radius: 4px; margin-right: 8px;">
                                                            1 € = <?= number_format($eurokur, 2, ',', '.') ?> ₺
                                                        </span>
                                                        <span style="background: #e7f3ff; padding: 2px 8px; border-radius: 4px;">
                                                            1 € = <?= number_format($eur_to_usd_rate, 4, ',', '.') ?> $
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- End Modern Total Summary -->
                                <!-- Bitiş: Yeni Toplam Özeti Kartı -->
                                <?php if (!empty($ekstra_bilgi)): ?>
                                    <div class="alert alert-secondary mb-4">
                                        <h5>Ekstra Notlar</h5>
                                        <div><?php echo nl2br(htmlspecialchars($ekstra_bilgi)); ?></div>
                                    </div>
                                <?php endif; ?>

                                <div class="card border-0">
                                    <div class="card-header py-2" style="background: #f8f9fa;">
                                        <h6 class="mb-0" style="font-size: 13px; font-weight: 600;">📜 Genel Şartlar ve Koşullar</h6>
                                    </div>
                                    <div class="card-body py-2 px-3">
                                        <?php
                                        $Date = date("Y-m-d");
                                        
                                        // Geçerlilik tarihini göster
                                        if (!empty($_POST['teklifgecerlilik'])) {
                                            $dt = DateTime::createFromFormat('Y-m-d\TH:i', $_POST['teklifgecerlilik']);
                                            if ($dt) {
                                                echo '<div style="font-size: 11px; color: #666; margin-bottom: 10px; padding: 6px; background: #fff3cd; border-left: 3px solid #ffc107; border-radius: 4px;">';
                                                echo '⏱ <strong>Geçerlilik:</strong> ' . $dt->format('d.m.Y H:i') . '\'a kadar';
                                                echo '</div>';
                                            }
                                        }
                                        
                                        // Sözleşme metnini göster
                                        $sozlesmeMetni = $_POST['sozlesme_metin_edited'] ?? '';
                                        if (!empty($sozlesmeMetni)) {
                                            echo '<div style="font-size: 11px; line-height: 1.6; color: #333; padding: 10px; background: #fff; border: 1px solid #e9ecef; border-radius: 4px;">';
                                            echo nl2br(htmlspecialchars_decode($sozlesmeMetni));
                                            echo '</div>';
                                        } else {
                                            echo '<div style="font-size: 11px; color: #999; padding: 10px; background: #f8f9fa; border: 1px dashed #dee2e6; border-radius: 4px; text-align: center;">';
                                            echo 'Sözleşme metni girilmemiş.';
                                            echo '</div>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </form>
                            <!-- Form sonu -->
                        </div>
                    </div>
                    <!-- /card -->

                </div>
            </div>
        </div>
    </main>
    <!-- ========== END MAIN CONTENT ========== -->

    <!-- ========== FOOTER ========== -->
    <footer class="footer-wrapper">
        <div class="container text-center">
            <p>© 2025 @ Gemas A.Ş - Tüm Hakları Saklıdır</p>
        </div>
    </footer>
    <!-- ========== END FOOTER ========== -->

    <!-- JS Global Compulsory -->
    <!-- Local JS dependencies -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Plugin Scripts -->
    <script src="assets/vendor/appear.js"></script>
    <script src="assets/vendor/jquery.countdown.min.js"></script>
    <script src="assets/vendor/hs-megamenu/src/hs.megamenu.js"></script>
    <script src="assets/vendor/svg-injector/dist/svg-injector.min.js"></script>
    <script src="assets/vendor/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.concat.min.js"></script>
    <script src="assets/vendor/jquery-validation/dist/jquery.validate.min.js"></script>
    <script src="assets/vendor/fancybox/jquery.fancybox.min.js"></script>
    <script src="assets/vendor/typed.js/lib/typed.min.js"></script>
    <script src="assets/vendor/slick-carousel/slick/slick.js"></script>
    <script src="assets/vendor/bootstrap-select/dist/js/bootstrap-select.min.js"></script>

    <!-- Electro Scripts -->
    <script src="assets/js/hs.core.js"></script>
    <script src="assets/js/components/hs.countdown.js"></script>
    <script src="assets/js/components/hs.header.js"></script>
    <script src="assets/js/components/hs.hamburgers.js"></script>
    <script src="assets/js/components/hs.unfold.js"></script>
    <script src="assets/js/components/hs.focus-state.js"></script>
    <script src="assets/js/components/hs.malihu-scrollbar.js"></script>
    <script src="assets/js/components/hs.validation.js"></script>
    <script src="assets/js/components/hs.fancybox.js"></script>
    <script src="assets/js/components/hs.onscroll-animation.js"></script>
    <script src="assets/js/components/hs.slick-carousel.js"></script>
    <script src="assets/js/components/hs.show-animation.js"></script>
    <script src="assets/js/components/hs.svg-injector.js"></script>
    <script src="assets/js/components/hs.go-to.js"></script>
    <script src="assets/js/components/hs.selectpicker.js"></script>

    <!-- DataTables -->
    <script src="assets/js/jquery.dataTables.min.js"></script>
    <script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="assets/js/dataTables.buttons.min.js"></script>
    <script src="assets/libs/datatables.net-buttons-bs4/js/buttons.bootstrap4.min.js"></script>
    <script src="assets/js/buttons.html5.min.js"></script>
    <script src="assets/libs/datatables.net-buttons/js/buttons.print.min.js"></script>
    <script src="assets/js/buttons.colVis.min.js"></script>
    <script src="assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>
    <script src="assets/js/datatables.init.js"></script>
    <script>
        $(function() {
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
</body>

</html>