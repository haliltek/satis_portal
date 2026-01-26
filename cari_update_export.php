<?php
// cari_update_export.php - Sadece SPECODE ve IS_EXPORT Alanlarını Güncelle
error_reporting(E_ALL);
ini_set('display_errors', 1);
@set_time_limit(0);

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

echo "<h2>🔄 SPECODE ve IS_EXPORT Güncelleme</h2>";
echo "<hr>";

// Adım 1: sirket tablosunda sütunlar var mı kontrol et
echo "<h3>Adım 1: Sütun Kontrolü</h3>";
$columns = mysqli_query($db, "SHOW COLUMNS FROM sirket LIKE 'specode'");
if (mysqli_num_rows($columns) == 0) {
    echo "<p>⚙️ 'specode' ve 'is_export' sütunları ekleniyor...</p>";
    mysqli_query($db, "ALTER TABLE sirket ADD COLUMN specode VARCHAR(100) NULL AFTER trading_grp");
    mysqli_query($db, "ALTER TABLE sirket ADD COLUMN is_export TINYINT(1) DEFAULT 0 AFTER specode");
    mysqli_query($db, "CREATE INDEX idx_is_export ON sirket(is_export)");
    echo "<p>✅ Sütunlar eklendi</p>";
} else {
    echo "<p>✅ Sütunlar mevcut</p>";
}

$count_sirket = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as total FROM sirket"))['total'];
echo "<p>📊 Toplam sirket kaydı: <strong>" . number_format($count_sirket) . "</strong></p>";

echo "<hr>";

// Adım 2: Logo'ya bağlan
echo "<h3>Adım 2: Logo Bağlantısı</h3>";
try {
    $dsn = "sqlsrv:Server=$logo_host;Database=$logo_db";
    $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
    if (defined('PDO::SQLSRV_ATTR_ENCODING') && defined('PDO::SQLSRV_ENCODING_UTF8')) {
        $options[PDO::SQLSRV_ATTR_ENCODING] = PDO::SQLSRV_ENCODING_UTF8;
    }
    $logo_conn = new PDO($dsn, $logo_user, $logo_pass, $options);
    echo "<p>✅ Logo bağlantısı başarılı</p>";
} catch (PDOException $e) {
    die("<p style='color:red;'>❌ Logo bağlantı hatası: " . htmlspecialchars($e->getMessage()) . "</p>");
}

echo "<hr>";

// Adım 3: Logo'dan SPECODE bilgilerini çek ve güncelle
echo "<h3>Adım 3: Güncelleme Başlıyor...</h3>";
echo "<p>Logo'dan SPECODE bilgileri çekiliyor...</p>";
flush();

$sql = "
SELECT 
    CODE,
    SPECODE,
    CASE 
        WHEN SPECODE LIKE '%İhracat%' OR SPECODE LIKE '%EXPORT%' OR SPECODE LIKE '%Ihracat%' THEN 1
        ELSE 0
    END AS is_export
FROM LG_566_CLCARD
WHERE CODE IS NOT NULL AND CODE != ''
ORDER BY CODE";

$stmt = $logo_conn->query($sql);
$logo_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>✅ Logo'dan <strong>" . number_format(count($logo_data)) . "</strong> kayıt çekildi</p>";
echo "<p>⏳ Güncelleme yapılıyor...</p>";
flush();

$update_count = 0;
$not_found_count = 0;
$export_found = 0;

foreach ($logo_data as $row) {
    $code = mysqli_real_escape_string($db, $row['CODE']);
    $specode = mysqli_real_escape_string($db, $row['SPECODE'] ?? '');
    $is_export = (int)$row['is_export'];
    
    if ($is_export == 1) {
        $export_found++;
    }
    
    // sirket tablosunda bu code var mı?
    $check = mysqli_query($db, "SELECT s_arp_code FROM sirket WHERE s_arp_code = '$code'");
    
    if (mysqli_num_rows($check) > 0) {
        // Güncelle
        $update_sql = "UPDATE sirket SET 
            specode = '$specode',
            is_export = $is_export
        WHERE s_arp_code = '$code'";
        
        if (mysqli_query($db, $update_sql)) {
            if (mysqli_affected_rows($db) > 0) {
                $update_count++;
            }
        }
    } else {
        $not_found_count++;
    }
    
    // Her 1000 kayıtta ilerleme göster
    if (($update_count + $not_found_count) % 1000 == 0) {
        echo "<p>📊 İşlenen: " . number_format($update_count + $not_found_count) . " / " . number_format(count($logo_data)) . "</p>";
        flush();
    }
}

echo "<hr>";
echo "<h3>✅ Güncelleme Tamamlandı!</h3>";

echo "<table border='1' cellpadding='10' style='border-collapse:collapse; margin:20px 0;'>";
echo "<tr><th>Durum</th><th>Sayı</th></tr>";
echo "<tr><td>🔄 Güncellenen Kayıt</td><td><strong>" . number_format($update_count) . "</strong></td></tr>";
echo "<tr><td>❓ sirket'te Bulunamayan</td><td>" . number_format($not_found_count) . "</td></tr>";
echo "<tr><td>🌍 Logo'da İhracat Müşterisi</td><td><strong>" . number_format($export_found) . "</strong></td></tr>";
echo "</table>";

// Adım 4: Doğrulama
echo "<hr>";
echo "<h3>Adım 4: Doğrulama</h3>";

$final_export = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as total FROM sirket WHERE is_export = 1"))['total'];
$has_specode = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as total FROM sirket WHERE specode IS NOT NULL AND specode != ''"))['total'];

echo "<p>📊 sirket tablosunda:</p>";
echo "<ul>";
echo "<li>🌍 <strong>is_export = 1</strong> olan kayıt: <strong>" . number_format($final_export) . "</strong></li>";
echo "<li>📝 <strong>specode</strong> dolu olan kayıt: <strong>" . number_format($has_specode) . "</strong></li>";
echo "</ul>";

// Örnek kayıtlar
echo "<h4>📋 Örnek İhracat Müşterileri (is_export = 1):</h4>";
$samples = mysqli_query($db, "SELECT s_arp_code, s_adi, s_country, specode FROM sirket WHERE is_export = 1 LIMIT 10");

if (mysqli_num_rows($samples) > 0) {
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
    echo "<tr><th>Kod</th><th>Firma Adı</th><th>Ülke</th><th>SPECODE</th></tr>";
    while ($row = mysqli_fetch_assoc($samples)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['s_arp_code']) . "</td>";
        echo "<td>" . htmlspecialchars($row['s_adi']) . "</td>";
        echo "<td>" . htmlspecialchars($row['s_country'] ?? '-') . "</td>";
        echo "<td>" . htmlspecialchars($row['specode']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>⚠️ Hiç ihracat müşterisi bulunamadı!</p>";
}

echo "<hr>";
echo "<h3>🎉 İşlem Tamamlandı!</h3>";
echo "<p>✅ <strong>specode</strong> ve <strong>is_export</strong> alanları başarıyla güncellendi.</p>";
echo "<p>🔍 Artık PDF ve email'lerde doğru dil seçimi yapılacak!</p>";
echo "<p><a href='anasayfa.php'>Anasayfaya Dön</a> | <a href='sirket_cek.php'>Şirket Listesi</a></p>";
?>
