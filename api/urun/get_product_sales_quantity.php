<?php
/**
 * API: Get Product Sales Quantity by Year
 * Endpoint: api/urun/get_product_sales_quantity.php
 * 
 * Bu API, Logo ERP'den belirtilen stok kodunun yıllara göre satış miktarlarını getirir.
 * 
 * Parameters:
 *   - code: Stok kodu (required)
 *   - token: Güvenlik token'ı (required)
 *   - years: Virgülle ayrılmış yıllar (optional, default: 2024,2025,2026)
 *   - firm: Firma (optional: 'gemas', 'gempa', 'both', default: 'both')
 * 
 * Example:
 *   GET /api/urun/get_product_sales_quantity.php?code=PROD123&token=xxx&years=2024,2025
 */

header("Content-Type: application/json; charset=utf-8");
header('Access-Control-Allow-Origin: *');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Production'da kapalı
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
$yearsParam = isset($_GET['years']) ? $_GET['years'] : '2024,2025,2026';
$firm = isset($_GET['firm']) ? strtolower($_GET['firm']) : 'both';

if (empty($code)) {
    http_response_code(400);
    die(json_encode(["status" => "error", "message" => "Product code required"]));
}

// Parse years
$years = array_map('intval', explode(',', $yearsParam));
$years = array_filter($years, function($y) { return $y >= 2020 && $y <= 2030; }); // Geçerli yıl aralığı

if (empty($years)) {
    http_response_code(400);
    die(json_encode(["status" => "error", "message" => "Valid years required (2020-2030)"]));
}

// Logo Connection Info
$mssql_hostname = "192.168.5.253,1433";
$mssql_username = "halil";
$mssql_password = "12621262";

$results = [];
$totalQuantity = 0;

// Sadece GEMPA
$databases = [];
if ($firm === 'gempa' || $firm === 'both') {
    $databases[] = ['name' => 'GEMPA'];
}

foreach ($databases as $dbConfig) {
    $firmName = $dbConfig['name'];
    
    foreach ($years as $year) {
        // GEMPA için yıla göre firma kodu:
        // 2024: 564, 2025: 565, 2026: 566 (hem ITEMS hem STLINE)
        $firmCode = 562 + ($year - 2022); // 564, 565, 566
        
        $dbname = $firmName . $year;
        $tableName = "LG_{$firmCode}_01_STLINE"; // Fatura satır tablosu
        
        try {
            // PDO bağlantısı
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
            
            // Satış miktarını getir
            // STLINE tablosunda AMOUNT = miktar, TRCODE = işlem tipi
            // TRCODE: 7-8 (Perakende satış faturası), 2-3 (Toptan satış faturası)
            $sql = "SELECT 
                        ISNULL(SUM(AMOUNT), 0) as total_quantity,
                        COUNT(*) as invoice_count
                    FROM $tableName
                    WHERE STOCKREF = (
                        SELECT LOGICALREF 
                        FROM LG_{$firmCode}_ITEMS 
                        WHERE CODE = :code
                    )
                    AND TRCODE IN (2, 3, 7, 8)
                    AND LINETYPE = 0"; // 0 = Normal satır
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':code', $code, PDO::PARAM_STR);
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $quantity = $row ? (float)$row['total_quantity'] : 0;
            $invoiceCount = $row ? (int)$row['invoice_count'] : 0;
            
            $results[] = [
                'firm' => $firmName,
                'year' => $year,
                'quantity' => $quantity,
                'invoice_count' => $invoiceCount,
                'database' => $dbname
            ];
            
            $totalQuantity += $quantity;
            
        } catch (PDOException $e) {
            // Veritabanı bulunamadıysa veya hata varsa
            $results[] = [
                'firm' => $firmName,
                'year' => $year,
                'quantity' => 0,
                'invoice_count' => 0,
                'database' => $dbname,
                'error' => $e->getMessage()
            ];
        }
    }
}

// Response
echo json_encode([
    'found' => $totalQuantity > 0,
    'stock_code' => $code,
    'total_quantity' => $totalQuantity,
    'details' => $results,
    'summary' => [
        'years' => $years,
        'firms' => array_column($databases, 'name'),
        'total_invoices' => array_sum(array_column($results, 'invoice_count'))
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
