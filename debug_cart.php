<?php
header('Content-Type: text/plain; charset=utf-8');
require_once 'include/vt.php';

try {
    $pdo = new PDO("mysql:host={$sql_details['host']};dbname={$sql_details['db']};charset=utf8mb4", $sql_details['user'], $sql_details['pass']);
    
    // 1. Fetch Rules
    $stmtRules = $pdo->prepare("SELECT * FROM custom_campaigns WHERE is_active = 1 ORDER BY LENGTH(category_name) DESC");
    $stmtRules->execute();
    $dbCampaigns = $stmtRules->fetchAll(PDO::FETCH_ASSOC);
    $campaignRules = [];
    foreach ($dbCampaigns as $dbCamp) {
        $campaignRules[$dbCamp['category_name']] = $dbCamp;
    }

    // 2. Products
    $productCodes = ['02213027', '021714', '051170E', '0312221', '023411K'];
    // Mock Cart quantities
    $cart = [
        '02213027' => 100, // VANA
        '021714' => 10,    // FILTRE
        '051170E' => 100,  // LED
        '0312221' => 50,   // MERDIVEN
        '023411K' => 5000  // KUM
    ];
    $prices = [
        '02213027' => 70,
        '021714' => 436,
        '051170E' => 68,
        '0312221' => 189,
        '023411K' => 0.76
    ];

    $placeholders = implode(',', array_fill(0, count($productCodes), '?'));
    $stmt = $pdo->prepare("SELECT stok_kodu, kategori FROM kampanya_ozel_fiyatlar WHERE stok_kodu IN ($placeholders)");
    $stmt->execute($productCodes);
    $campaignProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $categoryGroups = [];

    // 3. Grouping Logic
    echo "--- Grouping ---\n";
    foreach ($campaignProducts as $product) {
        $prodCat = $product['kategori'] ?: 'Genel';
        $mappedCat = $prodCat;

        if (!isset($campaignRules[$prodCat])) {
            foreach ($campaignRules as $ruleCat => $ruleVal) {
                if (mb_stripos($prodCat, $ruleCat) !== false) {
                    $mappedCat = $ruleCat;
                    break;
                }
            }
        }
        echo "Product {$product['stok_kodu']} ($prodCat) -> Mapped: $mappedCat\n";

        if (!isset($categoryGroups[$mappedCat])) {
            $categoryGroups[$mappedCat] = ['products' => [], 'total_quantity' => 0];
        }
        $categoryGroups[$mappedCat]['products'][] = $product['stok_kodu'];
        $categoryGroups[$mappedCat]['total_quantity'] += $cart[$product['stok_kodu']];
    }

    // 4. Checking Logic
    echo "\n--- Checking ---\n";
    foreach ($categoryGroups as $kategori => $groupData) {
        echo "Checking Group: $kategori (Qty: {$groupData['total_quantity']})\n";
        
        if (isset($campaignRules[$kategori])) {
            $rule = $campaignRules[$kategori];
            $minQty = intval($rule['min_quantity']);
            
            $minAmount = floatval($rule['min_amount']);
            if ($minAmount <= 0 && isset($rule['min_total_amount'])) {
                $minAmount = floatval($rule['min_total_amount']);
            }
            
            $catTotalAmount = 0;
            foreach ($groupData['products'] as $pCode) {
                 $catTotalAmount += ($prices[$pCode] * $cart[$pCode]);
            }
            
            echo "  Rule Qty: $minQty | Rule Amount: $minAmount\n";
            echo "  User Qty: {$groupData['total_quantity']} | User Amount: $catTotalAmount\n";
            
            $qtyCondition = ($minQty > 0) ? ($groupData['total_quantity'] >= $minQty) : true;
            $amountCondition = ($minAmount > 0) ? ($catTotalAmount >= $minAmount) : true;
            
            echo "  QtyCond: " . ($qtyCondition?"PASS":"FAIL") . " | AmtCond: " . ($amountCondition?"PASS":"FAIL") . "\n";
            
            if ($qtyCondition && $amountCondition) {
                echo "  RESULT: ELIGIBLE\n";
            } else {
                echo "  RESULT: NOT ELIGIBLE\n";
            }
        } else {
            echo "  No Rule found for $kategori\n";
        }
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
