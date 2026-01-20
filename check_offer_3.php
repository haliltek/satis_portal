<?php
include_once "fonk.php";

if (!isset($db) || !$db) {
    die("Database connection failed from fonk.php");
}

$teklifId = 3;
echo "Checking Offer ID: $teklifId\n";

// Fetch items for this offer
$query = "SELECT id, kod, iskonto, iskonto_formulu FROM ogteklifurun2 WHERE teklifid = ? LIMIT 10";
$stmt = $db->prepare($query);
$stmt->bind_param('i', $teklifId);
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    echo sprintf("%-10s %-20s %-10s %-20s\n", "ID", "Kod", "Iskonto", "Formula");
    echo str_repeat("-", 60) . "\n";
    while ($row = $result->fetch_assoc()) {
        echo sprintf("%-10s %-20s %-10s %-20s\n", 
            $row['id'], 
            substr($row['kod'], 0, 20), 
            $row['iskonto'], 
            $row['iskonto_formulu'] ?? 'NULL'
        );
    }
} else {
    echo "Error: " . $db->error;
}
?>
