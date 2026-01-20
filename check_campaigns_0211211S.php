<?php
require_once __DIR__ . "/fonk.php";

$config = require __DIR__ . '/config/config.php';
$logo = $config['logo'];

try {
    // Logo bağlantısı
    $dsn = "sqlsrv:Server={$logo['host']};Database={$logo['db']}";
    $pdo = new PDO($dsn, $logo['user'], $logo['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
    ]);
    
    // Tiger bağlantısı (filtre tabloları için)
    $dsnTiger = "sqlsrv:Server={$logo['host']};Database=Tiger";
    $pdoTiger = new PDO($dsnTiger, $logo['user'], $logo['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
    ]);
    
    echo "=== AKTİF KAMPANYALAR ===\n\n";
    
    // Aktif satış kampanyalarını çek
    $sql = "SELECT LOGICALREF, NAME, PRIORITY, VARIABLEDEFS1, VARIABLEDEFS2, VARIABLEDEFS3, ACTIVE, CARDTYPE 
            FROM LG_566_CAMPAIGN 
            WHERE ACTIVE = 0 AND CARDTYPE = 2 
            ORDER BY PRIORITY DESC";
    
    $stmt = $pdo->query($sql);
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Toplam Kampanya Sayısı: " . count($campaigns) . "\n\n";
    
    foreach ($campaigns as $i => $camp) {
        echo "--- Kampanya " . ($i + 1) . " ---\n";
        echo "Adı: " . $camp['NAME'] . "\n";
        echo "Priority: " . $camp['PRIORITY'] . "\n";
        echo "V1 (Listede var mı): " . $camp['VARIABLEDEFS1'] . "\n";
        echo "V2 (İskonto oranı): " . $camp['VARIABLEDEFS2'] . "\n";
        echo "V3 (Min miktar): " . $camp['VARIABLEDEFS3'] . "\n\n";
    }
    
    echo "\n=== 0211211S İÇİN KONTROL ===\n\n";
    
    $productCode = '0211211S';
    
    foreach ($campaigns as $i => $camp) {
        echo "--- Kampanya: " . $camp['NAME'] . " ---\n";
        
        // V1: Ürün listede var mı?
        $v1 = $camp['VARIABLEDEFS1'];
        
        // Tabloyu bul (MEG_565_FILTRE veya HALIL_OZEL_FIYAT)
        if (strpos($v1, 'MEG_565_FILTRE') !== false) {
            $tableName = 'MEG_565_FILTRE';
        } elseif (strpos($v1, 'HALIL_OZEL_FIYAT') !== false) {
            $tableName = 'HALIL_OZEL_FIYAT';
        } else {
            echo "  → Tablo bulunamadı\n\n";
            continue;
        }
        
        // COUNT kontrolü
        $sql = "SELECT COUNT(*) FROM $tableName WHERE KOD = ?";
        $stmt = $pdoTiger->prepare($sql);
        $stmt->execute([$productCode]);
        $count = $stmt->fetchColumn();
        
        echo "  → Ürün listede var mı? " . ($count > 0 ? "EVET ($count)" : "HAYIR") . "\n";
        
        if ($count > 0) {
            // V2: İskonto oranı
            $v2 = $camp['VARIABLEDEFS2'];
            if (strpos($v2, '_SQLINFO') !== false) {
                // Parse SQL
                if (preg_match('/_SQLINFO\("([^"]+)","([^"]+)","([^"]+)"\)/', $v2, $matches)) {
                    $col = $matches[1];
                    $table = $matches[2];
                    $where = str_replace(["'+P101+'", '"+P101+"'], $productCode, $matches[3]);
                    
                    $sql2 = "SELECT TOP 1 $col FROM $table WHERE $where";
                    echo "  → İskonto SQL: $sql2\n";
                    
                    $stmt2 = $pdoTiger->query($sql2);
                    $rate = $stmt2->fetchColumn();
                    echo "  → İskonto Oranı: " . ($rate ?: 0) . "%\n";
                }
            }
            
            // V3: Min miktar
            $v3 = $camp['VARIABLEDEFS3'];
            if (strpos($v3, '_SQLINFO') !== false) {
                if (preg_match('/_SQLINFO\("([^"]+)","([^"]+)","([^"]+)"\)/', $v3, $matches)) {
                    $col = $matches[1];
                    $table = $matches[2];
                    $where = str_replace(["'+P101+'", '"+P101+"'], $productCode, $matches[3]);
                    
                    $sql3 = "SELECT TOP 1 $col FROM $table WHERE $where";
                    echo "  → Min Miktar SQL: $sql3\n";
                    
                    $stmt3 = $pdoTiger->query($sql3);
                    $minQty = $stmt3->fetchColumn();
                    echo "  → Min Miktar: " . ($minQty ?: 0) . "\n";
                    echo "  → 115 adet için uygun mu? " . (115 >= $minQty ? "EVET ✓" : "HAYIR ✗") . "\n";
                }
            }
        }
        
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "HATA: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>
