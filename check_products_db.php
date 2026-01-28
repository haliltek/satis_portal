<?php
require_once 'include/vt.php';

try {
    $pdo = new PDO("mysql:host={$sql_details['host']};dbname={$sql_details['db']};charset=utf8mb4", $sql_details['user'], $sql_details['pass']);
    
    $products = ['02213027', '021132'];
    
    foreach ($products as $code) {
        echo "Checking product: $code\n";
        $stmt = $pdo->prepare("SELECT * FROM kampanya_ozel_fiyatlar WHERE stok_kodu = ?");
        $stmt->execute([$code]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo "Found in kampanya_ozel_fiyatlar:\n";
            print_r($result);
        } else {
            echo "NOT FOUND in kampanya_ozel_fiyatlar\n";
        }
        echo "-------------------\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
