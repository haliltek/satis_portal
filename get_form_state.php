<?php
// get_form_state.php - Form durumunu session'dan alma
require_once "fonk.php";
oturumkontrol();

header('Content-Type: application/json');

$musteriId = $_SESSION['form_musteri_id'] ?? null;
$musteriData = null;

// Müşteri ID varsa müşteri bilgilerini çek
if ($musteriId && $musteriId !== '786') {
    $stmt = $db->prepare("SELECT sirket_id, s_arp_code, s_adi FROM sirket WHERE sirket_id = ?");
    $stmt->bind_param("i", $musteriId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        // Select2 formatına uygun: musteri-search.php'deki formatı kullan
        $musteriData = [
            'id' => $row['sirket_id'],
            'text' => $row['s_arp_code'] . ' - ' . $row['s_adi']
        ];
    }
    $stmt->close();
}

$response = [
    'success' => true,
    'musteri_id' => $musteriId,
    'musteri_data' => $musteriData,
    'ekstra_bilgi' => $_SESSION['form_ekstra_bilgi'] ?? '',
    'iskontolar' => $_SESSION['form_iskontolar'] ?? [],
    'sozlesme_metin' => $_SESSION['form_sozlesme_metin'] ?? '',
    'sozlesme_id' => $_SESSION['form_sozlesme_id'] ?? null
];

echo json_encode($response);

