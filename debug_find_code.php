<?php
require_once "include/fonksiyon.php";
local_database();

$res = mysqli_query($db, "SELECT s_arp_code, s_adi FROM sirket WHERE s_arp_code LIKE '120.%' LIMIT 1");
if ($res && $row = mysqli_fetch_assoc($res)) {
    echo "Found Code: " . $row['s_arp_code'] . " (" . $row['s_adi'] . ")\n";
} else {
    echo "No 120.% code found.\n";
}
