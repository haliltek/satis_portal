<?php
// Debug dosyası - production'daki sorunları tespit etmek için
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>DataTable Debug Bilgileri</h1>";

// 1. Dosya kontrolü
echo "<h2>1. Dosya Varlık Kontrolü</h2>";
$files = ['ssp.php', 'fonk.php', 'include/vt.php'];
foreach ($files as $file) {
    $exists = file_exists($file);
    $status = $exists ? '<span style="color:green">✓ Var</span>' : '<span style="color:red">✗ Yok</span>';
    echo "{$file}: {$status}<br>";
    if ($exists) {
        echo "&nbsp;&nbsp;→ Dosya yolu: " . realpath($file) . "<br>";
    }
}

// 2. Veritabanı bağlantısı kontrolü
echo "<h2>2. Veritabanı Bağlantı Kontrolü</h2>";
try {
    if (file_exists('include/vt.php')) {
        include 'include/vt.php';
        
        if (isset($sql_details)) {
            echo "Veritabanı bilgileri:<br>";
            echo "&nbsp;&nbsp;Host: " . htmlspecialchars($sql_details['host'] ?? 'YOK') . "<br>";
            echo "&nbsp;&nbsp;DB: " . htmlspecialchars($sql_details['db'] ?? 'YOK') . "<br>";
            echo "&nbsp;&nbsp;User: " . htmlspecialchars($sql_details['user'] ?? 'YOK') . "<br>";
            
            // PDO bağlantısı test et
            try {
                $pdo = new PDO(
                    "mysql:host={$sql_details['host']};dbname={$sql_details['db']}",
                    $sql_details['user'],
                    $sql_details['pass'],
                    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
                );
                echo '<span style="color:green">✓ Veritabanı bağlantısı başarılı</span><br>';
                
                // Sirket tablosunu kontrol et
                $stmt = $pdo->query("SHOW TABLES LIKE 'sirket'");
                if ($stmt->rowCount() > 0) {
                    echo '<span style="color:green">✓ sirket tablosu mevcut</span><br>';
                    
                    // Tablo yapısını göster
                    $stmt = $pdo->query("DESCRIBE sirket");
                    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    echo "Tablo sütunları: " . implode(', ', $columns) . "<br>";
                    
                    // Kayıt sayısı
                    $stmt = $pdo->query("SELECT COUNT(*) FROM sirket");
                    $count = $stmt->fetchColumn();
                    echo "Toplam kayıt sayısı: {$count}<br>";
                } else {
                    echo '<span style="color:red">✗ sirket tablosu bulunamadı!</span><br>';
                }
                
            } catch (PDOException $e) {
                echo '<span style="color:red">✗ Veritabanı bağlantı hatası: ' . htmlspecialchars($e->getMessage()) . '</span><br>';
            }
        } else {
            echo '<span style="color:red">✗ $sql_details tanımlı değil!</span><br>';
        }
    } else {
        echo '<span style="color:red">✗ include/vt.php dosyası bulunamadı!</span><br>';
    }
} catch (Exception $e) {
    echo '<span style="color:red">Hata: ' . htmlspecialchars($e->getMessage()) . '</span><br>';
}

// 3. Session kontrolü
echo "<h2>3. Oturum Kontrolü</h2>";
try {
    if (file_exists('fonk.php')) {
        require_once 'fonk.php';
        
        // Session durumu
        echo "Session durumu: " . (session_status() === PHP_SESSION_ACTIVE ? '<span style="color:green">Aktif</span>' : '<span style="color:orange">Pasif</span>') . "<br>";
        
        // Oturum kontrol fonksiyonu test
        if (function_exists('oturumkontrol')) {
            echo "oturumkontrol() fonksiyonu: <span style=\"color:green\">✓ Mevcut</span><br>";
            try {
                oturumkontrol();
                echo "Oturum kontrolü: <span style=\"color:green\">✓ Başarılı (Giriş yapılmış)</span><br>";
            } catch (Exception $e) {
                echo "Oturum kontrolü: <span style=\"color:red\">✗ Başarısız - " . htmlspecialchars($e->getMessage()) . "</span><br>";
            }
        } else {
            echo "oturumkontrol() fonksiyonu: <span style=\"color:red\">✗ Bulunamadı</span><br>";
        }
    }
} catch (Exception $e) {
    echo '<span style="color:red">Hata: ' . htmlspecialchars($e->getMessage()) . '</span><br>';
}

// 4. PHP Bilgileri
echo "<h2>4. PHP Bilgileri</h2>";
echo "PHP Versiyonu: " . PHP_VERSION . "<br>";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Bilinmiyor') . "<br>";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Bilinmiyor') . "<br>";
echo "Script Filename: " . __FILE__ . "<br>";

// 5. Error log kontrol
echo "<h2>5. Error Log</h2>";
$error_log = __DIR__ . '/error_log.txt';
if (file_exists($error_log)) {
    echo '<span style="color:green">✓ error_log.txt mevcut</span><br>';
    echo "Son 20 satır:<br>";
    echo "<pre style='background:#f5f5f5; padding:10px; max-height:300px; overflow:auto;'>";
    $lines = file($error_log);
    $last_lines = array_slice($lines, -20);
    echo htmlspecialchars(implode('', $last_lines));
    echo "</pre>";
} else {
    echo '<span style="color:orange">error_log.txt henüz oluşmamış</span><br>';
}

echo "<hr>";
echo "<h2>Test URL'si</h2>";
echo "<p>Şimdi bu URL'yi test edin:</p>";
echo "<code>sirketcekdatatable.php?draw=1&start=0&length=10</code><br>";
echo '<a href="sirketcekdatatable.php?draw=1&start=0&length=10" target="_blank">Test Et</a>';
?>
