<?php
include "fonk.php";
oturumkontrol();
global $db;
$id = $_GET["id"];
$delete = mysqli_query($db, "delete from kategoriler where kategori_id='$id'");
$delete = mysqli_query($db, "delete from altkategoriler where ustkategori_id='$id'");
$delete = mysqli_query($db, "delete from kategorigrup where ustkategori_id='$id'");

if ($delete) {
    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Kategori Silme','$yonetici_id_sabit','$zaman','Başarılı')";
    $logislem = mysqli_query($db, $logbaglanti);
    header('Location: kategoriler.php');
} else {
    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Kategori Silme','$yonetici_id_sabit','$zaman','Başarısız')";
    $logislem = mysqli_query($db, $logbaglanti);
    header('Location: kategoriler.php');
}
