<?php
$hostname = '192.168.5.253';
$dbname = 'TEST';
$username = 'halil';
$password = '12621262';
$baglanti = new PDO("sqlsrv:Server=$hostname;Database=$dbname", $username, $password);
$baglanti->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
if ($baglanti) {
} else {
  echo "Veritabanına Bağlantılamadı";
  exit;
}
$dosya = "veri.sql";
unlink($dosya);
$dosya = fopen("veri.sql", 'w'); //dosya oluşturma işlemi
$sorgu = $baglanti->query("SELECT * FROM PBS_B2BURUNLIST");
while ($dev2 = $sorgu->fetch(PDO::FETCH_ASSOC)) {
  $stokkodu = $dev2["STOK_KODU"];
  $stokadim = addslashes($dev2["STOK_ADI"]);
  $stokadimm = str_replace("'", "", $stokadim);
  $stokadi = str_replace('"', '', $stokadimm);
  $olcubirimi = $dev2["OLCU_BR1"];
  $fiyati = number_format($dev2["SATIS_FIAT1"], 2, ',', '.');
  $dovtip = $dev2["SAT_DOV_TIP"];
  $grups = $dev2["GRUP_ISIM"];
  $zaman = date("d.m.Y H:i");
  if ($grups == null) {
    $grup = 'Diğer';
  } else {
    $grup = $grups;
  }
  if ($dovtip == '0') {
    $tipi = "TL";
  } else if ($dovtip == '1') {
    $tipi = "USD";
  } else if ($dovtip == '2') {
    $tipi = "EUR";
  }
  $yaz = "INSERT INTO urunler2(marka,fiyat,guncelleme,zaman,stokkodu,stokadi,olcubirimi,doviz) VALUES('$grup','$fiyati','0','0','$stokkodu','$stokadi','$olcubirimi','$tipi')";
  // $yaz = " UPDATE urunler2 SET marka='".$grup."',fiyat='".$fiyati."',guncelleme = '1', zaman = '".$zaman."' where stokkodu='".$stokkodu."';";
  fwrite($dosya, $yaz . "\n");
}
fclose($dosya);
header("Location: bigdatas.php");
