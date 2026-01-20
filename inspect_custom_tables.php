<?php
/**
 * Logo Veritabanı Tarayıcı
 * "TIGER" adında veya benzeri veritabanlarını arar.
 */

require_once "fonk.php";
// oturumkontrol(); 

$config = require __DIR__ . '/config/config.php';
$logo = $config['logo'];

echo "<h3>Veritabanı Listesi ve Çapraz Sorgu Testi</h3>";

try {
    $dsn = "sqlsrv:Server={$logo['host']};Database={$logo['db']}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
    ];
    
    $pdo = new PDO($dsn, $logo['user'], $logo['pass'], $options);
    echo "<div style='color:green'>Mevcut Bağlantı: <strong>{$logo['db']}</strong></div><hr>";

    // 1. Veritabanlarını Listele
    echo "<h4>Sunucudaki Veritabanları</h4>";
    $dbs = $pdo->query("SELECT name FROM sys.databases WHERE name NOT IN ('master','tempdb','model','msdb') ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<ul>";
    $tigerDbCandidate = null;
    foreach ($dbs as $db) {
        echo "<li>$db";
        if (stripos($db, 'TIGER') !== false) {
            echo " <strong><-- Olası Hedef?</strong>";
            $tigerDbCandidate = $db;
        }
        echo "</li>";
    }
    echo "</ul><hr>";

    // 2. Eğer TIGER benzeri bir DB bulunduysa veya kullanıcı "TIGER" dediyse test et
    $testDbs = array_filter([$tigerDbCandidate, 'TIGER', 'GO3', 'LOGO'], function($v) { return !empty($v); });
    $testDbs = array_unique($testDbs);

    foreach ($testDbs as $targetDb) {
        echo "<h4>Hedef DB Testi: [$targetDb]</h4>";
        $testSql = "SELECT TOP 1 * FROM [$targetDb].[dbo].[MEG_565_FILTRE]";
        echo "Sorgu: <code>$testSql</code><br>";
        
        try {
            $stmt = $pdo->query($testSql);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                echo "<div style='color:green; font-weight:bold'>BAŞARILI! View bu veritabanında bulundu.</div>";
                echo "<pre>" . print_r($row, true) . "</pre>";
            } else {
                echo "<div style='color:orange'>Sorgu çalıştı ama veri dönmedi.</div>";
            }
        } catch (Exception $e) {
            echo "<div style='color:red'>Hata: " . $e->getMessage() . "</div>";
        }
        echo "<hr>";
    }

} catch (PDOException $e) {
    die("Bağlantı Hatası: " . $e->getMessage());
}
?>
