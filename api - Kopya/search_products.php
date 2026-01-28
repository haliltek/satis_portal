<?php
// api/search_products.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../fonk.php';

global $db;

$query = $_GET['query'] ?? '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;

if (strlen($query) < 2) {
    echo json_encode(['success' => false, 'message' => 'En az 2 karakter girin']);
    exit;
}

try {
    $searchTerm = '%' . $query . '%';
    
    $stmt = $db->prepare("
        SELECT stokkodu, stokadi, olcubirimi 
        FROM urunler 
        WHERE stokadi LIKE ? OR stokkodu LIKE ?
        ORDER BY stokadi ASC
        LIMIT ?
    ");
    
    $stmt->bind_param("ssi", $searchTerm, $searchTerm, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'products' => $products
    ]);

} catch (Exception $e) {
    error_log("search_products.php Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Arama sırasında hata oluştu']);
}
