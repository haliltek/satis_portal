<?php
require_once 'fonk.php';
oturumkontrol();

// Kampanyaları veritabanından çek
$config = require 'config/config.php';
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

// Tüm kampanyaları ve ürünlerini çek
$query = "
    SELECT 
        c.id as campaign_id,
        c.category_name,
        c.min_quantity,
        c.min_total_amount,
        c.fallback_discount_credit,
        c.fallback_discount_cash,
        p.product_code,
        p.discount_rate,
        u.stokadi as product_name
    FROM custom_campaigns c
    LEFT JOIN custom_campaign_products p ON c.id = p.campaign_id
    LEFT JOIN urunler u ON p.product_code = u.stokkodu
    WHERE c.is_active = 1
    ORDER BY c.id, p.product_code
";

$result = $db->query($query);

// Kampanyaları kategorilere göre grupla
$campaigns = [];
while ($row = $result->fetch_assoc()) {
    $catName = $row['category_name'];
    
    if (!isset($campaigns[$catName])) {
        $campaigns[$catName] = [
            'min_quantity' => $row['min_quantity'],
            'min_total_amount' => $row['min_total_amount'],
            'fallback_credit' => $row['fallback_discount_credit'],
            'fallback_cash' => $row['fallback_discount_cash'],
            'products' => []
        ];
    }
    
    if ($row['product_code']) {
        $campaigns[$catName]['products'][] = [
            'code' => $row['product_code'],
            'name' => $row['product_name'] ?? 'Ürün Adı Bulunamadı',
            'discount' => $row['discount_rate']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kampanya Listesi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid #007bff;
        }
        .category-section {
            margin-bottom: 40px;
        }
        .category-title {
            background: #007bff;
            color: white;
            padding: 12px 20px;
            font-size: 18px;
            font-weight: 600;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .category-info {
            background: #f8f9fa;
            padding: 10px 15px;
            margin-bottom: 15px;
            border-left: 4px solid #007bff;
            font-size: 13px;
        }
        .category-info strong {
            color: #007bff;
        }
        .product-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .product-item {
            padding: 10px 15px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .product-item:hover {
            background: #f8f9fa;
        }
        .product-code {
            font-family: monospace;
            font-weight: 600;
            color: #333;
        }
        .product-name {
            color: #666;
            font-size: 14px;
        }
        .product-discount {
            background: #28a745;
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <?php include 'menuler/ustmenu.php'; ?>

    <div class="container">
        <h1>📋 Ana Bayi Kampanya Listesi</h1>
        
        <?php foreach ($campaigns as $categoryName => $campaign): ?>
        <div class="category-section">
            <div class="category-title">
                <?= htmlspecialchars($categoryName) ?> (<?= count($campaign['products']) ?> Ürün)
            </div>
            
            <div class="category-info">
                <?php if ($campaign['min_quantity']): ?>
                    <strong>Min Miktar:</strong> <?= number_format($campaign['min_quantity']) ?> adet &nbsp;|&nbsp;
                <?php endif; ?>
                <?php if ($campaign['min_total_amount']): ?>
                    <strong>Min Tutar:</strong> <?= number_format($campaign['min_total_amount']) ?>€ &nbsp;|&nbsp;
                <?php endif; ?>
                <strong>Fallback:</strong> 
                Kredili: %<?= number_format($campaign['fallback_credit'], 1) ?>, 
                Nakit: %<?= number_format($campaign['fallback_cash'], 1) ?>
            </div>
            
            <ul class="product-list">
                <?php foreach ($campaign['products'] as $product): ?>
                <li class="product-item">
                    <div>
                        <span class="product-code"><?= htmlspecialchars($product['code']) ?></span>
                        <span class="product-name"> - <?= htmlspecialchars($product['name']) ?></span>
                    </div>
                    <span class="product-discount">%<?= number_format($product['discount'], 2) ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endforeach; ?>
        
        <?php if (empty($campaigns)): ?>
        <div class="alert alert-warning">
            Aktif kampanya bulunamadı.
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
// Database bağlantısını kapat
if (isset($db) && $db instanceof mysqli) {
    $db->close();
}
?>
