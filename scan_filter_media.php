<?php
require_once 'include/vt.php';
$db = mysqli_connect($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
mysqli_set_charset($db, "utf8");

echo "Scanning for products in 'FİLTRELER' that might belong in 'FİLTRE MEDYA'...\n";

// Get all products in FİLTRELER
$res = mysqli_query($db, "SELECT stok_kodu, kategori FROM kampanya_ozel_fiyatlar WHERE kategori = 'FİLTRELER'");
$candidates = [];
while($row = mysqli_fetch_assoc($res)){
    // Get product name from urunler for context
    $code = $row['stok_kodu'];
    $r2 = mysqli_query($db, "SELECT stokadi FROM urunler WHERE stokkodu = '$code'");
    $name = "";
    if($prod = mysqli_fetch_assoc($r2)) {
        $name = $prod['stokadi'];
    }
    
    // Check if name suggests Media (Sand, Kum, Cam, Antrasit)
    $nameUpper = mb_strtoupper($name, 'UTF-8');
    if (strpos($nameUpper, 'KUM') !== false || 
        strpos($nameUpper, 'CAM') !== false || 
        strpos($nameUpper, 'ANTRASIT') !== false ||
        strpos($nameUpper, 'ZEOLIT') !== false ||
        strpos($nameUpper, 'MEDYA') !== false) {
            
        echo "Candidate: [$code] $name (Current Cat: {$row['kategori']})\n";
        $candidates[] = $code;
    }
}

if(empty($candidates)){
    echo "No obvious candidates found in FİLTRELER.\n";
} else {
    echo "Found " . count($candidates) . " potential Filter Media products.\n";
}
?>
