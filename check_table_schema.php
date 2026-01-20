<?php
include_once "fonk.php";

if (!isset($db) || !$db) {
    die("Database connection failed from fonk.php");
}

$result = mysqli_query($db, "DESCRIBE ogteklifurun2");
if ($result) {
    while ($row = mysqli_fetch_array($result)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
} else {
    echo "Error: " . mysqli_error($db);
}
?>
