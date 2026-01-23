<?php
// api/kampanya/get_special_prices.php
// Verilen ürün kodları için özel fiyatları döndürür

header('Content-Type: application/json; charset=utf-8');

session_start();
require_once __DIR__ . '/../../include/vt.php';

try {
    $pdo = new PDO(
        "mysql:host={$sql_details['host']};dbname={$sql_details['db']};charset=utf8mb4",
        $sql_details['user'],
        $sql_details['pass'],
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
    
    // POST'tan ürün kodlarını al (array veya JSON string)
    $codes = $_POST['codes'] ?? [];
    if (is_string($codes)) {
        $codes = json_decode($codes, true);
    }
    
    if (!is_array($codes) || empty($codes)) {
        echo json_encode([]);
        exit;
    }
    
    // Özel fiyatları çek
    $placeholders = implode(',', array_fill(0, count($codes), '?'));
    $stmt = $pdo->prepare("
        SELECT stok_kodu, ozel_fiyat 
        FROM kampanya_ozel_fiyatlar 
        WHERE stok_kodu IN ($placeholders)
    ");
    $stmt->execute($codes);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Stok kodu => özel fiyat formatında döndür
    $prices = [];
    foreach ($results as $row) {
        $prices[$row['stok_kodu']] = floatval($row['ozel_fiyat']);
    }
    
    echo json_encode($prices);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
