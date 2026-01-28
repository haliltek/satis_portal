<?php
// api/check_special_pricing.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

// Buffer temizle
if (ob_get_level()) ob_end_clean();

// Manuel Bağlantı (Dependencies Bypass)
$db = new mysqli('localhost', 'root', '', 'b2bgemascom_teklif');
if ($db->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB Connection Error: ' . $db->connect_error]);
    exit;
}
$db->set_charset("utf8");

$sirket_id = 0;
$work = null;
$debug_info = [];

// Eğer cari_kodu gönderilmişse
if (isset($_GET['cari_kodu']) && !empty($_GET['cari_kodu'])) {
    $cari_kod = $_GET['cari_kodu'];
    $safe_kod = $db->real_escape_string($cari_kod);
    
    // 1. Önce kod ile ara
    $query1 = "SELECT sirket_id FROM sirket WHERE s_arp_code = '$safe_kod' LIMIT 1";
    $companyResult = $db->query($query1);
    
    if ($companyResult && $companyResult->num_rows > 0) {
        $row = $companyResult->fetch_assoc();
        $sirket_id = $row['sirket_id'];
    } else {
        // 2. Bulunamazsa ID ile ara
        $query2 = "SELECT sirket_id FROM sirket WHERE sirket_id = '$safe_kod' LIMIT 1";
        $companyResult = $db->query($query2);
        
        if ($companyResult && $companyResult->num_rows > 0) {
            $row = $companyResult->fetch_assoc();
            $sirket_id = $row['sirket_id'];
        }
    }
    
    // DEBUG: Tablo boş mu?
    $count_res = $db->query("SELECT COUNT(*) as cnt FROM sirket");
    $total_rows = $count_res ? $count_res->fetch_assoc()['cnt'] : -1;
    
    $debug_info = [
        'input_code' => $cari_kod,
        'resolved_id' => $sirket_id,
        'db_error' => $db->error,
        'total_sirket_rows' => $total_rows
    ];
}

// DEBUG CHECK (Raw Output)
if (isset($_GET['debug'])) {
    echo "DB Object Type: " . gettype($db) . "<br>";
    if ($db instanceof mysqli) {
        $db_name_res = $db->query("SELECT DATABASE()");
        if ($db_name_res) {
            $db_name_row = $db_name_res->fetch_row();
            echo "Current DB: " . $db_name_row[0] . "<br>";
        }
    }
    var_dump($_GET);
    var_dump($debug_info);
    exit;
}

if ($sirket_id <= 0) {
    echo json_encode([
        'success' => false, 
        'message' => 'Geçersiz şirket ID', 
        'debug_info' => $debug_info
    ]);
    exit;
}

// Özel fiyat çalışması var mı?
// DİKKAT: Tablo sütun adı 'aktif', 'silindi' sütunu YOK.
$query3 = "SELECT * FROM ozel_fiyat_calismalari WHERE sirket_id = $sirket_id AND aktif = 1 ORDER BY id DESC LIMIT 1";
$workResult = null;

try {
    $workResult = $db->query($query3);
} catch (Exception $e) {
    $debug_info['first_query_error'] = $e->getMessage();
}

if ($workResult && $workResult->num_rows > 0) {
    $work = $workResult->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'has_work' => true,
        'debug_info' => $debug_info,
        'work' => [
            'id' => $work['id'],
            'title' => $work['baslik'],
            'currency' => 'EUR', // Veritabanında para birimi yok, default EUR
            'date' => date('d.m.Y', strtotime($work['olusturma_tarihi']))
        ]
    ]);
} else {
    echo json_encode([
        'success' => true,
        'has_work' => false,
        'debug_info' => $debug_info,
        'last_query_error' => $db->error
    ]);
}
?>
