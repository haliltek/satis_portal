<?php
require_once 'include/vt.php';

try {
    $pdo = new PDO("mysql:host={$sql_details['host']};dbname={$sql_details['db']};charset=utf8mb4", $sql_details['user'], $sql_details['pass']);
    
    // User claims this product exists
    $code = '021714';
    
    echo "Checking product: $code\n";
    $stmt = $pdo->prepare("SELECT * FROM kampanya_ozel_fiyatlar WHERE stok_kodu = ?");
    $stmt->execute([$code]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "Found in kampanya_ozel_fiyatlar:\n";
        print_r($result);
        
        // Also check if there is a matching campaign rule for this category
        $category = $result['kategori'];
        echo "\nChecking campaign rules for category: '$category'\n";
        
        $stmt2 = $pdo->prepare("SELECT * FROM custom_campaigns WHERE category_name = ?");
        $stmt2->execute([$category]);
        $rule = $stmt2->fetch(PDO::FETCH_ASSOC);
        
        if ($rule) {
            echo "Found matching rule in custom_campaigns:\n";
            print_r($rule);
        } else {
            echo "WARNING: No matching rule found in custom_campaigns for category '$category'!\n";
            echo "This confirms why it might not work even if the product is in the price list.\n";
            
            // List all categories to see if there is a mismatch
            echo "\nAvailable Categories in custom_campaigns:\n";
            $stmt3 = $pdo->query("SELECT category_name FROM custom_campaigns");
            while ($row = $stmt3->fetch(PDO::FETCH_ASSOC)) {
                echo "- " . $row['category_name'] . "\n";
            }
        }
        
    } else {
        echo "NOT FOUND in kampanya_ozel_fiyatlar (This would be strange given user feedback)\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
