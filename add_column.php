<?php
include 'fonk.php';
$sql = "ALTER TABLE ayarlar ADD COLUMN whatsapp_approval_phone VARCHAR(50) DEFAULT '905525287286'";
if ($db->query($sql) === TRUE) {
    echo "Column added successfully";
} else {
    echo "Error adding column: " . $db->error;
}
?>
