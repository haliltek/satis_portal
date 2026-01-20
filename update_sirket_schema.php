<?php
try {
    $db = new PDO("mysql:host=localhost;dbname=b2bgemascom_teklif;charset=utf8", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // List of columns to check/add
    $columns = [
        'ciro_hedefi' => "ALTER TABLE sirket ADD COLUMN ciro_hedefi DECIMAL(15,2) DEFAULT 0.00",
        'anlasilan_iskonto' => "ALTER TABLE sirket ADD COLUMN anlasilan_iskonto DECIMAL(5,2) DEFAULT 0.00",
        'ozel_risk_notu' => "ALTER TABLE sirket ADD COLUMN ozel_risk_notu TEXT",
        'manual_data_updated_at' => "ALTER TABLE sirket ADD COLUMN manual_data_updated_at DATETIME",
        'manual_data_updated_by' => "ALTER TABLE sirket ADD COLUMN manual_data_updated_by INT"
    ];

    // Get current columns
    $stmt = $db->query("DESCRIBE sirket");
    $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($columns as $col => $sql) {
        if (!in_array($col, $existingColumns)) {
            echo "Adding column: $col\n";
            $db->exec($sql);
        } else {
            echo "Column exists: $col\n";
        }
    }
    
    echo "Done.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
