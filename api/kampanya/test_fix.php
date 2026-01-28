<?php
// Mock POST data
$_POST['cart'] = json_encode([
    ['code' => '02400', 'quantity' => 150] // ÇOK YOLLU VANA (Min 100 Adet)
]);
$_POST['customer_id'] = 123;
$_POST['customer_name'] = 'Test Müşteri'; // Not main dealer

// Capture output
ob_start();
require 'check_conditions.php';
$output = ob_get_clean();

echo "--- OUTPUT ---\n";
echo $output . "\n";
echo "----------------\n";

$json = json_decode($output, true);
if ($json && $json['eligible']) {
    echo "RESULT: SUCCESS (Eligible)\n";
    print_r($json['campaigns']);
} else {
    echo "RESULT: FAILURE (Not Eligible)\n";
}
