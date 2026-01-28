<?php
require_once 'include/vt.php';

try {
    $pdo = new PDO("mysql:host={$sql_details['host']};dbname={$sql_details['db']};charset=utf8mb4", $sql_details['user'], $sql_details['pass']);
    
    $stmt = $pdo->prepare("SELECT * FROM custom_campaigns WHERE category_name LIKE '%LED%'");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "LED Campaign Config:\n";
    print_r($results);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
