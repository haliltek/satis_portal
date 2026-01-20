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
    if (!class_exists('Proje\DatabaseManager')) {
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
foreach ($satirlar as $row) {
    if ((int)$row['transaction_type'] === 0) {
        // Yurtdışı müşteri için ürün ismini İngilizce'ye çevir
        if ($isForeignCustomer && !empty($row['kod'])) {
            $stokKodu = trim($row['kod']);
            // Uzak MySQL'den İngilizce isim çek
            $translationDb = null;
            try {
                $hostname = "89.43.31.214";
                $username = "gemas_mehmet";
                $password = "2261686Me!";
                $dbname = "gemas_pool_technology";
                $port = 3306;
                $translationDb = new mysqli($hostname, $username, $password, $dbname, $port);
                if (!$translationDb->connect_error) {
                    $translationDb->set_charset("utf8");
                    // malzeme ve malzeme_translations tablolarından İngilizce isim çek
                    $stmt = $translationDb->prepare("
                        SELECT mt.ad, mt.name, mt.title, mt.baslik, mt.urun_adi, mt.malzeme_adi, mt.aciklama
                        FROM malzeme m
                        INNER JOIN malzeme_translations mt ON mt.malzeme_id = m.id
                        WHERE UPPER(TRIM(m.stok_kodu)) = UPPER(TRIM(?)) AND mt.locale = 'en'
                        LIMIT 1
                    ");
                    if ($stmt) {
                        $stmt->bind_param("s", $stokKodu);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($transRow = $result->fetch_assoc()) {
                            // İlk bulunan İngilizce ismi kullan
                            $englishName = '';
                            $nameColumns = ['ad', 'name', 'title', 'baslik', 'urun_adi', 'malzeme_adi', 'aciklama'];
                            foreach ($nameColumns as $col) {
                                if (!empty($transRow[$col])) {
                                    $englishName = trim($transRow[$col]);
                                    break;
                                }
                            }
                            if (!empty($englishName)) {
                                $row['adi'] = $englishName;
                            }
                        }
                        $stmt->close();
                    }
                    $translationDb->close();
                }
            } catch (Exception $e) {
                error_log("İngilizce isim çevirisi hatası: " . $e->getMessage());
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
            'total' => 'TOPLAM TUTAR',
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
            'net_price' => 'DISC. UNIT PRICE',
            'total' => 'TOTAL',
            'offer_summary' => 'Offer Summary & Currency Conversions',
            'main_unit' => 'Main Unit',
            'vat_included' => 'VAT Included',
            'currency' => 'Currency',
            'net' => 'Net',
            'vat' => 'VAT (20%)',
            'general' => 'General',
            'terms' => 'Terms and Conditions',
            'yes' => 'YES',
            'no' => 'NO',
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
    <style>
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

        @media print {
            @page {
                size: A4;
                margin: 5mm 10mm;
            }
            body {
                background-color: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                font-size: 11px;
            }
            .no-print, 
            .nav-tabs, 
            .footer, 
            .page-topbar, 
            #durum, 
            .btn,
            .rightbar-overlay,
            .modal {
                display: none !important;
            }
            
            /* Layout Resets */
            .main-content { margin-left: 0 !important; }
            .page-content { padding: 0 !important; margin: 0 !important; }
            .container-fluid { padding: 0 !important; max-width: 100% !important; }
            .card {
                border: none !important;
                box-shadow: none !important;
                margin-bottom: 10px !important;
            }
            .card-body { padding: 0 !important; }
            .card-header {
                background-color: transparent !important;
                border-bottom: 1px solid #ddd !important;
                padding: 5px 0 !important;
                margin-bottom: 5px !important;
            }
            .card-header h5 {
                font-size: 14px !important;
                font-weight: bold;
                margin: 0 !important;
            }

            /* Header Grid Fix */
            .row { display: flex !important; flex-wrap: nowrap !important; }
            .col-md-6 { width: 50% !important; flex: 0 0 50% !important; max-width: 50% !important; }
            .logo-container { text-align: left !important; }
            .qr-container { text-align: right !important; }
            .logo-container img { max-width: 120px !important; }
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
            
            /* Avoid page breaks inside elements */
            tr { page-break-inside: avoid; }
            .card, .table { page-break-inside: avoid; }
            
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
                font-size: 11px !important; /* Slightly larger than text but not huge */
                font-weight: bold !important;
            }
        }
    </style>
</head>

<body data-layout="horizontal" data-topbar="colored">
    <div id="layout-wrapper">
        <div class="main-content">
            <div class="page-content pt-2">
                <div class="container-fluid">
                    <!-- Logo ve QR Kod -->
                    <div class="row mb-3 align-items-center">
                        <div class="col-md-6 logo-container">
                            <a href="teklifsiparisler.php">
                                <img src="images/<?= htmlspecialchars($genelAyar["resim"] ?? ""); ?>" alt="Logo">
                            </a>
                        </div>
                        <div class="col-md-6 text-end qr-container">
                            <?php
                            function qrCode($icerik, $width = 130, $height = 130)
                            {
                                $apiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=%dx%d&data=%s';
                                return sprintf($apiUrl, $width, $height, urlencode($icerik));
                            }
                            // $url include/url.php'den geliyor, kontrol et
                            if (!isset($url) || empty($url)) {
                                $url = 'http://localhost/b2b-gemas-project-main';
                            }
                            $qrKodURL  = qrCode($url . '/offer_detail.php?te=' . $teklifId . '&sta=' . urlencode($siparisStatu), 120, 120);
                            ?>
                            <img src="<?= htmlspecialchars($qrKodURL) ?>" alt="QR Kod">
                            <p class="small mt-1">Mobil cihazınızla tarayarak detaylara ulaşın.</p>
                        </div>
                    </div>
                    <div class="row mb-3 no-print">
                        <div class="col text-end">
                            <button onclick="window.print()" class="btn btn-outline-secondary">PDF / Yazdır</button>
                        </div>
                    </div>

                    <!-- İki Tab: Detaylar ve Durum Düzenleme -->
                    <ul class="nav nav-tabs" id="teklifTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="detay-tab" data-bs-toggle="tab" data-bs-target="#detay" type="button" role="tab" aria-controls="detay" aria-selected="true"><?= t('offer_details', $isForeignCustomer); ?></button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="durum-tab" data-bs-toggle="tab" data-bs-target="#durum" type="button" role="tab" aria-controls="durum" aria-selected="false">Revize Süreci</button>
                        </li>
                    </ul>
                    <div class="tab-content" id="teklifTabContent">
                        <!-- Tab 1: Detaylar -->
                        <div class="tab-pane fade show active" id="detay" role="tabpanel" aria-labelledby="detay-tab">
                            <!-- Şirket ve Ürün Detayları -->
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5><?= t('company_product_info', $isForeignCustomer); ?></h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered">
                                        <tbody>
                                            <tr>
                                                <th><?= t('company_name', $isForeignCustomer); ?></th>
                                                <td>
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
                                                </td>
                                                <th><?= t('prepared_by', $isForeignCustomer); ?></th>
                                                <td><?= htmlspecialchars($personelProfil["adsoyad"] ?? ''); ?> (<small><?= htmlspecialchars($hazirlayanKaynak) ?></small>)</td>
                                            </tr>
                                            <tr>
                                                <th><?= t('customer_record', $isForeignCustomer); ?></th>
                                                <td><?= empty($teklifBilgi["sirket_arp_code"]) ? '<span class="text-danger">' . t('no', $isForeignCustomer) . '</span>' : '<span class="text-success">' . t('yes', $isForeignCustomer) . '</span>'; ?></td>
                                                <th><?= t('email', $isForeignCustomer); ?></th>
                                                <td><?= htmlspecialchars($contactMail); ?></td>
                                            </tr>
                                            <tr>
                                                <th><?= t('offer_validity_date', $isForeignCustomer); ?></th>
                                                <td><?= htmlspecialchars($gecerlilikDisplay); ?></td>
                                                <th><?= t('phone', $isForeignCustomer); ?></th>
                                                <td><?= htmlspecialchars($contactPhone); ?></td>
                                            </tr>
                                            <tr>
                                                <th><?= t('offer_process_date', $isForeignCustomer); ?></th>
                                                <td><?= htmlspecialchars($teklifBilgi["tekliftarihi"]); ?></td>
                                                <th><?= t('offer_code', $isForeignCustomer); ?></th>
                                                <td><?= htmlspecialchars($teklifBilgi["teklifkodu"]); ?></td>
                                            </tr>

                                            <tr>
                                                <th><?= t('delivery_place', $isForeignCustomer); ?></th>
                                                <td colspan="3"><?= htmlspecialchars($teklifBilgi["teslimyer"]); ?></td>
                                            </tr>
                                            <tr>
                                                <th><?= t('payment_plan', $isForeignCustomer); ?></th>
                                                <td colspan="3"><?= htmlspecialchars($payPlanDisplay); ?></td>
                                            </tr>
                                            <?php if (!empty($teklifBilgi["notes1"])): ?>
                                            <tr>
                                                <th><?= t('extra_info_notes', $isForeignCustomer); ?></th>
                                                <td colspan="3"><?= nl2br(strip_tags($teklifBilgi["notes1"])); ?></td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>

                                    <!-- Ürün Listesi -->
                                    <div class="table-responsive">
                                        <table class="table table-bordered mb-0" style="font-size: 11px;">
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
                                            <thead style="background: #f8f9fa;">
                                                <tr>
                                                    <th style="padding: 6px; width: 40px; text-align: center;">#</th>
                                                    <th style="padding: 6px;"><?= t('product_service', $isForeignCustomer); ?></th>
                                                    <th style="padding: 6px; text-align: center;"><?= t('quantity', $isForeignCustomer); ?></th>
                                                    <th style="padding: 6px; text-align: center;"><?= t('unit', $isForeignCustomer); ?></th>
                                                    <th style="padding: 6px; text-align: right;"><?= t('list_price', $isForeignCustomer); ?></th>
                                                    <th style="padding: 6px; text-align: center;"><?= t('discount', $isForeignCustomer); ?></th>
                                                    <th style="padding: 6px; text-align: right;"><?= t('net_price', $isForeignCustomer); ?></th>
                                                    <th style="padding: 6px; text-align: right;"><?= t('total', $isForeignCustomer); ?></th>
                                                    <th style="padding: 6px; text-align: center;"><?= t('vat_rate', $isForeignCustomer); ?></th>
                                                    <th style="padding: 6px; text-align: right;"><?= t('vat_unit_price', $isForeignCustomer); ?></th>
                                                    <th style="padding: 6px; text-align: right;"><?= t('grand_total', $isForeignCustomer); ?></th>
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
                            <div class="card mt-3">
                                <div class="card-header text-secondary">
                                    <h5 class="mb-0 text-black"><?= t('offer_summary', $isForeignCustomer); ?></h5>
                                </div>
                                <div class="card-body">
                                    <div class="text-center mb-4">
                                        <h1 class="display-4 font-weight-bold">
                                            <?= number_format($anaGross, 2, ',', '.'); ?> <?= $anaSembol; ?>
                                        </h1>
                                        <p class="mb-0 text-muted"><?= t('main_unit', $isForeignCustomer); ?>: <?= $anaBirim; ?> (<?= t('vat_included', $isForeignCustomer); ?>)</p>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered text-right">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th class="text-left"><?= t('currency', $isForeignCustomer); ?></th>
                                                    <th><?= t('net', $isForeignCustomer); ?></th>
                                                    <th><?= t('vat', $isForeignCustomer); ?></th>
                                                    <th><?= t('general', $isForeignCustomer); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if ($dovizGoster === 'EUR'): ?>
                                                <tr class="table-primary">
                                                    <th class="text-left">EUR</th>
                                                    <td><?= number_format($netEUR,  2, ',', '.'); ?> €</td>
                                                    <td><?= number_format($kdvEUR,  2, ',', '.'); ?> €</td>
                                                    <td><?= number_format($grossEUR, 2, ',', '.'); ?> €</td>
                                                </tr>
                                                <?php elseif ($dovizGoster === 'TL'): ?>
                                                <tr class="table-success">
                                                    <th class="text-left">TL</th>
                                                    <td><?= number_format($netTL,  2, ',', '.'); ?> ₺</td>
                                                    <td><?= number_format($kdvTL,  2, ',', '.'); ?> ₺</td>
                                                    <td><?= number_format($grossTL, 2, ',', '.'); ?> ₺</td>
                                                </tr>
                                                <?php elseif ($dovizGoster === 'USD'): ?>
                                                <tr class="table-warning">
                                                    <th class="text-left">USD</th>
                                                    <td><?= number_format($netUSD,  2, ',', '.'); ?> $</td>
                                                    <td><?= number_format($kdvUSD,  2, ',', '.'); ?> $</td>
                                                    <td><?= number_format($grossUSD, 2, ',', '.'); ?> $</td>
                                                </tr>
                                                <?php else: // TUMU ?>
                                                <tr class="table-primary">
                                                    <th class="text-left">EUR</th>
                                                    <td><?= number_format($netEUR,  2, ',', '.'); ?> €</td>
                                                    <td><?= number_format($kdvEUR,  2, ',', '.'); ?> €</td>
                                                    <td><?= number_format($grossEUR, 2, ',', '.'); ?> €</td>
                                                </tr>
                                                <tr class="table-success">
                                                    <th class="text-left">TL <small class="text-muted">(≈)</small></th>
                                                    <td class="small text-muted"><?= number_format($netTL,  2, ',', '.'); ?> ₺</td>
                                                    <td class="small text-muted"><?= number_format($kdvTL,  2, ',', '.'); ?> ₺</td>
                                                    <td class="small text-muted"><?= number_format($grossTL, 2, ',', '.') ?> ₺</td>
                                                </tr>
                                                <tr class="table-warning">
                                                    <th class="text-left">USD <small class="text-muted">(≈)</small></th>
                                                    <td class="small text-muted"><?= number_format($netUSD,  2, ',', '.'); ?> $</td>
                                                    <td class="small text-muted"><?= number_format($kdvUSD,  2, ',', '.'); ?> $</td>
                                                    <td class="small text-muted"><?= number_format($grossUSD, 2, ',', '.') ?> $</td>
                                                </tr>
                                                <?php endif; ?>
                                            </tbody>

                                        </table>
                                    </div>
                                </div>
                            </div>
                            <!-- /Teklif Özeti & Döviz Dönüşümleri -->

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
                                <div class="card mt-4">
                                    <div class="card-header text-secondary">
                                        <h5 class="mb-0 text-black"><?= htmlspecialchars($soz['sozlesmeadi']); ?></h5>
                                    </div>
                                    <div class="card-body terms-content">
                                        <?= $soz['sozlesme_metin']; /* zaten HTML <ol>…</ol> içeriyor */ ?>
                                    </div>
                                </div>
                            <?php
                            endif;
                            ?>

                            <!-- İşlem Butonları -->
                            <?php if ($isEditable): ?>
                                <div class="card mt-3">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-1">İşlem Seçenekleri</h5>
                                        <p class="mb-0">Mevcut Durum: <strong><?= htmlspecialchars($currentDurum); ?></strong></p>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-end align-items-center gap-2">
                                            <form action="offer_detail.php?te=<?= urlencode($teklifId); ?>&sta=<?= urlencode($siparisStatu); ?>" method="POST" data-parsley-validate class="d-inline">
                                                <input type="hidden" name="durum" value="Onayla">
                                                <button type="submit" class="btn btn-success" data-bs-toggle="tooltip" title="<?= htmlspecialchars($onayTooltip); ?>">Onayla</button>
                                            </form>
                                            <form action="offer_detail.php?te=<?= urlencode($teklifId); ?>&sta=<?= urlencode($siparisStatu); ?>" method="POST" data-parsley-validate class="d-inline">
                                                <input type="hidden" name="durum" value="Reddet">
                                                <button type="submit" class="btn btn-danger" data-bs-toggle="tooltip" title="<?= htmlspecialchars($redTooltip); ?>">Reddet</button>
                                            </form>
                                            <?php if ($canRevise): ?>
                                                <button type="button" class="btn btn-warning" data-bs-toggle="tooltip" title="Revize etmek için tıklayın." onclick="switchTabToDurum()">Revize Et</button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-warning" disabled data-bs-toggle="tooltip" title="Revize hakkınız doldu.">Revize Hakkınız Doldu</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="card mt-3">
                                    <div class="card-body">
                                        <p class="mb-0">Bu teklif üzerinde değişiklik yapılamaz. Mevcut Durum: <strong><?= htmlspecialchars($currentDurum); ?></strong></p>
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div>

                        <!-- Tab 2: Durum Düzenleme (Revize İşlemi) -->
                        <div class="tab-pane fade" id="durum" role="tabpanel" aria-labelledby="durum-tab">
                            <div class="card mt-3">

                                <div class="card-header">
                                    <h5>Revize Süreci</h5>
                                </div>
                                <div class="card-body">
                                    <form action="offer_detail.php?te=<?= urlencode($teklifId); ?>&sta=<?= urlencode($siparisStatu); ?>" method="POST" data-parsley-validate>
                                        <input type="hidden" name="durum" value="Revize Et">
                                        <div class="mb-3">
                                            <label for="notlar" class="form-label">Revize Notunuz:</label>
                                            <textarea class="form-control" id="notlar" name="notlar" rows="4"
                                                placeholder="Revize işlemi ile ilgili not ekleyin"
                                                data-parsley-minlength="10"
                                                data-parsley-maxlength="300"
                                                data-parsley-trigger="change"></textarea>
                                        </div>
                                        <div class="text-end">
                                            <button type="submit" class="btn btn-warning" data-bs-toggle="tooltip" title="Revize işlemini tamamlamak için tıklayın.">Revize Güncelle</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5>Durum Geçmişi</h5>
                                </div>
                                <div class="card-body table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Tarih</th>
                                                <th>Eski Durum</th>
                                                <th>Yeni Durum</th>
                                                <th>Değişikliği Yapan</th>
                                                <th>Notlar</th>
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
                                                    echo '<td>' . htmlspecialchars($hSatir["degistirme_tarihi"]) . '</td>';
                                                    echo '<td>' . htmlspecialchars($hSatir["eski_durum"]) . '</td>';
                                                    echo '<td>' . htmlspecialchars($hSatir["yeni_durum"]) . '</td>';
                                                    echo '<td>' . htmlspecialchars($pAd) . '</td>';
                                                    echo '<td>' . htmlspecialchars($hSatir["notlar"]) . '</td>';
                                                    echo '</tr>';
                                                }
                                            } else {
                                                echo '<tr><td colspan="5" class="text-center">Kayıt Yok</td></tr>';
                                            }
                                            ?>
                                        </tbody>
                                    </table>
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
            <footer class="footer mt-4">
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
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        function switchTabToDurum() {
            var triggerEl = document.querySelector('#durum-tab');
            bootstrap.Tab.getInstance(triggerEl).show();
        }
    </script>
</body>

</html>
