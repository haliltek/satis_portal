<?php
// Hata raporlamayı aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Veritabanı bağlantısı
if (file_exists("include/vt.php")) {
    include "include/vt.php";
} elseif (file_exists("../../include/vt.php")) {
    include "../../include/vt.php";
} else {
    die("vt.php dosyası bulunamadı!");
}

// Eğer $sql_details yoksa, manuel bağlantı bilgileri
if (!isset($sql_details)) {
    // .env dosyasından veya manuel olarak
    $sql_details = [
        'host' => 'localhost',
        'user' => 'root',
        'pass' => '',
        'db' => 'gemas_db'
    ];
}

$db = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
if ($db->connect_error) {
    die("Bağlantı hatası: " . $db->connect_error);
}
$db->set_charset("utf8");

echo "<h2>teklif_decisions Tablo Kontrolü</h2>";

// 1. Tablo var mı?
$result = $db->query("SHOW TABLES LIKE 'teklif_decisions'");
if ($result->num_rows > 0) {
    echo "<p style='color:green;'>✅ teklif_decisions tablosu mevcut</p>";
    
    // 2. Tablo yapısını göster
    echo "<h3>Tablo Yapısı:</h3>";
    $structure = $db->query("DESCRIBE teklif_decisions");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 3. Kayıt sayısı
    $count = $db->query("SELECT COUNT(*) as total FROM teklif_decisions")->fetch_assoc();
    echo "<h3>Toplam Kayıt: {$count['total']}</h3>";
    
    // 4. Son 10 kayıt
    if ($count['total'] > 0) {
        echo "<h3>Son 10 Kayıt:</h3>";
        $records = $db->query("SELECT * FROM teklif_decisions ORDER BY decision_date DESC LIMIT 10");
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Message ID</th><th>Teklif ID</th><th>Manager Phone</th><th>Decision Type</th><th>Decision Status</th><th>Decision Date</th></tr>";
        while ($row = $records->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 5. Test INSERT
    echo "<h3>Test INSERT Denemesi:</h3>";
    $testMessageId = "TEST_" . time();
    $testTeklifId = "999999"; // varchar olarak
    $testPhone = "905555555555";
    $testDecision = "ONAY"; // 'TEST' yerine 'ONAY' (ENUM değeri)
    
    $stmt = $db->prepare("INSERT INTO teklif_decisions 
        (message_id, teklif_id, manager_phone, decision_type, decision_status) 
        VALUES (?, ?, ?, ?, 'PROCESSED')
        ON DUPLICATE KEY UPDATE 
        decision_type = VALUES(decision_type),
        decision_date = CURRENT_TIMESTAMP");
    
    if ($stmt) {
        // teklif_id varchar olduğu için "ssss" kullan
        $stmt->bind_param("ssss", $testMessageId, $testTeklifId, $testPhone, $testDecision);
        if ($stmt->execute()) {
            echo "<p style='color:green;'>✅ Test INSERT başarılı! Affected rows: " . $stmt->affected_rows . "</p>";
            
            // Test kaydını sil
            $db->query("DELETE FROM teklif_decisions WHERE message_id = '$testMessageId'");
            echo "<p style='color:gray;'>Test kaydı silindi.</p>";
        } else {
            echo "<p style='color:red;'>❌ Test INSERT hatası: " . $stmt->error . "</p>";
        }
        $stmt->close();
    } else {
        echo "<p style='color:red;'>❌ Prepared statement oluşturulamadı: " . $db->error . "</p>";
    }
    
} else {
    echo "<p style='color:red;'>❌ teklif_decisions tablosu BULUNAMADI!</p>";
    echo "<h3>Tablo Oluşturma SQL'i:</h3>";
    echo "<pre>";
    echo "CREATE TABLE `teklif_decisions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message_id` varchar(255) NOT NULL,
  `teklif_id` int(11) NOT NULL,
  `manager_phone` varchar(50) DEFAULT NULL,
  `decision_type` enum('ONAY','RED') NOT NULL,
  `decision_status` enum('PENDING','PROCESSED') DEFAULT 'PENDING',
  `decision_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `message_id` (`message_id`),
  KEY `teklif_id` (`teklif_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    echo "</pre>";
    echo "<p>Bu SQL'i phpMyAdmin'de çalıştırın.</p>";
}

$db->close();
?>
