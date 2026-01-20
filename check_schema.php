<?php
include "fonk.php";
local_database();
global $db;
$res = $db->query("DESCRIBE sirket");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>
