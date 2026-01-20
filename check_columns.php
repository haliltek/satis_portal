<?php
require_once "fonk.php";
$result = $db->query("SHOW COLUMNS FROM ogteklifurun2");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
