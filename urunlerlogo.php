<?php
// urunlerlogo.php

include "fonk.php";
require_once __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/config/config.php';
$isAjax = isset($_GET['ajax']) && $_GET['ajax'] == '1';
oturumkontrol();
ob_start();

date_default_timezone_set('Europe/Istanbul');

global $db, $gemas_web_db, $gemas_logo_db, $gempa_logo_db, $yonetici_id_sabit;

require_once 'services/AuthService.php';
require_once 'services/LoggerService.php';
require_once 'services/MaterialService.php';
require_once 'services/PriceUpdater.php';
require_once 'services/ProductTranslationService.php';
require_once 'services/MailService.php';
require_once 'services/MailRepository.php';
require_once 'services/PriceUpdateService.php';
require_once 'services/ActiveStatusService.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

$logger = new LoggerService(__DIR__ . '/error.log');
$yonetici_id_sabit = $_SESSION['yonetici_id'] ?? 'default_id';
$authService = new AuthService($db, $yonetici_id_sabit);
$authService->checkSession();
$user_type = $authService->getUserType();
if (!$user_type) {
    echo '<div class="alert alert-danger" role="alert">Kullanıcı türü alınamadı. Lütfen tekrar giriş yapınız.</div>';
    exit();
}

// Redirect non-admin users (e.g. Personnel) to their specific page
if ($user_type !== 'Yönetici') {
    $queryString = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
    header("Location: urunlerlogo_personel.php" . $queryString);
    exit();
}

$priceUpdater = new PriceUpdater($db, $gemas_logo_db, $gempa_logo_db, $gemas_web_db, $logger, $yonetici_id_sabit);
$productTranslationService = new ProductTranslationService(
    $gemas_web_db,
    $logger,
    $gemas_logo_db,
    $gempa_logo_db,
    $db
);
$materialService = new MaterialService($config, $logger);

$mailService = new MailService('mail.gemas.com.tr', 465, 'ssl', 'fiyat@gemas.com.tr', 'Test123Test321', $logger);
// $mailService = new MailService('smtp.gmail.com', 587, 'tsl', 'video.gemas@gmail.com', 'tgdt arlc axah hrba', $logger); //Peer certificate CN=`florida.hozzt.com' did not match expected CN=`smtp.gmail.com'
// $mailService = new MailService('b2b.gemas.com.tr', 465,'ssl', 'bilgi@b2b.gemas.com.tr', 'Asdas123456!', $logger);
$mailRepository = new MailRepository($db, $logger);
$priceUpdateService = new PriceUpdateService($priceUpdater, $mailService, $logger);
$activeStatusService = new ActiveStatusService($priceUpdater, $mailService, $logger);

if (isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    ob_clean();
    $action = $_POST['action'];
    if ($action === 'getDetails') {
        $stokKodu = trim($_POST['stok_kodu'] ?? '');
        if (empty($stokKodu)) {
            echo json_encode(['error' => 'Stok kodu eksik'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $locales = ['tr', 'en', 'ru', 'fr'];
        $result = $productTranslationService->getMaterialAndProductsByStockCode($stokKodu, $locales);
        if (!$result) {
            echo json_encode(['error' => 'Web sitesinde böyle bir ürün bulunamadı.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $logger->log("getDetails: Stok Kodu: $stokKodu, Sonuç: " . json_encode($result, JSON_UNESCAPED_UNICODE));
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    } elseif ($action === 'updateDetails') {
        $result = $productTranslationService->updateMaterialAndProducts($_POST);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    } elseif ($action === 'updateActive') {
        $stokKodu   = trim($_POST['stok_kodu'] ?? '');
        $gempaRef   = intval($_POST['gempa_logicalref'] ?? 0);
        $gemasRef   = intval($_POST['gemas_logicalref'] ?? 0);
        $active     = intval($_POST['active'] ?? 0);
        $oldStatus  = intval($_POST['old_status'] ?? ($active ? 0 : 1));
        $urunAdi    = trim($_POST['urun_adi'] ?? '');
        $sendMail   = isset($_POST['send_mail']) && $_POST['send_mail'] === '1';
        $mailList   = [];
        if ($sendMail && !empty($_POST['selected_mail_ids'] ?? '')) {
            $ids = array_map('intval', explode(',', $_POST['selected_mail_ids']));
            $mailList = $mailRepository->getMailList();
            $mailList = array_filter($mailList, function($m) use ($ids) { return in_array($m['mail_id'], $ids); });
        }
        $logger->log("updateActive request: $stokKodu gempa=$gempaRef gemas=$gemasRef active=$active sendMail=".($sendMail?'1':'0'));
        $result = $activeStatusService->updateStatusWithMail($stokKodu, $urunAdi, $oldStatus, $active, $gempaRef, $gemasRef, $sendMail, $mailList);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    } elseif ($action === 'getInvoiceHistory') {
        $stokKodu = trim($_POST['stok_kodu'] ?? '');
        $limit    = intval($_POST['limit'] ?? 3);
        if (empty($stokKodu)) {
            echo json_encode(['error' => 'Stok kodu eksik'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}

// Fiyat güncelleme isteği (sadece fiyat güncelleme)
if (isset($_POST['update_fiyat'])) {
    $rawKod    = trim($_POST['stok_kodu'] ?? '');
    $stok_kodu = $rawKod;
    $logicalref     = intval($_POST['logicalref'] ?? 0);
    $gempa_logicalref = intval($_POST['gempa_logicalref'] ?? 0);
    $gemas_logicalref = intval($_POST['gemas_logicalref'] ?? 0);
    $domestic_price = floatval($_POST['yeni_domestic_price'] ?? 0);
    $export_price   = floatval($_POST['yeni_export_price'] ?? 0);
    if (empty($stok_kodu) || $domestic_price < 0 || $export_price < 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Veriler eksik veya hatalı. Stok kodu: ' . $stok_kodu . ', Logicalref: ' . $logicalref . ', Yurtiçi Fiyat: ' . $domestic_price . ', İhracat Fiyat: ' . $export_price
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    $result = $priceUpdater->updatePrices(
        $stok_kodu,
        $gempa_logicalref,
        $gemas_logicalref,
        $domestic_price,
        $export_price
    );
    header('Content-Type: application/json');
    echo json_encode($result);
    exit();
}

// Yeni ürün ekleme işlemi
if (isset($_POST['add_new_material_card'])) {
    $result = $materialService->createMaterialCard($_POST);
    if ($isAjax) {
        header('Content-Type: application/json');
        ob_clean();
        echo json_encode($result);
        exit;
    } else {
        $_SESSION['message'] = $result['success']
            ? '<div class="alert alert-success">' . $result['message'] . '</div>'
            : '<div class="alert alert-danger">' . $result['message'] . '</div>';
        header('Location: urunlerlogo.php');
        exit;
    }
}
if (isset($_SESSION['message'])) {
    echo $_SESSION['message'];
    unset($_SESSION['message']);
}

if (isset($_POST['action']) && $_POST['action'] === 'updatePriceWithMail') {
    $logger->info("DEBUG - updatePriceWithMail POST:" . json_encode($_POST, JSON_UNESCAPED_UNICODE));
    header('Content-Type: application/json; charset=utf-8');
    $stok_kodu      = $db->real_escape_string($_POST['stok_kodu'] ?? '');
    $logicalref     = intval($_POST['logicalref'] ?? 0);
    $gempa_logicalref = intval($_POST['gempa_logicalref'] ?? 0);
    $gemas_logicalref = intval($_POST['gemas_logicalref'] ?? 0);
    $domestic_price = floatval($_POST['yeni_domestic_price'] ?? 0);
    $export_price   = floatval($_POST['yeni_export_price'] ?? 0);
    $send_mail      = (isset($_POST['send_mail']) && $_POST['send_mail'] === '1') ? true : false;
    $selected_mail_ids = isset($_POST['selected_mail_ids']) ? $_POST['selected_mail_ids'] : '';
    $urun_adi            = trim($_POST['urun_adi'] ?? '');
    $old_domestic_price   = floatval($_POST['old_domestic_price'] ?? 0);
    $old_export_price     = floatval($_POST['old_export_price'] ?? 0);

    if (empty($stok_kodu) || $domestic_price < 0 || $export_price < 0) {
        echo json_encode(['status' => 'error', 'message' => 'Geçersiz veriler.']);
        exit();
    }

    // Eğer mail gönderimi seçilmişse, MailRepository üzerinden mail listesi çekiyoruz.
    $mailList = [];
    if ($send_mail && !empty($selected_mail_ids)) {
        $mail_ids = explode(',', $selected_mail_ids);
        $mail_ids = array_map('intval', $mail_ids);
        $mailList = $mailRepository->getMailList();
        $mailList = array_filter($mailList, function ($mail) use ($mail_ids) {
            return in_array($mail['mail_id'], $mail_ids);
        });
    }
    $logger->info("Bulk güncelleme AJAX isteği geldi: " . json_encode($_POST, JSON_UNESCAPED_UNICODE));

    $result = $priceUpdateService->updatePriceWithMail(
        stokKodu: $stok_kodu,
        urunAdi: $urun_adi,
        oldDomesticPrice: $old_domestic_price,
        oldExportPrice: $old_export_price,
        gempaLogoLogicalRef: $gempa_logicalref,
        gemasLogoLogicalRef: $gemas_logicalref,
        newDomesticPrice: $domestic_price,
        newExportPrice: $export_price,
        sendMail: $send_mail,
        mailList: $mailList
    );

    $logger->info('Price update result: ' . json_encode($result));

    echo json_encode($result);
    exit();
}
if (isset($_POST['bulk_action']) && $_POST['bulk_action'] === 'clear_preview') {
    unset($_SESSION['previewRows']);
    header('Location: urunlerlogo.php');
    exit;
}

if (isset($_POST['bulk_action']) && $_POST['bulk_action'] === 'download_excel') {
    $rows = json_decode($_POST['rows'] ?? '[]', true);
    if (!is_array($rows) || empty($rows)) {
        echo 'Eksik veri';
        exit;
    }
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray(
        ['Stok Kodu','Eski Yurtİçi','Yeni Yurtİçi','Yurtİçi Fark','Eski Export','Yeni Export','Export Fark'],
        null,
        'A1'
    );
    $rowIdx = 2;
    foreach ($rows as $r) {
        $oldY = isset($r['oldY']) ? (float)$r['oldY'] : 0;
        $newY = isset($r['newY']) ? (float)$r['newY'] : 0;
        $oldE = isset($r['oldE']) ? (float)$r['oldE'] : 0;
        $newE = isset($r['newE']) ? (float)$r['newE'] : 0;
        $sheet->fromArray([
            $r['kod'] ?? '',
            $oldY,
            $newY,
            $newY - $oldY,
            $oldE,
            $newE,
            $newE - $oldE,
        ], null, 'A' . $rowIdx);
        $rowIdx++;
    }
    $sheet->getStyle('B2:G' . ($rowIdx - 1))->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
    $filename = 'fiyat_farklari_' . date('Ymd_His') . '.xlsx';
    ob_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');
    exit;
}


if (
    isset($_POST['bulk_action'])
    && $_POST['bulk_action'] === 'upload_excel'
    && isset($_FILES['excel_file'])
    && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK
) {
    $tmpPath = $_FILES['excel_file']['tmp_name'];
    try {
        $spreadsheet = IOFactory::load($tmpPath);
    } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
        die("Excel dosyası açılamadı: " . $e->getMessage());
    }
    $sheet = $spreadsheet->getActiveSheet();

    $previewRows = [];

    /**
     * Excel’den gelen “$ 1.00” / “€ 1.234,56” / “1,272” / “1.129” / “PAP” / “-” / “€ -” gibi formatları
     * saf bir float’a veya null’a çevirir.
     */
    $normalize = function ($v) {
        // 1) Trim: baştaki/sondaki boşlukları al
        $trimmed = trim((string)$v);
        $upper   = mb_strtoupper($trimmed, 'UTF-8');

        // 2) Eğer boş, "-", "$ -", "€ -", "PAP" ise → null döndür
        if (
            $trimmed === ''
            || $upper === '-'
            || $upper === '$ -'
            || $upper === '€ -'
            || $upper === 'PAP'
        ) {
            return null;
        }

        // 3) Para sembollerini temizle (hem $ hem €), sonra tekrar trim yap
        $noCurrency = trim(str_replace(['$', '€'], '', $trimmed));

        // 4) “Sadece virgül + 3 hane” var mı? (örneğin "1,272") → binlik virgül
        if (
            strpos($noCurrency, ',') !== false
            && strpos($noCurrency, '.') === false
            && preg_match('/^[0-9]{1,3}(,[0-9]{3})+$/', $noCurrency)
        ) {
            // "1,272" → "1272"
            $noCurrency = str_replace(',', '', $noCurrency);
        }
        // 5) “Hem nokta hem virgül var mı?” (örneğin "1.234,56") → EU‐Style ondalık
        elseif (strpos($noCurrency, '.') !== false && strpos($noCurrency, ',') !== false) {
            // Örnek: "1.234,56" → ["1.234","56"]
            $parts  = explode(',', $noCurrency);
            // Binlik noktalarını sil: "1.234" → "1234"
            $intPart = str_replace('.', '', $parts[0]);
            $decPart = $parts[1];
            $noCurrency = $intPart . '.' . $decPart; // "1234.56"
        }
        // 6) “Sadece virgül var” (örneğin "7,20") → ondalık virgül
        elseif (strpos($noCurrency, ',') !== false) {
            // "7,20" → "7.20"
            $noCurrency = str_replace(',', '.', $noCurrency);
        }
        // 7) “Sadece nokta + 3 hane” var mı? (örneğin "1.129") → binlik nokta
        elseif (preg_match('/^([0-9]+)\.([0-9]{3})$/', $noCurrency, $m)) {
            // "1.129" → "1129"
            $noCurrency = $m[1] . $m[2];
        }
        // 8) Geri kalan (örneğin "2.45" veya "438") olduğu gibi bırakılır.

        // 9) Tekrar trim: gereksiz boşluk kalmadığından emin ol
        $noCurrency = trim($noCurrency);

        // 10) Artık elimizde saf rakamsal bir dize var mı? → float döndür, yoksa null
        if (!is_numeric($noCurrency)) {
            return null;
        }
        $num = (float)$noCurrency;
        // Excel'den gelen 0 veya negatif değerler geçersiz kabul edilir
        if ($num <= 0) {
            return null;
        }
        // Ondalık kısmı iki haneye yuvarla
        return round($num, 2);
    };



    foreach ($sheet->getRowIterator(2) as $row) {
        $cells = [];
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);
        foreach ($cellIterator as $cell) {
            $cells[] = $cell->getValue();
        }

        $kod      = trim($cells[0] ?? '');
        $aciklama = trim($cells[1] ?? '');
        $rawY     = trim($cells[2] ?? '');
        $rawE     = trim($cells[3] ?? '');

        if ($kod === '') {
            continue;
        }

        $yeniYurtici = $normalize($rawY);
        $yeniExport  = $normalize($rawE);

        $safeKod = $db->real_escape_string($kod);
        $safeKodNoZero = $db->real_escape_string(ltrim($kod, '0'));
        $sql = "
            SELECT
                fiyat            AS yurticiEski,
                export_fiyat     AS exportEski,
                LOGICALREF       AS logicalref
                -- GEMPA2026LOGICAL AS gempa_logicalref,
                -- GEMAS2026LOGICAL AS gemas_logicalref
            FROM urunler
            WHERE stokkodu = '{$safeKod}' OR stokkodu = '{$safeKodNoZero}'
            LIMIT 1
        ";
        $result = $db->query($sql);
        $rowData = $result ? $result->fetch_assoc() : [];

        $previewRows[] = [
            'kod'          => $kod,
            'aciklama'     => $aciklama,
            'yurticiEski'  => isset($rowData['yurticiEski']) ? (float)$rowData['yurticiEski'] : null,
            'exportEski'   => isset($rowData['exportEski']) ? (float)$rowData['exportEski'] : null,
            'yeniYurtici'  => $yeniYurtici,   // artık mutlaka float veya null
            'yeniExport'   => $yeniExport,    // artık mutlaka float veya null
            'logicalref'   => $rowData['logicalref']     ?? 0,
            'gempa'        => $rowData['gempa_logicalref'] ?? 0,
            'gemas'        => $rowData['gemas_logicalref'] ?? 0,
        ];
    }

    // >>> İsterseniz loglamak için:
    $logger->info("Excel'den previewRows olarak okundu: " . json_encode($previewRows, JSON_UNESCAPED_UNICODE));

    $_SESSION['previewRows'] = $previewRows;
    header('Location: urunlerlogo.php');
    exit;
}


// Aşağıdaki kodlar, MailRepository üzerinden ekleme, güncelleme, silme işlemlerini gerçekleştirmektedir.

if (isset($_POST['action']) && $_POST['action'] === 'getMailList') {
    header('Content-Type: application/json; charset=utf-8');
    $mailList = $mailRepository->getMailList();
    echo json_encode($mailList);
    exit();
}

if (isset($_POST['action']) && $_POST['action'] === 'addMail') {
    header('Content-Type: application/json; charset=utf-8');
    $email   = trim($_POST['email'] ?? '');
    $adsoyad = trim($_POST['adsoyad'] ?? '');
    $result  = $mailRepository->addMail($email, $adsoyad);
    echo json_encode($result);
    exit();
}

if (isset($_POST['action']) && $_POST['action'] === 'updateMail') {
    header('Content-Type: application/json; charset=utf-8');
    $mail_id = intval($_POST['mail_id'] ?? 0);
    $email   = trim($_POST['email'] ?? '');
    $adsoyad = trim($_POST['adsoyad'] ?? '');
    $result  = $mailRepository->updateMail($mail_id, $email, $adsoyad);
    echo json_encode($result);
    exit();
}

if (isset($_POST['action']) && $_POST['action'] === 'deleteMail') {
    header('Content-Type: application/json; charset=utf-8');
    $mail_id = intval($_POST['mail_id'] ?? 0);
    $result  = $mailRepository->deleteMail($mail_id);
    echo json_encode($result);
    exit();
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title><?php echo $sistemayar["title"]; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $sistemayar["description"]; ?>" name="description" />
    <meta content="<?php echo $sistemayar["keywords"]; ?>" name="keywords" />
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link href="assets/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" />
    <link href="assets/css/icons.min.css" rel="stylesheet" />
    <link href="assets/css/app.min.css" id="app-style" rel="stylesheet" />
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" />
    <link href="assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css" rel="stylesheet" />
    <link href="assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <script src="//cdn.ckeditor.com/4.18.0/full/ckeditor.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        /* İkonu tıklanınca -180° döndürmek için */
        #toggleDetailsBtn .bi-chevron-down {
            transition: transform 0.3s ease;
        }

        #toggleDetailsBtn[aria-expanded="true"] .bi-chevron-down {
            transform: rotate(-180deg);
        }

        /* Buton hover’unda parlaklaşma efekti */
        #toggleDetailsBtn:hover {
            filter: brightness(1.1);
        }
    </style>
</head>

<body data-layout="horizontal" data-topbar="colored">
    <div id="layout-wrapper">
        <header id="page-topbar">
            <?php include "menuler/ustmenu.php"; ?>
            <?php include "menuler/solmenu.php"; ?>
        </header>
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">

                    <!-- ============================================================== -->
                    <!-- 1. Üst Araç Çubuğu: Yeni Ürün, Yardım ve Excel Yükleme Formu   -->
                    <!-- ============================================================== -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">

                                <!-- Sol tarafta: “Yeni Ürün Tanımla” & “Yardım” butonları -->
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button"
                                        class="btn btn-success"
                                        data-bs-toggle="modal"
                                        data-bs-target="#yenikategoriModal">
                                        <i class="bi bi-plus-circle me-1"></i> Yeni Ürün Tanımlayınız
                                    </button>
                                </div>

                                <!-- Sağ tarafta: Excel Yükleme formu -->
                                <button type="button"
                                    id="bulkUpdateBtn"
                                    class="btn btn-warning btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#bulkModal">
                                    <i class="bi bi-upload me-1"></i> Toplu Malzeme Güncelleme
                                </button>
                                <a href="urunler_senkron.php" class="btn btn-info btn-sm">
                                    <i class="bi bi-arrow-repeat me-1"></i> Logo Ürün Senkronizasyonu
                                </a>

                            </div>
                        </div>
                    </div>

                    <!-- ============================================================== -->
                    <!-- 3. Ana “Ürünleri İnceleyiniz” Tablosu (DataTable)        -->
                    <!-- ============================================================== -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow-sm">
                                <div class="card-body">
                                    <h4 class="card-title mb-4">Ürünleri İnceleyiniz</h4>
                                    <div class="table-responsive">
                                        <table id="example" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%;">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Stok Kodu</th>
                                                    <th>Stok Adı</th>
                                                    <th>Yurtiçi Fiyatı</th>
                                                    <th>İhracat Fiyatı</th>
                                                    <th>Web/App Fiyatı</th>
                                                    <th>Maliyet</th>
                                                    <th>Döviz</th>
                                                    <th>Stok</th>
                                                    <th>Aktif</th>
                                                    <th>Fiyat İşlemi</th>
                                                    <th>Detay Güncelle</th>
                                                </tr>
                                            </thead>
                                            <tfoot class="table-light">
                                                <tr>
                                                    <th>Stok Kodu</th>
                                                    <th>Stok Adı</th>
                                                    <th>Yurtiçi Fiyatı</th>
                                                    <th>İhracat Fiyatı</th>
                                                    <th>Web/App Fiyatı</th>
                                                    <th>Maliyet</th>
                                                    <th>Döviz</th>
                                                    <th>Stok</th>
                                                    <th>Aktif</th>
                                                    <th>Fiyat İşlemi</th>
                                                    <th>Detay Güncelle</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include "menuler/footer.php"; ?>
        </div>
    </div>

    <!-- Yeni Ürün Modal (Malzeme Fişi) -->
    <div class="modal fade" id="yenikategoriModal" tabindex="-1" aria-labelledby="yenikategoriModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form id="newMaterialForm" method="post" action="urunlerlogo.php?ajax=1" novalidate>
                <input type="hidden" name="add_new_material_card" value="1">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="yenikategoriModalLabel">Yeni Ürün Tanımlama (Malzeme Fişi)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                    </div>
                    <div class="modal-body">
                        <div id="processStatus" style="margin-top:10px;"></div>

                        <div class="container-fluid">
                            <!-- GENEL ÜRÜN BİLGİLERİ -->
                            <fieldset class="border rounded-2 p-3 mb-3">
                                <legend class="float-none w-auto px-2">Genel Ürün Bilgileri</legend>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="card_type" class="form-label">
                                            Kart Tipi <span class="text-danger">*</span>
                                            <i class="bi bi-info-circle" data-bs-toggle="tooltip" data-bs-placement="top" title="Ürünün tipini belirleyen kodu giriniz."></i>
                                        </label>
                                        <input type="number" class="form-control form-control-sm" id="card_type" name="card_type" value="10" required>
                                        <div class="invalid-feedback">Lütfen kart tipini giriniz.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="kod" class="form-label">
                                            Stok Kodu <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control form-control-sm" id="kod" name="kod" placeholder="Stok kodu" required>
                                        <div class="invalid-feedback">Lütfen stok kodunu giriniz.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="stokadi" class="form-label">
                                            Stok Adı <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control form-control-sm" id="stokadi" name="stokadi" placeholder="Stok adı" required>
                                        <div class="invalid-feedback">Lütfen stok adını giriniz.</div>
                                    </div>
                                </div>
                            </fieldset>
                            <!-- KOD BİLGİLERİ -->
                            <fieldset class="border rounded-2 p-3 mb-3">
                                <legend class="float-none w-auto px-2">Kod Bilgileri</legend>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label for="auxil_code" class="form-label">Özel Kod</label>
                                        <input type="text" class="form-control form-control-sm" id="auxil_code" name="auxil_code" placeholder="Özel kod">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="auth_code" class="form-label">Yetki Kodu</label>
                                        <input type="text" class="form-control form-control-sm" id="auth_code" name="auth_code" placeholder="Yetki kodu">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="group_code" class="form-label">Grup Kodu</label>
                                        <input type="text" class="form-control form-control-sm" id="group_code" name="group_code" placeholder="Boş bırakılabilir">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="unitset_code" class="form-label">Birim Set Kodu</label>
                                        <input type="text" class="form-control form-control-sm" id="unitset_code" name="unitset_code" placeholder="Birim set kodu">
                                    </div>
                                </div>
                            </fieldset>
                            <!-- KDV ve VERGİ BİLGİLERİ -->
                            <fieldset class="border rounded-2 p-3 mb-3">
                                <legend class="float-none w-auto px-2">KDV ve Vergi Bilgileri</legend>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="vat" class="form-label">KDV</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm" id="vat" name="vat" value="20" placeholder="KDV Oranı">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="selvat" class="form-label">Satış KDV</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm" id="selvat" name="selvat" value="20" placeholder="Satış KDV Oranı">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="returnvat" class="form-label">İade KDV</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm" id="returnvat" name="returnvat" value="20" placeholder="İade KDV Oranı">
                                    </div>
                                </div>
                                <div class="row g-3 mt-1">
                                    <div class="col-md-4">
                                        <label for="selprvat" class="form-label">Satış Pr KDV</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm" id="selprvat" name="selprvat" value="20" placeholder="Satış Pr KDV Oranı">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="returnprvat" class="form-label">İade Pr KDV</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm" id="returnprvat" name="returnprvat" value="20" placeholder="İade Pr KDV Oranı">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="auxil_code5" class="form-label">Özel Kod 5</label>
                                        <input type="number" class="form-control form-control-sm" id="auxil_code5" name="auxil_code5" placeholder="Özel kod 5">
                                    </div>
                                </div>
                            </fieldset>
                            <!-- ERİŞİM ve EK VERGİ SEÇENEKLERİ -->
                            <fieldset class="border rounded-2 p-3 mb-3">
                                <legend class="float-none w-auto px-2">Ek Seçenekler</legend>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="border rounded p-2">
                                            <h6>Erişim Seçenekleri</h6>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" id="ext_acc_eis" name="ext_acc_eis" value="1">
                                                <label class="form-check-label" for="ext_acc_eis">e-İş Ortamında Erişilebilir</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" id="ext_acc_satis" name="ext_acc_satis" value="2">
                                                <label class="form-check-label" for="ext_acc_satis">Satış Noktalarında Erişilebilir</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="border rounded p-2">
                                            <h6>Çoklu Ek Vergi</h6>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="multi_add_tax" name="multi_add_tax" value="1">
                                                <label class="form-check-label" for="multi_add_tax">Çoklu ek vergi kullanılsın</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                        </div> <!-- container-fluid -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Vazgeç</button>
                        <button type="submit" name="add_new_material_card" class="btn btn-primary btn-sm">Kaydet</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Detay Güncelle Modal (Malzeme & Ürün Çeviri Bilgileri) -->
    <div class="modal fade" id="detayModal" tabindex="-1" aria-labelledby="detayModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <form id="detayForm" method="post" novalidate>
                <!-- Güncelleme isteğinde action parametresi ekleniyor -->
                <input type="hidden" name="action" value="updateDetails">
                <input type="hidden" name="stok_kodu" id="detay_stok_kodu" value="">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="detayModalLabel">Malzeme ve Ürün Detay Güncelleme</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                    </div>
                    <div class="modal-footer sticky-top bg-white" style="z-index:1055;">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Vazgeç</button>
                        <button type="submit" class="btn btn-primary btn-sm">Güncelle</button>
                    </div>
                    <div class="modal-body">
                        <!-- Malzeme Çeviri Bilgileri -->
                        <fieldset class="modal-fieldset">
                            <legend>Malzeme Çeviri Bilgileri</legend>
                            <div id="materialTranslationsContainer">
                                <!-- AJAX ile doldurulacak -->
                            </div>
                        </fieldset>
                        <!-- İlişkili Ürün Çeviri Bilgileri -->
                        <fieldset class="modal-fieldset">
                            <legend>İlişkili Ürün Çeviri Bilgileri</legend>
                            <div id="associatedProductsContainer">
                                <!-- AJAX ile doldurulacak -->
                            </div>
                        </fieldset>
                        <fieldset class="modal-fieldset">
                            <legend>Logo Fatura Geçmişi</legend>
                            <div id="logoInvoiceHistory" class="logo-invoice-history text-muted small">
                                Stok kodu seçildiğinde son üç fatura burada listelenir.
                            </div>
                        </fieldset>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Vazgeç</button>
                        <button type="submit" class="btn btn-primary btn-sm">Güncelle</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Fiyat Güncelleme Onay Modalı -->
    <div class="modal fade custom-modal" id="priceUpdateModal" tabindex="-1" role="dialog" aria-labelledby="priceUpdateModalLabel" aria-describedby="priceUpdateModalDesc" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="priceUpdateForm" method="post" novalidate>
                <input type="hidden" name="action" value="updatePriceWithMail">
                <input type="hidden" name="stok_kodu" id="modal_stok_kodu" value="">
                <input type="hidden" name="logicalref" id="modal_logicalref" value="">
                <input type="hidden" name="gempa_logicalref" id="modal_gempa_logicalref" value="">
                <input type="hidden" name="gemas_logicalref" id="modal_gemas_logicalref" value="">
                <input type="hidden" name="yeni_domestic_price" id="modal_yeni_domestic_price" value="">
                <input type="hidden" name="yeni_export_price" id="modal_yeni_export_price" value="">
                <input type="hidden" name="urun_adi" id="modal_urun_adi" value="">
                <input type="hidden" name="old_domestic_price" id="modal_old_domestic_price" value="">
                <input type="hidden" name="old_export_price" id="modal_old_export_price" value="">

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="priceUpdateModalLabel"><i class="fa fa-edit me-2"></i> Fiyat Güncelleme</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                        <p id="priceUpdateModalDesc" class="visually-hidden">Fiyat güncelleme işlemi sonucu.</p>
                    </div>
                    <div class="modal-body">
                        <!-- Ürün Bilgileri -->
                        <div class="mb-4">
                            <div class="row">
                                <div class="col-6">
                                    <p><strong>Stok Kodu:</strong> <span id="modal_display_stok_kodu"></span></p>
                                </div>
                                <div class="col-6">
                                    <p><strong>Ürün Adı:</strong> <span id="modal_display_urun_adi"></span></p>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-6">
                                    <p class="mb-1"><strong>Eski Yurtiçi Fiyatı:</strong></p>
                                    <p class="info-box" id="modal_display_old_domestic"></p>
                                </div>
                                <div class="col-6">
                                    <p class="mb-1"><strong>Yeni Yurtiçi Fiyatı:</strong></p>
                                    <p class="info-box" id="modal_display_new_domestic"></p>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-6">
                                    <p class="mb-1"><strong>Eski İhracat Fiyatı:</strong></p>
                                    <p class="info-box" id="modal_display_old_export"></p>
                                </div>
                                <div class="col-6">
                                    <p class="mb-1"><strong>Yeni İhracat Fiyatı:</strong></p>
                                    <p class="info-box" id="modal_display_new_export"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Vazgeç & Güncelle Butonları -->
                        <div class="d-flex justify-content-end mb-4">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Vazgeç</button>
                            <button type="submit" class="btn btn-success">Güncelle</button>
                        </div>

                        <!-- Mail Gönderimi Seçeneği -->
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="sendMailCheckbox" name="send_mail" value="1" checked>
                            <label class="form-check-label" for="sendMailCheckbox">
                                Güncelleme sonrası mail gönderilsin mi?
                            </label>
                        </div>

                        <!-- Mail Yönetim Paneli -->
                        <div id="mailListContainer" class="card mb-0" style="display: none;">
                            <div class="card-header">
                                <h6 class="mb-0">Mail Gönderilecek Adresler</h6>
                            </div>
                            <div class="card-body" id="mailListContent">
                                <!-- AJAX ile mail listesi yüklenecek -->
                            </div>
                            <div class="card-footer">
                                <button type="button" id="refreshMailList" class="btn btn-sm btn-outline-primary">Mail Listesini Yenile</button>
                                <hr>
                                <div class="mb-2"><strong>Yeni Mail Ekle</strong></div>
                                <div class="mb-2">
                                    <input type="email" class="form-control" id="newMailEmail" placeholder="E-posta">
                                </div>
                                <div class="mb-2">
                                    <input type="text" class="form-control" id="newMailAdsoyad" placeholder="Ad Soyad (opsiyonel)">
                                </div>
                                <button type="button" id="addNewMail" class="btn btn-sm btn-primary">Ekle</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Kullanım Durumu Güncelleme Modalı -->
    <div class="modal fade custom-modal" id="activeUpdateModal" tabindex="-1" aria-labelledby="activeUpdateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="activeUpdateForm" method="post" novalidate>
                <input type="hidden" name="action" value="updateActive">
                <input type="hidden" name="stok_kodu" id="active_stok_kodu" value="">
                <input type="hidden" name="gempa_logicalref" id="active_gempa_logicalref" value="">
                <input type="hidden" name="gemas_logicalref" id="active_gemas_logicalref" value="">
                <input type="hidden" name="active" id="active_new_status" value="">
                <input type="hidden" name="old_status" id="active_old_status" value="">
                <input type="hidden" name="urun_adi" id="active_urun_adi" value="">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="activeUpdateModalLabel"><i class="fa fa-edit me-2"></i> Kullanım Durumu Güncelleme</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Stok Kodu:</strong> <span id="active_display_stok_kodu"></span></p>
                        <p><strong>Ürün Adı:</strong> <span id="active_display_urun_adi"></span></p>
                        <p><strong>Yeni Durum:</strong> <span id="active_display_new_status"></span></p>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="activeSendMailCheckbox" name="send_mail" value="1" checked>
                            <label class="form-check-label" for="activeSendMailCheckbox">Durum değişikliği sonrası mail gönderilsin mi?</label>
                        </div>
                        <div id="activeMailListContainer" class="card mb-0" style="display:none;">
                            <div class="card-header"><h6 class="mb-0">Mail Gönderilecek Adresler</h6></div>
                            <div class="card-body" id="activeMailListContent"></div>
                            <div class="card-footer">
                                <button type="button" id="activeRefreshMailList" class="btn btn-sm btn-outline-primary">Mail Listesini Yenile</button>
                                <hr>
                                <div class="mb-2"><strong>Yeni Mail Ekle</strong></div>
                                <div class="mb-2">
                                    <input type="email" class="form-control" id="activeNewMailEmail" placeholder="E-posta">
                                </div>
                                <div class="mb-2">
                                    <input type="text" class="form-control" id="activeNewMailAdsoyad" placeholder="Ad Soyad (opsiyonel)">
                                </div>
                                <button type="button" id="activeAddNewMail" class="btn btn-sm btn-primary">Ekle</button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
                        <button type="submit" class="btn btn-success">Güncelle</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="bulkModal" tabindex="-1" aria-labelledby="bulkModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xxl modal-dialog-scrollable" style="max-width:95vw;">
            <form id="bulkUploadForm" method="post" enctype="multipart/form-data" action="urunlerlogo.php">
                <div class="modal-content">

                    <!-- -------------------------------------------------- -->
                    <!--  1) Başlık                                        -->
                    <!-- -------------------------------------------------- -->
                    <!--  Modal’ın En Üstündeki Başlık Kısmı  -->
                    <div class="modal-header">
                        <h5 class="modal-title" id="bulkModalLabel">
                            <i class="bi bi-upload me-1"></i> Toplu Malzeme Güncelleme
                        </h5>

                        <!-- Eğer önizleme (previewRows) doluysa, "Önizlemeyi Temizle" butonunu gösteriyoruz -->
                        <?php if (!empty($_SESSION['previewRows'])): ?>
                            <button
                                type="submit"
                                name="bulk_action"
                                value="clear_preview"
                                formnovalidate
                                class="btn btn-outline-danger ms-auto"
                                data-bs-dismiss="modal"
                                aria-label="Önizlemeyi Temizle ve Kapat"
                                title="Önizlemeyi Temizle ve Bu Modalı Kapat">
                                <i class="bi bi-trash me-1"></i> Önizlemeyi Temizle
                            </button>
                        <?php endif; ?>
                    </div>


                    <!-- -------------------------------------------------- -->
                    <!--  2) Gövde (Hızlı Özet + Görsel Örnek + Detay)     -->
                    <!-- -------------------------------------------------- -->
                    <div class="modal-body">

                        <!-- 2-A | Hızlı 3 Adım Özet --------------------------->
                        <div class="alert alert-primary mb-4">
                            <p class="mb-1"><strong>📥 3 Adımda Hızlı Başlangıç:</strong></p>
                            <ol class="ps-4 mb-0">
                                <li>Önce “<a href="assets/template/toplu_fiyat_guncelle.xlsx" download class="link-dark">örnek Excel şablonunu</a>” indirin.</li>
                                <li class="mt-1">4 sütunu sırasıyla doldurup (Kod / Açıklama / 2025 YURTİÇİ LİSTE / 2025 EXPORT DÜZELTME) kaydedin.</li>
                                <li class="mt-1">“Excel Dosyası” alanından dosyanızı seçip “Excel’i Yükle” butonuna tıklayın.</li>
                            </ol>
                        </div>
                        <!-- ↓ Buraya eklenen uyarı notu: --------------------------------->
                        <div class="alert alert-warning small mb-4">
                            <strong>Not:</strong> Eğer daha önce yüklediğiniz verileri artık görmek istemiyorsanız “Önizlemeyi Temizle” butonuna tıklamalısınız.
                            Yeniden incelemek isterseniz, sadece “Kapat” tuşuna basarak modalı kapatmanız yeterlidir.
                        </div>
                        <!-- 2-B | Doğru / Yanlış Görsel Örnekler --------------->
                        <div class="row g-2 mb-4">
                            <!-- Yeşil Kutuda Desteklenen Formatlar -->
                            <div class="col-md-6">
                                <div class="border border-success rounded-3 p-3 h-100">
                                    <h6 class="text-success mb-2">✅ Desteklenen Fiyat Formatları</h6>
                                    <ul class="ps-3 mb-0 small">
                                        <li><code>1.234,56</code> → <code>1234.56</code></li>
                                        <li><code>€ 7,20</code> → <code>7.20</code></li>
                                        <li><code>1,272</code> → <code>1272.0</code> <span class="text-muted">(3 hane sonrası virgül = binlik ayracı)</span></li>
                                        <li><code>2.45</code> → <code>2.45</code></li>
                                        <li><code>438</code> → <code>438.0</code></li>
                                        <li><code>1.129</code> → <code>1129.0</code></li>
                                    </ul>
                                </div>
                            </div>
                            <!-- Kırmızı Kutuda Desteklenmeyen Formatlar -->
                            <div class="col-md-6">
                                <div class="border border-danger rounded-3 p-3 h-100">
                                    <h6 class="text-danger mb-2">❌ Desteklenmeyen / Atlanan Formatlar</h6>
                                    <ul class="ps-3 mb-0 small">
                                        <li><code>1,234.56</code> (ABD‐usûlü: <span class="fw-semibold">virgül=binlik, nokta=ondalık</span>)</li>
                                        <li><code>₺ 85,00</code> (“₺” simgesi tanınmıyor)</li>
                                        <li><code>$ -</code> (“‐” eksi işareti tek başına, null döner)</li>
                                        <li><code>-</code> (sadece eksi “-” – null döner)</li>
                                        <li><code>PAP</code> (özel metin, null döner)</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- 2-C | “Daha Fazla Detay” Collapse Butonu (Güncellenmiş) -->
                        <p class="mb-3">
                            <a
                                id="toggleDetailsBtn"
                                class="btn btn-primary btn-lg w-100 d-flex align-items-center justify-content-center"
                                data-bs-toggle="collapse"
                                href="#bulkDetailsCollapse"
                                role="button"
                                aria-expanded="false"
                                aria-controls="bulkDetailsCollapse">
                                <i class="bi bi-chevron-down me-2"></i>
                                <span class="button-text">Daha Fazla Detay</span>
                            </a>
                        </p>

                        <!-- 2-D | Açılır Detaylı Kurallar Bölümü --------------->
                        <div class="collapse" id="bulkDetailsCollapse">
                            <div class="card card-body mb-4 small">
                                <h6 class="fw-semibold mb-2">📋 Yüklemeden Önce Dikkat Edilecek Kurallar</h6>
                                <ol class="ps-3 mb-0">
                                    <li>
                                        <strong>Sütun Şeması (Kesin Sıra):</strong><br>
                                        – Excel dosyanızda <em>sadece</em> 4 sütun olacak ve aşağıdaki sırada olmalı:
                                        <ul class="ps-4 mb-2">
                                            <li><code>1. sütun</code>: Kod (Stok kodu – <span class="fw-semibold text-danger">metin formatında</span> olmalı)</li>
                                            <li><code>2. sütun</code>: Açıklama (Ürün adı/metni)</li>
                                            <li><code>3. sütun</code>: <strong>2025 YURTİÇİ LİSTE</strong> (Yeni yurtiçi fiyat)</li>
                                            <li><code>4. sütun</code>: <strong>2025 EXPORT DÜZELTME</strong> (Yeni ihracat fiyat)</li>
                                        </ul>
                                        <small class="text-muted">
                                            • İlk satır yalnızca başlıklar için ayrılabilir (örn. “KOD – AÇIKLAMA – 2025 YURTİÇİ LİSTE – 2025 EXPORT DÜZELTME”),
                                            ancak sistem <em>sütun sırasına</em> bakar; başlık metni “KOD” olmak zorunda değildir.
                                        </small>
                                    </li>

                                    <li class="mt-3">
                                        <strong>Stok Kodları (1. Sütun):</strong><br>
                                        <ul class="ps-4 mb-2">
                                            <li>Excel, eğer “Sayı” formatına alırsa <span class="text-danger">başındaki “0”</span> karakterini siler.
                                                <br>Örnek: <code>0131313</code> → Excel’de “131313” görünür.
                                            </li>
                                            <li>Bu durumda, veritabanındaki “0131313” kodlu ürün bulunamaz ve <strong>yanlış satır</strong> güncellenir.</li>
                                            <li>Çözüm: <strong>Yüklemeden önce</strong> 1. sütunu tamamen seçip
                                                <code>Sağ Tık → Hücreleri Biçimlendir → Metin (Text)</code> yapın.
                                            </li>
                                            <li>Hâlâ baştaki “0” eksikse, elle “0” ekleyin.</li>
                                        </ul>
                                    </li>

                                    <li class="mt-3">
                                        <strong>Fiyat Formatı (3. ve 4. sütunlar):</strong><br>
                                        Aşağıdaki “EU‐Style” formatlar <em>doğru biçimde</em> işlenir:
                                        <ul class="ps-4 mb-2">
                                            <li>
                                                <strong>Binlik Nokta + Virgül Ondalık</strong>
                                                <br>Örnek: <code>1.234,56</code> veya <code>€ 1.234,56</code> → <code>1234.56</code>
                                            </li>
                                            <li>
                                                <strong>Binlik Virgül Tam Sayı</strong>
                                                <br>Örnek: <code>1,272</code> → <code>1272.0</code>
                                                <span class="text-muted">(virgülden sonra 3 hane varsa “binlik ayracı” kabul edilir)</span>
                                            </li>
                                            <li>
                                                <strong>Yalnızca Virgül Ondalık</strong>
                                                <br>Örnek: <code>7,20</code> veya <code>€ 7,20</code> → <code>7.20</code>
                                            </li>
                                            <li>
                                                <strong>Nokta Ondalık veya Tam Sayı</strong>
                                                <br>Örnek: <code>2.45</code> → <code>2.45</code>
                                                <br>Örnek: <code>438</code> veya <code>€ 438</code> → <code>438.0</code>
                                            </li>
                                            <li>
                                                <strong>“Tek Nokta + Üç Hane” (Binlik Nokta)</strong>
                                                <br>Örnek: <code>1.129</code> → <code>1129.0</code>
                                            </li>
                                            <li>
                                                <strong>“Tek Nokta + İki Hane” (Ondalık Nokta)</strong>
                                                <br>Örnek: <code>5.20</code> → <code>5.20</code>
                                            </li>
                                        </ul>
                                        <small class="text-muted">
                                            • “<code>1,234.56</code>” (ABD‐usûlü: virgül=binlik, nokta=ondalık) **desteklenmez** → <code>null</code> döner.
                                            • “<code>$</code>” veya “<code>€</code>” sembolleri silinir, ancak “TL” veya “₺” tanınmaz → Lütfen Excel’den çıkartın.
                                        </small>
                                    </li>

                                    <li class="mt-3">
                                        <strong>Hücrelerde Formül Olmamalı:</strong><br>
                                        <ul class="ps-4 mb-2">
                                            <li>Fiyat sütunundaki hücreler mutlaka **sabit** değer (sayı veya metin) olmalı.
                                                <br>Örnek: <code>1.234,56</code> yazılı hücre “formül” değil, sabit bir değerdir.
                                            </li>
                                            <li>Formül varsa: ilgili sütunu <code>Kopyala → Sağ Tık → Yalnızca Değer Olarak Yapıştır</code> yöntemiyle “değer” haline getirin.</li>
                                        </ul>
                                    </li>

                                    <li class="mt-3">
                                        <strong>Tek Bir Aktif Sayfa Olmalı:</strong><br>
                                        <ul class="ps-4 mb-0">
                                            <li>Excel dosyanızda **yalnızca tek bir aktif “Sheet”** üzerinden veri okunur.
                                            </li>
                                            <li>Başka sayfa veya gizli sayfa varsa, sistem onları görmez; sadece “4 sütun” içeren tek bir sayfa kalmalı.</li>
                                        </ul>
                                    </li>
                                </ol>
                            </div>
                        </div>

                        <!-- 2-E | Dosya Seçim Alanı ---------------------------->
                        <div class="mb-4">
                            <label for="excelFile" class="form-label fw-semibold">Excel Dosyasını Seçin</label>
                            <input
                                type="file"
                                name="excel_file"
                                id="excelFile"
                                accept=".xls,.xlsx"
                                class="form-control"
                                required>
                        </div>

                        <!-- 2-F | Önizleme Tablosu (varsa) -------------------->
                        <?php if (!empty($_SESSION['previewRows'])): ?>
                            <hr>
                            <h6 class="mb-3">
                                Önizleme <small class="text-muted">(istediğiniz hücreyi düzenleyebilirsiniz)</small>
                            </h6>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="d-flex gap-3 align-items-center">
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="checkbox" id="showDifferences">
                                        <label class="form-check-label" for="showDifferences">Sadece farklı olanları göster</label>
                                    </div>
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="checkbox" id="showZeroChanged">
                                        <label class="form-check-label" for="showZeroChanged">Eski fiyatı 0 olup yeni fiyat girilenleri göster</label>
                                    </div>
                                    <div class="small">Seçili: <span id="selectedCount">0</span></div>
                                </div>
                                <div class="d-flex gap-2">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                                            Excel
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item export-option" data-mode="all" href="#">Tümü</a></li>
                                            <li><a class="dropdown-item export-option" data-mode="filtered" href="#">Filtrelenen</a></li>
                                            <li><a class="dropdown-item export-option" data-mode="selected" href="#">Seçilen</a></li>
                                        </ul>
                                    </div>
                                    <button type="button" id="startUpdates" class="btn btn-success btn-sm">Değişiklikleri Uygula</button>
                                    <button type="button" id="revertLast" class="btn btn-danger btn-sm" disabled>Son Güncellemeyi Geri Al</button>
                                </div>
                            </div>
                            <div id="progress" class="small text-muted mb-2"></div>
                            <div class="table-responsive" style="max-height:1000px; overflow:auto;">
                                <table
                                    id="modalPreviewTable"
                                    class="table table-sm table-bordered table-hover mb-0"
                                    style="min-width:900px;">
                                    <thead class="table-light text-center sticky-top">
                                        <tr>
                                            <th class="text-center"><input type="checkbox" id="selectAll"></th>
                                            <th>Kod</th>
                                            <th>Açıklama</th>
                                            <th>Eski Yurtİçi</th>
                                            <th>Eski Export</th>
                                            <th>Yeni Yurtİçi</th>
                                            <th>Yeni Export</th>
                                            <th>Durum</th>
                                            <!-- Gizli: logicalref, gempa, gemas -->
                                            <th class="d-none">logicalref</th>
                                            <th class="d-none">gempa</th>
                                            <th class="d-none">gemas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($_SESSION['previewRows'] as $i => $r): ?>
                                            <tr data-index="<?= $i ?>">
                                                <td class="text-center"><input type="checkbox" class="row-select"></td>
                                                <td class="kod"><?= htmlspecialchars($r['kod']) ?></td>
                                                <td><?= htmlspecialchars($r['aciklama']) ?></td>
                                                <td class="yurticiOld"><?= $r['yurticiEski'] !== null ? number_format($r['yurticiEski'],2,'.','') : '' ?></td>
                                                <td class="exportOld"><?= $r['exportEski'] !== null ? number_format($r['exportEski'],2,'.','') : '' ?></td>
                                                <td>
                                                    <input
                                                        type="text"
                                                        class="form-control form-control-sm newY"
                                                        value="<?= $r['yeniYurtici'] !== null ? number_format($r['yeniYurtici'],2,'.','') : '' ?>">
                                                </td>
                                                <td>
                                                    <input
                                                        type="text"
                                                        class="form-control form-control-sm newE"
                                                        value="<?= $r['yeniExport'] !== null ? number_format($r['yeniExport'],2,'.','') : '' ?>">
                                                </td>
                                                <td class="status text-center">—</td>
                                                <td class="logicalref d-none"><?= $r['logicalref'] ?></td>
                                                <td class="gempa      d-none"><?= $r['gempa'] ?></td>
                                                <td class="gemas      d-none"><?= $r['gemas'] ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                    </div><!-- /.modal-body -->

                    <!-- -------------------------------------------------- -->
                    <!--  3) Footer (Butonlar)                              -->
                    <!-- -------------------------------------------------- -->
                    <div class="modal-footer">
                        <!-- “Yeni Excel Yükle” normal submit yapacak (doğrulama devam edecek) -->
                        <button
                            type="submit"
                            name="bulk_action"
                            value="upload_excel"
                            class="btn btn-primary">
                            <i class="bi bi-upload me-1"></i> Excel’i Yükle
                        </button>

                        <!-- “Önizlemeyi Temizle” butonuna formnovalidate ekliyoruz -->
                        <?php if (!empty($_SESSION['previewRows'])): ?>
                            <button
                                type="submit"
                                name="bulk_action"
                                value="clear_preview"
                                formnovalidate
                                class="btn btn-outline-danger">
                                <i class="bi bi-trash me-1"></i> Önizlemeyi Temizle
                            </button>
                        <?php endif; ?>

                        <!-- Sadece modal’ı kapatacak -->
                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">
                            Kapat
                        </button>
                    </div>
                </div><!-- /.modal-content -->
            </form>
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
    <form id="exportForm" method="post" target="_blank" class="d-none">
        <input type="hidden" name="bulk_action" value="download_excel">
        <input type="hidden" name="rows" id="exportRows">
    </form>
    <!-- 1. Uyarı Modali -->
    <div class="modal fade" id="confirmStep1" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-warning">
                <div class="modal-body text-center">
                    <i class="bi bi-exclamation-triangle-fill display-1 text-dark"></i>
                    <p class="fs-5 fw-bold text-dark mt-3 mb-4">Güncellemeleri uygulamak üzeresiniz. Emin misiniz?</p>
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
                        <button type="button" id="confirmStep1Btn" class="btn btn-dark">Devam</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Dikkat Modali -->
    <div class="modal fade" id="confirmStep2" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-danger text-white">
                <div class="modal-body text-center">
                    <i class="bi bi-exclamation-octagon-fill display-1"></i>
                    <p class="fs-5 fw-bold mt-3 mb-4">Bu işlem tüm platformlardaki fiyatları değiştirecek. Devam etmek istediğinizden emin misiniz?</p>
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Vazgeç</button>
                        <button type="button" id="confirmStep2Btn" class="btn btn-dark">Evet, Güncelle</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="toast-container position-fixed bottom-0 end-0 p-3" id="statusToastContainer"></div>

    <!-- Scriptler -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/metismenu/metisMenu.min.js"></script>
    <script src="assets/libs/simplebar/simplebar.min.js"></script>
    <script src="assets/libs/node-waves/waves.min.js"></script>
    <script src="assets/libs/waypoints/lib/jquery.waypoints.min.js"></script>
    <script src="assets/libs/jquery.counterup/jquery.counterup.min.js"></script>
    <script src="assets/js/app.js"></script>
    <script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>
    <script src="assets/libs/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
    <script src="assets/libs/parsleyjs/parsley.min.js"></script>
    <script>
        $(document).ready(function() {
            var pagePath = window.location.pathname;
            var ajaxUrl = pagePath.replace(/urunlerlogo\.php$/, 'urunlerlogo_datatable.php');
            if (ajaxUrl === pagePath) {
                ajaxUrl = 'urunlerlogo_datatable.php';
            }
            var table = $('#example').DataTable({
                "serverSide": true,
                "processing": true,
                "ajax": ajaxUrl,
                "pageLength": 10,
                "language": {
                    "url": "assets/libs/datatables.net/i18n/tr.json"
                },
                "columnDefs": [
                    { "targets": 1, "width": "30%", "className": "stock-col" }
                ]
            });

            // Eğer URL'de "stok_kodu" parametresi varsa DataTable aramasını önceden uygula
            var searchParam = new URLSearchParams(window.location.search).get('stok_kodu');
            if (searchParam) {
                table.search(searchParam).draw();
                // Arama kutusunun içini de doldur
                $('#example_filter input').val(searchParam);
            }
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // PHP tarafında önizleme satırları varsa modal'ı aç
            <?php if (!empty($_SESSION['previewRows'])): ?>
                var bulkModalEl = document.getElementById('bulkModal');
                // Bootstrap 5 Modal nesnesi
                var bulkModal = new bootstrap.Modal(bulkModalEl);
                bulkModal.show();
            <?php endif; ?>
            var collapseEl = document.getElementById('bulkDetailsCollapse');
            var toggleBtn = document.getElementById('toggleDetailsBtn');
            var iconEl = toggleBtn.querySelector('i');
            var textEl = toggleBtn.querySelector('.button-text');

            // Collapse açılırken
            collapseEl.addEventListener('show.bs.collapse', function() {
                iconEl.classList.remove('bi-chevron-down');
                iconEl.classList.add('bi-chevron-up');
                textEl.textContent = 'Daha Az Detay';
            });

            // Collapse kapanırken
            collapseEl.addEventListener('hide.bs.collapse', function() {
                iconEl.classList.remove('bi-chevron-up');
                iconEl.classList.add('bi-chevron-down');
                textEl.textContent = 'Daha Fazla Detay';
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            var pendingRows = [];
            var lastUpdatedRows = [];
            // Fiyat farkı için minimum eşik kaldırıldı; tüm farklılıklar dikkate alınır

            function round2(num) {
                return Math.round(num * 100) / 100;
            }

            function evaluateRowChange($tr) {
                var oldY = round2(parseFloat($tr.find('.yurticiOld').text()));
                var oldE = round2(parseFloat($tr.find('.exportOld').text()));
                var y = $tr.find('.newY').val().trim();
                var e = $tr.find('.newE').val().trim();
                var validY = (y !== '' && !isNaN(y) && parseFloat(y) > 0);
                var validE = (e !== '' && !isNaN(e) && parseFloat(e) > 0);
                var newY = validY ? round2(parseFloat(y)) : oldY;
                var newE = validE ? round2(parseFloat(e)) : oldE;
                if (validY) { $tr.find('.newY').val(newY.toFixed(2)); }
                if (validE) { $tr.find('.newE').val(newE.toFixed(2)); }
                var changedY = validY && ((oldY === 0 && newY > 0) || newY !== oldY);
                var changedE = validE && ((oldE === 0 && newE > 0) || newE !== oldE);
                var changed = changedY || changedE;
                var zeroChange = (oldY === 0 && validY && newY > 0) || (oldE === 0 && validE && newE > 0);
                $tr.attr('data-changed', changed ? '1' : '0');
                $tr.attr('data-zerochange', zeroChange ? '1' : '0');
            }

            function updateSelectedCount(){
                $('#selectedCount').text($('.row-select:checked').length);
            }

            function filterRows() {
                var onlyDiff = $('#showDifferences').is(':checked');
                var onlyZero = $('#showZeroChanged').is(':checked');
                $('#modalPreviewTable tbody tr').each(function() {
                    var changed = $(this).attr('data-changed') === '1';
                    var zeroChange = $(this).attr('data-zerochange') === '1';
                    var show = true;
                    if (onlyDiff && !changed) show = false;
                    if (onlyZero && !zeroChange) show = false;
                    $(this).toggle(show);
                    if (!show) { $(this).find('.row-select').prop('checked', false); }
                });
                updateSelectedCount();
                $('#selectAll').prop('checked', $('.row-select:visible').length > 0 && $('.row-select:visible').length === $('.row-select:visible:checked').length);
            }

            $('#modalPreviewTable tbody tr').each(function(){ evaluateRowChange($(this)); });
            filterRows();

            $('#modalPreviewTable').on('input', '.newY, .newE', function(){
                var $tr = $(this).closest('tr');
                evaluateRowChange($tr);
                filterRows();
            });

            $('#showDifferences, #showZeroChanged').on('change', function(){ filterRows(); });

            $('#selectAll').on('change', function(){
                $('#modalPreviewTable tbody tr:visible .row-select').prop('checked', $(this).is(':checked'));
                updateSelectedCount();
            });

            $('#modalPreviewTable').on('change', '.row-select', function(){
                if(!$(this).is(':checked')) { $('#selectAll').prop('checked', false); }
                else if($('.row-select:visible').length === $('.row-select:visible:checked').length){ $('#selectAll').prop('checked', true); }
                updateSelectedCount();
            });

            $('.export-option').on('click', function(e){
                e.preventDefault();
                var mode = $(this).data('mode');
                var rows = [];
                $('#modalPreviewTable tbody tr').each(function(){
                    var $tr = $(this);
                    if($tr.attr('data-changed') !== '1') return;
                    if(mode === 'filtered' && !$tr.is(':visible')) return;
                    if(mode === 'selected' && !$tr.find('.row-select').is(':checked')) return;
                    var y = $tr.find('.newY').val().trim();
                    var e = $tr.find('.newE').val().trim();
                    var oldY = round2(parseFloat($tr.find('.yurticiOld').text()));
                    var oldE = round2(parseFloat($tr.find('.exportOld').text()));
                    var validY = (y !== '' && !isNaN(y) && parseFloat(y) > 0);
                    var validE = (e !== '' && !isNaN(e) && parseFloat(e) > 0);
                    var newY = validY ? round2(parseFloat(y)) : oldY;
                    var newE = validE ? round2(parseFloat(e)) : oldE;
                    rows.push({
                        kod: $tr.find('.kod').text().trim(),
                        oldY: oldY,
                        newY: newY,
                        oldE: oldE,
                        newE: newE
                    });
                });
                if(rows.length === 0){
                    alert('Excel için veri bulunamadı.');
                    return;
                }
                $('#exportRows').val(JSON.stringify(rows));
                $('#exportForm').submit();
            });

            $('#startUpdates').on('click', function() {
                var rows = [];
                $('#modalPreviewTable tbody tr').each(function() {
                    var $tr = $(this);
                    if(!$tr.find('.row-select').is(':checked')) return;
                    if($tr.attr('data-changed') !== '1') return; // değişmemiş satırları atla
                    var y = $tr.find('.newY').val().trim();
                    var e = $tr.find('.newE').val().trim();
                    var oldY = parseFloat($tr.find('.yurticiOld').text());
                    var oldE = parseFloat($tr.find('.exportOld').text());
                    var validY = (y !== '' && !isNaN(y) && parseFloat(y) > 0);
                    var validE = (e !== '' && !isNaN(e) && parseFloat(e) > 0);
                    if (!validY && !validE) return;
                    rows.push({
                        kod: $tr.find('.kod').text().trim(),
                        aciklama: $tr.find('td').eq(2).text().trim(), // 3. sütun (index 2) = Açıklama
                        yurtici: validY ? round2(parseFloat(y)) : round2(oldY),
                        export: validE ? round2(parseFloat(e)) : round2(oldE),
                        oldYurtici: round2(oldY), // Eski yurtiçi fiyat
                        oldExport: round2(oldE),  // Eski export fiyat
                        logicalref: $tr.find('.logicalref').text().trim(),
                        gempa: $tr.find('.gempa').text().trim(),
                        gemas: $tr.find('.gemas').text().trim(),
                        $tr: $tr
                    });
                });

                if (rows.length === 0) {
                    return alert('Güncellenecek satır bulunamadı.');
                }

                pendingRows = rows;
                $('#confirmStep1').modal('show');
            });

            $('#confirmStep1Btn').on('click', function() {
                $('#confirmStep1').modal('hide');
                $('#confirmStep2').modal('show');
            });

            $('#confirmStep2Btn').on('click', function() {
                $('#confirmStep2').modal('hide');
                $('#startUpdates').prop('disabled', true);
                $('#revertLast').prop('disabled', true);
                lastUpdatedRows = pendingRows;
                runUpdateSequence(pendingRows, 0);
            });

            $('#revertLast').on('click', function() {
                if (!lastUpdatedRows.length) return;
                $(this).prop('disabled', true);
                runRevertSequence(lastUpdatedRows, 0);
            });

            function runUpdateSequence(rows, idx) {
                if (idx >= rows.length) {
                    $('#progress').text('✅ Tüm satırların güncellemesi tamamlandı.');
                    $('#revertLast').prop('disabled', false);
                    return;
                }
                var row = rows[idx],
                    $tr = row.$tr;
                $tr.find('.status').text('⏳');
                $.post('urunlerlogo.php', {
                    action: 'updatePriceWithMail',
                    stok_kodu: row.kod,
                    urun_adi: row.aciklama || row.kod, // Ürün adı
                    yeni_domestic_price: row.yurtici,
                    yeni_export_price: row.export,
                    old_domestic_price: row.oldYurtici, // Eski yurtiçi
                    old_export_price: row.oldExport,    // Eski export
                    logicalref: row.logicalref,
                    gempa_logicalref: row.gempa,
                    gemas_logicalref: row.gemas,
                    send_mail: '0' // Toplu güncellemede mail gönderme (varsayılan: hayır)
                }, function(resp) {
                    if (resp.status === 'success') {
                        $tr.find('.status').html('<span class="text-success">✔</span>');
                    } else if (resp.status === 'partial' || resp.status === 'warning') {
                        $tr.find('.status').html('<span class="text-warning">⚠</span>');
                    } else {
                        $tr.find('.status').html('<span class="text-danger">✖</span>');
                    }
                    $('#progress').text((idx + 1) + ' / ' + rows.length);
                    setTimeout(function() { runUpdateSequence(rows, idx + 1); }, 200);
                }, 'json').fail(function() {
                    $tr.find('.status').html('<span class="text-danger">ERR</span>');
                    $('#progress').text((idx + 1) + ' / ' + rows.length);
                    setTimeout(function() { runUpdateSequence(rows, idx + 1); }, 200);
                });
            }

            function runRevertSequence(rows, idx) {
                if (idx >= rows.length) {
                    $('#progress').text('↩️ Geri alma tamamlandı.');
                    return;
                }
                var row = rows[idx];
                $.post('urun_fiyat_log_revert_last.php', {
                    stokkodu: row.kod
                }, function(resp) {
                    $('#progress').text('Geri alınıyor: ' + (idx + 1) + ' / ' + rows.length);
                    setTimeout(function() { runRevertSequence(rows, idx + 1); }, 200);
                }, 'json').fail(function() {
                    $('#progress').text('Hata: ' + row.kod);
                    setTimeout(function() { runRevertSequence(rows, idx + 1); }, 200);
                });
            }

            $('#clearPreview').on('click', function() {
                $('#bulkUploadForm').submit();
            });
        });
    </script>
    <script>
        <?php if ($user_type === 'Yönetici') { ?>
            var priceUpdateOriginalBody = $('#priceUpdateModal .modal-body').html();
            var activePriceRow = null;

            $(document).on('click', '.update-price-btn', function() {
                var button = $(this);
                var urun_id = button.data('id');
                var stok_kodu = button.data('stokkodu');
                var logicalref = button.data('logicalref');
                var gemas_logicalref = button.data('GEMAS2026logical');
                var gempa_logicalref = button.data('GEMPA2026logical');

                activePriceRow = button.closest('tr');
                var row = activePriceRow;
                var urunAdi = row.find('td:eq(1)').text().trim();
                // Eski fiyatı input'un value attribute'undan (son kayıtlı değer)
                // okuyalım ki kullanıcı satırı değiştirmiş olsa bile doğru eski
                // fiyatı gösterelim
                var oldDomestic = row.find('input.domestic-price-input').attr('value');
                var oldExport = row.find('input.export-price-input').attr('value');
                var newDomestic = row.find('input.domestic-price-input').val();
                var newExport = row.find('input.export-price-input').val();

                // Modal alanlarını dolduralım
                $('#modal_stok_kodu').val(stok_kodu);
                $('#modal_logicalref').val(logicalref);
                $('#modal_gemas_logicalref').val(gemas_logicalref);
                $('#modal_gempa_logicalref').val(gempa_logicalref);
                $('#modal_yeni_domestic_price').val(newDomestic);
                $('#modal_yeni_export_price').val(newExport);
                $('#modal_urun_adi').val(urunAdi);
                $('#modal_old_domestic_price').val(oldDomestic);
                $('#modal_old_export_price').val(oldExport);
                $('#modal_display_stok_kodu').text(stok_kodu);
                $('#modal_display_urun_adi').text(urunAdi);
                $('#modal_display_old_domestic').text(oldDomestic);
                $('#modal_display_new_domestic').text(newDomestic);
                $('#modal_display_old_export').text(oldExport);
                $('#modal_display_new_export').text(newExport);

                // Mail gönderim seçeneğini resetleyelim
                $('#sendMailCheckbox').prop('checked', true);
                $('#mailListContainer').show();
                loadMailListTo('#mailListContent');

                $('#priceUpdateModal').modal('show');
            });

            // Eğer "mail gönderilsin" seçeneğine tıklanırsa mail listesi alanı açılsın ve AJAX ile liste yüklensin
            // Dinamik olarak eklendiği için checkbox'ı belge üzerinden dinle
            $(document).on('change', '#sendMailCheckbox', function() {
                if ($(this).is(':checked')) {
                    $('#mailListContainer').show();
                    loadMailListTo('#mailListContent');
                } else {
                    $('#mailListContainer').hide();
                }
            });

            if ($('#sendMailCheckbox').is(':checked')) {
                $('#mailListContainer').show();
                loadMailListTo('#mailListContent');
            }

            function loadMailListTo(containerSelector) {
                $.ajax({
                    url: 'urunlerlogo.php',
                    type: 'POST',
                    data: {
                        action: 'getMailList'
                    },
                    dataType: 'json',
                    success: function(mails) {
                        var html = '';
                        if (mails.length > 0) {
                            $.each(mails, function(index, mail) {
                                html += '<div class="form-check">';
                                html += '<input class="form-check-input mail-checkbox" type="checkbox" value="' + mail.mail_id + '" id="mail_' + mail.mail_id + '">';
                                html += '<label class="form-check-label" for="mail_' + mail.mail_id + '">' + mail.email + ' (' + (mail.adsoyad ? mail.adsoyad : '') + ')</label>';
                                html += ' <button type="button" class="btn btn-sm btn-link editMailBtn" data-mail-id="' + mail.mail_id + '" data-email="' + mail.email + '" data-adsoyad="' + mail.adsoyad + '">Düzenle</button>';
                                html += ' <button type="button" class="btn btn-sm btn-link text-danger deleteMailBtn" data-mail-id="' + mail.mail_id + '">Sil</button>';
                                html += '</div>';
                            });
                        } else {
                            html = '<p>Mail adresi bulunamadı.</p>';
                        }
                        $(containerSelector).html(html);

                        $(containerSelector + ' .mail-checkbox').prop('checked', true);
                    },
                    error: function(xhr, status, error) {
                        console.error("Mail listesi yüklenirken hata oluştu:", error);
                    }
                });
            }

            // Modal gövdesi yenilendiğinde de çalışması için delegasyon kullan
            $(document).on('click', '#refreshMailList', function() {
                loadMailListTo('#mailListContent');
            });

            // Yeni mail ekleme işlemi
            // Yeni mail butonu da delegasyon ile çalışmalı
            $(document).on('click', '#addNewMail', function() {
                var email = $('#newMailEmail').val().trim();
                var adsoyad = $('#newMailAdsoyad').val().trim();
                if (email === '') {
                    alert("E-posta adresi boş olamaz.");
                    return;
                }
                $.ajax({
                    url: 'urunlerlogo.php',
                    type: 'POST',
                    data: {
                        action: 'addMail',
                        email: email,
                        adsoyad: adsoyad
                    },
                    dataType: 'json',
                    success: function(response) {
                        alert(response.message);
                        loadMailListTo('#mailListContent');
                        $('#newMailEmail').val('');
                        $('#newMailAdsoyad').val('');
                    },
                    error: function() {
                        alert("Yeni mail eklenirken hata oluştu.");
                    }
                });
            });

            // Kullanım durumu modali için yeni mail ekleme - delegasyon
            $(document).on('click', '#activeAddNewMail', function() {
                var email = $('#activeNewMailEmail').val().trim();
                var adsoyad = $('#activeNewMailAdsoyad').val().trim();
                if (email === '') {
                    alert('E-posta adresi boş olamaz.');
                    return;
                }
                $.ajax({
                    url: 'urunlerlogo.php',
                    type: 'POST',
                    data: {
                        action: 'addMail',
                        email: email,
                        adsoyad: adsoyad
                    },
                    dataType: 'json',
                    success: function(response) {
                        alert(response.message);
                        loadMailListTo('#activeMailListContent');
                        $('#activeNewMailEmail').val('');
                        $('#activeNewMailAdsoyad').val('');
                    },
                    error: function() {
                        alert('Yeni mail eklenirken hata oluştu.');
                    }
                });
            });

            function showActiveUpdateToast(response) {
                var labels = {
                    mysql: 'Yerel',
                    logo_gempa: 'Logo Gempa',
                    logo_gemas: 'Logo Gemas'
                };
                var successes = [];
                var fails = [];
                if (response.results) {
                    $.each(response.results, function(key, val) {
                        if (val.success) {
                            successes.push(labels[key] || key);
                        } else {
                            fails.push(labels[key] || key);
                        }
                    });
                }
                var msg = response.message || '';
                if (!msg) {
                    if (successes.length) {
                        msg += 'Başarılı: ' + successes.join(', ');
                    }
                    if (fails.length) {
                        msg += (msg ? ' | ' : '') + 'Başarısız: ' + fails.join(', ');
                    }
                }
                var style = fails.length ? 'warning' : 'success';
                var toastHtml = `
                    <div class="toast align-items-center text-bg-${style} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                      <div class="d-flex">
                        <div class="toast-body">${msg}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                      </div>
                    </div>`;
                var $toast = $(toastHtml);
                $('#statusToastContainer').append($toast);
                new bootstrap.Toast($toast[0], {delay: 5000}).show();
            }

            // Mail düzenleme
            $(document).on('click', '.editMailBtn', function() {
                var mail_id = $(this).data('mail-id');
                var email = $(this).data('email');
                var adsoyad = $(this).data('adsoyad');
                var newEmail = prompt("Yeni e-posta adresini giriniz:", email);
                if (newEmail != null && newEmail != "") {
                    var newAdsoyad = prompt("Yeni ad soyadını giriniz (opsiyonel):", adsoyad);
                    $.ajax({
                        url: 'urunlerlogo.php',
                        type: 'POST',
                        data: {
                            action: 'updateMail',
                            mail_id: mail_id,
                            email: newEmail,
                            adsoyad: newAdsoyad
                        },
                        dataType: 'json',
                        success: function(response) {
                            alert(response.message);
                            loadMailListTo('#mailListContent');
                        },
                        error: function() {
                            alert("Mail güncellenirken hata oluştu.");
                        }
                    });
                }
            });

            // Mail silme
            $(document).on('click', '.deleteMailBtn', function() {
                var mail_id = $(this).data('mail-id');
                if (confirm("Mail adresini silmek istediğinize emin misiniz?")) {
                    $.ajax({
                        url: 'urunlerlogo.php',
                        type: 'POST',
                        data: {
                            action: 'deleteMail',
                            mail_id: mail_id
                        },
                        dataType: 'json',
                        success: function(response) {
                            alert(response.message);
                            loadMailListTo('#mailListContent');
                        },
                        error: function() {
                            alert("Mail silinirken hata oluştu.");
                        }
                    });
                }
            });

            // Fiyat güncelleme formunun gönderilmesi (modal üzerindeki "Güncelle" butonu)
            $('#priceUpdateForm').on('submit', function(e) {
                e.preventDefault();
                // Seçili mail id'lerini toplayalım
                var selectedMailIds = [];
                if ($('#sendMailCheckbox').is(':checked')) {
                    $('.mail-checkbox:checked').each(function() {
                        selectedMailIds.push($(this).val());
                    });
                }
                var formData = $(this).serializeArray();
                formData.push({
                    name: 'selected_mail_ids',
                    value: selectedMailIds.join(',')
                });

                $.ajax({
                    url: 'urunlerlogo.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    beforeSend: function() {
                        $('#priceUpdateModal .modal-body').append('<div id="loadingIndicator">İşlem devam ediyor, lütfen bekleyiniz...</div>');
                    },
                    success: function(response) {
                        const $modal = $('#priceUpdateModal');
                        const $body = $modal.find('.modal-body');
                        const $footer = $modal.find('.modal-footer');

                        // 1) spinner’ı temizle, footer’ı gizle
                        $modal.find('#loadingIndicator').remove();
                        $footer.hide();

                        // 2) gövdedeki form alanlarını temizle
                        $body.empty();

                        // 3) ignored-error kontrolü (LogoGemas export)
                        const ignoredExportError =
                            response.platforms?.logo_gemas?.export?.ignored_error;

                        // 4) ikon ve başlık belirle
                        let iconClass, titleText;
                        if (response.status === 'success' || ignoredExportError) {
                            // normal success veya ignored-export-hatası
                            iconClass = 'fa-check-circle text-success';
                            titleText = ignoredExportError ? 'Güncelleme Kısmen Başarılı' : 'Güncelleme Başarılı';
                        } else if (response.status === 'partial' || response.status === 'warning') {
                            iconClass = 'fa-exclamation-triangle text-warning';
                            titleText = 'Güncelleme Kısmen Başarılı';
                        } else {
                            iconClass = 'fa-times-circle text-danger';
                            titleText = 'Güncelleme Başarısız';
                        }
                        // 5) Başlık ve mesaj
                        $body.append(`
                            <div class="text-center p-3">
                                <i class="fa ${iconClass} fa-3x"></i>
                                <h5 class="mt-3">${titleText}</h5>
                                <p>${response.message}</p>
                            </div>
                        `);

                        // 5.1) Progress bar
                        if (response.platforms) {
                            const counts = { success: 0, skip: 0, fail: 0 };
                            $.each(response.platforms, (pk, res) => {
                                ['domestic', 'export'].forEach(type => {
                                    const entry = res[type];
                                    const ignored = pk === 'logo_gemas' && type === 'export' && entry.ignored_error;
                                    if (entry.success === true || ignored) {
                                        if (entry.error !== 'No change') counts.success++;
                                    } else if (entry.success === null) {
                                        counts.skip++;
                                    } else if (entry.success === false) {
                                        counts.fail++;
                                    }
                                });
                            });
                            const total = counts.success + counts.skip + counts.fail;
                            const successPct = total ? (counts.success / total) * 100 : 0;
                            const skipPct = total ? (counts.skip / total) * 100 : 0;
                            const failPct = total ? (counts.fail / total) * 100 : 0;
                            $body.append(`
                                <div class="progress mb-2 progress-summary" style="height:18px;">
                                    ${counts.success ? `<div class="progress-bar progress-bar-success" style="width:${successPct}%"></div>` : ''}
                                    ${counts.skip ? `<div class="progress-bar bg-warning text-dark" style="width:${skipPct}%"></div>` : ''}
                                    ${counts.fail ? `<div class="progress-bar bg-danger" style="width:${failPct}%"></div>` : ''}
                                </div>
                                <div class="text-center mb-3 small">${counts.success} Başarılı${counts.skip ? ' • ' + counts.skip + ' Atlandı' : ''}${counts.fail ? ' • ' + counts.fail + ' Hata' : ''}</div>
                            `);
                        }

                        // 6) Platform detaylarını göster (tablo)
                        if (response.platforms) {
                            const labels = {
                                mysql: '<i class="fa fa-database text-primary me-1"></i>Satış Web Veritabanı',
                                logo_gempa: '<i class="fa fa-industry text-danger me-1"></i>Logo GEMPAS Veritabanı',
                                logo_gemas: '<i class="fa fa-industry text-danger me-1"></i>Logo GEMAS Veritabanı',
                                web: '<i class="fa fa-globe text-info me-1"></i>Gemas Web/App Veritabanı'
                            };
                            const rows = [];
                            $.each(response.platforms, (platformKey, results) => {
                                let rowHtml = `<tr><th>${labels[platformKey]}</th>`;
                                ['domestic', 'export'].forEach(type => {
                                    const entry = results[type];
                                    const isIgnored = platformKey === 'logo_gemas' && type === 'export' && entry.ignored_error;
                                    const isSkipped = entry.success === null;
                                    const isSuccess = entry.success === true || isIgnored;
                                    let badgeClass, text, iconHtml;
                                    if (isSkipped) {
                                        badgeClass = 'status-badge warning';
                                        iconHtml = '<i class="fa fa-forward me-1 text-warning"></i>';
                                        text = 'Atlandı';
                                    } else if (isSuccess) {
                                        const noChange = entry.error === 'No change';
                                        badgeClass = noChange ? 'status-badge same' : 'status-badge success';
                                        iconHtml = noChange
                                            ? '<i class="fa fa-minus-circle me-1 text-secondary"></i>'
                                            : '<i class="fa fa-check-circle me-1 text-success"></i>';
                                        text = noChange ? 'Aynı Fiyat' : 'Başarılı';
                                    } else {
                                        badgeClass = 'status-badge error';
                                        iconHtml = '<i class="fa fa-times-circle me-1 text-danger"></i>';
                                        text = 'Hata';
                                    }
                                    let detailMsg = '';
                                    if (isSkipped) {
                                        detailMsg = entry.error;
                                    } else if (!isSuccess && entry.error !== 'No change') {
                                        detailMsg = entry.error;
                                    } else if (isIgnored) {
                                        detailMsg = `Bilgi: ${entry.ignored_error}`;
                                    }
                                    const tooltip = detailMsg ? ` data-bs-toggle="tooltip" title="${detailMsg}"` : '';
                                    rowHtml += `<td><span class="${badgeClass}"${tooltip}>${iconHtml}${text}</span></td>`;
                                });
                                rowHtml += '</tr>';
                                rows.push(rowHtml);
                            });

                            $body.append(`
                                <table class="table table-striped price-summary-table">
                                    <thead>
                                        <tr><th>Platform</th><th>Yurtiçi</th><th>İhracat</th></tr>
                                    </thead>
                                    <tbody>
                                        ${rows.join('')}
                                    </tbody>
                                </table>
                            `);
                            const ttList = [].slice.call(document.querySelectorAll('#priceUpdateModal [data-bs-toggle="tooltip"]'));
                            ttList.forEach(el => new bootstrap.Tooltip(el));
                        }

                        // 7) Mail durumu
                        if (response.mailTotal > 0) {
                            $body.append(`
                                <h6 class="mt-3">Mail Gönderim Durumu:</h6>
                                <p>Gönderilen: <strong>${response.mailSent}</strong> / ${response.mailTotal}</p>
                                ${response.mailFailed.length ? `<p>Başarısız Mail Adresleri: ${response.mailFailed.join(', ')}</p>` : ''}
                            `);
                        }

                        // 8) Kapat düğmesi
                        $body.append(`
                            <div class="text-center mt-3">
                                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Kapat</button>
                            </div>
                        `);

                        // 9) İşlem hatasız ise satırı güncelle
                        if (response.status !== 'error' && activePriceRow) {
                            var newDom = $('#modal_yeni_domestic_price').val();
                            var newExp = $('#modal_yeni_export_price').val();
                            activePriceRow.find('input.domestic-price-input').val(newDom).attr('value', newDom);
                            activePriceRow.find('input.export-price-input').val(newExp).attr('value', newExp);
                        }

                        
                    },
                    fail: function(xhr) {
                        const $body = $('#priceUpdateModal .modal-body');
                        const $footer = $('#priceUpdateModal .modal-footer');
                        $footer.hide();
                        $body.html(`
                                        <div class="text-center p-4">
                                            <i class="fa fa-times-circle fa-3x text-danger"></i>
                                            <h4 class="mt-3">Sunucu Hatası</h4>
                                            <pre>${xhr.responseText}</pre>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                                        </div>
                                        `);
                    },
                    error: function(xhr, status, error) {
                        // Spinner’ı mutlaka temizleyin
                        $('#priceUpdateModal #loadingIndicator').remove();

                        console.error('AJAX hata:', status, error);
                        console.log('Sunucu cevap metni:', xhr.responseText);
                        alert('Sunucu hatası oluştu; konsolu kontrol edin.');
                    }
                });
            });
        <?php } ?>
        // priceUpdateModal kapandığında herhangi bir işlem yapma
        $('#priceUpdateModal').on('hidden.bs.modal', function() {
            // Modal kapatıldığında içerik ve form ilk haline döner
            $('#priceUpdateModal .modal-body').html(priceUpdateOriginalBody);
            $('#priceUpdateForm')[0].reset();
            loadMailListTo('#mailListContent');
            activePriceRow = null;
        });

        // HTML içine yerleştirilecek değeri güvenli hale getiren fonksiyon
        function escapeHtml(text) {
            if (!text) return '';
            return text.replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function formatLogoNumber(value) {
            if (value === null || value === undefined || isNaN(value)) {
                return '-';
            }
            return Number(value).toLocaleString('tr-TR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function formatLogoPrice(value, currencyLabel) {
            var numberText = formatLogoNumber(value);
            return currencyLabel ? numberText + ' ' + currencyLabel : numberText;
        }

        function renderInvoiceHistory(response) {
            var $container = $('#logoInvoiceHistory');
            if (!response || !response.sources) {
                $container.html('<div class="text-danger small">Fatura verileri alınamadı.</div>');
                return;
            }

            var keys = Object.keys(response.sources);
            if (keys.length === 0) {
                $container.html('<div class="text-muted small">Logo referansı bulunamadı.</div>');
                return;
            }

            var html = '';
            keys.forEach(function(key) {
                var src = response.sources[key];
                var invoices = src.invoices || [];
                html += '<div class="card shadow-sm mb-3">';
                html += '<div class="card-body p-3">';
                html += '<div class="d-flex justify-content-between align-items-center mb-2">';
                html += '<span class="fw-semibold">' + escapeHtml(src.label || key) + '</span>';
                if (src.firm_nr) {
                    html += '<span class="badge bg-light text-dark">Firma ' + escapeHtml(String(src.firm_nr)) + '</span>';
                }
                html += '</div>';
                if (!invoices.length) {
                    html += '<p class="text-muted small mb-0">Fatura kaydı bulunamadı.</p>';
                } else {
                    html += '<div class="table-responsive">';
                    html += '<table class="table table-sm table-striped mb-0">';
                    html += '<thead><tr><th>Fatura</th><th>Tarih</th><th>Müşteri</th><th class="text-end">Adet</th><th class="text-end">Birim Fiyat</th></tr></thead><tbody>';
                    invoices.forEach(function(inv) {
                        var qtyText = formatLogoNumber(inv.quantity);
                        var unitText = inv.unit_code ? ' ' + escapeHtml(inv.unit_code) : '';
                        var customerCell = '-';
                        if (inv.customer_code || inv.customer_name) {
                            var code = inv.customer_code ? '<div class="fw-semibold">' + escapeHtml(inv.customer_code) + '</div>' : '';
                            var name = inv.customer_name ? '<div class="text-muted small">' + escapeHtml(inv.customer_name) + '</div>' : '';
                            customerCell = code + name;
                        }
                        html += '<tr>';
                        html += '<td class="text-nowrap">' + escapeHtml(inv.invoice_no || '-') + '</td>';
                        html += '<td>' + escapeHtml(inv.invoice_date || '') + '</td>';
                        html += '<td>' + customerCell + '</td>';
                        html += '<td class="text-end">' + qtyText + unitText + '</td>';
                        html += '<td class="text-end">' + formatLogoPrice(inv.unit_price, inv.currency_label) + '</td>';
                        html += '</tr>';
                    });
                    html += '</tbody></table></div>';
                }
                html += '</div></div>';
            });

            $container.html(html);
        }

        function loadInvoiceHistory(stokKodu, limit = 5) {
            var $container = $('#logoInvoiceHistory');
            $container.html('<div class="text-muted small">Logo faturaları yükleniyor...</div>');
            $.ajax({
                url: 'urunlerlogo.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'getInvoiceHistory',
                    stok_kodu: stokKodu,
                    limit: limit
                },
                success: function(response) {
                    if (!response) {
                        $container.html('<div class="text-danger small">Fatura bilgisi alınamadı.</div>');
                        return;
                    }
                    if (response.error) {
                        $container.html('<div class="text-danger small">' + escapeHtml(response.error) + '</div>');
                        return;
                    }
                    if (response.success === false && response.message) {
                        $container.html('<div class="text-danger small">' + escapeHtml(response.message) + '</div>');
                        return;
                    }
                    renderInvoiceHistory(response);
                },
                error: function() {
                    $container.html('<div class="text-danger small">Logo faturaları yüklenirken hata oluştu.</div>');
                }
            });
        }

        $(document).on('click', '.detail-update-btn', function() {
            var stokKodu = $(this).data('stokkodu');
            console.log("Detay güncelleme işlemi başlatıldı. Stok Kodu:", stokKodu);

            $('#detay_stok_kodu').val(stokKodu);
            $('#materialTranslationsContainer').html('');
            $('#associatedProductsContainer').html('');
            loadInvoiceHistory(stokKodu);

            $.ajax({
                url: 'urunlerlogo.php',
                type: 'POST',
                data: {
                    action: 'getDetails',
                    stok_kodu: stokKodu
                },
                dataType: 'json',
                success: function(response) {
                    console.log("getDetails AJAX isteği başarılı. Yanıt:", response);
                    if (response.error) {
                        console.error("AJAX Hatası (getDetails):", response.error);
                        alert(response.error);
                        return;
                    }

                    // Malzeme çeviri bilgilerini oluştur
                    var materialHtml = '';
                    var logoSection = '';
                    if (response.gempa_name !== undefined || response.gemas_name !== undefined ||
                        response.gempa_name3 !== undefined || response.gemas_name3 !== undefined ||
                        response.gempa_name4 !== undefined || response.gemas_name4 !== undefined) {
                        logoSection += '<div class="accordion mb-3" id="logoAccordion">';
                        logoSection += '<div class="accordion-item">';
                        logoSection += '<h2 class="accordion-header" id="logoHeading">';
                        // "collapsed" class removed, aria-expanded="true"
                        logoSection += '<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#logoCollapse" aria-expanded="true" aria-controls="logoCollapse">Logo Bilgileri</button>';
                        logoSection += '</h2>';
                        // "show" class added
                        logoSection += '<div id="logoCollapse" class="accordion-collapse collapse show" aria-labelledby="logoHeading" data-bs-parent="#logoAccordion">';
                        logoSection += '<div class="accordion-body">';
                        if (response.gempa_name !== undefined) {
                            logoSection += '<div class="mb-2"><label for="gempa_name" class="form-label">Gempa Logo Açıklama</label>';
                            logoSection += '<input type="text" class="form-control form-control-sm" name="gempa_name" id="gempa_name" value="' + escapeHtml(response.gempa_name || '') + '"></div>';
                        }
                        if (response.gempa_name3 !== undefined) {
                            logoSection += '<div class="mb-2"><label for="gempa_name3" class="form-label">Gempa Logo Açıklama 1</label>';
                            logoSection += '<input type="text" class="form-control form-control-sm" name="gempa_name3" id="gempa_name3" value="' + escapeHtml(response.gempa_name3 || '') + '"></div>';
                        }
                        if (response.gempa_name4 !== undefined) {
                            logoSection += '<div class="mb-2"><label for="gempa_name4" class="form-label">Gempa Logo Açıklama 2</label>';
                            logoSection += '<input type="text" class="form-control form-control-sm" name="gempa_name4" id="gempa_name4" value="' + escapeHtml(response.gempa_name4 || '') + '"></div>';
                        }
                        if (response.gemas_name !== undefined) {
                            logoSection += '<div class="mb-2"><label for="gemas_name" class="form-label">Gemas Logo Açıklama</label>';
                            logoSection += '<input type="text" class="form-control form-control-sm" name="gemas_name" id="gemas_name" value="' + escapeHtml(response.gemas_name || '') + '"></div>';
                        }
                        if (response.gemas_name3 !== undefined) {
                            logoSection += '<div class="mb-2"><label for="gemas_name3" class="form-label">Gemas Logo Açıklama 1</label>';
                            logoSection += '<input type="text" class="form-control form-control-sm" name="gemas_name3" id="gemas_name3" value="' + escapeHtml(response.gemas_name3 || '') + '"></div>';
                        }
                        if (response.gemas_name4 !== undefined) {
                            logoSection += '<div class="mb-2"><label for="gemas_name4" class="form-label">Gemas Logo Açıklama 2</label>';
                            logoSection += '<input type="text" class="form-control form-control-sm" name="gemas_name4" id="gemas_name4" value="' + escapeHtml(response.gemas_name4 || '') + '"></div>';
                        }
                        logoSection += '</div></div></div></div>';
                        materialHtml += logoSection;
                    }
                    var hasMaterialTranslations = Array.isArray(response.material_translations) && response.material_translations.length;
                    if (hasMaterialTranslations) {
                        $.each(response.material_translations, function(index, item) {
                            materialHtml += '<div class="mb-3">';
                            materialHtml += '<label for="aciklama_' + item.locale + '">Açıklama (' + item.locale + ')</label>';
                            materialHtml += '<textarea class="form-control" name="material[' + item.locale + '][aciklama]" id="aciklama_' + item.locale + '">' + item.aciklama + '</textarea>';
                            materialHtml += '<input type="hidden" name="material[' + item.locale + '][malzeme_id]" value="' + item.malzeme_id + '">';
                            materialHtml += '<input type="hidden" name="material[' + item.locale + '][locale]" value="' + item.locale + '">';
                            materialHtml += '</div>';
                        });
                    }
                    if (!hasMaterialTranslations) {
                        materialHtml += '<div class="alert alert-warning mb-0">Web sitesinde bu stok kodu için çeviri bilgisi bulunamadı.</div>';
                    }
                    $('#materialTranslationsContainer').html(materialHtml);

                    // Ürün çeviri bilgileri için container'ı temizleyip DOM elemanları ekleyelim
                    var productsContainer = $('#associatedProductsContainer');
                    productsContainer.empty();
                    if (response.associated_products && Object.keys(response.associated_products).length) {
                        $.each(response.associated_products, function(urun_id, translations) {
                            var card = $('<div class="card mb-3"></div>');
                            card.append('<div class="card-header">Ürün ID: ' + urun_id + '</div>');
                            var cardBody = $('<div class="card-body"></div>');
                            $.each(translations, function(index, trans) {
                                var fieldDiv = $('<div class="mb-3"></div>');
                                fieldDiv.append('<label for="urun_ad_' + urun_id + '_' + trans.locale + '">Ürün Adı (' + trans.locale + ')</label>');

                                // jQuery nesnesi oluşturup, .val() ile ürün adını atıyoruz:
                                var inputAd = $('<input type="text" class="form-control">')
                                    .attr("name", "products[" + urun_id + "][" + trans.locale + "][ad]")
                                    .attr("id", "urun_ad_" + urun_id + "_" + trans.locale)
                                    .val(trans.ad);
                                fieldDiv.append(inputAd);

                                cardBody.append(fieldDiv);

                                var areaDiv = $('<div class="mb-3"></div>');
                                areaDiv.append('<label for="urun_aciklama_' + urun_id + '_' + trans.locale + '">Açıklama (' + trans.locale + ')</label>');
                                areaDiv.append('<textarea class="form-control" name="products[' + urun_id + '][' + trans.locale + '][aciklama]" id="urun_aciklama_' + urun_id + '_' + trans.locale + '">' + trans.aciklama + '</textarea>');
                                // Gizli inputlar
                                areaDiv.append('<input type="hidden" name="products[' + urun_id + '][' + trans.locale + '][urun_id]" value="' + urun_id + '">');
                                areaDiv.append('<input type="hidden" name="products[' + urun_id + '][' + trans.locale + '][locale]" value="' + trans.locale + '">');
                                cardBody.append(areaDiv);
                            });
                            card.append(cardBody);
                            productsContainer.append(card);
                        });
                    } else {
                        productsContainer.html('<div class="alert alert-info mb-0">İlişkili ürün çeviri bilgisi bulunamadı.</div>');
                    }

                    $('#detayModal').modal('show');
                    console.log("Detay modal gösterildi.");
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Hatası (getDetails):", status, error);
                    console.log("Ham Yanıt:", xhr.responseText);
                    alert('Detay bilgileri alınırken bir hata oluştu.');
                }
            });
            });

            // Aktiflik değiştirme
            $(document).on('click', '.active-toggle', function(e) {
                e.preventDefault();
                var chk = $(this);
                var oldStatus = parseInt(chk.data('current'), 10);
                var newStatus = oldStatus === 0 ? 1 : 0;
                // eski durumu hemen geri yükle ki kullanıcı onaylamazsa görünüm değişmesin
                chk.prop('checked', oldStatus === 0);

                $('#active_stok_kodu').val(chk.data('stokkodu'));
                $('#active_gempa_logicalref').val(chk.data('gempa'));
                $('#active_gemas_logicalref').val(chk.data('gemas'));
                $('#active_new_status').val(newStatus);
                $('#active_old_status').val(oldStatus);
                $('#active_urun_adi').val(chk.data('urunadi'));
                $('#active_display_stok_kodu').text(chk.data('stokkodu'));
                $('#active_display_urun_adi').text(chk.data('urunadi'));
                $('#active_display_new_status').text(newStatus === 0 ? 'Kullanımda' : 'Kullanım Dışı');
                $('#activeUpdateModal').data('checkbox', chk);
                if ($('#activeSendMailCheckbox').is(':checked')) {
                    $('#activeMailListContainer').show();
                    loadMailListTo('#activeMailListContent');
                } else {
                    $('#activeMailListContainer').hide();
                }
                $('#activeUpdateModal').modal('show');
            });

            // Aktiflik mail gönderim kutusu için de delegasyon
            $(document).on('change', '#activeSendMailCheckbox', function() {
                if ($(this).is(':checked')) {
                    $('#activeMailListContainer').show();
                    loadMailListTo('#activeMailListContent');
                } else {
                    $('#activeMailListContainer').hide();
                }
            });

            if ($('#activeSendMailCheckbox').is(':checked')) {
                $('#activeMailListContainer').show();
                loadMailListTo('#activeMailListContent');
            }

            $(document).on('click', '#activeRefreshMailList', function() {
                loadMailListTo('#activeMailListContent');
            });

            $('#activeUpdateForm').on('submit', function(e) {
                e.preventDefault();
                var selectedMailIds = [];
                if ($('#activeSendMailCheckbox').is(':checked')) {
                    $('#activeMailListContent .mail-checkbox:checked').each(function() {
                        selectedMailIds.push($(this).val());
                    });
                }
                var formData = $(this).serializeArray();
                formData.push({ name: 'selected_mail_ids', value: selectedMailIds.join(',') });
                var chk = $('#activeUpdateModal').data('checkbox');
                var oldStatus = $('#active_old_status').val();
                var newStatusVal = $('#active_new_status').val();
                $.ajax({
                    url: 'urunlerlogo.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        $('#activeUpdateModal').modal('hide');
                        showActiveUpdateToast(response);
                        if (response.status === 'success') {
                            chk.data('current', parseInt(newStatusVal));
                            chk.prop('checked', newStatusVal == 0);
                        } else {
                            chk.prop('checked', oldStatus == 0);
                        }
                    },
                    error: function() {
                        alert('Aktiflik güncellenemedi');
                        chk.prop('checked', oldStatus == 0);
                    }
                });
            });

        // Detay form gönderimi işlemi
        $('#detayForm').on('submit', function(e) {
            e.preventDefault();
            var formData = $(this).serialize();
            console.log("Detay formu gönderiliyor. Form verileri:", formData);

            $.ajax({
                url: 'urunlerlogo.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    console.log("Güncelleme AJAX isteği yanıtı:", response);
                    if (response.success) {
                        alert(response.message);
                        $('#detayModal').modal('hide');
                        console.log("Güncelleme başarılı, modal kapatıldı.");
                    } else {
                        alert(response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Hatası (updateDetails):", status, error);
                    alert('Güncelleme sırasında bir hata oluştu.');
                }
            });
        });
    </script>
    <script>
        // Cost visibility toggle functionality
        $(document).on('click', '.cost-toggle-icon', function() {
            const icon = $(this);
            const input = icon.siblings('.cost-price-input');
            const isVisible = input.css('-webkit-text-security') === 'none';
            
            if (isVisible) {
                // Hide the value
                input.css({
                    '-webkit-text-security': 'disc',
                    'text-security': 'disc'
                });
                input.attr('readonly', true);
                icon.removeClass('bi-eye-slash').addClass('bi-eye');
                icon.attr('title', 'Maliyeti Göster');
            } else {
                // Show the value
                input.css({
                    '-webkit-text-security': 'none',
                    'text-security': 'none'
                });
                input.attr('readonly', false);
                icon.removeClass('bi-eye').addClass('bi-eye-slash');
                icon.attr('title', 'Maliyeti Gizle');
            }
        });
    </script>
    <script>
        $(document).ready(function() {
            // URL'den 'search' parametresini al
            var urlParams = new URLSearchParams(window.location.search);
            var searchTerm = urlParams.get('search');
            
            // Eğer arama terimi varsa
            if (searchTerm) {
                // Datatable henüz yüklenmemiş olabilir, biraz bekleyip deneyelim veya hemen deneyelim
                var table = $('#example').DataTable();
                
                // Arama yap
                table.search(searchTerm).draw();
                
                // Debug için
                console.log("URL search parametresi algılandı ve arama yapıldı:", searchTerm);
            }
        });
    </script>
</body>

</html>
