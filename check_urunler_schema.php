<?php
require_once 'include/vt.php';
$res = mysqli_query($db, "DESCRIBE urunler");
while($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . "\n";
}
?>
