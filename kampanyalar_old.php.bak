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
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8" />
    <title><?php echo $sistemayar["title"]; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $sistemayar["description"]; ?>" name="description" />
    <meta content="<?php echo $sistemayar["keywords"]; ?>" name="keywords" />
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link href="assets/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" />
    <link href="assets/css/icons.min.css" rel="stylesheet" />
    <link href="assets/css/app.min.css" id="app-style" rel="stylesheet" />
    <style>
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

<body data-layout="horizontal" data-topbar="colored">
    <div id="layout-wrapper">
        <header id="page-topbar">
            <?php include "menuler/ustmenu.php"; ?>
            <?php include "menuler/solmenu.php"; ?>
        </header>
        
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                                <h4 class="mb-sm-0 font-size-18">📋 Ana Bayi Kampanya Listesi</h4>
                            </div>
                        </div>
                    </div>

                    <?php foreach ($campaigns as $categoryName => $campaign): ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
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
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($campaigns)): ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-warning">
                                Aktif kampanya bulunamadı.
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
            <?php include "menuler/footer.php"; ?>
        </div>
    </div>

    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/metismenu/metisMenu.min.js"></script>
    <script src="assets/libs/simplebar/simplebar.min.js"></script>
    <script src="assets/libs/node-waves/waves.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
<?php
// Database bağlantısını kapat
if (isset($db) && $db instanceof mysqli) {
    $db->close();
}
?>
