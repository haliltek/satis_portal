<?php
// Fiyat Talepleri Tablosunu Oluştur
error_reporting(E_ALL);
ini_set('display_errors', 1);

function parseEnvFile($path) {
    $vars = [];
    if (!file_exists($path)) return $vars;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        [$key, $value] = explode('=', $line, 2);
        $vars[trim($key)] = trim($value);
    }
    return $vars;
}

$env = parseEnvFile(__DIR__ . '/.env');
$mysql_host = $env['DB_HOST'] ?? 'localhost';
$mysql_dbname = $env['DB_NAME'] ?? 'b2bgemascom_teklif';
$mysql_username = $env['DB_USER'] ?? 'root';
$mysql_password = $env['DB_PASS'] ?? '';

echo "<h2>🔧 Fiyat Talepleri Tablosu Kurulumu</h2>";
echo "<pre>";

try {
    $mysql_dsn = "mysql:host=$mysql_host;dbname=$mysql_dbname;charset=utf8mb4";
    $pdo = new PDO($mysql_dsn, $mysql_username, $mysql_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ MySQL Bağlantısı BAŞARILI!\n\n";
    
    // Tablo var mı kontrol et
    $tables = $pdo->query("SHOW TABLES LIKE 'fiyat_talepleri'")->fetchAll();
    
    if (count($tables) > 0) {
        echo "⚠️  'fiyat_talepleri' tablosu zaten mevcut.\n\n";
        
        // Mevcut yapıyı göster
        echo "📋 Mevcut Tablo Yapısı:\n";
        echo "─────────────────────────────────────\n";
        $columns = $pdo->query("DESCRIBE fiyat_talepleri")->fetchAll();
        foreach ($columns as $col) {
            echo sprintf("%-20s %-20s %s\n", $col['Field'], $col['Type'], $col['Key'] ? "[$col[Key]]" : '');
        }
        
        // Kayıt sayısı
        $count = $pdo->query("SELECT COUNT(*) as total FROM fiyat_talepleri")->fetch();
        echo "\n📊 Toplam kayıt: " . $count['total'] . "\n";
        
    } else {
        echo "📝 'fiyat_talepleri' tablosu oluşturuluyor...\n\n";
        
        $sql = "CREATE TABLE IF NOT EXISTS fiyat_talepleri (
            talep_id INT(11) AUTO_INCREMENT PRIMARY KEY,
            urun_id INT(11) NOT NULL,
            stokkodu VARCHAR(100),
            stokadi VARCHAR(255),
            talep_eden_id INT(11) NOT NULL,
            talep_eden_adi VARCHAR(100),
            talep_notu TEXT,
            talep_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
            durum ENUM('beklemede', 'onaylandi', 'reddedildi') DEFAULT 'beklemede',
            yonetici_notu TEXT,
            cevaplayan_id INT(11),
            cevap_tarihi DATETIME,
            onerilen_fiyat DECIMAL(15,2),
            onerilen_doviz VARCHAR(10),
            INDEX idx_urun (urun_id),
            INDEX idx_talep_eden (talep_eden_id),
            INDEX idx_durum (durum),
            INDEX idx_tarih (talep_tarihi)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        
        echo "✅ Tablo başarıyla oluşturuldu!\n\n";
        
        // Oluşturulan yapıyı göster
        echo "📋 Oluşturulan Tablo Yapısı:\n";
        echo "─────────────────────────────────────\n";
        $columns = $pdo->query("DESCRIBE fiyat_talepleri")->fetchAll();
        foreach ($columns as $col) {
            echo sprintf("%-20s %-20s %s\n", $col['Field'], $col['Type'], $col['Key'] ? "[$col[Key]]" : '');
        }
    }
    
    echo "\n✅ Kurulum tamamlandı!\n";
    echo "\n🎯 Sonraki Adımlar:\n";
    echo "1. Ürün listesine 'Fiyat Talep Et' butonu eklenecek\n";
    echo "2. Talep modal'ı oluşturulacak\n";
    echo "3. Yönetici panelinde talep listesi sayfası eklenecek\n";
    
} catch (PDOException $e) {
    echo "❌ HATA: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
