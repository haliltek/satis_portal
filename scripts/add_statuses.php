<?php
include __DIR__ . '/../include/fonksiyon.php';
local_database();
global $db;

$statusesToAdd = [
    'Yönetici Onayı Bekleniyor',
    'Yönetici Onayladı / Gönderilecek',
    'Yönetici Tarafından Red'
];

foreach ($statusesToAdd as $status) {
    // Check if exists
    $check = $db->query("SELECT * FROM siparissureci WHERE surec = '$status'");
    if ($check->num_rows == 0) {
        $sql = "INSERT INTO siparissureci (surec) VALUES ('$status')";
        if ($db->query($sql)) {
            echo "Added: $status\n";
        } else {
            echo "Error adding $status: " . $db->error . "\n";
        }
    } else {
        echo "Exists: $status\n";
    }
}
?>
