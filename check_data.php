<?php
include 'fonk.php';
$res = mysqli_query($db, "SELECT hazirlayanid, COUNT(*) as c FROM ogteklif2 GROUP BY hazirlayanid");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
$res = mysqli_query($db, "SELECT yonetici_id, adsoyad FROM yonetici LIMIT 5");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>
