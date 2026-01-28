<?php
require_once 'include/vt.php';

try {
    $pdo = new PDO("mysql:host={$sql_details['host']};dbname={$sql_details['db']};charset=utf8mb4", $sql_details['user'], $sql_details['pass']);
    
    $stmt = $pdo->query("SELECT category_name FROM custom_campaigns WHERE is_active = 1");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Active Campaign Categories:\n";
    foreach ($categories as $cat) {
        echo "- $cat\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
