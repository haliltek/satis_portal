<?php
// create_test_offer.php
include __DIR__ . "/include/vt.php";

header('Content-Type: application/json');

// Database connection
$db = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
$db->set_charset("utf8");

if ($db->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed: " . $db->connect_error]));
}

// Dummy Data
$cari = "TEST CARI (AI Generated)";
$temsilci = "AI Agent";
$stokadi = "Test Urunu - " . rand(100, 999);
$toplam = rand(1000, 5000);
$tarih = date('Y-m-d H:i:s');
$approval_status = 'pending';
$sozlesme_id = 5; // Default contract ID
$order_status = 1; 
$show_sozlesme_footer = 1;

// Corrected columns based on schema check
$sql = "INSERT INTO ogteklif2 (musteriadi, hazirlayanid, geneltoplam, tekliftarihi, approval_status, sozlesme_id, order_status, show_sozlesme_footer) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $db->prepare($sql);

if (!$stmt) {
    die(json_encode(["status" => "error", "message" => "Prepare failed: " . $db->error]));
}

// Bind (s s d s s i i i)
// musteriadi (s), hazirlayanid (s - assuming text/int), geneltoplam (string/decimal in schema it said text/varchar?), tekliftarihi (text), approval_status (s), sozlesme_id (i), order_status (i), show_sozlesme_footer (i)
// Note: Schema said tltutar/geneltoplam is 'text'. Safest to bind as string or double. Let's use string for 'text' types.
$stmt->bind_param("ssisssii", $cari, $temsilci, $toplam, $tarih, $approval_status, $sozlesme_id, $order_status, $show_sozlesme_footer);

if ($stmt->execute()) {
    $new_id = $stmt->insert_id;
    echo json_encode([
        "status" => "success",
        "message" => "Test offer created successfully.",
        "teklif_id" => $new_id,
        "details" => [
            "cari" => $cari,
            "tutar" => $toplam
        ]
    ], JSON_PRETTY_PRINT);
} else {
    echo json_encode(["status" => "error", "message" => "Execute failed: " . $stmt->error]);
}

$stmt->close();
$db->close();
?>
