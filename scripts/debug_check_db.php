<?php
require_once 'fonk.php';

// Check for any records with is_special_offer = 1
$sql1 = "SELECT count(*) as count FROM ogteklif2 WHERE is_special_offer = 1";
$res1 = $db->query($sql1);
$row1 = $res1->fetch_assoc();
echo "Records with is_special_offer=1: " . $row1['count'] . "\n";

// Check for any records with approval_status = 'pending'
$sql2 = "SELECT count(*) as count FROM ogteklif2 WHERE approval_status = 'pending'";
$res2 = $db->query($sql2);
$row2 = $res2->fetch_assoc();
echo "Records with approval_status='pending': " . $row2['count'] . "\n";

// Check for any records with durum = 'Yönetici Onayı Bekliyor'
$sql3 = "SELECT count(*) as count FROM ogteklif2 WHERE durum = 'Yönetici Onayı Bekliyor'";
$res3 = $db->query($sql3);
$row3 = $res3->fetch_assoc();
echo "Records with durum='Yönetici Onayı Bekliyor': " . $row3['count'] . "\n";

// List recent proposals to see their status
$sql4 = "SELECT id, teklifkodu, durum, is_special_offer, approval_status FROM ogteklif2 ORDER BY id DESC LIMIT 5";
$res4 = $db->query($sql4);
echo "\nLast 5 Proposals:\n";
while ($row = $res4->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Code: " . $row['teklifkodu'] . " | Status: " . $row['durum'] . " | Special: " . $row['is_special_offer'] . " | Approval: " . $row['approval_status'] . "\n";
}
?>
