<?php
// api/webhook_proxy.php
header("Content-Type: application/json");

// 1. Get Incoming Data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['status' => 'ok', 'message' => 'No data received']);
    exit;
}

// 2. LOGIC: Filter "fromMe" (Bot's own messages)
// Support multiple common Evolution API structures
$isFromMe = false;

// Case A: formatted root
if (isset($data['fromMe']) && $data['fromMe'] === true) $isFromMe = true;
// Case B: data.key.fromMe
if (isset($data['data']['key']['fromMe']) && $data['data']['key']['fromMe'] === true) $isFromMe = true;
// Case C: key.fromMe
if (isset($data['key']['fromMe']) && $data['key']['fromMe'] === true) $isFromMe = true;

// If it is the bot's own message, STOP here.
if ($isFromMe) {
    http_response_code(200);
    echo json_encode(['status' => 'ignored', 'reason' => 'Message is fromMe']);
    exit;
}

// 3. Forward to n8n
// User provided URL: https://halil11.app.n8n.cloud/webhook/teklifOnayCevap
$n8n_url = 'https://halil11.app.n8n.cloud/webhook/teklifOnayCevap';

$ch = curl_init($n8n_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $input); // Send raw input to preserve structure
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Optional: Timeout to prevent hanging
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// 4. Return n8n's response to the caller (Evolution API)
http_response_code($http_code);
if ($error) {
    echo json_encode(['status' => 'error', 'message' => 'Forwarding failed', 'details' => $error]);
} else {
    echo $response;
}
?>
