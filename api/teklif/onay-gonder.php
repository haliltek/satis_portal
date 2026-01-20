<?php
header("Content-Type: application/json");

// Gelen JSON verisini al
$data = json_decode(file_get_contents("php://input"), true);

// Debug log
$logFile = __DIR__ . '/onay-gonder.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Request Data: " . json_encode($data) . "\n", FILE_APPEND);

// VPS üzerindeki n8n Webhook URL
$webhookUrl = "https://flow.gemas.com.tr/webhook/teklifOnay";

$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // SSL doğrulamasını kapat
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 saniye timeout

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Debug log
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Response Code: $httpCode, Error: $error, Response: $response\n", FILE_APPEND);

if ($response === false) {
    echo json_encode(["status" => "error", "message" => "Webhook gönderilemedi", "error" => $error]);
} else {
    echo json_encode(["status" => "sent", "http_code" => $httpCode, "n8n_response" => json_decode($response)]);
}
?>
