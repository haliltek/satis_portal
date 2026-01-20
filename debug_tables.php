<?php
// debug_tables.php
require_once "include/fonksiyon.php";

header('Content-Type: text/plain; charset=utf-8');

// Connect to Logo DB
gempa_logo_veritabani();

if (!isset($gempa_logo_db) || $gempa_logo_db === null) {
    die("Gempa Logo veritabanı bağlantısı başarısız.");
}

try {
    // MSSQL system views to list tables
    $sql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME LIKE 'LG_%_PAYTRANS' ORDER BY TABLE_NAME";
    $stmt = $gempa_logo_db->prepare($sql);
    $stmt->execute();
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tables)) {
        echo "Hiçbir PAYTRANS tablosu bulunamadı.\n";
    } else {
        echo "Bulunan Tablolar:\n";
        foreach ($tables as $t) {
            echo $t . "\n";
        }
    }

} catch (PDOException $e) {
    echo "SQL Hatası: " . $e->getMessage();
}
