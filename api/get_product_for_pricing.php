<?php
// api/get_product_for_pricing.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../fonk.php';

global $db;

$code = $_GET['code'] ?? '';
$isExport = isset($_GET['is_export']) && $_GET['is_export'] == '1';

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Stok kodu gerekli']);
    exit;
}

try {
    // Ürünü veritabanından çek
    $stmt = $db->prepare("SELECT stokkodu, stokadi, olcubirimi, fiyat, export_fiyat, doviz, maliyet FROM urunler WHERE stokkodu = ? LIMIT 1");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Ürün bulunamadı']);
        exit;
    }

    // İhracat müşterisi ise export_fiyat kullan
    if ($isExport) {
        $price = (float)($product['export_fiyat'] ?? 0);
        $currency = 'EUR'; // Export fiyatları genelde EUR
    } else {
        $price = (float)($product['fiyat'] ?? 0);
        $currency = $product['doviz'] ?? 'TL';
    }

    // Maliyet bilgisi
    $cost = (float)($product['maliyet'] ?? 0);

    echo json_encode([
        'success' => true,
        'product' => [
            'code' => $product['stokkodu'],
            'name' => $product['stokadi'],
            'unit' => $product['olcubirimi'],
            'price' => $price,
            'cost' => $cost,
            'currency' => $currency
        ]
    ]);

} catch (Exception $e) {
    error_log("get_product_for_pricing.php Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ürün sorgulanırken hata oluştu']);
}
