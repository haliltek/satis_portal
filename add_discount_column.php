<?php
include_once "fonk.php";

if (!isset($db) || !$db) {
    die("Database connection failed from fonk.php");
}

// Check if column exists first
$check = mysqli_query($db, "SHOW COLUMNS FROM ogteklifurun2 LIKE 'iskonto_formulu'");
if (mysqli_num_rows($check) == 0) {
    $sql = "ALTER TABLE ogteklifurun2 ADD COLUMN iskonto_formulu VARCHAR(255) DEFAULT NULL COMMENT 'Raw discount formula like 50+10'";
    if (mysqli_query($db, $sql)) {
        echo "Column 'iskonto_formulu' added successfully.";
    } else {
        echo "Error adding column: " . mysqli_error($db);
    }
} else {
    echo "Column 'iskonto_formulu' already exists.";
}
?>
