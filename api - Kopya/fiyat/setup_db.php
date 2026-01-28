<?php
// api/fiyat/setup_db.php
// api/fiyat/setup_db.php
// Robust include for CLI vs Web
if (file_exists("../../include/vt.php")) {
    include "../../include/vt.php";
} elseif (file_exists(__DIR__ . "/../../include/vt.php")) {
    include __DIR__ . "/../../include/vt.php"; 
} else {
    // CLI fallback from project root
    include "include/vt.php";
}

$db = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Create table if not exists (Updated with last_message_id)
$sql = "CREATE TABLE IF NOT EXISTS fiyat_sessions (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    phone_number VARCHAR(50) NOT NULL UNIQUE,
    is_active TINYINT(1) DEFAULT 1,
    last_message_id VARCHAR(255) NULL,
    started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    INDEX (phone_number),
    INDEX (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

if ($db->query($sql) === TRUE) {
    echo "Table 'fiyat_sessions' checked/created.\n";
    
    // Attempt to add column if it doesn't exist (for existing tables)
    // We suppress errors or check explicitly. Simple way: Try ADD COLUMN, if fail ignore.
    $alterSql = "ALTER TABLE fiyat_sessions ADD COLUMN last_message_id VARCHAR(255) NULL AFTER is_active";
    // Using try-catch or silent execution because checking column existence in pure SQL without procedure is verbose in PHP
    try {
        if ($db->query($alterSql)) {
            echo "Column 'last_message_id' added.\n";
        } else {
            // Likely already exists or error
             // echo "Column addition skipped (maybe exists): " . $db->error . "\n";
        }
    } catch (Exception $e) {
        // Ignore
    }
} else {
    echo "Error creating table: " . $db->error . "\n";
}

$db->close();
?>
