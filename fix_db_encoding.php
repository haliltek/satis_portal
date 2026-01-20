<?php
// fix_db_encoding.php
include "fonk.php";

echo "<h1>Database Encoding Fixer</h1>";

// Explicitly set connection to allow utf8mb4 interactions
mysqli_set_charset($db, "utf8mb4");

$table = 'yonetici';

echo "<h3>Target Table: $table</h3>";

// 1. Check current Status
$check = $db->query("SHOW CREATE TABLE $table");
$row = $check->fetch_assoc();
echo "<pre>Before:\n" . htmlspecialchars($row['Create Table']) . "</pre>";

// 2. Convert Table
// We convert to binary first to avoid data loss during transcoding, then to utf8mb4
// Actually, direct conversion often works, but let's do direct to utf8mb4.
$sql = "ALTER TABLE $table CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($db->query($sql)) {
    echo "<h2 style='color:green'>Conversion Successful!</h2>";
} else {
    echo "<h2 style='color:red'>Conversion Failed: " . $db->error . "</h2>";
}

// 3. Check Status Again
$check2 = $db->query("SHOW CREATE TABLE $table");
$row2 = $check2->fetch_assoc();
echo "<pre>After:\n" . htmlspecialchars($row2['Create Table']) . "</pre>";

echo "<hr>";
echo "<p>Now try logging in again via <a href='index.php'>index.php</a></p>";
?>
