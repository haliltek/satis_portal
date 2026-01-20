<?php
require 'c:/xampp/htdocs/b2b-gemas-project-main/include/fonksiyon.php';
local_database();
global $db;
$res = $db->query("SELECT count(1) as c FROM ogteklif2 WHERE is_special_offer=1");
if ($res) {
    $row = $res->fetch_assoc();
    echo "Found " . $row['c'] . " special offers.\n";
    
    $res2 = $db->query("SELECT count(1) as c FROM ogteklif2 WHERE is_special_offer=1 AND durum!='Yönetici Onayı Bekleniyor'");
    $row2 = $res2->fetch_assoc();
    echo "Of which " . $row2['c'] . " have wrong status.\n";
} else {
    echo "Query failed.\n";
}
