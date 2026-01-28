<?php
include "../../include/vt.php";

// Manuel bağlantı (include/vt.php'deki değişkenleri kullanarak)
$db = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}
$db->set_charset("utf8");

$sql = "CREATE TABLE IF NOT EXISTS teklif_decisions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id VARCHAR(255) UNIQUE,
    teklif_id VARCHAR(100),
    manager_phone VARCHAR(20),
    decision_type ENUM('ONAY', 'RED') NOT NULL,
    decision_status VARCHAR(50) DEFAULT 'PROCESSED',
    decision_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_message_id (message_id),
    INDEX idx_manager_phone (manager_phone),
    INDEX idx_teklif_id (teklif_id)
)";

if ($db->query($sql) === TRUE) {
    echo "Table 'teklif_decisions' created successfully";
} else {
    echo "Error creating table: " . $db->error;
}

$db->close();
?>
