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
        'formatter' => function ($d, $row) use ($user_type) {
            if ($user_type === 'Yönetici') {
                return "<input type='number' step='0.01' class='form-control domestic-price-input' 
                        value='{$d}' data-id='{$row['urun_id']}' 
                        id='domestic-price-input-{$row['urun_id']}' 
                        data-logicalref='{$row['logicalref']}' style='width:100px;'>";
            } else {
                return $d;
            }
        }
    ),
    // 3 - İhracat Fiyatı
    array(
        'db' => 'export_fiyat',
        'dt' => 3,
        'formatter' => function ($d, $row) use ($user_type) {
            if ($user_type === 'Yönetici') {
                return "<input type='number' step='0.01' class='form-control export-price-input' 
                        value='{$d}' data-id='{$row['urun_id']}' 
                        id='export-price-input-{$row['urun_id']}' 
                        data-logicalref='{$row['logicalref']}' style='width:100px;'>";
            } else {
                return $d;
            }
        }
    ),
    // 4 - Döviz
    array('db' => 'doviz', 'dt' => 4),
    // 5 - Stok (Miktar)
    array('db' => 'miktar', 'dt' => 5),
    // 6 - Aktiflik
    array(
        'db' => 'logo_active',
        'dt' => 6,
        'formatter' => function ($d, $row) use ($user_type) {
            $checked = ($d == 0) ? 'checked' : '';
            if ($user_type === 'Yönetici') {
                $urunAdiAttr = htmlspecialchars($row['stokadi'], ENT_QUOTES);
                $gempaAcc = isset($row['GEMPA2026logical']) ? $row['GEMPA2026logical'] : '';
                $gemasAcc = isset($row['GEMAS2026logical']) ? $row['GEMAS2026logical'] : '';
                return "<input type='checkbox' class='form-check-input active-toggle' data-stokkodu='{$row['stokkodu']}' data-gempa='{$gempaAcc}' data-gemas='{$gemasAcc}' data-current='{$row['logo_active']}' data-urunadi='{$urunAdiAttr}' {$checked}>";
            } else {
                return $d == 0 ? 'Aktif' : 'Pasif';
            }
        }
    ),
    // 7 - Fiyat İşlemi: Güncelleme butonu + güncelleme tarih bilgileri
    array(
        'db' => 'urun_id',
        'dt' => 7,
        'formatter' => function ($d, $row) use ($user_type) {
            $domesticUpdate = !empty($row['mysql_guncelleme']) ? $row['mysql_guncelleme'] : '-';
            $exportUpdate   = !empty($row['export_mysql_guncelleme']) ? $row['export_mysql_guncelleme'] : '-';
            $updateInfo = "<div class='update-info d-flex flex-column'>
                            <span class='badge bg-info mb-1'>Yurtiçi: {$domesticUpdate}</span>
                            <span class='badge bg-warning'>İhracat: {$exportUpdate}</span>
                           </div>";
            if ($user_type === 'Yönetici') {
                $gemasRef = isset($row['GEMAS2026logical']) ? $row['GEMAS2026logical'] : '';
                $gempaRef = isset($row['GEMPA2026logical']) ? $row['GEMPA2026logical'] : '';
                $btn = "<button class='btn btn-success btn-sm update-price-btn'
                            data-id='{$row['urun_id']}'
                            data-stokkodu='{$row['stokkodu']}'
                            data-logicalref='{$row['logicalref']}'
                            data-GEMAS2026logical='{$gemasRef}'
                            data-GEMPA2026logical='{$gempaRef}'>
                            Fiyat Güncelle
                        </button>";
                return $btn . "<br>" . $updateInfo;
            } else {
                return $updateInfo;
            }
        }
    ),
    // 8 - Detay Güncelle Butonu
    array(
        'db' => 'stokkodu',
        'dt' => 8,
        'formatter' => function ($d, $row) {
            return "<button class='btn btn-secondary btn-sm detail-update-btn' 
                        data-stokkodu='{$d}'>
                        Detay Görüntüle
                    </button>";
        }
    ),
    // 9 - mysql_guncelleme (sadece sunucu tarafında kullanılıyor)
    array('db' => 'mysql_guncelleme', 'dt' => 'mysql_guncelleme'),
    // 10 - export_mysql_guncelleme (sadece sunucu tarafında kullanılıyor)
    array('db' => 'export_mysql_guncelleme', 'dt' => 'export_mysql_guncelleme'),
    // 11 - logicalref (sadece sunucu tarafında kullanılıyor)
    array('db' => 'logicalref', 'dt' => 'logicalref'),
    // 12 - GEMAS2026logical (sadece sunucu tarafında kullanılıyor)
    // 12 - GEMAS2026logical (sadece sunucu tarafında kullanılıyor)
    // array('db' => 'GEMAS2026logical', 'dt' => 'GEMAS2026logical'),
    // 13 - GEMPA2026logical (sadece sunucu tarafında kullanılıyor)
    // array('db' => 'GEMPA2026logical', 'dt' => 'GEMPA2026logical'),
);


$result = SSP::simple($_GET, $sql_details, $table, $primaryKey, $columns);

$start = strtotime('2025-06-02T11:58:26');
$end   = strtotime('2025-06-03T09:20:29');

foreach ($result['data'] as &$row) {
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
    for ($i = 0; $i <= 8; $i++) {
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
