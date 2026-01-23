<?php
// fiyat_talepleri_islem.php - Yeni tablo yapısı ile güncellendi
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

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'ID eksik']);
    exit;
}

if ($action === 'reject') {
    $yonetici_id = $_SESSION['yonetici_id'] ?? 0;
    $stmt = $db->prepare("UPDATE fiyat_talepleri SET 
        durum='reddedildi', 
        yonetici_notu=?, 
        cevaplayan_id=?, 
        cevap_tarihi=NOW() 
        WHERE talep_id=?");
    $stmt->bind_param("sii", $note, $yonetici_id, $id);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Talep reddedildi.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $db->error]);
    }
} elseif ($action === 'approve') {
    $yonetici_id = $_SESSION['yonetici_id'] ?? 0;
    $stmt = $db->prepare("UPDATE fiyat_talepleri SET 
        durum='onaylandi', 
        cevaplayan_id=?, 
        cevap_tarihi=NOW() 
        WHERE talep_id=?");
    $stmt->bind_param("ii", $yonetici_id, $id);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Talep onaylandı olarak işaretlendi.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $db->error]);
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz işlem']);
}
?>
