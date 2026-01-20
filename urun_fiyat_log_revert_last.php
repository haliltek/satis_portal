<?php
// urun_fiyat_log_revert_last.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "fonk.php";
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/services/LoggerService.php';
require_once __DIR__ . '/services/PriceUpdater.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['yonetici_id'])) {
    echo json_encode(['status' => 'error', 'error' => 'Yetkisiz erişim']);
    exit;
}

$stokKodu = trim($_POST['stokkodu'] ?? '');
if ($stokKodu === '') {
    echo json_encode(['status' => 'error', 'error' => 'Geçersiz stok kodu']);
    exit;
}

$db->set_charset('utf8');

$stmt = $db->prepare("SELECT log_id, guncelleme_tarihi FROM urun_fiyat_log WHERE stokkodu = ? AND reverted = 0 ORDER BY log_id DESC LIMIT 2");
$stmt->bind_param("s", $stokKodu);
$stmt->execute();
$res = $stmt->get_result();
$rows = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (!$rows) {
    echo json_encode(['status' => 'error', 'error' => 'Geri alınacak kayıt bulunamadı']);
    exit;
}

$latestTs = $rows[0]['guncelleme_tarihi'];
$logIds = [];
foreach ($rows as $r) {
    if ($r['guncelleme_tarihi'] === $latestTs) {
        $logIds[] = (int)$r['log_id'];
    }
}

$logger = new LoggerService(__DIR__ . '/error.log');
$yoneticiId = intval($_SESSION['yonetici_id']);
$priceUpdater = new PriceUpdater($db, $gemas_logo_db, $gempa_logo_db, $gemas_web_db, $logger, $yoneticiId);

$results = [];
foreach ($logIds as $logId) {
    $stmt = $db->prepare("SELECT stokkodu, onceki_fiyat, fiyat_tipi FROM urun_fiyat_log WHERE log_id = ?");
    $stmt->bind_param("i", $logId);
    $stmt->execute();
    $log = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$log) {
        $results[] = ['id' => $logId, 'status' => 'missing'];
        continue;
    }
    $stok = $log['stokkodu'];
    $onceki = $log['onceki_fiyat'];
    $tip = $log['fiyat_tipi'];

    $prodStmt = $db->prepare("SELECT fiyat, export_fiyat, GEMPA2026LOGICAL, GEMAS2026LOGICAL FROM urunler WHERE stokkodu = ? LIMIT 1");
    $prodStmt->bind_param("s", $stok);
    $prodStmt->execute();
    $product = $prodStmt->get_result()->fetch_assoc();
    $prodStmt->close();

    if (!$product) {
        $results[] = ['id' => $logId, 'status' => 'missing'];
        continue;
    }

    $newDomestic = (float)$product['fiyat'];
    $newExport   = (float)$product['export_fiyat'];
    if ($tip === 'export') {
        $newExport = (float)$onceki;
    } else {
        $newDomestic = (float)$onceki;
    }

    $res = $priceUpdater->updatePrices(
        $stok,
        intval($product['GEMPA2026LOGICAL'] ?? 0),
        intval($product['GEMAS2026LOGICAL'] ?? 0),
        $newDomestic,
        $newExport
    );

    if ($res['overallStatus'] === 'partial' || $res['overallStatus'] === 'success' || $res['overallStatus'] === 'no_change') {
        $mark = $db->prepare("UPDATE urun_fiyat_log SET reverted = 1, reverted_by = ?, reverted_at = NOW() WHERE log_id = ?");
        $mark->bind_param("ii", $yoneticiId, $logId);
        $mark->execute();
        $results[] = ['id' => $logId, 'status' => $res['overallStatus']];
    } else {
        $results[] = ['id' => $logId, 'status' => 'error'];
    }
}

echo json_encode(['status' => 'success', 'results' => $results]);
