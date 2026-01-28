<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Veritabanı bağlantı bilgilerini dahil et
include "../../include/vt.php";

// Manuel bağlantı kur
$db = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
if ($db->connect_error) {
    http_response_code(500);
    die(json_encode(["error" => "Veritabanı bağlantı hatası: " . $db->connect_error]));
}
$db->set_charset("utf8");

// Gelen veriyi al
$data = json_decode(file_get_contents("php://input"), true);

// Token kontrolü (Body veya Header)
$headers = getallheaders();
$token = $data['token'] ?? ($headers['Authorization'] ? str_replace('Bearer ', '', $headers['Authorization']) : '');

if ($token !== 'gemas_secret_n8n_token_2025') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$teklif_id = $data['teklif_id'] ?? null;
$message_id = $data['message_id'] ?? null;

if (!$teklif_id || !$message_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing teklif_id or message_id']);
    exit;
}

// Veritabanını güncelle
$sql = "UPDATE ogteklif2 SET n8n_instance_id = ? WHERE id = ?";
$stmt = $db->prepare($sql);

if ($stmt) {
    $stmt->bind_param("si", $message_id, $teklif_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Message ID saved successfully']);
        } else {
            // ID bulunamamış olabilir veya aynı ID tekrar kaydedilmiş olabilir
            echo json_encode(['status' => 'success', 'message' => 'No changes made (ID might be same or Offer not found)']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database update failed: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Query preparation failed']);
}

$db->close();
?>
