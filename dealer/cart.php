<?php
// Bayi Sepet
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

$db->close();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sepetim - GEMAS B2B Portal</title>
    <link rel="shortcut icon" href="../assets/images/favicon.ico">
    <?php include "includes/styles.php"; ?>
    <style>
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 30px;
            color: white;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        .cart-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        .cart-item {
            border-bottom: 1px solid #f0f0f0;
            padding: 20px 0;
        }
        .cart-item:last-child {
            border-bottom: none;
        }
        .quantity-input {
            width: 80px;
            text-align: center;
        }
        .summary-card {
            background: linear-gradient(135deg, #667eea10, #764ba210);
            border-radius: 15px;
            padding: 25px;
            position: sticky;
            top: 20px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .summary-row:last-child {
            border-bottom: none;
            font-size: 20px;
            font-weight: 700;
            margin-top: 10px;
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
                                    <i class="mdi mdi-cart me-2"></i>Sepetim
                                </h2>
                                <p class="mb-0 opacity-90">
                                    Sepetinizdeki ürünleri kontrol edin ve siparişinizi tamamlayın
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <a href="products.php" class="btn btn-light btn-lg">
                                    <i class="mdi mdi-arrow-left me-2"></i>Alışverişe Devam
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Sepet Ürünleri -->
                        <div class="col-lg-8">
                            <div class="cart-card">
                                <h5 class="mb-4">
                                    <i class="mdi mdi-cart-outline text-primary me-2"></i>Sepetteki Ürünler
                                    <span class="badge bg-primary ms-2" id="itemCount">0</span>
                                </h5>
                                
                                <div id="cartItems">
                                    <div class="text-center py-5" id="emptyCart">
                                        <i class="mdi mdi-cart-off" style="font-size: 64px; color: #ccc;"></i>
                                        <p class="text-muted mt-3">Sepetiniz boş.</p>
                                        <a href="products.php" class="btn btn-primary mt-2">
                                            <i class="mdi mdi-package-variant me-2"></i>Ürünleri İncele
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Özet -->
                        <div class="col-lg-4">
                            <div class="summary-card">
                                <h5 class="mb-4">
                                    <i class="mdi mdi-clipboard-text text-primary me-2"></i>Sipariş Özeti
                                </h5>
                                
                                <div class="summary-row">
                                    <span>Ara Toplam:</span>
                                    <strong id="subtotal">₺0,00</strong>
                                </div>
                                
                                <div class="summary-row">
                                    <span>KDV (%20):</span>
                                    <strong id="vat">₺0,00</strong>
                                </div>
                                
                                <div class="summary-row text-success">
                                    <span>Genel Toplam:</span>
                                    <strong id="total">₺0,00</strong>
                                </div>
                                
                                <div class="mt-4">
                                    <button id="checkoutBtn" class="btn btn-success btn-lg w-100" disabled>
                                        <i class="mdi mdi-check-circle me-2"></i>Siparişi Tamamla
                                    </button>
                                    <button id="clearCartBtn" class="btn btn-outline-danger w-100 mt-2">
                                        <i class="mdi mdi-delete me-2"></i>Sepeti Temizle
                                    </button>
                                </div>
                                
                                <div class="alert alert-info mt-3 mb-0">
                                    <small>
                                        <i class="mdi mdi-information-outline me-1"></i>
                                        Fiyatlar KDV hariçtir.
                                    </small>
                                </div>
                            </div>
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
    <script src="../assets/js/app.js"></script>
    
    <script>
    $(document).ready(function() {
        loadCart();
        
        // Sepeti temizle
        $('#clearCartBtn').on('click', function() {
            if (confirm('Sepeti tamamen temizlemek istediğinizden emin misiniz?')) {
                localStorage.removeItem('b2b_cart');
                loadCart();
            }
        });
        
        // Siparişi tamamla
        $('#checkoutBtn').on('click', function() {
            var cart = JSON.parse(localStorage.getItem('b2b_cart') || '[]');
            if (cart.length === 0) {
                alert('Sepetiniz boş!');
                return;
            }
            
            // Sipariş oluşturma sayfasına yönlendir
            window.location.href = 'create_order.php';
        });
    });
    
    function loadCart() {
        var cart = JSON.parse(localStorage.getItem('b2b_cart') || '[]');
        
        if (cart.length === 0) {
            $('#emptyCart').show();
            $('#itemCount').text('0');
            $('#checkoutBtn').prop('disabled', true);
            updateSummary(0, 0, 0);
            return;
        }
        
        $('#emptyCart').hide();
        $('#itemCount').text(cart.length);
        $('#checkoutBtn').prop('disabled', false);
        
        var html = '';
        var subtotal = 0;
        
        cart.forEach(function(item, index) {
            var itemTotal = item.fiyat * item.miktar;
            subtotal += itemTotal;
            
            html += '<div class="cart-item">' +
                    '<div class="row align-items-center">' +
                    '<div class="col-md-5">' +
                    '<strong>' + escapeHtml(item.stokkodu) + '</strong><br>' +
                    '<small class="text-muted">' + escapeHtml(item.stokadi) + '</small>' +
                    '</div>' +
                    '<div class="col-md-2 text-center">' +
                    '<strong class="text-success">₺' + parseFloat(item.fiyat).toLocaleString('tr-TR', {minimumFractionDigits: 2}) + '</strong>' +
                    '</div>' +
                    '<div class="col-md-3 text-center">' +
                    '<div class="input-group">' +
                    '<button class="btn btn-sm btn-outline-secondary" onclick="updateQuantity(' + index + ', -1)">-</button>' +
                    '<input type="number" class="form-control quantity-input" value="' + item.miktar + '" onchange="setQuantity(' + index + ', this.value)" min="1">' +
                    '<button class="btn btn-sm btn-outline-secondary" onclick="updateQuantity(' + index + ', 1)">+</button>' +
                    '</div>' +
                    '</div>' +
                    '<div class="col-md-2 text-end">' +
                    '<strong>₺' + itemTotal.toLocaleString('tr-TR', {minimumFractionDigits: 2}) + '</strong><br>' +
                    '<button class="btn btn-sm btn-outline-danger mt-1" onclick="removeItem(' + index + ')">' +
                    '<i class="mdi mdi-delete"></i>' +
                    '</button>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
        });
        
        $('#cartItems').html(html);
        
        var vat = subtotal * 0.20;
        var total = subtotal + vat;
        updateSummary(subtotal, vat, total);
    }
    
    function updateSummary(subtotal, vat, total) {
        $('#subtotal').text('₺' + subtotal.toLocaleString('tr-TR', {minimumFractionDigits: 2}));
        $('#vat').text('₺' + vat.toLocaleString('tr-TR', {minimumFractionDigits: 2}));
        $('#total').text('₺' + total.toLocaleString('tr-TR', {minimumFractionDigits: 2}));
    }
    
    function updateQuantity(index, change) {
        var cart = JSON.parse(localStorage.getItem('b2b_cart') || '[]');
        if (cart[index]) {
            cart[index].miktar += change;
            if (cart[index].miktar < 1) cart[index].miktar = 1;
            localStorage.setItem('b2b_cart', JSON.stringify(cart));
            loadCart();
        }
    }
    
    function setQuantity(index, value) {
        var cart = JSON.parse(localStorage.getItem('b2b_cart') || '[]');
        var qty = parseInt(value);
        if (cart[index] && qty > 0) {
            cart[index].miktar = qty;
            localStorage.setItem('b2b_cart', JSON.stringify(cart));
            loadCart();
        }
    }
    
    function removeItem(index) {
        var cart = JSON.parse(localStorage.getItem('b2b_cart') || '[]');
        cart.splice(index, 1);
        localStorage.setItem('b2b_cart', JSON.stringify(cart));
        loadCart();
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

