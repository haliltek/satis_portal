<?php
// Tiger veritabanına erişim testi
$host = '192.168.5.253,1433';
$user = 'halil';
$pass = '12621262';

try {
    // GEMPA2026'dan Tiger'a cross-database sorgu
    $dsn = "sqlsrv:Server=$host;Database=GEMPA2026";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    echo "✓ GEMPA2026 bağlantısı başarılı<br>";
    
    // Tiger'daki tabloya erişim testi
    $sql = "SELECT TOP 1 * FROM [Tiger].[dbo].[MEG_565_FILTRE]";
    $stmt = $pdo->query($sql);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        echo "✓ Tiger.MEG_565_FILTRE tablosuna erişim başarılı<br>";
        echo "<pre>" . print_r($row, true) . "</pre>";
    }
    
} catch (PDOException $e) {
    echo "❌ Hata: " . $e->getMessage();
}
?>
