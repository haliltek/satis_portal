<?php
include "fonk.php";
oturumkontrol();
global $db;
$id = $_GET["id"];
$gr = $_GET["gr"];
$kt = $_GET["kt"];
$delete = mysqli_query($db, "delete from altkategoriler where altkategori_id='$id'");
if ($delete) {
   $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Alt Kategori Silme İşlemi','$yonetici_id_sabit','$zaman','Başarılı')";
   $logislem = mysqli_query($db, $logbaglanti);
   header('Location: kategori_altkategoriler.php?id=' . $gr . '&kat=' . $kt);
} else {
   $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Alt Kategori Silme İşlemi','$yonetici_id_sabit','$zaman','Başarısız')";
   $logislem = mysqli_query($db, $logbaglanti);
   header('Location: kategori_altkategoriler.php?id=' . $gr . '&kat=' . $kt);
}
