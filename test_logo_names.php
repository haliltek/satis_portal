<?php
// Logo veritabanındaki NAME alanlarını kontrol etme scripti
// İngilizce ürün adının hangi kolonda olduğunu bulmak için

require 'include/vt.php';
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

$config = require __DIR__ . '/config/config.php';
$logo = $config['logo'];

echo "Logo Veritabanı NAME Alanları Kontrolü\n";
echo "=======================================\n\n";

if (empty($logo['host']) || empty($logo['user']) || empty($logo['db'])) {
    die("Logo bağlantı bilgileri eksik! .env dosyasını kontrol edin.\n");
}

if (!extension_loaded('pdo_sqlsrv')) {
    die("PDO SQLSRV extension yüklü değil!\n");
}

try {
    $dsn = "sqlsrv:Server={$logo['host']};Database={$logo['db']}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ];
    
    if (defined('PDO::SQLSRV_ATTR_ENCODING') && defined('PDO::SQLSRV_ENCODING_UTF8')) {
        $options[PDO::SQLSRV_ATTR_ENCODING] = PDO::SQLSRV_ENCODING_UTF8;
    }
    
    $pdo = new PDO($dsn, $logo['user'], $logo['pass'], $options);
    echo "✓ Logo veritabanına bağlanıldı\n\n";
    
    // Tüm NAME alanlarını çek (NAME, NAME2, NAME3, NAME4)
    $sql = "
        SELECT TOP 100
            CODE,
            NAME,
            NAME2,
            NAME3,
            NAME4
        FROM LG_565_ITEMS
        WHERE CODE IS NOT NULL
        ORDER BY CODE
    ";
    
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Toplam " . count($rows) . " ürün bulundu\n\n";
    echo str_repeat("=", 150) . "\n";
    echo sprintf("%-15s | %-35s | %-35s | %-35s | %-35s\n", 
        "CODE", "NAME (TR?)", "NAME2", "NAME3 (EN?)", "NAME4");
    echo str_repeat("-", 150) . "\n";
    
    $name2Count = 0;
    $name3Count = 0;
    $name4Count = 0;
    
    foreach ($rows as $row) {
        $code = substr($row['CODE'] ?? '', 0, 15);
        $name = substr($row['NAME'] ?? '', 0, 35);
        $name2 = substr($row['NAME2'] ?? '', 0, 35);
        $name3 = substr($row['NAME3'] ?? '', 0, 35);
        $name4 = substr($row['NAME4'] ?? '', 0, 35);
        
        if (!empty($name2)) $name2Count++;
        if (!empty($name3)) $name3Count++;
        if (!empty($name4)) $name4Count++;
        
        echo sprintf("%-15s | %-35s | %-35s | %-35s | %-35s\n", 
            $code, $name, $name2, $name3, $name4);
    }
    
    echo str_repeat("=", 150) . "\n\n";
    echo "İstatistikler:\n";
    echo "  NAME2 dolu: $name2Count ürün\n";
    echo "  NAME3 dolu: $name3Count ürün\n";
    echo "  NAME4 dolu: $name4Count ürün\n\n";
    
    // İngilizce karakter kontrolü (NAME3'te İngilizce olup olmadığını kontrol et)
    echo "NAME3 alanında İngilizce karakter kontrolü:\n";
    $englishCheck = $pdo->query("
        SELECT TOP 10 CODE, NAME, NAME3 
        FROM LG_565_ITEMS 
        WHERE NAME3 IS NOT NULL AND NAME3 != ''
        ORDER BY CODE
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($englishCheck as $item) {
        echo "  CODE: " . $item['CODE'] . "\n";
        echo "    NAME (TR): " . substr($item['NAME'], 0, 50) . "\n";
        echo "    NAME3 (EN?): " . substr($item['NAME3'], 0, 50) . "\n\n";
    }
    
} catch (PDOException $e) {
    die("Hata: " . $e->getMessage() . "\n");
}

