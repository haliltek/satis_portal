<?php
require_once 'include/vt.php';
$res = mysqli_query($db, "DESCRIBE durum_gecisleri");
while($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . "\n";
}
?>
