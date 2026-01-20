<?php
include __DIR__ . '/../fonk.php';
$db = local_database();

$result = $db->query("SELECT * FROM siparissureci");
if ($result) {
    echo "Current Statuses in 'siparissureci':\n";
    while ($row = $result->fetch_assoc()) {
        echo "- " . $row['surec'] . "\n";
    }
} else {
    echo "Error: " . $db->error;
}
?>
