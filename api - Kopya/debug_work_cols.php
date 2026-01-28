<?php
// api/debug_work_cols.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
if (ob_get_level()) ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

$db = new mysqli('localhost', 'root', '', 'b2bgemascom_teklif');
if ($db->connect_error) {
    die(json_encode(['error' => $db->connect_error]));
}
$db->set_charset("utf8");

$res = $db->query("SHOW COLUMNS FROM ozel_fiyat_calismalari");
$columns = [];
if ($res) {
    while($row = $res->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    echo json_encode(['columns' => $columns]);
} else {
    echo json_encode(['error' => $db->error]);
}
?>
