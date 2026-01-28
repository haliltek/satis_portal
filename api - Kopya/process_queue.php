<?php
// api/process_queue.php
// This script should be run via cron or triggered after adding to queue.
// It ensures ONLY ONE transfer happens at a time using a lock file.

set_time_limit(300); // 5 minutes max per run
ignore_user_abort(true);

require_once __DIR__ . '/../fonk.php';

// Global objects from fonk.php are already initialized:
// $db (mysqli), $dbManager (DatabaseManager), $logoService (LogoService)
$conn = $db;

// 1. Singleton Lock
$lockFile = __DIR__ . '/../temp_files/process_queue.lock';
if (!is_dir(dirname($lockFile))) {
    mkdir(dirname($lockFile), 0777, true);
}

$fp = fopen($lockFile, "w+");
if (!flock($fp, LOCK_EX | LOCK_NB)) {
    // Already running
    echo "Another process is already running.";
    fclose($fp);
    exit;
}

if (!$conn) {
    echo "DB Connection Error";
    flock($fp, LOCK_UN);
    fclose($fp);
    exit;
}

// 3. Find next pending
$sql = "SELECT id, offer_id, admin_id FROM logo_transfer_queue WHERE status = 'pending' ORDER BY id ASC LIMIT 1";
$result = $conn->query($sql);

if ($result && $row = $result->fetch_assoc()) {
    $queue_id = (int)$row['id'];
    $offer_id = (int)$row['offer_id'];

    // 4. Mark as processing
    $conn->query("UPDATE logo_transfer_queue SET status = 'processing', processed_at = NOW() WHERE id = $queue_id");

    // 5. Execute Transfer
    try {
        // Global $logoService is already initialized in fonk.php
        global $logoService;


        $transferResult = $logoService->transferOrder($offer_id);

        if ($transferResult['status']) {
            $logo_ref = (int)($transferResult['response']['INTERNAL_REFERENCE'] ?? 0);
            $logo_no = $transferResult['response']['NUMBER'] ?? '';
            
            $stmt = $conn->prepare("UPDATE logo_transfer_queue SET status = 'success', message = ?, logo_ref = ?, logo_no = ?, processed_at = NOW() WHERE id = ?");
            $msg = 'Başarıyla aktarıldı.';
            $stmt->bind_param("sisi", $msg, $logo_ref, $logo_no, $queue_id);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare("UPDATE logo_transfer_queue SET status = 'error', message = ?, processed_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $transferResult['message'], $queue_id);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) {
        $stmt = $conn->prepare("UPDATE logo_transfer_queue SET status = 'error', message = ?, processed_at = NOW() WHERE id = ?");
        $msg = "Exception: " . $e->getMessage();
        $stmt->bind_param("si", $msg, $queue_id);
        $stmt->execute();
        $stmt->close();
    }
} else {
    echo "Queue is empty.";
}

$conn->close();
flock($fp, LOCK_UN);
fclose($fp);
?>
