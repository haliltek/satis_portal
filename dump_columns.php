<?php
include "fonk.php";
local_database();
$res = $db->query("SHOW COLUMNS FROM urunler");
$cols = [];
while($row = $res->fetch_assoc()){
    $cols[] = $row['Field'];
}
echo implode(',', $cols);
?>
