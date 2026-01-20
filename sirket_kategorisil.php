<?php
include "fonk.php";
oturumkontrol();
global $db;
$id = $_GET["id"];

$delete = mysqli_query($db, "delete from sirket_kategori where id='$id'");

if ($delete) {
    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Şirket Kategori Silme','$yonetici_id_sabit','$zaman','Başarılı')";
    $logislem = mysqli_query($db, $logbaglanti);
    header('Location: sirket_kategori.php');
} else {
    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Şirket Kategori Silme','$yonetici_id_sabit','$zaman','Başarısız')";
    $logislem = mysqli_query($db, $logbaglanti);
    header('Location: sirket_kategori.php');
}
