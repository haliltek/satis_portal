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
            
            // Koşul kontrolü KALDIRILDI - Her ürün için kampanya göster
            // Ürün detaylarını çek (Liste fiyatı, Özel fiyat ve Ürün adı)
            $productDetails = [];
            $catTotalAmount = 0;
            
            if (!empty($groupData['products'])) {
                $placeholders = implode(',', array_fill(0, count($groupData['products']), '?'));
                $stmtPrice = $pdo->prepare("
                    SELECT kof.stok_kodu, kof.yurtici_fiyat, kof.ozel_fiyat, u.stokadi 
                    FROM kampanya_ozel_fiyatlar kof 
                    LEFT JOIN urunler u ON kof.stok_kodu = u.stokkodu 
                    WHERE kof.stok_kodu IN ($placeholders)
                ");
                $stmtPrice->execute($groupData['products']);
                $productPrices = $stmtPrice->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($productPrices as $prod) {
                    $productDetails[$prod['stok_kodu']] = [
                        'list_price' => floatval($prod['yurtici_fiyat']),
                        'special_price' => floatval($prod['ozel_fiyat']),
                        'product_name' => $prod['stokadi'] ?: '-'
                    ];
                }
                
                foreach ($cart as $item) {
                     if (in_array($item['code'], $groupData['products']) && isset($productDetails[$item['code']])) {
                         $qty = floatval($item['quantity'] ?? 1);
                         $catTotalAmount += ($productDetails[$item['code']]['special_price'] * $qty);
                     }
                }
            }

            // Dinamik metin (bilgi amaçlı)
            $minQty = intval($rule['min_quantity']);
            $minAmount = floatval($rule['min_amount']);
            if ($minAmount <= 0 && isset($rule['min_total_amount'])) {
                $minAmount = floatval($rule['min_total_amount']);
            }
            
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
                'name' => "{$rule['category_name']} Özel Fiyat",
                'condition' => $condText . " " . $currentStatus,
                'products' => $groupData['products'],
                'product_details' => $productDetails, // Fiyat detayları eklendi
                'category' => $rule['category_name'],
                'quantity' => $groupData['total_quantity'],
                // Meta veriler (JS tarafı için)
                'campaign_meta' => [
                     'min_amount' => $minAmount,
                     'min_quantity' => $minQty,
                     'min_purchase_amount' => floatval($rule['min_purchase_amount'] ?? 0),
                     'condition_text' => $condText
                ]
            ];
        } else {
             // Veritabanında kural yoksa varsayılan
             if ($kategori === 'Genel') {
                $campaigns[] = [
                    'name' => "{$kategori} Özel Fiyat",
                    'condition' => "Genel kampanya ({$groupData['total_quantity']} adet)",
                    'products' => $groupData['products'],
                    'category' => $kategori,
                    'quantity' => $groupData['total_quantity']
                ];
            }
        }
    }
    
    
    // Ana Bayi ve Peşin Ödeme kontrolleri kaldırıldı
    
    
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
