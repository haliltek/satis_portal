<?php
include "fonk.php";
include "include/vt.php";

$code = '120.01.E04';
$stmt = $db->prepare("SELECT sirket_id, s_arp_code, acikhesap FROM sirket WHERE s_arp_code = ?");
$stmt->bind_param("s", $code);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo "ID:" . $row['sirket_id'] . "\n";
} else {
    echo "Not Found";
}
?>
