<?php
// Debug Script: Analyze Sales Discrepancy for 0111STRM50M
header("Content-Type: text/plain; charset=utf-8");
error_reporting(E_ALL);
ini_set('display_errors', 1);

$mssql_hostname = "192.168.5.253,1433";
$mssql_username = "halil";
$mssql_password = "12621262";
$dbname         = "GEMPA2026"; 
$viewName       = '[dbo].[LV_566_SALES_ITEMS]';
$productCode    = '0111STRM50M';
$outputFile     = __DIR__ . '/debug_discrepancy_output.txt';

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

    // 2. Get Detailed Breakdown by TRCODE and WHAREHOUSE
    $sqlBreakdown = "SELECT 
                        STLINE_TRCODE, 
                        STLINE_IOCODE, 
                        STFICHE_TRCODE, /* Sometimes explicit fiche type differs */
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

    file_put_contents($outputFile, "BREAKDOWN BY TRCODE & WAREHOUSE:\n", FILE_APPEND);
    // Header
    file_put_contents($outputFile, sprintf("%-10s %-10s %-15s %-10s %-15s %-15s\n", "TRCODE", "IOCODE", "FICHE_TR", "WH", "OUTPUT(Sales)", "INPUT(Ret?)"), FILE_APPEND);
    file_put_contents($outputFile, str_repeat("-", 80) . "\n", FILE_APPEND);

    foreach ($breakdown as $row) {
        file_put_contents($outputFile, sprintf(
            "%-10s %-10s %-15s %-10s %-15s %-15s\n", 
            $row['STLINE_TRCODE'], 
            $row['STLINE_IOCODE'],
            $row['STFICHE_TRCODE'],
            $row['CAPIWHOUSE_NR'],
            $row['OutputQty'],
            $row['InputQty']
        ), FILE_APPEND);
    }

    // 3. Dump Raw Lines (Top 50) to verify columns
    file_put_contents($outputFile, "\nRAW LINES (Last 50):\n", FILE_APPEND);
    $sqlRaw = "SELECT TOP 50 
                STLINE_DATE_, STLINE_TRCODE, STLINE_OUTPUT_AMOUNT, STLINE_INPUT_AMOUNT, STLINE_LINETYPE, CAPIWHOUSE_NR
               FROM $viewName 
               WHERE ITEMS_CODE = :code
               ORDER BY STLINE_DATE_ DESC";
    
    $stmtRaw = $pdo->prepare($sqlRaw);
    $stmtRaw->execute([':code' => $productCode]);
    $raw = $stmtRaw->fetchAll(PDO::FETCH_ASSOC);
    
    file_put_contents($outputFile, print_r($raw, true), FILE_APPEND);


} catch (PDOException $e) {
    file_put_contents($outputFile, "FATAL ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
}
