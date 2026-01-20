<?php
// test_risk_data.php
require_once "include/fonksiyon.php";

echo "<h1>Logo Risk Data Check (LG_CLRNUMS)</h1>";
echo "<pre>";

// 1. Init Connections
gemas_logo_veritabani();
gempa_logo_veritabani();

global $gemas_logo_db, $gempa_logo_db;


function checkRiskData($pdo, $firmNr, $periodNr, $dbName) {
    if (!$pdo) {
        echo "[$dbName] Connection Failed.\n";
        return;
    }

    $tableName = "LG_{$firmNr}_{$periodNr}_CLRNUMS";
    echo "[$dbName] Connected. Checking $tableName...\n";

    try {
        $sql = "
            SELECT TOP 10
                C.CODE as CariKodu,
                C.DEFINITION_ as CariAdi,
                R.RISKTOTAL,
                R.RISKLIMIT,
                R.RISKBALANCED,
                R.ORDRISKTOTAL,
                R.DESPRISKTOTAL,
                R.CEKRISKFACTOR
            FROM $tableName R
            JOIN LG_{$firmNr}_CLCARD C ON C.LOGICALREF = R.CLCARDREF
            WHERE C.CODE = '120.01.e04'
            ORDER BY R.RISKTOTAL DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($rows) > 0) {
            echo "Found " . count($rows) . " records with Risk Data:\n";
            print_r($rows);
        } else {
            echo "No records found with RISKTOTAL > 0.\n";
        }

    } catch (PDOException $e) {
        echo "Query Failed: " . $e->getMessage() . "\n";
    }
    echo "---------------------------------------------------\n";
}

// Check Firm 525 (Gemas) - Period 01
checkRiskData($gemas_logo_db, '525', '01', 'GEMAS2026');

// Check Firm 565 (Gempa) - Period 01
checkRiskData($gempa_logo_db, '565', '01', 'GEMPA2026');

echo "</pre>";
