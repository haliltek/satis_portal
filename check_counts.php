<?php
// check_counts.php - Şirket sayılarını kontrol et
require_once 'fonk.php';

// Logo bağlantı bilgileri
function parseEnvFile($path) {
    $vars = [];
    if (!file_exists($path)) return $vars;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) {
        if (strpos(trim($l), '#') === 0 || strpos($l, '=') === false) continue;
        [$k, $v] = explode('=', $l, 2);
        $vars[trim($k)] = trim($v);
    }
    return $vars;
}

$env = parseEnvFile(__DIR__ . '/.env');
$logo_host = $env['GEMPA_LOGO_HOST'] ?? 'localhost';
$logo_db = $env['GEMPA_LOGO_DB'] ?? '';
$logo_user = $env['GEMPA_LOGO_USER'] ?? '';
$logo_pass = $env['GEMPA_LOGO_PASS'] ?? '';

echo "<h2>📊 Şirket Sayıları Karşılaştırması</h2>";
echo "<hr>";

// MySQL sirket tablosu
$mysql_count = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as total FROM sirket"))['total'];
echo "<h3>MySQL (B2B Portal)</h3>";
echo "<p>🗄️ <strong>sirket</strong> tablosu: <strong>" . number_format($mysql_count) . "</strong> kayıt</p>";

// Logo bağlantısı
try {
    $dsn = "sqlsrv:Server=$logo_host;Database=$logo_db";
    $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
    if (defined('PDO::SQLSRV_ATTR_ENCODING') && defined('PDO::SQLSRV_ENCODING_UTF8')) {
        $options[PDO::SQLSRV_ATTR_ENCODING] = PDO::SQLSRV_ENCODING_UTF8;
    }
    $logo_conn = new PDO($dsn, $logo_user, $logo_pass, $options);
    
    $stmt = $logo_conn->query("SELECT COUNT(*) as total FROM LG_566_CLCARD WHERE CODE IS NOT NULL AND CODE != ''");
    $logo_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "<h3>Logo Tiger (MSSQL)</h3>";
    echo "<p>🗄️ <strong>LG_566_CLCARD</strong> tablosu: <strong>" . number_format($logo_count) . "</strong> kayıt</p>";
    
    echo "<hr>";
    echo "<h3>📈 Karşılaştırma</h3>";
    
    $diff = $logo_count - $mysql_count;
    
    echo "<table border='1' cellpadding='10' style='border-collapse:collapse;'>";
    echo "<tr><th>Veritabanı</th><th>Kayıt Sayısı</th></tr>";
    echo "<tr><td>Logo Tiger</td><td><strong>" . number_format($logo_count) . "</strong></td></tr>";
    echo "<tr><td>MySQL sirket</td><td><strong>" . number_format($mysql_count) . "</strong></td></tr>";
    echo "<tr><th>Fark</th><th>" . ($diff > 0 ? "+" : "") . number_format($diff) . "</th></tr>";
    echo "</table>";
    
    if ($diff > 0) {
        echo "<p style='color:orange;'>⚠️ Logo'da <strong>" . number_format($diff) . "</strong> kayıt daha fazla var.</p>";
        echo "<p>💡 <strong>cari_update_export.php</strong> çalıştırıldığında sadece mevcut kayıtlar güncellenecek.</p>";
        echo "<p>💡 Yeni kayıtları da eklemek için <strong>cari_sync.php</strong> kullanın.</p>";
    } else if ($diff < 0) {
        echo "<p style='color:blue;'>ℹ️ MySQL'de <strong>" . number_format(abs($diff)) . "</strong> kayıt daha fazla var.</p>";
    } else {
        echo "<p style='color:green;'>✅ Her iki veritabanında da aynı sayıda kayıt var!</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ Logo bağlantı hatası: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><a href='cari_update_export.php'>SPECODE Güncelle</a> | <a href='anasayfa.php'>Anasayfa</a></p>";
?>
