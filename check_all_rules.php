<?php
require_once 'include/vt.php';

try {
    $pdo = new PDO("mysql:host={$sql_details['host']};dbname={$sql_details['db']};charset=utf8mb4", $sql_details['user'], $sql_details['pass']);
    
    $stmt = $pdo->prepare("SELECT category_name, min_quantity, min_amount, min_total_amount FROM custom_campaigns WHERE is_active = 1");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($rows as $row) {
        echo "Rule: {$row['category_name']} | Qty: {$row['min_quantity']} | Amt: {$row['min_amount']} | TotAmt: {$row['min_total_amount']}\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
