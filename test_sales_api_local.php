<?php
// Mock parameters
$_GET['code'] = '0111STRM50M';
$_GET['token'] = 'gemas_secret_n8n_token_2025';

// Capture output
ob_start();
include __DIR__ . '/api/urun/get_product_sales_summary.php';
$output = ob_get_clean();

echo "API Response:\n";
echo $output;

// Verify JSON
$json = json_decode($output, true);
if ($json) {
    echo "\n\nJSON Decode Successful.\n";
    print_r($json['summary']);
} else {
    echo "\n\nInvalid JSON.\n";
}
