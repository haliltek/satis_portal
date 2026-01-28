<?php
// api/kampanya/debug_cart_conditions.php
// CLI veya Browser üzerinden test etmek için debug scripti

header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/../../include/vt.php';

// Test verisi (Harici POST yoksa bunu kullan)
$mockCart = [
    ['code' => '02400', 'quantity' => 150], // ÇOK YOLLU VANA (Min 100 Adet)
];

// Eğer POST ile cart gelirse onu kullan
if (isset($_POST['cart'])) {
    $cart = json_decode($_POST['cart'], true);
} else {
    $cart = $mockCart;
    echo "--- MOCK DATA KULLANILIYOR ---\n";
}

echo "DEBUG BAŞLIYOR...\n";
echo "Sepet: " . json_encode($cart) . "\n\n";

try {
    $pdo = new PDO(
        "mysql:host={$sql_details['host']};dbname={$sql_details['db']};charset=utf8mb4",
        $sql_details['user'],
        $sql_details['pass'],
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );

    $productCodes = array_column($cart, 'code');
    if (empty($productCodes)) die("Sepet boş.");

    // 1. Ürünlerin Kategorilerini ve Fiyatlarını Çek
    $placeholders = implode(',', array_fill(0, count($productCodes), '?'));
    
    // Hem kampanya tablosundan hem ana ürün tablosundan bilgi alalım
    echo "1. Ürün Bilgileri Kontrol Ediliyor...\n";
    
    $stmt = $pdo->prepare("
        SELECT k.stok_kodu, k.kategori, k.ozel_fiyat, u.stokadi, u.fiyat as liste_fiyati
        FROM kampanya_ozel_fiyatlar k
        LEFT JOIN urunler u ON (u.stokkodu = k.stok_kodu OR u.stokkodu = REPLACE(k.stok_kodu, ' ', ''))
        WHERE k.stok_kodu IN ($placeholders)
    ");
    $stmt->execute($productCodes);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $foundCodes = [];
    $categoryGroups = [];

    foreach ($products as $p) {
        $foundCodes[] = $p['stok_kodu'];
        $cat = mb_strtoupper(trim($p['kategori'])); // Normalize
        
        echo "  [BULDUM] Kod: {$p['stok_kodu']} | Kat: {$p['kategori']} -> Norm: {$cat} | ÖzelFiyat: {$p['ozel_fiyat']} | ListeFiyat: {$p['liste_fiyati']}\n";

        if (!isset($categoryGroups[$cat])) {
            $categoryGroups[$cat] = ['qty' => 0, 'special_total' => 0, 'list_total' => 0, 'items' => []];
        }

        // Sepetteki adedi bul
        $qty = 0;
        foreach($cart as $c) if($c['code'] == $p['stok_kodu']) $qty = $c['quantity'];

        $categoryGroups[$cat]['qty'] += $qty;
        $categoryGroups[$cat]['special_total'] += ($p['ozel_fiyat'] * $qty);
        $categoryGroups[$cat]['list_total'] += ($p['liste_fiyati'] * $qty);
        $categoryGroups[$cat]['items'][] = $p['stok_kodu'];
    }

    echo "\n--- KATEGORİ TOPLAMLARI ---\n";
    foreach($categoryGroups as $cat => $data) {
        echo "Kategori: [$cat]\n";
        echo "  Toplam Adet: {$data['qty']}\n";
        echo "  Toplam Tutar (Özel Fiyat): " . number_format($data['special_total'], 2) . " EUR\n";
        echo "  Toplam Tutar (Liste Fiyatı): " . number_format($data['list_total'], 2) . " EUR\n";
    }

    // 2. Kampanya Kurallarını Kontrol Et
    echo "\n2. Kampanya Kuralları (custom_campaigns)...\n";
    $stmt = $pdo->query("SELECT * FROM custom_campaigns WHERE is_active = 1");
    $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "--- MEVCUT AKTİF KURALLAR ---\n";
    foreach($rules as $r) {
        echo "Rule ID: {$r['id']} | Cat: [{$r['category_name']}] | MinQty: {$r['min_quantity']} | MinAmount: {$r['min_total_amount']}\n";
    }
    echo "---------------------------\n";

    foreach($rules as $rule) {
        $ruleCat = mb_strtoupper(trim($rule['category_name']));
        echo "  Kural: [{$rule['category_name']}] -> Norm: [$ruleCat] | MinQty: {$rule['min_quantity']} | MinAmount: {$rule['min_total_amount']}\n";
        
        if (isset($categoryGroups[$ruleCat])) {
            $group = $categoryGroups[$ruleCat];
            $qtyCheck = $group['qty'] >= $rule['min_quantity'];
            
            // HANGİ TUTAR KULLANILIYOR?
            // Şu anki kod (check_conditions.php): ozel_fiyat kullanıyor
            $amountCheckSpecial = $group['special_total'] >= $rule['min_total_amount'];
            $amountCheckList = $group['list_total'] >= $rule['min_total_amount'];

            echo "    -> EŞLEŞME: EVET\n";
            echo "    -> Miktar Koşulu ({$rule['min_quantity']}): " . ($qtyCheck ? "GEÇTİ" : "KALDI") . " ({$group['qty']})\n";
            echo "    -> Tutar Koşulu (ÖZEL FİYAT İLE) ({$rule['min_total_amount']}): " . ($amountCheckSpecial ? "GEÇTİ" : "KALDI") . " ({$group['special_total']})\n";
            echo "    -> Tutar Koşulu (LİSTE FİYATI İLE) ({$rule['min_total_amount']}): " . ($amountCheckList ? "GEÇTİ" : "KALDI") . " ({$group['list_total']})\n";
        } else {
            // Fuzzy match denemesi
            echo "    -> EŞLEŞME: YOK (Tam eşleşme bulunamadı)\n";
        }
    }

} catch (Exception $e) {
    echo "HATA: " . $e->getMessage();
}
