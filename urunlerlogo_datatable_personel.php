<?php
// urunlerlogo_datatable_personel.php
error_reporting(0);
ini_set('display_errors', '0');
ob_start();

header('Content-Type: application/json; charset=utf-8');

session_start();
require_once __DIR__ . '/include/vt.php';
require_once __DIR__ . '/ssp.php';
require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Uzak Veritabanı Bağlantısı (Güvenli)
$remoteDb = null;
$webPrices = [];
mysqli_report(MYSQLI_REPORT_OFF);

try {
    $remoteDb = mysqli_init();
    if ($remoteDb) {
        $remoteDb->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2);
        $connected = @$remoteDb->real_connect("89.43.31.214", "gemas_mehmet", "2261686Me!", "gemas_pool_technology", 3306);
        if ($connected) $remoteDb->set_charset("utf8");
        else $remoteDb = null;
    }
} catch (Exception $e) { $remoteDb = null; }

$highlightCodes = [];
$xlsPath = __DIR__ . '/assets/price_update/updated_active_product_list.xlsx';
if (is_readable($xlsPath)) {
    try {
        $sheet = IOFactory::load($xlsPath)->getActiveSheet();
        foreach ($sheet->toArray(null, true, true, true) as $index => $row) {
            if ($index === 1) continue;
            $code = trim($row['A'] ?? '');
            if ($code !== '') $highlightCodes[$code] = true;
        }
    } catch (Exception $e) {}
}

$user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : '';

$table = 'urunler';
$primaryKey = 'urun_id';

$columns = array(
    array('db' => 'stokkodu', 'dt' => 0),
    array('db' => 'stokadi', 'dt' => 1),
    array(
        'db' => 'fiyat',
        'dt' => 2,
        'formatter' => function ($d, $row) use ($user_type) {
            if ($user_type === 'Yönetici') {
                return "<input type='number' step='0.01' class='form-control domestic-price-input' 
                        value='{$d}' data-id='{$row['urun_id']}' 
                        id='domestic-price-input-{$row['urun_id']}' 
                        data-logicalref='{$row['logicalref']}' style='width:90px;'>";
            } else {
                return number_format((float)$d, 2, ',', '.');
            }
        }
    ),
    array(
        'db' => 'export_fiyat',
        'dt' => 3,
        'formatter' => function ($d, $row) use ($user_type) {
            if ($user_type === 'Yönetici') {
                return "<input type='number' step='0.01' class='form-control export-price-input' 
                        value='{$d}' data-id='{$row['urun_id']}' 
                        id='export-price-input-{$row['urun_id']}' 
                        data-logicalref='{$row['logicalref']}' style='width:90px;'>";
            } else {
                return number_format((float)$d, 2, ',', '.');
            }
        }
    ),
    // Web/App Fiyatı (Index 4)
    array(
        'db' => 'stokkodu', 
        'dt' => 4,
        'formatter' => function($d) { return '-'; }
    ),
    // YENİ: Talep Durumu (Index 5)
    array(
        'db' => 'stokkodu', 
        'dt' => 5,
        'formatter' => function($d) { return '-'; }
    ),
    // Döviz (Index 6 - Eskiden 5)
    array('db' => 'doviz', 'dt' => 6),
    // Miktar (Index 7 - Eskiden 6)
    array('db' => 'miktar', 'dt' => 7),
    // Aktif (Index 8 - Eskiden 7)
    array(
        'db' => 'logo_active',
        'dt' => 8,
        'formatter' => function ($d, $row) use ($user_type) {
            $checked = ($d == 0) ? 'checked' : '';
            if ($user_type === 'Yönetici') {
                return "<input type='checkbox' class='form-check-input active-toggle' data-stokkodu='{$row['stokkodu']}' data-current='{$row['logo_active']}' {$checked}>";
            } else {
                return $d == 0 ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-danger">Pasif</span>';
            }
        }
    ),
    // İşlemler (Index 9 - Eskiden 8)
    array(
        'db' => 'urun_id',
        'dt' => 9, 
        'formatter' => function ($d, $row) use ($user_type) {
            $domesticUpdate = !empty($row['mysql_guncelleme']) ? $row['mysql_guncelleme'] : '-';
            $exportUpdate   = !empty($row['export_mysql_guncelleme']) ? $row['export_mysql_guncelleme'] : '-';
            $fDate = function($dateStr) { if($dateStr == '-') return '-'; $ts = strtotime($dateStr); return $ts ? date('d.m.Y', $ts) : '-'; };
            $info = "<small class='text-muted d-block'>TR: " . $fDate($domesticUpdate) . "</small><small class='text-muted d-block'>EX: " . $fDate($exportUpdate) . "</small>";

            if ($user_type === 'Yönetici') {
                return "<button class='btn btn-success btn-sm update-price-btn mb-1 w-100' data-id='{$row['urun_id']}'>Güncelle</button>";
            } else {
                // MODAL TRIGGER: fiyat_talep_modal.php ile uyumlu hale getirildi (Popover kaldırıldı)
                // data-urun-id olarak $d kullanıldı (urun_id kolonu değeri)
                return "<button class='btn btn-warning btn-sm fiyat-yok-text w-100 mb-1' 
                        data-urun-id='{$d}' 
                        data-stokkodu='{$row['stokkodu']}' 
                        data-urunadi='" . htmlspecialchars($row['stokadi'], ENT_QUOTES) . "'>Talep Gönder</button>" . $info;
            }



        }
    ),
    // Detay (Index 10 - Eskiden 9)
    array(
        'db' => 'stokkodu',
        'dt' => 10,
        'formatter' => function ($d) {
            return "<button class='btn btn-outline-secondary btn-sm detail-update-btn' data-stokkodu='{$d}' title='Detay'><i class='bi bi-search'></i></button>";
        }
    ),
    array('db' => 'mysql_guncelleme', 'dt' => 'mysql_guncelleme'),
    array('db' => 'export_mysql_guncelleme', 'dt' => 'export_mysql_guncelleme'),
    array('db' => 'logicalref', 'dt' => 'logicalref'),
);

$result = SSP::simple($_GET, $sql_details, $table, $primaryKey, $columns);
$start = strtotime('2025-06-02T11:58:26'); $end = strtotime('2025-06-03T09:20:29');

$stokKodlari = [];
if (isset($result['data'])) {
    foreach ($result['data'] as $r) { if (isset($r[0])) $stokKodlari[] = $r[0]; }
}

// 1. Uzak Web Fiyat Sorgusu
if ($remoteDb && !empty($stokKodlari)) {
    $codes = array_map(function($c) use ($remoteDb) { return "'" . $remoteDb->real_escape_string($c) . "'"; }, $stokKodlari);
    $inSql = implode(',', $codes);
    $resWeb = @$remoteDb->query("SELECT stok_kodu, fiyat, doviz FROM malzeme WHERE stok_kodu IN ($inSql)");
    if ($resWeb) {
        while($wRow = $resWeb->fetch_assoc()) {
            $webPrices[trim($wRow['stok_kodu'])] = ['fiyat' => $wRow['fiyat'], 'doviz' => $wRow['doviz']];
        }
    }
    $remoteDb->close();
}

// 2. Talep Durum Sorgusu (Yerel DB)
$talepDurumlari = [];
if (!empty($stokKodlari)) {
    $myId = isset($_SESSION['yonetici_id']) ? intval($_SESSION['yonetici_id']) : 0;
    // Yerel bağlantı aç
    $localDb = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
    if (!$localDb->connect_error) {
        $localDb->set_charset("utf8");
        $codesLocal = array_map(function($c) use ($localDb) { return "'" . $localDb->real_escape_string($c) . "'"; }, $stokKodlari);
        $inSqlLocal = implode(',', $codesLocal);
        
        // Kendi taleplerim
        $scopeSql = ($user_type !== 'Yönetici') ? "AND talep_eden_id = $myId" : ""; 
        
        $qt = "SELECT stokkodu, durum FROM fiyat_talepleri WHERE stokkodu IN ($inSqlLocal) $scopeSql ORDER BY talep_id ASC";
        $resT = $localDb->query($qt);
        if ($resT) {
            while ($rT = $resT->fetch_assoc()) {
                // Her seferinde üstüne yazarak son durumu al
                $talepDurumlari[trim($rT['stokkodu'])] = $rT['durum'];
            }
        }
        $localDb->close();
    }
}

if (isset($result['data'])) {
    foreach ($result['data'] as &$row) {
        $kod = trim($row[0]);
        
        // Index 4: Web Fiyat
        if (isset($webPrices[$kod])) {
            $priceVal = number_format((float)$webPrices[$kod]['fiyat'], 2, ',', '.');
            $row[4] = '<span class="text-primary fw-bold">' . $priceVal . ' ' . $webPrices[$kod]['doviz'] . '</span>';
        } else {
            $row[4] = '-';
        }
        
        // Index 5: Talep Durumu (Yeni)
        if (isset($talepDurumlari[$kod])) {
            $st = $talepDurumlari[$kod];
            $badges = [
                'Beklemede' => 'bg-warning text-dark',
                'beklemede' => 'bg-warning text-dark',
                'Onaylandı' => 'bg-success',
                'onaylandi' => 'bg-success',
                'Reddedildi' => 'bg-danger',
                'reddedildi' => 'bg-danger'
            ];
            $cls = $badges[$st] ?? 'bg-secondary';
            $row[5] = "<span class='badge $cls'>$st</span>";

            // Eğer talep beklemedeyse, İşlemler kolonundaki butonu değiştir
            if (strtolower($st) === 'beklemede') {
                $row[9] = "<button class='btn btn-outline-warning btn-sm w-100 mb-1' disabled title='Talebiniz yönetici onayını beklemektedir.'>Güncelleme Bekliyor</button>";
            }
        } else {
            $row[5] = '-';
        }

        // data-urun-id düzeltmesi (Eğer formatter'da dt:9 üzerinden atandıysa burası sembolik kalır ama index 0'dan gelen veriye güveniyoruz)
        // SSP'de dt:9 hücresindeki urun_id değerini doğru geçmek için formatter'ı kontrol etmiştik.


        // Row Class
        $domestic = !empty($row['mysql_guncelleme']) ? strtotime($row['mysql_guncelleme']) : null;
        $exportD  = !empty($row['export_mysql_guncelleme']) ? strtotime($row['export_mysql_guncelleme']) : null;
        
        $codeValue = trim((string)($row[0] ?? ''));
        if ($codeValue !== '' && isset($highlightCodes[$codeValue])) $row['DT_RowClass'] = 'table-success';
        if (($domestic && $domestic >= $start && $domestic <= $end) || ($exportD && $exportD >= $start && $exportD <= $end)) {
            $row['DT_RowClass'] = 'table-success';
        }
    }
}

ob_clean(); 
echo json_encode($result);
exit();
