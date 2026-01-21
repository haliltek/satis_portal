<?php
/**
 * API: Kategori Bazlı Manuel Kampanya Uygulama
 * Tarih: 2026-01-21
 * 
 * Input: {
 *   items: [{code, quantity, price}], 
 *   customerCode, 
 *   isCashPayment
 * }
 * 
 * Output: {
 *   success, 
 *   discounts: {productCode: {rates[], display, total, campaigns[]}},
 *   applied_campaigns[]
 * }
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . "/../fonk.php";

$response = [
    'success' => false,
    'message' => '',
    'discounts' => [],
    'applied_campaigns' => [],
    'logs' => []
];

function apiLog($msg) {
    global $response;
    $response['logs'][] = date('H:i:s') . ' | ' . $msg;
}

try {
    // ============================================
    // 1. INPUT VALIDATION
    // ============================================
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);
    
    if (!$input) {
        throw new Exception("Geçersiz JSON verisi");
    }
    
    $cartItems = $input['items'] ?? [];
    $customerCode = $input['customerCode'] ?? '';
    $isCashPayment = $input['isCashPayment'] ?? false;
    
    if (empty($customerCode) || empty($cartItems)) {
        throw new Exception("Eksik parametreler (customerCode veya items)");
    }
    
    apiLog("İşlem başladı - Cari: $customerCode, Ürün sayısı: " . count($cartItems) . ", Peşin: " . ($isCashPayment ? 'EVET' : 'HAYIR'));
    
    // ============================================
    // 2. DATABASE CONNECTION
    // ============================================
    $config = require __DIR__ . '/../config/config.php';
    $db = $config['db'];
    $conn = new mysqli($db['host'], $db['user'], $db['pass'], $db['name'], $db['port']);
    $conn->set_charset("utf8mb4");
    
    if ($conn->connect_error) {
        throw new Exception("Veritabanı bağlantı hatası: " . $conn->connect_error);
    }
    
    // ============================================
    // 3. ANA BAYİ KONTROLÜ
    // ============================================
    $stmtCustomer = $conn->prepare("SELECT customer_type, active FROM custom_campaign_customers WHERE customer_code = ? LIMIT 1");
    $stmtCustomer->bind_param("s", $customerCode);
    $stmtCustomer->execute();
    $customerResult = $stmtCustomer->get_result()->fetch_assoc();
    $stmtCustomer->close();
    
    if (!$customerResult || $customerResult['active'] != 1) {
        apiLog("Müşteri kampanya kapsamında değil");
        throw new Exception("Bu müşteri için kampanya bulunmuyor");
    }
    
    $isMainDealer = ($customerResult['customer_type'] === 'ana_bayi');
    apiLog("Müşteri tipi: " . $customerResult['customer_type'] . ($isMainDealer ? ' (ANA BAYİ)' : ''));
    
    // ============================================
    // 4. AKTİF KAMPANYALARI ÇEK
    // ============================================
    $sql = "SELECT * FROM custom_campaigns WHERE active = 1 AND customer_type = 'ana_bayi' ORDER BY priority DESC";
    $campaigns = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    
    apiLog("Bulunan kampanya sayısı: " . count($campaigns));
    
    if (empty($campaigns)) {
        throw new Exception("Aktif kampanya bulunamadı");
    }
    
    // ============================================
    // 5. KAMPANYA ÜRÜN LİSTESİNİ HAZIRLA
    // ============================================
    $campaignProductMap = []; // [product_code => campaign_info]
    
    foreach ($campaigns as $campaign) {
        $stmtProducts = $conn->prepare("SELECT product_code, discount_rate FROM custom_campaign_products WHERE campaign_id = ?");
        $stmtProducts->bind_param("i", $campaign['id']);
        $stmtProducts->execute();
        $products = $stmtProducts->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmtProducts->close();
        
        foreach ($products as $product) {
            $campaignProductMap[$product['product_code']] = [
                'campaign' => $campaign,
                'discount_rate' => floatval($product['discount_rate'])
            ];
        }
        
        apiLog("Kampanya: {$campaign['name']} - {$campaign['category_name']} (" . count($products) . " ürün)");
    }
    
    // ============================================
    // 6. KATEGORİ BAZLI GRUPLAMA
    // ============================================
    $categoryGroups = []; // [category_name => [items]]
    $otherProducts = [];
    
    foreach ($cartItems as $item) {
        $productCode = $item['code'] ?? '';
        $quantity = floatval($item['quantity'] ?? 0);
        $price = floatval($item['price'] ?? 0);
        
        if (!$productCode || $quantity <= 0) {
            continue;
        }
        
        $itemData = [
            'code' => $productCode,
            'quantity' => $quantity,
            'price' => $price
        ];
        
        if (isset($campaignProductMap[$productCode])) {
            // Bu ürün bir kampanyada var
            $campaignInfo = $campaignProductMap[$productCode];
            $category = $campaignInfo['campaign']['category_name'];
            
            if (!isset($categoryGroups[$category])) {
                $categoryGroups[$category] = [
                    'campaign' => $campaignInfo['campaign'],
                    'items' => []
                ];
            }
            
            $itemData['campaign_discount'] = $campaignInfo['discount_rate'];
            $categoryGroups[$category]['items'][] = $itemData;
        } else {
            // Bu ürün kampanyada yok
            $otherProducts[] = $itemData;
        }
    }
    
    apiLog("Kategori grupları: " . count($categoryGroups) . ", Diğer ürünler: " . count($otherProducts));
    
    // ============================================
    // 7. HER KATEGORİ İÇİN KAMPANYA UYGULA
    // ============================================
    foreach ($categoryGroups as $categoryName => $group) {
        $campaign = $group['campaign'];
        $items = $group['items'];
        
        // Kategori toplamları (SADECE bu kategorideki ürünler)
        $totalQty = 0;
        $totalAmount = 0;
        
        foreach ($items as $item) {
            $totalQty += $item['quantity'];
            $totalAmount += ($item['price'] * $item['quantity']);
        }
        
        apiLog("━━━ {$categoryName} ━━━");
        apiLog("  Ürün sayısı: " . count($items) . ", Toplam miktar: {$totalQty}, Toplam tutar: " . number_format($totalAmount, 2) . "€");
        
        // Minimum miktar kontrolü
        $minQty = intval($campaign['min_quantity']);
        $conditionsMet = ($totalQty >= $minQty);
        
        apiLog("  Min miktar: {$minQty} / Durum: " . ($conditionsMet ? '✓ SAĞLANDI' : '✗ SAĞLANMADI'));
        
        if (!$conditionsMet) {
            // FALLBACK
            $fallbackRate = $isCashPayment ? floatval($campaign['fallback_discount_cash']) : floatval($campaign['fallback_discount_credit']);
            apiLog("  → FALLBACK uygulanıyor: {$fallbackRate}%");
            
            foreach ($items as $item) {
                $response['discounts'][$item['code']] = [
                    'rates' => [$fallbackRate],
                    'display' => number_format($fallbackRate, 2, ',', ''),
                    'total' => $fallbackRate,
                    'campaigns' => ["{$categoryName} (Fallback)"]
                ];
            }
            continue;
        }
        
        // KOŞUL SAĞLANDI - Kademeli iskonto kurallarını çek
        $stmtRules = $conn->prepare("SELECT * FROM custom_campaign_rules WHERE campaign_id = ? ORDER BY priority ASC");
        $stmtRules->bind_param("i", $campaign['id']);
        $stmtRules->execute();
        $rules = $stmtRules->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmtRules->close();
        
        apiLog("  Kademeli iskonto kuralı sayısı: " . count($rules));
        
        // Her ürün için cascade iskonto hesapla
        foreach ($items as $item) {
            $cascadeRates = [];
            $cascadeCampaigns = [];
            
            // 1. Ürün özel iskontosu
            $baseDiscount = $item['campaign_discount'];
            $cascadeRates[] = $baseDiscount;
            $cascadeCampaigns[] = $categoryName;
            
            // 2. Kademeli iskonto kuralları
            foreach ($rules as $rule) {
                $ruleApplies = false;
                $ruleName = $rule['rule_name'];
                
                switch ($rule['rule_type']) {
                    case 'amount_based':
                        $threshold = floatval($rule['condition_value']);
                        $ruleApplies = ($totalAmount >= $threshold);
                        if ($ruleApplies) {
                            apiLog("    ✓ {$ruleName} sağlandı ({$totalAmount}€ >= {$threshold}€)");
                        }
                        break;
                    
                    case 'quantity_based':
                        $threshold = floatval($rule['condition_value']);
                        $ruleApplies = ($totalQty >= $threshold);
                        if ($ruleApplies) {
                            apiLog("    ✓ {$ruleName} sağlandı ({$totalQty} adet >= {$threshold} adet)");
                        }
                        break;
                    
                    case 'payment_based':
                        $ruleApplies = $isCashPayment;
                        if ($ruleApplies) {
                            apiLog("    ✓ {$ruleName} sağlandı");
                        }
                        break;
                }
                
                if ($ruleApplies) {
                    $cascadeRates[] = floatval($rule['discount_rate']);
                    $cascadeCampaigns[] = $ruleName;
                }
            }
            
            // Cascade format oluştur
            $displayFormat = implode('-', array_map(function($r) { 
                return number_format($r, 2, ',', ''); 
            }, $cascadeRates));
            
            // Kümülatif toplam iskonto hesapla
            $cumulativePrice = 100;
            foreach ($cascadeRates as $rate) {
                $cumulativePrice = $cumulativePrice * (1 - ($rate / 100));
            }
            $totalDiscount = 100 - $cumulativePrice;
            
            $response['discounts'][$item['code']] = [
                'rates' => $cascadeRates,
                'display' => $displayFormat,
                'total' => round($totalDiscount, 2),
                'campaigns' => $cascadeCampaigns
            ];
            
            apiLog("  → {$item['code']}: {$displayFormat} (Toplam: " . round($totalDiscount, 2) . "%)");
        }
        
        $response['applied_campaigns'][] = $campaign['name'];
    }
    
    // ============================================
    // 8. DİĞER ÜRÜNLERE FALLBACK UYGULA
    // ============================================
    if (!empty($otherProducts)) {
        $fallbackRate = $isCashPayment ? 50.5 : 45.0;
        apiLog("━━━ DİĞER ÜRÜNLER ━━━");
        apiLog("  Ürün sayısı: " . count($otherProducts) . ", Fallback: {$fallbackRate}%");
        
        foreach ($otherProducts as $item) {
            $response['discounts'][$item['code']] = [
                'rates' => [$fallbackRate],
                'display' => number_format($fallbackRate, 2, ',', ''),
                'total' => $fallbackRate,
                'campaigns' => ['Genel Fallback']
            ];
        }
    }
    
    $response['success'] = true;
    $response['message'] = count($response['discounts']) . " ürüne kampanya uygulandı";
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    apiLog("HATA: " . $e->getMessage());
}

// Debug log
$logEntry = "=== " . date('Y-m-d H:i:s') . " ===\n";
$logEntry .= "INPUT: " . json_encode($input, JSON_UNESCAPED_UNICODE) . "\n";
$logEntry .= "RESPONSE: " . json_encode($response, JSON_UNESCAPED_UNICODE) . "\n\n";
@file_put_contents(__DIR__ . '/campaign_debug.log', $logEntry, FILE_APPEND);

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
