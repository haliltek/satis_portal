<?php
require_once 'config/config.php';
$config = require 'config/config.php';

// Database connection
$db = new mysqli(
    $config['db']['host'],
    $config['db']['user'],
    $config['db']['pass'],
    $config['db']['name']
);

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$db->set_charset("utf8mb4");

echo "<h2>is_active kolonu ekleniyor...</h2>";

// SQL dosyasını oku ve çalıştır
$sql = file_get_contents(__DIR__ . '/sql/add_is_active_column.sql');

if ($db->query($sql)) {
    echo "<p style='color: green;'>✓ is_active kolonu başarıyla eklendi!</p>";
} else {
    echo "<p style='color: red;'>✗ Hata: " . $db->error . "</p>";
}

// Tüm kampanyaları aktif yap
$db->query("UPDATE `custom_campaigns` SET `is_active` = 1");
echo "<p style='color: green;'>✓ Tüm kampanyalar aktif yapıldı!</p>";

echo "<hr>";
echo "<h3>Tekrar doğrulama için <a href='verify_categories.php'>buraya tıklayın</a></h3>";

$db->close();
