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

    echo "--- RULES IN DATABASE ---\n";
    $stmt = $pdo->query("SELECT id, category_name, min_quantity, min_total_amount FROM custom_campaigns WHERE is_active = 1");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Rule: [{$row['category_name']}] | Qty: {$row['min_quantity']} | Amt: {$row['min_total_amount']}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
