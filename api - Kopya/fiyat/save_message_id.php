<?php
// api/fiyat/save_message_id.php
header("Content-Type: application/json; charset=utf-8");
// Robust include for CLI vs Web
if (file_exists("../../include/vt.php")) {
    include "../../include/vt.php";
} elseif (file_exists(__DIR__ . "/../../include/vt.php")) {
    include __DIR__ . "/../../include/vt.php"; 
} else {
    include "include/vt.php";
}

$db = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
if ($db->connect_error) {
    http_response_code(500);
    die(json_encode(["status" => "error", "message" => "Database connection failed"]));
}

// Get Params
$phone = $_POST['phone'] ?? $data['phone'] ?? '';
$message_id = $_POST['message_id'] ?? $data['message_id'] ?? '';

// Support JSON input as well
if (empty($phone) || empty($message_id)) {
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, TRUE);
    $phone = $input['phone'] ?? $phone;
    $message_id = $input['message_id'] ?? $message_id;
}

if (empty($phone) || empty($message_id)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Phone and Message ID required"]);
    exit;
}

// Set expiration (5 mins default, refreshed on new prompt)
$expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));

// Insert or Update user session with the last message ID sent by BOT
// We don't care about is_active as much here, but we can set it.
$stmt = $db->prepare("INSERT INTO fiyat_sessions (phone_number, last_message_id, is_active, expires_at) VALUES (?, ?, 1, ?) ON DUPLICATE KEY UPDATE last_message_id = ?, is_active = 1, expires_at = ?, started_at = CURRENT_TIMESTAMP");
$stmt->bind_param("sssss", $phone, $message_id, $expires_at, $message_id, $expires_at);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Message ID saved",
        "message_id" => $message_id
    ]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "DB Error: " . $stmt->error]);
}

$stmt->close();
$db->close();
?>
