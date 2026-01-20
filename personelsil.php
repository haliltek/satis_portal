<?php
include "fonk.php";
oturumkontrol();
global $db;
$id = $_GET["id"];
$delete = mysqli_query($db, "delete from yonetici where yonetici_id='$id'");

if ($delete) {
    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Kayıtlı Personel Silme','$yonetici_id_sabit','$zaman','Başarılı')";
    $logislem = mysqli_query($db, $logbaglanti);
    header('Location: personeller.php');
} else {
    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Kayıtlı Personel Silme','$yonetici_id_sabit','$zaman','Başarısız')";
    $logislem = mysqli_query($db, $logbaglanti);
    header('Location: personeller.php');
}
