<?php
// api/debug_v2.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Buffer temizle
if (ob_get_level()) ob_end_clean();
ob_implicit_flush(true);
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Debug V2 Başladı</h1>";

// Manuel Bağlantı
$db = new mysqli('localhost', 'root', '', 'b2bgemascom_teklif');
if ($db->connect_error) {
    die("Bağlantı Hatası: " . $db->connect_error);
}
$db->set_charset("utf8");

$testCari = "120.03.C33";
echo "<h2>Test Cari: $testCari</h2>";

// 1. Şirketi Kod ile Bul
echo "<h3>1. Kod ile Sorgu</h3>";
$res = $db->query("SELECT * FROM sirket WHERE s_arp_code = '$testCari' LIMIT 1");

if ($res && $res->num_rows > 0) {
    $company = $res->fetch_assoc();
    echo "<div style='border:2px solid green; padding:10px'>";
    echo "<b>Şirket Bulundu!</b><br>";
    echo "<h3>GERÇEK ID: " . $company['sirket_id'] . "</h3>";
    echo "Kod: " . $company['s_arp_code'] . "<br>";
    echo "</div>";
    
    $sirket_id = $company['sirket_id'];
    
    // 2. Çalışma Var mı?
    echo "<h3>2. Çalışma Sorgusu (ID: $sirket_id)</h3>";
    $res2 = $db->query("SELECT * FROM ozel_fiyat_calismalari WHERE sirket_id = $sirket_id ORDER BY id DESC");
    
    if ($res2 && $res2->num_rows > 0) {
        $w = $res2->fetch_assoc();
        echo "<b>Çalışma Bulundu:</b> " . $w['baslik'] . " (ID: " . $w['id'] . ")<br>";
    } else {
        echo "Çalışma Yok.<br>";
    }

} else {
    echo "Şirket Bulunamadı!<br>";
}

// 3. ID 786 Kontrolü
echo "<h3>3. ID 786 Kontrolü</h3>";
$res3 = $db->query("SELECT * FROM sirket WHERE sirket_id = 786");
if ($res3 && $res3->num_rows > 0) {
    $c3 = $res3->fetch_assoc();
    echo "ID 786 -> " . $c3['s_arp_code'] . " BULUNDU.<br>";
} else {
    echo "ID 786 -> BULUNAMADI.<br>";
}
?>
