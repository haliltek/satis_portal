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

    echo "--- SCHEMA: custom_campaigns ---\n";
    $stmt = $pdo->query("DESCRIBE custom_campaigns");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($columns as $c) {
        echo "Field: {$c['Field']} | Type: {$c['Type']} | Null: {$c['Null']} | Default: {$c['Default']}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
