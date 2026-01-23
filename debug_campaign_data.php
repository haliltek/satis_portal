<?php
// Doğrudan bağlantı kuralım (Config dosyasındaki varsayılanlara göre)
$host = 'localhost';
$user = 'root';
$pass = ''; // XAMPP default
$name = 'b2bgemascom_teklif';

try {
    $conn = new mysqli($host, $user, $pass, $name);
    if ($conn->connect_error) {
        die("Bağlantı hatası: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");

    echo "--- COLUMNS (custom_campaigns) ---\n";
    $result = $conn->query("SHOW COLUMNS FROM custom_campaigns");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    }

    echo "\n--- DATA (custom_campaigns) ---\n";
    $result = $conn->query("SELECT * FROM custom_campaigns");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "ID: " . $row['id'] . "\n";
            echo "Name: " . $row['name'] . "\n";
            echo "Cat: " . $row['category_name'] . "\n";
            echo "MinQty: " . ($row['min_quantity'] ?? 'NULL') . "\n";
            echo "MinAmt: " . ($row['min_amount'] ?? 'NULL') . "\n";
            // echo "MinPurchAmt: " . ($row['min_purchase_amount'] ?? 'NULL') . "\n"; // Kontrol için
            echo "Extra: " . ($row['is_extra_discount'] ?? 'NULL') . "\n";
            echo "CustomerType: " . ($row['customer_type'] ?? 'NULL') . "\n";
            echo "--------------------------\n";
        }
    }

} catch (Exception $e) {
    echo "Hata: " . $e->getMessage();
}
?>
