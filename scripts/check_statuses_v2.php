<?php
include __DIR__ . '/../include/fonksiyon.php';
local_database();
global $db;

$result = $db->query("SELECT * FROM siparissureci");
if ($result) {
    echo "Current Statuses:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- " . $row['surec'] . "\n";
    }
} else {
    echo "Error: " . $db->error;
}
?>
