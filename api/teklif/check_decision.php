<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
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

// Token kontrolü
$headers = getallheaders();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';
// Fallback for some servers where Authorization header is not accessible via getallheaders
if (empty($token) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
}

// Güvenlik tokenini burada sabit bırakıyoruz, üretimde env veya config'den gelmeli
if ($token !== 'gemas_secret_n8n_token_2025') {
    http_response_code(401);
    // Debug için alınan token'ı ve headerları gösterelim
    echo json_encode([
        'error' => 'Unauthorized', 
        'expected' => 'gemas_secret_n8n_token_2025',
        'received' => $token,
        'debug_headers' => $headers
    ]);
    exit;
}

// Parametreleri al
$message_id = isset($_GET['message_id']) ? $_GET['message_id'] : '';
$manager_phone = isset($_GET['manager_phone']) ? $_GET['manager_phone'] : '';

if (empty($message_id) && empty($manager_phone)) {
    http_response_code(400);
    echo json_encode(['error' => 'message_id veya manager_phone gerekli']);
    exit;
}

try {
    $query = "SELECT 
                decision_status, 
                decision_type, 
                decision_date,
                manager_phone,
                teklif_id
              FROM teklif_decisions 
              WHERE ";
    
    $params = [];
    $types = "";

    if (!empty($message_id)) {
        $query .= "message_id = ?";
        $params[] = $message_id;
        $types .= "s";
    } else {
        $query .= "manager_phone = ? ORDER BY decision_date DESC LIMIT 1";
        $params[] = $manager_phone;
        $types .= "s";
    }
    
    $stmt = $db->prepare($query);
    if ($stmt === false) {
        throw new Exception($db->error);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result) {
        // Daha önce karar verilmiş
        echo json_encode([
            'decided' => true,
            'decision' => $result['decision_type'], // 'ONAY' veya 'RED'
            'date' => $result['decision_date'],
            'manager_phone' => $result['manager_phone'],
            'teklif_id' => $result['teklif_id']
        ]);
    } else {
        // İlk kez karar veriliyor
        echo json_encode([
            'decided' => false,
            'decision' => null,
            'date' => null
        ]);
    }
    $stmt->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

$db->close();
?>
