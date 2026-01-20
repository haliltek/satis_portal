<?php
// Mock file to emulate check_decision.php behavior for testing via CLI
$sql_details = [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => '',
    'db'   => 'b2bgemascom_teklif',
];

$db = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
$db->set_charset("utf8");

// Simulate GET parameter
$message_id = "3EB0565FF0504027F11643"; // Previously confirmed ID
// $message_id = "NON_EXISTENT_ID"; 

echo "Testing check_decision.php logic for Message ID: $message_id\n";
echo "--------------------------------------------------------\n";

// Logic copied from check_decision.php
$query = "SELECT decision_status, decision_type, decision_date, manager_phone, teklif_id FROM teklif_decisions WHERE message_id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("s", $message_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if ($result) {
    echo json_encode([
        'decided' => true,
        'decision' => $result['decision_type'],
        'date' => $result['decision_date'],
        'manager_phone' => $result['manager_phone'],
        'teklif_id' => $result['teklif_id']
    ], JSON_PRETTY_PRINT);
} else {
    echo json_encode([
        'decided' => false,
        'decision' => null,
        'date' => null
    ], JSON_PRETTY_PRINT);
}

$stmt->close();
$db->close();
?>
