<?php
// fiyat_talepleri_datatable.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ini_set('display_errors', 0);
error_reporting(E_ALL);
ob_start();

include "include/vt.php";

try {
    $db = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
    if ($db->connect_error) {
        throw new Exception("Connection failed: " . $db->connect_error);
    }
    $db->set_charset('utf8');

    $request = $_POST;
    $columns = ['id', 'stok_kodu', 'urun_adi', 'adsoyad', 'tarih', 'mevcut_fiyat_yurtici', 'oneri_fiyat_yurtici', 'mevcut_fiyat_export', 'oneri_fiyat_export', 'oneri_not', 'durum'];

    $draw = intval($request['draw'] ?? 0);
    $start = intval($request['start'] ?? 0);
    $length = intval($request['length'] ?? 10);
    $searchVal = $request['search']['value'] ?? '';
    $statusFilter = $request['status'] ?? '';

    // Order
    $orderIdx = isset($request['order'][0]['column']) ? intval($request['order'][0]['column']) : 0;
    $orderDir = isset($request['order'][0]['dir']) && strtolower($request['order'][0]['dir']) === 'desc' ? 'DESC' : 'ASC';
    $orderCol = $columns[$orderIdx] ?? 'id';
    // If ordering by urun_adi (idx 2), use p.urun_adi
    if ($orderCol == 'urun_adi') $orderCol = 'p.urun_adi';
    if ($orderCol == 'stok_kodu') $orderCol = 'f.stok_kodu';

    // Base query
    $baseQuery = "FROM fiyat_onerileri f 
                  LEFT JOIN urunler p ON p.stokkodu = f.stok_kodu";
    
    // Where
    $where = [];
    if (!empty($searchVal)) {
        $esc = $db->real_escape_string($searchVal);
        $where[] = "(f.stok_kodu LIKE '%$esc%' OR p.stokadi LIKE '%$esc%' OR f.adsoyad LIKE '%$esc%')";
    }
    if ($statusFilter !== '') {
        $escS = $db->real_escape_string($statusFilter);
        $where[] = "f.durum = '$escS'";
    }

    $whereSql = '';
    if (count($where) > 0) {
        $whereSql = 'WHERE ' . implode(' AND ', $where);
    }

    // Counts
    $countSql = "SELECT COUNT(*) $baseQuery $whereSql";
    $countRes = $db->query($countSql);
    $recordsFiltered = $countRes ? $countRes->fetch_row()[0] : 0;

    $totalRes = $db->query("SELECT COUNT(*) FROM fiyat_onerileri");
    $recordsTotal = $totalRes ? $totalRes->fetch_row()[0] : 0;

    // Data
    $sql = "SELECT f.*, 
            COALESCE(p.stokadi, '') as urun_adi,
            COALESCE(p.doviz, '') as doviz,
            COALESCE(p.miktar, 0) as miktar,
            COALESCE(p.logo_active, 0) as logo_active
            $baseQuery $whereSql 
            ORDER BY $orderCol $orderDir 
            LIMIT $start, $length";
            
    $res = $db->query($sql);
    $data = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $data[] = $row;
        }
    }

    ob_clean();
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data
    ]);

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'error' => $e->getMessage(),
        'data' => []
    ]);
}
exit;
