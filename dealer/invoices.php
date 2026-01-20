<?php
// Bayi Faturalar
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

// Filtreleme
$filterStartDate = $_GET['start_date'] ?? '';
$filterEndDate = $_GET['end_date'] ?? '';
$filterStatus = $_GET['status'] ?? '';

// Fatura verilerini çek
$query = "SELECT * FROM faturairsaliye WHERE cari_kod = ?";
$params = [$cariCode];
$types = 's';

if ($filterStartDate) {
    $query .= " AND tarih >= ?";
    $params[] = $filterStartDate;
    $types .= 's';
}

if ($filterEndDate) {
    $query .= " AND tarih <= ?";
    $params[] = $filterEndDate;
    $types .= 's';
}

$query .= " ORDER BY tarih DESC, fatura_no DESC";

$stmt = $db->prepare($query);
if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$invoices = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$db->close();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faturalarım - GEMAS B2B Portal</title>
    <link rel="shortcut icon" href="../assets/images/favicon.ico">
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/icons.min.css" rel="stylesheet">
    <link href="../assets/css/app.min.css" rel="stylesheet">
    <link href="../assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="../assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet">
    <style>
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 30px;
            color: white;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        .table-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        .btn-filter {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            padding: 10px 30px;
            border-radius: 10px;
        }
        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
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
                                    <i class="mdi mdi-file-document me-2"></i>Faturalarım
                                </h2>
                                <p class="mb-0 opacity-90">
                                    Tüm fatura ve irsaliye bilgilerinizi görüntüleyin
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="badge bg-light text-dark" style="padding: 10px 20px; font-size: 14px;">
                                    <i class="mdi mdi-receipt me-2"></i>Toplam: <?= count($invoices) ?> Fatura
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filtreler -->
                    <div class="filter-card">
                        <h5 class="mb-3"><i class="mdi mdi-filter me-2"></i>Filtreleme</h5>
                        <form method="GET" action="">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Başlangıç Tarihi</label>
                                        <input type="date" class="form-control" name="start_date" 
                                               value="<?= htmlspecialchars($filterStartDate) ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Bitiş Tarihi</label>
                                        <input type="date" class="form-control" name="end_date" 
                                               value="<?= htmlspecialchars($filterEndDate) ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">&nbsp;</label>
                                        <div>
                                            <button type="submit" class="btn btn-filter">
                                                <i class="mdi mdi-filter-check me-2"></i>Filtrele
                                            </button>
                                            <a href="invoices.php" class="btn btn-outline-secondary">
                                                <i class="mdi mdi-refresh me-2"></i>Temizle
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Faturalar Tablosu -->
                    <div class="table-card">
                        <h5 class="mb-4">
                            <i class="mdi mdi-file-document-outline text-primary me-2"></i>Fatura Listesi
                        </h5>
                        
                        <?php if (count($invoices) > 0): ?>
                        <div class="table-responsive">
                            <table id="invoiceTable" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Fatura No</th>
                                        <th>Tarih</th>
                                        <th>Belge Tipi</th>
                                        <th>Tutar</th>
                                        <th>KDV</th>
                                        <th>Genel Toplam</th>
                                        <th>Döviz</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($invoices as $invoice): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($invoice['fatura_no'] ?? '-') ?></strong>
                                        </td>
                                        <td>
                                            <?php
                                            if (!empty($invoice['tarih'])) {
                                                $date = new DateTime($invoice['tarih']);
                                                echo $date->format('d.m.Y');
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            $docType = $invoice['belge_turu'] ?? '';
                                            $badge = 'secondary';
                                            if (strpos($docType, 'Fatura') !== false) {
                                                $badge = 'primary';
                                            } elseif (strpos($docType, 'İrsaliye') !== false) {
                                                $badge = 'info';
                                            }
                                            ?>
                                            <span class="badge bg-<?= $badge ?>">
                                                <?= htmlspecialchars($docType ?: 'Belge') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= number_format(floatval($invoice['tutar'] ?? 0), 2, ',', '.') ?>
                                        </td>
                                        <td>
                                            <?= number_format(floatval($invoice['kdv'] ?? 0), 2, ',', '.') ?>
                                        </td>
                                        <td>
                                            <strong class="text-success">
                                                <?= number_format(floatval($invoice['genel_toplam'] ?? 0), 2, ',', '.') ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?= htmlspecialchars($invoice['doviz_cinsi'] ?? 'TRY') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="viewInvoiceDetail(<?= $invoice['fatura_id'] ?>)">
                                                <i class="mdi mdi-eye"></i> Detay
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="mdi mdi-file-document-outline" style="font-size: 64px; color: #ccc;"></i>
                            <p class="text-muted mt-3">
                                <?php if ($filterStartDate || $filterEndDate): ?>
                                    Belirtilen kriterlere uygun fatura bulunamadı.
                                <?php else: ?>
                                    Henüz faturanız bulunmamaktadır.
                                <?php endif; ?>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                </div>
            </div>
            <?php include "includes/footer.php"; ?>
        </div>
    </div>

    <!-- Modal için placeholder -->
    <div class="modal fade" id="invoiceDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Fatura Detayı</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="invoiceDetailContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Yükleniyor...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/libs/jquery/jquery.min.js"></script>
    <script src="../assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/libs/metismenu/metisMenu.min.js"></script>
    <script src="../assets/libs/simplebar/simplebar.min.js"></script>
    <script src="../assets/libs/node-waves/waves.min.js"></script>
    <script src="../assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="../assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="../assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="../assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>
    <script src="../assets/js/app.js"></script>
    
    <script>
    $(document).ready(function() {
        $('#invoiceTable').DataTable({
            language: {
                url: '../assets/libs/datatables.net/i18n/tr.json'
            },
            pageLength: 25,
            order: [[1, 'desc']],
            responsive: true
        });
    });
    
    function viewInvoiceDetail(invoiceId) {
        // Modal'ı göster
        var modal = new bootstrap.Modal(document.getElementById('invoiceDetailModal'));
        modal.show();
        
        // AJAX ile detay bilgilerini yükle (şimdilik basit bir mesaj)
        $('#invoiceDetailContent').html('<div class="alert alert-info">Fatura ID: ' + invoiceId + ' - Detay bilgileri yüklenecek</div>');
    }
    </script>
</body>
</html>

