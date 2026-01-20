<?php
include "fonk.php";

echo "<h1>Comprehensive Database Fixer</h1>";

// 1. Fix Missing Columns in 'sirket'
echo "<h2>1. Checking 'sirket' table columns...</h2>";
$cols = $db->query("SHOW COLUMNS FROM sirket");
$found_payplan = false;
while($row = $cols->fetch_assoc()) {
    if($row['Field'] == 'payplan_code') $found_payplan = true;
}

if (!$found_payplan) {
    echo "Adding payplan columns... ";
    $sql = "ALTER TABLE sirket ADD COLUMN payplan_code VARCHAR(50) DEFAULT NULL, ADD COLUMN payplan_def VARCHAR(255) DEFAULT NULL";
    if ($db->query($sql)) {
        echo "<span style='color:green'>SUCCESS</span><br>";
    } else {
        echo "<span style='color:red'>FAILED: " . $db->error . "</span><br>";
    }
} else {
    echo "<span style='color:green'>Columns already exist.</span><br>";
}

// 2. Fix Collation Mismatch
echo "<h2>2. Standardizing Collations (utf8mb4_unicode_ci)</h2>";
$tables = ['urun_fiyat_log', 'yonetici', 'urunler', 'sirket', 'ogteklif2', 'ogteklifurun2'];

foreach ($tables as $table) {
    echo "Converting table <b>$table</b>... ";
    
    // First convert table default
    $sql1 = "ALTER TABLE $table CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    
    if ($db->query($sql1)) {
        echo "<span style='color:green'>OK</span><br>";
    } else {
        echo "<span style='color:red'>FAILED: " . $db->error . "</span><br>";
    }
}

}

// 3. Ensure 'fiyat_onerileri' table exists
echo "<h2>3. Verifying 'fiyat_onerileri' Table</h2>";
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
    echo "<span style='color:green'>Table 'fiyat_onerileri' verified/created!</span><br>";
} else {
    echo "<span style='color:red'>Table creation FAILED: " . $db->error . "</span><br>";
}

echo "<h2>Done.</h2>";
?>
