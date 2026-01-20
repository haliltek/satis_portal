<?php
header("Content-Type: application/json");

// Veritabanı bağlantı bilgilerini dahil et
include "../../include/vt.php";

// Manuel bağlantı kur
$db = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
if ($db->connect_error) {
    die(json_encode(["status" => "error", "message" => "Veritabanı bağlantı hatası: " . $db->connect_error]));
}
$db->set_charset("utf8");

$data = json_decode(file_get_contents("php://input"), true);

$teklif_id = $data["teklif_id"] ?? null;
$neden     = $data["neden"] ?? "Yönetici tarafından reddedildi";
$reddeden  = $data["reddeden"] ?? null; // Loglamak istenirse

if ($teklif_id) {
    // Ogteklif2 tablosunu güncelle
    // approval_status: 'rejected'
    // notes1 veya uygun bir alana red nedenini ekleyelim mi? 
    // Mevcut yapıda 'red_nedeni' kolonu yoksa notes1'e ekleyebiliriz veya sadece durumu güncelleriz.
    // Kullanıcı talebinde "red_nedeni" kolonu var (teklifler tablosu için).
    // ogteklif2'de red_nedeni yok ancak notes1 var. Oraya ekleyelim.
    
    $updateSql = "UPDATE ogteklif2 SET approval_status='rejected', durum='Yönetici Reddetti', notes1=CONCAT(COALESCE(notes1,''), ' [Red Nedeni: ', ? , ']') WHERE id=?";
    
    $stmt = $db->prepare($updateSql);
    $stmt->bind_param("si", $neden, $teklif_id);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Teklif reddedildi"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Veritabanı hatası: " . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(["status" => "error", "message" => "Teklif ID eksik"]);
}

$db->close();
?>
