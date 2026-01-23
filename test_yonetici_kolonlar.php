<?php
// Yönetici tablosu kolon kontrolü
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

echo "<h2>Yönetici Tablosu Kolonları</h2><pre>";

try {
    $pdo = new PDO("mysql:host=$mysql_host;dbname=$mysql_dbname;charset=utf8mb4", $mysql_username, $mysql_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    $columns = $pdo->query("DESCRIBE yonetici")->fetchAll();
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }
    
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage();
}

echo "</pre>";
?>
