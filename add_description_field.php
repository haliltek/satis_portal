<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli('localhost', 'root', '', 'b2bgemascom_teklif');
$db->set_charset('utf8');

// Check if column exists
$result = $db->query("SHOW COLUMNS FROM ogteklifurun2 LIKE 'aciklama'");
if ($result->num_rows == 0) {
    echo "Column 'aciklama' does not exist. Adding it...\n";
    $db->query("ALTER TABLE ogteklifurun2 ADD COLUMN aciklama TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL AFTER adi");
    echo "Column added successfully.\n";
} else {
    echo "Column 'aciklama' already exists.\n";
}
?>
