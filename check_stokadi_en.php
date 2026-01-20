<?php
// stokadi_en alanının dolu olup olmadığını kontrol etme scripti

require 'include/vt.php';

$db = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
$db->set_charset('utf8');

echo "stokadi_en Alanı Kontrolü\n";
echo "=========================\n\n";

$result = $db->query('SELECT stokkodu, stokadi, stokadi_en FROM urunler WHERE stokadi_en IS NOT NULL AND stokadi_en != "" LIMIT 10');

if ($result && $result->num_rows > 0) {
    echo "stokadi_en dolu kayıtlar:\n";
    echo str_repeat("-", 100) . "\n";
    while ($row = $result->fetch_assoc()) {
        echo "CODE: " . $row['stokkodu'] . "\n";
        echo "  TR: " . substr($row['stokadi'], 0, 50) . "\n";
        echo "  EN: " . substr($row['stokadi_en'], 0, 50) . "\n\n";
    }
} else {
    echo "stokadi_en alanı boş görünüyor.\n";
    echo "Senkronizasyon çalıştırıldı mı kontrol edin.\n";
}

// Toplam sayıları göster
$total = $db->query('SELECT COUNT(*) as total FROM urunler')->fetch_assoc()['total'];
$withEn = $db->query('SELECT COUNT(*) as total FROM urunler WHERE stokadi_en IS NOT NULL AND stokadi_en != ""')->fetch_assoc()['total'];

echo "\nİstatistikler:\n";
echo "  Toplam ürün: $total\n";
echo "  stokadi_en dolu: $withEn\n";
echo "  stokadi_en boş: " . ($total - $withEn) . "\n";

$db->close();

