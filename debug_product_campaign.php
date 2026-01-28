<?php
require_once 'include/vt.php';

try {
    $pdo = new PDO("mysql:host={$sql_details['host']};dbname={$sql_details['db']};charset=utf8mb4", $sql_details['user'], $sql_details['pass']);
    $stmt = $pdo->prepare("SELECT * FROM kampanya_ozel_fiyatlar WHERE stok_kodu = ?");
    $stmt->execute(['02213027']);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Product Data:\n";
    print_r($result);
    
    // Check custom_campaigns for this category
    if ($result && isset($result['kategori'])) {
        $stmt2 = $pdo->prepare("SELECT * FROM custom_campaigns WHERE category_name = ?");
        $stmt2->execute([$result['kategori']]);
        $campaign = $stmt2->fetch(PDO::FETCH_ASSOC);
        echo "\nCampaign Rule:\n";
        print_r($campaign);
    } else {
        echo "\nProduct not found or has no category.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
