<?php
// check_session_status.php
header("Content-Type: text/plain");

// Robust include logic
if (file_exists("include/vt.php")) {
    include "include/vt.php";
} elseif (file_exists(__DIR__ . "/include/vt.php")) {
    include __DIR__ . "/include/vt.php"; 
} else {
    // Fallback if runs from root or elsewhere
    if (file_exists("c:/xampp/htdocs/b2b-gemas-project-main/include/vt.php")) {
         include "c:/xampp/htdocs/b2b-gemas-project-main/include/vt.php";
    } else {
        die("vt.php not found");
    }
}

$db = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "Current Time (Full): " . date('Y-m-d H:i:s') . "\n";
echo "--- Active or Recent Sessions ---\n";

$sql = "SELECT id, phone_number, is_active, last_message_id, started_at, expires_at, (expires_at > NOW()) as is_not_expired FROM fiyat_sessions ORDER BY started_at DESC LIMIT 10";
$result = $db->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $status = ($row['is_active'] && $row['is_not_expired']) ? "ACTIVE" : "INACTIVE/EXPIRED";
        echo "Phone: " . $row['phone_number'] . " | Status: " . $status . " | Started: " . $row['started_at'] . " | Expires: " . $row['expires_at'] . " | MsgID: " . $row['last_message_id'] . "\n";
    }
} else {
    echo "No sessions found.\n";
}

$db->close();
?>
