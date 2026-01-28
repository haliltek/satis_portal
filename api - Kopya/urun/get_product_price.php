<?php
// api/urun/get_product_price.php
header("Content-Type: application/json; charset=utf-8");
header('Access-Control-Allow-Origin: *');

// Includes
if (file_exists("../../include/vt.php")) {
    include "../../include/vt.php";
} elseif (file_exists(__DIR__ . "/../../include/vt.php")) {
    include __DIR__ . "/../../include/vt.php";
} else {
    // Fallback for CLI testing from root
    include "include/vt.php"; 
}

// Manual DB Connection
if (!isset($sql_details)) {
    die(json_encode(["status" => "error", "message" => "Database config not found"]));
}

$db = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
if ($db->connect_error) {
    http_response_code(500);
    die(json_encode(["status" => "error", "message" => "Database connection failed"]));
}
$db->set_charset("utf8");

// Polyfill for getallheaders if not exists (CLI or some servers)
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

// Token Check
$validToken = "gemas_secret_n8n_token_2025"; 
$headers = getallheaders();
$inputToken = $headers['Authorization'] ?? '';
// Handle 'Bearer ' prefix and GET param fallback
if (strpos($inputToken, 'Bearer ') === 0) {
    $inputToken = substr($inputToken, 7);
}
if (!$inputToken && isset($_GET['token'])) {
    $inputToken = $_GET['token'];
}

if ($inputToken !== $validToken) {
    http_response_code(401);
    die(json_encode(["status" => "error", "message" => "Unauthorized"]));
}

// Get Code
$code = isset($_GET['code']) ? trim($_GET['code']) : '';

if (empty($code)) {
    http_response_code(400);
    die(json_encode(["status" => "error", "message" => "Product code required"]));
}

// Query
$stmt = $db->prepare("SELECT urun_id, stokkodu, stokadi, fiyat, doviz, marka, stokadi_en FROM urunler WHERE stokkodu = ? LIMIT 1");
$stmt->bind_param("s", $code);
$stmt->execute();
$res = $stmt->get_result();
$product = $res->fetch_assoc();
$stmt->close();
$db->close();

if ($product) {
    echo json_encode([
        "found" => true,
        "stokkodu" => $product['stokkodu'],
        "name" => $product['stokadi'],
        "name_en" => $product['stokadi_en'],
        "price" => $product['fiyat'],
        "currency" => $product['doviz'],
        "brand" => $product['marka']
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    // 200 OK but found=false is often easier for n8n to handle than 404 error flow
    echo json_encode([
        "found" => false,
        "message" => "Product not found"
    ]);
}
?>
