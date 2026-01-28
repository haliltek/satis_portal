<?php
// api/add_maliyet_column.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (file_exists('../fonk.php')) {
    include "../fonk.php";
} elseif (file_exists('fonk.php')) {
    include "fonk.php";
} else {
    die("fonk.php bulunamadı");
}

global $db;
header('Content-Type: text/plain; charset=utf-8');

echo "=== MALIYET SUTUNU EKLEME ===\n";

// Sütun var mı kontrol et
$check = $db->query("SHOW COLUMNS FROM ozel_fiyat_urunler LIKE 'maliyet'");

if ($check && $check->num_rows > 0) {
    echo "maliyet sütunu ZATEN VAR.\n";
} else {
    echo "Sütun yok, ekleniyor...\n";
    $sql = "ALTER TABLE ozel_fiyat_urunler ADD COLUMN maliyet DECIMAL(15, 4) DEFAULT 0.00 AFTER ozel_fiyat";
    if ($db->query($sql)) {
        echo "BAŞARILI: maliyet sütunu eklendi.\n";
    } else {
        echo "HATA: " . $db->error . "\n";
    }
}

// Kontrol
$check2 = $db->query("SHOW COLUMNS FROM ozel_fiyat_urunler");
while($row = $check2->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

echo "=== ISLEM BITTI ===";
?>
