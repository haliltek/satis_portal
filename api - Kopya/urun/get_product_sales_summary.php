<?php
/**
 * API: Get Product Sales Summary
 * Endpoint: api/urun/get_product_sales_summary.php
 * 
 * Bu API, Logo ERP'den belirtilen stok kodunun genel satış özetini getirir.
 * Yöntem: Kullanıcının belirttiği özel SQL sorgusu (STLINE JOIN ITEMS, TRCODE IN 7,8)
 * Veri Kaynağı: SADECE GEMPA (Firma 566/565/564)
 * 
 * Parameters:
 *   - code: Stok kodu (required)
 *   - token: Güvenlik token'ı (required)
 */

header("Content-Type: application/json; charset=utf-8");
header('Access-Control-Allow-Origin: *');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Includes
if (file_exists("../../include/vt.php")) {
    include "../../include/vt.php";
} elseif (file_exists(__DIR__ . "/../../include/vt.php")) {
    include __DIR__ . "/../../include/vt.php";
} else {
    include "include/vt.php"; 
}

// Token Check
$validToken = "gemas_secret_n8n_token_2025"; 
$inputToken = $_GET['token'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
if (strpos($inputToken, 'Bearer ') === 0) {
    $inputToken = substr($inputToken, 7);
}

if ($inputToken !== $validToken) {
    http_response_code(401);
    die(json_encode(["status" => "error", "message" => "Unauthorized"]));
}

// Get Parameters
$code = isset($_GET['code']) ? trim($_GET['code']) : '';

if (empty($code)) {
    http_response_code(400);
    die(json_encode(["status" => "error", "message" => "Product code required"]));
}

// Logo Connection Info
$mssql_hostname = "192.168.5.253,1433";
$mssql_username = "halil";
$mssql_password = "12621262";

$debugLog = __DIR__ . '/sales_api_debug.log';
file_put_contents($debugLog, "\n" . date('Y-m-d H:i:s') . " - NEW REQUEST for code: $code\n", FILE_APPEND);

// Son 3 yıl + mevcut yıl
$currentYear = (int)date('Y');
$years = [$currentYear - 2, $currentYear - 1, $currentYear]; // ör: 2024, 2025, 2026

file_put_contents($debugLog, "Years to query: " . implode(', ', $years) . "\n", FILE_APPEND);

// DB Configs with Base Year 2024 for Firm Codes
// GEMPA Series (56x): 2024=564, 2025=565, 2026=566
$databases = [
    ['name' => 'GEMPA', 'base_code_2024' => 564]
];

$yearlyData = [];
$totalQuantity = 0;
$totalInvoices = 0;
$productName = ''; 

foreach ($years as $year) {
    $yearTotal = 0;
    $yearInvoices = 0;
    
    foreach ($databases as $dbConfig) {
        $dbPrefix = $dbConfig['name']; // GEMPA
        $base2024 = $dbConfig['base_code_2024'];
        
        // Calculate Firm Code for the specific year
        // 2024 -> +0, 2025 -> +1, 2026 -> +2
        $offset = $year - 2024;
        $firmCode = $base2024 + $offset;
        
        $dbname = $dbPrefix . $year; // e.g. GEMPA2026
        
        // Table Definitions
        $stlineTable = "[dbo].[LG_{$firmCode}_01_STLINE]";
        $itemsTable  = "[dbo].[LG_{$firmCode}_ITEMS]";
        $unitsetTable = "[dbo].[LG_{$firmCode}_UNITSETL]";
        
        file_put_contents($debugLog, "Checking $dbname -> $stlineTable\n", FILE_APPEND);
        
        try {
            $dsn = "sqlsrv:Server=$mssql_hostname;Database=$dbname";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::SQLSRV_ATTR_QUERY_TIMEOUT => 5
            ];
            
            $pdo = new PDO($dsn, $mssql_username, $mssql_password, $options);
            
            // User Provided Query Pattern
            // SELECT SUM(STL.AMOUNT) AS "Toplam Satış Miktarı", MAX(I.NAME) ...
            // FROM LG_566_01_STLINE STL INNER JOIN LG_566_ITEMS I ON STL.STOCKREF=I.LOGICALREF
            // WHERE STL.TRCODE IN (7,8) AND STL.CANCELLED=0 AND STL.LINETYPE=0 AND I.CODE = :code
            
            $sql = "SELECT 
                        ISNULL(SUM(STL.AMOUNT), 0) as total_qty,
                        MAX(I.NAME) as product_name,
                        COUNT(DISTINCT STL.INVOICEREF) as invoice_count
                    FROM $stlineTable STL 
                    INNER JOIN $itemsTable I ON STL.STOCKREF = I.LOGICALREF
                    WHERE STL.TRCODE IN (7,8) 
                      AND STL.CANCELLED = 0 
                      AND STL.LINETYPE = 0 
                      AND I.CODE = :code";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':code', $code, PDO::PARAM_STR);
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $qty = (float)$row['total_qty'];
                $inv = (int)$row['invoice_count'];
                $pName = $row['product_name'];
                
                if ($pName && empty($productName)) {
                    $productName = $pName;
                }
                
                if ($qty > 0) {
                    file_put_contents($debugLog, "  => Found: $qty (Invoices: $inv) in $dbname\n", FILE_APPEND);
                    $yearTotal += $qty;
                    $yearInvoices += $inv;
                }
            }
            
        } catch (PDOException $e) {
            file_put_contents($debugLog, "  => Error ($dbname): " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
    
    $yearlyData[] = [
        'year' => $year,
        'quantity' => $yearTotal,
        'invoices' => $yearInvoices 
    ];
    
    $totalQuantity += $yearTotal;
    $totalInvoices += $yearInvoices;
}

// Ortalama hesapla
$avgQuantity = count($yearlyData) > 0 ? $totalQuantity / count($yearlyData) : 0;

// En çok satılan yıl
$bestYear = null;
$bestQuantity = 0;
foreach ($yearlyData as $data) {
    if ($data['quantity'] > $bestQuantity) {
        $bestQuantity = $data['quantity'];
        $bestYear = $data['year'];
    }
}

// Response
echo json_encode([
    'found' => $totalQuantity > 0,
    'stock_code' => $code,
    'product_name' => $productName,
    'summary' => [
        'total_quantity' => round($totalQuantity, 2),
        'total_invoices' => $totalInvoices,
        'avg_yearly_quantity' => round($avgQuantity, 2),
        'best_year' => $bestYear,
        'best_year_quantity' => round($bestQuantity, 2)
    ],
    'yearly_breakdown' => $yearlyData,
    'period' => [
        'start_year' => min($years),
        'end_year' => max($years),
        'total_years' => count($years)
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
