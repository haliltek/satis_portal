<?php
include 'fonk.php';

echo "\nAll Tables:\n";
$res = mysqli_query($db, "SHOW TABLES");
while ($row = mysqli_fetch_row($res)) {
    echo "  " . $row[0] . "\n";
}

$tables = ['yonetici', 'personel', 'b2b_users', 'ogteklif2'];
foreach ($tables as $t) {
    echo "\nTable: $t\n";
    $res = mysqli_query($db, "DESCRIBE $t");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            echo "  " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        echo "  Error describing $t: " . mysqli_error($db) . "\n";
    }
}
