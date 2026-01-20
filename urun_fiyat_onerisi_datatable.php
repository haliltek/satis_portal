<?php
// urunlerlogo_datatable.php
// DataTables sunucu tarafı isteğine JSON dönebilmek için çıktı tipini baştan
// tanımlıyoruz ve uyarı çıktılarının JSON biçimini bozmasını engelliyoruz.
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
// Tüm çıktıları tamponlayarak olası uyarıların JSON'u bozmasını engelle
ob_start();

session_start();
require_once __DIR__ . '/include/vt.php'; // Veritabanı bağlantısı ve $sql_details tanımlı olsun
require_once __DIR__ . '/ssp.php';
require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Helper to get external prices
function getExternalPrices($stockCodes) {
    if (empty($stockCodes)) return [];
    
    $dsn = "mysql:host=89.43.31.214;port=3306;dbname=gemas_pool_technology;charset=utf8";
    $username = "gemas_mehmet";
    $password = "2261686Me!";
    
    try {
        $pdo = new PDO($dsn, $username, $password, [
             PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
             PDO::ATTR_TIMEOUT => 2
        ]);
        
        $placeholders = implode(',', array_fill(0, count($stockCodes), '?'));
        // Using urunler_full_view as identified
        $sql = "SELECT stok_kodu, fiyat FROM urunler_full_view WHERE stok_kodu IN ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($stockCodes);
        
        $prices = [];
        while ($row = $stmt->fetch()) {
            $prices[$row['stok_kodu']] = $row['fiyat'];
        }
        return $prices;
    } catch (Exception $e) {
        return [];
    }
}

// Product codes that should be highlighted in the table. These are read
// from the first column of updated_active_product_list.xlsx.
$highlightCodes = [];
$xlsPath = __DIR__ . '/assets/price_update/updated_active_product_list.xlsx';
if (is_readable($xlsPath)) {
    try {
        $sheet = IOFactory::load($xlsPath)->getActiveSheet();
        foreach ($sheet->toArray(null, true, true, true) as $index => $row) {
            if ($index === 1) continue; // header
            $code = trim($row['A'] ?? '');
            if ($code !== '') {
                $highlightCodes[$code] = true;
            }
        }
    } catch (Exception $e) {
        // ignore errors reading the file
    }
}

// Kullanıcı tipini oturumdan alıyoruz
$user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : '';

// Tablonun adı ve birincil anahtar
$table = 'urunler';
$primaryKey = 'urun_id';

// DataTables için sütun tanımları (Marka sütunu kaldırıldı)
$columns = array(
    // 0 - Stok Kodu
    array('db' => 'stokkodu', 'dt' => 0),
    // 1 - Stok Adı
    array('db' => 'stokadi', 'dt' => 1),
    // 2 - Yurtiçi Fiyatı
    array(
        'db' => 'fiyat',
        'dt' => 2,
        'formatter' => function ($d, $row) {
            return $d;
        }
    ),
    // 3 - İhracat Fiyatı
    array(
        'db' => 'export_fiyat',
        'dt' => 3,
        'formatter' => function ($d, $row) {
            return $d;
        }
    ),
    // 4 - Web/App Fiyat (Placeholder, populated later)
    array('db' => 'stokkodu', 'dt' => 4),
    // 5 - Döviz
    array('db' => 'doviz', 'dt' => 5),
    // 6 - Stok (Miktar)
    array('db' => 'miktar', 'dt' => 6),
    // 7 - Aktiflik
    array(
        'db' => 'logo_active',
        'dt' => 7,
        'formatter' => function ($d, $row) use ($user_type) {
            $checked = ($d == 0) ? 'checked' : '';
            return $d == 0 ? 'Aktif' : 'Pasif';
        }
    ),
    // 8 - Fiyat İşlemi: Öner Ver butonu
    array(
        'db' => 'urun_id',
        'dt' => 8,
        'formatter' => function ($d, $row) {
            $domesticUpdate = !empty($row['mysql_guncelleme']) ? $row['mysql_guncelleme'] : '-';
            $exportUpdate   = !empty($row['export_mysql_guncelleme']) ? $row['export_mysql_guncelleme'] : '-';
            $updateInfo = "<div class='update-info d-flex flex-column mb-1'>
                            <span class='badge bg-info mb-1'>Yurtiçi: {$domesticUpdate}</span>
                            <span class='badge bg-warning'>İhracat: {$exportUpdate}</span>
                           </div>";
            $btn = "<button class='btn btn-primary btn-sm suggest-price-btn' 
                        data-id='{$row['urun_id']}' 
                        data-stokkodu='{$row['stokkodu']}' 
                        data-domestic='{$row['fiyat']}' 
                        data-export='{$row['export_fiyat']}' 
                        data-urunadi='" . htmlspecialchars($row['stokadi'], ENT_QUOTES) . "'>
                        Öneri Ver
                    </button>";
            return $updateInfo . $btn;
        }
    ),
    // 9 - Detay Güncelle Butonu
    array(
        'db' => 'stokkodu',
        'dt' => 9,
        'formatter' => function ($d, $row) {
            return "<button class='btn btn-secondary btn-sm detail-update-btn' 
                        data-stokkodu='{$d}'>
                        Detay Görüntüle
                    </button>";
        }
    ),
    // 10 - mysql_guncelleme (sadece sunucu tarafında kullanılıyor)
    array('db' => 'mysql_guncelleme', 'dt' => 'mysql_guncelleme'),
    // 11 - export_mysql_guncelleme (sadece sunucu tarafında kullanılıyor)
    array('db' => 'export_mysql_guncelleme', 'dt' => 'export_mysql_guncelleme'),
    // 12 - logicalref (sadece sunucu tarafında kullanılıyor)
    array('db' => 'logicalref', 'dt' => 'logicalref'),
    // 12 - GEMAS2026logical (sadece sunucu tarafında kullanılıyor)
    // 12 - GEMAS2026logical (sadece sunucu tarafında kullanılıyor)
    // array('db' => 'GEMAS2026logical', 'dt' => 'GEMAS2026logical'),
    // 13 - GEMPA2026logical (sadece sunucu tarafında kullanılıyor)
    // array('db' => 'GEMPA2026logical', 'dt' => 'GEMPA2026logical'),
);


$result = SSP::simple($_GET, $sql_details, $table, $primaryKey, $columns);

// Fetch external prices
$codes = [];
foreach ($result['data'] as $row) {
    // $row[0] is stokkodu
    $codes[] = $row[0];
}
$webPrices = getExternalPrices($codes);


$start = strtotime('2025-06-02T11:58:26');
$end   = strtotime('2025-06-03T09:20:29');

foreach ($result['data'] as &$row) {
    // Populate Web Price
    $stok = $row[0];
    $val = isset($webPrices[$stok]) ? $webPrices[$stok] : '-';
    // Format to 2 decimal places if it's numeric
    if (is_numeric($val)) {
        $row[4] = number_format((float)$val, 2, '.', '');
    } else {
        $row[4] = $val;
    }

    $domestic = !empty($row['mysql_guncelleme']) ? strtotime($row['mysql_guncelleme']) : null;
    $exportD  = !empty($row['export_mysql_guncelleme']) ? strtotime($row['export_mysql_guncelleme']) : null;

    $codeValue = trim((string)($row[0] ?? ''));
    if ($codeValue !== '' && isset($highlightCodes[$codeValue])) {
        $row['DT_RowClass'] = 'table-success';
    }

    if (($domestic && $domestic >= $start && $domestic <= $end) ||
        ($exportD && $exportD >= $start && $exportD <= $end)) {
        $row['DT_RowClass'] = 'table-success';
    }

    $clean = [];
    // Adjust loop to include the new column (total 10 visible columns: 0-9)
    for ($i = 0; $i <= 9; $i++) {
        $clean[] = $row[$i];
    }
    if (isset($row['DT_RowClass'])) {
        $clean['DT_RowClass'] = $row['DT_RowClass'];
    }
    $row = $clean;
}

echo json_encode($result);
// Tamponu temizleyip yalnızca JSON çıktısını gönder
ob_end_flush();
exit();
