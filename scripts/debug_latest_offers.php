<?php
include __DIR__ . '/../include/fonksiyon.php';
local_database();
global $db;

// Fetch last 5 proposals
$sql = "SELECT id, teklifkodu, durum, hazirlayanid, tekliftarihi FROM ogteklif2 ORDER BY id DESC LIMIT 5";
$res = $db->query($sql);

echo "Last 5 Proposals:\n";
echo "----------------------------------------------------------------\n";
echo sprintf("%-6s | %-15s | %-30s | %-5s\n", "ID", "Code", "Status", "User");
echo "----------------------------------------------------------------\n";

while ($row = $res->fetch_assoc()) {
    echo sprintf("%-6s | %-15s | %-30s | %-5s\n", 
        $row['id'], 
        $row['teklifkodu'], 
        $row['durum'], 
        $row['hazirlayanid']
    );
}

// Also check the hex/binary of the status for the first one to rule out hidden chars
if ($res->num_rows > 0) {
    $res->data_seek(0);
    $first = $res->fetch_assoc();
    echo "\nHex dump of status '" . $first['durum'] . "':\n";
    echo bin2hex($first['durum']) . "\n";
}
?>
