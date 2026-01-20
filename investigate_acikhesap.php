<?php
include "fonk.php";
include "include/vt.php";

$output = "";

$output .= "--- Table Schema for 'sirket' ---\n";
// ... (schema part is fine, skipping to save tokens if I can, but replace tool needs context)
// Re-writing the file is cheaper than complex replace sometimes.

$output .= "\n--- Sample Non-Zero Data ---\n";
$result = $db->query("SELECT sirket_id, s_arp_code, acikhesap FROM sirket WHERE acikhesap != '0' AND acikhesap != '' AND acikhesap IS NOT NULL LIMIT 5");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $output .= "ID: " . $row['sirket_id'] . " | Code: " . $row['s_arp_code'] . " | Raw acikhesap: " . var_export($row['acikhesap'], true) . "\n";
        
        // Simulate the logic in get_acikhesap.php
        $acikhesap = $row['acikhesap'];
        $normalized = str_replace([','], '', $acikhesap);
        $numeric = floatval($normalized);
        $output .= "   -> Logic Result: " . $numeric . "\n";
    }
} else {
    $output .= "No non-zero records found.\n";
}

file_put_contents('investigation_result.txt', $output);
echo "Done.";
?>
