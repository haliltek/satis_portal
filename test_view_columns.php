<?php
// Test Script: Inspect View Columns to File
header("Content-Type: text/plain; charset=utf-8");
error_reporting(E_ALL);
ini_set('display_errors', 1);

$mssql_hostname = "192.168.5.253,1433";
$mssql_username = "halil";
$mssql_password = "12621262";
$dbname         = "GEMPA2026"; 
$viewName       = '[dbo].[LV_566_SALES_ITEMS]';
$outputFile     = __DIR__ . '/debug_columns.txt';

file_put_contents($outputFile, "Connecting to $dbname...\n");

try {
    $dsn = "sqlsrv:Server=$mssql_hostname;Database=$dbname";
    $pdo = new PDO($dsn, $mssql_username, $mssql_password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    file_put_contents($outputFile, "Connected.\n", FILE_APPEND);

    $sql = "SELECT TOP 1 * FROM $viewName";
    $stmt = $pdo->query($sql);
    
    if ($stmt) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            file_put_contents($outputFile, "Columns for $viewName:\n", FILE_APPEND);
            foreach (array_keys($row) as $col) {
                file_put_contents($outputFile, "- $col\n", FILE_APPEND);
            }
        } else {
            file_put_contents($outputFile, "View $viewName returned no rows (empty).\n", FILE_APPEND);
            // Metadata fallback
            $metaSql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'LV_566_SALES_ITEMS'";
            $stmtM = $pdo->query($metaSql);
            $cols = $stmtM->fetchAll(PDO::FETCH_COLUMN);
            if ($cols) {
                file_put_contents($outputFile, "Columns from Metadata:\n", FILE_APPEND);
                foreach ($cols as $c) file_put_contents($outputFile, "- $c\n", FILE_APPEND);
            }
        }
    } else {
         file_put_contents($outputFile, "Query failed.\n", FILE_APPEND);
    }

} catch (PDOException $e) {
    file_put_contents($outputFile, "FATAL ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
}
