<?php
// cari_sync.php - Logo Cari Verilerini Sirket Tablosuna Senkronize Et
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'fonk.php';

echo "<h2>Logo Cari → Sirket Senkronizasyonu</h2>";
echo "<hr>";

// Adım 1: logo_cari_import tablosu var mı kontrol et
echo "<h3>Adım 1: Kontrol</h3>";
$check = mysqli_query($db, "SHOW TABLES LIKE 'logo_cari_import'");
if (mysqli_num_rows($check) == 0) {
    die("<p style='color:red;'><strong>HATA:</strong> logo_cari_import tablosu bulunamadı!<br>Önce cari.sql dosyasını import edin.</p>");
}

$count_import = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as total FROM logo_cari_import"))['total'];
echo "<p>✅ logo_cari_import tablosu bulundu: <strong>" . number_format($count_import) . "</strong> kayıt</p>";

// Adım 2: sirket tablosunda specode ve is_export sütunları var mı kontrol et
$columns = mysqli_query($db, "SHOW COLUMNS FROM sirket LIKE 'specode'");
if (mysqli_num_rows($columns) == 0) {
    echo "<p>⚠️ sirket tablosunda 'specode' sütunu yok, ekleniyor...</p>";
    mysqli_query($db, "ALTER TABLE sirket ADD COLUMN specode VARCHAR(100) NULL AFTER trading_grp");
    mysqli_query($db, "ALTER TABLE sirket ADD COLUMN is_export TINYINT(1) DEFAULT 0 AFTER specode");
    echo "<p>✅ Sütunlar eklendi</p>";
} else {
    echo "<p>✅ sirket tablosunda gerekli sütunlar mevcut</p>";
}

$count_sirket = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as total FROM sirket"))['total'];
echo "<p>📊 Mevcut sirket kayıtları: <strong>" . number_format($count_sirket) . "</strong></p>";

echo "<hr>";

// Adım 3: Senkronizasyon
echo "<h3>Adım 2: Senkronizasyon Başlıyor...</h3>";

$new_count = 0;
$update_count = 0;
$skip_count = 0;
$error_count = 0;

// Logo'dan gelen tüm kayıtları al
$result = mysqli_query($db, "SELECT * FROM logo_cari_import ORDER BY code");

while ($logo_row = mysqli_fetch_assoc($result)) {
    $code = mysqli_real_escape_string($db, $logo_row['code']);
    
    // Bu code sirket tablosunda var mı?
    $check = mysqli_query($db, "SELECT s_arp_code FROM sirket WHERE s_arp_code = '$code'");
    
    if (mysqli_num_rows($check) == 0) {
        // YENİ KAYIT - INSERT
        $sql = "INSERT INTO sirket (
            s_arp_code, s_adi, s_adresi, s_il, s_country, s_country_code,
            s_telefonu, mail, specode, is_export, acikhesap, 
            payplan_code, payplan_def, trading_grp, internal_reference
        ) VALUES (
            '" . mysqli_real_escape_string($db, $logo_row['code']) . "',
            '" . mysqli_real_escape_string($db, $logo_row['definition_']) . "',
            '" . mysqli_real_escape_string($db, trim($logo_row['addr1'] . ' ' . $logo_row['addr2'])) . "',
            '" . mysqli_real_escape_string($db, $logo_row['city']) . "',
            '" . mysqli_real_escape_string($db, $logo_row['country']) . "',
            '" . mysqli_real_escape_string($db, $logo_row['country_code']) . "',
            '" . mysqli_real_escape_string($db, $logo_row['telnrs1']) . "',
            '" . mysqli_real_escape_string($db, $logo_row['emailaddr']) . "',
            '" . mysqli_real_escape_string($db, $logo_row['specode']) . "',
            " . (int)$logo_row['is_export'] . ",
            " . (float)$logo_row['bakiye'] . ",
            '" . mysqli_real_escape_string($db, $logo_row['payplan_code']) . "',
            '" . mysqli_real_escape_string($db, $logo_row['payplan_def']) . "',
            NULL,
            " . (int)$logo_row['logicalref'] . "
        )";
        
        if (mysqli_query($db, $sql)) {
            $new_count++;
        } else {
            $error_count++;
            echo "<p style='color:orange;'>⚠️ INSERT hatası: $code - " . mysqli_error($db) . "</p>";
        }
        
    } else {
        // MEVCUT KAYIT - UPDATE (sadece Logo'dan gelen alanları güncelle)
        $sql = "UPDATE sirket SET
            s_adi = '" . mysqli_real_escape_string($db, $logo_row['definition_']) . "',
            s_adresi = '" . mysqli_real_escape_string($db, trim($logo_row['addr1'] . ' ' . $logo_row['addr2'])) . "',
            s_il = '" . mysqli_real_escape_string($db, $logo_row['city']) . "',
            s_country = '" . mysqli_real_escape_string($db, $logo_row['country']) . "',
            s_country_code = '" . mysqli_real_escape_string($db, $logo_row['country_code']) . "',
            s_telefonu = '" . mysqli_real_escape_string($db, $logo_row['telnrs1']) . "',
            specode = '" . mysqli_real_escape_string($db, $logo_row['specode']) . "',
            is_export = " . (int)$logo_row['is_export'] . ",
            acikhesap = " . (float)$logo_row['bakiye'] . ",
            payplan_code = '" . mysqli_real_escape_string($db, $logo_row['payplan_code']) . "',
            payplan_def = '" . mysqli_real_escape_string($db, $logo_row['payplan_def']) . "',
            internal_reference = " . (int)$logo_row['logicalref'] . "
        WHERE s_arp_code = '$code'";
        
        if (mysqli_query($db, $sql)) {
            if (mysqli_affected_rows($db) > 0) {
                $update_count++;
            } else {
                $skip_count++; // Değişiklik yok
            }
        } else {
            $error_count++;
            echo "<p style='color:orange;'>⚠️ UPDATE hatası: $code - " . mysqli_error($db) . "</p>";
        }
    }
    
    // Her 1000 kayıtta bir ilerleme göster
    if (($new_count + $update_count + $skip_count) % 1000 == 0) {
        echo "<p>📊 İşlenen: " . number_format($new_count + $update_count + $skip_count) . " / " . number_format($count_import) . "</p>";
        flush();
    }
}

echo "<hr>";
echo "<h3>✅ Senkronizasyon Tamamlandı!</h3>";
echo "<table border='1' cellpadding='10' style='border-collapse:collapse;'>";
echo "<tr><th>İşlem</th><th>Sayı</th></tr>";
echo "<tr><td>🆕 Yeni Eklenen</td><td><strong>" . number_format($new_count) . "</strong></td></tr>";
echo "<tr><td>🔄 Güncellenen</td><td><strong>" . number_format($update_count) . "</strong></td></tr>";
echo "<tr><td>⏭️ Değişiklik Yok</td><td>" . number_format($skip_count) . "</td></tr>";
echo "<tr><td>❌ Hata</td><td>" . ($error_count > 0 ? "<span style='color:red;'>" . $error_count . "</span>" : "0") . "</td></tr>";
echo "<tr><th>TOPLAM</th><th>" . number_format($new_count + $update_count + $skip_count + $error_count) . "</th></tr>";
echo "</table>";

// Adım 4: Doğrulama
echo "<hr>";
echo "<h3>Adım 3: Doğrulama</h3>";

$final_count = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as total FROM sirket"))['total'];
$export_count = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as total FROM sirket WHERE is_export = 1"))['total'];

echo "<p>📊 Toplam sirket kaydı: <strong>" . number_format($final_count) . "</strong></p>";
echo "<p>🌍 İhracat müşterisi: <strong>" . number_format($export_count) . "</strong></p>";

// Örnek kayıtlar
echo "<h4>Örnek İhracat Müşterileri:</h4>";
$samples = mysqli_query($db, "SELECT s_arp_code, s_adi, s_country, specode FROM sirket WHERE is_export = 1 LIMIT 10");
echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
echo "<tr><th>Kod</th><th>Firma Adı</th><th>Ülke</th><th>SPECODE</th></tr>";
while ($row = mysqli_fetch_assoc($samples)) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['s_arp_code']) . "</td>";
    echo "<td>" . htmlspecialchars($row['s_adi']) . "</td>";
    echo "<td>" . htmlspecialchars($row['s_country']) . "</td>";
    echo "<td>" . htmlspecialchars($row['specode']) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<h3>🎉 İşlem Başarıyla Tamamlandı!</h3>";
echo "<p><a href='sirket_cek.php'>Şirket Listesine Git</a> | <a href='anasayfa.php'>Anasayfaya Dön</a></p>";
?>
