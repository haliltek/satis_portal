<?php
include __DIR__ . "/include/vt.php";
$db = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
$db->set_charset("utf8");

$msg_id = "3EB0573CAD125802E58DB0";

echo "Checking for Message ID: $msg_id\n";

// 1. Check ogteklif2 (Linkage)
$stmt = $db->prepare("SELECT id, n8n_instance_id FROM ogteklif2 WHERE n8n_instance_id = ?");
$stmt->bind_param("s", $msg_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    echo "[FOUND] in ogteklif2. Teklif ID: " . $row['id'] . "\n";
} else {
    echo "[NOT FOUND] in ogteklif2.\n";
}
$stmt->close();

// 2. Check teklif_decisions (History)
$stmt2 = $db->prepare("SELECT * FROM teklif_decisions WHERE message_id = ?");
$stmt2->bind_param("s", $msg_id);
$stmt2->execute();
$res2 = $stmt2->get_result();
if ($res2->num_rows > 0) {
    echo "[FOUND] in teklif_decisions:\n";
    while ($row = $res2->fetch_assoc()) {
        echo " - Type: " . $row['decision_type'] . " Status: " . $row['decision_status'] . "\n";
    }
} else {
    echo "[NOT FOUND] in teklif_decisions.\n";
}
$stmt2->close();
$db->close();
?>
