<?php
include "fonk.php";
oturumkontrol();

header('Content-Type: application/json');

$sozlesme_id = isset($_GET['sozlesme_id']) ? (int)$_GET['sozlesme_id'] : 0;

if ($sozlesme_id > 0) {
    $sozlesmeSorgu = mysqli_query($db, "SELECT sozlesme_metin FROM sozlesmeler WHERE sozlesme_id = " . (int)$sozlesme_id);
    if ($sozlesmeRow = mysqli_fetch_assoc($sozlesmeSorgu)) {
        echo json_encode([
            'success' => true,
            'metin' => $sozlesmeRow['sozlesme_metin'] ?? ''
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Sözleşme bulunamadı'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Geçersiz sözleşme ID'
    ]);
}
?>


