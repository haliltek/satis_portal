<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "b2bgemascom_teklif";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
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
    adsoyad VARCHAR(255),
    tarih DATETIME DEFAULT CURRENT_TIMESTAMP,
    durum ENUM('Beklemede', 'Onaylandı', 'Reddedildi') DEFAULT 'Beklemede'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
if ($conn->query($sql) === TRUE) { echo "SUCCESS"; } else { echo "Error: " . $conn->error; }
$conn->close();
?>
