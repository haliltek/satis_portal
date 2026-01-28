<?php
header("Content-Type: application/json");

// Veritabanı bağlantı bilgilerini dahil et
include "../../include/vt.php";

// Manuel bağlantı kur (fonk.php'nin session redirectlerinden kaçınmak için)
$db = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
if ($db->connect_error) {
    die(json_encode(["status" => "error", "message" => "Veritabanı bağlantı hatası: " . $db->connect_error]));
}
$db->set_charset("utf8");

$data = json_decode(file_get_contents("php://input"), true);

$teklif_id = $data["teklif_id"] ?? null;
$teklif_id = $data["teklif_id"] ?? null;
$onaylayan = $data["onaylayan"] ?? null; // Eğer INT gelirse approved_by'a yazabiliriz
$message_id = $data["message_id"] ?? null;
$manager_phone = $data["manager_phone"] ?? null;

if ($teklif_id) {
    // Ogteklif2 tablosunu güncelle
    // approval_status: ENUM('none', 'pending', 'approved', 'rejected')
    // approved_at: NOW()
    $sql = "UPDATE ogteklif2 SET approval_status='approved', durum='Yönetici Onayladı / Gönderilecek', approved_at=NOW()";
    
    // Eğer onaylayan sayısal bir ID ise approved_by alanını da güncelle
    if (is_numeric($onaylayan)) {
        $sql .= ", approved_by=?";
    }
    
    $sql .= " WHERE id=?";
    
    $stmt = $db->prepare($sql);
    
    if (is_numeric($onaylayan)) {
        $stmt->bind_param("ii", $onaylayan, $teklif_id);
    } else {
        $stmt->bind_param("i", $teklif_id);
    }
    
    if ($stmt->execute()) {
        // Kararı teklif_decisions tablosuna kaydet
        if ($message_id) {
            $decision_sql = "INSERT INTO teklif_decisions 
                            (message_id, teklif_id, manager_phone, decision_type, decision_status) 
                            VALUES (?, ?, ?, 'ONAY', 'PROCESSED')
                            ON DUPLICATE KEY UPDATE 
                            decision_type = 'ONAY',
                            decision_date = CURRENT_TIMESTAMP";
            
            $stmt_dec = $db->prepare($decision_sql);
            if ($stmt_dec) {
                $stmt_dec->bind_param("sss", $message_id, $teklif_id, $manager_phone);
                $stmt_dec->execute();
                $stmt_dec->close();
            }
        }
        echo json_encode(["status" => "success", "message" => "Teklif onaylandı"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Veritabanı hatası: " . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(["status" => "error", "message" => "Teklif ID eksik"]);
}

$db->close();
?>
