<?php
// Test için stokadi_en alanını manuel doldurma scripti
// Bu script sadece test amaçlıdır ve mevcut verileri değiştirmez

require 'include/vt.php';

$db = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
$db->set_charset('utf8');

// Test için birkaç ürün seçin ve İngilizce adlarını manuel olarak girin
// Önce veritabanından ürün kodlarını alalım
$result = $db->query("SELECT stokkodu, stokadi FROM urunler WHERE stokkodu IS NOT NULL AND stokkodu != '' LIMIT 10");
$products = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// İlk 5 ürün için test verisi oluştur (İngilizce adları manuel olarak düzenleyebilirsiniz)
$testProducts = [];
foreach (array_slice($products, 0, 5) as $product) {
    // Test için Türkçe adın başına "EN: " ekliyoruz (gerçek İngilizce adlar Logo'dan gelecek)
    $testProducts[$product['stokkodu']] = 'EN: ' . $product['stokadi'];
}

if (empty($testProducts)) {
    echo "Test için ürün ekleyin. Script içindeki \$testProducts dizisine ürün kodları ve İngilizce adlarını ekleyin.\n";
    echo "\nÖrnek kullanım:\n";
    echo "\$testProducts = [\n";
    echo "    'ABC123' => 'English Product Name',\n";
    echo "    'XYZ789' => 'Another English Name',\n";
    echo "];\n";
    exit;
}

$updated = 0;
$errors = [];

foreach ($testProducts as $stokkodu => $stokadiEn) {
    $stmt = $db->prepare("UPDATE urunler SET stokadi_en = ? WHERE stokkodu = ?");
    $stmt->bind_param("ss", $stokadiEn, $stokkodu);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $updated++;
            echo "✓ {$stokkodu} -> {$stokadiEn}\n";
        } else {
            $errors[] = "Ürün bulunamadı: {$stokkodu}";
        }
    } else {
        $errors[] = "Hata ({$stokkodu}): " . $stmt->error;
    }
    $stmt->close();
}

echo "\nToplam güncellenen: {$updated}\n";

if (!empty($errors)) {
    echo "\nHatalar:\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
}

$db->close();

