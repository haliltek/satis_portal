<?php
include "fonk.php";

echo "<h1>Schema Fixer</h1>";

// Create fiyat_onerileri table
$sql = "CREATE TABLE IF NOT EXISTS fiyat_onerileri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stok_kodu VARCHAR(100),
    urun_id INT,
    mevcut_fiyat_yurtici DECIMAL(15, 2),
    mevcut_fiyat_export DECIMAL(15, 2),
    oneri_fiyat_yurtici DECIMAL(15, 2),
    oneri_fiyat_export DECIMAL(15, 2),
    oneri_not TEXT,
    yonetici_id INT,
    tarih DATETIME DEFAULT CURRENT_TIMESTAMP,
    durum ENUM('Beklemede', 'Onaylandı', 'Reddedildi') DEFAULT 'Beklemede'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($db->query($sql)) {
    echo "<h2 style='color:green'>Table 'fiyat_onerileri' created/verified!</h2>";
} else {
    echo "<h2 style='color:red'>Error creating table: " . $db->error . "</h2>";
}
?>
