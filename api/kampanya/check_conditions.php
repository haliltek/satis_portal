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
    
    // kampanya_ozel_fiyatlar tablosundan ürünleri çek
    $placeholders = implode(',', array_fill(0, count($productCodes), '?'));
    $stmt = $pdo->prepare("
        SELECT stok_kodu, kategori, ozel_fiyat 
        FROM kampanya_ozel_fiyatlar 
        WHERE stok_kodu IN ($placeholders)
    ");
    $stmt->execute($productCodes);
    $campaignProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Kategorilere göre grupla
    foreach ($campaignProducts as $product) {
        $kategori = $product['kategori'] ?: 'Genel';
        if (!isset($categoryGroups[$kategori])) {
            $categoryGroups[$kategori] = [
                'products' => [],
                'total_quantity' => 0
            ];
        }
        
        // Sepette bu ürünün miktarını bul
        foreach ($cart as $item) {
            if ($item['code'] === $product['stok_kodu']) {
                $qty = intval($item['quantity'] ?? 1);
                $categoryGroups[$kategori]['products'][] = $product['stok_kodu'];
                $categoryGroups[$kategori]['total_quantity'] += $qty;
                break;
            }
        }
    }
    
    // Kampanyaları çek (Veritabanından)
    $stmt = $pdo->prepare("SELECT * FROM custom_campaigns WHERE active = 1 AND customer_type = 'ana_bayi'");
    $stmt->execute();
    $dbCampaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Kategoriye göre map oluştur
    $campaignRules = [];
    foreach ($dbCampaigns as $dbCamp) {
        $campaignRules[$dbCamp['category_name']] = $dbCamp;
    }

    $campaigns = [];
    foreach ($categoryGroups as $kategori => $groupData) {
        // Bu kategori için veritabanında kampanya var mı?
        if (isset($campaignRules[$kategori])) {
            $rule = $campaignRules[$kategori];
            $minQty = intval($rule['min_quantity']);
            $minAmount = floatval($rule['min_amount']); // Tutar bazlı
            
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

            // Koşul kontrolü
            $qtyCondition = ($minQty > 0) ? ($groupData['total_quantity'] >= $minQty) : true;
            $amountCondition = ($minAmount > 0) ? ($catTotalAmount >= $minAmount) : true;
            $isEligible = ($qtyCondition && $amountCondition);
            
            // Eğer ikisi de 0 ise (Genel bir kampanya değilse atla)
            if ($minQty == 0 && $minAmount == 0) continue;

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

                $campaigns[] = [
                    'name' => "{$kategori} Özel Fiyat",
                    'condition' => $condText . " " . $currentStatus,
                    'products' => $groupData['products'],
                    'category' => $kategori,
                    'quantity' => $groupData['total_quantity'],
                    // Meta veriler (JS tarafı için)
                    'campaign_meta' => [
                         'min_amount' => $minAmount,
                         'condition_text' => $condText
                    ]
                ];
            }
        } else {
             // Veritabanında kural yoksa varsayılan (Legacy - 10 Adet)
             if ($groupData['total_quantity'] >= 10) {
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
    
    // Ana Bayi mi?
    $isMainDealer = (strpos($customerName, 'ERTEK') !== false || 
                     strpos($customerName, 'Ana Bayi') !== false);
    
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
