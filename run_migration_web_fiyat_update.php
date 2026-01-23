<?php
// Doğrudan bağlantı (Config include sorunu yaşamamak için)
$host = 'localhost';
$user = 'root';
$pass = ''; 
$name = 'b2bgemascom_teklif';

try {
    $conn = new mysqli($host, $user, $pass, $name);
    $conn->set_charset("utf8mb4");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "<h1>Kampanya Güncelleme Migrasyonu</h1>";

    // 0. Sütun Kontrolü ve Ekleme
    echo "<h2>0. Tablo Yapısı Kontrolü</h2>";
    $params = [
        'min_amount' => "ADD COLUMN min_amount DECIMAL(15,2) DEFAULT 0 AFTER min_quantity",
        'min_purchase_amount' => "ADD COLUMN min_purchase_amount DECIMAL(15,2) DEFAULT 0 AFTER min_amount", 
        'is_extra_discount' => "ADD COLUMN is_extra_discount TINYINT(1) DEFAULT 0 AFTER active",
        'discount_rate' => "ADD COLUMN discount_rate DECIMAL(5,2) DEFAULT 0 AFTER name" // Discount rate sütununu da ekle
    ];

    foreach ($params as $col => $sql) {
        $check = $conn->query("SHOW COLUMNS FROM custom_campaigns LIKE '$col'");
        if ($check->num_rows == 0) {
            if ($conn->query("ALTER TABLE custom_campaigns $sql") === TRUE) {
                 echo "<p>✅ Sütun Eklendi: <b>$col</b></p>";
            } else {
                 echo "<p>❌ Hata ($col): " . $conn->error . "</p>";
            }
        } else {
            echo "<p>ℹ️ Sütun zaten var: <b>$col</b></p>";
        }
    }
    
    // 1. TEMİZLİK EKİPMANLARI Güncelleme (ID 14)
    // Min Tutar: 1500 EUR, Min Adet: 0
    echo "<h2>1. Temizlik Ekipmanları Güncelleme</h2>";
    $sql1 = "UPDATE custom_campaigns 
             SET min_amount = 1500, min_quantity = 0, min_purchase_amount = 1500 
             WHERE id = 14";
             
    if ($conn->query($sql1) === TRUE) {
        echo "<p>✅ Temizlik Ekipmanları (ID: 14) güncellendi: Min Tutar = 1500</p>";
    } else {
        echo "<p>❌ Hata (ID 14): " . $conn->error . "</p>";
    }

    // 2. ANA BAYİ EK İSKONTO Güncelleme/Ekleme
    echo "<h2>2. Ana Bayi Ek İskonto Kampanyası</h2>";
    // Ek İskonto Kampanyasını Bul
    $result = $conn->query("SELECT id FROM custom_campaigns WHERE name LIKE '%Ana Bayi Ek İskonto%' OR is_extra_discount = 1 LIMIT 1");
    
    if ($result->num_rows > 0) {
        // Varsa güncelle
        $row = $result->fetch_assoc();
        $id = $row['id'];
        $sql2 = "UPDATE custom_campaigns 
                 SET min_amount = 5000, 
                     min_purchase_amount = 5000,
                     is_extra_discount = 1, 
                     discount_rate = 5.00
                 WHERE id = $id";
        if ($conn->query($sql2) === TRUE) {
            echo "<p>✅ Ana Bayi Ek İskonto (ID: $id) güncellendi: Min Tutar = 5000</p>";
        }
    } else {
        // Yoksa ekle
        $sql3 = "INSERT INTO custom_campaigns (name, category_name, min_quantity, min_amount, min_purchase_amount, discount_rate, customer_type, active, is_extra_discount, priority)
                 VALUES ('Ana Bayi Ek İskonto', 'Tüm Kategoriler', 0, 5000, 5000, 5.00, 'ana_bayi', 1, 1, 100)";
                 
        if ($conn->query($sql3) === TRUE) {
            echo "<p>✅ Ana Bayi Ek İskonto kampanyası OLUŞTURULDU (Min Tutar: 5000)</p>";
        } else {
            echo "<p>❌ Hata (Insert): " . $conn->error . "</p>";
        }
    }

} catch (Exception $e) {
    echo "Hata: " . $e->getMessage();
}
?>
