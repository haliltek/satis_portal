<?php
// urun_fiyat_log_changes.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "include/vt.php";
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['yonetici_id'])) {
    echo json_encode(['status' => 'error', 'error' => 'Yetkisiz erişim']);
    exit;
}

$startRaw = $_GET['start'] ?? '';
$endRaw   = $_GET['end']   ?? '';
$start = $startRaw ? str_replace('T',' ', $startRaw) : '';
$end   = $endRaw ? str_replace('T',' ', $endRaw) : '';

if ($start === '' || $end === '') {
    echo json_encode(['status' => 'error', 'error' => 'Tarih aralığı gerekli']);
    exit;
}

$_SESSION['revert_start'] = $startRaw;
$_SESSION['revert_end']   = $endRaw;

$db = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
$db->set_charset('utf8');

$startEsc = $db->real_escape_string($start);
$endEsc   = $db->real_escape_string($end);

$query = "SELECT l.stokkodu,
    MAX(CASE WHEN l.fiyat_tipi='domestic' THEN l.log_id END) AS domestic_log_id,
    MAX(CASE WHEN l.fiyat_tipi='domestic' THEN l.stokadi END) AS stokadi_dom,
    MAX(CASE WHEN l.fiyat_tipi='domestic' THEN l.onceki_fiyat END) AS eski_domestic,
    MAX(CASE WHEN l.fiyat_tipi='domestic' THEN l.yeni_fiyat END) AS yeni_domestic,
    MAX(CASE WHEN l.fiyat_tipi='domestic' THEN u.fiyat END) AS current_domestic,
    MAX(CASE WHEN l.fiyat_tipi='domestic' THEN l.guncelleyen END) AS dom_user_id,
    MAX(CASE WHEN l.fiyat_tipi='domestic' THEN l.guncelleme_tarihi END) AS dom_date,
    MAX(CASE WHEN l.fiyat_tipi='export' THEN l.log_id END) AS export_log_id,
    MAX(CASE WHEN l.fiyat_tipi='export' THEN l.stokadi END) AS stokadi_exp,
    MAX(CASE WHEN l.fiyat_tipi='export' THEN l.onceki_fiyat END) AS eski_export,
    MAX(CASE WHEN l.fiyat_tipi='export' THEN l.yeni_fiyat END) AS yeni_export,
    MAX(CASE WHEN l.fiyat_tipi='export' THEN u.export_fiyat END) AS current_export,
    MAX(CASE WHEN l.fiyat_tipi='export' THEN l.guncelleyen END) AS exp_user_id,
    MAX(CASE WHEN l.fiyat_tipi='export' THEN l.guncelleme_tarihi END) AS exp_date
    FROM urun_fiyat_log l
    LEFT JOIN urunler u ON u.stokkodu = l.stokkodu
    WHERE l.guncelleme_tarihi BETWEEN '$startEsc' AND '$endEsc'
      AND l.reverted = 0
    GROUP BY l.stokkodu
    ORDER BY l.stokkodu";

$res = $db->query($query);
$data = [];
$userIds = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $row['stokadi'] = $row['stokadi_dom'] ?: $row['stokadi_exp'] ?: '';
        $userId = $row['dom_user_id'] ?: $row['exp_user_id'];
        if ($userId) { $userIds[$userId] = true; }
        $row['user_id'] = $userId;
        $row['guncelleme_tarihi'] = $row['dom_date'] ?: $row['exp_date'] ?: '';
        unset($row['stokadi_dom'], $row['stokadi_exp'], $row['dom_user_id'], $row['exp_user_id'], $row['dom_date'], $row['exp_date']);
        $data[] = $row;
    }
}

$names = [];
if (!empty($userIds)) {
    $ids = implode(',', array_map('intval', array_keys($userIds)));
    $res2 = $db->query("SELECT yonetici_id, adsoyad FROM yonetici WHERE yonetici_id IN ($ids)");
    if ($res2) {
        while ($r = $res2->fetch_assoc()) {
            $names[$r['yonetici_id']] = $r['adsoyad'];
        }
    }
}

foreach ($data as &$r) {
    $id = $r['user_id'];
    $r['adsoyad'] = $id && isset($names[$id]) ? $names[$id] : '';
    unset($r['user_id']);
}
unset($r);

echo json_encode(['status' => 'success', 'data' => $data]);
