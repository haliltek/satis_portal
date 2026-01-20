<?php
$serverName = "195.175.85.186"; // Sunucu adı veya IP adresi (örnek: localhost, 127.0.0.1)
$database = "GEMPA2024"; // Veritabanı adı
$username = "halil "; // MSSQL kullanıcı adı
$password = "12621262"; // MSSQL kullanıcı şifresi
try {
    $dsn = "sqlsrv:Server=$serverName;Database=$database;Encrypt=no;TrustServerCertificate=yes";
    $pdo = new PDO($dsn, $username, $password);
    echo "MSSQL bağlantısı başarılı!";
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage();
}
