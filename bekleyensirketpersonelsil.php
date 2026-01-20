<?php
include "fonk.php";
oturumkontrol();
global $db;
$id = $_GET["id"];
$delete = mysqli_query($db, "delete from personel where personel_id='$id'");

if ($delete) {
    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Şirket Personeli Silme','$yonetici_id_sabit','$zaman','Başarılı')";
    $logislem = mysqli_query($db, $logbaglanti);
    header('Location: beklemedekiuyeler.php');
} else {
    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Şirket Peroneli Silme','$yonetici_id_sabit','$zaman','Başarısız')";
    $logislem = mysqli_query($db, $logbaglanti);
    header('Location: beklemedekiuyeler.php');
}
