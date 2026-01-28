<?php
// api/get_product_price.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../fonk.php';

global $db, $logoService, $config;

// Parametreleri al
$code = $_GET['code'] ?? '';
$customerCode = $_GET['customer_code'] ?? '';
$isExport = isset($_GET['is_export']) && $_GET['is_export'] == '1';

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Stok kodu gerekli']);
    exit;
}

try {
    // Önce yerel veritabanından ürünü ara
    $stmt = $db->prepare("SELECT stokkodu, stokadi, fiyat, doviz, olcubirimi, LOGICALREF FROM urunler WHERE stokkodu = ? LIMIT 1");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();

    if (!$product) {
        // Yerel DB'de yoksa Logo'dan çek
        $firmNr = (int)($config['firmNr'] ?? 997);
        $logoProduct = $logoService->getItemByCode($firmNr, $code);
        
        if (!$logoProduct) {
            echo json_encode(['success' => false, 'message' => 'Ürün bulunamadı']);
            exit;
        }
        
        $product = [
            'stokkodu' => $logoProduct['CODE'] ?? $code,
            'stokadi' => $logoProduct['NAME'] ?? '',
            'fiyat' => 0,
            'doviz' => 'TL',
            'olcubirimi' => $logoProduct['UNITSETCODE'] ?? 'AD',
            'LOGICALREF' => $logoProduct['LOGICALREF'] ?? 0
        ];
    }

    // Fiyatı belirle - Export veya Yurtiçi
    $price = 0;
    $currency = 'TL';
    
    if ($isExport) {
        // Export fiyatı - Logo'dan PRCLIST 2'den al
        $firmNr = (int)($config['firmNr'] ?? 997);
        $logicalRef = (int)($product['LOGICALREF'] ?? 0);
        
        if ($logicalRef > 0) {
            $priceData = $logoService->getItemPrice($firmNr, $logicalRef, 2); // PRCLIST 2 = Export
            if ($priceData && isset($priceData['PRICE'])) {
                $price = (float)$priceData['PRICE'];
                $currency = $priceData['CURRENCY'] ?? 'EUR';
            }
        }
        
        // Eğer Logo'dan fiyat alınamazsa, yerel DB'deki fiyatı kullan
        if ($price <= 0) {
            $price = (float)($product['fiyat'] ?? 0);
            $currency = $product['doviz'] ?? 'EUR';
        }
    } else {
        // Yurtiçi fiyatı - PRCLIST 1
        $firmNr = (int)($config['firmNr'] ?? 997);
        $logicalRef = (int)($product['LOGICALREF'] ?? 0);
        
        if ($logicalRef > 0) {
            $priceData = $logoService->getItemPrice($firmNr, $logicalRef, 1); // PRCLIST 1 = Yurtiçi
            if ($priceData && isset($priceData['PRICE'])) {
                $price = (float)$priceData['PRICE'];
                $currency = 'TL';
            }
        }
        
        // Eğer Logo'dan fiyat alınamazsa, yerel DB'deki fiyatı kullan
        if ($price <= 0) {
            $price = (float)($product['fiyat'] ?? 0);
            $currency = 'TL';
        }
    }

    echo json_encode([
        'success' => true,
        'product' => [
            'code' => $product['stokkodu'],
            'name' => $product['stokadi'],
            'unit' => $product['olcubirimi'],
            'price' => $price,
            'currency' => $currency
        ]
    ]);

} catch (Exception $e) {
    error_log("get_product_price.php Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Fiyat sorgulanırken hata oluştu']);
}
