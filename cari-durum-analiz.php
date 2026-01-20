<?php
// cari-durum-analiz.php
require_once "include/fonksiyon.php";
oturumkontrol();

if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Bayi') {
    header("Location: index.php");
    exit;
}

// Admin Permission Check (Raporlar)
$personel_id = $_SESSION['yonetici_id'] ?? 0;
$hasAccess = false;
if($personel_id) {
    $adminQuery = mysqli_query($db, "SELECT bolum FROM yonetici WHERE yonetici_id = '$personel_id'");
    if($adminQuery && mysqli_num_rows($adminQuery) > 0) {
        $adminRow = mysqli_fetch_array($adminQuery);
        $departmanKodu = $adminRow['bolum'] ?? '';
        
        if ($departmanKodu) {
            $depQuery = mysqli_query($db, "SELECT id FROM departmanlar WHERE departman = '$departmanKodu'");
            if($depQuery && mysqli_num_rows($depQuery) > 0) {
                $depRow = mysqli_fetch_array($depQuery);
                $departmanId = $depRow['id'] ?? 0;

                if ($departmanId) {
                    $authQuery = mysqli_query($db, "SELECT raporlar FROM yetkiler WHERE departmanid = '$departmanId'");
                    if($authQuery && mysqli_num_rows($authQuery) > 0) {
                        $authRow = mysqli_fetch_array($authQuery);
                        if (($authRow['raporlar'] ?? '') === 'Evet') {
                            $hasAccess = true;
                        }
                    }
                }
            }
        }
    }
}

if (!$hasAccess) {
    header("Location: anasayfa.php");
    exit;
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8" />
    <title>Cari Durum Analizi | <?php echo $sistemayar["title"]; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <link href="assets/css/icons.min.css" rel="stylesheet" />
    <link href="assets/css/app.min.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

    <style>
        .select2-container--bootstrap-5 .select2-selection { border-radius: 0.25rem; }
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
                
                <!-- Page Title & Selected Customer Header -->
                <div class="row mb-3 align-items-center">
                    <div class="col-md-6">
                        <h4 class="mb-0 text-primary fw-bold"><i class="bx bx-pie-chart-alt-2 me-1"></i> Cari Durum & Risk Analizi</h4>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h5 class="mb-0 text-muted" id="headerCustomerInfo"></h5>
                    </div>
                </div>

                <!-- Filter Bar -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-body p-3 bg-light rounded">
                                <form id="analysisForm" onsubmit="return false;">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-5">
                                            <label for="musteri" class="form-label fw-semibold text-muted small">Cari Seçimi</label>
                                            <select class="form-select select2" id="musteri" name="musteri" required>
                                                <option value="">Cari Ara...</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                             <div class="form-check form-switch mb-2">
                                                <input class="form-check-input" type="checkbox" id="pazarSwitch" name="pazar_tipi" value="yurtdisi">
                                                <label class="form-check-label" for="pazarSwitch">Yurtdışı Pazarı</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <button type="button" id="btnAnaliz" class="btn btn-primary px-4" disabled>
                                                <i class="bx bx-search-alt me-1"></i> Analizi Getir
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="dashboardArea" style="display:none;">
                    
                    <!-- KPI Cards (Turnover, Risk, Payments, Overdue) -->
                    <div class="row mb-4">
                        <!-- 1. Toplam Ciro (Bu Yıl) - YENİ -->
                        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="avatar-sm me-3">
                                            <span class="avatar-title bg-primary bg-opacity-10 rounded-circle text-primary">
                                                <i class="bx bx-bar-chart-alt-2 fs-3"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 text-muted">Toplam Ciro (Bu Yıl)</h6>
                                            <small class="text-primary" id="kpiTurnoverYearLabel">-</small>
                                        </div>
                                    </div>
                                    <h4 class="mb-0 fw-bold text-primary" id="kpiTotalTurnoverYear">0,00 ₺</h4>
                                    <p class="text-muted small mt-2 mb-0">Faturalanmış Satışlar</p>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Risk Durumu -->
                        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="avatar-sm me-3">
                                            <span class="avatar-title bg-warning bg-opacity-25 rounded-circle text-warning">
                                                <i class="bx bx-shield-quarter fs-3"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 text-muted">Cari Risk Durumu</h6>
                                            <small class="text-muted" id="kpiRiskLimitLabel">Limit: -</small>
                                        </div>
                                    </div>
                                    <h4 class="mb-2 fw-bold text-dark" id="kpiCurrentBalance">-</h4>
                                    
                                    <div class="progress mt-2" style="height: 6px;">
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: 0%" id="kpiRiskBar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <div class="d-flex justify-content-between mt-1">
                                        <small class="text-muted" id="kpiRiskRatio">%0</small>
                                        <small class="text-danger fw-bold" id="kpiRiskWarning" style="display:none;">Limit Aşımı!</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Tahsilat (Bu Ay) -->
                        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="avatar-sm me-3">
                                            <span class="avatar-title bg-success bg-opacity-10 rounded-circle text-success">
                                                <i class="bx bx-wallet fs-3"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 text-muted">Toplam Tahsilat</h6>
                                            <small class="text-success">Tüm Hareketler</small>
                                        </div>
                                    </div>
                                    <h4 class="mb-0 fw-bold text-success" id="kpiMonthCollection">0,00 ₺</h4>
                                    <p class="text-muted small mt-2 mb-0">Genel Tahsilat Toplamı</p>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Toplam Geçikmiş Borç -->
                        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="avatar-sm me-3">
                                            <span class="avatar-title bg-danger bg-opacity-10 rounded-circle text-danger">
                                                <i class="bx bx-error text-danger fs-3"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 text-muted">Geçikmiş Borç</h6>
                                            <small class="text-danger">60+ Gün</small>
                                        </div>
                                    </div>
                                    <h4 class="mb-0 fw-bold text-danger" id="kpiTotalOverdue">0,00 ₺</h4>
                                    <p class="text-muted small mt-2 mb-0">Riskli bakiye tutarı</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 3-Year Sales Chart Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-transparent border-bottom py-3">
                                    <h5 class="card-title mb-0 d-flex align-items-center">
                                        <i class="bx bx-bar-chart-alt-2 text-primary me-2"></i> Satış Analizi (Son 3 Yıl)
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div id="turnoverChart" style="min-height: 350px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- NEW: Ticari Bilgiler (Manual Data) Section -->
                    <div class="row mb-4" id="commercialInfoRow" style="display:none;">
                        <div class="col-12">
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-soft-warning border-bottom py-3 d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0 text-warning d-flex align-items-center">
                                        <i class="bx bx-briefcase-alt-2 me-2"></i> Ticari Bilgiler & Hedefler
                                    </h5>
                                    <button class="btn btn-sm btn-warning" id="btnEditCommercial">
                                        <i class="bx bx-edit"></i> Düzenle
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3 border-end">
                                            <label class="text-muted small">Ciro Hedefi (Yıllık)</label>
                                            <h5 class="fw-bold text-dark" id="dispCiroHedefi">-</h5>
                                        </div>
                                        <div class="col-md-3 border-end">
                                            <label class="text-muted small">Anlaşılan İskonto</label>
                                            <h5 class="fw-bold text-dark" id="dispAnlasilanIskonto">-</h5>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted small">Özel Risk Notu</label>
                                            <div class="p-2 bg-light rounded text-secondary" id="dispOzelRiskNotu" style="min-height: 40px; font-style: italic;">
                                                -
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Columns (Turnover & Overdue) -->
                    <div class="row">
                        <!-- Left Col: Turnover -->
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-header bg-transparent border-bottom py-3">
                                    <h5 class="card-title mb-0 d-flex align-items-center">
                                        <i class="bx bx-bar-chart-alt-2 text-primary me-2"></i> Satış Analizi (Ciro)
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-scroller" style="max-height: 400px; overflow-y: auto !important;">
                                        <table class="table table-hover align-middle mb-0 table-sm">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th style="width: 40px;"></th>
                                                    <th>Yıl</th>
                                                    <th class="text-end pe-3">Toplam Ciro</th>
                                                </tr>
                                            </thead>
                                            <tbody id="turnoverTableBody" class="border-top-0"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Col: Overdue -->
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-header bg-transparent border-bottom py-3 d-flex justify-content-between align-items-center click-pointer" 
                                     data-bs-toggle="collapse" data-bs-target="#overdueCardWrapper" aria-expanded="true">
                                    <h5 class="card-title mb-0 d-flex align-items-center text-danger">
                                        <i class="bx bx-alarm-exclamation me-2"></i> Riskli Faturalar (60+ Gün)
                                    </h5>
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-danger rounded-pill me-2 px-3" id="overdueCountBadge">0 Adet</span>
                                        <i class="bx bx-chevron-down fs-4 text-muted toggle-rotate"></i>
                                    </div>
                                </div>
                                
                                <div class="collapse show" id="overdueCardWrapper">
                                    <div class="card-body p-0">
                                        <div class="table-scroller" style="max-height: 400px; overflow-y: auto !important;">
                                            <table class="table table-hover align-middle mb-0 table-sm" style="font-size: 0.85rem;">
                                                <thead class="table-light sticky-top">
                                                    <tr>
                                                        <th class="ps-3"><i class="bx bx-hash"></i> Fatura</th>
                                                        <th>Vade</th>
                                                        <th class="text-center">Gün</th>
                                                        <th class="text-end pe-3">Bakiye</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="overdueTableBody"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Satır 2: Ürün Analizi -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow-sm border-0 mb-4 h-100">
                                <div class="card-header bg-transparent border-bottom py-3">
                                    <h5 class="card-title mb-0 d-flex align-items-center">
                                        <i class="bx bx-package text-info me-2"></i> En Çok Alınan Ürünler (Top 10)
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0 table-sm">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="ps-4">Ürün Kodu</th>
                                                    <th>Ürün Adı</th>
                                                    <th class="text-center">Toplam Adet</th>
                                                    <th class="text-end pe-4">Toplam Tutar</th>
                                                </tr>
                                            </thead>
                                            <tbody id="topProductsTableBody"></tbody>
                                        </table>
                                    </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                    <!-- NEW: Return Invoices (İade Faturaları) -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-transparent border-bottom py-3 d-flex justify-content-between align-items-center"
                                     data-bs-toggle="collapse" data-bs-target="#returnsCardWrapper" aria-expanded="true" style="cursor:pointer;">
                                    <h5 class="card-title mb-0 d-flex align-items-center text-danger">
                                        <i class="bx bx-undo me-2"></i> İade Edilen Ürünler
                                    </h5>
                                    <div>
                                        <span class="badge bg-danger rounded-pill me-2" id="kpiTotalReturns">0,00 ₺</span>
                                        <i class="bx bx-chevron-down fs-4 text-muted toggle-rotate rotated"></i>
                                    </div>
                                </div>
                                <div class="collapse show" id="returnsCardWrapper">
                                    <div class="card-body p-0">
                                        <div class="table-scroller" style="max-height: 300px; overflow-y: auto !important;">
                                            <table class="table table-hover align-middle mb-0 table-sm">
                                                <thead class="table-light sticky-top">
                                                    <!-- Totals Row -->
                                                    <tr class="bg-white text-danger fw-bold border-bottom">
                                                        <td colspan="3" class="text-end pe-3">TOPLAM:</td>
                                                        <td class="text-center" id="retTotalAdet">0</td>
                                                        <td class="text-end pe-4" id="retTotalTutar">0,00 ₺</td>
                                                    </tr>
                                                    <!-- Column Headers -->
                                                    <tr>
                                                        <th class="ps-3">Tarih</th>
                                                        <th>Fatura No</th>
                                                        <th>Ürün</th>
                                                        <th class="text-center">Adet</th>
                                                        <th class="text-end pe-4">Tutar</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="returnsTableBody"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- NEW: Cari Hesap Ekstresi (Statement) -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow-sm border-0 mb-4 h-100">
                                <div class="card-header bg-transparent border-bottom py-3 d-flex justify-content-between align-items-center"
                                     data-bs-toggle="collapse" data-bs-target="#statementCardWrapper" aria-expanded="true" style="cursor:pointer;">
                                    <h5 class="card-title mb-0 d-flex align-items-center">
                                        <i class="bx bx-list-ul text-secondary me-2"></i> Cari Hesap Ekstresi (Tüm Hareketler)
                                    </h5>
                                    <i class="bx bx-chevron-down fs-4 text-muted toggle-rotate"></i>
                                </div>
                                <div class="collapse show" id="statementCardWrapper">
                                    <div class="card-body p-0">
                                        <div class="table-scroller" style="max-height: 500px; overflow-y: auto !important;">
                                            <table class="table table-hover align-middle mb-0 table-sm" style="font-size: 0.85rem;">
                                                <thead class="table-light sticky-top" style="z-index: 5;">
                                                    <!-- Totals Row -->
                                                    <tr class="bg-light border-bottom border-2">
                                                        <th colspan="4" class="text-end text-muted small pe-3 py-2">TOPLAM:</th>
                                                        <th class="text-end fw-bold text-dark py-2" id="stmtTotalDebt">0,00 ₺</th>
                                                        <th class="text-end fw-bold text-success py-2" id="stmtTotalCredit">0,00 ₺</th>
                                                        <th class="text-end fw-bold text-primary py-2" id="stmtLastBalance">0,00 ₺</th>
                                                    </tr>
                                                    <!-- Headers -->
                                                    <tr>
                                                        <th class="ps-3" style="width: 100px;">Tarih</th>
                                                        <th style="width: 120px;">Fiş No</th>
                                                        <th style="width: 180px;">İşlem Türü</th>
                                                        <th>Açıklama</th>
                                                        <th class="text-end" style="width: 120px;">Borç</th>
                                                        <th class="text-end" style="width: 120px;">Alacak</th>
                                                        <th class="text-end pe-3" style="width: 130px;">Bakiye</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="statementTableBody"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <style>
                    .toggle-rotate { transition: transform 0.3s ease; }
                    [aria-expanded="false"] .toggle-rotate { transform: rotate(-90deg); }
                    .table-sm td, .table-sm th { padding: 0.5rem 0.5rem; }
                    .click-pointer { cursor: pointer; }
                </style>

                <div id="emptyState" class="text-center py-5">
                    <div class="mb-3">
                         <i class="bx bx-pie-chart-alt text-muted" style="font-size: 4rem; opacity: 0.3;"></i>
                    </div>
                    <h5 class="text-muted">Analiz Raporu</h5>
                    <p class="text-muted small">Lütfen yukarıdan bir cari seçerek analizi başlatın.</p>
                </div>

            </div>
        </div>
        <?php include "menuler/footer.php"; ?>
    </div>
</div>

<!-- Edit Commercial Info Modal -->
<div class="modal fade" id="modalEditCommercial" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">Ticari Bilgileri Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formCommercialData">
                    <input type="hidden" id="editSirketId" name="sirket_id">
                    <div class="mb-3">
                        <label class="form-label">Ciro Hedefi (Yıllık)</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="editCiroHedefi" name="ciro_hedefi" placeholder="0.00">
                            <span class="input-group-text">₺</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Anlaşılan İskonto (%)</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="editAnlasilanIskonto" name="anlasilan_iskonto" step="0.01" placeholder="0.00">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Özel Risk Notu</label>
                        <textarea class="form-control" id="editOzelRiskNotu" name="ozel_risk_notu" rows="4" placeholder="Müşteri ile ilgili özel notlar..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" id="btnSaveCommercial">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="assets/libs/jquery/jquery.min.js"></script>
<script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/tr.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
$(document).ready(function() {
    // Select2 Initialization
    $('#musteri').select2({
        theme: 'bootstrap-5',
        language: 'tr',
        placeholder: 'Cari Kodu veya Ünvanı ile arayın...',
        allowClear: true,
        minimumInputLength: 2,
        ajax: {
            url: 'musteri-search.php',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                var pazarTipi = $('#pazarSwitch').is(':checked') ? 'yurtdisi' : 'yurtici';
                return {
                    q: params.term || '',
                    page: params.page || 1,
                    pazar_tipi: pazarTipi
                };
            },
            processResults: function(data, params) {
                params.page = params.page || 1;
                return {
                    results: data.results,
                    pagination: { more: data.pagination.more }
                };
            },
            cache: true
        }
    });

    // Enable/Disable Button based on Selection
    $('#musteri').on('select2:select', function(e) {
        $('#btnAnaliz').prop('disabled', false).removeClass('btn-secondary').addClass('btn-primary');
    });

    $('#musteri').on('select2:unselect', function(e) {
        $('#btnAnaliz').prop('disabled', true);
        resetDashboard();
    });

    $('#pazarSwitch').on('change', function() {
        $('#musteri').val(null).trigger('change');
    });

    $('#btnAnaliz').click(function() {
        var data = $('#musteri').select2('data')[0];
        if(!data) return;
        
        loadDashboard(data.id, data.text);
    });

    // Collapse icon rotation logic
    $(document).on('show.bs.collapse', '.collapse', function () {
        $(this).parent().find('.toggle-rotate').css('transform', 'rotate(0deg)');
    });
    $(document).on('hide.bs.collapse', '.collapse', function () {
        $(this).parent().find('.toggle-rotate').css('transform', 'rotate(-90deg)');
    });


    function resetDashboard() {
        $('#dashboardArea').hide();
        $('#emptyState').show();
        $('#headerCustomerInfo').text('');
        
        // Reset KPIs
        $('#kpiCurrentBalance').text('-');
        $('#kpiRiskLimitLabel').text('Limit: -');
        $('#kpiRiskBar').css('width', '0%').removeClass('bg-danger bg-warning bg-success');
        $('#kpiRiskRatio').text('%0');
        $('#kpiRiskWarning').hide();
        $('#kpiMonthCollection').text('0,00 ₺');
        $('#kpiTotalOverdue').text('0,00 ₺');
    }

    function loadDashboard(sirketId, fullText) {
        $('#emptyState').hide();
        $('#dashboardArea').show();
        $('#headerCustomerInfo').text(fullText);

        // Reset Tables & Loaders
        $('#turnoverTableBody').html('<tr><td colspan="3" class="text-center py-4"><div class="spinner-border text-primary spinner-border-sm"></div> Veriler çekiliyor...</td></tr>');
        $('#overdueTableBody').html('<tr><td colspan="4" class="text-center py-4"><div class="spinner-border text-danger spinner-border-sm"></div> Risk analizi yapılıyor...</td></tr>');
        $('#overdueCountBadge').text('0 Adet');
        
        // Reset Financial Loaders
        $('#kpiCurrentBalance, #kpiMonthCollection').html('<div class="spinner-border spinner-border-sm"></div>');

        // 1. FINANCIALS (Risk & Collections & Commercial Data)
        $.ajax({
            url: 'api/get_customer_analysis.php',
            data: { sirket_id: sirketId, type: 'financials' },
            dataType: 'json',
            success: function(resp) {
                if(resp.success && resp.data) {
                    var data = resp.data;
                    
                    // Risk & Balance
                    var balance = parseFloat(data.GuncelBakiye || 0);
                    var limit = parseFloat(data.RiskLimiti || 0);
                    var collection = parseFloat(data.BuYilTahsilat || 0); // Changed to Yearly

                    // --- Manual Commercial Data ---
                    var ciroHedefi = parseFloat(data.CiroHedefi || 0);
                    var anlasilanIskonto = parseFloat(data.AnlasilanIskonto || 0);
                    var ozelRiskNotu = data.OzelRiskNotu || '';
                    
                    // Display Manual Data
                    $('#commercialInfoRow').show();
                    $('#dispCiroHedefi').text(ciroHedefi > 0 ? formatCurrency(ciroHedefi) : '-');
                    $('#dispAnlasilanIskonto').text(anlasilanIskonto > 0 ? '%' + anlasilanIskonto.toFixed(2) : '-');
                    $('#dispOzelRiskNotu').text(ozelRiskNotu ? ozelRiskNotu : '-');
                    
                    // Populate Edit Modal
                    $('#editSirketId').val(sirketId);
                    $('#editCiroHedefi').val(ciroHedefi > 0 ? ciroHedefi : '');
                    $('#editAnlasilanIskonto').val(anlasilanIskonto > 0 ? anlasilanIskonto : '');
                    $('#editOzelRiskNotu').val(ozelRiskNotu);

                    // KPI Cards
                    $('#kpiCurrentBalance').text(formatCurrency(balance));
                    $('#kpiMonthCollection').text(formatCurrency(collection));
                    
                    if(limit > 0) {
                        $('#kpiRiskLimitLabel').text('Limit: ' + formatCurrency(limit));
                        var ratio = (balance / limit) * 100;
                        if(ratio > 100) ratio = 100;
                        if(ratio < 0) ratio = 0; // Alacaklı ise risk 0

                        $('#kpiRiskBar').css('width', ratio + '%').attr('aria-valuenow', ratio);
                        $('#kpiRiskRatio').text('%' + ratio.toFixed(1));

                        // Renklendirme
                        $('#kpiRiskBar').removeClass('bg-success bg-warning bg-danger');
                        if(ratio >= 90) {
                            $('#kpiRiskBar').addClass('bg-danger');
                            $('#kpiRiskWarning').show();
                        } else if(ratio >= 70) {
                            $('#kpiRiskBar').addClass('bg-warning');
                            $('#kpiRiskWarning').hide();
                        } else {
                            $('#kpiRiskBar').addClass('bg-success');
                            $('#kpiRiskWarning').hide();
                        }
                    } else {
                        $('#kpiRiskLimitLabel').text('Limit: Tanımsız');
                        $('#kpiRiskBar').css('width', '0%');
                        $('#kpiRiskRatio').text('-');
                        $('#kpiRiskWarning').hide();
                    }

                } else {
                    $('#kpiCurrentBalance').text('Bilinmiyor');
                    $('#commercialInfoRow').hide();
                }
            },
            error: function() {
                $('#kpiCurrentBalance').text('Hata');
            }
        });
        
    // --- Manual Data Save Handler ---
    $('#btnEditCommercial').off('click').on('click', function() {
        $('#modalEditCommercial').modal('show');
    });

    $('#btnSaveCommercial').off('click').on('click', function() {
        var formData = $('#formCommercialData').serialize();
        
        // Disable button
        var $btn = $(this);
        $btn.prop('disabled', true).text('Kaydediliyor...');
        
        $.ajax({
            url: 'api/save_customer_commercials.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(resp) {
                if(resp.success) {
                    // Refresh Dashboard
                    $('#modalEditCommercial').modal('hide');
                    var sirketId = $('#editSirketId').val();
                    loadDashboard(sirketId, $('#headerCustomerInfo').text()); // Reload logic
                } else {
                    alert(resp.message || 'Bir hata oluştu.');
                }
            },
            error: function() {
                alert('Sunucu hatası oluştu.');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Kaydet');
            }
        });
    });

        // 2. Ciro Analizi
        $.ajax({
            url: 'api/get_customer_analysis.php',
            data: { sirket_id: sirketId, type: 'turnover' },
            dataType: 'json',
            success: function(resp) {
                if(resp.success && resp.data) {
                    renderTurnoverTable(resp.data);
                    // Add Chart Render Call
                    if(typeof renderTurnoverChart === 'function') {
                        renderTurnoverChart(resp.data);
                    }
                    
                    // Update Turnover KPI (First item is usually the latest year)
                    if(resp.data.length > 0) {
                        $('#kpiTotalTurnoverYear').text(formatCurrency(resp.data[0].total));
                        $('#kpiTurnoverYearLabel').text(resp.data[0].year);
                    } else {
                        $('#kpiTotalTurnoverYear').text('0,00 ₺');
                        $('#kpiTurnoverYearLabel').text('-');
                    }
                } else {
                    $('#turnoverTableBody').html('<tr><td colspan="3" class="text-center text-muted py-3">Kayıt bulunamadı.</td></tr>');
                    $('#kpiTotalTurnoverYear').text('0,00 ₺');
                }
            },
            error: function() {
                $('#turnoverTableBody').html('<tr><td colspan="3" class="text-center text-danger py-3">Bağlantı hatası.</td></tr>');
            }
        });

        // 3. Geçikmiş Faturalar
        $.ajax({
            url: 'api/get_customer_analysis.php',
            data: { sirket_id: sirketId, type: 'overdue' },
            dataType: 'json',
            success: function(resp) {
                if(resp.success && resp.data) {
                    renderOverdueTable(resp.data);
                    // Calc Total Overdue for KPI
                    var totalDebt = 0;
                    resp.data.forEach(function(item) {
                        totalDebt += parseFloat(item['Kalan']);
                    });
                    $('#kpiTotalOverdue').text(formatCurrency(totalDebt));

                } else {
                    $('#overdueTableBody').html('<tr><td colspan="4" class="text-center text-muted py-3">' + (resp.error || 'Geçikmiş borç bulunmuyor.') + '</td></tr>');
                    $('#overdueCountBadge').text('0 Adet');
                    $('#kpiTotalOverdue').text('0,00 ₺');
                }
            },
            error: function() {
                $('#overdueTableBody').html('<tr><td colspan="4" class="text-center text-danger py-3">Bağlantı hatası.</td></tr>');
            }
        });

        // 4. En Çok Alınan Ürünler
        $('#topProductsTableBody').html('<tr><td colspan="4" class="text-center py-4"><div class="spinner-border text-info spinner-border-sm"></div> Ürün analizi yapılıyor...</td></tr>');
        $.ajax({
            url: 'api/get_customer_analysis.php',
            data: { sirket_id: sirketId, type: 'top_products' },
            dataType: 'json',
            success: function(resp) {
                if(resp.success && resp.data) {
                    renderTopProductsTable(resp.data);
                } else {
                    $('#topProductsTableBody').html('<tr><td colspan="4" class="text-center text-muted py-3">Ürün verisi bulunamadı.</td></tr>');
                }
            },
            error: function() {
                $('#topProductsTableBody').html('<tr><td colspan="4" class="text-center text-danger py-3">Bağlantı hatası.</td></tr>');
            }
        });



        // 6. Return Invoices (İade Faturaları)
        $('#returnsTableBody').html('<tr><td colspan="3" class="text-center py-4"><div class="spinner-border text-danger spinner-border-sm"></div> Kontrol ediliyor...</td></tr>');
        $.ajax({
            url: 'api/get_customer_analysis.php',
            data: { sirket_id: sirketId, type: 'returns' },
            dataType: 'json',
            success: function(resp) {
                if(resp.success && resp.data) {
                    renderReturnInvoicesTable(resp.data);
                } else {
                    $('#returnsTableBody').html('<tr><td colspan="3" class="text-center text-muted py-3">İade bulunamadı.</td></tr>');
                    $('#kpiTotalReturns').text('0,00 ₺');
                }
            },
            error: function() {
                $('#returnsTableBody').html('<tr><td colspan="3" class="text-center text-danger py-3">Bağlantı hatası.</td></tr>');
            }
        });

        // 5. Cari Hesap Ekstresi (Statement)
        $('#statementTableBody').html('<tr><td colspan="7" class="text-center py-4"><div class="spinner-border text-secondary spinner-border-sm"></div> Ekstre hazırlanıyor...</td></tr>');
        $.ajax({
            url: 'api/get_customer_analysis.php',
            data: { sirket_id: sirketId, type: 'statement' },
            dataType: 'json',
            success: function(resp) {
                if(resp.success && resp.data) {
                    renderStatementTable(resp.data);
                } else {
                    $('#statementTableBody').html('<tr><td colspan="7" class="text-center text-muted py-3">Hareket bulunamadı.</td></tr>');
                }
            },
            error: function() {
                $('#statementTableBody').html('<tr><td colspan="7" class="text-center text-danger py-3">Bağlantı hatası.</td></tr>');
            }
        });
    }

    function renderStatementTable(data) {
        var html = '';
        var tBorc = 0;
        var tAlacak = 0;
        var lastBal = 0;
        var lastStatus = '';

        if(data.length === 0) {
            html = '<tr><td colspan="7" class="text-center text-muted">Kayıt bulunamadı.</td></tr>';
            $('#stmtTotalDebt').text('0,00 ₺');
            $('#stmtTotalCredit').text('0,00 ₺');
            $('#stmtLastBalance').text('0,00 ₺');
        } else {
            data.forEach(function(item) {
                var borc = parseFloat(item.Borc);
                var alacak = parseFloat(item.Alacak);
                var bakiye = parseFloat(item.RawBakiye); 
                
                tBorc += borc;
                tAlacak += alacak;
                lastBal = bakiye; // Son satır bakiyesi güncel bakiyedir
                lastStatus = item.Durum || '';

                html += '<tr>';
                html += '<td class="ps-3 text-nowrap">' + item.Tarih + '</td>';
                html += '<td class="text-nowrap small text-muted">' + item.FisNo + '</td>';
                html += '<td class="text-nowrap small fw-semibold">' + item.FisTuru + '</td>';
                html += '<td class="small text-muted text-truncate" style="max-width: 250px;" title="' + (item.Aciklama || '') + '">' + (item.Aciklama || '-') + '</td>';
                
                html += '<td class="text-end ' + (borc > 0 ? 'text-dark' : 'text-muted opacity-25') + '">' + (borc > 0 ? formatCurrency(borc) : '-') + '</td>';
                html += '<td class="text-end ' + (alacak > 0 ? 'text-success' : 'text-muted opacity-25') + '">' + (alacak > 0 ? formatCurrency(alacak) : '-') + '</td>';
                
                var absBakiye = Math.abs(bakiye);
                var suffix = item.Durum ? ' (' + item.Durum + ')' : '';
                var colorClass = bakiye > 0 ? 'text-danger' : (bakiye < 0 ? 'text-success' : 'text-muted');
                
                html += '<td class="text-end pe-3 fw-bold ' + colorClass + '">' + formatCurrency(absBakiye) + suffix + '</td>';
                html += '</tr>';
            });

            // Update Totals
            $('#stmtTotalDebt').text(formatCurrency(tBorc));
            $('#stmtTotalCredit').text(formatCurrency(tAlacak));
            
            // User Request: Update the Collection KPI with Statement Total Credit
            $('#kpiMonthCollection').text(formatCurrency(tAlacak));

            var absLast = Math.abs(lastBal);
            var suffixLast = lastStatus ? ' (' + lastStatus + ')' : '';
            $('#stmtLastBalance').text(formatCurrency(absLast) + suffixLast);
            
            // Colorize Final Balance
            $('#stmtLastBalance').removeClass('text-danger text-success text-dark text-primary');
            if(lastBal > 0.009) $('#stmtLastBalance').addClass('text-danger');
            else if(lastBal < -0.009) $('#stmtLastBalance').addClass('text-success');
            else $('#stmtLastBalance').addClass('text-dark');
        }
        $('#statementTableBody').html(html);
    }

    function renderTurnoverTable(years) {
        var html = '';
        if(years.length === 0) {
            html = '<tr><td colspan="3" class="text-center text-muted">Kayıt bulunamadı.</td></tr>';
        } else {
            years.forEach(function(y) {
                var yearId = 'year_' + y.year;
                // Ana Satır
                html += '<tr class="fw-semibold year-row text-dark" data-bs-toggle="collapse" data-bs-target="#'+yearId+'" style="cursor:pointer;">';
                html += '<td class="text-center"><i class="bx bx-plus-circle text-primary fs-5 expand-icon"></i></td>';
                html += '<td>' + y.year + '</td>';
                html += '<td class="text-end fw-bold pe-3">' + formatCurrency(y.total) + '</td>';
                html += '</tr>';

                // Detay Satırı (Collapse)
                html += '<tr><td colspan="3" class="p-0 border-0">';
                html += '<div class="collapse bg-light" id="'+yearId+'">';
                html += '<table class="table table-sm mb-0 table-borderless">';
                html += '<thead class="text-muted small border-bottom"><tr><th class="ps-5">Ay</th><th class="text-end pe-5">Aylık Ciro</th></tr></thead>';
                html += '<tbody>';
                
                var monthNames = ["", "Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran", "Temmuz", "Ağustos", "Eylül", "Ekim", "Kasım", "Aralık"];
                
                if (y.months && y.months.length > 0) {
                    y.months.forEach(function(m) {
                        html += '<tr>';
                        html += '<td class="ps-5 text-secondary">' + (monthNames[m.month] || m.month) + '</td>';
                        html += '<td class="text-end pe-5 text-dark">' + formatCurrency(m.total) + '</td>';
                        html += '</tr>';
                    });
                } else {
                    html += '<tr><td colspan="2" class="text-center text-muted small py-2">Detay yok.</td></tr>';
                }

                html += '</tbody></table>';
                html += '</div></td></tr>';
            });
        }
        $('#turnoverTableBody').html(html);
        
        // Icon toggle logic
        $('.collapse').on('show.bs.collapse', function () {
            $(this).parent().parent().prev().find('.expand-icon').removeClass('bx-plus-circle').addClass('bx-minus-circle text-danger');
        }).on('hide.bs.collapse', function () {
            $(this).parent().parent().prev().find('.expand-icon').removeClass('bx-minus-circle text-danger').addClass('bx-plus-circle text-primary');
        });
    }

    function renderReturnInvoicesTable(data) {
        var html = '';
        var total = 0;
        var totalAdet = 0;
        
        if(data.length === 0) {
            html = '<tr><td colspan="5" class="text-center text-muted">İade kaydı yok.</td></tr>';
        } else {
            data.forEach(function(item) {
                total += parseFloat(item.Tutar);
                totalAdet += parseFloat(item.Adet || 0);
                
                html += '<tr>';
                html += '<td class="ps-3" style="width: 100px;">' + (item.Tarih || '-') + '</td>';
                html += '<td style="width: 130px;"><span class="badge bg-light text-dark border">' + (item.FisNo || '-') + '</span></td>';
                html += '<td><div class="d-flex flex-column"><span class="fw-semibold text-dark small mb-0">' + (item.UrunKodu || '-') + '</span><small class="text-muted" style="font-size:0.75rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:250px;">' + (item.UrunAdi || '') + '</small></div></td>';
                html += '<td class="text-center">' + (item.Adet || 0) + '</td>';
                html += '<td class="text-end pe-4 fw-bold text-danger">' + formatCurrency(item.Tutar) + '</td>';
                html += '</tr>';
            });
        }
        $('#returnsTableBody').html(html);
        $('#kpiTotalReturns').text(formatCurrency(total));
        
        // Update Header Totals
        $('#retTotalAdet').text(totalAdet.toLocaleString());
        $('#retTotalTutar').text(formatCurrency(total));
    }

    var turnoverChart = null;

    function renderTurnoverChart(data) {
        // Data format: [{year: 2025, total: X, months: [{month: 1, total: Y}, ...]}, ...]
        
        var series = [];
        var categories = ['Oca','Şub','Mar','Nis','May','Haz','Tem','Ağu','Eyl','Eki','Kas','Ara'];

        // Sort data by year ascending for chart columns (2023, 2024, 2025)
        // Currently data is descending (2025 first). We want chart to show years side by side?
        // Let's keep data order but name series correctly.
        
        data.forEach(function(yData) {
            var monthlyData = new Array(12).fill(0);
            if(yData.months) {
                yData.months.forEach(function(m) {
                    var mIndex = parseInt(m.month) - 1;
                    if(mIndex >= 0 && mIndex < 12) {
                        monthlyData[mIndex] = parseFloat(m.total);
                    }
                });
            }
            series.push({
                name: yData.year.toString(),
                data: monthlyData
            });
        });

        // Sort series by name (Year) Ascending: 2023, 2024, 2025
        series.sort(function(a, b) {
            return parseInt(a.name) - parseInt(b.name);
        });

        var options = {
            series: series,
            chart: {
                type: 'bar',
                height: 350,
                toolbar: { show: false }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '60%',
                    endingShape: 'rounded'
                },
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            xaxis: {
                categories: categories,
            },
            yaxis: {
                title: {
                    text: 'Ciro (TL)'
                },
                labels: {
                    formatter: function (value) {
                        return formatCurrency(value).replace(' ₺', '');
                    }
                }
            },
            fill: {
                opacity: 1
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return formatCurrency(val);
                    }
                }
            },
            colors: ['#0d6efd', '#6c757d', '#ffc107', '#198754'] // Standard Colors
        };

        if(turnoverChart) {
            turnoverChart.destroy();
        }

        turnoverChart = new ApexCharts(document.querySelector("#turnoverChart"), options);
        turnoverChart.render();
    }

    function renderOverdueTable(data) {
        var html = '';
        if(data.length === 0) {
            html = '<tr><td colspan="4" class="text-center text-success py-3"><i class="bx bx-check-circle fs-4"></i><br>Geçikmiş borç bulunmuyor.</td></tr>';
        } else {
            $('#overdueCountBadge').text(data.length + ' Adet').show();
            data.forEach(function(item) {
                html += '<tr>';
                html += '<td class="ps-3"><span class="fw-semibold text-dark">' + (item['Fatura No'] || '-') + '</span><br><small class="text-muted" style="font-size:0.75rem">' + (item['Fatura Tarihi'] || '') + '</small></td>';
                html += '<td>' + (item['Vade Tarihi'] || '-') + '</td>';
                html += '<td class="text-center"><span class="badge bg-danger bg-opacity-10 text-danger">' + item['Geçen Gün'] + ' Gün</span></td>';
                html += '<td class="text-end fw-bold text-danger pe-3">' + formatCurrency(item['Kalan']) + '</td>';
                html += '</tr>';
            });
        }
        $('#overdueTableBody').html(html);
    }

    function renderTopProductsTable(data) {
        var html = '';
        if(data.length === 0) {
            html = '<tr><td colspan="4" class="text-center text-muted py-3">Veri yok.</td></tr>';
        } else {
            data.forEach(function(item) {
                html += '<tr>';
                html += '<td class="ps-4 fw-semibold text-dark">' + (item['UrunKodu'] || '-') + '</td>';
                html += '<td>' + (item['UrunAdi'] || '-') + '</td>';
                html += '<td class="text-center"><span class="badge bg-info bg-opacity-10 text-info px-3">' + parseFloat(item['ToplamAdet']).toLocaleString() + '</span></td>';
                html += '<td class="text-end fw-bold text-dark pe-4">' + formatCurrency(item['ToplamTutar']) + '</td>';
                html += '</tr>';
            });
        }
        $('#topProductsTableBody').html(html);
    }

    function formatCurrency(val) {
        return parseFloat(val).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ₺';
    }
});
</script>
</body>
</html>
