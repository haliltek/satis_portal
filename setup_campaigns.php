<?php
// Config dosyasından bağlantı al
$config = require __DIR__ . '/config/config.php';
$db = $config['db'];

// MySQLi bağlantısı
$conn = new mysqli($db['host'], $db['user'], $db['pass'], $db['name'], $db['port']);

if ($conn->connect_error) {
    die("Bağlantı hatası: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

echo "Veritabanı: {$db['name']}\n";
echo "Bağlantı başarılı!\n\n";

// SQL dosyasını oku
$sql = file_get_contents(__DIR__ . '/sql/20260114_create_custom_campaigns.sql');

// Çoklu sorguları çalıştır
if ($conn->multi_query($sql)) {
    do {
        // Sonuçları temizle
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
}

if ($conn->error) {
    echo "HATA: " . $conn->error . "\n";
} else {
    echo "✓ Tablolar başarıyla oluşturuldu!\n\n";
}

// Kontrol et
$result = $conn->query("SELECT COUNT(*) as cnt FROM custom_campaigns");
if ($result) {
    $row = $result->fetch_assoc();
    echo "✓ Toplam kampanya sayısı: " . $row['cnt'] . "\n";
}

$result = $conn->query("SELECT COUNT(*) as cnt FROM custom_campaign_products");
if ($result) {
    $row = $result->fetch_assoc();
    echo "✓ Toplam ürün sayısı: " . $row['cnt'] . "\n";
}

$result = $conn->query("SELECT COUNT(*) as cnt FROM custom_campaign_rules");
if ($result) {
    $row = $result->fetch_assoc();
    echo "✓ Toplam kural sayısı: " . $row['cnt'] . "\n";
}

// Kampanya detayını göster
echo "\n=== Demo Kampanya ===\n";
$result = $conn->query("SELECT * FROM custom_campaigns LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    echo "Ad: " . $row['name'] . "\n";
    echo "Müşteri: " . $row['customer_code'] . " (" . $row['customer_type'] . ")\n";
    echo "Min Adet: " . $row['min_quantity'] . "\n";
    echo "Min Tutar: " . $row['min_total_amount'] . " €\n";
    echo "Fallback İskonto: %" . $row['fallback_discount'] . "\n";
}

$conn->close();
?>
