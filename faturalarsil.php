<?php
include "fonk.php";
oturumkontrol();
global $db;
$id = $_GET["id"];
$sid = $_GET["sid"];
$delete = mysqli_query($db, "delete from faturairsaliye where id='$id'");

if ($delete) {
	$logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Fatura Silme','$yonetici_id_sabit','$zaman','Başarılı')";
	$logislem = mysqli_query($db, $logbaglanti);
	header('Location: faturalar.php?id=' . $sid);
} else {
	$logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Fatura Silme','$yonetici_id_sabit','$zaman','Başarısız')";
	$logislem = mysqli_query($db, $logbaglanti);
	header('Location: faturalar.php?id=' . $sid);
}
