<?php
// Bayi Sipariş Oluştur
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
$dealerId = (int)$_SESSION['yonetici_id'];
$cariCode = $_SESSION['dealer_cari_code'] ?? '';

// Şirket bilgilerini çek
$stmt = $db->prepare("SELECT * FROM sirket WHERE sirket_id = ?");
$stmt->bind_param('i', $companyId);
$stmt->execute();
$company = $stmt->get_result()->fetch_assoc();
$stmt->close();

$message = '';
$messageType = '';

// Sipariş kaydetme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_order'])) {
    $cartData = $_POST['cart_data'] ?? '';
    $aciklama = trim($_POST['aciklama'] ?? '');
    $teslimat_adresi = trim($_POST['teslimat_adresi'] ?? '');
    
    if (empty($cartData)) {
        $message = 'Sepet verisi bulunamadı!';
        $messageType = 'danger';
    } else {
        $cart = json_decode($cartData, true);
        
        if (empty($cart)) {
            $message = 'Sepet boş!';
            $messageType = 'danger';
        } else {
            // Sipariş oluştur
            $tekliftarihi = date('Y-m-d H:i:s');
            $durum = 'Beklemede';
            
            // Toplam hesapla
            $toplamtutar = 0;
            foreach ($cart as $item) {
                $toplamtutar += floatval($item['fiyat']) * floatval($item['miktar']);
            }
            $kdv = $toplamtutar * 0.20;
            $geneltoplam = $toplamtutar + $kdv;
            
            // ogteklif2 tablosuna ekle
            $stmt = $db->prepare("INSERT INTO ogteklif2 (sirket_arp_code, sirketid, tekliftarihi, durum, notes1, 
                                   teslimyer, toplamtutar, kdv, geneltoplam, musteriadi, hazirlayanid, tur) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'bayi_siparis')");
            $stmt->bind_param('sissssdddsi', $cariCode, $companyId, $tekliftarihi, $durum, $aciklama, 
                             $teslimat_adresi, $toplamtutar, $kdv, $geneltoplam, $company['s_adi'], $dealerId);
            
            if ($stmt->execute()) {
                $orderId = $stmt->insert_id;
                $stmt->close();
                
                // Sipariş ürünlerini ekle (ogteklifurun2 tablosu)
                $stmtItem = $db->prepare("INSERT INTO ogteklifurun2 (teklifid, kod, adi, miktar, liste, birim) 
                                          VALUES (?, ?, ?, ?, ?, 'Adet')");
                
                foreach ($cart as $item) {
                    $stmtItem->bind_param('issdd', $orderId, $item['stokkodu'], $item['stokadi'], 
                                         $item['miktar'], $item['fiyat']);
                    $stmtItem->execute();
                }
                $stmtItem->close();
                
                $message = 'Siparişiniz başarıyla oluşturuldu! Sipariş No: #' . $orderId;
                $messageType = 'success';
                
                // Sepeti temizle (JavaScript ile yapılacak)
            } else {
                $message = 'Sipariş oluşturulurken bir hata oluştu: ' . $stmt->error;
                $messageType = 'danger';
                $stmt->close();
            }
        }
    }
}

$db->close();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sipariş Oluştur - GEMAS B2B Portal</title>
    <link rel="shortcut icon" href="../assets/images/favicon.ico">
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/icons.min.css" rel="stylesheet">
    <link href="../assets/css/app.min.css" rel="stylesheet">
    <style>
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 30px;
            color: white;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        .order-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        .summary-card {
            background: linear-gradient(135deg, #667eea10, #764ba210);
            border-radius: 15px;
            padding: 25px;
            position: sticky;
            top: 20px;
        }
        .order-item {
            border-bottom: 1px solid #f0f0f0;
            padding: 15px 0;
        }
        .order-item:last-child {
            border-bottom: none;
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
                        <h2 class="mb-2">
                            <i class="mdi mdi-cart-check me-2"></i>Sipariş Oluştur
                        </h2>
                        <p class="mb-0 opacity-90">
                            Sipariş bilgilerinizi gözden geçirin ve onaylayın
                        </p>
                    </div>
                    
                    <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                        <i class="mdi mdi-<?= $messageType === 'success' ? 'check-circle' : 'alert-circle' ?> me-2"></i>
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    
                    <?php if ($messageType === 'success'): ?>
                    <div class="text-center mb-4">
                        <a href="orders.php" class="btn btn-primary btn-lg">
                            <i class="mdi mdi-format-list-bulleted me-2"></i>Siparişlerime Git
                        </a>
                        <a href="products.php" class="btn btn-outline-primary btn-lg ms-2">
                            <i class="mdi mdi-cart-plus me-2"></i>Yeni Sipariş
                        </a>
                    </div>
                    <script>
                        // Başarılı sipariş sonrası sepeti temizle
                        localStorage.removeItem('b2b_cart');
                    </script>
                    <?php endif; ?>
                    <?php endif; ?>
                    
                    <form method="POST" id="orderForm">
                        <input type="hidden" name="create_order" value="1">
                        <input type="hidden" name="cart_data" id="cartData">
                        
                        <div class="row">
                            <!-- Sipariş Detayları -->
                            <div class="col-lg-8">
                                <!-- Ürünler -->
                                <div class="order-card">
                                    <h5 class="mb-4">
                                        <i class="mdi mdi-package-variant text-primary me-2"></i>Sipariş Ürünleri
                                    </h5>
                                    <div id="orderItems"></div>
                                </div>
                                
                                <!-- Teslimat Bilgileri -->
                                <div class="order-card">
                                    <h5 class="mb-4">
                                        <i class="mdi mdi-truck text-primary me-2"></i>Teslimat Bilgileri
                                    </h5>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Teslimat Adresi</label>
                                        <textarea class="form-control" name="teslimat_adresi" rows="3" 
                                                  placeholder="Teslimat adresini girin..."><?= htmlspecialchars($company['s_adresi'] ?? '') ?></textarea>
                                        <small class="text-muted">Varsayılan olarak şirket adresiniz kullanılacaktır.</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Sipariş Notu / Açıklama</label>
                                        <textarea class="form-control" name="aciklama" rows="3" 
                                                  placeholder="Sipariş ile ilgili not veya açıklama..."></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Özet ve Onay -->
                            <div class="col-lg-4">
                                <div class="summary-card">
                                    <h5 class="mb-4">
                                        <i class="mdi mdi-clipboard-check text-primary me-2"></i>Sipariş Özeti
                                    </h5>
                                    
                                    <div class="mb-3 pb-3 border-bottom">
                                        <strong>Şirket:</strong><br>
                                        <small><?= htmlspecialchars($company['s_adi']) ?></small>
                                    </div>
                                    
                                    <div class="mb-3 pb-3 border-bottom">
                                        <strong>Cari Kodu:</strong><br>
                                        <span class="badge bg-primary"><?= htmlspecialchars($cariCode) ?></span>
                                    </div>
                                    
                                    <div class="mb-3 pb-3 border-bottom">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Ürün Sayısı:</span>
                                            <strong id="itemCount">0</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Ara Toplam:</span>
                                            <strong id="subtotal">₺0,00</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>KDV (%20):</span>
                                            <strong id="vat">₺0,00</strong>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between">
                                            <h5 class="mb-0">Genel Toplam:</h5>
                                            <h5 class="mb-0 text-success" id="total">₺0,00</h5>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-success btn-lg w-100 mb-2" id="confirmBtn" disabled>
                                        <i class="mdi mdi-check-circle me-2"></i>Siparişi Onayla
                                    </button>
                                    
                                    <a href="cart.php" class="btn btn-outline-secondary w-100">
                                        <i class="mdi mdi-arrow-left me-2"></i>Sepete Dön
                                    </a>
                                    
                                    <div class="alert alert-info mt-3 mb-0">
                                        <small>
                                            <i class="mdi mdi-information-outline me-1"></i>
                                            Siparişiniz onaylandıktan sonra sistem yöneticileri tarafından işleme alınacaktır.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                    
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
    <script src="../assets/js/app.js"></script>
    
    <script>
    $(document).ready(function() {
        loadOrderPreview();
        
        $('#orderForm').on('submit', function() {
            $('#confirmBtn').prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin me-2"></i>İşleniyor...');
        });
    });
    
    function loadOrderPreview() {
        var cart = JSON.parse(localStorage.getItem('b2b_cart') || '[]');
        
        if (cart.length === 0) {
            window.location.href = 'cart.php';
            return;
        }
        
        // Sepet verisini forma ekle
        $('#cartData').val(JSON.stringify(cart));
        
        var html = '';
        var subtotal = 0;
        
        cart.forEach(function(item) {
            var itemTotal = item.fiyat * item.miktar;
            subtotal += itemTotal;
            
            html += '<div class="order-item">' +
                    '<div class="row align-items-center">' +
                    '<div class="col-md-6">' +
                    '<strong>' + escapeHtml(item.stokkodu) + '</strong><br>' +
                    '<small class="text-muted">' + escapeHtml(item.stokadi) + '</small>' +
                    '</div>' +
                    '<div class="col-md-2 text-center">' +
                    '<span class="badge bg-secondary">' + item.miktar + ' adet</span>' +
                    '</div>' +
                    '<div class="col-md-2 text-center">' +
                    '<strong>₺' + parseFloat(item.fiyat).toLocaleString('tr-TR', {minimumFractionDigits: 2}) + '</strong>' +
                    '</div>' +
                    '<div class="col-md-2 text-end">' +
                    '<strong class="text-success">₺' + itemTotal.toLocaleString('tr-TR', {minimumFractionDigits: 2}) + '</strong>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
        });
        
        $('#orderItems').html(html);
        
        var vat = subtotal * 0.20;
        var total = subtotal + vat;
        
        $('#itemCount').text(cart.length);
        $('#subtotal').text('₺' + subtotal.toLocaleString('tr-TR', {minimumFractionDigits: 2}));
        $('#vat').text('₺' + vat.toLocaleString('tr-TR', {minimumFractionDigits: 2}));
        $('#total').text('₺' + total.toLocaleString('tr-TR', {minimumFractionDigits: 2}));
        $('#confirmBtn').prop('disabled', false);
    }
    
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    </script>
</body>
</html>

