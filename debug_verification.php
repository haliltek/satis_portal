<?php
include __DIR__ . "/include/vt.php";
$db = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
$db->set_charset("utf8");

$teklif_id = 89;

echo "Checking Teklif ID: $teklif_id\n";
$stmt = $db->prepare("SELECT id, n8n_instance_id, approval_status FROM ogteklif2 WHERE id = ?");
$stmt->bind_param("i", $teklif_id);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    echo "[FOUND] Teklif ID $teklif_id exists.\n";
    echo "Saved Message ID (n8n_instance_id): " . ($row['n8n_instance_id'] ? $row['n8n_instance_id'] : "NULL") . "\n";
    echo "Approval Status: " . $row['approval_status'] . "\n";
    
    if ($row['n8n_instance_id']) {
        echo "Checking decisions for Message ID: " . $row['n8n_instance_id'] . "\n";
        $stmt2 = $db->prepare("SELECT * FROM teklif_decisions WHERE message_id = ?");
        $stmt2->bind_param("s", $row['n8n_instance_id']);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        if ($res2->num_rows > 0) {
            echo "[DECISION LOGGED]:\n";
            while ($dec = $res2->fetch_assoc()) {
                echo " - Type: " . $dec['decision_type'] . ", Status: " . $dec['decision_status'] . ", Date: " . $dec['decision_date'] . "\n";
            }
        } else {
            echo "[NO DECISION FOUND] in teklif_decisions table.\n";
        }
        $stmt2->close();
    }
} else {
    echo "[NOT FOUND] Teklif ID $teklif_id does NOT exist.\n";
}

$stmt->close();
$db->close();
?>
