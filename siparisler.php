<?php include "fonk.php";
oturumkontrol();
$departmansor = $yoneticisorgula["bolum"];
$departmansor = mysqli_query($db, "SELECT * FROM  departmanlar where departman='$departmansor' ");
$departmanim = mysqli_fetch_array($departmansor);
$departman = $departmanim["departman"];
if ($departman == 'E-Ticaret Departmanı' or $departman == 'Yazılım Departmanı') {
  echo '<meta http-equiv="refresh" content="0; url=sip-dep1.php">';
} else if ($departman == 'Depo Departmanı' or $departman == 'Lojistik Departmanı') {
  echo '<meta http-equiv="refresh" content="0; url=sip-dep1.php">';
} else {
  echo '<meta http-equiv="refresh" content="0; url=sip-dep1.php">';
}
