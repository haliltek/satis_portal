<?php
require_once __DIR__ . '/../fonk.php';
oturumkontrol();
header('Content-Type: application/json; charset=utf-8');
$id = intval($_GET['id'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false]);
    exit;
}
$stmt = $db->prepare("SELECT LOGICALREF FROM urunler WHERE urun_id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$row) {
    echo json_encode(['success' => false]);
    exit;
}
$rate = $dbManager->getCampaignDiscountForProduct((int)$row['LOGICALREF']);
if ($rate === null) {
    $rate = 0.0;
}
echo json_encode(['success' => true, 'rate' => $rate]);
