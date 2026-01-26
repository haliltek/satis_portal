<?php
require_once 'include/vt.php';
$db = mysqli_connect($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
$res = mysqli_query($db, "DESCRIBE custom_campaigns");
while($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
