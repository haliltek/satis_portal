<?php
$config = require 'config/config.php';
$db_cfg = $config['db'];

mysqli_report(MYSQLI_REPORT_OFF);
$db = new mysqli($db_cfg['host'], $db_cfg['user'], $db_cfg['pass'], $db_cfg['name'], $db_cfg['port']);
if ($db->connect_errno) {
    die("Connect Error: " . $db->connect_error);
}

// Check if column exists
$check = $db->query("SHOW COLUMNS FROM ogteklif2 LIKE 'genel_iskonto'");
if ($check->num_rows == 0) {
    // Add column
    $sql = "ALTER TABLE ogteklif2 ADD COLUMN genel_iskonto DECIMAL(5,2) DEFAULT 0.00 AFTER sozlesme_id"; // Adjust 'AFTER' if needed, or just append
    if ($db->query($sql) === TRUE) {
        echo "Column 'genel_iskonto' added successfully.";
    } else {
        echo "Error adding column: " . $db->error;
    }
} else {
    echo "Column 'genel_iskonto' already exists.";
}

$db->close();
?>
