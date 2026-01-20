<?php
include "fonk.php";
oturumkontrol();
global $db;
$id = $_GET["id"];
$kat = $_GET["kat"];
$delete = mysqli_query($db, "delete from kategorigrup where id='$id'");
$delete = mysqli_query($db, "delete from altkategoriler where grupid='$id'");

if ($delete) {
   $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Kategori Grubu Silme','$yonetici_id_sabit','$zaman','Başarılı')";
   $logislem = mysqli_query($db, $logbaglanti);
   header('Location: kategori_grup.php?id=' . $kat);
} else {
   $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Kategori Grubu Silme','$yonetici_id_sabit','$zaman','Başarısız')";
   $logislem = mysqli_query($db, $logbaglanti);
   header('Location: kategori_grup.php?id=' . $kat);
}
