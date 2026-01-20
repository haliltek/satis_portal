<?php
// test_price_api.php
$url = "http://localhost/b2b-gemas-project-main/api/urun/get_product_price.php";
$token = "gemas_secret_n8n_token_2025";
$code = "0111001"; // Assuming a common code, or we can query one first

// 1. Get a valid code first
include __DIR__ . "/include/vt.php";
$db = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
$res = $db->query("SELECT stokkodu FROM urunler LIMIT 1");
if ($row = $res->fetch_assoc()) {
    $code = $row['stokkodu'];
    echo "Found valid stock code for test: $code\n";
} else {
    echo "No products found in database.\n";
    exit;
}
$db->close();

// 2. Test API via CLI simulation (since we can't curl localhost easily if blocked, but we can include the file or use php-cgi if installed. 
// Let's emulate via $_GET and include, safer for simple script test)

$_GET['code'] = $code;
$_GET['token'] = $token;
$_SERVER['REQUEST_METHOD'] = 'GET';

echo "Testing API with Code: $code\n";
echo "Response:\n";

// We need to suppress headers sent from the included file to avoid CLI warnings
ob_start();
include __DIR__ . "/api/urun/get_product_price.php";
$output = ob_get_clean();
echo $output . "\n";
?>
