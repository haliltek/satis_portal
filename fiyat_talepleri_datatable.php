<?php
// fiyat_talepleri_datatable.php - Yeni tablo yapısı ile güncellendi
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
    $columns = ['talep_id', 'stokkodu', 'stokadi', 'talep_eden_adi', 'talep_tarihi', 'talep_notu', 'durum', 'onerilen_fiyat'];

    $draw = intval($request['draw'] ?? 0);
    $start = intval($request['start'] ?? 0);
    $length = intval($request['length'] ?? 10);
    $searchVal = $request['search']['value'] ?? '';
    $statusFilter = $request['status'] ?? '';

    // Order
    $orderIdx = isset($request['order'][0]['column']) ? intval($request['order'][0]['column']) : 0;
    $orderDir = isset($request['order'][0]['dir']) && strtolower($request['order'][0]['dir']) === 'desc' ? 'DESC' : 'ASC';
    $orderCol = $columns[$orderIdx] ?? 'talep_id';

    // Base query - Yeni tablo yapısı
    $baseQuery = "FROM fiyat_talepleri ft";
    
    // Where
    $where = [];
    
    // Personel ise sadece kendi taleplerini göster
    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] !== 'Yönetici') {
        $yonetici_id = intval($_SESSION['yonetici_id'] ?? 0);
        if ($yonetici_id > 0) {
            $where[] = "ft.talep_eden_id = $yonetici_id";
        }
    }
    
    if (!empty($searchVal)) {
        $esc = $db->real_escape_string($searchVal);
        $where[] = "(ft.stokkodu LIKE '%$esc%' OR ft.stokadi LIKE '%$esc%' OR ft.talep_eden_adi LIKE '%$esc%' OR ft.talep_notu LIKE '%$esc%')";
    }
    if ($statusFilter !== '') {
        $escS = $db->real_escape_string($statusFilter);
        $where[] = "ft.durum = '$escS'";
    }

    $whereSql = '';
    if (count($where) > 0) {
        $whereSql = 'WHERE ' . implode(' AND ', $where);
    }

    // Counts
    $countSql = "SELECT COUNT(*) $baseQuery $whereSql";
    $countRes = $db->query($countSql);
    $recordsFiltered = $countRes ? $countRes->fetch_row()[0] : 0;

    $totalRes = $db->query("SELECT COUNT(*) FROM fiyat_talepleri");
    $recordsTotal = $totalRes ? $totalRes->fetch_row()[0] : 0;

    // Data
    $sql = "SELECT ft.talep_id,
            ft.urun_id,
            ft.stokkodu,
            ft.stokadi,
            ft.talep_eden_id,
            ft.talep_eden_adi,
            ft.talep_notu,
            ft.talep_tarihi,
            ft.durum,
            ft.yonetici_notu,
            ft.cevaplayan_id,
            ft.cevap_tarihi,
            ft.onerilen_fiyat,
            ft.onerilen_doviz
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
