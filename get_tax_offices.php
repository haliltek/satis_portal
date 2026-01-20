<?php
include "fonk.php";
oturumkontrol();
header('Content-Type: application/json; charset=utf-8');

global $logoService, $config;

$firmNr = isset($_GET['firmnr']) ? (int)$_GET['firmnr'] : ($config['firmNr'] ?? 0);

try {
    $items = $logoService->getTaxOffices($firmNr);
    echo json_encode(['success' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log('get_tax_offices.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Logo API error'], JSON_UNESCAPED_UNICODE);
}

