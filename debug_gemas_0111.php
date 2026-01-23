<?php
// Debug Script: Analyze Sales Discrepancy for 0111STRM50M in GEMAS
header("Content-Type: text/plain; charset=utf-8");
error_reporting(E_ALL);
ini_set('display_errors', 1);

$mssql_hostname = "192.168.5.253,1433";
$mssql_username = "halil";
$mssql_password = "12621262";
$dbname         = "GEMAS2026"; 
$viewName       = '[dbo].[LV_526_SALES_ITEMS]'; // Note LV_526 for GEMAS
$productCode    = '0111STRM50M';
$outputFile     = __DIR__ . '/debug_gemas_output.txt';

file_put_contents($outputFile, "DEBUG ANALYSIS FOR: $productCode\n");
file_put_contents($outputFile, "Database: $dbname, View: $viewName\n");
file_put_contents($outputFile, "Date: " . date('Y-m-d H:i:s') . "\n\n", FILE_APPEND);

try {
    $dsn = "sqlsrv:Server=$mssql_hostname;Database=$dbname";
    $pdo = new PDO($dsn, $mssql_username, $mssql_password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    // 1. Get Totals first
    $sqlTotal = "SELECT 
                    SUM(STLINE_OUTPUT_AMOUNT) as TotalOutput, 
                    SUM(STLINE_INPUT_AMOUNT) as TotalInput,
                    COUNT(*) as TotalCount
                 FROM $viewName 
                 WHERE ITEMS_CODE = :code";
    
    $stmtTotal = $pdo->prepare($sqlTotal);
    $stmtTotal->execute([':code' => $productCode]);
    $total = $stmtTotal->fetch(PDO::FETCH_ASSOC);
    
    file_put_contents($outputFile, "AGGREGATE TOTALS:\n", FILE_APPEND);
    file_put_contents($outputFile, print_r($total, true) . "\n", FILE_APPEND);

    // 2. Breakdown
    $sqlBreakdown = "SELECT 
                        STLINE_TRCODE, 
                        STLINE_IOCODE, 
                        STFICHE_TRCODE,
                        CAPIWHOUSE_NR,
                        SUM(STLINE_OUTPUT_AMOUNT) as OutputQty,
                        SUM(STLINE_INPUT_AMOUNT) as InputQty,
                        COUNT(*) as TotalCount
                     FROM $viewName 
                     WHERE ITEMS_CODE = :code
                     GROUP BY STLINE_TRCODE, STLINE_IOCODE, STFICHE_TRCODE, CAPIWHOUSE_NR
                     ORDER BY STLINE_TRCODE";

    $stmtBreakdown = $pdo->prepare($sqlBreakdown);
    $stmtBreakdown->execute([':code' => $productCode]);
    $breakdown = $stmtBreakdown->fetchAll(PDO::FETCH_ASSOC);

    file_put_contents($outputFile, "BREAKDOWN BY TRCODE:\n", FILE_APPEND);
    foreach ($breakdown as $row) {
        file_put_contents($outputFile, print_r($row, true), FILE_APPEND);
    }

} catch (PDOException $e) {
    file_put_contents($outputFile, "FATAL ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
}
