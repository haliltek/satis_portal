<?php
// Bayi Ürünler (Sadece Domestic)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Giriş kontrolü
if (!isset($_SESSION['yonetici_id']) || ($_SESSION['user_type'] ?? '') !== 'Bayi') {
    header('Location: index.php');
    exit;
}

include "../include/vt.php";

$db = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
$db->set_charset('utf8mb4');

$companyId = (int)$_SESSION['dealer_company_id'];

// Şirket bilgilerini çek
$stmt = $db->prepare("SELECT * FROM sirket WHERE sirket_id = ?");
$stmt->bind_param('i', $companyId);
$stmt->execute();
$company = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Filtreleme
$searchTerm = $_GET['search'] ?? '';
$filterCategory = $_GET['category'] ?? '';
$filterBrand = $_GET['brand'] ?? '';

// Kategorileri çek (kat1 kullanıyoruz)
$categories = $db->query("SELECT DISTINCT kat1 as kategori FROM urunler WHERE kat1 IS NOT NULL AND kat1 != '' ORDER BY kat1")->fetch_all(MYSQLI_ASSOC);

// Markaları çek
$brands = $db->query("SELECT DISTINCT marka FROM urunler WHERE marka IS NOT NULL AND marka != '' ORDER BY marka")->fetch_all(MYSQLI_ASSOC);

$db->close();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ürün Kataloğu - GEMAS B2B Portal</title>
    <link rel="shortcut icon" href="../assets/images/favicon.ico">
    <?php include "includes/styles.php"; ?>
    <link href="../assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="../assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet">
    <style>
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 30px;
            color: white;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        .table-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        .price-tag {
            font-size: 18px;
            font-weight: 700;
            color: #28a745;
        }
        .stock-badge {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
        }
        .add-to-cart-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            padding: 5px 15px;
            border-radius: 5px;
            transition: transform 0.3s;
        }
        .add-to-cart-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }
    </style>
</head>
<body data-layout="horizontal" data-topbar="colored">
    <div id="layout-wrapper">
        <?php include "includes/header.php"; ?>
        <?php include "includes/menu.php"; ?>
        
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    
                    <!-- Page Header -->
                    <div class="page-header">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h2 class="mb-2">
                                    <i class="mdi mdi-package-variant me-2"></i>Ürün Kataloğu
                                </h2>
                                <p class="mb-0 opacity-90">
                                    Yurtiçi satışa uygun tüm ürünleri görüntüleyin ve sepete ekleyin
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <a href="cart.php" class="btn btn-light btn-lg">
                                    <i class="mdi mdi-cart me-2"></i>Sepetim <span class="badge bg-danger" id="cartCount">0</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filtreler -->
                    <div class="filter-card">
                        <h5 class="mb-3"><i class="mdi mdi-filter me-2"></i>Filtreleme ve Arama</h5>
                        <form method="GET" action="" id="filterForm">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Arama</label>
                                        <input type="text" class="form-control" name="search" 
                                               placeholder="Ürün adı veya kodu..." 
                                               value="<?= htmlspecialchars($searchTerm) ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Kategori</label>
                                        <select class="form-select" name="category">
                                            <option value="">Tümü</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?= htmlspecialchars($cat['kategori']) ?>" 
                                                        <?= $filterCategory === $cat['kategori'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($cat['kategori']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Marka</label>
                                        <select class="form-select" name="brand">
                                            <option value="">Tümü</option>
                                            <?php foreach ($brands as $brand): ?>
                                                <option value="<?= htmlspecialchars($brand['marka']) ?>" 
                                                        <?= $filterBrand === $brand['marka'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($brand['marka']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="mb-3">
                                        <label class="form-label">&nbsp;</label>
                                        <div>
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="mdi mdi-magnify me-2"></i>Ara
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Ürünler Tablosu -->
                    <div class="table-card">
                        <h5 class="mb-4">
                            <i class="mdi mdi-package-variant-closed text-primary me-2"></i>Ürün Listesi
                            <span class="badge bg-info ms-2">Yurtiçi Fiyatlar</span>
                        </h5>
                        
                        <div class="table-responsive">
                            <table id="productsTable" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Stok Kodu</th>
                                        <th>Resim</th>
                                        <th>Ürün Adı</th>
                                        <th>Kategori</th>
                                        <th>Marka</th>
                                        <th>Birim</th>
                                        <th>Fiyat (YURTİÇİ)</th>
                                        <th>Stok</th>
                                        <th>İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- DataTables ile AJAX yüklenecek -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                </div>
            </div>
            <?php include "includes/footer.php"; ?>
        </div>
    </div>

    <script src="../assets/libs/jquery/jquery.min.js"></script>
    <script src="../assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/libs/metismenu/metisMenu.min.js"></script>
    <script src="../assets/libs/simplebar/simplebar.min.js"></script>
    <script src="../assets/libs/node-waves/waves.min.js"></script>
    <script src="../assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="../assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="../assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="../assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>
    <script src="../assets/js/app.js"></script>
    
    <script>
    $(document).ready(function() {
        // DataTable başlat
        var table = $('#productsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: 'api/get_products.php',
                type: 'POST',
                data: function(d) {
                    d.search_term = '<?= addslashes($searchTerm) ?>';
                    d.category = '<?= addslashes($filterCategory) ?>';
                    d.brand = '<?= addslashes($filterBrand) ?>';
                }
            },
            columns: [
                { data: 'stokkodu' },
                { 
                    data: 'image_url',
                    orderable: false,
                    render: function(data, type, row) {
                        var imageUrl = data || 'assets/front/assets/images/unnamed.png';
                        return '<img src="' + imageUrl + '" class="product-image" alt="' + (row.stokadi || '') + '" onerror="this.src=\'assets/front/assets/images/unnamed.png\'">';
                    }
                },
                { data: 'stokadi' },
                { data: 'kategori' },
                { data: 'marka' },
                { data: 'birim' },
                { 
                    data: 'fiyat',
                    render: function(data, type, row) {
                        return '<span class="price-tag">₺' + parseFloat(data || 0).toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</span>';
                    }
                },
                { 
                    data: 'stok',
                    render: function(data, type, row) {
                        var stock = parseFloat(data || 0);
                        var badgeClass = stock > 10 ? 'bg-success' : (stock > 0 ? 'bg-warning' : 'bg-danger');
                        return '<span class="badge ' + badgeClass + '">' + stock.toFixed(0) + '</span>';
                    }
                },
                { 
                    data: null,
                    orderable: false,
                    render: function(data, type, row) {
                        return '<button class="btn btn-sm add-to-cart-btn" onclick="addToCart(\'' + row.stokkodu + '\', \'' + row.stokadi.replace(/'/g, "\\'") + '\', ' + row.fiyat + ')">' +
                               '<i class="mdi mdi-cart-plus"></i> Sepete Ekle' +
                               '</button>';
                    }
                }
            ],
            language: {
                url: '../assets/libs/datatables.net/i18n/tr.json'
            },
            pageLength: 25,
            order: [[1, 'asc']],
            responsive: true
        });
        
        // Sepet sayısını güncelle
        updateCartCount();
    });
    
    function addToCart(stokKodu, stokAdi, fiyat) {
        // LocalStorage'dan sepeti al
        var cart = JSON.parse(localStorage.getItem('b2b_cart') || '[]');
        
        // Ürünü bul veya ekle
        var existingItem = cart.find(item => item.stokkodu === stokKodu);
        if (existingItem) {
            existingItem.miktar++;
        } else {
            cart.push({
                stokkodu: stokKodu,
                stokadi: stokAdi,
                fiyat: fiyat,
                miktar: 1
            });
        }
        
        // Sepeti kaydet
        localStorage.setItem('b2b_cart', JSON.stringify(cart));
        
        // Bildirim göster
        alert('Ürün sepete eklendi: ' + stokAdi);
        
        // Sepet sayısını güncelle
        updateCartCount();
    }
    
    function updateCartCount() {
        var cart = JSON.parse(localStorage.getItem('b2b_cart') || '[]');
        var totalItems = cart.reduce((sum, item) => sum + item.miktar, 0);
        $('#cartCount').text(totalItems);
    }
    </script>
</body>
</html>

