<?php
// scripts/apply_migration_v2.php

$db_host = "localhost";
$db_user = "root";
$db_password = "";
$db_name = "b2bgemascom_teklif";
$db_port = 3306;

echo "Connecting to database...\n";
$db = new mysqli($db_host, $db_user, $db_password, $db_name, $db_port);

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error . "\n");
}
echo "Connected successfully.\n";

$sqlFile = __DIR__ . '/../sql/20251209_add_special_offer_columns.sql';
if (!file_exists($sqlFile)) {
    die("SQL file not found: $sqlFile\n");
}

$sqlContent = file_get_contents($sqlFile);
// Remove comments
$sqlContent = preg_replace('/--.*$/m', '', $sqlContent);
// Split by semicolon
$queries = explode(';', $sqlContent);

foreach ($queries as $query) {
    $query = trim($query);
    if (empty($query)) continue;

    echo "Executing query: " . substr($query, 0, 50) . "...\n";
    if ($db->query($query) === TRUE) {
        echo "Query executed successfully.\n";
    } else {
        // Ignore "Duplicate column name" errors
        if ($db->errno == 1060) {
            echo "Column already exists (skipped).\n";
        } elseif ($db->errno == 1061) {
            echo "Index already exists (skipped).\n";
        } else {
            echo "Error executing query: " . $db->error . "\n";
        }
    }
}

$db->close();
echo "Migration completed.\n";
?>
