<?php
include "fonk.php";
$result = $db->query("DESCRIBE urunler");
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
