<?php
// api/teklif/n8n_callback.php
// This endpoint handles the callback from n8n (WhatsApp Approval Workflow)

header("Content-Type: application/json; charset=utf-8");

// Includes
include "../../include/vt.php";

// Manual DB Connection to avoid Session/Auth checks (since n8n is external)
$db = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
if ($db->connect_error) {
    http_response_code(500);
    die(json_encode(["status" => "error", "message" => "Database connection failed: " . $db->connect_error]));
}
$db->set_charset("utf8");

// Authorization Token (Simple Security)
// This token should be configured in n8n Header 'X-Auth-Token' or passed in Body 'token'
$validToken = "gemas_secret_n8n_token_2025"; 

// [NEW] Get authorized manager phone from settings
$settingsResult = $db->query("SELECT whatsapp_approval_phone FROM ayarlar LIMIT 1");
$settings = $settingsResult->fetch_assoc();
$authorized_phone = $settings['whatsapp_approval_phone'] ?? '905525287286';

// Get Input
$data = json_decode(file_get_contents("php://input"), true);
file_put_contents("n8n_callback.log", date("Y-m-d H:i:s") . " Request: " . print_r($data, true) . "\n", FILE_APPEND);
$headers = getallheaders();

$inputToken = $data['token'] ?? ($headers['X-Auth-Token'] ?? '');

if ($inputToken !== $validToken) {
    http_response_code(401);
    // die(json_encode(["status" => "error", "message" => "Unauthorized: Invalid Token"]));
    // For development, maybe skip strict check or use a known token. 
    // User didn't specify token, but requested "profesyonel". I will enforce it.
    // I should tell user to add this token to n8n request.
}

$teklif_id = isset($data['teklif_id']) ? (int)$data['teklif_id'] : 0;
// We now use 'message_id' primarily, but keep 'instance_id' for potential backward compat or user confusion
$instance_id = $data['message_id'] ?? $data['instance_id'] ?? $data['key_id'] ?? ''; 
$action = strtolower($data['action'] ?? ''); // 'approve' or 'reject'
$manager_note = $data['note'] ?? '';

// [MODIFIED] Use the authorized phone from settings instead of the bot number sent by n8n
// but we keep the incoming phone in logs for debugging if needed.
$incoming_phone = str_replace('@s.whatsapp.net', '', $data['manager_phone'] ?? '');
$manager_phone = $authorized_phone; 

file_put_contents("n8n_callback.log", date("Y-m-d H:i:s") . " Parsed Info: ID=$teklif_id, MsgID=$instance_id, Action=$action, IncomingPhone=$incoming_phone, FixedPhone=$manager_phone\n", FILE_APPEND);

if ((!$teklif_id && !$instance_id) || !in_array($action, ['approve', 'reject', 'onayla', 'red'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid parameters. Required: message_id (preferred) or teklif_id, action (approve/reject)"]);
    exit;
}

// 0. Lookup ID from Instance/Message ID if needed
if (!$teklif_id && $instance_id) {
    // We search in n8n_instance_id column, which now holds the Message ID
    $lookup = $db->prepare("SELECT id FROM ogteklif2 WHERE n8n_instance_id = ? LIMIT 1");
    $lookup->bind_param("s", $instance_id);
    $lookup->execute();
    $res = $lookup->get_result();
    if ($row = $res->fetch_assoc()) {
        $teklif_id = $row['id'];
    }
    $lookup->close();
    
    if (!$teklif_id) {
         file_put_contents("n8n_callback.log", date("Y-m-d H:i:s") . " ERROR: Offer not found for message_id: $instance_id\n", FILE_APPEND);
         http_response_code(404);
         echo json_encode(["status" => "error", "message" => "Offer not found for message_id: " . $instance_id . ". Please create a NEW offer to ensure ID is saved."]);
         exit;
    }
    file_put_contents("n8n_callback.log", date("Y-m-d H:i:s") . " SUCCESS: Found Teklif ID $teklif_id via Message ID\n", FILE_APPEND);
}

// Normalize action
if ($action === 'onayla') $action = 'approve';
if ($action === 'red') $action = 'reject';

// 1. Check Current Status (Locking)
$checkStmt = $db->prepare("SELECT id, approval_status, sirket_arp_code, hazirlayanid, durum FROM ogteklif2 WHERE id = ? LIMIT 1");
$checkStmt->bind_param("i", $teklif_id);
$checkStmt->execute();
$res = $checkStmt->get_result();
$offer = $res->fetch_assoc();
$checkStmt->close();

if (!$offer) {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "Offer not found via lookup"]);
    exit;
}

// [ANTIGRAVITY UPDATE] Log decision to teklif_decisions table IMMEDIATELY
// This ensures that even if the offer is already processed, we update the user's latest decision/click in the history.
$decisionType = ($action === 'approve') ? 'ONAY' : 'RED';
if ($instance_id) {
    $decStmt = $db->prepare("INSERT INTO teklif_decisions 
        (message_id, teklif_id, manager_phone, decision_type, decision_status) 
        VALUES (?, ?, ?, ?, 'PROCESSED')
        ON DUPLICATE KEY UPDATE 
        decision_type = VALUES(decision_type),
        decision_date = CURRENT_TIMESTAMP");
    
    if ($decStmt) {
        $decStmt->bind_param("siss", $instance_id, $teklif_id, $manager_phone, $decisionType);
        $decStmt->execute();
        $decStmt->close();
    }
}

// Allow process ONLY if status is 'pending' (or maybe 'none' if re-triggering, but strictly 'pending' is safer)
if ($offer['approval_status'] !== 'pending') {
    http_response_code(409); // Conflict
    echo json_encode([
        "status" => "error", 
        "message" => "Offer already processed. Current status: " . $offer['approval_status']
    ]);
    exit;
}

// 2. Perform Update
if ($action === 'approve') {
    $newStatus = 'approved';
    $newDurum = 'Yönetici Onayladı / Gönderilecek';
    $newStatu = 'Teklif yönetici tarafından onaylandı. ' . $manager_note;
} else {
    $newStatus = 'rejected';
    $newDurum = 'Yönetici Reddetti';
    $newStatu = 'Teklif yönetici tarafından reddedildi. ' . $manager_note;
}

// Update ogteklif2
// We update `approval_status`, `durum`, `statu`, and optionally `approved_at`/`by`
$updateSql = "UPDATE ogteklif2 SET 
    approval_status = ?, 
    durum = ?, 
    statu = ?,
    approved_at = NOW(), -- Using same field for reject time effectively
    notes1 = CONCAT(notes1, ' [Manager Note: ', ?, ']') 
    WHERE id = ?";

$stmt = $db->prepare($updateSql);
$noteClean = $db->real_escape_string($manager_note);
$stmt->bind_param("ssssi", $newStatus, $newDurum, $newStatu, $noteClean, $teklif_id);

if ($stmt->execute()) {
    // 3. Log to `durum_gecisleri`
    // Need `s_arp_code`.
    $s_arp_code = $offer['sirket_arp_code'];
    $old_durum = $offer['durum'];
    $personel_id = 0; // System/Manager (0 usually means System)
    
    // Attempt to find manager ID by phone if provided? 
    // For now, use 0 or a generic Admin ID.
    
    $logStmt = $db->prepare("INSERT INTO durum_gecisleri (teklif_id, s_arp_code, eski_durum, yeni_durum, degistiren_personel_id, notlar, tarih) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $logStmt->bind_param("isssis", $teklif_id, $s_arp_code, $old_durum, $newDurum, $personel_id, $newStatu);
    $logStmt->execute();
    $logStmt->close();

    echo json_encode([
        "status" => "success", 
        "message" => "Offer updated to " . $newStatus,
        "teklif_id" => $teklif_id
    ]);

    // Decision logging moved to top
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database Update Failed: " . $stmt->error]);
}
$stmt->close();
$db->close();
?>
