<?php
// urun_fiyat_log_datatable.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Hatalari ekrana degil loga bas
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Ciktilari tamponla
ob_start();

include "include/vt.php";

// Log request
// file_put_contents(__DIR__ . '/debug_request.txt', print_r($_REQUEST, true));

$response = [];

try {
    // Veritabanı bağlantısı
    $db = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
    if ($db->connect_error) {
        throw new Exception("Connection failed: " . $db->connect_error);
    }
    $db->set_charset('utf8');

    // ... rest of logic
    header('Content-Type: application/json; charset=utf-8');
    
    $request = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;


$columns = [
    'log_id',
    'stokkodu',
    'stokadi',
    'adsoyad',
    'guncelleme_tarihi',
    'onceki_domestic',
    'onceki_export',
    'fiyat_farki',
    'guncel_fiyat_domestic',
    'guncel_fark_domestic',
    'guncel_fiyat_export',
    'guncel_fark_export',
    'reverted'
];

$start = isset($request['start']) ? intval($request['start']) : 0;
$length = isset($request['length']) ? intval($request['length']) : 10;
$searchValue = $request['search']['value'] ?? '';
$user = $request['user'] ?? '';
$startDate = $request['start_date'] ?? '';
$endDate = $request['end_date'] ?? '';
$reverted = $request['reverted'] ?? '';
$orderIdx = isset($request['order'][0]['column']) ? intval($request['order'][0]['column']) : 0;
$orderDir = isset($request['order'][0]['dir']) && strtolower($request['order'][0]['dir']) === 'desc' ? 'DESC' : 'ASC';
$orderCol = $columns[$orderIdx] ?? 'guncelleme_tarihi';

$baseQuery = "FROM urun_fiyat_log u LEFT JOIN yonetici y ON y.yonetici_id = u.guncelleyen LEFT JOIN urunler p ON p.stokkodu = u.stokkodu";
$where = [];
if ($searchValue !== '') {
    $search = $db->real_escape_string($searchValue);
    $where[] = "(u.stokkodu LIKE '%$search%' OR u.stokadi LIKE '%$search%' OR y.adsoyad LIKE '%$search%')";
}
if ($user !== '') {
    $userId = intval($user);
    $where[] = "u.guncelleyen = $userId";
}
if ($startDate !== '') {
    $sd = $db->real_escape_string($startDate);
    $where[] = "DATE(u.guncelleme_tarihi) >= '$sd'";
}
if ($endDate !== '') {
    $ed = $db->real_escape_string($endDate);
    $where[] = "DATE(u.guncelleme_tarihi) <= '$ed'";
}
if ($reverted !== '') {
    if ($reverted == '1') {
        $where[] = "u.reverted > 0";
    } elseif ($reverted == '0') {
        $where[] = "u.reverted = 0";
    }
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$totalResult = $db->query("SELECT COUNT(*) $baseQuery");
$totalRecords = $totalResult ? (int)$totalResult->fetch_row()[0] : 0;

$filteredResult = $db->query("SELECT COUNT(*) $baseQuery $whereSql");
$recordsFiltered = $filteredResult ? (int)$filteredResult->fetch_row()[0] : 0;

$limitSql = $length > 0 ? "LIMIT $start, $length" : '';
$query = "SELECT u.log_id, u.stokkodu, COALESCE(u.stokadi,'') AS stokadi, COALESCE(y.adsoyad,'-') as adsoyad, u.guncelleme_tarihi,
    CASE WHEN u.fiyat_tipi='domestic' THEN u.onceki_fiyat END AS onceki_domestic,
    CASE WHEN u.fiyat_tipi='domestic' THEN u.yeni_fiyat END AS yeni_domestic,
    CASE WHEN u.fiyat_tipi='export' THEN u.onceki_fiyat END AS onceki_export,
    CASE WHEN u.fiyat_tipi='export' THEN u.yeni_fiyat END AS yeni_export,
    (u.yeni_fiyat - u.onceki_fiyat) AS fiyat_farki,
    p.fiyat AS guncel_fiyat_domestic,
    CASE WHEN u.fiyat_tipi='domestic' THEN (p.fiyat - u.yeni_fiyat) END AS guncel_fark_domestic,
    p.export_fiyat AS guncel_fiyat_export,
    CASE WHEN u.fiyat_tipi='export' THEN (p.export_fiyat - u.yeni_fiyat) END AS guncel_fark_export,
    u.reverted $baseQuery $whereSql ORDER BY $orderCol $orderDir $limitSql";
$res = $db->query($query);
$data = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
}

$response = [
    'draw' => isset($request['draw']) ? intval($request['draw']) : 0,
    'recordsTotal' => $totalRecords,
    'recordsFiltered' => $recordsFiltered,
    'data' => $data
];

    echo json_encode($response);

} catch (Exception $e) {
    // Tamponu temizle ki sadece JSON gönderilsin
    ob_end_clean();
    echo json_encode([
        'error' => $e->getMessage(),
        'draw' => isset($_REQUEST['draw']) ? intval($_REQUEST['draw']) : 0,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => []
    ]);
}
// Tamponu gönder
ob_end_flush();
exit;
