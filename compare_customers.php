<?php
include "fonk.php";
include "include/vt.php";

$codes = ['120.01.E04', '120.01.E350'];

foreach ($codes as $code) {
    echo "--- CHECKING $code ---\n";
    $stmt = $db->prepare("SELECT sirket_id, s_arp_code, trading_grp, acikhesap FROM sirket WHERE s_arp_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        print_r($row);
    } else {
        echo "Not Found\n";
    }
}
?>
