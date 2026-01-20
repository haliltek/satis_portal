<?php
// Config ve database bağlantısı
$config = require __DIR__ . '/config/config.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/classes/DatabaseManager.php';

use Proje\DatabaseManager;

$dbConfig = [
    'host' => $config['db']['host'],
    'port' => $config['db']['port'],
    'user' => $config['db']['user'],
    'pass' => $config['db']['pass'],
    'name' => $config['db']['name'],
];
$dbManager = new DatabaseManager($dbConfig);
$conn = $dbManager->getConnection();

// Sütunları kontrol et
$cols = [];
$res = $conn->query("SHOW COLUMNS FROM ogteklif2");
while ($row = $res->fetch_assoc()) {
    $cols[] = $row['Field'];
}

$alter = [];
if (!in_array('currency', $cols)) {
    $alter[] = "ADD COLUMN currency VARCHAR(10) DEFAULT 'TL'";
}
if (!in_array('shipping_agent', $cols)) {
    $alter[] = "ADD COLUMN shipping_agent VARCHAR(50) DEFAULT 'GEMPA'";
}

if (!empty($alter)) {
    $sql = "ALTER TABLE ogteklif2 " . implode(', ', $alter);
    if ($conn->query($sql)) {
        echo "Sütunlar başarıyla eklendi: " . implode(', ', $alter) . "\n";
    } else {
        echo "Hata: " . $conn->error . "\n";
    }
} else {
    echo "Sütunlar zaten mevcut.\n";
}
