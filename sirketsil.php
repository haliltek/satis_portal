<?php
include "fonk.php";
oturumkontrol();
global $db;
$id = $_GET["id"];

$delete = mysqli_query($db, "delete from sirket where sirket_id='$id'");

if ($delete) {
    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Şirket Silme','$yonetici_id_sabit','$zaman','Başarılı')";
    $logislem = mysqli_query($db, $logbaglanti);
    header('Location: tumsirketler.php');
} else {
    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Şirket Silme','$yonetici_id_sabit','$zaman','Başarısız')";
    $logislem = mysqli_query($db, $logbaglanti);
    header('Location: tumsirketler.php');
}
