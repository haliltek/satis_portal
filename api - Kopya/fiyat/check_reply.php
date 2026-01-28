<?php
// api/fiyat/check_reply.php
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

$phone = '';
$replied_id = '';
$fromMe = false;

// 1. JSON Input
$jsonData = json_decode(file_get_contents('php://input'), true);
if (isset($jsonData['phone'])) $phone = $jsonData['phone'];
if (isset($jsonData['replied_message_id'])) $replied_id = $jsonData['replied_message_id'];
if (isset($jsonData['fromMe'])) $fromMe = filter_var($jsonData['fromMe'], FILTER_VALIDATE_BOOLEAN);

// 2. GET Input
if (empty($phone) && isset($_GET['phone'])) $phone = $_GET['phone'];
if (empty($replied_id) && isset($_GET['replied_message_id'])) $replied_id = $_GET['replied_message_id'];
if (isset($_GET['fromMe'])) $fromMe = filter_var($_GET['fromMe'], FILTER_VALIDATE_BOOLEAN);

// Bot Message Loop Prevention
if ($fromMe === true) {
    echo json_encode(["is_reply" => false, "reason" => "Message is fromMe (Bot loop protection)"]);
    exit;
}

if (empty($phone) || empty($replied_id)) {
    echo json_encode(["is_reply" => false, "reason" => "No replied_message_id provided"]);
    exit;
}

// Check if this phone has a session where last_message_id matches replied_id AND is not expired
$stmt = $db->prepare("SELECT id, last_message_id FROM fiyat_sessions WHERE phone_number = ? AND last_message_id = ? AND expires_at > NOW() LIMIT 1");
$stmt->bind_param("ss", $phone, $replied_id);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    echo json_encode([
        "is_reply" => true,
        "original_message_id" => $row['last_message_id']
    ]);
} else {
    echo json_encode([
        "is_reply" => false,
        "reason" => "Session not found, expired, or ID mismatch"
    ]);
}

$stmt->close();
$db->close();
?>
