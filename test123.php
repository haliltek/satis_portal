<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$host = "89.43.31.214";
$port = 3306;
$db   = "gemas_pool_technology";
$user = "gemas_mehmet";
$pass = "2261686Me!";

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5
    ]);

    echo "<h3>MySQL bağlantısı başarılı</h3>";

    // malzeme tablosundaki tüm verileri çek
    $sql = "SELECT * FROM malzeme";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();

    if (count($rows) === 0) {
        echo "Kayıt bulunamadı.";
        exit;
    }

    // HTML tablo olarak yazdır
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr>";

    // Başlıklar (kolon adları)
    foreach (array_keys($rows[0]) as $column) {
        echo "<th>" . htmlspecialchars($column) . "</th>";
    }
    echo "</tr>";

    // Satırlar
    foreach ($rows as $row) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars((string)$value) . "</td>";
        }
        echo "</tr>";
    }

    echo "</table>";

} catch (PDOException $e) {
    echo "❌ Hata: " . $e->getMessage();
}
