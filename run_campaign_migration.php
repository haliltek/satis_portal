<?php
// Tabloları oluştur
require_once __DIR__ . "/fonk.php";

global $baglan;

$sql = file_get_contents(__DIR__ . '/sql/20260114_create_custom_campaigns.sql');

// Çoklu sorguları ayır ve çalıştır
$queries = explode(';', $sql);

foreach ($queries as $query) {
    $query = trim($query);
    if (empty($query)) continue;
    
    echo "Executing: " . substr($query, 0, 50) . "...\n";
    
    if ($baglan->query($query)) {
        echo "✓ Success\n";
    } else {
        echo "✗ Error: " . $baglan->error . "\n";
    }
}

echo "\n=== Tablolar başarıyla oluşturuldu! ===\n";

// Kontrol et
$result = $baglan->query("SELECT COUNT(*) as cnt FROM custom_campaigns");
$row = $result->fetch_assoc();
echo "Toplam kampanya sayısı: " . $row['cnt'] . "\n";

$result = $baglan->query("SELECT COUNT(*) as cnt FROM custom_campaign_products");
$row = $result->fetch_assoc();
echo "Toplam ürün sayısı: " . $row['cnt'] . "\n";
?>
