<?php
//teklifsiparisler-duzenle.php
ob_start();

$logErrorFile = __DIR__ . "/error.log";
$logDebugFile = __DIR__ . "/debug.log";

// Enable PHP error logging to our custom file so unexpected issues are recorded
ini_set('log_errors', 1);
ini_set('error_log', $logErrorFile);
$config = require __DIR__ . '/config/config.php';

require_once __DIR__ . '/vendor/autoload.php';
include "fonk.php";
oturumkontrol();
$userType = $_SESSION['user_type'] ?? '';

// Include the DatabaseManager and LogoService class definitions if not loaded by autoloader
require_once __DIR__ . '/classes/DatabaseManager.php';
require_once __DIR__ . '/classes/LogoService.php';
require_once __DIR__ . '/src/Models/SalesOrderMap.php';
require_once __DIR__ . '/services/OrderComparisonService.php';

use Proje\LogoService;
use Proje\DatabaseManager;
use Proje\Services\OrderComparisonService;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

require_once __DIR__ . '/services/RevisionService.php';

use Services\RevisionService;
// en başta (config’den sonra)
$controllerLogger = new Logger('controller');
$controllerLogger->pushHandler(new StreamHandler(__DIR__ . '/ajax.log', Logger::DEBUG));

$dbConfig = [
    'host' => $config['db']['host'],
    'port' => $config['db']['port'],
    'user' => $config['db']['user'],
    'pass' => $config['db']['pass'],
    'name' => $config['db']['name'],
];

global $dbManager;
$dbManager = new DatabaseManager($dbConfig);

$logoService = new LogoService(
    db: $dbManager,
    configArray: $config,
    logErrorFile: $logErrorFile,
    logDebugFile: $logDebugFile
);

$comparisonService = new OrderComparisonService(__DIR__ . '/debug.log');
$revisionService = new RevisionService($dbManager->getConnection());
$firmNr = 997;
$departments = $logoService->getDepartments($firmNr);
$factories = $logoService->getFactories($firmNr);
$divisions = $logoService->getDivisions($firmNr);
$warehouses = $logoService->getWarehouses($firmNr);
$tradeGroups = $logoService->getTradeGroups();
$salesmen = $logoService->getSalesmen($firmNr);
$payPlans = $logoService->getPayPlans($firmNr);
$unitSets = $logoService->getUnitSets($firmNr);
$specodes = $logoService->getSpecodes($firmNr);
$contracts = $dbManager->getContracts();

if (isset($_POST['action']) && $_POST['action'] === 'syncRef') {
    $firmNr = 997;
    $result = $logoService->syncReferenceData($firmNr);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result);
    exit;
}

// dosyanın en başındaki global $dbManager instance’ınız zaten mevcut
if (isset($_POST['action']) && $_POST['action'] === 'addDiscount') {
    header('Content-Type: application/json; charset=utf-8');

    $teklifid = (int) ($_POST['teklifid'] ?? 0);
    $parent_id = (int) ($_POST['parent_id'] ?? 0);
    $rate = (float) ($_POST['discount_rate'] ?? 0);
    $desc = trim($_POST['description'] ?? '');

    if ($parent_id > 0) {
        // Kalem-altı indirim
        $ok = $dbManager->addOfferItemDiscount($teklifid, $parent_id, $rate, $desc);
    } else {
        // Genel indirim
        $ok = $dbManager->addOfferTotalDiscount($teklifid, $rate, $desc);
    }

    if ($ok) {
        echo json_encode([
            'status' => true,
            'message' => $parent_id > 0
                ? 'Kalem iskonto satırı başarıyla eklendi.'
                : 'Genel iskonto başarıyla eklendi.'
        ]);
    } else {
        $err = $dbManager->getConnection()->error;
        echo json_encode(['status' => false, 'message' => 'Ekleme başarısız: ' . $err]);
    }
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'respondRevize') {
    $notes = trim($_POST['notes'] ?? '');
    $oldStatus = $teklif['durum'];
    // Tek tip yeni durum
    $newStatus = 'Revize Talebine Yanıt Verildi';

    $revisionService->changeStatus(
        $teklifid,
        $oldStatus,
        $newStatus,
        $yonetici_id,
        $notes,
        $teklif['sirket_arp_code']
    );
    header("Location: teklifsiparisler-duzenle.php?te={$teklifid}");
    exit;
}

// -- Admin Approval Actions --


$ajaxActions = [
    'compareHeader',
    'compareItems',
    'updateHeaderToLogo',
    'updateItemsToLogo',
    'getContract'
];

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['action'])
    && in_array($_POST['action'], $ajaxActions, true)
) {
    ob_clean();

    header('Content-Type: application/json; charset=utf-8');

    $action = $_POST['action'];
    if ($action !== 'getContract') {
        $ref = trim($_POST['internal_reference'] ?? '');
        if ($ref === '') {
            echo json_encode(['status' => false, 'message' => 'Internal reference eksik']);
            exit;
        }
    }

    // Hangi alanları ignore edeceksin?
    $ignore = [
        'due_date',
        'curr_price',
        'pc_price',
        'rc_xrate',
        'excline_price',
        'excline_total',
        'excline_vat_matrah',
        'excline_line_net',
        'edt_price',
        'edt_curr',
        'org_due_date',
        'org_price',
        'parent_internal_reference'
    ];

    switch ($_POST['action']) {
        case 'compareHeader':
            $ref = (int) $_POST['internal_reference'];
            $logoRaw = $logoService->getSalesOrder($ref);
            $localMapped = $dbManager->getOfferHeaderMapped((int) $_POST['teklifid']);

            // Service’i kullan
            $diff = $comparisonService->compareHeaders(
                $localMapped,
                $logoRaw
            );

            echo json_encode(['status' => true, 'diff' => $diff]);
            exit;

        case 'compareItems':
            $ref = (int) $_POST['internal_reference'];
            $logoRawItems = $logoService->getSalesOrderTransactions($ref)['items'] ?? [];
            $localItems = $dbManager->getOfferItemsMapped((int) $_POST['teklifid']);

            // Service’i kullan
            $diffItems = $comparisonService->compareItems(
                $localItems,
                $logoRawItems,
                $ignore
            );

            echo json_encode(['status' => true, 'diffItems' => $diffItems]);
            exit;

        case 'updateHeaderToLogo':
            $ref = (int) $_POST['internal_reference'];
            $teklifId = (int) $_POST['teklifid'];
            $logoRaw = $logoService->getSalesOrder($ref);
            $localMapped = $dbManager->getOfferHeaderMapped($teklifId);
            $result = $comparisonService->compareHeadersWithPayload($localMapped, $logoRaw);
            $diff = $result['diff'];
            $payload = $result['updatePayload'];
            if (empty($payload)) {
                echo json_encode([
                    'status' => true,
                    'message' => 'Güncellenecek fark bulunamadı.',
                    'diff' => $diff
                ]);
                exit;
            }
            $apiResp = $logoService->updateOrderHeader($ref, $payload);
            $freshHeader = $logoService->getSalesOrder($ref);
            $logoService->updateHeaderFields($teklifId, $freshHeader);
            echo json_encode([
                'status' => true,
                'message' => 'Başlık Logo’da güncellendi.',
                'diff' => $diff,
                'apiResponse' => $apiResp
            ]);
            exit;

        case 'updateItemsToLogo':
            $ref = (int) $_POST['internal_reference'];
            $teklifId = (int) $_POST['teklifid'];

            // 1) Logo’daki ve DB’deki kalemleri alıp diff & payload oluşturun (mevcut kod)
            $transResp = $logoService->getSalesOrderTransactions($ref);
            $logoRawItems = $transResp['items'] ?? ($transResp['TRANSACTIONS']['items'] ?? []);
            $localItems = $dbManager->getOfferItemsMapped($teklifId);
            $result = $comparisonService->compareItemsWithPayload($localItems, $logoRawItems, $ignore);
            $diffItems = $result['diff'];
            $payloads = $result['updatePayload'];

            if (empty($payloads)) {
                echo json_encode([
                    'status' => true,
                    'message' => 'Güncellenecek kalem farkı bulunamadı.',
                    'diff' => $diffItems,
                ]);
                exit;
            }

            // 2) PUT çağrısını try/catch içinde yapın
            try {
                $apiResp = $logoService->updateOrderItems($teklifId, $ref, $payloads);
                $response = [
                    'status' => true,
                    'message' => 'Kalemler Logo’da başarıyla güncellendi.',
                    'diff' => $diffItems,
                    'apiResponse' => $apiResp,
                ];
            } catch (Exception $e) {
                // Burada e->getMessage() LogoService::compileErrorMessage() ile gelen Logo hatasını dönecek
                $response = [
                    'status' => false,
                    'message' => 'Kalem güncelleme hatası: ' . $e->getMessage(),
                    'diff' => $diffItems,
                ];
            }

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($response);
            exit;

        case 'getContract':
            $id = (int) ($_POST['id'] ?? 0);
            $c = $dbManager->getContractById($id);
            if ($c) {
                echo json_encode(['status' => true, 'data' => $c]);
            } else {
                echo json_encode(['status' => false]);
            }
            exit;
    }
}

function convert($data)
{
    return strpos($data, ",") !== false ? str_replace(",", ".", $data) : $data;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. "Logoya Gönder" işlemi:
    if (isset($_POST['logoyaAktar']) && $_POST['logoyaAktar'] == 1) {
        $orderId = (int) ($_POST['icerikid'] ?? 0);
        $result = $logoService->transferOrder($orderId);
        ob_clean();
        echo json_encode($result);
        exit;
    }

    // 2. Manuel Logo bilgileri güncelleme:
    if (isset($_POST['updateLogoInfo'])) {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        $teklifid = filter_input(INPUT_POST, 'teklifid', FILTER_SANITIZE_NUMBER_INT);
        $vatexcept_code = $_POST['vatexcept_code'] ?? "";
        if ($vatexcept_code === 'other') {
            $vatexcept_code = trim($_POST['vatexcept_code_other'] ?? '');
        }

        $vatexcept_reason = $_POST['vatexcept_reason'] ?? "";
        if ($vatexcept_reason === 'other') {
            $vatexcept_reason = trim($_POST['vatexcept_reason_other'] ?? '');
        }
        $auxil_code = $_POST['auxil_code'] ?? "";
        $auth_code = $_POST['auth_code'] ?? "";
        $order_note = $_POST['order_note'] ?? "";

        $division = (int) ($_POST['division'] ?? 0);
        $department = (int) ($_POST['department'] ?? 0);

        $source_wh = (int) ($_POST['source_wh'] ?? 0);
        $source_costgrp = (int) ($_POST['source_costgrp'] ?? 0);

        $factory = (string) ($_POST['factory'] ?? "");

        $salesman_code = trim($_POST['salesman_code'] ?? '');
        $salesmanref = (int) ($_POST['salesmanref'] ?? 0);

        $trading_grp = trim($_POST['trading_grp'] ?? '');
        $payment_code = trim($_POST['payment_code'] ?? '');
        $paydefref = (int) ($_POST['paydefref'] ?? 0);
        $doc_number = trim($_POST['doc_number'] ?? '');
       $orderStatus = (int) ($_POST['order_status'] ?? 0);
        $sozlesmeId = (int) ($_POST['sozlesme_id'] ?? 5);

        $yoneticiId = $_SESSION['yonetici_id'];

        // 2) Güncelleme
        $ok = $dbManager->updateLogoInfo(
            teklifId: $teklifid,
            vatexceptCode: $vatexcept_code,
            vatexceptReason: $vatexcept_reason,
            auxilCode: $auxil_code,
            authCode: $auth_code,
            notes: $order_note,
            division: $division,
            department: $department,
            sourceWh: $source_wh,
            sourceCostGrp: $source_costgrp,
            factory: $factory,
            salesmanCode: $salesman_code,
            salesmanRef: $salesmanref,
            tradingGrp: $trading_grp,
            paymentCode: $payment_code,
            paydefRef: $paydefref,
            docNumber: $doc_number,
            orderStatus: $orderStatus,
            sozlesmeId: $sozlesmeId,
        );

        if ($ok) {
            $dbManager->saveHeaderPrefs(
                yoneticiId: $yoneticiId,
                auxilCode: $auxil_code,
                division: $division,
                department: $department,
                sourceWh: $source_wh,
                factory: $factory,
                salesmanRef: $salesmanref
            );
        }

        // 3) JSON olarak dönelim
        if ($ok) {
            echo json_encode(["status" => true, "message" => "Logo bilgileri başarıyla güncellendi."]);
        } else {
            // DB hatasını da debug için verelim
            $dbErr = $dbManager->getConnection()->error;
            error_log("DB Error: $dbErr\n", 3, $logErrorFile);
            echo json_encode([
                "status" => false,
                "message" => "Güncelleme sırasında hata oluştu.",
                "debug" => $dbErr
            ]);
        }
        exit;
    }
}

// 1. Gelen teklif ID’sini sanitize edin ve integer’a çevirin
$teklifid = 0;
if (isset($_GET['te'])) {
    $teklifid = (int) $_GET['te'];
} elseif (isset($_GET['tid'])) {
    $teklifid = (int) $_GET['tid'];
}

// Teklif ve ilgili kişi bilgilerini çekiyoruz:
$teklif = $dbManager->getOffer($teklifid);
$dealerCompany = $_SESSION['dealer_company_id'] ?? 0;
if ($userType === 'Bayi') {
    if (!$teklif || (int)($teklif['sirketid'] ?? 0) !== (int)$dealerCompany) {
        die('Bu teklife erişim yetkiniz yok.');
    }
}
$company = [];
if (!empty($teklif['sirketid']) && ctype_digit((string) $teklif['sirketid'])) {
    $company = $dbManager->getCompanyInfoById((int) $teklif['sirketid']);
} elseif (!empty($teklif['sirket_arp_code'])) {
    $company = $dbManager->getCompanyInfo($teklif['sirket_arp_code']);
}
$detailUrl = 'company_details.php';
if (!empty($teklif['sirketid']) && ctype_digit((string)$teklif['sirketid'])) {
    $detailUrl .= '?id=' . (int) $teklif['sirketid'];
} elseif (!empty($teklif['sirket_arp_code'])) {
    $detailUrl .= '?code=' . urlencode($teklif['sirket_arp_code']);
}

// -- Admin Approval Actions (Moved here to ensure $teklif is defined) --
$currentStatus = trim($teklif['durum'] ?? '');
if (isset($_POST['approveOffer']) && $currentStatus === 'Yönetici Onayı Bekleniyor') {
    $approverId = $_SESSION['yonetici_id'] ?? 0;
    // Update status and set approver ID
    $upd = $db->prepare("UPDATE ogteklif2 SET durum='Yönetici Onayladı / Gönderilecek', onaylayanid=? WHERE id=?");
    $upd->bind_param("ii", $approverId, $teklifid);
    if ($upd->execute()) {
        echo "<script>alert('Teklif başarıyla ONAYLANDI.'); window.location.href='teklifsiparisler-duzenle.php?te={$teklifid}';</script>";
        exit;
    } else {
        echo "<script>alert('Hata: " . $db->error . "');</script>";
    }
}
if (isset($_POST['rejectOffer']) && $currentStatus === 'Yönetici Onayı Bekleniyor') {
    $approverId = $_SESSION['yonetici_id'] ?? 0;
    $upd = $db->prepare("UPDATE ogteklif2 SET durum='Yönetici Tarafından Red', onaylayanid=? WHERE id=?");
    $upd->bind_param("ii", $approverId, $teklifid);
    if ($upd->execute()) {
         echo "<script>alert('Teklif REDDEDİLDİ.'); window.location.href='teklifsiparisler-duzenle.php?te={$teklifid}';</script>";
         exit;
    }
}
$orderStatusCurrent = (int)($teklif['order_status'] ?? 1);
$prepInfo = $dbManager->resolvePreparer($teklif["hazirlayanid"] ?? "");
$personelprofil = ["adsoyad" => $prepInfo["name"], "eposta" => $prepInfo["email"], "telefon" => ""];
$hazirlayanKaynak = $prepInfo["source"];

// Telefon bilgisini al
if ($hazirlayanKaynak === 'Bayi') {
    $hazirlayanIdNum = (int)preg_replace('/\D+/', '', $teklif["hazirlayanid"] ?? "");
    if ($hazirlayanIdNum > 0) {
        $b2bUser = $dbManager->getB2bUserById($hazirlayanIdNum);
        if ($b2bUser) {
            // b2b_users tablosunda telefon kolonu varsa kullan
            $personelprofil["telefon"] = $b2bUser['telefon'] ?? $b2bUser['phone'] ?? '';
            // Şirket bilgisinden telefon al
            if (empty($personelprofil["telefon"]) && !empty($b2bUser['company_id'])) {
                $companyInfo = $dbManager->getCompanyInfoById((int)$b2bUser['company_id']);
                if ($companyInfo) {
                    $personelprofil["telefon"] = $companyInfo['s_telefonu'] ?? $companyInfo['s_telefonu2'] ?? '';
                }
            }
        }
    }
} else {
    // Manager için telefon bilgisini al
    $hazirlayanIdNum = (int)preg_replace('/\D+/', '', $teklif["hazirlayanid"] ?? "");
    if ($hazirlayanIdNum > 0) {
        $mgr = $dbManager->getManagerProfile($hazirlayanIdNum);
        if ($mgr) {
            $personelprofil["telefon"] = $mgr['telefon'] ?? $mgr['phone'] ?? '';
        }
    }
}
$yonetici_id = $_SESSION['yonetici_id'];
$headerPrefs = $dbManager->getHeaderPrefs($yonetici_id);
foreach ([
    'auxil_code' => 'pref_auxil_code',
    'division' => 'pref_division',
    'department' => 'pref_department',
    'source_wh' => 'pref_source_wh',
    'factory' => 'pref_factory',
    'salesmanref' => 'pref_salesmanref'
] as $field => $prefField) {
    if (empty($teklif[$field]) && isset($headerPrefs[$prefField])) {
        $teklif[$field] = $headerPrefs[$prefField];
    }
}
$iskonto_max = $dbManager->getMaxDiscount($yonetici_id);

// Ödeme/Tahsilat planı görüntü bilgisi
$payPlanDisplay = '-';
if (!empty($teklif['paydefref'])) {
    foreach ($payPlans as $p) {
        if ((int)$p['LOGICALREF'] === (int)$teklif['paydefref']) {
            $payPlanDisplay = $p['CODE'] . ' - ' . $p['DEFINITION_'];
            break;
        }
    }
} else {
    $plan = trim(($company['payplan_code'] ?? '') . ' - ' . ($company['payplan_def'] ?? ''));
    if ($plan !== '') {
        $payPlanDisplay = $plan;
    }
}

$gecerlilikDisplay = '-';
if (!empty($teklif['teklifgecerlilik'])) {
    $ts = strtotime(str_replace('Saat', '', $teklif['teklifgecerlilik']));
    if ($ts !== false) {
        $gecerlilikDisplay = date('d.m.Y H:i', $ts);
    } else {
        $gecerlilikDisplay = $teklif['teklifgecerlilik'];
    }
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && (isset($_POST['action']) && $_POST['action'] === 'updateItem')
) {
    header('Content-Type: application/json; charset=utf-8');
    $id = (int) $_POST['id'];
    $miktar = (float) $_POST['miktar'];
    $birim = trim($_POST['birim']);
    $iskonto = (float) $_POST['iskonto'];
    $stmtRef = $conn->prepare("SELECT LOGICALREF FROM urunler u JOIN ogteklifurun2 o ON u.urun_id=o.urun_id WHERE o.id=?");
    $stmtRef->bind_param('i', $id);
    $stmtRef->execute();
    $refRow = $stmtRef->get_result()->fetch_assoc();
    $stmtRef->close();
    $campRate = null;
    if ($refRow) {
        $campRate = $dbManager->getCampaignDiscountForProduct((int)$refRow['LOGICALREF']);
    }
    if ($campRate !== null) {
        $iskonto = $campRate;
    } elseif ($iskonto_max <= 0) {
        $iskonto = 0.0;
    } elseif ($iskonto > $iskonto_max) {
        $iskonto = $iskonto_max;
    }
    $teklifid = (int) $_POST['teklifid'];

    // 1) Kalemi güncelle
    $ok = $dbManager->updateOfferItemAndTotals($id, $miktar, $birim, $iskonto);

    if ($ok) {
        // 2) Yeni toplamları al
        $totals = $dbManager->getOrderTotals($teklifid);
        echo json_encode([
            'status' => true,
            'totals' => $totals
        ]);
    } else {
        echo json_encode([
            'status' => false,
            'message' => 'Güncelleme başarısız.'
        ]);
    }
    exit;
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['action']) && $_POST['action'] === 'bulkUpdateRows'
) {
    header('Content-Type: application/json; charset=utf-8');
    // JSON olarak gelen items parametresini oku
    $items = json_decode($_POST['items'], true) ?? [];
    $errors = [];

    foreach ($items as $item) {
        $id = (int) $item['id'];
        $miktar = (int) $item['miktar'];
        $birim = trim($item['birim']);
        $iskonto = (float) $item['iskonto'];
        $stmtRef = $conn->prepare("SELECT LOGICALREF FROM urunler u JOIN ogteklifurun2 o ON u.urun_id=o.urun_id WHERE o.id=?");
        $stmtRef->bind_param('i', $id);
        $stmtRef->execute();
        $refRow = $stmtRef->get_result()->fetch_assoc();
        $stmtRef->close();
        $campRate = null;
        if ($refRow) {
            $campRate = $dbManager->getCampaignDiscountForProduct((int)$refRow['LOGICALREF']);
        }
        if ($campRate !== null) {
            $iskonto = $campRate;
        } elseif ($iskonto_max <= 0) {
            $iskonto = 0.0;
        } elseif ($iskonto > $iskonto_max) {
            $iskonto = $iskonto_max;
        }

        $stmt0 = $conn->prepare("SELECT liste FROM ogteklifurun2 WHERE id = ?");
        $stmt0->bind_param("i", $id);
        $stmt0->execute();
        $row0 = $stmt0->get_result()->fetch_assoc();
        $stmt0->close();
        $listeFiyati = floatval(str_replace(',', '.', $row0['liste']));
        $netUnitPrice = $listeFiyati * (100 - $iskonto) / 100;
        $rowTotal = $netUnitPrice * $miktar;

        // 2) UPDATE sorgusunu nettutar ve tutar’ı da kapsayacak şekilde değiştir
        $stmt = $conn->prepare("
            UPDATE ogteklifurun2
                    SET miktar   = ?,
                        birim    = ?,
                        iskonto  = ?,
                        nettutar = ?,
                        tutar    = ?
                WHERE id       = ?
        ");
        $stmt->bind_param("isdddi", $miktar, $birim, $iskonto, $netUnitPrice, $rowTotal, $id);
        if (!$stmt->execute()) {
            $errors[] = "ID {$id}: " . $stmt->error;
        }
        $stmt->close();
    }

    if (empty($errors)) {
        echo json_encode([
            'status' => true,
            'message' => 'Tüm satırlar başarıyla güncellendi.'
        ]);
    } else {
        echo json_encode([
            'status' => false,
            'message' => 'Bazı satırlar güncellenirken hata oluştu.',
            'errors' => $errors
        ]);
    }
    exit;
}

// GET üzerinden gelen aksiyonlar:
if (isset($_GET['islem']) && $_GET['islem'] === 'sildir') {
    $ims = filter_input(INPUT_GET, 'im', FILTER_SANITIZE_NUMBER_INT);
    if ($dbManager->deleteOfferItem($ims)) {
        $dbManager->logAction('Teklif / Sipariş Ürün Kalemi Silme', $yonetici_id, time(), 'Başarılı');
    } else {
        $dbManager->logAction('Teklif / Sipariş Ürün Kalemi Silme', $yonetici_id, time(), 'Başarısız');
    }
    header('Location: teklifsiparisler-duzenle.php?te=' . $teklifid);
    exit;
}
// GET işlemleri için güncelleme
if (isset($_GET['ekle'])) {
    $urun_id = (int) $_GET['ekle'];
    $teklifid = (int) $_GET['te'];

    // 1) Ürün bilgilerini al
    $p = $dbManager->getProductInfo($urun_id);
    if ($p) {
        // 2) Tüm değerleri string olarak hazırla (DB'deki TEXT sütununa uygun)
        $miktar = '1';
        $birim = $p['olcubirimi'];
        $liste = $p['fiyat'];   // metin olarak saklanıyor
        $doviz = $p['doviz'];
        $iskonto = '0';
        $nettutar = $liste;        // miktar=1 için nettutar = liste
        $tutar = $liste;        // aynı şekilde tutar da liste

        // 3) Hazır SQL
        $sql = "INSERT INTO ogteklifurun2
            (teklifid, kod, adi, miktar, birim, liste, doviz, iskonto, nettutar, tutar)
         VALUES (?,?,?,?,?,?,?,?,?,?)";
        $stmt = $dbManager->getConnection()->prepare($sql);
        // Tip dizisi: 1 i (integer), 9 s (string)
        $stmt->bind_param(
            "isssssssss",
            $teklifid,
            $p['stokkodu'],
            $p['stokadi'],
            $miktar,
            $birim,
            $liste,
            $doviz,
            $iskonto,
            $nettutar,
            $tutar
        );
        $stmt->execute();
        $stmt->close();
    }

    // 4) Yeniden yükle
    header('Location: teklifsiparisler-duzenle.php?te=' . $teklifid);
    exit;
}

if (isset($_GET['cikart'])) {
    $id = filter_input(INPUT_GET, 'cikart', FILTER_SANITIZE_NUMBER_INT);
    $teklifid = filter_input(INPUT_GET, 'te', FILTER_SANITIZE_NUMBER_INT);
    setcookie("teklif_products[$id]", $id, time() - 86400);
    header('Location: teklifsiparisler-duzenle.php?te=' . $teklifid);
    exit;
}

if (isset($_GET['bosalt'])) {
    $teklifid = filter_input(INPUT_GET, 'te', FILTER_SANITIZE_NUMBER_INT);
    if (isset($_COOKIE['teklif_products'])) {
        foreach ($_COOKIE['teklif_products'] as $key => $val) {
            setcookie("teklif_products[$key]", $key, time() - 86400);
        }
    }
    header('Location: teklifsiparisler-duzenle.php?te=' . $teklifid);
    exit;
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title><?= htmlspecialchars($sistemayar["title"]) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?= htmlspecialchars($sistemayar["description"]) ?>" name="description" />
    <meta content="<?= htmlspecialchars($sistemayar["keywords"]) ?>" name="keywords" />
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <!-- CSS Dosyaları -->
    <link href="assets/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/app.min.css" id="app-style" rel="stylesheet" type="text/css" />
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css" rel="stylesheet"
        type="text/css" />
    <link href="assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet"
        type="text/css" />
    <style>
        a {
            text-decoration: none;
        }

        .demo {
            width: 100%;
            min-height: 20vh;
            max-height: 40vh;
            border: 1px solid;
            border-collapse: collapse;
            padding: 5px;
        }

        .demo th,
        .demo td {
            border: 1px solid;
            padding: 5px;
        }

        .page-topbar,
        .navbar {
            display: none;
        }

        body {
            background-color: #f8f9fa;
        }

        .page-content {
            padding: 1rem 0;
        }

        .info-card {
            transition: transform .2s, box-shadow .2s;
        }

        .info-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15);
        }

        .info-card .card-header {
            font-size: 1.1rem;
            font-weight: 600;
        }

        .info-card .list-group-item {
            padding: .75rem 1.25rem;
        }

        /* ERP Form Grid - teklif-olustur.php ile aynı */
        .erp-form-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-bottom: 8px;
            padding: 8px;
            background: #f9f9f9;
            border: 1px solid #ddd;
        }
        
        .erp-form-label {
            font-size: 10px;
            font-weight: 600;
            color: #333;
            margin-bottom: 2px;
            display: block;
            white-space: nowrap;
        }
        
        .erp-form-input {
            font-size: 11px;
            padding: 2px 6px;
            border: 1px solid #ccc;
            border-radius: 0;
            background: white;
            height: 24px;
            line-height: 20px;
        }
        
        .erp-form-input:focus {
            border-color: #6c5ce7;
            outline: 1px solid #6c5ce7;
            outline-offset: -1px;
        }
        
        .erp-form-input[readonly] {
            background-color: #f9f9f9;
        }

        .toolbar .btn {
            min-width: 140px;
        }
    </style>
</head>

<body data-layout="horizontal" data-topbar="colored">
    <!-- Begin page -->
    <div id="layout-wrapper">
        <header id="page-topbar">
            <?php include "menuler/ustmenu.php"; ?>
        </header>
        <!-- Start Content -->
        <div class="main-content">
            <div class="container page-content">
                <!-- ERP Form Grid - teklif-olustur.php ile birebir aynı -->
                <div class="erp-form-grid">
                    <div>
                        <label class="erp-form-label">Teklif No</label>
                        <input type="text" class="form-control erp-form-input" value="<?= htmlspecialchars($teklif["teklifkodu"]) ?>" readonly>
                    </div>
                    <div>
                        <label class="erp-form-label">Tarih</label>
                        <input type="text" class="form-control erp-form-input" value="<?= htmlspecialchars($teklif["tekliftarihi"]) ?>" readonly>
                    </div>
                    <div>
                        <label class="erp-form-label">Geçerlilik</label>
                        <input type="text" class="form-control erp-form-input" value="<?= htmlspecialchars($gecerlilikDisplay) ?>" readonly>
                    </div>
                    <div>
                        <label class="erp-form-label">Logo Aktarım</label>
                        <?= empty($teklif['internal_reference'])
                            ? '<span style="background: #ffc107; color: #000; padding: 2px 8px; border-radius: 3px; font-size: 10px; display: inline-block; margin-top: 2px;">BEKLEMEDE</span>'
                            : '<span style="background: #28a745; color: white; padding: 2px 8px; border-radius: 3px; font-size: 10px; display: inline-block; margin-top: 2px;">TAMAMLANDI</span>' ?>
                    </div>
                </div>
                
                <div class="erp-form-grid">
                    <div>
                        <label class="erp-form-label">Cari Kodu</label>
                        <input type="text" class="form-control erp-form-input" value="<?= htmlspecialchars($teklif["sirket_arp_code"]) ?>" readonly>
                    </div>
                    <div>
                        <label class="erp-form-label">Cari Unvanı</label>
                        <input type="text" class="form-control erp-form-input" value="<?= htmlspecialchars($teklif["musteriadi"]) ?>" readonly>
                    </div>
                    <div>
                        <label class="erp-form-label">Cari Telefon</label>
                        <input type="text" class="form-control erp-form-input" value="<?= htmlspecialchars($company['s_telefonu'] ?? $teklif['projeadi']) ?>" readonly>
                    </div>
                    <div>
                        <label class="erp-form-label">Ödeme Planı</label>
                        <input type="text" class="form-control erp-form-input" value="<?= htmlspecialchars($payPlanDisplay) ?>" readonly>
                    </div>
                </div>
                
                <div class="erp-form-grid">
                    <div>
                        <label class="erp-form-label">Hazırlayan</label>
                        <?php
                        $approverSuffix = "";
                        if (!empty($teklif['onaylayanid'])) {
                            $aid = (int)$teklif['onaylayanid'];
                            $ast = $db->prepare("SELECT adsoyad FROM yonetici WHERE yonetici_id=?");
                            $ast->bind_param("i", $aid);
                            $ast->execute();
                            $ares = $ast->get_result()->fetch_assoc();
                            if ($ares) {
                                $approverSuffix = " (Onaylayan: " . $ares['adsoyad'] . ")";
                            }
                        }
                        ?>
                        <input type="text" class="form-control erp-form-input" value="<?= htmlspecialchars($personelprofil["adsoyad"]) ?> (<?= htmlspecialchars($hazirlayanKaynak) ?>)<?= htmlspecialchars($approverSuffix) ?>" readonly>
                    </div>
                    <div>
                        <label class="erp-form-label">E-Posta</label>
                        <input type="text" class="form-control erp-form-input" value="<?= htmlspecialchars($personelprofil["eposta"]) ?>" readonly>
                    </div>
                    <div>
                        <label class="erp-form-label">Telefon</label>
                        <input type="text" class="form-control erp-form-input" value="<?= htmlspecialchars($personelprofil["telefon"]) ?>" readonly>
                    </div>
                    <div>
                        <label class="erp-form-label">Ödeme Türü</label>
                        <input type="text" class="form-control erp-form-input" value="<?= htmlspecialchars($teklif["odemeturu"]) ?>" readonly>
                    </div>
                </div>
                
                <!-- Ekstra Bilgi / Notlar - teklif-olustur.php ile birebir aynı -->
                <div class="row mt-3">
                    <div class="col-12">
                        <label for="ekstraBilgi" class="form-label" style="font-size: 11px; font-weight: 600; color: #333;">Ekstra Bilgi / Notlar</label>
                        <textarea
                            name="ekstra_bilgi"
                            id="ekstraBilgi"
                            class="form-control"
                            rows="2"
                            style="resize: vertical; min-height: 40px; max-height: 100px; overflow-y: auto; font-size: 12px;"><?php echo htmlspecialchars($teklif['notes1'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <!-- Ürün Listeleme Tablosu - ERP Görünümü -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center" style="padding: 8px 12px; background: #f8f9fa;">
                        <h6 class="mb-0" style="font-size: 13px; font-weight: 600;">🛒 Teklif Kalemlerini İnceleyin</h6>
                        <div class="d-flex gap-2">
                            <?php if (!empty($teklif['internal_reference'])): ?>
                                <button id="btnCompareItems" class="btn btn-info btn-sm" style="font-size: 11px; padding: 4px 8px;">
                                    <i class="bi bi-sliders me-1"></i> Kalem Farklarını Göster
                                </button>
                            <?php endif; ?>
                            <button id="addGlobalDiscountBtn" class="btn btn-primary btn-sm" style="font-size: 11px; padding: 4px 8px;">
                                <i class="bi bi-percent-square me-1"></i> Genel İskonto
                            </button>
                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target=".canliarama" style="font-size: 11px; padding: 4px 8px;">
                                <i class="bi bi-plus-circle me-1"></i> Ürün Ekle
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div style="overflow: visible;">
                            <table id="datatable" class="table table-bordered" style="font-size: 11px; width: 100%; table-layout: fixed;">
                                <thead>
                                    <tr>
                                        <th style="width: 5%;">Aktarım</th>
                                        <th style="width: 4%;">Tür</th>
                                        <th style="width: 10%;">Stok Kodu</th>
                                        <th style="width: 20%;">Stok Adı</th>
                                        <th style="width: 6%;">Miktar</th>
                                        <th style="width: 7%;">İskonto (%)</th>
                                        <th style="width: 10%;">İskonto Formülü</th>
                                        <th style="width: 9%;">İskontolu Birim Fiyat</th>
                                        <th style="width: 9%;">İskontolu Toplam</th>
                                        <th style="width: 6%;">Birim</th>
                                        <th style="width: 5%;">KDV (%)</th>
                                        <th style="width: 9%;">Liste Fiyatı</th>
                                        <th style="width: 5%;">İşlem</th>
                                    </tr>
                                </thead>
                        <tbody>
                            <?php
                            $teklifid = $teklif['id'];
                            // Tüm satırları tek seferde, SIRALAMASIZ çekiyoruz!
                            $stmt = $db->prepare("SELECT * FROM ogteklifurun2 WHERE teklifid = ?");
                            $stmt->bind_param("i", $teklifid);
                            $stmt->execute();
                            $rows = [];
                            $result = $stmt->get_result();
                            while ($row = $result->fetch_assoc()) {
                                if (empty(trim($row['birim'] ?? ''))) {
                                    $uStmt = $db->prepare("SELECT olcubirimi FROM urunler WHERE stokkodu = ? LIMIT 1");
                                    $uStmt->bind_param("s", $row['kod']);
                                    $uStmt->execute();
                                    $uRes = $uStmt->get_result()->fetch_assoc();
                                    if ($uRes) {
                                        $row['birim'] = $uRes['olcubirimi'];
                                    }
                                    $uStmt->close();
                                }
                                $rows[] = $row;
                            }
                            $stmt->close();

                            // 1) Sınıflandır
                            $productRows = [];      // transaction_type = 0
                            $childDiscounts = [];   // transaction_type = 2, parent_internal_reference DOLU
                            $generalDiscounts = []; // transaction_type = 2, parent_internal_reference BOŞ

                            foreach ($rows as $row) {
                                if ((int)$row['transaction_type'] === 0) {
                                    $productRows[] = $row;
                                } elseif ((int)$row['transaction_type'] === 2 && !empty($row['parent_internal_reference'])) {
                                    $childDiscounts[$row['parent_internal_reference']][] = $row;
                                } elseif ((int)$row['transaction_type'] === 2 && empty($row['parent_internal_reference'])) {
                                    $generalDiscounts[] = $row;
                                }
                            }

                            // 2) Ürünleri kendi id'sine göre sırala (güvenlik için)
                            usort($productRows, fn($a, $b) => $a['id'] <=> $b['id']);

                            foreach ($productRows as $fihrist) {
                                // --- ÜRÜN SATIRI ---
                                $isDiscount = false;
                                $miktar = $fihrist['miktar'];
                                $listeFiyati = $fihrist["liste"];
                                $iskonto = $fihrist["iskonto"];
                                $birim = $fihrist["birim"];
                                $netUnitPrice = $listeFiyati * (100 - $iskonto) / 100;
                                $rowTotal = $miktar * $netUnitPrice;
                                $currencySymbol = $fihrist["doviz"] === 'TL' ? "₺" : ($fihrist["doviz"] === 'USD' ? "$" : "€");
                            ?>
                                <tr data-row-id="<?= $fihrist['id'] ?>" data-type="0"
                                    data-internal-ref="<?= $fihrist['internal_reference'] ?>">
                                    <!-- Aktarım Durumu -->
                                    <td style="padding: 0; text-align: center;">
                                        <?php if (!empty($fihrist['internal_reference'])): ?>
                                            <span style="background: #28a745; color: white; padding: 1px 4px; border-radius: 3px; font-size: 8px;">✓</span>
                                        <?php else: ?>
                                            <span style="background: #6c757d; color: white; padding: 1px 4px; border-radius: 3px; font-size: 8px;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <!-- Tür -->
                                    <td style="padding: 0; text-align: center;"><span style="background: #0d6efd; color: white; padding: 1px 6px; border-radius: 3px; font-size: 9px;">M</span></td>
                                    <!-- Stok Kodu -->
                                    <td style="padding: 0;">
                                        <input type="text" value="<?= htmlspecialchars($fihrist["kod"]) ?>" readonly
                                            style="width: 100%; border: 1px solid #ccc; padding: 2px 4px; height: 22px; font-size: 11px; background: #f9f9f9;">
                                    </td>
                                    <!-- Stok Adı -->
                                    <td style="padding: 0;">
                                        <input type="text" value="<?= htmlspecialchars($fihrist["adi"]) ?>" readonly
                                            style="width: 100%; border: 1px solid #ccc; padding: 2px 4px; height: 22px; font-size: 11px; background: #f9f9f9;">
                                    </td>
                                    <!-- Miktar -->
                                    <td style="padding: 0;">
                                        <input type="number" class="form-control qty-input"
                                            value="<?= htmlspecialchars($miktar) ?>" min="0"
                                            data-row-id="<?= $fihrist['id'] ?>"
                                            style="padding: 2px 4px; font-size: 11px; width: 100%; height: 22px; border: 1px solid #ccc; text-align: center;">
                                    </td>
                                    <!-- İskonto (%) -->
                                    <td style="padding: 0;">
                                        <input type="number" class="form-control discount-input"
                                            value="<?= htmlspecialchars($iskonto) ?>" min="0" max="100"
                                            data-row-id="<?= $fihrist['id'] ?>"
                                            style="padding: 2px 4px; font-size: 11px; width: 100%; height: 22px; border: 1px solid #ccc; text-align: center;">
                                    </td>
                                    <!-- İskonto Formülü (kademeli iskonto için: 50-5-10) -->
                                    <td style="padding: 0;">
                                        <input type="text" class="form-control iskonto-formula-input"
                                            value="<?= htmlspecialchars($fihrist['iskonto_formulu'] ?? '') ?>" 
                                            data-row-id="<?= $fihrist['id'] ?>"
                                            placeholder="50-5-10"
                                            style="padding: 2px 4px; font-size: 10px; width: 100%; height: 22px; border: 1px solid #ccc; text-align: center;">
                                    </td>
                                    <!-- İskontolu Birim Fiyat -->
                                    <td style="padding: 0; text-align: right;">
                                        <input type="text" class="form-control net-price-display" readonly
                                            value="<?= number_format($netUnitPrice, 2, ',', '.') ?>"
                                            data-row-id="<?= $fihrist['id'] ?>"
                                            data-list-price="<?= htmlspecialchars($listeFiyati) ?>"
                                            style="padding: 2px 4px; font-size: 11px; width: 100%; height: 22px; border: 1px solid #ccc; text-align: right; background: #f9f9f9;">
                                        <input type="hidden" name="net_price_unit[<?= $fihrist['id'] ?>]"
                                            class="final-price-hidden"
                                            value="<?= number_format($netUnitPrice, 2, '.', '') ?>">
                                    </td>
                                    <!-- İskontolu Toplam -->
                                    <td style="padding: 0; text-align: right;">
                                        <input type="text" class="total-price-display" readonly
                                            value="<?= number_format($rowTotal, 2, ',', '.') ?>"
                                            style="padding: 2px 4px; font-size: 11px; width: 100%; height: 22px; border: 1px solid #ccc; text-align: right; font-weight: bold; background: #f9f9f9;">
                                        <span class="total-price" data-row-id="<?= $fihrist['id'] ?>" style="display: none;"><?= number_format($rowTotal, 2, ',', '.') ?></span>
                                    </td>
                                    <!-- Birim -->
                                    <td style="padding: 0; text-align: center;">
                                        <input type="text" class="form-control unit-input"
                                            value="<?= htmlspecialchars($birim) ?>" data-row-id="<?= $fihrist['id'] ?>"
                                            style="padding: 2px 4px; font-size: 10px; width: 100%; height: 22px; border: 1px solid #ccc; text-align: center;">
                                    </td>
                                    <!-- KDV (%) -->
                                    <td style="padding: 0; text-align: center;">
                                        <span style="font-size: 11px;">20</span>
                                    </td>
                                    <!-- Liste Fiyatı -->
                                    <td style="padding: 0; text-align: right;">
                                        <span style="font-size: 11px; padding-right: 4px;"><?= number_format($listeFiyati, 2, ',', '.') ?> <?= $currencySymbol ?></span>
                                        <input type="hidden" class="list-price"
                                            value="<?= htmlspecialchars($listeFiyati) ?>"
                                            data-row-id="<?= $fihrist['id'] ?>"
                                            data-currency="<?= htmlspecialchars($fihrist["doviz"]) ?>">
                                    </td>
                                    <!-- İşlem -->
                                    <td style="padding: 0; text-align: center;">
                                        <button type="button" class="btn btn-danger btn-sm remove-btn" 
                                            data-id="<?= $fihrist['id'] ?>"
                                            onclick="if (!confirm('Bu kalemi kaldırmak istiyor musunuz?')) return false; window.location.href='teklifsiparisler-duzenle.php?te=<?= $teklifid ?>&islem=sildir&im=<?= $fihrist['id'] ?>';"
                                            style="padding: 1px 6px; font-size: 10px; height: 20px; line-height: 18px;">Kaldır</button>
                                    </td>
                                </tr>
                                <?php
                                // --- O ÜRÜNE AİT EK İSKONTO SATIRLARI ---
                                $internalRef = $fihrist['internal_reference'];
                                if (!empty($childDiscounts[$internalRef])) {
                                    // Ek indirim satırlarını ID'ye göre sırala (isteğe bağlı)
                                    usort($childDiscounts[$internalRef], fn($a, $b) => $a['id'] <=> $b['id']);
                                    foreach ($childDiscounts[$internalRef] as $child) {
                                        // Parent ürün kod ve adı
                                        $parentCode = $fihrist['kod'];
                                        $parentName = $fihrist['adi'];
                                        $isDiscount = true;
                                        $miktar = $child['miktar'];
                                        $listeFiyati = $child["liste"];
                                        $iskonto = $child["iskonto"];
                                        $birim = $child["birim"];
                                        $netUnitPrice = $listeFiyati * (100 - $iskonto) / 100;
                                        $rowTotal = $miktar * $netUnitPrice;
                                        $currencySymbol = $child["doviz"] === 'TL' ? "₺" : ($child["doviz"] === 'USD' ? "$" : "€");
                                ?>
                                        <tr data-row-id="<?= $child['id'] ?>" data-type="2"
                                            data-internal-ref="<?= $child['internal_reference'] ?>"
                                            data-parent-internal-ref="<?= htmlspecialchars($child['parent_internal_reference']) ?>"
                                            style="background: #fffbf0;">
                                            <!-- Aktarım -->
                                            <td style="padding: 0; text-align: center;">
                                                <?php if (!empty($child['internal_reference'])): ?>
                                                    <span style="background: #28a745; color: white; padding: 1px 4px; border-radius: 3px; font-size: 8px;">✓</span>
                                                <?php else: ?>
                                                    <span style="background: #6c757d; color: white; padding: 1px 4px; border-radius: 3px; font-size: 8px;">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <!-- Tür -->
                                            <td style="padding: 0; text-align: center;"><span style="background: #ffc107; color: #000; padding: 1px 6px; border-radius: 3px; font-size: 9px;">İSK</span></td>
                                            <!-- Stok Kodu -->
                                            <td style="padding: 0;"><span style="font-size: 10px; color: #999; padding-left: 12px;">↳ <?= htmlspecialchars($child["kod"]) ?></span></td>
                                            <!-- Stok Adı -->
                                            <td style="padding: 0;"><span style="font-size: 10px; color: #666; font-style: italic; padding-left: 4px;"><?= htmlspecialchars($child["adi"]) ?></span></td>
                                            <!-- Miktar -->
                                            <td style="padding: 0; text-align: center;"><span style="font-size: 10px; color: #999;">-</span></td>
                                            <!-- İskonto (%) -->
                                            <td style="padding: 0;">
                                                <input type="number" class="form-control discount-input"
                                                    value="<?= htmlspecialchars($iskonto) ?>" min="0" max="100"
                                                    data-row-id="<?= $child['id'] ?>"
                                                    style="padding: 2px 4px; font-size: 11px; width: 100%; height: 22px; border: 1px solid #ccc; text-align: center; background: #fffbf0;">
                                            </td>
                                            <!-- İskontolu Birim Fiyat -->
                                            <td style="padding: 0; text-align: right;">
                                                <span style="font-size: 11px; color: #f59e0b; font-weight: 600; padding-right: 4px;"><?= number_format($netUnitPrice, 2, ',', '.') ?> <?= $currencySymbol ?></span>
                                                <input type="hidden" name="net_price_unit[<?= $child['id'] ?>]"
                                                    class="final-price-hidden"
                                                    value="<?= number_format($netUnitPrice, 2, '.', '') ?>">
                                            </td>
                                            <!-- İskontolu Toplam -->
                                            <td style="padding: 0; text-align: right;">
                                                <span class="total-price" data-row-id="<?= $child['id'] ?>" style="font-size: 11px; color: #f59e0b; font-weight: 600; padding-right: 4px;">
                                                    <?= number_format($rowTotal, 2, ',', '.') ?> <?= $currencySymbol ?>
                                                </span>
                                            </td>
                                            <!-- Birim -->
                                            <td style="padding: 0; text-align: center;"><span style="font-size: 10px; color: #999;">-</span></td>
                                            <!-- KDV (%) -->
                                            <td style="padding: 0; text-align: center;"><span style="font-size: 10px; color: #999;">-</span></td>
                                            <!-- Liste Fiyatı -->
                                            <td style="padding: 0; text-align: right;"><span style="font-size: 10px; color: #999;">-</span></td>
                                            <!-- İşlem -->
                                            <td style="padding: 0; text-align: center;">
                                                <button type="button" class="btn btn-danger btn-sm remove-btn"
                                                    data-id="<?= $child['id'] ?>"
                                                    onclick="if (!confirm('Bu kalemi kaldırmak istiyor musunuz?')) return false; window.location.href='teklifsiparisler-duzenle.php?te=<?= $teklifid ?>&islem=sildir&im=<?= $child['id'] ?>';"
                                                    style="padding: 1px 6px; font-size: 10px; height: 20px; line-height: 18px;">Kaldır</button>
                                            </td>
                                        </tr>
                                <?php
                                    }
                                }
                            }

                            // --- EN ALTA GENEL İSKONTO SATIRLARI EKLE ---
                            foreach ($generalDiscounts as $fihrist) {
                                $isDiscount = true;
                                $miktar = $fihrist['miktar'];
                                $listeFiyati = $fihrist["liste"];
                                $iskonto = $fihrist["iskonto"];
                                $birim = $fihrist["birim"];
                                $netUnitPrice = $listeFiyati * (100 - $iskonto) / 100;
                                $rowTotal = $miktar * $netUnitPrice;
                                $currencySymbol = $fihrist["doviz"] === 'TL' ? "₺" : ($fihrist["doviz"] === 'USD' ? "$" : "€");
                                ?>
                                <tr data-row-id="<?= $fihrist['id'] ?>" data-type="2"
                                    data-internal-ref="<?= $fihrist['internal_reference'] ?>"
                                    style="background: #fff8e1;">
                                    <!-- Aktarım -->
                                    <td style="padding: 0; text-align: center;">
                                        <?php if (!empty($fihrist['internal_reference'])): ?>
                                            <span style="background: #28a745; color: white; padding: 1px 4px; border-radius: 3px; font-size: 8px;">✓</span>
                                        <?php else: ?>
                                            <span style="background: #6c757d; color: white; padding: 1px 4px; border-radius: 3px; font-size: 8px;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <!-- Tür -->
                                    <td style="padding: 0; text-align: center;"><span style="background: #ff9800; color: white; padding: 1px 6px; border-radius: 3px; font-size: 9px;">GEN</span></td>
                                    <!-- Stok Kodu -->
                                    <td style="padding: 0;"><span style="font-size: 10px; color: #999; padding-left: 4px;"><?= htmlspecialchars($fihrist["kod"]) ?></span></td>
                                    <!-- Stok Adı -->
                                    <td style="padding: 0;"><span style="font-size: 10px; color: #666; font-weight: 600; padding-left: 4px;"><?= htmlspecialchars($fihrist["adi"]) ?></span></td>
                                    <!-- Miktar -->
                                    <td style="padding: 0; text-align: center;"><span style="font-size: 10px; color: #999;">-</span></td>
                                    <!-- İskonto (%) -->
                                    <td style="padding: 0;">
                                        <input type="number" class="form-control discount-input"
                                            value="<?= htmlspecialchars($iskonto) ?>" min="0" max="100"
                                            data-row-id="<?= $fihrist['id'] ?>"
                                            style="padding: 2px 4px; font-size: 11px; width: 100%; height: 22px; border: 1px solid #ccc; text-align: center; background: #fff8e1;">
                                    </td>
                                    <!-- İskontolu Birim Fiyat -->
                                    <td style="padding: 0; text-align: right;">
                                        <span style="font-size: 11px; color: #ff9800; font-weight: 600; padding-right: 4px;"><?= number_format($netUnitPrice, 2, ',', '.') ?> <?= $currencySymbol ?></span>
                                        <input type="hidden" name="net_price_unit[<?= $fihrist['id'] ?>]"
                                            class="final-price-hidden"
                                            value="<?= number_format($netUnitPrice, 2, '.', '') ?>">
                                    </td>
                                    <!-- İskontolu Toplam -->
                                    <td style="padding: 0; text-align: right;">
                                        <span class="total-price" data-row-id="<?= $fihrist['id'] ?>" style="font-size: 11px; color: #ff9800; font-weight: 600; padding-right: 4px;">
                                            <?= number_format($rowTotal, 2, ',', '.') ?> <?= $currencySymbol ?>
                                        </span>
                                    </td>
                                    <!-- Birim -->
                                    <td style="padding: 0; text-align: center;"><span style="font-size: 10px; color: #999;">-</span></td>
                                    <!-- KDV (%) -->
                                    <td style="padding: 0; text-align: center;"><span style="font-size: 10px; color: #999;">-</span></td>
                                    <!-- Liste Fiyatı -->
                                    <td style="padding: 0; text-align: right;"><span style="font-size: 10px; color: #999;">-</span></td>
                                    <!-- İşlem -->
                                    <td style="padding: 0; text-align: center;">
                                        <button type="button" class="btn btn-danger btn-sm remove-btn"
                                            data-id="<?= $fihrist['id'] ?>"
                                            onclick="if (!confirm('Bu kalemi kaldırmak istiyor musunuz?')) return false; window.location.href='teklifsiparisler-duzenle.php?te=<?= $teklifid ?>&islem=sildir&im=<?= $fihrist['id'] ?>';"
                                            style="padding: 1px 6px; font-size: 10px; height: 20px; line-height: 18px;">Kaldır</button>
                                    </td>
                                </tr>
                            <?php
                            }
                            ?>
                        </tbody>





                    </table>
                        </div>
                    </div>
                </div>

                <?php
                $sql = "SELECT dolarkur,eurokur,kurtarih FROM ogteklif2 WHERE id=?";
                $stmt = $db->prepare($sql);
                $stmt->bind_param("i", $teklifid);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                $dolarkuru = (float) str_replace(',', '.', $row['dolarkur'] ?? '0');
                $eurokuru = (float) str_replace(',', '.', $row['eurokur'] ?? '0');
                $kurtarihi = $row['kurtarih'] ?? '';
                $kdvOrani = 0.20;
                ?>

                <input type="hidden" id="euroRate" value="<?= $eurokuru ?>">
                <input type="hidden" id="dollarRate" value="<?= $dolarkuru ?>">

                <!-- Detaylı Özet Tablosu - ERP Görünümü -->
                <div class="card mb-3 mt-3" style="border: 1px solid #dee2e6; border-radius: 6px;">
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
                                    <td id="summary-brut-TL" style="padding: 6px 8px; text-align: right; font-weight: 600;"><?= number_format(0, 2, ',', '.') ?> TL</td>
                                    <td id="summary-brut-EUR" style="padding: 6px 8px; text-align: right; font-weight: 600;"><?= number_format(0, 2, ',', '.') ?> €</td>
                                    <td id="summary-brut-USD" style="padding: 6px 8px; text-align: right; font-weight: 600;"><?= number_format(0, 2, ',', '.') ?> $</td>
                                </tr>
                                <tr style="background: #fff3cd;">
                                    <td style="padding: 6px 8px;">İndirim Tutarı</td>
                                    <td id="summary-disc-TL" style="padding: 6px 8px; text-align: right; color: #dc3545; font-weight: 600;">- <?= number_format(0, 2, ',', '.') ?> TL</td>
                                    <td id="summary-disc-EUR" style="padding: 6px 8px; text-align: right; color: #dc3545; font-weight: 600;">- <?= number_format(0, 2, ',', '.') ?> €</td>
                                    <td id="summary-disc-USD" style="padding: 6px 8px; text-align: right; color: #dc3545; font-weight: 600;">- <?= number_format(0, 2, ',', '.') ?> $</td>
                                </tr>
                                <tr>
                                    <td style="padding: 6px 8px;">Net Toplam (KDV Hariç)</td>
                                    <td id="summary-net-TL" style="padding: 6px 8px; text-align: right; font-weight: 600;"><?= number_format(0, 2, ',', '.') ?> TL</td>
                                    <td id="summary-net-EUR" style="padding: 6px 8px; text-align: right; font-weight: 600;"><?= number_format(0, 2, ',', '.') ?> €</td>
                                    <td id="summary-net-USD" style="padding: 6px 8px; text-align: right; font-weight: 600;"><?= number_format(0, 2, ',', '.') ?> $</td>
                                </tr>
                                <tr style="background: #e7f3ff;">
                                    <td style="padding: 6px 8px; font-weight: 600;">Genel Toplam (KDV Hariç)</td>
                                    <td colspan="3" id="summary-net-EUR2" style="padding: 6px 8px; text-align: right; font-weight: 700; color: #0d6efd;"><?= number_format(0, 2, ',', '.') ?> €</td>
                                </tr>
                                <tr>
                                    <td style="padding: 6px 8px;">KDV (<?= (int) ($kdvOrani * 100) ?>%)</td>
                                    <td colspan="3" id="summary-kdv-EUR" style="padding: 6px 8px; text-align: right; font-weight: 600;"><?= number_format(0, 2, ',', '.') ?> €</td>
                                </tr>
                                <tr style="background: #d4edda; border-top: 2px solid #28a745;">
                                    <td style="padding: 8px; font-weight: 700; font-size: 12px;">Genel Toplam (KDV Dahil)</td>
                                    <td colspan="3" id="summary-grand-EUR" style="padding: 8px; text-align: right; font-weight: 700; font-size: 12px; color: #28a745;"><?= number_format(0, 2, ',', '.') ?> €</td>
                                </tr>
                            </tbody>
                        </table>
                        <div style="text-align: center; margin-top: 8px; padding-top: 8px; border-top: 1px solid #dee2e6;">
                            <small style="font-size: 10px; color: #666;">
                                <?php if ($kurtarihi): ?>
                                    <?= htmlspecialchars($kurtarihi) ?> tarihli <strong>Garanti BBVA</strong> kurları dikkate alınmıştır.
                                <?php else: ?>
                                    <?= date('d.m.Y H:i') ?> tarihli <strong>Garanti BBVA</strong> kurları dikkate alınmıştır.
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                </div>



                <div id="status-container">
                <?php
                // Durum değişkenini tanımla
                $currentStatus = trim($teklif['durum'] ?? '');
                
                // Yönetici kontrolü eklenebilir: && $userType === 'Yönetici'
                if ($currentStatus === 'Yönetici Onayı Bekleniyor'): ?>
                <!-- Admin Approval Action Buttons -->
                <div class="card mb-3" style="border: 1px solid #ffc107; background-color: #fffbf0;">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 text-warning"><i class="bi bi-exclamation-triangle-fill"></i> Yönetici Onayı Bekleniyor</h5>
                            <small class="text-muted">Bu teklif özel fiyat/şartlar içerdiği için onaylanmadan iletilemez.</small>
                        </div>
                        <div class="d-flex gap-2">
                             <form method="post" style="display:inline-block;" onsubmit="return confirm('Bu teklifi ONAYLAMAK istediğinize emin misiniz?');">
                                 <button type="submit" name="approveOffer" class="btn btn-success btn-lg">
                                     <i class="bi bi-check-lg"></i> ONAYLA
                                 </button>
                             </form>
                             <form method="post" style="display:inline-block;" onsubmit="return confirm('Bu teklifi REDDETMEK istediğinize emin misiniz?');">
                                 <button type="submit" name="rejectOffer" class="btn btn-danger btn-lg">
                                     <i class="bi bi-x-lg"></i> REDDET
                                 </button>
                             </form>
                        </div>
                    </div>
                </div>
                <?php elseif ($currentStatus === 'Yönetici Onayladı / Gönderilecek'): ?>
                 <div class="card mb-3" style="border: 1px solid #198754; background-color: #d1e7dd;">
                    <div class="card-body">
                         <h5 class="mb-1 text-success"><i class="bi bi-check-circle-fill"></i> ONAYLANDI</h5>
                         <div>Bu teklif yönetici tarafından onaylanmıştır. Artık bayi veya müşteriye iletilebilir.</div>
                    </div>
                </div>
                <?php elseif ($currentStatus === 'Yönetici Tarafından Red'): ?>
                 <div class="card mb-3" style="border: 1px solid #dc3545; background-color: #f8d7da;">
                    <div class="card-body">
                         <h5 class="mb-1 text-danger"><i class="bi bi-x-circle-fill"></i> REDDEDİLDİ</h5>
                         <div>Bu teklif yönetici tarafından reddedilmiştir. Revize edilmesi gerekmektedir.</div>
                    </div>
                </div>
                <?php endif; ?>
                </div>

                <?php
                // Tam geçmişi ve en son talebi al
                $history = $revisionService->getHistory($teklifid);
                $pending = array_filter(
                    $history,
                    fn($h) => $h['yeni_durum'] === 'Teklife Revize Talep Edildi / İnceleme Bekliyor'
                );
                $lastReq = $pending ? reset($pending) : null;
                $reqUser = '';
                if ($lastReq && !empty($lastReq['degistiren_personel_id'])) {
                    $mgrProfile = $dbManager->getManagerProfile((int) $lastReq['degistiren_personel_id']);
                    if ($mgrProfile && !empty($mgrProfile['adsoyad'])) {
                        $reqUser = $mgrProfile['adsoyad'];
                    } else {
                        $b2bUser = $dbManager->getB2bUserById((int) $lastReq['degistiren_personel_id']);
                        if ($b2bUser && !empty($b2bUser['username'])) {
                            $reqUser = $b2bUser['username'];
                        } else {
                            $reqUser = 'Bilinmiyor';
                        }
                    }
                }
                ?>

                <div class="accordion mb-4" id="revizeAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingRevize">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapseRevize" aria-expanded="false" aria-controls="collapseRevize">
                                Revize Talebi ve Geçmişi
                            </button>
                        </h2>
                        <div id="collapseRevize" class="accordion-collapse collapse" aria-labelledby="headingRevize"
                            data-bs-parent="#revizeAccordion">
                            <div class="accordion-body">

                                <?php if ($lastReq): ?>
                                    <div class="mb-4">
                                        <h5 class="fw-bold mb-3">Gelen Revize Talebi</h5>
                                        <p class="mb-2"><strong>Tarih:</strong>
                                            <?= htmlspecialchars($lastReq['degistirme_tarihi']) ?></p>
                                        <p class="mb-2"><strong>Talep Eden:</strong>
                                            <?= htmlspecialchars($reqUser) ?></p>
                                        <p class="mb-2"><strong>Notu:</strong><br>
                                            <?= nl2br(htmlspecialchars($lastReq['notlar'])) ?></p>
                                        <form method="post" class="mb-4">
                                            <input type="hidden" name="action" value="respondRevize">
                                            <div class="mb-3">
                                                <label for="sellerNotes" class="form-label">Yanıt Notu</label>
                                                <textarea id="sellerNotes" name="notes" class="form-control" rows="3"
                                                    placeholder="Talebinize yanıtınızı yazın…"></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-primary">
                                                Yanıtı Gönder
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>

                                <h5 class="fw-bold mb-3">Revize Geçmişi</h5>
                                <?php if (empty($history)): ?>
                                    <p class="text-muted">Henüz geçmiş kaydı yok.</p>
                                <?php else: ?>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($history as $h):
                                            $actor = 'Bilinmiyor';
                                            if (!empty($h['degistiren_personel_id'])) {
                                                $mgrProfile = $dbManager->getManagerProfile((int) $h['degistiren_personel_id']);
                                                if ($mgrProfile && !empty($mgrProfile['adsoyad'])) {
                                                    $actor = $mgrProfile['adsoyad'];
                                                } else {
                                                    $b2bUser = $dbManager->getB2bUserById((int) $h['degistiren_personel_id']);
                                                    if ($b2bUser && !empty($b2bUser['username'])) {
                                                        $actor = $b2bUser['username'];
                                                    }
                                                }
                                            }
                                        ?>
                                            <li class="list-group-item py-3">
                                                <small class="text-secondary d-block mb-1">
                                                    <?= htmlspecialchars($h['degistirme_tarihi']) ?>
                                                </small>
                                                <div>
                                                    <strong><?= htmlspecialchars($actor) ?>:</strong>
                                                    <?= htmlspecialchars($h['eski_durum']) ?>
                                                    &rarr;
                                                    <?= htmlspecialchars($h['yeni_durum']) ?>
                                                </div>
                                                <?php if (!empty($h['notlar'])): ?>
                                                    <div class="mt-2">
                                                        <em><?= nl2br(htmlspecialchars($h['notlar'])) ?></em>
                                                    </div>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- Revizyon bölümünün ardından sözleşme ve logo ayarlarını içeren form -->
                <form id="logoUpdateForm" method="post" action="teklifsiparisler-duzenle.php?te=<?= $teklifid ?>" autocomplete="off">
                    <input type="hidden" name="teklifid" value="<?= htmlspecialchars($teklifid) ?>">
                    <input type="hidden" id="internal_reference" name="internal_reference" value="<?= htmlspecialchars($teklif['internal_reference'] ?? '') ?>">

                    <!-- Sözleşme Kartı -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-soft-secondary bg-gradient text-white py-3">
                            <h5 class="mb-0">Sözleşme</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="sozlesme_id" class="form-label">Sözleşme</label>
                                    <select name="sozlesme_id" id="sozlesme_id" class="form-select">
                                        <?php foreach ($contracts as $c): ?>
                                            <option value="<?= $c['sozlesme_id'] ?>" <?= $c['sozlesme_id'] == ($teklif['sozlesme_id'] ?? 5) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($c['sozlesmeadi']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="previewSozBtn">Önizle</button>
                                    <a href="#" id="editSozBtn" target="_blank" class="btn btn-sm btn-outline-secondary mt-2 ms-1">Güncelle</a>
                                    <a href="sozlesme_duzenle.php" target="_blank" class="btn btn-sm btn-outline-primary mt-2 ms-1">Yeni</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Logo Transfer Bilgileri Formu -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-soft-secondary bg-gradient text-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Logo Transfer Bilgileri</h5>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge rounded-pill bg-light text-dark">
                                <?= htmlspecialchars($teklif['logo_transfer_status'] ?? 'Beklemede') ?>
                            </span>
                            <span class="small ms-1 text-black opacity-75">
                                Son: <?= htmlspecialchars($teklif['last_logo_update_date'] ?? '—') ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <!-- Sipariş Referansları Paneli ve Farkları Göster Butonu -->
                            <div class="col-lg-2 mb-2 mb-lg-0">
                                <div class="p-2 bg-light rounded-3 border h-100 d-flex flex-column justify-content-between"
                                    style="min-width:140px;">
                                    <div>
                                        <div class="fw-semibold mb-1 small">Sipariş Referansları</div>
                                        <div class="d-flex align-items-center gap-1" style="font-size:13px;">
                                            <span class="text-muted">Ref:</span>
                                            <span
                                                class="fw-semibold text-dark"><?= htmlspecialchars($teklif['internal_reference'] ?? '-') ?></span>
                                        </div>
                                        <div class="small text-secondary" style="font-size:11px;">
                                            Logo aktarım sonrası otomatik oluşur.
                                        </div>
                                    </div>
                                    <?php if (!empty($teklif['internal_reference'])): ?>
                                        <button id="btnCompareHeader" type="button" class="btn btn-warning fw-semibold mt-2"
                                            style="font-size:0.94rem; height: 54px; padding: 2px 8px;"
                                            title="Logo ve veritabanı başlık bilgilerindeki farkları karşılaştırır.">
                                            <i class="bi bi-list-columns"></i>
                                            Başlık Farklarını Göster
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Form Alanı -->
                            <div class="col-lg-10">
                                    <!-- Logo Ayarları -->
                                    <div class="mb-3 border-bottom pb-2">
                                        <div class="fw-bold mb-2 text-primary">Logo Ayarları</div>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="vatexcept_code" class="form-label">KDV Muafiyet
                                                    Kodu</label>
                                                <select name="vatexcept_code" id="vatexcept_code" class="form-select">
                                                    <option value="">Muafiyet Yok</option>
                                                    <option value="301" <?= (($teklif['vatexcept_code'] ?? '') == '301' ? 'selected' : '') ?>>301</option>
                                                    <option value="other" <?= (($teklif['vatexcept_code'] ?? '') && $teklif['vatexcept_code'] != '301' ? 'selected' : '') ?>>
                                                        Diğer</option>
                                                </select>
                                                <input type="text" name="vatexcept_code_other" id="vatexcept_code_other"
                                                    class="form-control mt-2"
                                                    style="<?= (($teklif['vatexcept_code'] ?? '') && $teklif['vatexcept_code'] != '301') ? '' : 'display:none;' ?>"
                                                    placeholder="Muafiyet kodunu giriniz"
                                                    value="<?= ($teklif['vatexcept_code'] && $teklif['vatexcept_code'] != '301') ? htmlspecialchars($teklif['vatexcept_code']) : '' ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="vatexcept_reason" class="form-label">KDV Muafiyet
                                                    Sebebi</label>
                                                <select name="vatexcept_reason" id="vatexcept_reason"
                                                    class="form-select">
                                                    <option value="">Muafiyet Yok</option>
                                                    <option value="11/1-a Mal İhracatı" <?= (($teklif['vatexcept_reason'] ?? '') == '11/1-a Mal İhracatı' ? 'selected' : '') ?>>11/1-a Mal
                                                        İhracatı</option>
                                                    <option value="other" <?= (($teklif['vatexcept_reason'] ?? '') && $teklif['vatexcept_reason'] != '11/1-a Mal İhracatı' ? 'selected' : '') ?>>Diğer</option>
                                                </select>
                                                <input type="text" name="vatexcept_reason_other"
                                                    id="vatexcept_reason_other" class="form-control mt-2"
                                                    style="<?= (($teklif['vatexcept_reason'] ?? '') && $teklif['vatexcept_reason'] != '11/1-a Mal İhracatı') ? '' : 'display:none;' ?>"
                                                    placeholder="Muafiyet sebebini giriniz"
                                                    value="<?= ($teklif['vatexcept_reason'] && $teklif['vatexcept_reason'] != '11/1-a Mal İhracatı') ? htmlspecialchars($teklif['vatexcept_reason']) : '' ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Sipariş Detayı -->
                                    <div class="mb-3 border-bottom pb-2">
                                        <div class="fw-bold mb-2 text-primary">Sipariş Detayı</div>
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label for="auxil_code" class="form-label">Özel Kod</label>
                                                <select name="auxil_code" id="auxil_code" class="form-select">
                                                    <option value="">— Seçiniz —</option>
                                                    <?php foreach ($specodes as $s): ?>
                                                        <option value="<?= htmlspecialchars($s['SPECODE'], ENT_QUOTES) ?>"
                                                            <?= ($s['SPECODE'] === ($teklif['auxil_code'] ?? '') ? 'selected' : '') ?>>
                                                            <?= htmlspecialchars($s['SPECODE'] . ' — ' . $s['DEFINITION_']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="auth_code" class="form-label">Yetki Kodu</label>
                                                <input type="text" name="auth_code" id="auth_code" class="form-control"
                                                    value="<?= htmlspecialchars($teklif['auth_code'] ?? '') ?>"
                                                    placeholder="Yetki kodu giriniz">
                                            </div>
                                            <div class="col-md-4">
                                                <label for="order_note" class="form-label">Sipariş Notu</label>
                                                <textarea name="order_note" id="order_note" class="form-control"
                                                    rows="1"
                                                    placeholder="Not giriniz"><?= htmlspecialchars($teklif['notes1'] ?? '') ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Operasyonel Alanlar -->
                                    <div class="mb-3">
                                        <div class="fw-bold mb-2 text-primary">Operasyonel Seçimler</div>
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <label for="division" class="form-label">İşyeri</label>
                                                <select name="division" id="division" class="form-select">
                                                    <?php foreach ($divisions as $d): ?>
                                                        <option value="<?= $d['NR'] ?>" <?= ($d['NR'] == $teklif['division'] ? 'selected' : '') ?>>
                                                            <?= htmlspecialchars($d['NR'] . ' - ' . $d['NAME'], ENT_QUOTES, 'UTF-8') ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="department" class="form-label">Bölüm</label>
                                                <select name="department" id="department" class="form-select">
                                                    <?php foreach ($departments as $d): ?>
                                                        <option value="<?= $d['NR'] ?>" <?= ($d['NR'] == $teklif['department'] ? 'selected' : '') ?>>
                                                            <?= htmlspecialchars($d['NR'] . ' - ' . $d['NAME'], ENT_QUOTES, 'UTF-8') ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="source_wh" class="form-label">Ambar</label>
                                                <select name="source_wh_logicalref" id="source_wh" class="form-select">
                                                    <?php foreach ($warehouses as $w): ?>
                                                        <option value="<?= $w['NR'] ?>" data-costgrp="<?= $w['COSTGRP'] ?>"
                                                            <?= $w['NR'] == $teklif['source_wh'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($w['NR'] . ' - ' . $w['NAME'], ENT_QUOTES, 'UTF-8') ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="factory" class="form-label">Fabrika</label>
                                                <select name="factory" id="factory" class="form-select">
                                                    <?php $seen = [];
                                                    foreach ($factories as $f):
                                                        if (in_array($f['NAME'], $seen))
                                                            continue;
                                                        $seen[] = $f['NAME']; ?>
                                                        <option value="<?= htmlspecialchars($f['NR'], ENT_QUOTES) ?>"
                                                            <?= ($f['NR'] === $teklif['factory'] ? ' selected' : '') ?>>
                                                            <?= htmlspecialchars($f['NR'] . ' - ' . $f['NAME'], ENT_QUOTES, 'UTF-8') ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row g-3 mt-1">
                                            <div class="col-md-3">
                                                <label for="salesmanref" class="form-label">Satış Elemanı</label>
                                                <select name="salesmanref" id="salesmanref" class="form-select">
                                                    <?php foreach ($salesmen as $s): ?>
                                                        <option
                                                            value="<?= htmlspecialchars($s['LOGICALREF'], ENT_QUOTES) ?>"
                                                            data-code="<?= htmlspecialchars($s['CODE'], ENT_QUOTES) ?>"
                                                            <?= ($s['LOGICALREF'] == $teklif['salesmanref'] ? 'selected' : '') ?>>
                                                            <?= htmlspecialchars("{$s['DEFINITION_']} ({$s['CODE']})") ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="trading_grp" class="form-label">Ticari Grup</label>
                                                <select name="trading_grp" id="trading_grp" class="form-select">
                                                    <?php foreach ($tradeGroups as $g): ?>
                                                        <option value="<?= $g['GCODE'] ?>"
                                                            <?= ($g['GCODE'] == $teklif['trading_grp'] ? 'selected' : '') ?>>
                                                            <?= htmlspecialchars("{$g['GCODE']} - {$g['GDEF']}") ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="paydefref" class="form-label">Tahsilat Planı</label>
                                                <select name="paydefref" id="paydefref" class="form-select">
                                                    <?php foreach ($payPlans as $p): ?>
                                                        <option value="<?= $p['LOGICALREF'] ?>"
                                                            data-code="<?= htmlspecialchars($p['CODE'], ENT_QUOTES) ?>"
                                                            data-definition="<?= htmlspecialchars($p['DEFINITION_'], ENT_QUOTES) ?>"
                                                            <?= $p['LOGICALREF'] == $teklif['paydefref'] ? 'selected' : '' ?>>
                                                            <?= "{$p['CODE']} - {$p['DEFINITION_']}" ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="doc_number" class="form-label">Belge No</label>
                                                <input type="text" name="doc_number" id="doc_number"
                                                    class="form-control"
                                                    value="<?= htmlspecialchars($teklif['doc_number'] ?? '') ?>"
                                                    placeholder="Belge numarasını giriniz">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="order_status" class="form-label">Sipariş Durumu</label>
                                        <select name="order_status" id="order_status" class="form-select">
                                            <option value="1" <?= $orderStatusCurrent === 1 ? 'selected' : '' ?>>Öneri</option>
                                            <option value="2" <?= $orderStatusCurrent === 2 ? 'selected' : '' ?>>Sevkedilemez</option>
                                            <option value="4" <?= $orderStatusCurrent === 4 ? 'selected' : '' ?>>Sevkedilebilir</option>
                                        </select>
                                    </div>
                                    <!-- Aksiyon Butonları -->
                                    <div class="d-flex justify-content-end align-items-center gap-3 mt-4 flex-wrap">
                                        <button type="button" id="updateLogoInfoBtn" class="btn btn-success fw-semibold"
                                            style="min-width:140px;"
                                            title="Yapılan değişiklikleri kaydeder ve veritabanına yazar.">
                                            <i class="bi bi-save"></i> Veritabanı Güncelle
                                        </button>
                                        <span id="updateStatus" class="small ms-2"></span>
                                        <span id="autoSaveStatus" class="text-muted small ms-2"></span>

                                        <button type="button" id="syncRefsBtn" class="btn btn-outline-secondary btn-sm"
                                            style="font-size:.95rem;"
                                            title="Logo’daki güncel referansları tekrar çekerek ekranı günceller.">
                                            <i class="bi bi-arrow-repeat"></i> Referansları Güncelle
                                        </button>
                                        <?php if (empty($teklif['internal_reference'])): ?>
                                            <button type="button" id="sendToLogoBtn" class="btn btn-primary fw-semibold"
                                                style="min-width:160px;" title="Teklifi/siparişi Logo’ya ilk defa aktarır.">
                                                <span id="sendToLogoBtnText"><i class="bi bi-upload"></i> Siparişi
                                                    Logoya Aktar</span>
                                                <span id="sendToLogoSpinner"
                                                    class="spinner-border spinner-border-sm ms-2 d-none" role="status"
                                                    aria-hidden="true"></span>
                                            </button>

                                        <?php endif; ?>
                                    </div>


                                    <!-- Gizli Alanlar -->
                                    <input type="hidden" name="updateLogoInfo" value="1">
                                    <input type="hidden" name="source_wh_nr" id="source_wh_nr" value="">
                                    <input type="hidden" name="source_costgrp" id="source_costgrp" value="">
                                    <input type="hidden" name="payment_code" id="payment_code" value="">
                                    <input type="hidden" name="salesman_code" id="salesman_code" value="">
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div> <!-- end .container.page-content -->
        </div> <!-- end .main-content -->
        <?php include "menuler/footer.php"; ?>
    </div> <!-- END layout-wrapper -->

    <!-- Canlı Arama Modal -->
    <div class="modal fade canliarama" role="dialog" aria-labelledby="myLargeModalLabel">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myLargeModalLabel">Arama Sonuçlarınızı Canlı Filitreleyin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <b style="font-family:arial; font-size:12px; color:red;">Fiyatlarımıza KDV Hariçtir</b><br>
                    <b style="font-family:arial; font-size:12px; color:red;">Liste fiyatları verilmiştir. İskonto
                        fiyatları için BÜLTEN VE İSKONTOLAR ALANINDAKİ İSKONTO FİYATLARINI BAZ ALARAK
                        HESAPLAYINIZ!</b><br><br>
                    <table id="examples" class="table table-responsive dt-responsive display"
                        style="width:100% !important;">
                        <thead>
                            <tr>
                                <th>Stok Kodu</th>
                                <th>Stok Adı</th>
                                <th>Birimi</th>
                                <th>Döviz</th>
                                <th>Liste Fiyatı</th>
                                <th>Marka</th>
                                <th>Stok</th>
                                <th>Seçim</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th>Stok Kodu</th>
                                <th>Stok Adı</th>
                                <th>Birimi</th>
                                <th>Döviz</th>
                                <th>Liste Fiyatı</th>
                                <th>Marka</th>
                                <th>Stok</th>
                                <th>Seçim</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Logo Aktarım Onay Modal'ı -->
    <div class="modal fade" id="confirmLogoTransferModal" tabindex="-1" aria-labelledby="confirmLogoTransferLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmLogoTransferLabel">Onayla</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <div class="modal-body">
                    Siparişi Logo’ya aktarmak istediğinize emin misiniz?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
                    <button type="button" id="confirmTransferBtn" class="btn btn-primary">Evet, Gönder</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Fiyat Düzenleme Modal’ı -->
    <div class="modal fade" id="priceModal" tabindex="-1" aria-labelledby="priceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="priceModalLabel">İskontolu Birim Fiyatı Düzenle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label for="priceModalInput" class="form-label">Yeni Birim Fiyat</label>
                    <input type="number" class="form-control" id="priceModalInput" step="0.01" min="0">
                    <div class="invalid-feedback" id="priceModalError"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-primary" id="priceModalSave">Onayla</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="sozPreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sozPreviewTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="sozPreviewBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="dataModal" tabindex="-1" aria-labelledby="dataModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dataModalLabel">Farklar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>

                <div class="modal-body p-0">
                    <!-- LOADER: ilk önce gösterilecek -->
                    <div id="dataModalLoader" class="d-flex justify-content-center align-items-center py-5 d-none">
                        <div class="spinner-border" role="status"></div>
                        <span class="ms-3">Veriler alınıyor… Lütfen bekleyin.</span>
                    </div>

                    <!-- ASIL İÇERİK: veri geldikten sonra gösterilecek -->
                    <div id="dataModalContent" class="table-responsive"
                        style="max-height:60vh; overflow:auto; display: none;">
                        <table id="diffTable" class="table table-sm table-striped table-hover mb-0">
                            <!-- Başlık ve gövde JS ile eklenecek -->
                        </table>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                </div>
            </div>
        </div>
    </div>

    <!-- İskonto Ekle Modal -->
    <div class="modal fade" id="discountModal" tabindex="-1">
        <div class="modal-dialog">
            <form id="discountForm" class="modal-content" method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title">İskonto Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- 1) Hangi aksiyonu çağıracağımızı bildiren hidden -->
                    <input type="hidden" name="action" value="addDiscount">
                    <!-- 2) Hangi teklif üzerine iskontoyu ekleyeceğiz -->
                    <input type="hidden" name="teklifid" value="<?= htmlspecialchars($teklifid) ?>">
                    <!-- 3) Hangi satıra bağlıyoruz -->
                    <input type="hidden" name="parent_id" id="discountParentId" value="">

                    <div class="mb-3">
                        <label for="discountRate" class="form-label">İskonto (%)</label>
                        <input type="number" name="discount_rate" id="discountRate" class="form-control" min="0"
                            max="<?= $iskonto_max ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>

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
    <script src="assets/libs/apexcharts/apexcharts.min.js"></script>
    <script src="assets/js/pages/dashboard.init.js"></script>
    <script src="assets/js/app.js"></script>
    <script src="assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>
    <script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="assets/libs/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
    <script src="assets/libs/parsleyjs/parsley.min.js"></script>
    <script>
        const teklifId = <?= json_encode($teklifid) ?>;

        document.addEventListener('DOMContentLoaded', function() {
            // DataTable örneği
            $('#examples').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": "uruncek.php?teklifid=<?= htmlspecialchars($teklifid) ?>"
            });

            // Form verilerini doğrudan serialize() ile alıyoruz

            // 2) Text tabanlı update cevabını işleyen ortak handler
            function handleUpdateResponse(txt) {
                console.log("RAW RESPONSE:", txt);
                let json;
                try {
                    json = JSON.parse(txt);
                } catch (e) {
                    return alert("Gelen ham çıktı JSON değil:\n" + txt);
                }
                alert(json.message);
                if (json.status) location.reload();
            }

            // Otomatik kayıtlarda uyarı göstermeden sadece hataları konsola yaz
            function handleAutoSaveResponse(txt) {
                try {
                    const json = JSON.parse(txt);
                    if (!json.status) {
                        console.warn('Auto-update failed:', json.message);
                    }
                } catch (e) {
                    console.warn('Unexpected auto-update response:', txt);
                }
            }

            // AJAX ile Manuel Logo bilgilerini güncelleme/kaydetme
            $('#updateLogoInfoBtn').on('click', function(e) {
                e.preventDefault();
                const $btn    = $(this).prop('disabled', true);
                const $form   = $('#logoUpdateForm');
                const $status = $('#updateStatus');

                $status.stop(true).hide().removeClass('text-success text-danger');

                $.ajax({
                    url:      $form.attr('action'),
                    type:     'POST',
                    data:     $form.serialize(),
                    dataType: 'json',
                    success(res) {
                        const cssClass = res.status ? 'text-success' : 'text-danger';
                        $status
                            .addClass(cssClass)
                            .text(res.message)
                            .show()
                            .delay(3000)
                            .fadeOut(500);
                        // if (res.status) location.reload();
                    },
                    error(xhr) {
                        $status
                            .addClass('text-danger')
                            .text('Sunucu hatası: ' + xhr.statusText)
                            .show()
                            .delay(5000)
                            .fadeOut(500);
                    },
                    complete() {
                        $btn.prop('disabled', false);
                    }
                });
            });

            // AJAX ile Siparişi Logo'ya gönderme
            $(function() {
                // 1) Butona tıklayınca önce onay modal’ını göster
                $('#sendToLogoBtn').on('click', function() {
                    if ($(this).prop('disabled') || $('#internal_reference').val() !== '') {
                        showLogoStatus('info', "Bu sipariş zaten Logo’ya aktarılmıştır.");
                        return;
                    }
                    $('#confirmLogoTransferModal').modal('show');
                });

                // 2) Modal’daki “Evet, Gönder” butonuna basılınca gerçek işlemi yap
                $('#confirmTransferBtn').on('click', function() {
                    $('#confirmLogoTransferModal').modal('hide');
                    performLocalUpdateThenTransfer();
                });

                // Yeni fonksiyon: önce güncelle, sonra transfer
                function performLocalUpdateThenTransfer() {
                    const $form = $('#logoUpdateForm');
                    $.post(
                        $form.attr('action'),
                        $form.serialize(),
                        function(res) { // burada JSON bekliyoruz
                            if (res.status) {
                                showLogoStatus('success', "Yerel veritabanı güncellendi, Logo’ya aktarıma geçiliyor…");
                                performLogoTransfer();
                            } else {
                                showLogoStatus('danger', "Güncelleme hatası: " + (res.message || "Bilinmeyen hata"));
                                $('#sendToLogoBtn').prop('disabled', false);
                            }
                        },
                        'json' // dataType
                    ).fail(function(xhr, status, err) {
                        showLogoStatus('danger', "Sunucu hatası (güncelleme): " + err);
                        $('#sendToLogoBtn').prop('disabled', false);
                    });
                }

                function performLogoTransfer() {
                    showLogoStatus('info', "Logo’ya aktarım başlatılıyor…");
                    $('#sendToLogoBtn').prop('disabled', true);
                    $('#sendToLogoSpinner').removeClass('d-none');

                    $.ajax({
                        url: '',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            logoyaAktar: 1,
                            icerikid: $('input[name="teklifid"]').val()
                        },
                        success: function(response) {
                            if (response.status) {
                                showLogoStatus('success', response.message || "Logo’ya aktarma başarılı.");
                                if (response.internal_reference) {
                                    $('#internal_reference').val(response.internal_reference);
                                }
                                setTimeout(function() {
                                    $('#confirmLogoTransferModal').modal('hide');
                                    location.reload();
                                }, 1000);
                            } else {
                                showLogoStatus('danger', response.message || "Logo’ya aktarma sırasında hata oluştu.");
                                $('#sendToLogoBtn').prop('disabled', false);
                            }
                        },
                        error: function(xhr, status, error) {
                            showLogoStatus('danger', "Sunucu hatası (transfer): " + error);
                            $('#sendToLogoBtn').prop('disabled', false);
                        },
                        complete: function() {
                            $('#sendToLogoSpinner').addClass('d-none');
                        }
                    });
                }

                function showLogoStatus(type, message) {
                    const $alert = $('#logoTransferAlert');
                    $alert
                        .removeClass('d-none alert-info alert-success alert-danger')
                        .addClass('alert-' + type)
                        .text(message);
                }

                $('.add-discount-btn').on('click', function() {
                    const parentId = $(this).data('row-id');
                    $('#discountParentId').val(parentId);
                    $('#discountRate, #discountDesc').val('');
                    new bootstrap.Modal($('#discountModal')).show();
                });

                $('#addGlobalDiscountBtn').on('click', function() {
                    $('#discountParentId').val('');
                    $('#discountModal .modal-title').text('Genel İskonto Ekle');
                    $('#discountRate').val('');
                    $('#discountDesc').val('');
                    console.log('Genel İskonto Ekle butonuna tıklandı');
                    new bootstrap.Modal($('#discountModal')).show();
                });

                // Modal form gönderildiğinde
                $('#discountForm').on('submit', function(e) {
                    e.preventDefault();
                    const postData = $(this).serialize();

                    $.post('', postData, function(res) {
                            if (res.status) {
                                alert('İskonto satırı başarıyla eklendi.');
                                location.reload();
                            } else {
                                alert('Hata: ' + res.message);
                            }
                        }, 'json')
                        .fail(function(xhr) {
                            alert('Sunucu hatası: ' + xhr.responseText);
                        });
                });
            });

            // Kod ve sebep seçimi kontrolü
            const codeSelect = document.getElementById('vatexcept_code');
            const codeOther = document.getElementById('vatexcept_code_other');
            const reasonSelect = document.getElementById('vatexcept_reason');
            const reasonOther = document.getElementById('vatexcept_reason_other');

            codeSelect.addEventListener('change', function() {
                codeOther.style.display = this.value === 'other' ? 'block' : 'none';
                if (this.value === '') {
                    codeOther.value = '';
                }
            });
            reasonSelect.addEventListener('change', function() {
                reasonOther.style.display = this.value === 'other' ? 'block' : 'none';
                if (this.value === '') {
                    reasonOther.value = '';
                }
            });
            if (codeSelect.value === 'other') {
                codeOther.style.display = 'block';
            }
            if (reasonSelect.value === 'other') {
                reasonOther.style.display = 'block';
            }

            // 1) showDataInModal'ı, boş veri geldiğinde bilgi satırı gösterecek şekilde güncelleyelim
            function showDataInModal(title, data, type) {
                $('#dataModalLoader').addClass('d-none');
                $('#dataModalContent').show();
                $('#dataModalLabel').text(title);
                const $table = $('#diffTable').empty();
                let $tbody;

                if (type === 'header') {
                    $table.append(`
                        <thead class="table-light">
                            <tr>
                                <th>Alan</th>
                                <th>Yerel Değer</th>
                                <th>Logo’daki Değer</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    `);
                    $tbody = $table.find('tbody');

                    const keys = Object.keys(data);
                    if (keys.length === 0) {
                        // Fark yoksa bilgi satırı
                        $tbody.append(`
                            <tr>
                                <td colspan="3" class="text-center text-info">
                                    <i class="bi bi-check-circle"></i> Hiç fark bulunamadı.
                                </td>
                            </tr>
                        `);
                    } else {
                        keys.forEach(field => {
                            const diff = data[field];
                            const oldVal = diff.old != null ? diff.old : '<i class="text-muted">yok</i>';
                            const newVal = diff.new != null ? diff.new : '<i class="text-muted">yok</i>';
                            $tbody.append(`
                                <tr>
                                    <td><code>${field}</code></td>
                                    <td>${oldVal}</td>
                                    <td>${newVal}</td>
                                </tr>
                            `);
                        });
                    }

                } else if (type === 'items') {
                    $table.append(`
                        <thead class="table-light">
                            <tr>
                                <th>Internal Ref</th>
                                <th>Alan</th>
                                <th>Yerel Değer</th>
                                <th>Logo’daki Değer</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    `);
                    $tbody = $table.find('tbody');

                    const refs = Object.keys(data);
                    if (refs.length === 0) {
                        $tbody.append(`
                          <tr>
                            <td colspan="4" class="text-center text-info">
                              <i class="bi bi-check-circle"></i> Hiç kalem farkı bulunamadı.
                            </td>
                          </tr>
                        `);
                    } else {
                        refs.forEach(ref => {
                            const diffs = data[ref];
                            const fields = Object.keys(diffs);
                            fields.forEach((field, idx) => {
                                const {
                                    old,
                                    new: neo
                                } = diffs[field];
                                const $tr = $('<tr>');
                                if (idx === 0) {
                                    $tr.append(`
                                      <td rowspan="${fields.length}" class="align-middle">
                                        <strong>${ref}</strong>
                                      </td>
                                    `);
                                }
                                $tr.append(
                                    `<td><code>${field}</code></td>`,
                                    `<td>${old != null ? old : '<i class="text-muted">yok</i>'}</td>`,
                                    `<td>${neo != null ? neo : '<i class="text-muted">yok</i>'}</td>`
                                );
                                $tbody.append($tr);
                            });
                        });
                    }
                }

                // Modal footer’a önceki güncelleme butonlarını temizle
                const $footer = $('#dataModal .modal-footer');
                $footer.find('#applyHeaderBtn,#applyItemsBtn').remove();

                // Eğer diff varsa, tip’e göre ilgili butonu ekle
                if (type === 'header' && Object.keys(data).length) {
                    $footer.prepend(`
                        <button id="applyHeaderBtn" class="btn btn-primary me-2">
                            Başlığı Güncelle
                        </button>
                    `);
                }
                if (type === 'items' && Object.keys(data).length) {
                    $footer.prepend(`
                        <button id="applyItemsBtn" class="btn btn-primary me-2">
                            Kalemleri Güncelle
                        </button>
                    `);
                }
                $('#dataModalLoader').hide();
                $('#dataModalContent').show();
                // new bootstrap.Modal($('#dataModal')[0]).show();
            }
            $('#dataModal').on('hidden.bs.modal', function() {
                $('#dataModalLoader').addClass('d-none');
                $('#dataModalContent').hide();
            });
            $(document).on('click', '#applyHeaderBtn', function() {
                const $btn = $(this).prop('disabled', true).text('Güncelleniyor…');
                $.ajax({
                    url: '',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'updateHeaderToLogo',
                        internal_reference: $('#internal_reference').val(),
                        teklifid: teklifId
                    },
                    success: function(res) {
                        alert(res.message);
                        $('#dataModal').modal('hide');
                        if (res.status) location.reload();
                    },
                    error: function(xhr) {
                        console.error('AJAX Error:', xhr);
                        alert('Sunucu hatası: ' + xhr.statusText);
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('Başlığı Güncelle');
                    }
                });
            });

            // ► Items güncelleme
            $(document).on('click', '#applyItemsBtn', function() {
                const $btn = $(this).prop('disabled', true).text('Güncelleniyor…');
                $.ajax({
                    url: '',
                    type: 'POST',
                    dataType: 'json', // JSON beklendiğini belirtin
                    data: {
                        action: 'updateItemsToLogo',
                        internal_reference: $('#internal_reference').val(),
                        teklifid: teklifId
                    },
                    success: function(res) {
                        // Artık res.status kesinlikle boolean
                        if (!res.status) {
                            // Hata varsa mesajı göster, reload yapmayın
                            alert('Hata: ' + res.message);
                        } else {
                            // Başarı
                            alert(res.message);
                            location.reload();
                        }
                        $('#dataModal').modal('hide');
                    },
                    error: function(xhr) {
                        alert('Sunucu hatası: ' + xhr.status + ' ' + xhr.statusText);
                        console.error('AJAX Error:', xhr);
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('Kalemleri Güncelle');
                    }
                });
            });

            const modalEl = document.getElementById('dataModal');
            const dataModal = bootstrap.Modal.getOrCreateInstance(modalEl);

            function openDataModal() {
                dataModal.show();
                $('#dataModalLoader').removeClass('d-none');
                $('#dataModalContent').hide();
            }

            // 2) AJAX success callback’lerini, boş datada da modalı açmak yerine bilgilendirme alert’i göstermeye ayarlayalım
            $('#btnCompareHeader').on('click', function() {
                const ref = $('#internal_reference').val().trim();
                if (!ref) return alert('Internal Reference boş!');

                openDataModal();
                $.ajax({
                    url: '',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'compareHeader',
                        internal_reference: ref,
                        teklifid: <?= $teklifid ?>
                    },
                    beforeSend: () => $(this).prop('disabled', true),
                    success: res => {
                        if (!res.status) {
                            alert('Karşılaştırma başarısız');
                            dataModal.hide();
                            return;
                        }
                        showDataInModal('Başlık Farkları', res.diff, 'header');
                    },
                    error: xhr => {
                        showError('Sunucu hatası: ' + xhr.statusText)
                        dataModal.hide();
                    },
                    complete: () => $('#btnCompareHeader').prop('disabled', false)
                });
            });

            $('#btnCompareItems').on('click', function() {
                const ref = $('#internal_reference').val().trim();
                if (!ref) return alert('Internal Reference boş!');
                openDataModal();
                $.ajax({
                    url: '',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'compareItems',
                        internal_reference: ref,
                        teklifid: <?= $teklifid ?>
                    },
                    beforeSend: () => $(this).prop('disabled', true),
                    success: res => {
                        if (!res.status) {
                            alert('Karşılaştırma başarısız');
                            dataModal.hide();
                            return;
                        }
                        showDataInModal('Kalem Farkları', res.diffItems, 'items');
                    },
                    error: xhr => {
                        showError('Sunucu hatası: ' + xhr.statusText)
                        $('#dataModal').modal('hide');
                    },
                    complete: () => $('#btnCompareItems').prop('disabled', false)
                });
            });

        });

        $(document).ready(function() {
            const maxPct = <?= json_encode($iskonto_max) ?>;
            let $currentRow, listPrice;

            // “Fiyatı Düzenle” butonuna tıklandığında modal’a veri yükle
            $(document).on('click', 'button[data-bs-target="#priceModal"]', function() {
                $currentRow = $(this).closest('tr');
                listPrice = parseFloat($(this).data('list-price')) || 0;
                $('#priceModalInput')
                    .val($currentRow.find('.final-price-hidden').val())
                    .removeClass('is-invalid');
                $('#priceModalError').text('');
            });

            // Modal’daki “Onayla” butonuna tıklandığında iskonto ve hesaplama
            $('#priceModalSave').on('click', function() {
                let newVal = parseFloat($('#priceModalInput').val());
                if (isNaN(newVal) || newVal < 0) {
                    $('#priceModalInput').addClass('is-invalid');
                    $('#priceModalError').text('Lütfen geçerli bir sayı girin.');
                    return;
                }
                let discPct = listPrice > 0 ? (1 - newVal / listPrice) * 100 : 0;
                discPct = Math.min(Math.max(discPct, 0), maxPct);

                $('#priceModal').modal('hide');
                $currentRow.find('.net-price-display').val(newVal.toFixed(2));
                $currentRow.find('.final-price-hidden').val(newVal.toFixed(2));
                $currentRow.find('.discount-input').val(discPct.toFixed(2));
                recalcOfferRow($currentRow);
            });
        });

        $('#bulkUpdateBtn').on('click', function() {
            const items = [];

            $('tr[data-row-id]').each(function() {
                const $row = $(this);
                items.push({
                    id: $row.data('row-id'),
                    miktar: $row.find('.qty-input').val(),
                    birim: $row.find('.unit-input').val(),
                    iskonto: $row.find('.discount-input').val()
                });
            });

            $.ajax({
                url: '',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'bulkUpdateRows',
                    items: JSON.stringify(items)
                },
                success: function(res) {
                    alert(res.message);
                    if (res.status) {
                        location.reload();
                    } else if (res.errors) {
                        console.error(res.errors);
                    }
                },
                error: function(xhr) {
                    console.error('AJAX Error:', xhr);
                    alert('Sunucu hatası: ' + xhr.statusText);
                }
            });
        });

        $('#source_wh').on('change', function() {
            const $opt = $(this).find('option:selected');
            $('#source_wh_nr').val($opt.data('nr'));
            $('#source_costgrp').val($opt.data('costgrp'));
        }).trigger('change');

        $('#salesmanref').on('change', function() {
            const code = $(this).find('option:selected').data('code') || '';
            $('input[name="salesman_code"]').val(code);
        });

       $('#salesmanref').trigger('change');

        <?php if (empty($teklif['paydefref']) && !empty($company['payplan_code'])): ?>
        $('#paydefref option').each(function(){
            if ($(this).data('code') === <?= json_encode($company['payplan_code']) ?>) {
                $(this).prop('selected', true);
            }
        });
        <?php endif; ?>

        $('#paydefref').on('change', function() {
            const code = $(this).find('option:selected').data('code') || '';
            $('#payment_code').val(code);
        }).trigger('change');

        document.querySelector('#syncRefsBtn').addEventListener('click', () => {
            const firmNr = 997;
            $.post('', {
                action: 'syncRef',
                firmNr
            }, function(res) {
                let msg = "✅ Synced: " + res.success.join(', ');
                if (Object.keys(res.failed).length) {
                    msg += "\n❌ Failed: " + JSON.stringify(res.failed);
                }
                alert(msg);
            }, 'json').fail(function(xhr, status, err) {
                console.error("Response text:", xhr.responseText);
                alert("Sync error: " + err + "\n\n" + xhr.responseText);
            });
        });
    </script>

    <script>
        $(function() {
            const teklifId = <?= json_encode($teklifid) ?>;
            const maxPct = <?= json_encode($iskonto_max) ?>;
            const euroRate = parseFloat($('#euroRate').val()) || 1.0;
            const dollarRate = parseFloat($('#dollarRate').val()) || 1.0;
            const kdvOrani = 0.20;
            
            // Eğer kurlar 0 veya NaN ise, varsayılan değerler kullan
            if (isNaN(euroRate) || euroRate <= 0) {
                console.warn('Euro kuru geçersiz, varsayılan değer kullanılıyor: 1.0');
            }
            if (isNaN(dollarRate) || dollarRate <= 0) {
                console.warn('Dolar kuru geçersiz, varsayılan değer kullanılıyor: 1.0');
            }

            // Otomatik kayıt işlemleri için sayfa yükleniyor mu kontrolü
            let initializing = true;

            // Debounce helper
            function debounce(fn, ms) {
                let timer;
                return function(...args) {
                    clearTimeout(timer);
                    timer = setTimeout(() => fn.apply(this, args), ms);
                };
            }

            // Genel indirim satırını tespit eder
            function isGeneralDiscountRow($row) {
                return $row.data('type') === 2 && !$row.data('parentInternalRef');
            }

            // 1) Satır bazında net + toplam hesaplama
            function recalcOfferRow($row) {
                if (!$row.length) return;

                // Eğer bu bir child indirim satırıysa, parent satırına taşı
                const rowType = parseInt($row.data('type'), 10);
                const parentRef = $row.data('parentInternalRef');
                if (rowType === 2 && parentRef) {
                    const $parent = $('tr[data-internal-ref="' + parentRef + '"]');
                    if ($parent.length) $row = $parent;
                }

                const listP = parseFloat(($row.find('.list-price').val() || '0').replace(',', '.')) || 0;
                const qty = parseFloat(($row.find('.qty-input').val() || '0').replace(',', '.')) || 0;
                const primaryPct = parseFloat(($row.find('.discount-input').val() || '0').replace(',', '.')) || 0;

                // NaN kontrolü
                if (isNaN(listP) || isNaN(qty) || isNaN(primaryPct)) {
                    console.warn('Geçersiz fiyat değerleri:', { listP, qty, primaryPct });
                    return;
                }

                // Ana iskonto sonrası birim fiyat
                let currUnitP = listP * (1 - primaryPct / 100);
                
                // NaN kontrolü
                if (isNaN(currUnitP)) {
                    currUnitP = 0;
                }

                // Üzerine ek indirimler (child indirim satırları)
                const internalRef = $row.data('internalRef');
                $('tr[data-type="2"][data-parent-internal-ref="' + internalRef + '"]').each(function() {
                    const $discRow = $(this);
                    const pct = parseFloat($discRow.find('.discount-input').val().replace(',', '.')) || 0;
                    const discUnit = currUnitP * (pct / 100);
                    $discRow.find('.total-price')
                        .text((discUnit * qty).toFixed(2).replace('.', ','));
                    currUnitP *= (1 - pct / 100);
                });

                // Satırın net fiyat ve toplamı
                const total = currUnitP * qty;
                
                // NaN kontrolü
                const finalUnitP = isNaN(currUnitP) ? 0 : currUnitP;
                const finalTotal = isNaN(total) ? 0 : total;
                
                $row.find('.net-price-display').val(finalUnitP.toFixed(2));
                $row.find('.final-price-hidden').val(finalUnitP.toFixed(2));
                $row.find('.total-price')
                    .text(finalTotal.toFixed(2).replace('.', ','));
            }

            // 2) Özet tablosunu yeniden hesaplama
            function recalcSummary() {
                const sumBrut = {
                        TL: 0,
                        EUR: 0,
                        USD: 0
                    },
                    sumNet = {
                        TL: 0,
                        EUR: 0,
                        USD: 0
                    },
                    sumDisc = {
                        TL: 0,
                        EUR: 0,
                        USD: 0
                    };

                // Satır bazlı toplamları çek
                $('#datatable tbody tr').each(function() {
                    const $r = $(this);
                    const cur = $r.find('.list-price').data('currency') || 'EUR';
                    const qty = parseFloat(($r.find('.qty-input').val() || '0').replace(',', '.')) || 0;
                    const listP = parseFloat(($r.find('.list-price').val() || '0').replace(',', '.')) || 0;
                    const netP = parseFloat(($r.find('.net-price-display').val() || '0').replace(',', '.')) || 0;
                    
                    // NaN kontrolü
                    if (isNaN(qty) || isNaN(listP) || isNaN(netP)) {
                        console.warn('Geçersiz satır değerleri:', { qty, listP, netP });
                        return;
                    }
                    
                    const brutL = listP * qty;
                    const netL = netP * qty;
                    const discL = brutL - netL;

                    // NaN kontrolü
                    if (!isNaN(brutL) && !isNaN(netL) && !isNaN(discL)) {
                        sumBrut[cur] = (sumBrut[cur] || 0) + brutL;
                        sumNet[cur] = (sumNet[cur] || 0) + netL;
                        sumDisc[cur] = (sumDisc[cur] || 0) + discL;
                    }
                });

                // Hepsini EUR cinsine çevir (euroRate ve dollarRate kontrolü ile)
                const validEuroRate = (euroRate > 0 && !isNaN(euroRate)) ? euroRate : 1.0;
                const validDollarRate = (dollarRate > 0 && !isNaN(dollarRate)) ? dollarRate : 1.0;
                
                let brutEUR = sumBrut.EUR + (validEuroRate > 0 ? sumBrut.TL / validEuroRate : 0) + (validEuroRate > 0 ? sumBrut.USD * validDollarRate / validEuroRate : 0);
                let netEUR = sumNet.EUR + (validEuroRate > 0 ? sumNet.TL / validEuroRate : 0) + (validEuroRate > 0 ? sumNet.USD * validDollarRate / validEuroRate : 0);
                let discEUR = sumDisc.EUR + (validEuroRate > 0 ? sumDisc.TL / validEuroRate : 0) + (validEuroRate > 0 ? sumDisc.USD * validDollarRate / validEuroRate : 0);
                
                // NaN kontrolü
                brutEUR = isNaN(brutEUR) ? 0 : brutEUR;
                netEUR = isNaN(netEUR) ? 0 : netEUR;
                discEUR = isNaN(discEUR) ? 0 : discEUR;

                // 3) Genel indirim satırlarını sırayla uygula
                $('#datatable tbody tr')
                    .filter((_, el) => isGeneralDiscountRow($(el)))
                    .each(function(i, el) {
                        const $row = $(el),
                            pct = parseFloat($row.find('.discount-input').val()) || 0,
                            thisDisc = netEUR * pct / 100;
                        discEUR += thisDisc;
                        netEUR -= thisDisc;
                    });

                // 4) TL / USD'e geri dön (validEuroRate ve validDollarRate kullan)
                const brutTL = brutEUR * validEuroRate,
                    netTL = netEUR * validEuroRate,
                    discTL = discEUR * validEuroRate,
                    brutUSD = (validDollarRate > 0) ? brutEUR * validEuroRate / validDollarRate : 0,
                    netUSD = (validDollarRate > 0) ? netEUR * validEuroRate / validDollarRate : 0,
                    discUSD = (validDollarRate > 0) ? discEUR * validEuroRate / validDollarRate : 0,

                    // 5) KDV ve Genel Toplam
                    kdvEUR = netEUR * kdvOrani,
                    grandEUR = netEUR + kdvEUR;
                
                // NaN kontrolü
                const finalBrutTL = isNaN(brutTL) ? 0 : brutTL;
                const finalNetTL = isNaN(netTL) ? 0 : netTL;
                const finalDiscTL = isNaN(discTL) ? 0 : discTL;
                const finalBrutUSD = isNaN(brutUSD) ? 0 : brutUSD;
                const finalNetUSD = isNaN(netUSD) ? 0 : netUSD;
                const finalDiscUSD = isNaN(discUSD) ? 0 : discUSD;
                const finalKdvEUR = isNaN(kdvEUR) ? 0 : kdvEUR;
                const finalGrandEUR = isNaN(grandEUR) ? 0 : grandEUR;

                // 6) DOM'a yaz (NaN kontrolü ile)
                $('#summary-brut-TL').text(finalBrutTL.toFixed(2).replace('.', ',') + ' TL');
                $('#summary-brut-EUR').text(brutEUR.toFixed(2).replace('.', ',') + ' €');
                $('#summary-brut-USD').text(finalBrutUSD.toFixed(2).replace('.', ',') + ' $');

                $('#summary-disc-TL').text('- ' + finalDiscTL.toFixed(2).replace('.', ',') + ' TL');
                $('#summary-disc-EUR').text('- ' + discEUR.toFixed(2).replace('.', ',') + ' €');
                $('#summary-disc-USD').text('- ' + finalDiscUSD.toFixed(2).replace('.', ',') + ' $');

                $('#summary-net-TL').text(finalNetTL.toFixed(2).replace('.', ',') + ' TL');
                $('#summary-net-EUR').text(netEUR.toFixed(2).replace('.', ',') + ' €');
                $('#summary-net-EUR2').text(netEUR.toFixed(2).replace('.', ',') + ' €');
                $('#summary-net-USD').text(finalNetUSD.toFixed(2).replace('.', ',') + ' $');

                $('#summary-kdv-EUR').text(finalKdvEUR.toFixed(2).replace('.', ',') + ' €');
                $('#summary-grand-EUR').text(finalGrandEUR.toFixed(2).replace('.', ',') + ' €');
            }

            // 3) Satır güncellemelerini DB’ye de yolla
            function sendItemUpdate($row) {
                $.post('', {
                    action: 'updateItem',
                    id: $row.data('row-id'),
                    miktar: parseFloat($row.find('.qty-input').val()) || 0,
                    birim: $row.find('.unit-input').val(),
                    iskonto: parseFloat($row.find('.discount-input').val()) || 0,
                    iskonto_formulu: $row.find('.iskonto-formula-input').val() || '',
                    teklifid: teklifId
                }, 'json');
            }

            // ► Form alanlarında değişiklik olduğunda başlığı otomatik kaydet
            const autoSaveHeader = debounce(() => {
                const $status = $('#autoSaveStatus');
                $status.stop(true)
                    .show()
                    .removeClass('text-success text-danger')
                    .addClass('text-muted')
                    .text('Kaydediliyor…');

                if (window.__autoSaveXhr) {
                    window.__autoSaveXhr.abort();
                }

                const $form = $('#logoUpdateForm');
                window.__autoSaveXhr = $.ajax({
                    url: $form.attr('action'),
                    type: 'POST',
                    data: $form.serialize(),
                    dataType: 'json'
                })
                .done(function(json, textStatus, xhr) {
                    console.log('Auto-save raw response:', xhr.responseText);
                    const ok  = json.status;
                    const cls = ok ? 'text-success' : 'text-danger';
                    $status
                        .removeClass('text-muted')
                        .addClass(cls)
                        .text(ok ? 'Kaydedildi' : 'Kaydetme hatası: ' + json.message)
                        .delay(ok ? 2000 : 5000)
                        .fadeOut(500);
                })
                .fail(function(xhr, status, err) {
                    console.error('Auto-save failed:', status, err);
                    $status
                        .removeClass('text-muted')
                        .addClass('text-danger')
                        .text('Sunucu hatası veya geçersiz JSON')
                        .delay(5000)
                        .fadeOut(500);
                });
            }, 1000);

            $('#logoUpdateForm').on('input change', 'input,select,textarea', () => {
                if (!initializing) autoSaveHeader();
            });

            // 4) Dinleyiciler
            $('#datatable tbody')
                .on('input change', '.qty-input, .discount-input', debounce(function() {
                    const $r = $(this).closest('tr');
                    recalcOfferRow($r);
                    recalcSummary();
                    sendItemUpdate($r);
                }, 300))
                .on('change', '.unit-input', function() {
                    sendItemUpdate($(this).closest('tr'));
                });

            function updateEditLink() {
                const id = $('#sozlesme_id').val();
                $('#editSozBtn').attr('href', 'sozlesme_duzenle.php?id=' + id);
            }

            updateEditLink();
            $('#sozlesme_id').on('change', updateEditLink);

            $('#previewSozBtn').on('click', function() {
                $.post('', {action: 'getContract', id: $('#sozlesme_id').val()}, function(r) {
                    if (r.status) {
                        $('#sozPreviewTitle').text(r.data.sozlesmeadi);
                        $('#sozPreviewBody').html(r.data.sozlesme_metin);
                        new bootstrap.Modal(document.getElementById('sozPreviewModal')).show();
                    }
                }, 'json');
            });

            // Sayfa yüklenince bir kere çalıştır
            $('#datatable tbody tr').each(function() {
                recalcOfferRow($(this));
            });
            recalcSummary();

            // Başlangıç tetiklemeleri bitti, otomatik güncelleme aktif
            initializing = false;
        });
    </script>

    <script>
    // Restriction Script for Special Offers
    document.addEventListener('DOMContentLoaded', function() {
        const status = <?= json_encode(trim($teklif['durum'] ?? '')) ?>;
        if (status === 'Yönetici Onayı Bekleniyor') {
            // Intercept clicks on WhatsApp and Mail generated buttons/links
            // We use event delegation on document body to catch dynamically created elements or existing ones
            document.body.addEventListener('click', function(e) {
                // Check if the clicked element or its parent is a whatsapp or mail button/link
                const target = e.target.closest('a[href*="whatsapp"], a[href^="mailto"], button[class*="whatsapp"], button[class*="mail"]');
                
                if (target) {
                    // Start blocking
                    e.preventDefault();
                    e.stopPropagation();
                    alert('⚠️ DİKKAT: Bu teklif "Yönetici Onayı Bekleniyor" durumundadır.\n\nOnaylanmadan mail veya WhatsApp gönderimi yapılamaz.');
                    return false;
                }
            }, true); // Use capture phase to be early
        }
    });
    </script>
<script>
    // --- Real-time Status Polling ---
    function updateStatusContainer(force = false) {
        var tid = <?= $teklifid ?>;
        if(tid > 0) {
             $.get('api/teklif/get_status_html.php?id=' + tid + '&t=' + new Date().getTime(), function(data) {
                if(data.trim() !== '') {
                     $('#status-container').html(data);
                } else {
                     $('#status-container').html('');
                }
            });
        }
    }

    // Regular polling (backup)
    setInterval(updateStatusContainer, 5000); 

    // Listen for Global Notification Event
    document.addEventListener('offerStatusUpdate', function(e) {
        // If the notification is about THIS offer, update immediately
        // The event detail.id might be string or int, compare loosely
        if(e.detail && e.detail.id == <?= $teklifid ?>) {
            console.log('Global notification received for this offer. Updating status...');
            updateStatusContainer(true);
        }
    });
</script>
<?php include "menuler/footer.php"; ?>
</body>

</html>
```
