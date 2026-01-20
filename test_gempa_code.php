<?php
// test_gempa_code.php
require_once "include/fonksiyon.php";

header('Content-Type: text/plain; charset=utf-8');

$cariKodu = "120.01.E04";
echo "Testing for Code: $cariKodu in GEMPA (565)...\n";

gempa_logo_veritabani();

if (!isset($gempa_logo_db) || $gempa_logo_db === null) {
    die("Gempa Logo DB Connection Failed.");
}

try {
    // 1. Check if Code exists in LG_565_CLCARD
    $sql1 = "SELECT LOGICALREF, DEFINITION_ FROM LG_565_CLCARD WHERE CODE = :code";
    $stmt = $gempa_logo_db->prepare($sql1);
    $stmt->execute([':code' => $cariKodu]);
    $card = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$card) {
        echo "ERROR: Code '$cariKodu' not found in LG_565_CLCARD.\n";
        exit;
    }
    echo "Found CLCARD: LogicRef={$card['LOGICALREF']}, Name={$card['DEFINITION_']}\n";
    
    // 2. Data check
    $sql2 = "SELECT count(*) 
             FROM LG_565_01_PAYTRANS 
             WHERE CARDREF = :ref 
               AND MODULENR = 4 
               AND (TOTAL - PAID) > 0.01 
               AND DATEDIFF(DAY, PROCDATE, GETDATE()) > 60";
    $stmt = $gempa_logo_db->prepare($sql2);
    $stmt->execute([':ref' => $card['LOGICALREF']]);
    $count = $stmt->fetchColumn();
    echo "Overdue Count: $count\n";

} catch (PDOException $e) {
    echo "SQL Exception: " . $e->getMessage();
}
