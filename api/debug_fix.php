<?php
// api/debug_fix.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Buffer temizle
if (ob_get_level()) ob_end_clean();
ob_implicit_flush(true);
header('Content-Type: text/html; charset=utf-8');

// Manuel Bağlantı
$db = new mysqli('localhost', 'root', '', 'b2bgemascom_teklif');
if ($db->connect_error) {
    die("Bağlantı Hatası: " . $db->connect_error);
}
$db->set_charset("utf8");

$testCari = "120.03.C33";
echo "<h1>Debug Raporu: $testCari</h1>";

// 1. Şirketi Kod ile Bul
echo "<h3>1. Şirket Sorgusu (Kod: $testCari)</h3>";
$query = "SELECT * FROM sirket WHERE s_arp_code = '$testCari' LIMIT 1";
$res = $db->query($query);

if ($res && $res->num_rows > 0) {
    $company = $res->fetch_assoc();
    echo "<div style='border:2px solid green; padding:10px'>";
    echo "<b>Şirket Bulundu!</b><br>";
    echo "Sirket ID: " . $company['sirket_id'] . "<br>";
    echo "Unvan: " . $company['s_adi'] . "<br>";
    echo "Kod: " . $company['s_arp_code'] . "<br>";
    echo "</div>";
    
    $sirket_id = $company['sirket_id'];
    
    // 2. Çalışmaları Sorgula
    echo "<h3>2. Özel Fiyat Çalışmaları (Sirket ID: $sirket_id)</h3>";
    $query2 = "SELECT * FROM ozel_fiyat_calismalari WHERE sirket_id = $sirket_id ORDER BY id DESC";
    $res2 = $db->query($query2);
    
    if ($res2 && $res2->num_rows > 0) {
        while ($w = $res2->fetch_assoc()) {
            echo "<div style='border:1px solid blue; margin:5px; padding:5px'>";
            echo "Work ID: " . $w['id'] . " - " . $w['baslik'];
            echo " (Aktif: " . $w['aktif'] . ", Silindi: " . $w['silindi'] . ")";
            echo "</div>";
        }
    } else {
        echo "Çalışma Bulunamadı.<br>";
    }
} else {
    echo "<div style='color:red'>Şirket Bulunamadı (Kod ile)!</div>";
}

// 3. Şirketi ID 786 ile Bul (Test)
echo "<h3>3. Şirket Sorgusu (ID: 786)</h3>";
$res3 = $db->query("SELECT * FROM sirket WHERE sirket_id = '786' LIMIT 1");
if ($res3 && $res3->num_rows > 0) {
    $c3 = $res3->fetch_assoc();
    echo "ID 786 -> " . $c3['s_arp_code'] . " (" . $c3['s_adi'] . ")<br>";
} else {
    echo "ID 786 ile Şirket BULUNAMADI!<br>";
}
?>
