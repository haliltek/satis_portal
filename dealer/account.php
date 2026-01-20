<?php
// Bayi Cari Bilgileri
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

if (!$company) {
    die('Şirket bilgisi bulunamadı.');
}

$db->close();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cari Bilgilerim - GEMAS B2B Portal</title>
    <link rel="shortcut icon" href="../assets/images/favicon.ico">
    <?php include "includes/styles.php"; ?>
    <style>
        /* Ek stiller varsa buraya */
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
                                    <i class="mdi mdi-account-box me-2"></i>Cari Bilgilerim
                                </h2>
                                <p class="mb-0 opacity-90">
                                    Şirketinize ait detaylı cari hesap bilgileri
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="badge bg-light text-dark badge-custom">
                                    <i class="mdi mdi-barcode me-2"></i><?= htmlspecialchars($company['s_arp_code']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Genel Bilgiler -->
                        <div class="col-lg-6">
                            <div class="info-card">
                                <h5><i class="mdi mdi-information me-2"></i>Genel Bilgiler</h5>
                                
                                <div class="info-row">
                                    <div class="info-label">
                                        <i class="mdi mdi-office-building text-primary me-2"></i>Ünvan
                                    </div>
                                    <div class="info-value">
                                        <strong><?= htmlspecialchars($company['s_adi']) ?></strong>
                                    </div>
                                </div>
                                
                                <div class="info-row">
                                    <div class="info-label">
                                        <i class="mdi mdi-barcode text-primary me-2"></i>Cari Kodu
                                    </div>
                                    <div class="info-value">
                                        <span class="badge bg-primary"><?= htmlspecialchars($company['s_arp_code']) ?></span>
                                    </div>
                                </div>
                                
                                <div class="info-row">
                                    <div class="info-label">
                                        <i class="mdi mdi-domain text-primary me-2"></i>Şirket Türü
                                    </div>
                                    <div class="info-value">
                                        <?= htmlspecialchars($company['s_turu'] ?? '-') ?>
                                    </div>
                                </div>
                                
                                <div class="info-row">
                                    <div class="info-label">
                                        <i class="mdi mdi-account-tie text-primary me-2"></i>Yetkili Kişi
                                    </div>
                                    <div class="info-value">
                                        <?= htmlspecialchars($company['yetkili'] ?? '-') ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($company['yetkili2'])): ?>
                                <div class="info-row">
                                    <div class="info-label">
                                        <i class="mdi mdi-account-tie text-primary me-2"></i>2. Yetkili
                                    </div>
                                    <div class="info-value">
                                        <?= htmlspecialchars($company['yetkili2']) ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="info-row">
                                    <div class="info-label">
                                        <i class="mdi mdi-tag text-primary me-2"></i>Kategori
                                    </div>
                                    <div class="info-value">
                                        <?= htmlspecialchars($company['kategori'] ?? '-') ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Vergi ve Mali Bilgiler -->
                        <div class="col-lg-6">
                            <div class="info-card">
                                <h5><i class="mdi mdi-file-document me-2"></i>Vergi ve Mali Bilgiler</h5>
                                
                                <div class="info-row">
                                    <div class="info-label">
                                        <i class="mdi mdi-bank text-success me-2"></i>Vergi Dairesi
                                    </div>
                                    <div class="info-value">
                                        <?= htmlspecialchars($company['s_vd'] ?? '-') ?>
                                    </div>
                                </div>
                                
                                <div class="info-row">
                                    <div class="info-label">
                                        <i class="mdi mdi-numeric text-success me-2"></i>Vergi No
                                    </div>
                                    <div class="info-value">
                                        <strong><?= htmlspecialchars($company['s_vno'] ?? '-') ?></strong>
                                    </div>
                                </div>
                                
                                <div class="info-row">
                                    <div class="info-label">
                                        <i class="mdi mdi-cash-multiple text-success me-2"></i>Açık Hesap
                                    </div>
                                    <div class="info-value">
                                        <strong class="text-<?= floatval($company['acikhesap']) > 0 ? 'danger' : 'success' ?>">
                                            ₺<?= number_format(floatval($company['acikhesap'] ?? 0), 2, ',', '.') ?>
                                        </strong>
                                    </div>
                                </div>
                                
                                <div class="info-row">
                                    <div class="info-label">
                                        <i class="mdi mdi-calendar-clock text-success me-2"></i>Ödeme Planı
                                    </div>
                                    <div class="info-value">
                                        <?php 
                                        $payplan = trim(($company['payplan_code'] ?? '') . ' - ' . ($company['payplan_def'] ?? ''));
                                        echo htmlspecialchars($payplan !== ' - ' ? $payplan : '-');
                                        ?>
                                    </div>
                                </div>
                                
                                <div class="info-row">
                                    <div class="info-label">
                                        <i class="mdi mdi-group text-success me-2"></i>Ticari Grup
                                    </div>
                                    <div class="info-value">
                                        <?= htmlspecialchars($company['trading_grp'] ?? '-') ?>
                                    </div>
                                </div>
                                
                                <div class="info-row">
                                    <div class="info-label">
                                        <i class="mdi mdi-code-brackets text-success me-2"></i>Logo Şirket Kodu
                                    </div>
                                    <div class="info-value">
                                        <span class="badge bg-info"><?= htmlspecialchars($company['logo_company_code'] ?? '-') ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- İletişim Bilgileri -->
                        <div class="col-lg-6">
                            <div class="info-card">
                                <h5><i class="mdi mdi-phone me-2"></i>İletişim Bilgileri</h5>
                                
                                <div class="info-row">
                                    <div class="info-label">
                                        <i class="mdi mdi-map-marker text-info me-2"></i>Adres
                                    </div>
                                    <div class="info-value">
                                        <?= nl2br(htmlspecialchars($company['s_adresi'] ?? '-')) ?>
                                    </div>
                                </div>
                                
                                <div class="info-row">
                                    <div class="info-label">
                                        <i class="mdi mdi-city text-info me-2"></i>İl / İlçe
                                    </div>
                                    <div class="info-value">
                                        <?= htmlspecialchars(($company['s_il'] ?? '-') . ' / ' . ($company['s_ilce'] ?? '-')) ?>
                                    </div>
                                </div>
                                
                                <div class="info-row">
                                    <div class="info-label">
                                        <i class="mdi mdi-earth text-info me-2"></i>Ülke
                                    </div>
                                    <div class="info-value">
                                        <?= htmlspecialchars($company['s_country'] ?? '-') ?>
                                        <?php if (!empty($company['s_country_code'])): ?>
                                            <span class="badge bg-secondary ms-2"><?= htmlspecialchars($company['s_country_code']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="info-row">
                                    <div class="info-label">
                                        <i class="mdi mdi-mailbox text-info me-2"></i>Posta Kodu
                                    </div>
                                    <div class="info-value">
                                        <?= htmlspecialchars($company['s_postal_code'] ?? '-') ?>
                                    </div>
                                </div>
                                
                                <div class="info-row">
                                    <div class="info-label">
                                        <i class="mdi mdi-phone text-info me-2"></i>Telefon
                                    </div>
                                    <div class="info-value">
                                        <a href="tel:<?= htmlspecialchars($company['s_telefonu'] ?? '') ?>">
                                            <?= htmlspecialchars($company['s_telefonu'] ?? '-') ?>
                                        </a>
                                    </div>
                                </div>
                                
                                <?php if (!empty($company['s_telefonu2'])): ?>
                                <div class="info-row">
                                    <div class="info-label">
                                        <i class="mdi mdi-phone text-info me-2"></i>Telefon 2
                                    </div>
                                    <div class="info-value">
                                        <a href="tel:<?= htmlspecialchars($company['s_telefonu2']) ?>">
                                            <?= htmlspecialchars($company['s_telefonu2']) ?>
                                        </a>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="info-row">
                                    <div class="info-label">
                                        <i class="mdi mdi-email text-info me-2"></i>E-posta
                                    </div>
                                    <div class="info-value">
                                        <a href="mailto:<?= htmlspecialchars($company['mail'] ?? '') ?>">
                                            <?= htmlspecialchars($company['mail'] ?? '-') ?>
                                        </a>
                                    </div>
                                </div>
                                
                                <?php if (!empty($company['s_web'])): ?>
                                <div class="info-row">
                                    <div class="info-label">
                                        <i class="mdi mdi-web text-info me-2"></i>Web Sitesi
                                    </div>
                                    <div class="info-value">
                                        <a href="<?= htmlspecialchars($company['s_web']) ?>" target="_blank">
                                            <?= htmlspecialchars($company['s_web']) ?>
                                        </a>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Ek Bilgiler -->
                        <div class="col-lg-6">
                            <div class="info-card">
                                <h5><i class="mdi mdi-information-outline me-2"></i>Ek Bilgiler</h5>
                                
                                <?php if (!empty($company['s_auxil_code'])): ?>
                                <div class="info-row">
                                    <div class="info-label">
                                        <i class="mdi mdi-code text-warning me-2"></i>Auxiliary Code
                                    </div>
                                    <div class="info-value">
                                        <?= htmlspecialchars($company['s_auxil_code']) ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($company['s_auth_code'])): ?>
                                <div class="info-row">
                                    <div class="info-label">
                                        <i class="mdi mdi-shield-check text-warning me-2"></i>Authorization Code
                                    </div>
                                    <div class="info-value">
                                        <?= htmlspecialchars($company['s_auth_code']) ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="info-row">
                                    <div class="info-label">
                                        <i class="mdi mdi-account-box text-warning me-2"></i>Hesap Tipi
                                    </div>
                                    <div class="info-value">
                                        <?php
                                        $accountType = 'Bilinmiyor';
                                        switch ($company['account_type'] ?? 3) {
                                            case 1:
                                                $accountType = 'Müşteri';
                                                break;
                                            case 2:
                                                $accountType = 'Tedarikçi';
                                                break;
                                            case 3:
                                                $accountType = 'Müşteri/Tedarikçi';
                                                break;
                                        }
                                        echo htmlspecialchars($accountType);
                                        ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($company['s_corresp_lang'])): ?>
                                <div class="info-row">
                                    <div class="info-label">
                                        <i class="mdi mdi-translate text-warning me-2"></i>İletişim Dili
                                    </div>
                                    <div class="info-value">
                                        <?= htmlspecialchars($company['s_corresp_lang']) ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($company['internal_reference'])): ?>
                                <div class="info-row">
                                    <div class="info-label">
                                        <i class="mdi mdi-link text-warning me-2"></i>Internal Reference
                                    </div>
                                    <div class="info-value">
                                        <?= htmlspecialchars($company['internal_reference']) ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="info-row">
                                    <div class="info-label">
                                        <i class="mdi mdi-calendar text-warning me-2"></i>Sipariş Sıklığı
                                    </div>
                                    <div class="info-value">
                                        <?= htmlspecialchars($company['cl_ord_freq'] ?? '-') ?>
                                    </div>
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

