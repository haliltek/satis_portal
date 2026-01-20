<?php
// fix_db_encoding.php (Advanced Version)
include "fonk.php";

echo "<h1>Database Encoding Fixer (Advanced)</h1>";

// Explicitly set connection to allow utf8mb4 interactions
mysqli_set_charset($db, "utf8mb4");
$db->query("SET SESSION sql_mode = ''"); // Disable strict mode to avoid default value validation errors

$table = 'yonetici';

echo "<h3>Target Table: $table</h3>";

// 1. Temporary Modification: Convert ENUM to TEXT and Drop Default
// This prevents "Invalid default value" errors during the charset conversion
echo "Step 1: Relaxing column constraints (ENUM -> TEXT)...<br>";
$lax1 = $db->query("ALTER TABLE $table ALTER COLUMN satis_tipi DROP DEFAULT");
$lax2 = $db->query("ALTER TABLE $table MODIFY satis_tipi TEXT");

if (!$lax2) {
    echo "<h3 style='color:red'>Step 1 Failed: " . $db->error . "</h3>";
    // Proceed anyway, might fail on Step 2 but worth a try
}

// 2. Convert Table
echo "Step 2: Converting table to UTF-8...<br>";
$sql = "ALTER TABLE $table CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($db->query($sql)) {
    echo "<h2 style='color:green'>Step 2: Conversion Successful!</h2>";
} else {
    echo "<h2 style='color:red'>Step 2: Conversion Failed: " . $db->error . "</h2>";
    exit;
}

// 3. Restore Column Definition
echo "Step 3: Restoring constraints...<br>";
$restore = "ALTER TABLE $table MODIFY satis_tipi ENUM('Yurt İçi','Yurt Dışı') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Yurt İçi'";
if ($db->query($restore)) {
    echo "<h3 style='color:green'>Step 3: Restoration Successful!</h3>";
} else {
    echo "<h3 style='color:red'>Step 3: Restoration Failed: " . $db->error . "</h3>";
    echo "Warning: 'satis_tipi' column is currently TEXT. Please check data.";
}

// 4. Check Status
$check2 = $db->query("SHOW CREATE TABLE $table");
$row2 = $check2->fetch_assoc();
echo "<pre>Final Structure:\n" . htmlspecialchars($row2['Create Table']) . "</pre>";

echo "<hr>";
echo "<p>Now try logging in again via <a href='index.php'>index.php</a></p>";
?>
