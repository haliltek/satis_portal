<?php
/**
 * Logo Kampanya Test Sayfası
 * LG_CAMPAIGN tablosundan kampanya verilerini okur ve test eder
 */

require_once "fonk.php";
oturumkontrol();

// Sayfa başlığı
$pageTitle = "Logo Kampanya Test Sayfası";

// Logo veritabanı bağlantısı
$config = require __DIR__ . '/config/config.php';
$logo = $config['logo'];

try {
    $dsn = "sqlsrv:Server={$logo['host']};Database={$logo['db']}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ];
    
    if (defined('PDO::SQLSRV_ATTR_ENCODING') && defined('PDO::SQLSRV_ENCODING_UTF8')) {
        $options[PDO::SQLSRV_ATTR_ENCODING] = PDO::SQLSRV_ENCODING_UTF8;
    }
    
    $logoConn = new PDO($dsn, $logo['user'], $logo['pass'], $options);
    
} catch (PDOException $e) {
    $error = "Logo veritabanı bağlantı hatası: " . $e->getMessage();
}

// Kampanya Aktifleştirme İşlemi
if (isset($_POST['action']) && $_POST['action'] == 'activate_campaign' && isset($_POST['campaign_ref'])) {
    try {
        $updateQuery = "UPDATE LG_566_CAMPAIGN SET ACTIVE = 0 WHERE LOGICALREF = ?";
        $updateStmt = $logoConn->prepare($updateQuery);
        $updateStmt->execute([$_POST['campaign_ref']]);
        
        $successMessage = "Kampanya başarıyla aktifleştirildi (LOGICALREF: " . $_POST['campaign_ref'] . ")";
    } catch (Exception $e) {
        $error = "Kampanya aktifleştirilemedi: " . $e->getMessage();
    }
}

// Kampanya verileri
$campaigns = [];
$selectedCampaign = null;
$campaignDetails = null;

// Kampanyaları çek
if (!isset($error)) {
    try {
        // Filtreleme mantığı
        $searchKeyword = $_GET['search_keyword'] ?? '';
        
        // Varsayılan olarak sadece PASİF (ACTIVE = 0) olanları getir
        $activeFilter = 0; 
        
        // Eğer arama yapılıyorsa aktiflik filtresini kaldırabiliriz veya kullanıcı seçimine bırakabiliriz
        // Şimdilik istek üzerine "İLK OLARAK SADECE ACTIVE=0" dendiği için
        // Arama yoksa ACTIVE=0, arama varsa Hepsi şekline dönüştürüyorum.
        
        if (empty($searchKeyword)) {
            $query = "SELECT * FROM LG_566_CAMPAIGN WHERE ACTIVE = 0 ORDER BY PRIORITY DESC, CODE";
            $stmt = $logoConn->prepare($query);
            $stmt->execute();
        } else {
            $query = "SELECT * FROM LG_566_CAMPAIGN WHERE (CODE LIKE ? OR NAME LIKE ?) ORDER BY PRIORITY DESC, CODE";
            $stmt = $logoConn->prepare($query);
            $searchTerm = '%' . $searchKeyword . '%';
            $stmt->execute([$searchTerm, $searchTerm]);
        }
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Tarihleri dönüştür (Logo formatı: gün sayısı 1899-12-30'dan itibaren)
        $begDate = null;
        $endDate = null;
        
        if ($row['BEGDATE']) {
            $begDate = date('d.m.Y', strtotime('1899-12-30 + ' . $row['BEGDATE'] . ' days'));
        }
        
        if ($row['ENDDATE']) {
            $endDate = date('d.m.Y', strtotime('1899-12-30 + ' . $row['ENDDATE'] . ' days'));
        }
        
        // Tüm satırı (raw data) al
        $campaignData = $row;
        
        // Tarihleri ekle
        $campaignData['formatted_BEGDATE'] = $begDate;
        $campaignData['formatted_ENDDATE'] = $endDate;
        
        $campaigns[] = $campaignData;
    }
    
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Seçili kampanya detaylarını al
$campaignLines = [];
if (isset($_GET['campaign_ref'])) {
    $campaignRef = (int)$_GET['campaign_ref'];
    
    foreach ($campaigns as $camp) {
        if ($camp['LOGICALREF'] == $campaignRef) {
            $selectedCampaign = $camp;
            
            // Kampanya satırlarını (koşulları) çek
            if (!isset($error)) {
                try {
                    $linesQuery = "SELECT 
                        LOGICALREF,
                        CLIENTCODE,
                        PAYPLANCODE,
                        CONDITIONTYPE,
                        BEGDATE,
                        ENDDATE,
                        PRIORITY,
                        MTRLCONDTYPE,
                        ORGSALESMANCODE,
                        DISCPER1,
                        DISCPER2,
                        DISCPER3,
                        DISCPER4,
                        DISCPER5,
                        DISCPER6,
                        DISAMNT1,
                        DISAMNT2,
                        DISAMNT3,
                        VARIANTCODE
                    FROM LG_566_CAMPAIGNLINES
                    WHERE CAMPAIGNREF = ?
                    ORDER BY PRIORITY DESC, LOGICALREF";
                    
                    $linesStmt = $logoConn->prepare($linesQuery);
                    $linesStmt->execute([$campaignRef]);
                    
                    while ($lineRow = $linesStmt->fetch(PDO::FETCH_ASSOC)) {
                        // Tarihleri dönüştür
                        $lineBegDate = null;
                        $lineEndDate = null;
                        
                        if ($lineRow['BEGDATE']) {
                            $lineBegDate = date('d.m.Y', strtotime('1899-12-30 + ' . $lineRow['BEGDATE'] . ' days'));
                        }
                        
                        if ($lineRow['ENDDATE']) {
                            $lineEndDate = date('d.m.Y', strtotime('1899-12-30 + ' . $lineRow['ENDDATE'] . ' days'));
                        }
                        
                        // Tüm satırı al
                        $lineData = $lineRow;
                        $lineData['formatted_BEGDATE'] = $lineBegDate;
                        $lineData['formatted_ENDDATE'] = $lineEndDate;
                        
                        $campaignLines[] = $lineData;
                    }
                } catch (Exception $e) {
                    // Hata durumunda sadece log'la, sayfa çalışmaya devam etsin
                    error_log("Campaign lines query error: " . $e->getMessage());
                }
            }
            
            break;
        }
    }
}

// Test için cari kodu
$testClientCode = $_POST['test_client_code'] ?? '';
$matchingCampaigns = [];

if ($testClientCode) {
    foreach ($campaigns as $camp) {
        // Cari kodu kontrolü (boş ise tüm cariler için geçerli)
        if (empty($camp['CLIENTCODE']) || $camp['CLIENTCODE'] == $testClientCode) {
            $matchingCampaigns[] = $camp;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/icons.min.css" rel="stylesheet">
    <link href="assets/css/app.min.css" rel="stylesheet">
    <style>
        .campaign-card {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
            background: #fff;
            transition: box-shadow 0.3s;
        }
        
        .campaign-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .campaign-active {
            border-left: 4px solid #28a745;
        }
        
        .campaign-inactive {
            border-left: 4px solid #dc3545;
            opacity: 0.7;
        }
        
        .badge-cardtype {
            font-size: 10px;
            padding: 4px 8px;
        }
        
        .detail-label {
            font-weight: 600;
            color: #555;
            font-size: 12px;
        }
        
        .detail-value {
            color: #333;
            font-size: 13px;
        }
        
        .test-section {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 20px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <header id="page-topbar">
        <?php include "menuler/ustmenu.php"; ?>
        <?php include "menuler/solmenu.php"; ?>
    </header>
    
    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-flex align-items-center justify-content-between">
                            <h4 class="mb-0"><?php echo $pageTitle; ?></h4>
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="index.php">Ana Sayfa</a></li>
                                    <li class="breadcrumb-item active">Logo Kampanya Test</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (isset($error)): ?>
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-danger">
                            <strong>Hata:</strong> <?php echo htmlspecialchars($error); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Kampanya İstatistikleri -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="text-muted mb-0">Toplam Kampanya</h5>
                                <h2 class="mt-2 mb-0"><?php echo count($campaigns); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="text-muted mb-0">Aktif Kampanyalar</h5>
                                <h2 class="mt-2 mb-0 text-success">
                                    <?php echo count(array_filter($campaigns, function($c) { return $c['ACTIVE'] == 0; })); ?>
                                </h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="text-muted mb-0">Satış Kampanyaları</h5>
                                <h2 class="mt-2 mb-0 text-primary">
                                    <?php echo count(array_filter($campaigns, function($c) { return $c['CARDTYPE'] == 2; })); ?>
                                </h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="text-muted mb-0">Satınalma Kampanyaları</h5>
                                <h2 class="mt-2 mb-0 text-info">
                                    <?php echo count(array_filter($campaigns, function($c) { return $c['CARDTYPE'] == 1; })); ?>
                                </h2>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Kampanya Arama Bölümü -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <form method="GET" class="row g-3 align-items-end">
                                    <div class="col-md-4">
                                        <label for="search_keyword" class="form-label">Kampanya Ara (Kod/Ad)</label>
                                        <input type="text" class="form-control" id="search_keyword" name="search_keyword" 
                                               value="<?php echo htmlspecialchars($_GET['search_keyword'] ?? ''); ?>" 
                                               placeholder="Örn: FILTRE">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="mdi mdi-magnify me-1"></i>Ara
                                        </button>
                                    </div>
                                    <div class="col-md-2">
                                        <a href="test_logo_campaigns.php" class="btn btn-secondary w-100">
                                            <i class="mdi mdi-refresh me-1"></i>Sıfırla
                                        </a>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <div class="text-muted small mt-2">
                                            * Pasif kampanyalar da dahil tüm kayıtlar aranır.
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cari Bazlı Kampanya Test Bölümü -->
                <div class="row">
                    <div class="col-12">
                        <div class="test-section">
                            <h5 class="mb-3"><i class="mdi mdi-test-tube me-2"></i>Cari Bazlı Kampanya Testi</h5>
                            <form method="POST" class="row">
                                <div class="col-md-6">
                                    <label for="test_client_code" class="form-label">Cari Kodu:</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="test_client_code" 
                                           name="test_client_code" 
                                           value="<?php echo htmlspecialchars($testClientCode); ?>"
                                           placeholder="Örn: 120.01.E04">
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="mdi mdi-magnify me-1"></i>Kampanyaları Bul
                                    </button>
                                </div>
                            </form>
                            
                            <?php if ($testClientCode && !empty($matchingCampaigns)): ?>
                            <div class="mt-4">
                                <h6 class="text-success">
                                    <i class="mdi mdi-check-circle me-1"></i>
                                    <?php echo count($matchingCampaigns); ?> Kampanya Bulundu
                                </h6>
                                <div class="table-responsive mt-3">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Kod</th>
                                                <th>Kampanya Adı</th>
                                                <th>Başlangıç</th>
                                                <th>Bitiş</th>
                                                <th>Öncelik</th>
                                                <th>Cari Kodu</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($matchingCampaigns as $camp): ?>
                                            <tr>
                                                <td><code><?php echo htmlspecialchars($camp['CODE']); ?></code></td>
                                                <td><?php echo htmlspecialchars($camp['NAME']); ?></td>
                                                <td><?php echo $camp['BEGDATE'] ?: '-'; ?></td>
                                                <td><?php echo $camp['ENDDATE'] ?: '-'; ?></td>
                                                <td><span class="badge bg-info"><?php echo $camp['PRIORITY']; ?></span></td>
                                                <td><?php echo $camp['CLIENTCODE'] ?: 'Tümü'; ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php elseif ($testClientCode): ?>
                            <div class="mt-4">
                                <div class="alert alert-warning mb-0">
                                    <i class="mdi mdi-alert-outline me-1"></i>
                                    Bu cari için kampanya bulunamadı.
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Seçili Kampanya Detayları -->
                <?php if ($selectedCampaign): ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="mdi mdi-format-list-bulleted me-2"></i>
                                    Kampanya Detayları: <?php echo htmlspecialchars($selectedCampaign['NAME']); ?>
                                </h5>
                                <a href="test_logo_campaigns.php" class="btn btn-sm btn-light">
                                    <i class="mdi mdi-close me-1"></i>Kapat
                                </a>
                           </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <p><strong>Kod:</strong> <code><?php echo htmlspecialchars($selectedCampaign['CODE']); ?></code></p>
                                        <p><strong>Tür:</strong> <?php echo $selectedCampaign['CARDTYPE'] == 2 ? 'Satış' : 'Satınalma'; ?></p>
                                        <p><strong>Başlangıç:</strong> <?php echo $selectedCampaign['BEGDATE'] ?: '-'; ?></p>
                                        <p><strong>Bitiş:</strong> <?php echo $selectedCampaign['ENDDATE'] ?: '-'; ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Öncelik:</strong> <span class="badge bg-secondary"><?php echo $selectedCampaign['PRIORITY']; ?></span></p>
                                        <p><strong>Cari Kodu:</strong> <?php echo $selectedCampaign['CLIENTCODE'] ?: 'Tümü'; ?></p>
                                        <p><strong>Ödeme Planı:</strong> <?php echo $selectedCampaign['PAYPLANCODE'] ?: '-'; ?></p>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <h6 class="mb-3"><i class="mdi mdi-format-list-checks me-2"></i>Kampanya Koşulları ve İskonto Formülleri</h6>
                                
                                <?php if (empty($campaignLines)): ?>
                                <div class="alert alert-info">
                                    <i class="mdi mdi-information-outline me-1"></i>
                                    Bu kampanya için koşul/formül tanımlanmamış.
                                </div>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Ref</th>
                                                <th>Cari Kodu</th>
                                                <th>Ödeme Planı</th>
                                                <th>Koşul Tipi</th>
                                                <th>Malzeme Koşul</th>
                                                <th>Öncelik</th>
                                                <th>İskonto %1</th>
                                                <th>İskonto %2</th>
                                                <th>İskonto %3</th>
                                                <th>İskonto Tutar</th>
                                                <th>Varyant</th>
                                                <th>Tarih</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($campaignLines as $line): ?>
                                            <tr>
                                                <td><small><?php echo $line['LOGICALREF']; ?></small></td>
                                                <td><?php echo $line['CLIENTCODE'] ?: '-'; ?></td>
                                                <td><?php echo $line['PAYPLANCODE'] ?: '-'; ?></td>
                                                <td><?php echo $line['CONDITIONTYPE'] ?: '-'; ?></td>
                                                <td><?php echo $line['MTRLCONDTYPE'] ?: '-'; ?></td>
                                                <td><span class="badge bg-secondary"><?php echo $line['PRIORITY']; ?></span></td>
                                                <td class="text-end">
                                                    <?php 
                                                    $disc1 = floatval($line['DISCPER1']);
                                                    echo $disc1 > 0 ? '<strong class="text-success">' . number_format($disc1, 2) . '%</strong>' : '-';
                                                    ?>
                                                </td>
                                                <td class="text-end">
                                                    <?php 
                                                    $disc2 = floatval($line['DISCPER2']);
                                                    echo $disc2 > 0 ? number_format($disc2, 2) . '%' : '-';
                                                    ?>
                                                </td>
                                                <td class="text-end">
                                                    <?php 
                                                    $disc3 = floatval($line['DISCPER3']);
                                                    echo $disc3 > 0 ? number_format($disc3, 2) . '%' : '-';
                                                    ?>
                                                </td>
                                                <td class="text-end">
                                                    <?php 
                                                    $amt = floatval($line['DISAMNT1']);
                                                    echo $amt > 0 ? number_format($amt, 2) : '-';
                                                    ?>
                                                </td>
                                                <td><?php echo $line['VARIANTCODE'] ?: '-'; ?></td>
                                                <td>
                                                    <small>
                                                        <?php echo $line['BEGDATE'] ? $line['BEGDATE'] : '-'; ?>
                                                        -
                                                        <?php echo $line['ENDDATE'] ? $line['ENDDATE'] : '-'; ?>
                                                    </small>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
                                
                                <hr>
                                
                                <h6 class="mb-3"><i class="mdi mdi-code-tags me-2"></i>Ham Veri (Debug)</h6>
                                <div class="accordion" id="debugAccordion">
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingHeader">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseHeader">
                                                Kampanya Başlık Verisi (Tüm Sütunlar)
                                            </button>
                                        </h2>
                                        <div id="collapseHeader" class="accordion-collapse collapse" data-bs-parent="#debugAccordion">
                                            <div class="accordion-body">
                                                <pre style="max-height: 300px; overflow: auto; background: #f8f9fa; padding: 10px;"><?php print_r($selectedCampaign); ?></pre>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingLines">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLines">
                                                Kampanya Satır Verileri (Tüm Sütunlar)
                                            </button>
                                        </h2>
                                        <div id="collapseLines" class="accordion-collapse collapse" data-bs-parent="#debugAccordion">
                                            <div class="accordion-body">
                                                <pre style="max-height: 300px; overflow: auto; background: #f8f9fa; padding: 10px;"><?php 
                                                // Tarih objelerini stringe çevir ki okunabilsin
                                                echo print_r($campaignLines, true); 
                                                ?></pre>
                                            </div>
                                        </div>
                                    </div>
                                </div>                              <div class="alert alert-secondary mt-3">
                                    <h6 class="mb-2"><i class="mdi mdi-information me-1"></i>İskonto Formülü Açıklaması</h6>
                                    <ul class="mb-0">
                                        <li><strong>İskonto %1-6:</strong> Kademeli iskonto yüzdeleri (DISCPER1-6)</li>
                                        <li><strong>İskonto Tutar:</strong> Sabit iskonto tutarları (DISAMNT1-3)</li>
                                        <li><strong>Koşul Tipi:</strong> Kampanyanın hangi koşulda uygulanacağı</li>
                                        <li><strong>Malzeme Koşul:</strong> Ürün bazlı koşullar</li>
                                        <li><strong>Varyant:</strong> Kampanya varyasyon kodu</li>
                                    </ul>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Kampanya Listesi -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Tüm Kampanyalar</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($campaigns)): ?>
                                <div class="alert alert-info">
                                    <i class="mdi mdi-information-outline me-1"></i>
                                    Sistemde kayıtlı kampanya bulunamadı.
                                </div>
                                <?php else: ?>
                                <?php foreach ($campaigns as $campaign): ?>
                                <div class="campaign-card <?php echo $campaign['ACTIVE'] == 0 ? 'campaign-active' : 'campaign-inactive'; ?>">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <h6 class="mb-2">
                                                <span class="badge badge-cardtype <?php echo $campaign['CARDTYPE'] == 2 ? 'bg-primary' : 'bg-info'; ?>">
                                                    <?php echo $campaign['CARDTYPE'] == 2 ? 'Satış' : 'Satınalma'; ?>
                                                </span>
                                                <code class="ms-2"><?php echo htmlspecialchars($campaign['CODE']); ?></code>
                                                <span class="ms-2"><?php echo htmlspecialchars($campaign['NAME']); ?></span>
                                            </h6>
                                            
                                            <div class="row mt-3">
                                                <div class="col-md-6">
                                                    <div class="mb-2">
                                                        <span class="detail-label">Başlangıç Tarihi:</span>
                                                        <span class="detail-value ms-2"><?php echo $campaign['BEGDATE'] ?: '-'; ?></span>
                                                    </div>
                                                    <div class="mb-2">
                                                        <span class="detail-label">Bitiş Tarihi:</span>
                                                        <span class="detail-value ms-2"><?php echo $campaign['ENDDATE'] ?: '-'; ?></span>
                                                    </div>
                                                    <div class="mb-2">
                                                        <span class="detail-label">Öncelik:</span>
                                                        <span class="badge bg-secondary ms-2"><?php echo $campaign['PRIORITY']; ?></span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <?php if ($campaign['CLIENTCODE']): ?>
                                                    <div class="mb-2">
                                                        <span class="detail-label">Cari Kodu:</span>
                                                        <code class="ms-2"><?php echo htmlspecialchars($campaign['CLIENTCODE']); ?></code>
                                                    </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($campaign['CLSPECODE']): ?>
                                                    <div class="mb-2">
                                                        <span class="detail-label">Cari Özel Kodu:</span>
                                                        <code class="ms-2"><?php echo htmlspecialchars($campaign['CLSPECODE']); ?></code>
                                                    </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($campaign['PAYPLANCODE']): ?>
                                                    <div class="mb-2">
                                                        <span class="detail-label">Ödeme Planı:</span>
                                                        <code class="ms-2"><?php echo htmlspecialchars($campaign['PAYPLANCODE']); ?></code>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <div class="d-flex justify-content-end align-items-center">
                                                <span class="badge <?php echo $campaign['ACTIVE'] == 0 ? 'bg-success' : 'bg-danger'; ?> mb-2 me-2">
                                                    <?php echo $campaign['ACTIVE'] == 0 ? 'Aktif' : 'Pasif'; ?>
                                                </span>
                                                
                                                <?php if ($campaign['ACTIVE'] == 1): ?>
                                                <form method="POST" onsubmit="return confirm('Bu kampanyayı aktifleştirmek istediğinize emin misiniz?');">
                                                    <input type="hidden" name="action" value="activate_campaign">
                                                    <input type="hidden" name="campaign_ref" value="<?php echo $campaign['LOGICALREF']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-success mb-2" title="Kampanyayı Aktifleştir">
                                                        <i class="mdi mdi-check"></i> Aktif Yap
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </div>
                                            <br>
                                            <small class="text-muted">Ref: <?php echo $campaign['LOGICALREF']; ?></small>
                                            <br><br>
                                            <a href="?campaign_ref=<?php echo $campaign['LOGICALREF']; ?>" class="btn btn-sm btn-info mt-2">
                                                <i class="mdi mdi-eye me-1"></i>Detayları Gör
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                                <?php if (isset($successMessage)): ?>
                                <div class="alert alert-success">
                                    <i class="mdi mdi-check-circle me-2"></i>
                                    <?php echo $successMessage; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Notlar -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card border-info">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="mdi mdi-information me-2"></i>Test Notları</h6>
                            </div>
                            <div class="card-body">
                                <ul class="mb-0">
                                    <li>Bu sayfa Logo ERP'deki <code>LG_566_CAMPAIGN</code> tablosundan kampanya verilerini okur</li>
                                    <li>CARDTYPE: 1 = Satınalma, 2 = Satış kampanyası</li>
                                    <li>ACTIVE: 1 = Aktif, 0 = Pasif kampanya</li>
                                    <li>CLIENTCODE boş ise kampanya tüm cariler için geçerlidir</li>
                                    <li>PRIORITY yüksek olan kampanyalar önceliklidir</li>
                                    <li>Tarihler Logo formatından (1899-12-30'dan itibaren gün sayısı) dönüştürülmüştür</li>
                                    <li>Cari bazlı test bölümünü kullanarak belirli bir cari için kampanyaları bulabilirsiniz</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
