<?php
// test_overdue.php
require_once "include/fonksiyon.php";

header('Content-Type: text/plain; charset=utf-8');

$cariKodu = "120.01.E04";
echo "Testing for Code: $cariKodu\n";

gemas_logo_veritabani();

if (!isset($gemas_logo_db) || $gemas_logo_db === null) {
    die("Logo DB Connection Failed.");
}

try {
    // 1. Check if Code exists in CLCARD
    $sql1 = "SELECT LOGICALREF, DEFINITION_ FROM LG_526_CLCARD WHERE CODE = :code";
    $stmt = $gemas_logo_db->prepare($sql1);
    $stmt->execute([':code' => $cariKodu]);
    $card = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$card) {
        echo "ERROR: Code '$cariKodu' not found in LG_526_CLCARD.\n";
        exit;
    }
    echo "Found CLCARD: LogicalRef={$card['LOGICALREF']}, Name={$card['DEFINITION_']}\n";
    $cardRef = $card['LOGICALREF'];

    // 2. Check total PAYTRANS records
    $sql2 = "SELECT COUNT(*) FROM LG_526_01_PAYTRANS WHERE CARDREF = :ref AND MODULENR = 4";
    $stmt = $gemas_logo_db->prepare($sql2);
    $stmt->execute([':ref' => $cardRef]);
    $countTotal = $stmt->fetchColumn();
    echo "Total Invoice Transactions (MODULENR=4): $countTotal\n";

    // 3. Check Unpaid Transactions (Total - Paid > 0)
    $sql3 = "SELECT COUNT(*) FROM LG_526_01_PAYTRANS WHERE CARDREF = :ref AND MODULENR = 4 AND (TOTAL - PAID) > 0.01";
    $stmt = $gemas_logo_db->prepare($sql3);
    $stmt->execute([':ref' => $cardRef]);
    $countUnpaid = $stmt->fetchColumn();
    echo "Unpaid Transactions: $countUnpaid\n";

    // 4. Check Overdue > 60 Days (The Target Query Logic)
    $sql4 = "SELECT count(*) 
             FROM LG_526_01_PAYTRANS 
             WHERE CARDREF = :ref 
               AND MODULENR = 4 
               AND (TOTAL - PAID) > 0.01 
               AND DATEDIFF(DAY, PROCDATE, GETDATE()) > 60";
    $stmt = $gemas_logo_db->prepare($sql4);
    $stmt->execute([':ref' => $cardRef]);
    $countOverdue = $stmt->fetchColumn();
    echo "Overdue > 60 Days: $countOverdue\n";

} catch (PDOException $e) {
    echo "SQL Exception: " . $e->getMessage();
}
