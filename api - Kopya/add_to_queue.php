<?php
// api/add_to_queue.php
require_once __DIR__ . '/../fonk.php';
header('Content-Type: application/json');

// $db is global from fonk.php
$conn = $db;
if (!$conn) {
    echo json_encode(['status' => false, 'message' => 'DB Connection Error']);
    exit;
}

$offer_id = (int)($_POST['offer_id'] ?? 0);
$admin_id = (int)($_SESSION['yonetici_id'] ?? 0);

if ($offer_id <= 0) {
    echo json_encode(['status' => false, 'message' => 'Geçersiz Teklif ID']);
    exit;
}

// Check if already successful in queue OR ogteklif2 table
$checkSql = "SELECT q.id FROM logo_transfer_queue q WHERE q.offer_id = ? AND q.status = 'success'";
$stmt = $conn->prepare($checkSql);
$stmt->bind_param("i", $offer_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['status' => false, 'message' => 'Bu teklif zaten kuyruk üzerinden Logo\'ya aktarılmış.']);
    exit;
}
$stmt->close();

$checkLogoSql = "SELECT id FROM ogteklif2 WHERE id = ? AND logo_transfer_status = 'Aktarıldı'";
$stmt = $conn->prepare($checkLogoSql);
$stmt->bind_param("i", $offer_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['status' => false, 'message' => 'Bu teklif zaten Logo\'ya aktarılmış (logo_transfer_status: Aktarıldı).']);
    exit;
}
$stmt->close();


// Check if pending or processing
$ongoingSql = "SELECT id FROM logo_transfer_queue WHERE offer_id = ? AND status IN ('pending', 'processing')";
$stmt = $conn->prepare($ongoingSql);
$stmt->bind_param("i", $offer_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['status' => false, 'message' => 'Bu teklif zaten kuyrukta işleniyor veya bekliyor.']);
    exit;
}
$stmt->close();

// Add to queue
$insertSql = "INSERT INTO logo_transfer_queue (offer_id, admin_id, status) VALUES (?, ?, 'pending')";
$stmt = $conn->prepare($insertSql);
$stmt->bind_param("ii", $offer_id, $admin_id);
if ($stmt->execute()) {
    echo json_encode(['status' => true, 'message' => 'Teklif aktarım kuyruğuna eklendi.', 'queue_id' => $stmt->insert_id]);
} else {
    echo json_encode(['status' => false, 'message' => 'Kuyruğa ekleme hatası: ' . $conn->error]);
}
$stmt->close();
$conn->close();
