<?php
require_once 'include/vt.php';
$db = mysqli_connect($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
mysqli_set_charset($db, "utf8");

$code = '023411';
echo "Checking Product: $code\n";

// 1. Check in kampanya_ozel_fiyatlar
$stmt = $db->prepare("SELECT stok_kodu, kategori FROM kampanya_ozel_fiyatlar WHERE stok_kodu LIKE ?");
$likeCode = "%$code%";
$stmt->bind_param("s", $likeCode);
$stmt->execute();
$res = $stmt->get_result();
if($row = $res->fetch_assoc()){
    echo "kampanya_ozel_fiyatlar -> Category: '" . $row['kategori'] . "'\n";
    echo "Hex: " . bin2hex($row['kategori']) . "\n";
} else {
    echo "Not found in kampanya_ozel_fiyatlar.\n";
}

// 2. Check custom_campaigns for FİLTRE MEDYA
$res2 = mysqli_query($db, "SELECT category_name FROM custom_campaigns WHERE category_name LIKE '%FİLTRE%' OR category_name LIKE '%FILTRE%'");
while($row2 = mysqli_fetch_assoc($res2)){
    echo "custom_campaigns -> Category: '" . $row2['category_name'] . "'\n";
    echo "Hex: " . bin2hex($row2['category_name']) . "\n";
}
?>
