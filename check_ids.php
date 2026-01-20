<?php
include __DIR__ . "/include/vt.php";
$db = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
$db->set_charset("utf8");

echo "Checking for saved n8n_instance_id values...\n";
$res = $db->query("SELECT id, n8n_instance_id FROM ogteklif2 WHERE n8n_instance_id IS NOT NULL AND n8n_instance_id != '' ORDER BY id DESC LIMIT 5");

if ($res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        echo "ID: " . $row['id'] . " - InstanceID: " . $row['n8n_instance_id'] . " - Date: " . $row['tarih'] . "\n";
    }
} else {
    echo "No records found with n8n_instance_id populated.\n";
}
$db->close();
?>
