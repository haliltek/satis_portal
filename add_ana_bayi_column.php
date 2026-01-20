<?php
// Ana bayi kolonu ekle
$config = require __DIR__ . '/config/config.php';
$db = $config['db'];
$conn = new mysqli($db['host'], $db['user'], $db['pass'], $db['name'], $db['port']);
$conn->set_charset("utf8mb4");

$sql = file_get_contents(__DIR__ . '/sql/20260114_add_ana_bayi_column.sql');

if ($conn->multi_query($sql)) {
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
}

if ($conn->error) {
    echo "HATA: " . $conn->error . "\n";
} else {
    echo "✓ Ana bayi kolonu eklendi!\n";
    
    // Test: Ertek ana bayi mi?
    $result = $conn->query("SELECT id, cari_kodu, ana_bayi FROM ogteklif2 WHERE cari_kodu = '120.01.E04' LIMIT 1");
    if ($result && $row = $result->fetch_assoc()) {
        echo "\nErtek kontrolü:\n";
        echo "ID: " . $row['id'] . "\n";
        echo "Cari: " . $row['cari_kodu'] . "\n";
        echo "Ana Bayi: " . ($row['ana_bayi'] == 1 ? "EVET ✓" : "HAYIR") . "\n";
    }
}

$conn->close();
?>
