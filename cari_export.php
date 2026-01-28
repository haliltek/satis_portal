<?php
// cari_export.php - Logo Cari Verileri Dışa Aktarıcı
// BigDump benzeri parçalı aktarım sistemi
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// AYARLAR
$records_per_session = 1000;  // Her oturumda işlenecek kayıt sayısı
$delay_per_session = 0;       // Oturumlar arası bekleme (ms)
$output_filename = 'cari.sql'; // Çıktı SQL dosyası
$ajax = true;

@set_time_limit(0);
@ini_set('auto_detect_line_endings', true);

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

if ($ajax) ob_start();

header("Expires: Mon, 1 Dec 2003 01:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
?>
<!DOCTYPE HTML>
<html>
<head>
    <title>Logo Cari Dışa Aktarıcı</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="Cache-Control" content="no-cache" />
    <style>
        body { background-color: #FFFFF0; font-family: Arial, sans-serif; }
        h1 { font-size: 20px; margin: 10px 0; }
        p { font-size: 14px; line-height: 18px; margin: 5px 0; }
        .container { max-width: 800px; margin: 20px auto; }
        .skin1 { border: 5px solid #3333EE; background-color: #AAAAEE; padding: 10px; margin: 5px; text-align: center; }
        .error { color: #FF0000; font-weight: bold; }
        .success { color: #00DD00; font-weight: bold; }
        .successcentr { color: #00DD00; background-color: #DDDDFF; font-weight: bold; text-align: center; padding: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th { background-color: #AAAAEE; color: white; padding: 8px; text-align: left; }
        td { background-color: #F8F8F8; padding: 8px; }
        .bg3 { background-color: #EEEE99; }
        .bg4 { background-color: #EEAA55; }
        .bgpctbar { background-color: #EEEEAA; }
        .pct-bar { height: 15px; background-color: #000080; }
    </style>
</head>
<body>
<div class="container">
    <div class="skin1">
        <h1>GEMAS: Logo Cari Verileri Dışa Aktarıcı v1.0</h1>
    </div>

<?php
$error = false;
$logo_conn = null;

// Logo MSSQL bağlantısı
if (!$error && !isset($_REQUEST['start'])) {
    try {
        $dsn = "sqlsrv:Server=$logo_host;Database=$logo_db";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
        ];
        $logo_conn = new PDO($dsn, $logo_user, $logo_pass, $options);
        
        // Toplam kayıt sayısını al
        $count_sql = "SELECT COUNT(*) as total FROM LG_566_CLCARD WHERE CODE IS NOT NULL AND CODE != ''";
        $stmt = $logo_conn->query($count_sql);
        $total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo "<div class='skin1'>";
        echo "<p><strong>Logo'da Toplam Cari Kaydı:</strong> " . number_format($total_records) . "</p>";
        echo "<p><strong>Çıktı Dosyası:</strong> $output_filename</p>";
        echo "<p><strong>Her Oturumda İşlenecek:</strong> $records_per_session kayıt</p>";
        echo "<p><strong>Tahmini Oturum Sayısı:</strong> " . ceil($total_records / $records_per_session) . "</p>";
        echo "</div>";
        
        // Başlat butonu
        echo "<div class='skin1'>";
        echo "<p><a href='" . $_SERVER['PHP_SELF'] . "?start=1&offset=0&total=0' style='font-size:18px; font-weight:bold; color:#003366;'>AKTARIMI BAŞLAT</a></p>";
        echo "</div>";
        
    } catch (PDOException $e) {
        echo "<p class='error'>Logo bağlantı hatası: " . htmlspecialchars($e->getMessage()) . "</p>";
        $error = true;
    }
}

// AKTARIM İŞLEMİ
if (!$error && isset($_REQUEST['start']) && isset($_REQUEST['offset'])) {
    $start = (int)$_REQUEST['start'];
    $offset = (int)$_REQUEST['offset'];
    $total_processed = (int)$_REQUEST['total'];
    
    try {
        // Logo bağlantısı
        $dsn = "sqlsrv:Server=$logo_host;Database=$logo_db";
        $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
        if (defined('PDO::SQLSRV_ATTR_ENCODING') && defined('PDO::SQLSRV_ENCODING_UTF8')) {
            $options[PDO::SQLSRV_ATTR_ENCODING] = PDO::SQLSRV_ENCODING_UTF8;
        }
        $logo_conn = new PDO($dsn, $logo_user, $logo_pass, $options);
        
        // Toplam kayıt sayısı
        $count_sql = "SELECT COUNT(*) as total FROM LG_566_CLCARD WHERE CODE IS NOT NULL AND CODE != ''";
        $stmt = $logo_conn->query($count_sql);
        $total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // İlerleme mesajı
        echo "<div class='skin1'>";
        echo "<p>İşleniyor: <strong>$output_filename</strong></p>";
        echo "<p>Toplam İşlenen Kayıt: <strong>$total_processed</strong> / <strong>$total_records</strong></p>";
        echo "</div>";
        
        // Verileri çek (OFFSET/FETCH ile - SQL Server için integer değerler direkt SQL'de olmalı)
        $sql = "
        SELECT 
            CL.LOGICALREF,
            CL.CODE,
            CL.DEFINITION_,
            CL.ADDR1,
            CL.ADDR2,
            CL.CITY,
            CL.COUNTRY,
            CL.TELNRS1,
            CL.EMAILADDR,
            CL.SPECODE,
            CASE 
                WHEN CL.COUNTRY IS NOT NULL AND CL.COUNTRY != '' AND CL.COUNTRY != 'TÜRKİYE' THEN CL.COUNTRY
                ELSE NULL
            END AS s_country_code,
            CASE 
                WHEN CL.SPECODE LIKE '%İhracat%' OR CL.SPECODE LIKE '%EXPORT%' THEN 1
                ELSE 0
            END AS is_export,
            PP.CODE AS PAYPLAN_CODE,
            PP.DEFINITION_ AS PAYPLAN_DEF,
            ISNULL(BAL.BAKIYE, 0) AS BAKIYE
        FROM LG_566_CLCARD CL
        LEFT JOIN LG_566_PAYPLANS PP ON CL.PAYMENTREF = PP.LOGICALREF
        LEFT JOIN (
            SELECT CLIENTREF,
                   SUM(CASE WHEN SIGN = 0 THEN AMOUNT ELSE -AMOUNT END) AS BAKIYE
            FROM LG_566_01_CLFLINE
            WHERE CANCELLED = 0
            GROUP BY CLIENTREF
        ) BAL ON BAL.CLIENTREF = CL.LOGICALREF
        WHERE CL.CODE IS NOT NULL AND CL.CODE != ''
        ORDER BY CL.LOGICALREF
        OFFSET $offset ROWS FETCH NEXT $records_per_session ROWS ONLY";
        
        $stmt = $logo_conn->query($sql);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $records_fetched = count($records);
        
        // SQL dosyasına yaz
        $file_mode = ($start == 1) ? 'w' : 'a'; // İlk oturumda yeni dosya, sonra append
        $fp = fopen($output_filename, $file_mode);
        
        if (!$fp) {
            throw new Exception("$output_filename dosyası açılamadı!");
        }
        
        // İlk oturumda tablo oluşturma SQL'i ekle
        if ($start == 1) {
            fwrite($fp, "-- Logo Cari Verileri Export\n");
            fwrite($fp, "-- Tarih: " . date('Y-m-d H:i:s') . "\n");
            fwrite($fp, "-- Toplam Kayıt: $total_records\n\n");
            
            fwrite($fp, "DROP TABLE IF EXISTS logo_cari_import;\n\n");
            
            fwrite($fp, "CREATE TABLE logo_cari_import (\n");
            fwrite($fp, "  id INT AUTO_INCREMENT PRIMARY KEY,\n");
            fwrite($fp, "  logicalref INT,\n");
            fwrite($fp, "  code VARCHAR(50),\n");
            fwrite($fp, "  definition_ VARCHAR(255),\n");
            fwrite($fp, "  addr1 VARCHAR(255),\n");
            fwrite($fp, "  addr2 VARCHAR(255),\n");
            fwrite($fp, "  city VARCHAR(100),\n");
            fwrite($fp, "  country VARCHAR(100),\n");
            fwrite($fp, "  country_code VARCHAR(10),\n");
            fwrite($fp, "  telnrs1 VARCHAR(50),\n");
            fwrite($fp, "  emailaddr VARCHAR(100),\n");
            fwrite($fp, "  specode VARCHAR(100),\n");
            fwrite($fp, "  is_export TINYINT(1) DEFAULT 0,\n");
            fwrite($fp, "  payplan_code VARCHAR(50),\n");
            fwrite($fp, "  payplan_def VARCHAR(255),\n");
            fwrite($fp, "  bakiye DECIMAL(18,2) DEFAULT 0,\n");
            fwrite($fp, "  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n");
            fwrite($fp, "  INDEX idx_code (code),\n");
            fwrite($fp, "  INDEX idx_is_export (is_export)\n");
            fwrite($fp, ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n");
        }
        
        // INSERT statements oluştur
        if ($records_fetched > 0) {
            fwrite($fp, "-- Batch: Offset $offset, Records: $records_fetched\n");
            fwrite($fp, "INSERT INTO logo_cari_import (logicalref, code, definition_, addr1, addr2, city, country, country_code, telnrs1, emailaddr, specode, is_export, payplan_code, payplan_def, bakiye) VALUES\n");
            
            $values = [];
            foreach ($records as $row) {
                $vals = [
                    (int)$row['LOGICALREF'],
                    "'" . addslashes($row['CODE'] ?? '') . "'",
                    "'" . addslashes($row['DEFINITION_'] ?? '') . "'",
                    "'" . addslashes($row['ADDR1'] ?? '') . "'",
                    "'" . addslashes($row['ADDR2'] ?? '') . "'",
                    "'" . addslashes($row['CITY'] ?? '') . "'",
                    "'" . addslashes($row['COUNTRY'] ?? '') . "'",
                    "'" . addslashes($row['s_country_code'] ?? '') . "'",
                    "'" . addslashes($row['TELNRS1'] ?? '') . "'",
                    "'" . addslashes($row['EMAILADDR'] ?? '') . "'",
                    "'" . addslashes($row['SPECODE'] ?? '') . "'",
                    (int)$row['is_export'],
                    "'" . addslashes($row['PAYPLAN_CODE'] ?? '') . "'",
                    "'" . addslashes($row['PAYPLAN_DEF'] ?? '') . "'",
                    (float)$row['BAKIYE']
                ];
                $values[] = '(' . implode(', ', $vals) . ')';
            }
            
            fwrite($fp, implode(",\n", $values) . ";\n\n");
        }
        
        fclose($fp);
        
        // İstatistikler
        $new_offset = $offset + $records_fetched;
        $new_total = $total_processed + $records_fetched;
        $file_size = filesize($output_filename);
        $file_mb = round($file_size / 1024 / 1024, 2);
        $pct_done = $total_records > 0 ? ceil($new_total / $total_records * 100) : 100;
        
        echo "<center>";
        echo "<table border='0' cellpadding='3' cellspacing='1'>";
        echo "<tr><th class='bg4'>Metric</th><th class='bg4'>Bu Oturum</th><th class='bg4'>Toplam</th><th class='bg4'>Kalan</th></tr>";
        echo "<tr><th class='bg4'>Kayıt</th><td class='bg3'>$records_fetched</td><td class='bg3'>$new_total</td><td class='bg3'>" . ($total_records - $new_total) . "</td></tr>";
        echo "<tr><th class='bg4'>Dosya Boyutu</th><td class='bg3'>-</td><td class='bg3'>{$file_mb} MB</td><td class='bg3'>-</td></tr>";
        echo "<tr><th class='bg4'>İlerleme %</th><td class='bg3'>-</td><td class='bg3'>{$pct_done}%</td><td class='bg3'>" . (100 - $pct_done) . "%</td></tr>";
        echo "<tr><th class='bg4'>Progress Bar</th><td class='bgpctbar' colspan='3'><div class='pct-bar' style='width:{$pct_done}%'></div></td></tr>";
        echo "</table>";
        echo "</center>";
        
        // Devam et veya bitir
        if ($new_total < $total_records) {
            $next_url = $_SERVER['PHP_SELF'] . "?start=" . ($start + 1) . "&offset=$new_offset&total=$new_total";
            
            // JavaScript ile otomatik devam
            echo "<script>window.setTimeout(function(){ location.href='$next_url'; }, 500 + $delay_per_session);</script>\n";
            
            echo "<noscript>";
            echo "<p style='text-align:center;'><a href='$next_url'>Devam Et</a> (JavaScript'i etkinleştirin)</p>";
            echo "</noscript>";
            echo "<p style='text-align:center;'>Durdurmak için <b><a href='" . $_SERVER['PHP_SELF'] . "'>DURDUR</a></b></p>";
        } else {
            echo "<p class='successcentr'>✅ EXPORT BAŞARIYLA TAMAMLANDI!</p>";
            echo "<p style='text-align:center;'><strong>Toplam:</strong> $new_total kayıt</p>";
            echo "<p style='text-align:center;'><strong>Dosya:</strong> $output_filename ({$file_mb} MB)</p>";
            echo "<p style='text-align:center;'><a href='" . $_SERVER['PHP_SELF'] . "'>Yeni Export Başlat</a></p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>Hata: " . htmlspecialchars($e->getMessage()) . "</p>";
        $error = true;
    }
}

if ($error && isset($_REQUEST['start'])) {
    echo "<p class='error'>İŞLEM HATA NEDENİYLE DURDURULDU</p>";
    echo "<p style='text-align:center;'><a href='" . $_SERVER['PHP_SELF'] . "'>Yeniden Başlat</a></p>";
}
?>

<p style="text-align:center; margin-top:20px;">&copy; <?php echo date('Y'); ?> <a href="mailto:haliltek@gemas.com.tr">Gemas Yazılım</a></p>

</div>
</body>
</html>
<?php
if ($ajax && isset($_REQUEST['start'])) {
    ob_flush();
}
?>
