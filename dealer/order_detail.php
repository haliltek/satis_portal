<?php
// Bayi Sipariş Detay
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

$orderId = (int)($_GET['id'] ?? 0);
$cariCode = $_SESSION['dealer_cari_code'] ?? '';

if ($orderId <= 0) {
    header('Location: orders.php');
    exit;
}

// Sipariş bilgilerini çek
$stmt = $db->prepare("SELECT * FROM ogteklif2 WHERE id = ? AND sirket_arp_code = ?");
$stmt->bind_param('is', $orderId, $cariCode);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    header('Location: orders.php');
    exit;
}

// Sipariş ürünlerini çek
$stmt = $db->prepare("SELECT * FROM ogteklifurun2 WHERE teklifid = ?");
$stmt->bind_param('i', $orderId);
$stmt->execute();
$orderItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$db->close();

function getStatusInfo($status) {
    $status = strtolower($status ?? '');
    
    if (stripos($status, 'bekle') !== false) {
        return ['text' => 'Beklemede', 'color' => 'warning', 'icon' => 'clock-outline'];
    } elseif (stripos($status, 'onay') !== false) {
        return ['text' => 'Onaylandı', 'color' => 'info', 'icon' => 'check-circle'];
    } elseif (stripos($status, 'tamamlan') !== false) {
        return ['text' => 'Tamamlandı', 'color' => 'success', 'icon' => 'check-all'];
    } elseif (stripos($status, 'iptal') !== false || stripos($status, 'red') !== false) {
        return ['text' => 'İptal', 'color' => 'danger', 'icon' => 'close-circle'];
    } else {
        return ['text' => ucfirst($status) ?: 'Bilinmiyor', 'color' => 'secondary', 'icon' => 'help-circle'];
    }
}

$statusInfo = getStatusInfo($order['durum']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sipariş Detayı #<?= $orderId ?> - GEMAS B2B Portal</title>
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
        .detail-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
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
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h2 class="mb-2">
                                    <i class="mdi mdi-file-document-outline me-2"></i>
                                    Sipariş Detayı #<?= $orderId ?>
                                </h2>
                                <p class="mb-0 opacity-90">
                                    <?= date('d.m.Y H:i', strtotime($order['olusturma_tarihi'])) ?>
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="badge bg-<?= $statusInfo['color'] ?>" style="font-size: 16px; padding: 10px 20px;">
                                    <i class="mdi mdi-<?= $statusInfo['icon'] ?> me-2"></i>
                                    <?= $statusInfo['text'] ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Sipariş Bilgileri -->
                        <div class="col-lg-8">
                            <!-- Ürünler -->
                            <div class="detail-card">
                                <h5 class="mb-4">
                                    <i class="mdi mdi-package-variant text-primary me-2"></i>Sipariş Ürünleri
                                </h5>
                                
                                <?php if (count($orderItems) > 0): ?>
                                    <?php foreach ($orderItems as $item): ?>
                                    <div class="order-item">
                                        <div class="row align-items-center">
                                            <div class="col-md-5">
                                                <strong><?= htmlspecialchars($item['kod'] ?? '-') ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($item['adi'] ?? '-') ?></small>
                                            </div>
                                            <div class="col-md-2 text-center">
                                                <span class="badge bg-secondary"><?= number_format(floatval($item['miktar'] ?? 0), 2) ?></span>
                                            </div>
                                            <div class="col-md-2 text-center">
                                                <strong>₺<?= number_format(floatval($item['liste'] ?? 0), 2, ',', '.') ?></strong>
                                            </div>
                                            <div class="col-md-3 text-end">
                                                <strong class="text-success">
                                                    ₺<?= number_format(floatval($item['liste'] ?? 0) * floatval($item['miktar'] ?? 0), 2, ',', '.') ?>
                                                </strong>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted text-center py-3">Sipariş ürünü bulunamadı.</p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Açıklama ve Notlar -->
                            <?php if (!empty($order['notes1']) || !empty($order['teslimyer'])): ?>
                            <div class="detail-card">
                                <h5 class="mb-4">
                                    <i class="mdi mdi-text text-primary me-2"></i>Ek Bilgiler
                                </h5>
                                
                                <?php if (!empty($order['teslimyer'])): ?>
                                <div class="mb-3">
                                    <strong><i class="mdi mdi-truck me-2"></i>Teslimat Yeri:</strong>
                                    <p class="mt-2 mb-0"><?= nl2br(htmlspecialchars($order['teslimyer'])) ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($order['notes1'])): ?>
                                <div>
                                    <strong><i class="mdi mdi-note-text me-2"></i>Notlar:</strong>
                                    <p class="mt-2 mb-0"><?= nl2br(htmlspecialchars($order['notes1'])) ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Özet -->
                        <div class="col-lg-4">
                            <div class="detail-card">
                                <h5 class="mb-4">
                                    <i class="mdi mdi-clipboard-text text-primary me-2"></i>Sipariş Özeti
                                </h5>
                                
                                <div class="mb-3 pb-3 border-bottom">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Sipariş No:</span>
                                        <strong>#<?= $orderId ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Tarih:</span>
                                        <strong><?= !empty($order['tekliftarihi']) ? date('d.m.Y H:i', strtotime($order['tekliftarihi'])) : '-' ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Durum:</span>
                                        <span class="badge bg-<?= $statusInfo['color'] ?>">
                                            <?= $statusInfo['text'] ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="mb-3 pb-3 border-bottom">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Ara Toplam:</span>
                                        <strong>₺<?= number_format(floatval($order['toplamtutar'] ?? 0), 2, ',', '.') ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>KDV:</span>
                                        <strong>₺<?= number_format(floatval($order['kdv'] ?? 0), 2, ',', '.') ?></strong>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between">
                                        <h5 class="mb-0">Genel Toplam:</h5>
                                        <h5 class="mb-0 text-success">
                                            ₺<?= number_format(floatval($order['geneltoplam'] ?? 0), 2, ',', '.') ?>
                                        </h5>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <a href="orders.php" class="btn btn-primary">
                                        <i class="mdi mdi-arrow-left me-2"></i>Siparişlerime Dön
                                    </a>
                                    <button class="btn btn-outline-primary" onclick="window.print()">
                                        <i class="mdi mdi-printer me-2"></i>Yazdır
                                    </button>
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
</body>
</html>

