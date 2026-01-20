<?php
include("include/fonksiyon.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

gemas_web_database();
local_database();
// Veritabanı bağlantısını kur (yerelde yorum halinde ama çalışıyor)

gempa_logo_veritabani(); // MSSQL bağlantısı localde kapatıldı çünkü sqldriver bozuk.
gemas_logo_veritabani(); // MSSQL bağlantısı localde kapatıldı çünkü sqldriver bozuk.

global $db, $gemas_web_db, $gemas_logo_db, $gempa_logo_db;
$ayarbagla = mysqli_query($db, "select * from genelayarlar");
$ayar = mysqli_fetch_array($ayarbagla);
$genelbagla = mysqli_query($db, "select * from ayarlar");
$sistemayar = mysqli_fetch_array($genelbagla);
$personelid = $_SESSION['yonetici_id'] ?? null;
$userType  = $_SESSION['user_type'] ?? '';
if ($personelid) {
    $res = mysqli_query($db, "SELECT id AS yonetici_id, username AS adsoyad, email AS eposta FROM b2b_users WHERE id='" . mysqli_real_escape_string($db, $personelid) . "'");
    $yoneticisorgula = mysqli_fetch_array($res);
    if ($yoneticisorgula) {
        $adsoyad = $yoneticisorgula['adsoyad'] ?? '';
        $userType = 'Bayi';
    } else {
        $sirketcekereksorgulama = mysqli_query($db, "SELECT * FROM  yonetici WHERE yonetici_id='$personelid'");
        $yoneticisorgula = mysqli_fetch_array($sirketcekereksorgulama) ?: [];
        $adsoyad = $yoneticisorgula["adsoyad"] ?? '';
        $userType = $yoneticisorgula["tur"] ?? $userType;
    }
} else {
    $yoneticisorgula = [];
    $adsoyad = '';
}
date_default_timezone_set('Etc/GMT-3');
$tarih = date("d.m.Y");
$saat =  date("h:i");
$zaman = $tarih . ' Saat: ' . $saat;
$yonetici_id_sabit = $_SESSION['yonetici_id'] ?? null;

use Proje\DatabaseManager;
use Proje\LogoService;

$config = require __DIR__ . '/config/config.php';

$dbManager = new DatabaseManager($config['db']);

$logoService = new LogoService(
    db: $dbManager,
    configArray: $config,
    logErrorFile: __DIR__ . '/error.log',
    logDebugFile: __DIR__ . '/debug.log'
);

?>
