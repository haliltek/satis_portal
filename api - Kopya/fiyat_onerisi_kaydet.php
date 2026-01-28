<?php
// api/fiyat_onerisi_kaydet.php
include "../fonk.php";
header('Content-Type: application/json; charset=utf-8');

oturumkontrol();
$db = local_database();

$yonetici_id = $_SESSION['yonetici_id'] ?? 0;
$urun_id = intval($_POST['urun_id'] ?? 0);
$stok_kodu = trim($_POST['stok_kodu'] ?? '');
$mevcut_fiyat_yurtici = floatval($_POST['mevcut_fiyat_yurtici'] ?? 0);
$mevcut_fiyat_export = floatval($_POST['mevcut_fiyat_export'] ?? 0);
$oneri_fiyat_yurtici = floatval($_POST['oneri_fiyat_yurtici'] ?? 0);
$oneri_fiyat_export = floatval($_POST['oneri_fiyat_export'] ?? 0);
$oneri_not = trim($_POST['oneri_not'] ?? '');

if ($urun_id <= 0 || empty($stok_kodu)) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz ürün bilgileri.']);
    exit;
}

if (empty($oneri_not)) {
    echo json_encode(['success' => false, 'message' => 'Lütfen öneri nedeninizi belirtiniz.']);
    exit;
}

$stmt = $db->prepare("INSERT INTO fiyat_onerileri (stok_kodu, urun_id, mevcut_fiyat_yurtici, mevcut_fiyat_export, oneri_fiyat_yurtici, oneri_fiyat_export, oneri_not, yonetici_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("siddddsi", $stok_kodu, $urun_id, $mevcut_fiyat_yurtici, $mevcut_fiyat_export, $oneri_fiyat_yurtici, $oneri_fiyat_export, $oneri_not, $yonetici_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Öneriniz başarıyla kaydedildi.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Kaydetme sırasında bir hata oluştu: ' . $db->error]);
}
?>
