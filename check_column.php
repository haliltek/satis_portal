<?php
include 'fonk.php'; // DB connection
$colCheck = $db->query("SHOW COLUMNS FROM ayarlar LIKE 'whatsapp_approval_phone'");
if ($colCheck->num_rows > 0) {
    echo "Column exists.";
} else {
    echo "Column missing.";
}
?>
