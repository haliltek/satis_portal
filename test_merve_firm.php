<?php
// test_merve_firm.php
include "include/fonksiyon.php";

header('Content-Type: text/plain; charset=utf-8');

echo "Connecting to MERVE1...\n";
merve1_veritabani();
global $merve1_db;

if (!$merve1_db) {
    echo "Connection Failed.\n";
    exit;
}

echo "Connected. searching for LG_..._CLCARD tables...\n";

try {
    $sql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_NAME LIKE 'LG_%_CLCARD'";
    $stmt = $merve1_db->query($sql);
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tables)) {
        echo "No CLCARD tables found.\n";
    } else {
        echo "Found Tables:\n";
        foreach ($tables as $t) {
            echo "- $t\n";
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
