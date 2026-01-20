<?php
include "fonk.php";
$id = 78;
$q = $db->prepare("SELECT id, durumu, onaylayanid FROM ogteklif2 WHERE id=?");
$q->bind_param("i", $id);
$q->execute();
$res = $q->get_result()->fetch_assoc();

header('Content-Type: text/plain');
echo "ID: " . $res['id'] . "\n";
echo "Durumu (DB): [" . $res['durumu'] . "]\n";
echo "Durumu (Hex): " . bin2hex($res['durumu']) . "\n";
echo "OnaylayanID: " . $res['onaylayanid'] . "\n";

// Target string check
$target = 'Yönetici Onayı Bekleniyor';
echo "Target: [" . $target . "]\n";
echo "Target (Hex): " . bin2hex($target) . "\n";

echo "Equal? " . ($res['durumu'] === $target ? "YES" : "NO") . "\n";
?>
