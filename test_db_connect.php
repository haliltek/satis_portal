<?php
try {
    $dsn = "mysql:host=89.43.31.214;port=3306;dbname=gemas_pool_technology;charset=utf8";
    $username = "gemas_mehmet";
    $password = "2261686Me!";
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    $pdo = new PDO($dsn, $username, $password, $options);
    $output = "Connected successfully\n";

    $output .= "Tables:\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $output .= print_r($tables, true);

    // Try to find a product table
    $productTable = null;
    foreach ($tables as $table) {
        if (strpos($table, 'product') !== false || strpos($table, 'urun') !== false) {
            $productTable = $table;
            $output .= "Found potential product table: $table\n";
            // Prefer 'urunler' if multiple
            if ($table === 'urunler') break; 
        }
    }
    
    if ($productTable) {
         $output .= "Columns in $productTable:\n";
         $stmt = $pdo->query("DESCRIBE $productTable");
         $output .= print_r($stmt->fetchAll(), true);
         
         // Check a sample row
         $output .= "Sample row from $productTable:\n";
         $stmt = $pdo->query("SELECT * FROM $productTable LIMIT 1");
         $output .= print_r($stmt->fetch(), true);
    }
    
    file_put_contents('db_info.txt', $output);
    echo "Done writing to db_info.txt";

} catch (PDOException $e) {
    file_put_contents('db_info.txt', "Connection failed: " . $e->getMessage());
}
