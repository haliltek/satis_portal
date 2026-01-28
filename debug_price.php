<?php
include "fonk.php";
$code = "0111STRN-100M";
$stmt = $db->prepare("SELECT stokkodu, maliyet, fiyat, export_fiyat, doviz FROM urunler WHERE stokkodu = ?");
$stmt->bind_param("s", $code);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
print_r($row);
?>
