<?php
// Test scripti - sync_row.php'nin çalışıp çalışmadığını kontrol et
error_reporting(E_ALL);
ini_set('display_errors', 1);

// .env dosyasını oku
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

echo "<h2>🔍 Veritabanı Bağlantı Testi</h2>";
echo "<pre>";
echo "Host: $mysql_host\n";
echo "Database: $mysql_dbname\n";
echo "User: $mysql_username\n";
echo "Password: " . (empty($mysql_password) ? "(boş)" : "***") . "\n\n";

try {
    $mysql_dsn = "mysql:host=$mysql_host;dbname=$mysql_dbname;charset=utf8mb4";
    $pdo = new PDO($mysql_dsn, $mysql_username, $mysql_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ MySQL Bağlantısı BAŞARILI!\n\n";
    
    // Sirket tablosu var mı?
    $tables = $pdo->query("SHOW TABLES LIKE 'sirket'")->fetchAll();
    if (count($tables) > 0) {
        echo "✅ 'sirket' tablosu mevcut\n\n";
        
        // Kolonları kontrol et
        echo "📋 Sirket Tablosu Kolonları:\n";
        echo "─────────────────────────────────────\n";
        $columns = $pdo->query("DESCRIBE sirket")->fetchAll();
        foreach ($columns as $col) {
            echo sprintf("%-30s %s\n", $col['Field'], $col['Type']);
        }
        
        // Kayıt sayısı
        $count = $pdo->query("SELECT COUNT(*) as total FROM sirket")->fetch();
        echo "\n📊 Toplam kayıt: " . $count['total'] . "\n";
        
    } else {
        echo "❌ 'sirket' tablosu BULUNAMADI!\n";
    }
    
} catch (PDOException $e) {
    echo "❌ HATA: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
