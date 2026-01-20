<?php
// api/fiyat/clear_session.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");

// Robust include logic
if (file_exists("../../include/vt.php")) {
    include "../../include/vt.php";
} elseif (file_exists(__DIR__ . "/../../include/vt.php")) {
    include __DIR__ . "/../../include/vt.php"; 
} else {
    // CLI fallback
    include "include/vt.php";
}

// Polyfill getallheaders if not exists
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

// Auth Verification
$headers = getallheaders();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

// Handle case-insensitive header keys
if (empty($authHeader)) {
    foreach ($headers as $key => $value) {
        if (strtolower($key) === 'authorization') {
            $authHeader = $value;
            break;
        }
    }
}

if ($authHeader !== "Bearer gemas_secret_n8n_token_2025") {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized token"]);
    exit;
}

// Get Phone Check
$data = json_decode(file_get_contents("php://input"), true);
$phone = isset($data['phone']) ? $data['phone'] : (isset($_GET['phone']) ? $_GET['phone'] : '');

if (empty($phone)) {
    http_response_code(400); // Bad Request
    echo json_encode(["status" => "error", "message" => "Phone number required"]);
    exit;
}

// Prepare usage of phone
// Remove non-numeric characters just in case, or trust n8n sends raw
// Usually better to match how it's stored. Stored as raw string but often clean.
// check_reply.php uses it directly. We will too.

$db = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
if ($db->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "DB Connection failed"]);
    exit;
}

// Logic: Reset session by expiring it immediately
// We could DELETE or UPDATE expires_at. UPDATE allows keeping history if we ever check logs.
// But check_session.php checks `expires_at > NOW()`. So setting it to NOW() - INTERVAL 1 SECOND works.

$stmt = $db->prepare("UPDATE fiyat_sessions SET expires_at = DATE_SUB(NOW(), INTERVAL 1 MINUTE), is_active = 0 WHERE phone_number = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Prepare failed: " . $db->error]);
    exit;
}

$stmt->bind_param("s", $phone);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(["status" => "success", "message" => "Session cleared", "phone" => $phone]);
    } else {
        // No session found or already cleared, but operation is 'success' in idempotent sense
        echo json_encode(["status" => "success", "message" => "No active session found or already cleared", "phone" => $phone]);
    }
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Execute failed: " . $stmt->error]);
}

$stmt->close();
$db->close();
?>
