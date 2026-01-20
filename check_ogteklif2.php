<?php
require 'fonk.php';
$res = $db->query('DESCRIBE ogteklif2');
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . PHP_EOL;
}
?>
