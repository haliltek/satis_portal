<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $db = new mysqli('localhost', 'root', '', 'b2bgemascom_teklif');
    $db->set_charset('utf8mb4');
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}

$id = 78;
$stmt = $db->prepare("SELECT id, durumu, onaylayanid FROM ogteklif2 WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if (!$res) {
    echo "No record found for ID $id\n";
    exit;
}

echo "ID: " . $res['id'] . "\n";
echo "Durumu: [" . $res['durumu'] . "]\n";
echo "OnaylayanID: " . $res['onaylayanid'] . "\n";
echo "Durumu Hex: " . bin2hex($res['durumu']) . "\n";

$target = 'Yönetici Onayı Bekleniyor';
echo "Expected: [" . $target . "]\n";
echo "Expected Hex: " . bin2hex($target) . "\n";

if ($res['durumu'] === $target) {
    echo "MATCH: Strings are identical.\n";
} else {
    echo "MISMATCH: Strings are different.\n";
}
?>
