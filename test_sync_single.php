<?php
// Test: Tek bir şirket kaydını senkronize et
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

echo "<h2>🧪 Senkronizasyon Testi</h2>";
echo "<pre>";

// Session'da veri var mı kontrol et
if (empty($_SESSION['sync_dataset'])) {
    echo "❌ Session'da 'sync_dataset' bulunamadı!\n";
    echo "📝 Önce sirket_cek.php sayfasını açıp veriyi yüklemelisiniz.\n\n";
    echo "<a href='sirket_cek.php'>👉 Şirket Çek Sayfasına Git</a>\n";
    exit;
}

$dataset = $_SESSION['sync_dataset'];
echo "✅ Session'da " . count($dataset) . " kayıt bulundu\n\n";

// İlk kaydı alalım
$firstCode = array_key_first($dataset);
$firstRecord = $dataset[$firstCode];

echo "📋 Test Kaydı:\n";
echo "─────────────────────────────────────\n";
echo "Kod: $firstCode\n";
echo "Ad: " . ($firstRecord['s_adi'] ?? 'N/A') . "\n";
echo "Internal Ref: " . ($firstRecord['internal_reference'] ?? 'N/A') . "\n\n";

// sync_row.php'yi simüle et
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

$row = $firstRecord;
if (isset($row['s_country_code'])) {
    $row['s_country_code'] = substr((string)$row['s_country_code'], 0, 5);
}

try {
    $mysql_dsn = "mysql:host=$mysql_host;dbname=$mysql_dbname;charset=utf8mb4";
    $pdo = new PDO($mysql_dsn, $mysql_username, $mysql_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ Veritabanı bağlantısı başarılı\n\n";
    
    // Kayıt var mı kontrol et
    $check = $pdo->prepare('SELECT sirket_id FROM sirket WHERE s_arp_code = ? OR internal_reference = ?');
    $check->execute([$firstCode, $row['internal_reference']]);
    $existing = $check->fetch();
    
    if ($existing) {
        echo "📝 Kayıt MEVCUT (sirket_id: {$existing['sirket_id']})\n";
        echo "🔄 UPDATE işlemi yapılacak...\n\n";
        
        $update = $pdo->prepare('UPDATE sirket SET 
            internal_reference=?, s_adi=?, s_adresi=?, s_il=?, s_ilce=?, 
            s_country_code=?, s_country=?, s_telefonu=?, mail=?, acikhesap=?, 
            payplan_code=?, payplan_def=?, trading_grp=?, logo_company_code=? 
            WHERE s_arp_code=? OR internal_reference=?');
        
        $result = $update->execute([
            $row['internal_reference'],
            $row['s_adi'],
            $row['s_adresi'],
            $row['s_il'],
            $row['s_ilce'],
            $row['s_country_code'],
            $row['s_country'],
            $row['s_telefonu'],
            $row['mail'],
            $row['acikhesap'],
            $row['payplan_code'],
            $row['payplan_def'],
            $row['trading_grp'],
            $row['logo_company_code'],
            $firstCode,
            $row['internal_reference']
        ]);
        
        echo "✅ UPDATE BAŞARILI!\n";
        echo "Etkilenen satır: " . $update->rowCount() . "\n";
        
    } else {
        echo "🆕 Kayıt YOK - INSERT yapılacak...\n\n";
        
        $insert = $pdo->prepare('INSERT INTO sirket 
            (internal_reference, s_adi, s_arp_code, s_adresi, s_il, s_ilce, 
            s_country_code, s_country, s_telefonu, s_vno, s_vd, yetkili, mail, 
            mailsifre, smtp, port, kategori, acikhesap, logo_company_code, 
            payplan_code, payplan_def, trading_grp) 
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        
        $result = $insert->execute([
            $row['internal_reference'],
            $row['s_adi'],
            $firstCode,
            $row['s_adresi'],
            $row['s_il'],
            $row['s_ilce'],
            $row['s_country_code'],
            $row['s_country'],
            $row['s_telefonu'],
            $row['s_vno'],
            $row['s_vd'],
            $row['yetkili'],
            $row['mail'],
            $row['mailsifre'],
            $row['smtp'],
            $row['port'],
            $row['kategori'],
            $row['acikhesap'],
            $row['logo_company_code'],
            $row['payplan_code'],
            $row['payplan_def'],
            $row['trading_grp']
        ]);
        
        echo "✅ INSERT BAŞARILI!\n";
        echo "Yeni ID: " . $pdo->lastInsertId() . "\n";
    }
    
    echo "\n🎉 Senkronizasyon testi BAŞARILI!\n";
    echo "\n💡 Artık sirket_cek.php'deki 'Güncelle ve Aktar' butonu çalışmalı.\n";
    
} catch (PDOException $e) {
    echo "❌ HATA: " . $e->getMessage() . "\n";
    echo "\n📋 Hata Detayları:\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "</pre>";
?>
