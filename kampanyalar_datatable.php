<?php
// kampanyalar_datatable.php
// Server-side DataTables endpoint for campaign special pricing

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
ob_start();

session_start();
require_once __DIR__ . '/include/vt.php';
require_once __DIR__ . '/ssp.php';

// Kullanıcı kontrolü
$user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : '';

$table = 'kampanya_ozel_fiyatlar';
$primaryKey = 'id';

// DataTables column definitions
$columns = array(
    // 0 - Stok Kodu
    array('db' => 'stok_kodu', 'dt' => 0),
    
    // 1 - Stok Adı (urunler tablosundan join)
    array(
        'db' => 'stok_adi',
        'dt' => 1,
        'formatter' => function($d, $row) {
            return $d ?: '-';
        }
    ),
    
    // 2 - Yurtiçi Fiyat (readonly - Logo'dan)
    array(
        'db' => 'yurtici_fiyat',
        'dt' => 2,
        'formatter' => function($d, $row) {
            $formatted = number_format($d, 2, ',', '.');
            return "<span class='text-muted'>{$formatted} €</span>";
        }
    ),
    
    // 3 - İhracat Fiyatı (readonly - Logo'dan)
    array(
        'db' => 'ihracat_fiyat',
        'dt' => 3,
        'formatter' => function($d, $row) {
            $formatted = number_format($d, 2, ',', '.');
            return "<span class='text-muted'>{$formatted} €</span>";
        }
    ),
    
    // 4 - Özel Fiyat (editable)
    array(
        'db' => 'ozel_fiyat',
        'dt' => 4,
        'formatter' => function($d, $row) use ($user_type) {
            if ($user_type === 'Yönetici') {
                return "<input type='number' step='0.01' class='form-control special-price-input' 
                        value='{$d}' data-id='{$row['id']}' 
                        style='width:120px; font-weight: bold; color: #28a745;'>";
            } else {
                return number_format($d, 2, ',', '.') . ' €';
            }
        }
    ),
    
    // 5 - Kategori
    array(
        'db' => 'kategori',
        'dt' => 5,
        'formatter' => function($d, $row) {
            if (empty($d)) return '-';
            return "<span class='badge bg-info'>{$d}</span>";
        }
    ),
    
    // 6 - İşlemler
    array(
        'db' => 'id',
        'dt' => 6,
        'formatter' => function($d, $row) use ($user_type) {
            if ($user_type === 'Yönetici') {
                return "<button class='btn btn-danger btn-sm delete-product-btn' 
                            data-id='{$d}' data-code='{$row['stok_kodu']}'>
                            <i class='bi bi-trash'></i> Sil
                        </button>";
            } else {
                return '-';
            }
        }
    )
);

try {
    $result = SSP::simple($_GET, $sql_details, $table, $primaryKey, $columns);
    
    // Her satır için, eğer Logo fiyatları 0 ise urunler tablosundan çekmeyi dene
    foreach ($result['data'] as &$row) {
        // Sonradan eklenecek: Logo sync logic
    }
    
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Veri yüklenirken hata: ' . $e->getMessage(),
        'draw' => isset($_GET['draw']) ? intval($_GET['draw']) : 0,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => []
    ]);
}

ob_end_flush();
?>
