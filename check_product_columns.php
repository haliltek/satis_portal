<?php
include __DIR__ . "/include/vt.php";
$db = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
$res = $db->query("SHOW COLUMNS FROM urunler");
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
$db->close();
?>
