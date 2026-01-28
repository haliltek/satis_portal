<?php
// api/save_special_pricing.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../fonk.php';

global $db;

// JSON verisini al
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz veri']);
    exit;
}

$workId = $data['work_id'] ?? 0;
$sirketId = $data['sirket_id'] ?? 0;
$cariKod = $data['cari_kod'] ?? '';
$baslik = $data['baslik'] ?? '';
$aciklama = $data['aciklama'] ?? '';
$aktif = $data['aktif'] ?? 1;
$yoneticiId = $data['olusturan_yonetici_id'] ?? 0;
$urunler = $data['urunler'] ?? [];

if (empty($baslik) || empty($cariKod) || empty($urunler)) {
    echo json_encode(['success' => false, 'message' => 'Eksik bilgi']);
    exit;
}

try {
    $db->begin_transaction();

    if ($workId > 0) {
        // Güncelleme
        $stmt = $db->prepare("UPDATE ozel_fiyat_calismalari SET baslik = ?, aciklama = ?, aktif = ? WHERE id = ?");
        $stmt->bind_param("ssii", $baslik, $aciklama, $aktif, $workId);
        $stmt->execute();
        $stmt->close();

        // Mevcut ürünleri sil
        $stmt = $db->prepare("DELETE FROM ozel_fiyat_urunler WHERE calisma_id = ?");
        $stmt->bind_param("i", $workId);
        $stmt->execute();
        $stmt->close();
    } else {
        // Yeni kayıt
        $stmt = $db->prepare("INSERT INTO ozel_fiyat_calismalari (sirket_id, cari_kod, baslik, aciklama, aktif, olusturan_yonetici_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssii", $sirketId, $cariKod, $baslik, $aciklama, $aktif, $yoneticiId);
        $stmt->execute();
        $workId = $db->insert_id;
        $stmt->close();
    }

    // Ürünleri ekle
    $stmt = $db->prepare("INSERT INTO ozel_fiyat_urunler (calisma_id, stok_kodu, urun_adi, birim, liste_fiyati, ozel_fiyat, maliyet, doviz, iskonto_orani, notlar) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($urunler as $urun) {
        $stokKodu = $urun['stok_kodu'] ?? '';
        $urunAdi = $urun['urun_adi'] ?? '';
        $birim = $urun['birim'] ?? '';
        $listeFiyati = $urun['liste_fiyati'] ?? 0;
        $ozelFiyat = $urun['ozel_fiyat'] ?? 0;
        $maliyet = $urun['maliyet'] ?? 0; // Yeni maliyet alanı
        $doviz = $urun['doviz'] ?? 'EUR';
        $iskontoOrani = $urun['iskonto_orani'] ?? 0;
        $notlar = $urun['notlar'] ?? '';

        $stmt->bind_param("isssdddsds", $workId, $stokKodu, $urunAdi, $birim, $listeFiyati, $ozelFiyat, $maliyet, $doviz, $iskontoOrani, $notlar);
        $stmt->execute();
    }
    $stmt->close();

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Çalışma kaydedildi',
        'work_id' => $workId
    ]);

} catch (Exception $e) {
    $db->rollback();
    error_log("save_special_pricing.php Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Kaydetme sırasında hata oluştu: ' . $e->getMessage()]);
}
