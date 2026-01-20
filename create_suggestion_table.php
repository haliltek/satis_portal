<?php
include 'fonk.php';
$db = local_database();
if (!$db) {
    die("Connection failed: " . mysqli_connect_error());
}

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($db->query($sql)) {
    echo "Table 'fiyat_onerileri' created successfully or already exists.";
} else {
    echo "Error creating table: " . $db->error;
}
?>
