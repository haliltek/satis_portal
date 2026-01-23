<?php
// Migration script to add web_fiyat and maliyet columns
require_once 'include/vt.php';

try {
    // PDO connection
    $pdo = new PDO(
        "mysql:host={$sql_details['host']};dbname={$sql_details['db']};charset=utf8mb4",
        $sql_details['user'],
        $sql_details['pass'],
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
    
    echo "<h2>Migration: Adding web_fiyat and maliyet columns</h2>";
    
    // Check if columns already exist
    $stmt = $pdo->query("SHOW COLUMNS FROM urunler LIKE 'web_fiyat'");
    $webFiyatExists = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW COLUMNS FROM urunler LIKE 'maliyet'");
    $maliyetExists = $stmt->rowCount() > 0;
    
    if ($webFiyatExists && $maliyetExists) {
        echo "<p style='color: orange;'>✓ Columns already exist. No changes needed.</p>";
    } else {
        // Read and execute SQL file
        $sqlFile = __DIR__ . '/sql/20260122_add_web_fiyat_maliyet.sql';
        $sql = file_get_contents($sqlFile);
        
        // Remove IF NOT EXISTS syntax as it's not supported in all MySQL versions
        $sql = "ALTER TABLE `urunler` 
                ADD COLUMN `web_fiyat` DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Web/App Fiyatı',
                ADD COLUMN `maliyet` DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Maliyet Fiyatı'";
        
        if (!$webFiyatExists && !$maliyetExists) {
            $pdo->exec($sql);
            echo "<p style='color: green;'>✓ Successfully added both columns!</p>";
        } elseif (!$webFiyatExists) {
            $pdo->exec("ALTER TABLE `urunler` ADD COLUMN `web_fiyat` DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Web/App Fiyatı'");
            echo "<p style='color: green;'>✓ Successfully added web_fiyat column!</p>";
        } elseif (!$maliyetExists) {
            $pdo->exec("ALTER TABLE `urunler` ADD COLUMN `maliyet` DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Maliyet Fiyatı'");
            echo "<p style='color: green;'>✓ Successfully added maliyet column!</p>";
        }
    }
    
    // Verify the columns
    echo "<h3>Verification:</h3>";
    $stmt = $pdo->query("DESCRIBE urunler");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        $highlight = ($col['Field'] === 'web_fiyat' || $col['Field'] === 'maliyet') ? " style='background-color: #d4edda;'" : "";
        echo "<tr{$highlight}>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><p><a href='urunlerlogo.php'>Go to Urunler Logo Page</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
