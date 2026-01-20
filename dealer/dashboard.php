<?php
// Bayi Dashboard
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

$dealerId = (int)$_SESSION['yonetici_id'];
$companyId = (int)$_SESSION['dealer_company_id'];

// Şirket bilgileri
$stmt = $db->prepare("SELECT * FROM sirket WHERE sirket_id = ?");
$stmt->bind_param('i', $companyId);
$stmt->execute();
$company = $stmt->get_result()->fetch_assoc();
$stmt->close();

// İstatistikler
// Toplam sipariş sayısı
$stmt = $db->prepare("SELECT COUNT(*) as total FROM ogteklif2 WHERE sirket_arp_code = ?");
$stmt->bind_param('s', $company['s_arp_code']);
$stmt->execute();
$orderCount = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Son 30 günde verilen siparişler
$stmt = $db->prepare("SELECT COUNT(*) as total FROM ogteklif2 WHERE sirket_arp_code = ? AND tekliftarihi >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$stmt->bind_param('s', $company['s_arp_code']);
$stmt->execute();
$recentOrderCount = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Bekleyen siparişler (durum: Beklemede veya Onaylandı)
$stmt = $db->prepare("SELECT COUNT(*) as total FROM ogteklif2 WHERE sirket_arp_code = ? AND durum IN ('Beklemede', 'Onaylandı')");
$stmt->bind_param('s', $company['s_arp_code']);
$stmt->execute();
$pendingOrderCount = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Açık hesap
$acikHesap = floatval($company['acikhesap'] ?? 0);

// Son 5 sipariş
$stmt = $db->prepare("SELECT * FROM ogteklif2 WHERE sirket_arp_code = ? ORDER BY tekliftarihi DESC LIMIT 5");
$stmt->bind_param('s', $company['s_arp_code']);
$stmt->execute();
$recentOrders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$db->close();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - GEMAS B2B Portal</title>
    <link rel="shortcut icon" href="../assets/images/favicon.ico">
    <?php include "includes/styles.php"; ?>
</head>
<body data-layout="horizontal" data-topbar="colored">
    <div id="layout-wrapper">
        <?php include "includes/header.php"; ?>
        <?php include "includes/menu.php"; ?>
        
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    
                    <!-- Welcome Banner -->
                    <div class="welcome-banner">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h2 class="mb-2">
                                    <i class="mdi mdi-hand-wave me-2"></i>
                                    Hoş Geldiniz, <?= htmlspecialchars($_SESSION['dealer_username']) ?>!
                                </h2>
                                <p class="mb-0 opacity-90">
                                    <i class="mdi mdi-office-building me-2"></i>
                                    <?= htmlspecialchars($company['s_adi']) ?> 
                                    <span class="ms-3">
                                        <i class="mdi mdi-barcode me-2"></i>
                                        Cari Kodu: <?= htmlspecialchars($company['s_arp_code']) ?>
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <a href="products.php" class="btn btn-light btn-lg quick-action-btn">
                                    <i class="mdi mdi-cart-plus me-2"></i>Sipariş Ver
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- İstatistikler -->
                    <div class="row">
                        <div class="col-xl-3 col-md-6">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon bg-primary text-white">
                                        <i class="mdi mdi-cart-outline"></i>
                                    </div>
                                    <div class="ms-3 flex-grow-1">
                                        <div class="stat-number"><?= number_format($orderCount) ?></div>
                                        <div class="stat-label">Toplam Sipariş</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon bg-warning text-white">
                                        <i class="mdi mdi-clock-outline"></i>
                                    </div>
                                    <div class="ms-3 flex-grow-1">
                                        <div class="stat-number"><?= number_format($pendingOrderCount) ?></div>
                                        <div class="stat-label">Bekleyen Sipariş</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon bg-info text-white">
                                        <i class="mdi mdi-calendar-month"></i>
                                    </div>
                                    <div class="ms-3 flex-grow-1">
                                        <div class="stat-number"><?= number_format($recentOrderCount) ?></div>
                                        <div class="stat-label">Son 30 Gün</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon bg-success text-white">
                                        <i class="mdi mdi-cash-multiple"></i>
                                    </div>
                                    <div class="ms-3 flex-grow-1">
                                        <div class="stat-number">₺<?= number_format($acikHesap, 2) ?></div>
                                        <div class="stat-label">Açık Hesap</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Hızlı Erişim -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                                <div class="card-body p-4">
                                    <h5 class="card-title mb-4">
                                        <i class="mdi mdi-lightning-bolt text-warning me-2"></i>Hızlı Erişim
                                    </h5>
                                    <div class="row g-3">
                                        <div class="col-md-3 col-6">
                                            <a href="products.php" class="btn btn-outline-primary w-100 quick-action-btn">
                                                <i class="mdi mdi-package-variant d-block mb-2" style="font-size: 24px;"></i>
                                                Ürünler
                                            </a>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <a href="orders.php" class="btn btn-outline-success w-100 quick-action-btn">
                                                <i class="mdi mdi-format-list-bulleted d-block mb-2" style="font-size: 24px;"></i>
                                                Siparişlerim
                                            </a>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <a href="invoices.php" class="btn btn-outline-info w-100 quick-action-btn">
                                                <i class="mdi mdi-file-document d-block mb-2" style="font-size: 24px;"></i>
                                                Faturalarım
                                            </a>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <a href="account.php" class="btn btn-outline-secondary w-100 quick-action-btn">
                                                <i class="mdi mdi-account-circle d-block mb-2" style="font-size: 24px;"></i>
                                                Hesabım
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Son Siparişler -->
                    <div class="row">
                        <div class="col-12">
                            <div class="recent-orders-table">
                                <h5 class="mb-4">
                                    <i class="mdi mdi-history text-primary me-2"></i>Son Siparişlerim
                                </h5>
                                <?php if (count($recentOrders) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Sipariş No</th>
                                                <th>Tarih</th>
                                                <th>Durum</th>
                                                <th>Toplam Tutar</th>
                                                <th>İşlem</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentOrders as $order): ?>
                                            <tr>
                                                <td><strong>#<?= htmlspecialchars($order['id']) ?></strong></td>
                                                <td><?= !empty($order['tekliftarihi']) ? date('d.m.Y H:i', strtotime($order['tekliftarihi'])) : '-' ?></td>
                                                <td>
                                                    <?php
                                                    $statusText = htmlspecialchars($order['durum'] ?? 'Bilinmiyor');
                                                    $statusColor = 'secondary';
                                                    if (stripos($statusText, 'Bekle') !== false) {
                                                        $statusColor = 'warning';
                                                    } elseif (stripos($statusText, 'Onay') !== false) {
                                                        $statusColor = 'info';
                                                    } elseif (stripos($statusText, 'Tamamlan') !== false) {
                                                        $statusColor = 'success';
                                                    } elseif (stripos($statusText, 'İptal') !== false || stripos($statusText, 'Red') !== false) {
                                                        $statusColor = 'danger';
                                                    }
                                                    ?>
                                                    <span class="badge bg-<?= $statusColor ?>"><?= $statusText ?></span>
                                                </td>
                                                <td><strong>₺<?= number_format(floatval($order['geneltoplam'] ?? 0), 2) ?></strong></td>
                                                <td>
                                                    <a href="order_detail.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="mdi mdi-eye"></i> Detay
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-end mt-3">
                                    <a href="orders.php" class="btn btn-primary">
                                        Tüm Siparişleri Görüntüle <i class="mdi mdi-arrow-right ms-1"></i>
                                    </a>
                                </div>
                                <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="mdi mdi-cart-off" style="font-size: 64px; color: #ccc;"></i>
                                    <p class="text-muted mt-3">Henüz siparişiniz bulunmamaktadır.</p>
                                    <a href="products.php" class="btn btn-primary mt-2">
                                        <i class="mdi mdi-cart-plus me-2"></i>İlk Siparişinizi Verin
                                    </a>
                                </div>
                                <?php endif; ?>
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
</body>
</html>

