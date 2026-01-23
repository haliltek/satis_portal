<?php
// Test: Yönetici tablosunda yetki kolonu kontrolü
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

echo "<h2>🔍 Yönetici Tablosu Kontrol</h2>";
echo "<pre>";

try {
    $mysql_dsn = "mysql:host=$mysql_host;dbname=$mysql_dbname;charset=utf8mb4";
    $pdo = new PDO($mysql_dsn, $mysql_username, $mysql_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ MySQL Bağlantısı BAŞARILI!\n\n";
    
    // Yönetici tablosu kolonları
    echo "📋 Yönetici Tablosu Kolonları:\n";
    echo "─────────────────────────────────────\n";
    $columns = $pdo->query("DESCRIBE yonetici")->fetchAll();
    $hasYetkiColumn = false;
    
    foreach ($columns as $col) {
        echo sprintf("%-30s %s\n", $col['Field'], $col['Type']);
        if ($col['Field'] === 'yetki') {
            $hasYetkiColumn = true;
        }
    }
    
    echo "\n";
    
    if ($hasYetkiColumn) {
        echo "✅ 'yetki' kolonu MEVCUT\n\n";
        
        // Yetki değerlerini göster
        echo "📊 Mevcut Yetki Değerleri:\n";
        echo "─────────────────────────────────────\n";
        $yetkiler = $pdo->query("SELECT DISTINCT yetki, COUNT(*) as sayi FROM yonetici GROUP BY yetki")->fetchAll();
        foreach ($yetkiler as $yetki) {
            echo sprintf("%-20s : %d kullanıcı\n", $yetki['yetki'] ?: '(boş)', $yetki['sayi']);
        }
        
    } else {
        echo "❌ 'yetki' kolonu BULUNAMADI!\n";
        echo "💡 Kolon ekleme SQL'i:\n\n";
        echo "ALTER TABLE yonetici ADD COLUMN yetki VARCHAR(50) DEFAULT 'Personel' AFTER iskonto_max;\n\n";
        echo "Veya tüm kullanıcıları 'Personel' olarak ayarlamak için:\n\n";
        echo "ALTER TABLE yonetici ADD COLUMN yetki VARCHAR(50) DEFAULT 'Personel';\n";
        echo "UPDATE yonetici SET yetki = 'Personel' WHERE yetki IS NULL OR yetki = '';\n";
    }
    
    // Örnek kullanıcıları göster
    echo "\n📋 Örnek Kullanıcılar:\n";
    echo "─────────────────────────────────────\n";
    $users = $pdo->query("SELECT yonetici_id, yonetici_adi, yetki, iskonto_max FROM yonetici LIMIT 5")->fetchAll();
    foreach ($users as $user) {
        echo sprintf("ID: %-5d | %-20s | Yetki: %-15s | İskonto Max: %s\n", 
            $user['yonetici_id'], 
            $user['yonetici_adi'], 
            $user['yetki'] ?: '(boş)',
            $user['iskonto_max'] ?? 'NULL'
        );
    }
    
} catch (PDOException $e) {
    echo "❌ HATA: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
