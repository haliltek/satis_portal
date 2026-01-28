<?php
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/../../include/vt.php';

try {
    $pdo = new PDO(
        "mysql:host={$sql_details['host']};dbname={$sql_details['db']};charset=utf8mb4",
        $sql_details['user'],
        $sql_details['pass'],
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );

    echo "Listing ALL active rules...\n";
    $stmt = $pdo->query("SELECT * FROM custom_campaigns WHERE is_active = 1");
    $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($rules as $r) {
        echo "ID: {$r['id']} | Name: [{$r['category_name']}] | Qty: {$r['min_quantity']} | Amt: {$r['min_total_amount']}\n";
    }
    
    if (empty($rules)) echo "No active rules found.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
