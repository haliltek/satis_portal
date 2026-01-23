<?php
$config = require_once 'config/config.php';

// Database connection
$db = new mysqli(
    $config['db']['host'],
    $config['db']['user'],
    $config['db']['pass'],
    $config['db']['name']
);

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$db->set_charset("utf8mb4");

echo "=== KAMPANYA SİSTEMİ DOĞRULAMA RAPORU ===\n\n";

// 1. Kampanya sayısı
$result = $db->query("SELECT COUNT(*) as cnt FROM custom_campaigns");
$campaignCount = $result->fetch_assoc()['cnt'];
echo "Toplam Kampanya Sayısı: $campaignCount\n\n";

// 2. Kategoriye göre ürün sayıları
echo "Kategorilere Göre Ürün Dağılımı:\n";
echo str_repeat('-', 70) . "\n";
printf("%-35s | %15s\n", "Kategori Adı", "Ürün Sayısı");
echo str_repeat('-', 70) . "\n";

$result = $db->query("
    SELECT 
        c.category_name, 
        COUNT(p.id) as product_count
    FROM custom_campaigns c
    LEFT JOIN custom_campaign_products p ON c.id = p.campaign_id
    GROUP BY c.id, c.category_name
    ORDER BY c.id
");

$totalProducts = 0;
while ($row = $result->fetch_assoc()) {
    printf("%-35s | %15d\n", $row['category_name'], $row['product_count']);
    $totalProducts += $row['product_count'];
}

echo str_repeat('-', 70) . "\n";
printf("%-35s | %15d\n", "TOPLAM", $totalProducts);
echo "\n";

// 3. Kural sayıları
$result = $db->query("SELECT COUNT(*) as cnt FROM custom_campaign_rules");
$ruleCount = $result->fetch_assoc()['cnt'];
echo "Toplam Kural Sayısı: $ruleCount\n\n";

// 4. Müşteri sayısı
$result = $db->query("SELECT COUNT(*) as cnt FROM custom_campaign_customers");
$customerCount = $result->fetch_assoc()['cnt'];
echo "Toplam Ana Bayi Sayısı: $customerCount\n\n";

// 5. Her kategorinin detayları
echo "=== KATEGORİ DETAYLARI ===\n\n";

$result = $db->query("
    SELECT 
        id,
        category_name,
        min_quantity,
        min_total_amount,
        fallback_discount_credit,
        fallback_discount_cash,
        is_active
    FROM custom_campaigns
    ORDER BY id
");

while ($row = $result->fetch_assoc()) {
    echo "Kategori: {$row['category_name']}\n";
    echo "  - ID: {$row['id']}\n";
    echo "  - Min Miktar: " . ($row['min_quantity'] ?? 'Yok') . "\n";
    echo "  - Min Tutar: " . ($row['min_total_amount'] ?? 'Yok') . "€\n";
    echo "  - Fallback İskonto (Kredili): {$row['fallback_discount_credit']}%\n";
    echo "  - Fallback İskonto (Nakit): {$row['fallback_discount_cash']}%\n";
    echo "  - Durum: " . ($row['is_active'] ? 'Aktif' : 'Pasif') . "\n";
    
    // Bu kategorideki örnek 3 ürün
    $productResult = $db->query("
        SELECT product_code, discount_rate 
        FROM custom_campaign_products 
        WHERE campaign_id = {$row['id']} 
        LIMIT 3
    ");
    
    if ($productResult->num_rows > 0) {
        echo "  - Örnek Ürünler:\n";
        while ($product = $productResult->fetch_assoc()) {
            echo "    * {$product['product_code']} ({$product['discount_rate']}%)\n";
        }
    }
    echo "\n";
}

// 6. Yeni eklenen 5 kategori kontrolü
echo "=== YENİ EKLENDİ (5 KATEGORİ) ===\n\n";

$newCategories = [
    'KENAR EKİPMAN - IZGARA',
    'HAVUZİÇİ EKİPMAN',
    'LEDLER',
    'TEMİZLİK EKİPMANLARI',
    'BORU'
];

foreach ($newCategories as $catName) {
    $result = $db->query("
        SELECT 
            c.id,
            c.category_name,
            COUNT(p.id) as product_count
        FROM custom_campaigns c
        LEFT JOIN custom_campaign_products p ON c.id = p.campaign_id
        WHERE c.category_name = '" . $db->real_escape_string($catName) . "'
        GROUP BY c.id, c.category_name
    ");
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "✓ {$row['category_name']}: {$row['product_count']} ürün\n";
    } else {
        echo "✗ $catName: BULUNAMADI!\n";
    }
}

echo "\n=== DOĞRULAMA TAMAMLANDI ===\n";

$db->close();
