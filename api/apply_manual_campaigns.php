<?php
/**
 * API: Manuel Kampanya Uygulama
 * Input: {items: [{code, quantity}], customerCode, isCashPayment}
 * Output: {discounts: {productCode: {rates, display, total, campaigns}}}
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
    $response['logs'][] = $msg;
}

try {
    // Input
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);
    
    if (!$input) {
        throw new Exception("Geçersiz JSON verisi");
    }
    
    $cartItems = $input['items'] ?? [];
    $customerCode = $input['customerCode'] ?? '';
    $isCashPayment = $input['isCashPayment'] ?? false;
    
    if (empty($customerCode) || empty($cartItems)) {
        throw new Exception("Eksik parametreler");
    }
    
    apiLog("İşlem başladı - Cari: $customerCode, Ürün sayısı: " . count($cartItems));
    
    // DB bağlantısı
    $config = require __DIR__ . '/../config/config.php';
    $db = $config['db'];
    $conn = new mysqli($db['host'], $db['user'], $db['pass'], $db['name'], $db['port']);
    // Veritabanı tablosu latin1 (veya varsayılan) olduğu halde UTF-8 verisi içerdiği için
    // utf8 bağlantısında "double encoding" oluşuyor (örn: Ä°).
    // latin1 bağlantısı ile ham baytları (UTF-8) çekip JSON olarak göndereceğiz.
    $conn->set_charset("latin1");
    
    // Müşteri ID'sini belirle
    // customerCode burada sirket ID'si olabilir veya cari kodu
    // Şimdilik basitleştirelim: kampanyalar customer_type='specific' ve customer_code ile eşleşir
    $isMainDealer = true; // Şimdilik tüm müşterilere izin ver, kampanya kendi kontrol edecek
    
    apiLog("Müşteri: $customerCode");
    
    // Uygun kampanyaları bul
    $sql = "SELECT * FROM custom_campaigns 
            WHERE active = 1 
            AND (
                customer_type = 'tum' 
                OR (customer_type = 'specific' AND customer_code = ?)
            )
            ORDER BY priority DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $customerCode);
    $stmt->execute();
    $campaigns = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    apiLog("Bulunan kampanya sayısı: " . count($campaigns));
    
    foreach ($campaigns as $campaign) {
        apiLog("Kampanya: " . $campaign['name']);
        
        // Kampanya ürünlerini çek
        $stmtProducts = $conn->prepare("SELECT * FROM custom_campaign_products WHERE campaign_id = ?");
        $stmtProducts->bind_param("i", $campaign['id']);
        $stmtProducts->execute();
        $campaignProducts = $stmtProducts->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Eşleşen ürünleri bul
        $matchedItems = [];
        $totalQuantity = 0;
        $totalAmount = 0;
        
        foreach ($cartItems as $item) {
            $productCode = $item['code'] ?? '';
            $quantity = floatval($item['quantity'] ?? 0);
            
            // Bu ürün kampanyada var mı?
            $campaignProduct = null;
            foreach ($campaignProducts as $cp) {
                if ($cp['product_code'] === $productCode) {
                    $campaignProduct = $cp;
                    break;
                }
            }
            
            if ($campaignProduct) {
                $matchedItems[] = [
                    'code' => $productCode,
                    'quantity' => $quantity,
                    'discount_rate' => $campaignProduct['discount_rate']
                ];
                $totalQuantity += $quantity;
                
                // Tutar hesapla (basit: liste fiyatı * miktar - burası geliştirilebilir)
                $price = floatval($item['price'] ?? 100);  // Varsayılan fiyat
                $totalAmount += ($price * $quantity);
            }
        }
        
        if (empty($matchedItems)) {
            apiLog("→ Eşleşen ürün yok");
            continue;
        }
        
        apiLog("→ Eşleşen ürün sayısı: " . count($matchedItems));
        apiLog("→ Toplam miktar: $totalQuantity");
        apiLog("→ Toplam tutar: $totalAmount €");
        
        // Minimum miktar VE tutar kontrolü
        $conditionsMet = true;
        
        if ($campaign['min_quantity'] > 0 && $totalQuantity < $campaign['min_quantity']) {
            $conditionsMet = false;
            apiLog("→ Min miktar sağlanmadı (" . $campaign['min_quantity'] . ")");
        }
        
        if ($campaign['min_total_amount'] > 0 && $totalAmount < $campaign['min_total_amount']) {
            $conditionsMet = false;
            apiLog("→ Min tutar sağlanmadı (" . $campaign['min_total_amount'] . "€)");
        }
        
        if (!$conditionsMet) {
            // Fallback iskonto uygula
            // Özel kural: Ödeme planı 060 ise farklı iskontolar
            $paymentPlan = $input['paymentPlan'] ?? '';
            $fallbackRate = $campaign['fallback_discount'];
            
            if ($paymentPlan === '060' || strpos($paymentPlan, '060') !== false) {
                // Ödeme planı 060 ise
                if ($isCashPayment) {
                    $fallbackRate = 50.5; // Peşin
                } else {
                    $fallbackRate = 45.0; // Vadeli
                }
            }
            
            apiLog("→ Fallback iskonto uygulanıyor: %" . $fallbackRate . " (Ödeme planı: $paymentPlan)");
            
            foreach ($matchedItems as $item) {
                $response['discounts'][$item['code']] = [
                    'rates' => [floatval($fallbackRate)],
                    'display' => number_format($fallbackRate, 2, ',', ''),
                    'total' => floatval($fallbackRate),
                    'campaigns' => [$campaign['name'] . ' (Fallback)']
                ];
            }
            continue;
        }
        
        // Kademeli iskonto kurallarını çek
        $stmtRules = $conn->prepare("SELECT * FROM custom_campaign_rules WHERE campaign_id = ? ORDER BY priority ASC");
        $stmtRules->bind_param("i", $campaign['id']);
        $stmtRules->execute();
        $rules = $stmtRules->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Her ürün için cascade iskonto hesapla
        foreach ($matchedItems as $item) {
            $cascadeRates = [];
            $cascadeCampaigns = [];
            
            // 1. Ürüne özel iskonto
            $cascadeRates[] = floatval($item['discount_rate']);
            $cascadeCampaigns[] = $campaign['name'];
            
            // 2. Kademeli iskonto kuralları
            // ÖNEMLİ: Kuralları kontrol ederken SADECE BU KAMPANYANIN ürünlerinin toplamını kullan
            foreach ($rules as $rule) {
                $ruleApplies = false;
                
                switch ($rule['rule_type']) {
                    case 'amount_based':
                        // SADECE bu kampanyanın ürünlerinin tutarına bak
                        $ruleApplies = ($totalAmount >= floatval($rule['condition_value']));
                        break;
                    case 'payment_based':
                        $ruleApplies = $isCashPayment;
                        break;
                    case 'quantity_based':
                        // SADECE bu kampanyanın ürünlerinin miktarına bak
                        $ruleApplies = ($totalQuantity >= floatval($rule['condition_value']));
                        break;
                }
                
                if ($ruleApplies) {
                    $cascadeRates[] = floatval($rule['discount_rate']);
                    $cascadeCampaigns[] = $rule['rule_name'];
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
            
            apiLog("→ Ürün " . $item['code'] . ": " . $displayFormat . " (Toplam: %" . round($totalDiscount, 2) . ")");
        }
        
        $response['applied_campaigns'][] = $campaign['name'];
    }
    
    $response['success'] = true;
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    apiLog("HATA: " . $e->getMessage());
}

// Debug log
$logEntry = "--- " . date('Y-m-d H:i:s') . " ---\n";
$logEntry .= "INPUT: " . print_r($input, true) . "\n";
$logEntry .= "RESPONSE: " . print_r($response, true) . "\n";
file_put_contents(__DIR__ . '/debug_manual_campaign.txt', $logEntry, FILE_APPEND);

echo json_encode($response);
?>
