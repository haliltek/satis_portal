<?php
// api/fiyat/check_session.php
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

// Parse Input
$phone = '';
$fromMe = false;

// 1. Check JSON Input
$jsonData = json_decode(file_get_contents('php://input'), true);
if (isset($jsonData['phone'])) $phone = $jsonData['phone'];
if (isset($jsonData['fromMe'])) $fromMe = filter_var($jsonData['fromMe'], FILTER_VALIDATE_BOOLEAN);

// 2. Check GET/POST
if (empty($phone) && isset($_GET['phone'])) $phone = $_GET['phone'];
if (empty($phone) && isset($_POST['phone'])) $phone = $_POST['phone'];
if (isset($_GET['fromMe'])) $fromMe = filter_var($_GET['fromMe'], FILTER_VALIDATE_BOOLEAN);

// Bot Message Loop Prevention
if ($fromMe === true) {
    echo json_encode(["active" => false, "reason" => "Message is fromMe (Bot loop protection)"]);
    exit;
}

if (empty($phone)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Phone number required"]);
    exit;
}

// Check if there is an active session that hasn't expired
// We accept any phone format, assuming n8n sends consistent format (e.g. 905...)
$stmt = $db->prepare("SELECT id FROM fiyat_sessions WHERE phone_number = ? AND is_active = 1 AND expires_at > NOW() LIMIT 1");
$stmt->bind_param("s", $phone);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    echo json_encode(["active" => true]);
} else {
    echo json_encode(["active" => false]);
}

$stmt->close();
$db->close();
?>
