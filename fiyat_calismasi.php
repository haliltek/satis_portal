<?php
include "fonk.php";
oturumkontrol();

global $dbManager, $logoService, $db;

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die('Geçersiz ID');
}

$company = $dbManager->getCompanyInfoById($id);
if (!$company) {
    die('Şirket bulunamadı');
}

// Müşterinin specode bilgisini al - ŞİRKET TABLOSUNDAN
$specode = trim($company['specode'] ?? '');
// Debug: Specode değerini kontrol et
error_log("DEBUG - Specode: '" . $specode . "' - Length: " . strlen($specode));
// Türkçe karakterleri de kontrol et
$isExport = (
    stripos($specode, 'yd') !== false || 
    stripos($specode, 'export') !== false || 
    stripos($specode, 'ihracat') !== false ||
    stripos($specode, 'İHRACAT') !== false ||
    strtoupper($specode) === 'IHRACAT' ||
    strtoupper($specode) === 'İHRACAT'
);
error_log("DEBUG - isExport: " . ($isExport ? 'true' : 'false'));
$cariKod = $company['s_arp_code'] ?? '';

// Mevcut çalışmaları çek
$calismaQuery = $db->prepare("SELECT * FROM ozel_fiyat_calismalari WHERE sirket_id = ? ORDER BY olusturma_tarihi DESC");
$calismaQuery->bind_param("i", $id);
$calismaQuery->execute();
$calismalar = $calismaQuery->get_result()->fetch_all(MYSQLI_ASSOC);
$calismaQuery->close();

// Seçili çalışma
$selectedWorkId = isset($_GET['work_id']) ? (int)$_GET['work_id'] : 0;
$selectedWork = null;
$workProducts = [];

if ($selectedWorkId > 0) {
    $workQuery = $db->prepare("SELECT * FROM ozel_fiyat_calismalari WHERE id = ? AND sirket_id = ?");
    $workQuery->bind_param("ii", $selectedWorkId, $id);
    $workQuery->execute();
    $selectedWork = $workQuery->get_result()->fetch_assoc();
    $workQuery->close();
    
    if ($selectedWork) {
        $productsQuery = $db->prepare("
            SELECT ofu.*, u.maliyet AS guncel_maliyet, u.mysql_guncelleme AS guncelleme_tarihi, u.fiyat AS urun_fiyat, u.export_fiyat AS urun_export_fiyat  
            FROM ozel_fiyat_urunler ofu 
            LEFT JOIN urunler u ON ofu.stok_kodu = u.stokkodu 
            WHERE ofu.calisma_id = ? 
            ORDER BY ofu.olusturma_tarihi ASC
        ");
        $productsQuery->bind_param("i", $selectedWorkId);
        $productsQuery->execute();
        $workProducts = $productsQuery->get_result()->fetch_all(MYSQLI_ASSOC);
        $productsQuery->close();
    }
}
$yonetici_id = $_SESSION['yonetici_id'] ?? 0;
// Admin kontrolü (Cost/Margin gizlemek için)
// user_type 'Bayi' veya 'Plasiyer' ise gizle. Sadece 'Yönetici' görsün.
// Ancak emin olmak için: user_type içinde 'Yönetici' veya 'Admin' geçiyorsa true yapalım.
// Veya basitçe: Bayi değilse ve Plasiyer değilse?
// Kullanıcı "Sadece Yöneticiler" dedi.
$userType = $_SESSION['user_type'] ?? '';
$isAdmin = (stripos($userType, 'Yönetici') !== false || stripos($userType, 'Admin') !== false);
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8" />
    <title>Fiyat Çalışması - <?php echo htmlspecialchars($company['s_adi'] ?? ''); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <link href="assets/css/icons.min.css" rel="stylesheet" />
    <link href="assets/css/app.min.css" rel="stylesheet" />
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 13px;
        }

        .price-work-container {
            background: #f5f5f5;
            padding: 15px;
            border: 1px solid #ddd;
        }

        .company-info-card {
            background: white;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
        }

        .company-info-card h5 {
            background: #6c5ce7;
            color: white;
            padding: 6px 10px;
            margin: -10px -10px 10px -10px;
            font-size: 13px;
            font-weight: 600;
        }

        .work-header-card {
            background: white;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
        }

        .work-header-card h5 {
            background: #28a745;
            color: white;
            padding: 6px 10px;
            margin: -15px -15px 15px -15px;
            font-size: 13px;
            font-weight: 600;
        }

        .company-info-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 8px;
        }

        .company-info-item {
            display: flex;
            flex-direction: column;
        }

        .company-info-label {
            font-size: 10px;
            font-weight: 600;
            color: #666;
            margin-bottom: 2px;
        }

        .company-info-value {
            font-size: 12px;
            color: #333;
            padding: 4px 6px;
            background: #f9f9f9;
            border: 1px solid #ddd;
        }

        .price-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
        }

        .price-badge.domestic {
            background: #d1e7dd;
            color: #0f5132;
        }

        .price-badge.export {
            background: #cfe2ff;
            color: #084298;
        }

        .erp-table-container {
            background: white;
            border: 1px solid #999;
            margin-top: 10px;
        }

        .erp-table {
            font-size: 11px;
            margin-bottom: 0;
            background: white;
            border-collapse: collapse;
            width: 100%;
        }

        .erp-table thead th {
            background: #e8e8e8;
            border: 1px solid #999;
            padding: 4px 6px;
            font-weight: 600;
            font-size: 10px;
            text-align: left;
            color: #333;
            white-space: nowrap;
        }

        .erp-table tbody td {
            border: 1px solid #ddd;
            padding: 2px 4px;
            background: white;
            vertical-align: middle;
            height: 24px;
        }

        .erp-table tbody tr:nth-child(odd) td {
            background-color: #ffffff;
        }

        .erp-table tbody tr:nth-child(even) td {
            background-color: #f8f9fa;
        }

        .erp-table tbody tr:hover td {
            background-color: #e9ecef !important;
        }

        .erp-table tbody input.form-control {
            border: none;
            padding: 2px 4px;
            font-size: 11px;
            height: 20px;
            width: 100%;
            background: transparent;
            margin: 0;
            box-sizing: border-box;
        }

        .erp-table tbody input.form-control:focus {
            border: 1px solid #6c5ce7;
            background: white;
            outline: none;
        }

        .erp-table tbody input[readonly] {
            background: #f9f9f9;
            border: none;
            font-weight: 500;
        }

        .btn-search, .btn-sm {
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 0;
            border: 1px solid #ccc;
            font-weight: 500;
            height: 22px;
            line-height: 18px;
        }

        .btn-search {
            background: #6c5ce7;
            border-color: #6c5ce7;
            color: white;
        }

        .btn-search:hover {
            background: #5a4ecf;
        }

        #product-autocomplete-global {
            position: fixed;
            z-index: 99999;
            background: white;
            border: 1px solid #ccc;
            max-height: 400px;
            overflow-y: auto;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            display: none;
        }

        #product-autocomplete-global ul {
            list-style: none !important;
            padding: 0 !important;
            margin: 0 !important;
            background: white !important;
        }

        #product-autocomplete-global li {
            padding: 8px 12px !important;
            cursor: pointer !important;
            border-bottom: 1px solid #eee !important;
            background: white !important;
            color: #333 !important;
        }

        #product-autocomplete-global li:hover {
            background: #f0f0f0 !important;
        }

        .action-buttons {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
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
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Fiyat Çalışması</h4>
        <a href="tumsirketler.php" class="btn btn-secondary btn-sm">
            <i class="fa fa-arrow-left me-1"></i> Geri Dön
        </a>
    </div>

    <!-- Müşteri Bilgileri -->
    <div class="company-info-card">
        <h5><i class="fa fa-building me-2"></i>Müşteri Bilgileri</h5>
        <div class="company-info-row">
            <div class="company-info-item">
                <span class="company-info-label">Şirket Adı</span>
                <span class="company-info-value"><?php echo htmlspecialchars($company['s_adi'] ?? ''); ?></span>
            </div>
            <div class="company-info-item">
                <span class="company-info-label">Cari Kodu</span>
                <span class="company-info-value"><?php echo htmlspecialchars($cariKod); ?></span>
            </div>
            <div class="company-info-item">
                <span class="company-info-label">Fiyat Tipi</span>
                <span class="company-info-value">
                    <?php if ($isExport): ?>
                        <span class="price-badge export">Export Fiyat</span>
                    <?php else: ?>
                        <span class="price-badge domestic">Yurtiçi Fiyat</span>
                    <?php endif; ?>
                </span>
            </div>
        </div>
        <div class="company-info-row">
            <div class="company-info-item">
                <span class="company-info-label">Specode (Debug)</span>
                <span class="company-info-value"><?php echo htmlspecialchars($specode); ?> <?php echo $specode ? '(Uzunluk: ' . strlen($specode) . ')' : '(BOŞ)'; ?></span>
            </div>
            <div class="company-info-item">
                <span class="company-info-label">Telefon</span>
                <span class="company-info-value"><?php echo htmlspecialchars($company['s_telefonu'] ?? ''); ?></span>
            </div>
            <div class="company-info-item">
                <span class="company-info-label">Ülke</span>
                <span class="company-info-value"><?php echo htmlspecialchars($company['s_country'] ?? 'Türkiye'); ?></span>
            </div>
        </div>
    </div>

    <!-- Çalışma Başlığı -->
    <div class="work-header-card">
        <h5><i class="fa fa-file-alt me-2"></i>Çalışma Bilgileri</h5>
        <div class="row">
            <div class="col-md-4">
                <label class="form-label" style="font-size: 11px; font-weight: 600;">Mevcut Çalışmalar</label>
                <select class="form-select form-select-sm" id="existingWorkSelect">
                    <option value="0">-- Yeni Çalışma --</option>
                    <?php foreach ($calismalar as $calisma): ?>
                        <option value="<?php echo $calisma['id']; ?>" 
                                <?php echo $selectedWorkId == $calisma['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($calisma['baslik']); ?>
                            <?php echo $calisma['aktif'] ? '(Aktif)' : '(Pasif)'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label" style="font-size: 11px; font-weight: 600;">Çalışma Başlığı</label>
                <input type="text" class="form-control form-control-sm" id="workTitle" 
                       value="<?php echo htmlspecialchars($selectedWork['baslik'] ?? ''); ?>"
                       placeholder="Örn: 2026 Ocak İhracat Fiyatları">
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-size: 11px; font-weight: 600;">Durum</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="workActive" 
                           <?php echo (!$selectedWork || $selectedWork['aktif']) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="workActive" style="font-size: 11px;">Aktif</label>
                </div>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <?php if ($selectedWorkId > 0): ?>
                    <button type="button" class="btn btn-danger btn-sm w-100" id="deleteWorkBtn">
                        <i class="fa fa-trash"></i>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-md-12">
                <label class="form-label" style="font-size: 11px; font-weight: 600;">Açıklama</label>
                <textarea class="form-control form-control-sm" id="workDescription" rows="2" 
                          placeholder="Çalışma hakkında notlar..."><?php echo htmlspecialchars($selectedWork['aciklama'] ?? ''); ?></textarea>
            </div>
        </div>
    </div>

    <!-- Ürün Listesi -->
    <div class="price-work-container">
        <div class="erp-table-container">
            <table class="erp-table" id="priceWorkTable">
                <thead>
                    <tr>
                        <th style="width: 40px;">Sıra</th>
                        <th style="width: 150px;">Stok Kodu</th>
                        <th style="width: 300px;">Ürün Adı</th>
                        <th style="width: 80px;">Birim</th>
                        <?php if ($isAdmin): ?>
                        <th style="width: 90px;">Maliyet</th>
                        <?php endif; ?>
                        <th style="width: 100px;">Liste Fiyatı</th>
                        <th style="width: 100px;">Özel Fiyat</th>
                        <th style="width: 70px;">İskonto %</th>
                        <?php if ($isAdmin): ?>
                        <th style="width: 70px;">Satış Marj %</th>
                        <?php endif; ?>
                        <th style="width: 70px;">Döviz</th>
                        <th style="width: 250px;">Not</th>
                        <th style="width: 60px;">İşlem</th>
                    </tr>
                </thead>
                <tbody id="priceWorkTableBody">
                    <?php 
                    $rowNum = 1;
                    foreach ($workProducts as $product): 
                        // Maliyet Kontrolü
                        $savedCost = (float)($product['maliyet'] ?? 0);
                        $currentCost = (float)($product['guncel_maliyet'] ?? 0);
                        
                        // Eğer kayıtlı maliyet 0 ise ve güncel maliyet varsa, güncel maliyeti baz al (Eski kayıtlar için)
                        if ($savedCost <= 0 && $currentCost > 0) {
                            $savedCost = $currentCost;
                        }

                        // Fark Kontrolü
                        $costChanged = false;
                        $tooltipAttr = '';
                        $inputStyle = 'text-align: right;';
                        
                        if ($savedCost > 0 && abs($savedCost - $currentCost) > 0.01) {
                            $costChanged = true;
                            $diff = $currentCost - $savedCost;
                            $diffStr = ($diff > 0 ? '+' : '') . number_format($diff, 2, ',', '.');
                            
                            $dateStr = '';
                            if (!empty($product['guncelleme_tarihi'])) {
                                $dateStr = "<br>Değişim Tarihi: " . date('d.m.Y', strtotime($product['guncelleme_tarihi']));
                            }
                            
                            $title = "Maliyet Değişti! <br>Eski: " . number_format($savedCost, 2, ',', '.') . 
                                     "<br>Yeni: " . number_format($currentCost, 2, ',', '.') . 
                                     "<br>Fark: " . $diffStr . 
                                     $dateStr;
                            
                            $tooltipAttr = 'data-bs-toggle="tooltip" data-bs-html="true" title="' . htmlspecialchars($title) . '"';
                            $inputStyle .= ' background-color: #fff3cd; color: #856404; font-weight: bold; cursor: help;';
                        }

                        // Soft-fix for List Price bug (List Price saved as Cost)
                        $displayListPrice = (float)$product['liste_fiyati'];
                        // Calculate expected list price based on export flag
                        $expectedListPrice = $isExport ? (float)($product['urun_export_fiyat'] ?? 0) : (float)($product['urun_fiyat'] ?? 0);
                        
                        // If saved list price is basically same as saved cost AND different from expected list price, prefer expected
                        // This handles the case where previous save logic wrote cost into list price column
                        if ($displayListPrice > 0 && abs($displayListPrice - $savedCost) < 0.01 && $expectedListPrice > 0 && abs($displayListPrice - $expectedListPrice) > 0.01) {
                             $displayListPrice = $expectedListPrice;
                        }
                    ?>
                    <tr data-product-id="<?php echo $product['id']; ?>" class="existing-product-row">
                        <td style="text-align: center;"><?php echo $rowNum++; ?></td>
                        <td>
                            <input type="text" class="form-control product-code-input" value="<?php echo htmlspecialchars($product['stok_kodu']); ?>" readonly>
                        </td>
                        <td>
                            <input type="text" class="form-control product-name-input" value="<?php echo htmlspecialchars($product['urun_adi']); ?>" readonly>
                        </td>
                        <td><input type="text" class="form-control product-unit" value="<?php echo htmlspecialchars($product['birim']); ?>" readonly></td>
                        <?php if ($isAdmin): ?>
                        <td style="text-align: right;" <?php echo $tooltipAttr; ?>>
                            <input type="text" class="form-control product-cost" 
                                   value="<?php echo number_format($savedCost > 0 ? $savedCost : $currentCost, 2, ',', '.'); ?>" 
                                   readonly style="<?php echo $inputStyle; ?>">
                        </td>
                        <?php endif; ?>
                        <td style="text-align: right;"><input type="text" class="form-control product-list-price" value="<?php echo number_format($displayListPrice, 2, ',', '.'); ?>" readonly style="text-align: right;"></td>
                        <td>
                            <input type="text" class="form-control special-price-input" 
                                   value="<?php echo number_format($product['ozel_fiyat'], 2, ',', '.'); ?>"
                                   data-list-price="<?php echo $product['liste_fiyati']; ?>"
                                   style="text-align: right;">
                        </td>
                        <td style="text-align: right;" class="discount-display">
                            <input type="text" class="form-control product-discount" 
                                   value="<?php echo number_format($product['iskonto_orani'], 2, ',', '.'); ?>%" 
                                   style="text-align: right;">
                        </td>
                        <?php
                            // Marj Hesaplama
                            $effectiveCost = $savedCost > 0 ? $savedCost : $currentCost;
                            $ozelFiyat = (float)$product['ozel_fiyat'];
                            $margin = 0;
                            if ($ozelFiyat > 0) {
                                $margin = (($ozelFiyat - $effectiveCost) / $ozelFiyat) * 100;
                            }
                            
                            // Renklendirme mantığı (JS ile uyumlu)
                            $marginColor = '#333';
                            if ($margin < 0) $marginColor = '#dc3545'; // Kırmızı
                            else if ($margin < 10) $marginColor = '#ffc107'; // Sarı/Turuncu (Koyu okunması için varsayalım)
                            else $marginColor = '#198754'; // Yeşil
                            
                            // Sarı rengi input içinde okunaklı olsun diye biraz koyulaştıralım veya text-warning class'ı kullanalım ama input style kullanıyoruz.
                            if ($margin > 0 && $margin < 10) $marginColor = '#d39e00';
                        ?>
                        <?php if ($isAdmin): ?>
                        <td style="text-align: right;"><input type="text" class="form-control product-margin" value="<?php echo number_format($margin, 2, ',', '.'); ?>%" readonly style="text-align: right; color: <?php echo $marginColor; ?>; font-weight: bold;"></td>
                        <?php endif; ?>
                        <td style="text-align: center;"><input type="text" class="form-control product-currency" value="<?php echo htmlspecialchars($product['doviz']); ?>" readonly style="text-align: center;"></td>
                        <td>
                            <input type="text" class="form-control product-note" 
                                   value="<?php echo htmlspecialchars($product['notlar'] ?? ''); ?>" 
                                   placeholder="Not..."
                                   style="font-size: 11px;">
                        </td>
                        <td style="text-align: center;">
                            <button type="button" class="btn btn-danger btn-sm delete-product-btn" 
                                    style="padding: 1px 6px; height: 20px; line-height: 18px;">
                                <i class="fa fa-trash" style="font-size: 10px;"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="action-buttons">
            <button type="button" class="btn btn-secondary" id="clearFormBtn">
                <i class="fa fa-eraser me-1"></i> Temizle
            </button>
            <button type="button" class="btn btn-success" id="saveWorkBtn">
                <i class="fa fa-save me-1"></i> Kaydet
            </button>
        </div>
    </div>

    <!-- Product Search Modal -->
    <div class="modal fade" id="productSearchModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white" style="padding: 10px 15px;">
                    <h5 class="modal-title text-white" style="font-size: 14px;"><i class="fa fa-search me-2"></i>Ürün Arama</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="modalSearchInput" placeholder="Stok kodu veya ürün adı ile arayın... (En az 2 karakter)">
                        <button class="btn btn-primary" type="button" id="modalSearchBtn">
                            <i class="fa fa-search me-1"></i> Ara
                        </button>
                    </div>
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-bordered table-striped table-hover table-sm" style="font-size: 12px;">
                            <thead class="table-light">
                                <tr>
                                    <th>Stok Kodu</th>
                                    <th>Ürün Adı</th>
                                    <th>Birim</th>
                                    <th style="width: 80px;">İşlem</th>
                                </tr>
                            </thead>
                            <tbody id="modalSearchResults">
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">
                                        Arama yapmak için yukarıdaki alanı kullanın.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Autocomplete Dropdown -->
    <div id="product-autocomplete-global" style="display: none;">
        <ul id="product-autocomplete-list"></ul>
    </div>

</div>
</div>
<?php include "menuler/footer.php"; ?>
</div>
</div>
<div class="rightbar-overlay"></div>

<script src="assets/libs/jquery/jquery.min.js"></script>
<script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/libs/metismenu/metisMenu.min.js"></script>
<script src="assets/libs/simplebar/simplebar.min.js"></script>
<script src="assets/libs/node-waves/waves.min.js"></script>
<script src="assets/js/app.js"></script>

<script>
$(document).ready(function() {
    const isExport = <?php echo $isExport ? 'true' : 'false'; ?>;
    const companyId = <?php echo $id; ?>;
    const cariKod = '<?php echo addslashes($cariKod); ?>';
    const yoneticiId = <?php echo $yonetici_id; ?>;
    const isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;
    
    let productCounter = <?php echo count($workProducts); ?>;
    let currentRowForModal = null;

    // Başlangıçta 10 boş satır ekle
    initializeEmptyRows();

    function initializeEmptyRows() {
        // Mevcut satır sayısını kontrol et
        const existingRows = $('#priceWorkTableBody tr.existing-product-row').length;
        const rowsToAdd = 10; // Her zaman en az 10 boş satır olsun
        
        for (let i = 0; i < rowsToAdd; i++) {
            addNewRow();
        }
    }

    function addNewRow() {
        productCounter++;
        const rowId = `row-${productCounter}`;
        
        const row = `
            <tr class="dynamic-row" id="${rowId}">
                <td style="text-align: center;" class="row-number">${productCounter}</td>
                <td>
                    <div style="display: flex; gap: 2px; align-items: center;">
                        <input type="text" class="form-control product-code-input" placeholder="Stok kodu..." autocomplete="off">
                        <button type="button" class="btn-search row-search-code-btn">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>
                </td>
                <td>
                    <div style="display: flex; gap: 2px; align-items: center;">
                        <input type="text" class="form-control product-name-input" placeholder="Ürün adı..." autocomplete="off">
                        <button type="button" class="btn-search row-search-name-btn modal-product-search">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>
                </td>
                <td><input type="text" class="form-control product-unit" readonly></td>
                ${isAdmin ? '<td><input type="text" class="form-control product-cost" readonly style="text-align: right;" value="0,00"></td>' : ''}
                <td><input type="text" class="form-control product-list-price" readonly style="text-align: right;"></td>
                <td><input type="text" class="form-control special-price-input" placeholder="0,00" style="text-align: right;"></td>
                <td><input type="text" class="form-control product-discount" placeholder="0,00" style="text-align: right;"></td>
                ${isAdmin ? '<td><input type="text" class="form-control product-margin" readonly style="text-align: right;" value="0,00%"></td>' : ''}
                <td><input type="text" class="form-control product-currency" readonly style="text-align: center;"></td>
                <td>
                    <input type="text" class="form-control product-note" placeholder="Not..." style="font-size: 11px;">
                </td>
                <td style="text-align: center;">
                    <button type="button" class="btn btn-danger btn-sm delete-row-btn" 
                            style="padding: 1px 6px; height: 20px; line-height: 18px;">
                        <i class="fa fa-trash" style="font-size: 10px;"></i>
                    </button>
                </td>
            </tr>
        `;
        $('#priceWorkTableBody').append(row);
        renumberRows();
    }

    // Mevcut çalışma seçildiğinde sayfayı yenile
    $('#existingWorkSelect').on('change', function() {
        const workId = $(this).val();
        if (workId > 0) {
            window.location.href = 'fiyat_calismasi.php?id=' + companyId + '&work_id=' + workId;
        } else {
            window.location.href = 'fiyat_calismasi.php?id=' + companyId;
        }
    });

    // SATIR İÇİ ARAMA FONKSİYONLARI

    // 1. Stok Kodu Butonu
    $(document).on('click', '.row-search-code-btn', function() {
        const $row = $(this).closest('tr');
        const code = $row.find('.product-code-input').val().trim();
        if (code) {
            searchProductForRow($row, code);
        } else {
            alert('Lütfen stok kodu girin');
        }
    });

    // 2. Stok Kodu Enter/Tab
    $(document).on('keydown', '.product-code-input', function(e) {
        if (e.key === 'Enter' || e.key === 'Tab') {
            const $row = $(this).closest('tr');
            const code = $(this).val().trim();
            
            if (code) {
                // Eğer ürün zaten doluysa tekrar arama yapma (isteğe bağlı)
                // Ama kullanıcı değiştiriyor olabilir, o yüzden ara
                searchProductForRow($row, code);
            }

            // Yeni satır kontrolü
            checkAndAddNewRow($row);

            if (e.key === 'Enter') {
                e.preventDefault();
            }
        }
    });

    // 3. Ürün Adı Modal Açma
    $(document).on('click', '.modal-product-search', function() {
        currentRowForModal = $(this).closest('tr');
        $('#productSearchModal').modal('show');
        setTimeout(function() {
            $('#modalSearchInput').focus();
        }, 500);
    });

    // Modal Arama İşlemleri
    $('#modalSearchBtn').on('click', function() {
        performModalSearch();
    });

    $('#modalSearchInput').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            performModalSearch();
        }
    });

    function performModalSearch() {
        const query = $('#modalSearchInput').val().trim();
        if (query.length < 2) {
            alert('Lütfen en az 2 karakter giriniz.');
            return;
        }

        $('#modalSearchResults').html('<tr><td colspan="4" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Yükleniyor...</span></div></td></tr>');

        $.ajax({
            url: 'api/search_products.php',
            method: 'GET',
            data: { query: query, limit: 50 },
            dataType: 'json',
            success: function(response) {
                const $tbody = $('#modalSearchResults');
                $tbody.empty();

                if (response.success && response.products && response.products.length > 0) {
                    response.products.forEach(function(product) {
                        const row = `
                            <tr>
                                <td>${product.stokkodu}</td>
                                <td>${product.stokadi}</td>
                                <td>${product.olcubirimi}</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-success select-product-btn" 
                                            data-code="${product.stokkodu}">
                                        Seç
                                    </button>
                                </td>
                            </tr>
                        `;
                        $tbody.append(row);
                    });
                } else {
                    $tbody.html('<tr><td colspan="4" class="text-center text-muted">Sonuç bulunamadı.</td></tr>');
                }
            },
            error: function() {
                $('#modalSearchResults').html('<tr><td colspan="4" class="text-center text-danger">Arama sırasında hata oluştu.</td></tr>');
            }
        });
    }

    // Modaldan Seçim
    $(document).on('click', '.select-product-btn', function() {
        const code = $(this).data('code');
        $('#productSearchModal').modal('hide');
        
        if (currentRowForModal) {
            currentRowForModal.find('.product-code-input').val(code);
            searchProductForRow(currentRowForModal, code);
            checkAndAddNewRow(currentRowForModal);
            currentRowForModal = null;
        }
    });

    // Ürün Arama Fonksiyonu
    function searchProductForRow($row, code) {
        $.ajax({
            url: 'api/get_product_for_pricing.php',
            method: 'GET',
            data: { 
                code: code,
                is_export: isExport ? 1 : 0
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.product) {
                    fillRowData($row, response.product);
                } else {
                    alert('Ürün bulunamadı: ' + code);
                    // Bulunamadıysa temizle
                    clearRowData($row);
                    $row.find('.product-code-input').val(code); // Kodu geri yaz
                }
            },
            error: function() {
                alert('Ürün arama sırasında hata oluştu');
            }
        });
    }

    function fillRowData($row, product) {
        $row.find('.product-code-input').val(product.code);
        $row.find('.product-name-input').val(product.name);
        $row.find('.product-unit').val(product.unit);
        $row.find('.product-cost').val(parseFloat(product.cost || 0).toFixed(2).replace('.', ',')); // Maliyet
        $row.find('.product-list-price').val(parseFloat(product.price).toFixed(2).replace('.', ','));
        $row.find('.product-currency').val(product.currency);
        
        // Özel fiyat boşsa boş bırak, doluysa veya 0 ise elleme
        const currentSpecial = $row.find('.special-price-input').val();
        if (!currentSpecial) {
            $row.find('.special-price-input').focus();
        } else {
             // Özel fiyat varsa marjı hesapla
             calculateMargin($row);
        }
        
        $row.addClass('filled-row'); // İşaretle
    }

    function clearRowData($row) {
        $row.find('.product-name-input').val('');
        $row.find('.product-unit').val('');
        $row.find('.product-cost').val('0,00');
        $row.find('.product-list-price').val('');
        $row.find('.special-price-input').val('');
        $row.find('.product-discount').val('');
        $row.find('.product-margin').val('0,00%');
        $row.find('.product-currency').val('');
        $row.find('.product-note').val('');
        $row.removeClass('filled-row');
    }

    function calculateMargin($row) {
        const cost = parseFloat($row.find('.product-cost').val().replace(',', '.')) || 0;
        const specialPrice = parseFloat($row.find('.special-price-input').val().replace(',', '.')) || 0;

        if (specialPrice > 0) {
            // Marj = ((Fiyat - Maliyet) / Fiyat) * 100
            const margin = ((specialPrice - cost) / specialPrice) * 100;
            $row.find('.product-margin').val(margin.toFixed(2).replace('.', ',') + '%');
            
            // Renklendirme (isteğe bağlı)
            const $marginInput = $row.find('.product-margin');
            if (margin < 0) $marginInput.css('color', 'red');
            else if (margin < 10) $marginInput.css('color', 'orange');
            else $marginInput.css('color', 'green');
        } else {
            $row.find('.product-margin').val('0,00%');
        }
    }

    function checkAndAddNewRow($row) {
        // Eğer bu son satırsa, yeni bir satır ekle
        if ($row.is(':last-child')) {
            addNewRow();
        }
    }

    // Özel fiyat değiştiğinde iskonto hesapla
    $(document).on('input', '.special-price-input', function() {
        const $row = $(this).closest('tr');
        const listPrice = parseFloat($row.find('.product-list-price').val().replace(',', '.')) || 
                          parseFloat($row.find('td:eq(4) input').val().replace(',', '.')) || 0;
        
        const specialPrice = parseFloat($(this).val().replace(',', '.')) || 0;
        
        if (listPrice > 0) {
            if (specialPrice > 0) {
                const discount = ((listPrice - specialPrice) / listPrice) * 100;
                $row.find('.product-discount').val(discount.toFixed(2).replace('.', ',') + '%');
            } else {
                $row.find('.product-discount').val('');
            }
        }
        
        // Veri girilince yeni satır ekle (eğer sonsa)
        checkAndAddNewRow($row);
        
        // Marjı güncelle
        calculateMargin($row);
    });

    // İskonto değiştiğinde özel fiyat hesapla (YENİ)
    $(document).on('input', '.product-discount', function() {
        // Yüzde işareti varsa temizle ve sayıya çevir
        let val = $(this).val().replace('%', '').trim();
        const discount = parseFloat(val.replace(',', '.')) || 0;
        
        const $row = $(this).closest('tr');
        const listPrice = parseFloat($row.find('.product-list-price').val().replace(',', '.')) || 
                          parseFloat($row.find('td:eq(4) input').val().replace(',', '.')) || 0; // Existing row için
        
        if (listPrice > 0) {
            if (discount > 0) {
                const specialPrice = listPrice - (listPrice * discount / 100);
                $row.find('.special-price-input').val(specialPrice.toFixed(2).replace('.', ','));
            } else {
                // İskonto silindiyse opsiyonel olarak özel fiyat işlem yapma
            }
        }
        
        checkAndAddNewRow($row);
        
        // Marjı güncelle
        calculateMargin($row);
    });

    // İskonto inputundan çıkıldığında % işareti ekle
    $(document).on('blur', '.product-discount', function() {
        let val = $(this).val().replace('%', '').trim();
        if (val !== '') {
            const num = parseFloat(val.replace(',', '.'));
            if (!isNaN(num)) {
                $(this).val(num.toFixed(2).replace('.', ',') + '%');
            }
        }
    });

    // İskonto inputuna girildiğinde % işaretini kaldır (kolay düzenleme için)
    $(document).on('focus', '.product-discount', function() {
        let val = $(this).val().replace('%', '').trim();
        if (val !== '') {
            $(this).val(val);
        }
        $(this).select();
    });

    // Satır silme
    $(document).on('click', '.delete-row-btn', function() {
        const $row = $(this).closest('tr');
        const rowCount = $('#priceWorkTableBody tr').length;
        
        // En az 1 satır kalsın
        if (rowCount > 1) {
            if ($row.find('.product-code-input').val() !== '' && !confirm('Bu satırı silmek istediğinizden emin misiniz?')) {
                return;
            }
            $row.remove();
            renumberRows();
        } else {
            // Son satırsa temizle
            clearRowData($row);
            $row.find('.product-code-input').val('');
        }
    });

    function renumberRows() {
        let num = 1;
        $('#priceWorkTableBody tr').each(function() {
            $(this).find('td:first').text(num++);
        });
        productCounter = num - 1;
    }

    // Kaydetme
    $('#saveWorkBtn').on('click', function() {
        const title = $('#workTitle').val().trim();
        if (!title) {
            alert('Lütfen çalışma başlığı girin');
            return;
        }

        const products = [];
        
        // Hem mevcut hem dinamik satırları tara
        $('#priceWorkTableBody tr').each(function() {
            const $row = $(this);
            
            // Veri alma (input veya text)
            let code, name, unit, listPrice, specialPrice, currency, cost, note;

            if ($row.hasClass('dynamic-row')) {
                code = $row.find('.product-code-input').val();
                if (!code) return; // Boş satırları atla
                
                name = $row.find('.product-name-input').val();
                unit = $row.find('.product-unit').val();
                listPrice = parseFloat($row.find('.product-list-price').val().replace(',', '.')) || 0;
                specialPrice = parseFloat($row.find('.special-price-input').val().replace(',', '.')) || 0;
                currency = $row.find('.product-currency').val();
                cost = parseFloat($row.find('.product-cost').val().replace(',', '.')) || 0;
                note = $row.find('.product-note').val();
            } else {
                // Existing rows
                code = $row.find('.product-code-input').val(); // Changed to input
                name = $row.find('.product-name-input').val(); // Changed to input 
                unit = $row.find('.product-unit').val();
                listPrice = parseFloat($row.find('.product-list-price').val().replace(',', '.')) || 0;
                specialPrice = parseFloat($row.find('.special-price-input').val().replace(',', '.')) || 0;
                currency = $row.find('.product-currency').val();
                cost = parseFloat($row.find('.product-cost').val().replace(',', '.')) || 0;
                note = $row.find('.product-note').val();
            }

            const discount = listPrice > 0 ? ((listPrice - specialPrice) / listPrice) * 100 : 0;

            if (code && specialPrice > 0) {
                products.push({
                    stok_kodu: code,
                    urun_adi: name,
                    birim: unit,
                    liste_fiyati: listPrice,
                    ozel_fiyat: specialPrice,
                    iskonto_orani: discount,
                    doviz: currency,
                    maliyet: cost,
                    notlar: note
                });
            }
        });

        if (products.length === 0) {
            alert('Lütfen en az bir ürün ve fiyat girin');
            return;
        }

        const data = {
            work_id: <?php echo $selectedWorkId; ?>,
            sirket_id: companyId,
            cari_kod: cariKod,
            baslik: title,
            aciklama: $('#workDescription').val().trim(),
            aktif: $('#workActive').is(':checked') ? 1 : 0,
            olusturan_yonetici_id: yoneticiId,
            urunler: products
        };

        $.ajax({
            url: 'api/save_special_pricing.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(data),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Çalışma başarıyla kaydedildi');
                    window.location.href = 'fiyat_calismasi.php?id=' + companyId + '&work_id=' + response.work_id;
                } else {
                    alert('Hata: ' + (response.message || 'Bilinmeyen hata'));
                }
            },
            error: function() {
                alert('Kaydetme sırasında hata oluştu');
            }
        });
    });

    // Çalışma silme
    $('#deleteWorkBtn').on('click', function() {
        if (!confirm('Bu çalışmayı silmek istediğinizden emin misiniz? Tüm ürünler de silinecektir.')) {
            return;
        }

        $.ajax({
            url: 'api/delete_special_pricing.php',
            method: 'POST',
            data: { work_id: <?php echo $selectedWorkId; ?> },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Çalışma silindi');
                    window.location.href = 'fiyat_calismasi.php?id=' + companyId;
                } else {
                    alert('Hata: ' + (response.message || 'Bilinmeyen hata'));
                }
            },
            error: function() {
                alert('Silme sırasında hata oluştu');
            }
        });
    });

    // Formu temizle
    $('#clearFormBtn').on('click', function() {
        if (confirm('Formu temizlemek istediğinizden emin misiniz?')) {
            window.location.href = 'fiyat_calismasi.php?id=' + companyId;
        }
    });

    function showAutocomplete(products) {
        // Bu eski autocomplete, modal ile değiştirdik ama yedek olarak kalsın
    }

    function hideAutocomplete() {
        $('#product-autocomplete-global').hide();
    }
});
</script>
</body>
</html>
