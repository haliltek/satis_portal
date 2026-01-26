<?php
include "fonk.php";
$sql = "ALTER TABLE ozel_fiyat_urunler ADD COLUMN notlar TEXT DEFAULT NULL AFTER iskonto_orani";
if ($db->query($sql)) {
    echo "Success: Column 'notlar' added.";
} else {
    echo "Error: " . $db->error;
}
?>
