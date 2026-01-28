<?php
// api/kampanya/check_conditions.php
// Sipariş sepetindeki ürünlerin özel fiyat kampanyasına uygunluğunu kontrol eder

header('Content-Type: application/json; charset=utf-8');

session_start();
require_once __DIR__ . '/../../include/vt.php';

try {
    $pdo = new PDO(
        "mysql:host={$sql_details['host']};dbname={$sql_details['db']};charset=utf8mb4",
        $sql_details['user'],
        $sql_details['pass'],
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
    
    // POST'tan sepet verisini al (JSON string)
    $cartJson = $_POST['cart'] ?? '[]';
    $cart = json_decode($cartJson, true);
    
    if (!is_array($cart) || empty($cart)) {
        echo json_encode(['eligible' => false, 'campaigns' => []]);
        exit;
    }
    
    // Sepetteki ürünlerin kategorilerine göre grupla
    $categoryGroups = [];
    $productCodes = array_column($cart, 'code');
    
    if (empty($productCodes)) {
        echo json_encode(['eligible' => false, 'campaigns' => []]);
        exit;
    }
    
    // 1. ÖNCE KAMPANYA KURALLARINI ÇEK (Mapping için gerekli)
    // Uzun isimleri önce kontrol etmek için ORDER BY LENGTH DESC ekliyoruz
    $stmtRules = $pdo->prepare("SELECT * FROM custom_campaigns WHERE is_active = 1 ORDER BY LENGTH(category_name) DESC");
    $stmtRules->execute();
    $dbCampaigns = $stmtRules->fetchAll(PDO::FETCH_ASSOC);
    
    $campaignRules = [];
    foreach ($dbCampaigns as $dbCamp) {
        $campaignRules[$dbCamp['category_name']] = $dbCamp;
    }

    // 2. Ürünleri Çek
    $placeholders = implode(',', array_fill(0, count($productCodes), '?'));
    $stmt = $pdo->prepare("
        SELECT stok_kodu, kategori, ozel_fiyat 
        FROM kampanya_ozel_fiyatlar 
        WHERE stok_kodu IN ($placeholders)
    ");
    $stmt->execute($productCodes);
    $campaignProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. Kategorilere göre grupla (Dinamik Mapping ile)
    foreach ($campaignProducts as $product) {
        $prodCat = $product['kategori'] ?: 'Genel';
        $mappedCat = $prodCat; // Varsayılan: Kendi kategorisi

        // Eğer bu kategori için birebir kural yoksa, partial match ara
        if (!isset($campaignRules[$prodCat])) {
            foreach ($campaignRules as $ruleCat => $ruleVal) {
                // mb_stripos ile büyük/küçük harf duyarsız ve güvenli kontrol
                if (mb_stripos($prodCat, $ruleCat) !== false) {
                    $mappedCat = $ruleCat;
                    break; // İlk (ve en uzun) eşleşeni al
                }
            }
        }
        
        if (!isset($categoryGroups[$mappedCat])) {
            $categoryGroups[$mappedCat] = [
                'products' => [],
                'total_quantity' => 0
            ];
        }
        
        // Sepette bu ürünün miktarını bul
        foreach ($cart as $item) {
            if ($item['code'] === $product['stok_kodu']) {
                $qty = intval($item['quantity'] ?? 1);
                $categoryGroups[$mappedCat]['products'][] = $product['stok_kodu'];
                $categoryGroups[$mappedCat]['total_quantity'] += $qty;
                break;
            }
        }
    }

    $campaigns = [];
    foreach ($categoryGroups as $kategori => $groupData) {
        if (isset($campaignRules[$kategori])) {
            $rule = $campaignRules[$kategori];
            $minQty = intval($rule['min_quantity']);
            
            // Tutar Kontrolü: min_amount (öncelikli) YOKSA min_total_amount (veritabanındaki 1500)
            $minAmount = floatval($rule['min_amount']);
            if ($minAmount <= 0 && isset($rule['min_total_amount'])) {
                $minAmount = floatval($rule['min_total_amount']);
            }
            
            // Tutar hesapla (Bu kategori için)
            $catTotalAmount = 0;
            // Ürün fiyatlarını çekmemiz lazım (Döngü içinde SQL kötü ama hızlı çözüm)
             if (!empty($groupData['products'])) {
                $placeholders = implode(',', array_fill(0, count($groupData['products']), '?'));
                $stmtPrice = $pdo->prepare("SELECT stok_kodu, ozel_fiyat FROM kampanya_ozel_fiyatlar WHERE stok_kodu IN ($placeholders)");
                $stmtPrice->execute($groupData['products']);
                $catPrices = $stmtPrice->fetchAll(PDO::FETCH_KEY_PAIR);
                
                foreach ($cart as $item) {
                     if (in_array($item['code'], $groupData['products']) && isset($catPrices[$item['code']])) {
                         $qty = floatval($item['quantity'] ?? 1);
                         $catTotalAmount += (floatval($catPrices[$item['code']]) * $qty);
                     }
                }
             }

            // Koşul kontrolü (ESNEK MODEL: Tanımlı şartlardan HERHANGİ BİRİ sağlanırsa yeterli)
            // Kullanıcı "Min Sipariş: 10" şartını sağladığında, "Min Tutar: 10.000" şartını sağlamasa bile kampanya geçerli olmalı.
            $isEligible = false;
            $hasRule = false;
            
            if ($minQty > 0) {
                $hasRule = true;
                if ($groupData['total_quantity'] >= $minQty) $isEligible = true;
            }
            
            if ($minAmount > 0) {
                $hasRule = true;
                if ($catTotalAmount >= $minAmount) $isEligible = true;
            }
            
            // Eğer hiçbir kural tanımlı değilse (0 ve 0), kampanya pasif sayılır
            if (!$hasRule) continue;

            if ($isEligible) {
                // Dinamik metin
                $condText = "";
                if ($minAmount > 0) $condText .= "Min " . number_format($minAmount, 0, ',', '.') . " € tutar";
                if ($minAmount > 0 && $minQty > 0) $condText .= " ve ";
                if ($minQty > 0) $condText .= "Min $minQty adet";
                
                $currentStatus = "(";
                if ($minAmount > 0) $currentStatus .= number_format($catTotalAmount, 2, ',', '.') . " €";
                if ($minAmount > 0 && $minQty > 0) $currentStatus .= " / ";
                if ($minQty > 0) $currentStatus .= "{$groupData['total_quantity']} adet";
                $currentStatus .= ")";

                // Ek İskonto Uygunluğu (Sadece Tutar Şartına Bağlı)
                // Kullanıcı Miktar şartı ile özel fiyata hak kazanmış olabilir (OR mantığı)
                // Ancak Ek İskonto (+%5) genellikle sadece Tutar/Ciro hedefine bağlıdır.
                $extraEligible = false;
                if ($minAmount > 0 && $catTotalAmount >= $minAmount) {
                     $extraEligible = true;
                }

                $campaigns[] = [
                    'name' => "{$rule['category_name']} Özel Fiyat",
                    'condition' => $condText . " " . $currentStatus,
                    'products' => $groupData['products'],
                    'category' => $groupData['products'][0] ?? '', // ilk ürün kodu referans
                    'discount_rate' => 0, // Özel fiyat (listeden)
                    'is_extra_discount' => false,
                    'is_cash_discount' => false,
                    'extra_eligible' => $extraEligible, // FRONTEND İÇİN YENİ FLAG
                    // Meta veriler (JS tarafı için)
                    'campaign_meta' => [
                         'min_amount' => $minAmount,
                         'min_quantity' => $minQty,
                         'min_purchase_amount' => floatval($rule['min_purchase_amount'] ?? 0),
                         'condition_text' => $condText
                    ]
                ];
            }
        } else {
             // Veritabanında kural yoksa varsayılan (Legacy - 10 Adet)
             if ($groupData['total_quantity'] >= 10 && $kategori === 'Genel') {
                $campaigns[] = [
                    'name' => "{$kategori} Özel Fiyat",
                    'condition' => "Min 10 adet alım ({$groupData['total_quantity']} adet)",
                    'products' => $groupData['products'],
                    'category' => $kategori,
                    'quantity' => $groupData['total_quantity']
                ];
            }
        }
    }
    
    // Ana Bayi Ek İskonto Kontrolü
    $customerId = $_POST['customer_id'] ?? 0;
    $customerName = $_POST['customer_name'] ?? '';
    
    // DEBUG: Müşteri ve ödeme bilgilerini logla
    error_log("DEBUG - Customer: $customerName, Payment: " . ($_POST['payment_method'] ?? 'EMPTY'));
    
    // Ana Bayi mi?
    $isMainDealer = (stripos($customerName, 'ERTEK') !== false || 
                     stripos($customerName, 'Ana Bayi') !== false);
    
    error_log("DEBUG - Is Main Dealer: " . ($isMainDealer ? 'YES' : 'NO'));
    
    if ($isMainDealer && count($campaigns) > 0) {
        // Tüm kampanyalı ürünleri topla
        $allCampaignProducts = [];
        foreach ($campaigns as $camp) {
            $allCampaignProducts = array_merge($allCampaignProducts, $camp['products']);
        }
        $allCampaignProducts = array_unique($allCampaignProducts);
        
        // Özel fiyatlı ürünlerin toplam EUR tutarını hesapla
        $totalEuroValue = 0;
        
        // Özel fiyatları çek
        if (!empty($allCampaignProducts)) {
            $placeholders = implode(',', array_fill(0, count($allCampaignProducts), '?'));
            $stmt = $pdo->prepare("
                SELECT stok_kodu, ozel_fiyat 
                FROM kampanya_ozel_fiyatlar 
                WHERE stok_kodu IN ($placeholders)
            ");
            $stmt->execute($allCampaignProducts);
            $specialPrices = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // Sepetten miktarları al ve tutar hesapla
            foreach ($cart as $item) {
                $code = $item['code'];
                if (isset($specialPrices[$code])) {
                    $qty = floatval($item['quantity'] ?? 1);
                    $price = floatval($specialPrices[$code]);
                    $totalEuroValue += ($price * $qty);
                }
            }
        }
        
        // 5000 EUR kontrolü
        if ($totalEuroValue >= 5000) {
            $campaigns[] = [
                'name' => 'Anabayi Ek İskonto',
                'condition' => "Min 5.000 EUR alım (" . number_format($totalEuroValue, 2, ',', '.') . " EUR)",
                'products' => $allCampaignProducts,
                'category' => 'Tüm Kategoriler',
                'quantity' => count($allCampaignProducts),
                'discount_rate' => 5,
                'is_extra_discount' => true,
                'total_value_eur' => $totalEuroValue
            ];
        }
        
        // Peşin Ödeme İskontosu Kontrolü (%10)
        $paymentMethod = $_POST['payment_method'] ?? '';
        $isCashPayment = (strpos($paymentMethod, 'PEŞİN') !== false || 
                         strpos($paymentMethod, 'PEŞIN') !== false ||
                         strpos($paymentMethod, 'Peşin') !== false);
        
        if ($isCashPayment && !empty($allCampaignProducts)) {
            $campaigns[] = [
                'name' => 'Ana Bayi Peşin İskontosu',
                'condition' => "Peşin ödeme seçildi",
                'products' => $allCampaignProducts,
                'category' => 'Tüm Kategoriler',
                'quantity' => count($allCampaignProducts),
                'discount_rate' => 10,
                'is_extra_discount' => true,
                'is_cash_discount' => true,
                'total_value_eur' => $totalEuroValue
            ];
        }
    }
    
    echo json_encode([
        'eligible' => !empty($campaigns),
        'campaigns' => $campaigns
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'eligible' => false,
        'campaigns' => [],
        'error' => $e->getMessage()
    ]);
}
?>
