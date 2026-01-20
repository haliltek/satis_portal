<?php
// Bayi Açık Hesap
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

// Açık hesap bilgisi
$acikHesap = floatval($company['acikhesap'] ?? 0);

$db->close();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Açık Hesap - GEMAS B2B Portal</title>
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
        .balance-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            text-align: center;
        }
        .balance-amount {
            font-size: 56px;
            font-weight: 700;
            margin: 20px 0;
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
                            <i class="mdi mdi-bank me-2"></i>Açık Hesap Bilgilerim
                        </h2>
                        <p class="mb-0 opacity-90">
                            Güncel açık hesap durumunuzu görüntüleyin
                        </p>
                    </div>
                    
                    <!-- Açık Hesap -->
                    <div class="row">
                        <div class="col-lg-8 mx-auto">
                            <div class="balance-card">
                                <div class="mb-3">
                                    <i class="mdi mdi-cash-multiple" style="font-size: 80px; color: #667eea;"></i>
                                </div>
                                <h3 class="text-muted mb-3">Toplam Açık Hesap</h3>
                                <div class="balance-amount text-<?= $acikHesap > 0 ? 'danger' : 'success' ?>">
                                    ₺<?= number_format($acikHesap, 2, ',', '.') ?>
                                </div>
                                <div class="mt-4">
                                    <?php if ($acikHesap > 0): ?>
                                        <div class="alert alert-warning">
                                            <i class="mdi mdi-alert-circle me-2"></i>
                                            <strong>Dikkat:</strong> Ödemeniz gereken bakiye bulunmaktadır.
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-success">
                                            <i class="mdi mdi-check-circle me-2"></i>
                                            <strong>Harika!</strong> Borç bakiyeniz bulunmamaktadır.
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="row mt-4">
                                    <div class="col-md-4">
                                        <div class="p-3" style="background: #f8f9fa; border-radius: 10px;">
                                            <small class="text-muted d-block">Cari Kodu</small>
                                            <strong><?= htmlspecialchars($company['s_arp_code']) ?></strong>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="p-3" style="background: #f8f9fa; border-radius: 10px;">
                                            <small class="text-muted d-block">Ödeme Planı</small>
                                            <strong>
                                                <?php 
                                                $payplan = trim(($company['payplan_code'] ?? '') . ' - ' . ($company['payplan_def'] ?? ''));
                                                echo htmlspecialchars($payplan !== ' - ' ? $payplan : '-');
                                                ?>
                                            </strong>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="p-3" style="background: #f8f9fa; border-radius: 10px;">
                                            <small class="text-muted d-block">Ticari Grup</small>
                                            <strong><?= htmlspecialchars($company['trading_grp'] ?? '-') ?></strong>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <a href="payments.php" class="btn btn-primary btn-lg">
                                        <i class="mdi mdi-cash-check me-2"></i>Ödeme Yap
                                    </a>
                                    <a href="invoices.php" class="btn btn-outline-primary btn-lg ms-2">
                                        <i class="mdi mdi-file-document me-2"></i>Faturalarım
                                    </a>
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

