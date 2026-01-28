<?php
// api/get_queue_status.php
require_once __DIR__ . '/../fonk.php';
header('Content-Type: application/json');

$conn = $db;
if (!$conn) {
    echo json_encode(['status' => false, 'message' => 'DB Connection Error']);
    exit;
}

$limit = (int)($_GET['limit'] ?? 20);

$sql = "SELECT q.*, t.musteriadi, t.tekliftarihi, t.teklifkodu, t.geneltoplam, t.currency, y.adsoyad as hazirlayan
        FROM logo_transfer_queue q
        LEFT JOIN ogteklif2 t ON q.offer_id = t.id
        LEFT JOIN yonetici y ON t.hazirlayanid = y.yonetici_id
        ORDER BY q.created_at DESC 
        LIMIT ?";


try {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }

    echo json_encode(['status' => true, 'data' => $items]);
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['status' => false, 'message' => 'SQL Error: ' . $e->getMessage()]);
}

$conn->close();
