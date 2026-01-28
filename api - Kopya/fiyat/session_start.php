<?php
// api/fiyat/session_start.php
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

// Output JSON response if not already set (for errors handled above)
// header("Content-Type: application/json"); // Already set at top

// Parse Input
$phone = '';
$fromMe = false;

// 1. Check JSON Input
$jsonData = json_decode(file_get_contents('php://input'), true);
if (isset($jsonData['phone'])) $phone = $jsonData['phone'];
if (isset($jsonData['fromMe'])) $fromMe = filter_var($jsonData['fromMe'], FILTER_VALIDATE_BOOLEAN);

// 2. Check POST (Form Data)
if (empty($phone) && isset($_POST['phone'])) $phone = $_POST['phone'];
if (isset($_POST['fromMe'])) $fromMe = filter_var($_POST['fromMe'], FILTER_VALIDATE_BOOLEAN);

// 3. Check GET (Query Param)
if (empty($phone) && isset($_GET['phone'])) $phone = $_GET['phone'];
if (isset($_GET['fromMe'])) $fromMe = filter_var($_GET['fromMe'], FILTER_VALIDATE_BOOLEAN);

// Bot Message Loop Prevention
if ($fromMe === true) {
    http_response_code(400); // Or 200 with error? 400 is fine implies "Bad Request" (Bot shouldn't ask)
    echo json_encode(["status" => "error", "message" => "Message is fromMe (Bot loop protection)"]);
    exit;
}



if (empty($phone)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Phone number required"]);
    exit;
}

// Set expiration to 30 seconds from now
$expires_at = date('Y-m-d H:i:s', strtotime('+30 seconds'));

// Upsert session
$stmt = $db->prepare("INSERT INTO fiyat_sessions (phone_number, is_active, expires_at) VALUES (?, 1, ?) ON DUPLICATE KEY UPDATE is_active = 1, expires_at = ?, started_at = CURRENT_TIMESTAMP");
$stmt->bind_param("sss", $phone, $expires_at, $expires_at);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Session started",
        "expires_at" => $expires_at,
        "phone" => $phone
    ]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to start session: " . $stmt->error]);
}

$stmt->close();
$db->close();
?>
