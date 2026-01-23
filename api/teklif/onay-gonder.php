<?php
header("Content-Type: application/json");

// Gelen JSON verisini al
$data = json_decode(file_get_contents("php://input"), true);

// Debug log - Daha güvenli log yazma
$logFile = __DIR__ . '/onay-gonder.log';
$logDir = dirname($logFile);

// Klasör yoksa oluştur
if (!file_exists($logDir)) {
    @mkdir($logDir, 0777, true);
}

// Log yazma fonksiyonu
function writeDebugLog($logFile, $message) {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message\n";
    
    // Önce dosyaya yazmayı dene
    if (@file_put_contents($logFile, $logEntry, FILE_APPEND) === false) {
        // Başarısızsa error_log'a yaz
        error_log("Onay-Gonder: $message");
    }
}

writeDebugLog($logFile, "Request Data: " . json_encode($data));

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
$errno = curl_errno($ch);
curl_close($ch);

// Detaylı debug log
writeDebugLog($logFile, "Webhook Response - HTTP Code: $httpCode, cURL Error: $errno, Error Message: $error");
if ($response) {
    writeDebugLog($logFile, "Webhook Response Body: " . substr($response, 0, 500));
}

if ($response === false || $errno !== 0) {
    $errorData = [
        "status" => "error", 
        "message" => "Webhook gönderilemedi", 
        "error" => $error,
        "errno" => $errno,
        "http_code" => $httpCode
    ];
    writeDebugLog($logFile, "ERROR: " . json_encode($errorData));
    echo json_encode($errorData);
} else {
    echo json_encode(["status" => "sent", "http_code" => $httpCode, "n8n_response" => json_decode($response)]);
}
?>
