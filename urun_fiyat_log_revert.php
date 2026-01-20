<?php
// urun_fiyat_log_revert.php
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

$logId = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($logId <= 0) {
    echo json_encode(['status' => 'error', 'error' => 'Geçersiz log ID']);
    exit;
}

$db->set_charset('utf8');

$stmt = $db->prepare("SELECT stokkodu, onceki_fiyat, fiyat_tipi, reverted FROM urun_fiyat_log WHERE log_id = ?");
$stmt->bind_param("i", $logId);
$stmt->execute();
$log = $stmt->get_result()->fetch_assoc();

if (!$log) {
    echo json_encode(['status' => 'error', 'error' => 'Kayıt bulunamadı']);
    exit;
}
if ((int)$log['reverted'] === 1) {
    echo json_encode(['status' => 'error', 'error' => 'Kayıt zaten geri alınmış']);
    exit;
}

$stokKodu = $log['stokkodu'];
$oncekiFiyat = $log['onceki_fiyat'];
$fiyatTipi = $log['fiyat_tipi'];

// Ürünün mevcut fiyatlarını ve LOGO referanslarını çek
$prodStmt = $db->prepare("SELECT fiyat, export_fiyat, GEMPA2026LOGICAL, GEMAS2026LOGICAL FROM urunler WHERE stokkodu = ? LIMIT 1");
$prodStmt->bind_param("s", $stokKodu);
$prodStmt->execute();
$product = $prodStmt->get_result()->fetch_assoc();
$prodStmt->close();

if (!$product) {
    echo json_encode(['status' => 'error', 'error' => 'Ürün bulunamadı']);
    exit;
}

$newDomestic = (float)$product['fiyat'];
$newExport   = (float)$product['export_fiyat'];
if ($fiyatTipi === 'export') {
    $newExport = (float)$oncekiFiyat;
} else {
    $newDomestic = (float)$oncekiFiyat;
}

$logger = new LoggerService(__DIR__ . '/error.log');
$yoneticiId = intval($_SESSION['yonetici_id']);
$priceUpdater = new PriceUpdater($db, $gemas_logo_db, $gempa_logo_db, $gemas_web_db, $logger, $yoneticiId);
$result = $priceUpdater->updatePrices(
    $stokKodu,
    intval($product['GEMPA2026LOGICAL'] ?? 0),
    intval($product['GEMAS2026LOGICAL'] ?? 0),
    $newDomestic,
    $newExport
);

if ($result['overallStatus'] === 'partial' || $result['overallStatus'] === 'success' || $result['overallStatus'] === 'no_change') {
    // continue
} else {
    echo json_encode(['status' => 'error', 'error' => $result['message'] ?? 'Güncelleme başarısız']);
    exit;
}
$mark = $db->prepare("UPDATE urun_fiyat_log SET reverted = 1, reverted_by = ?, reverted_at = NOW() WHERE log_id = ?");
$mark->bind_param("ii", $yoneticiId, $logId);
$mark->execute();

if ($mark->error) {
    echo json_encode(['status' => 'error', 'error' => $mark->error]);
    exit;
}
echo json_encode([
    'status'     => $result['overallStatus'],
    'message'    => $result['message'] ?? '',
    'platforms'  => $result['platforms'] ?? [],
    'stokKodu'   => $result['stokKodu'] ?? $stokKodu
]);
