<?php
// api/debug_cols.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
if (ob_get_level()) ob_end_clean();
ob_implicit_flush(true);
header('Content-Type: application/json; charset=utf-8');

$db = new mysqli('localhost', 'root', '', 'b2bgemascom_teklif');
if ($db->connect_error) {
    die(json_encode(['error' => $db->connect_error]));
}
$db->set_charset("utf8");

$res = $db->query("SELECT * FROM sirket LIMIT 1");
if ($res) {
    $row = $res->fetch_assoc();
    echo json_encode(['columns' => array_keys($row)]);
} else {
    echo json_encode(['error' => $db->error]);
}
?>
