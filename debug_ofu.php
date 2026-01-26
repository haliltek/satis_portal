<?php
include "fonk.php";
$result = $db->query("DESCRIBE ozel_fiyat_urunler");
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
