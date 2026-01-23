<?php
/**
 * API: Get Product Stock Quantity
 * Endpoint: api/urun/get_product_stock.php
 * 
 * Bu API, Logo ERP'den belirtilen stok kodunun mevcut stok miktarını getirir.
 * 
 * Parameters:
 *   - code: Stok kodu (required)
 *   - token: Güvenlik token'ı (required)
 * 
 * Example:
 *   GET /api/urun/get_product_stock.php?code=PROD123&token=xxx
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

// Debug log
$debugLog = __DIR__ . '/stock_api_debug.log';
file_put_contents($debugLog, "\n" . date('Y-m-d H:i:s') . " - NEW REQUEST for code: $code\n", FILE_APPEND);

// Mevcut yıl (2026)
$currentYear = (int)date('Y');
$firmCode = 562 + ($currentYear - 2022); // 566 for 2026

$dbname = "GEMPA" . $currentYear;

file_put_contents($debugLog, "Database: $dbname, FirmCode: $firmCode\n", FILE_APPEND);

try {
    $dsn = "sqlsrv:Server=$mssql_hostname;Database=$dbname";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ];
    
    // SQLSRV-specific timeout
    if (defined('PDO::SQLSRV_ATTR_QUERY_TIMEOUT')) {
        $options[PDO::SQLSRV_ATTR_QUERY_TIMEOUT] = 5;
    }
    
    if (defined('PDO::SQLSRV_ATTR_ENCODING') && defined('PDO::SQLSRV_ENCODING_UTF8')) {
        $options[PDO::SQLSRV_ATTR_ENCODING] = PDO::SQLSRV_ENCODING_UTF8;
    }
    
    $pdo = new PDO($dsn, $mssql_username, $mssql_password, $options);
    file_put_contents($debugLog, "✓ Connected to $dbname\n", FILE_APPEND);
    
    // Önce stok kodunun LOGICALREF'ini bul
    $itemsTable = "LG_{$firmCode}_ITEMS";
    $refSql = "SELECT LOGICALREF, CODE, NAME FROM $itemsTable WHERE CODE = :code";
    $refStmt = $pdo->prepare($refSql);
    $refStmt->bindParam(':code', $code, PDO::PARAM_STR);
    $refStmt->execute();
    $itemRow = $refStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$itemRow) {
        file_put_contents($debugLog, "✗ Stock code not found in $itemsTable\n", FILE_APPEND);
        http_response_code(404);
        echo json_encode([
            'found' => false,
            'stock_code' => $code,
            'message' => 'Product not found'
        ]);
        exit;
    }
    
    $stockRef = $itemRow['LOGICALREF'];
    $productName = $itemRow['NAME'];
    file_put_contents($debugLog, "✓ Found STOCKREF: $stockRef, NAME: $productName\n", FILE_APPEND);
    
    // Stok miktarını INVTOT tablosundan çek (Stok toplam tablosu)
    $invtotTable = "LG_{$firmCode}_01_INVTOT";
    
    // INVTOT: STOCKREF, INVENNO (ambar no), ONHAND (eldeki miktar)
    $sql = "SELECT 
                INVENNO,
                ISNULL(ONHAND, 0) as onhand_quantity
            FROM $invtotTable
            WHERE STOCKREF = :stockref
            ORDER BY INVENNO";
    
    file_put_contents($debugLog, "SQL: $sql\n", FILE_APPEND);
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':stockref', $stockRef, PDO::PARAM_INT);
    $stmt->execute();
    
    $warehouses = [];
    $totalStock = 0;
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $qty = (float)$row['onhand_quantity'];
        $warehouses[] = [
            'warehouse' => (int)$row['INVENNO'],
            'quantity' => $qty
        ];
        $totalStock += $qty;
    }
    
    file_put_contents($debugLog, "✓ Total Stock: $totalStock across " . count($warehouses) . " warehouse(s)\n", FILE_APPEND);
    
    // Response
    echo json_encode([
        'found' => true,
        'stock_code' => $code,
        'product_name' => $productName,
        'total_stock' => round($totalStock, 2),
        'warehouses' => $warehouses,
        'database' => $dbname,
        'query_date' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    file_put_contents($debugLog, "✗ ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode([
        'found' => false,
        'stock_code' => $code,
        'error' => $e->getMessage()
    ]);
}
?>
