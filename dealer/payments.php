<?php
// Bayi Ödemeler
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
$cariCode = $_SESSION['dealer_cari_code'] ?? '';

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
    <title>Ödemelerim - GEMAS B2B Portal</title>
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
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            text-align: center;
        }
        .balance-amount {
            font-size: 48px;
            font-weight: 700;
            margin: 20px 0;
        }
        .info-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
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
                            <div class="col-md-12">
                                <h2 class="mb-2">
                                    <i class="mdi mdi-cash-check me-2"></i>Ödemelerim
                                </h2>
                                <p class="mb-0 opacity-90">
                                    Ödeme bilgilerinizi ve açık hesap durumunuzu görüntüleyin
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Açık Hesap Kartı -->
                    <div class="row">
                        <div class="col-lg-6 mx-auto">
                            <div class="balance-card">
                                <div class="mb-3">
                                    <i class="mdi mdi-cash-multiple" style="font-size: 64px; color: #667eea;"></i>
                                </div>
                                <h4 class="text-muted mb-2">Açık Hesap Durumu</h4>
                                <div class="balance-amount text-<?= $acikHesap > 0 ? 'danger' : 'success' ?>">
                                    ₺<?= number_format($acikHesap, 2, ',', '.') ?>
                                </div>
                                <p class="text-muted mb-0">
                                    <?php if ($acikHesap > 0): ?>
                                        <i class="mdi mdi-alert-circle text-warning me-2"></i>
                                        Ödemeniz gereken bakiye bulunmaktadır.
                                    <?php else: ?>
                                        <i class="mdi mdi-check-circle text-success me-2"></i>
                                        Borç bakiyeniz bulunmamaktadır.
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Ödeme Bilgileri -->
                    <div class="row">
                        <div class="col-lg-8 mx-auto">
                            <div class="info-card">
                                <h5 class="mb-4">
                                    <i class="mdi mdi-information-outline text-primary me-2"></i>
                                    Ödeme Bilgileri
                                </h5>
                                
                                <div class="alert alert-info">
                                    <i class="mdi mdi-lightbulb-on-outline me-2"></i>
                                    <strong>Bilgilendirme:</strong> Ödeme işlemleri için lütfen finans departmanımız ile iletişime geçiniz.
                                </div>
                                
                                <div class="row mt-4">
                                    <div class="col-md-6 mb-3">
                                        <div class="d-flex align-items-center p-3" style="background: #f8f9fa; border-radius: 10px;">
                                            <div class="me-3">
                                                <i class="mdi mdi-calendar-clock" style="font-size: 32px; color: #667eea;"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted">Ödeme Planı</small>
                                                <div class="fw-bold">
                                                    <?php 
                                                    $payplan = trim(($company['payplan_code'] ?? '') . ' - ' . ($company['payplan_def'] ?? ''));
                                                    echo htmlspecialchars($payplan !== ' - ' ? $payplan : 'Tanımlı Değil');
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <div class="d-flex align-items-center p-3" style="background: #f8f9fa; border-radius: 10px;">
                                            <div class="me-3">
                                                <i class="mdi mdi-bank" style="font-size: 32px; color: #667eea;"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted">Vergi Dairesi</small>
                                                <div class="fw-bold"><?= htmlspecialchars($company['s_vd'] ?? '-') ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <div class="d-flex align-items-center p-3" style="background: #f8f9fa; border-radius: 10px;">
                                            <div class="me-3">
                                                <i class="mdi mdi-numeric" style="font-size: 32px; color: #667eea;"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted">Vergi No</small>
                                                <div class="fw-bold"><?= htmlspecialchars($company['s_vno'] ?? '-') ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <div class="d-flex align-items-center p-3" style="background: #f8f9fa; border-radius: 10px;">
                                            <div class="me-3">
                                                <i class="mdi mdi-barcode" style="font-size: 32px; color: #667eea;"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted">Cari Kodu</small>
                                                <div class="fw-bold"><?= htmlspecialchars($company['s_arp_code']) ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-4 p-4" style="background: linear-gradient(135deg, #667eea10, #764ba210); border-radius: 10px;">
                                    <h6 class="mb-3">
                                        <i class="mdi mdi-phone me-2"></i>İletişim
                                    </h6>
                                    <p class="mb-2">
                                        <strong>Finans Departmanı:</strong><br>
                                        <i class="mdi mdi-phone me-2"></i>+90 (XXX) XXX XX XX<br>
                                        <i class="mdi mdi-email me-2"></i>finans@gemas.com
                                    </p>
                                    <small class="text-muted">
                                        Ödeme ve fatura konularında bizimle iletişime geçebilirsiniz.
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
</body>
</html>

