<?php
include "include/vt.php";
$db = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
if ($db->connect_error) die("DB Error");

// Check if column exists
$check = $db->query("SHOW COLUMNS FROM ogteklif2 LIKE 'n8n_instance_id'");
if ($check->num_rows == 0) {
    if($db->query("ALTER TABLE ogteklif2 ADD n8n_instance_id VARCHAR(100) NULL, ADD INDEX (n8n_instance_id)")) {
        echo "Column added successfully";
    } else {
        echo "Error adding column: " . $db->error;
    }
} else {
    echo "Column already exists";
}
$db->close();
?>
