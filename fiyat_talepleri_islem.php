<?php
// fiyat_talepleri_islem.php
include "fonk.php";
oturumkontrol();

header('Content-Type: application/json; charset=utf-8');

if ($_SESSION['user_type'] !== 'Yönetici') {
    echo json_encode(['status' => 'error', 'message' => 'Yetkisiz işlem']);
    exit;
}

$action = $_POST['action'] ?? '';
$id = intval($_POST['id'] ?? 0);
$note = $_POST['note'] ?? '';

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'ID eksik']);
    exit;
}

if ($action === 'getProductDetails') {
    $stokKodu = $_POST['stok_kodu'] ?? '';
    if (!$stokKodu) {
        echo json_encode(['status' => 'error', 'message' => 'Stok kodu eksik']);
        exit;
    }
    $esc = $db->real_escape_string($stokKodu);
    $row = $db->query("SELECT LOGICALREF, GEMPA2026LOGICAL, GEMAS2026LOGICAL, stokadi, fiyat, export_fiyat FROM urunler WHERE stokkodu='$esc'")->fetch_assoc();
    if ($row) {
        echo json_encode(['status' => 'success', 'data' => $row]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Ürün bulunamadı']);
    }
    exit;
}

if ($action === 'reject') {
    $stmt = $db->prepare("UPDATE fiyat_onerileri SET durum='Reddedildi', oneri_not=CONCAT(oneri_not, ' [Red Sebebi: ', ?, ']') WHERE id=?");
    $stmt->bind_param("si", $note, $id);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $db->error]);
    }
} elseif ($action === 'approve') {
    $stmt = $db->prepare("UPDATE fiyat_onerileri SET durum='Onaylandı' WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Talep onaylandı olarak işaretlendi.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $db->error]);
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz işlem']);
}
?>
