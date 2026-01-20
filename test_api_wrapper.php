<?php
/**
 * API Wrapper Test
 * bypasses HTTP stack
 */

// Mock Input
$TEST_INPUT = [
    'clientCode' => '120.01.E04',
    'cartItems' => [
        ['productCode' => '0211211S', 'quantity' => 12], // Oran 53 bekleniyor
        ['productCode' => '021131', 'quantity' => 15],   // Oran 55 bekleniyor
        ['productCode' => '02400', 'quantity' => 50],    // min 100, oran 50 -> Beklenen: 0 (miktar yetersiz)
        ['productCode' => '02400', 'quantity' => 150]    // min 100, oran 50 -> Beklenen: 50
    ]
];

// Include API logic with Output Buffering
ob_start();
require 'api/get_campaign_discount.php';
$jsonOutput = ob_get_clean();

// Clean up any potential noise before JSON (though ob_clean catches included content)
// Sometimes warnings appear before `ob_start` if they are startup warnings, but `require` happens after.
// Let's decode
$data = json_decode($jsonOutput, true);

if ($data) {
    file_put_contents('test_result.txt', print_r($data, true));
    echo "Sonuç dosyaya yazıldı.";
} else {
    file_put_contents('test_result.txt', "JSON ERROR:\n" . $jsonOutput);
    echo "Hata dosyaya yazıldı.";
}
?>
