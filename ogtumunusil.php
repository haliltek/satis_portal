<?php
include "fonk.php";
oturumkontrol();
global $db;
$delete = mysqli_query($db, "delete from og");

if ($delete) {
    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('OG Toplu Silme','$yonetici_id_sabit','$zaman','Başarılı')";
    $logislem = mysqli_query($db, $logbaglanti);
    header('Location: ogexceliceriaktar.php');
} else {
    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('OG Toplu Silme','$yonetici_id_sabit','$zaman','Başarısız')";
    $logislem = mysqli_query($db, $logbaglanti);
    header('Location: ogexceliceriaktar.php');
}
