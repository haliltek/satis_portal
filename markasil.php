<?php
include "fonk.php";
oturumkontrol();
global $db;
$id = $_GET["id"];
$ad = $_GET["ad"];
$delete = mysqli_query($db, "delete from markalar where marka_id='$id'");
unlink("images/markalar/" . $ad);

if ($delete) {
   $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Marka Silme','$yonetici_id_sabit','$zaman','Başarılı')";
   $logislem = mysqli_query($db, $logbaglanti);
   header('Location: markalar.php');
} else {
   $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Marka Silme','$yonetici_id_sabit','$zaman','Başarısız')";
   $logislem = mysqli_query($db, $logbaglanti);
   header('Location: markalar.php');
}
