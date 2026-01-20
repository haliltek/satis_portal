<?php
session_start();
include "include/vt.php";
header('Content-Type: application/json; charset=utf-8');

$db = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
$db->set_charset('utf8');

$sql = "UPDATE urun_fiyat_log l JOIN urunler u ON u.stokkodu = l.stokkodu SET l.stokadi = u.stokadi WHERE l.stokadi IS NULL OR l.stokadi = ''";
$result = $db->query($sql);

if ($result) {
    echo json_encode(['status' => 'success', 'updated' => $db->affected_rows]);
} else {
    echo json_encode(['status' => 'error', 'error' => $db->error]);
}
