<?php
include 'fonk.php';
$db = local_database();
if (!$db) {
    die("Connection failed: " . mysqli_connect_error());
}
$res = $db->query("SHOW TABLES LIKE '%oner%'");
if ($res) {
    while($row = $res->fetch_row()) {
        echo $row[0] . "\n";
    }
} else {
    echo "SQL Error: " . $db->error;
}
?>
