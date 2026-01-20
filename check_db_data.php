<?php
include_once "fonk.php";

if (!isset($db) || !$db) {
    die("Database connection failed from fonk.php");
}

// Fetch the last 5 added product records
$query = "SELECT id, teklifid, kod, iskonto, iskonto_formulu FROM ogteklifurun2 ORDER BY id DESC LIMIT 5";
$result = mysqli_query($db, $query);

if ($result) {
    echo "Last 5 entries in ogteklifurun2:\n";
    echo sprintf("%-10s %-10s %-20s %-10s %-20s\n", "ID", "TeklifID", "Kod", "Iskonto", "Formula");
    echo str_repeat("-", 70) . "\n";
    while ($row = mysqli_fetch_assoc($result)) {
        echo sprintf("%-10s %-10s %-20s %-10s %-20s\n", 
            $row['id'], 
            $row['teklifid'], 
            substr($row['kod'], 0, 20), 
            $row['iskonto'], 
            $row['iskonto_formulu'] ?? 'NULL'
        );
    }
} else {
    echo "Error: " . mysqli_error($db);
}
?>
