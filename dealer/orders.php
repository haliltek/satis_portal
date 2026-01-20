<?php
// Bayi Siparişler
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
$filterStatus = $_GET['status'] ?? '';
$filterStartDate = $_GET['start_date'] ?? '';
$filterEndDate = $_GET['end_date'] ?? '';

// Siparişleri çek
$query = "SELECT * FROM ogteklif2 WHERE sirket_arp_code = ?";
$params = [$cariCode];
$types = 's';

if ($filterStatus !== '') {
    $query .= " AND durum LIKE ?";
    $statusMap = [
        '1' => '%Bekle%',
        '2' => '%Onay%',
        '3' => '%Tamamlan%',
        '4' => '%İptal%'
    ];
    $params[] = $statusMap[$filterStatus] ?? '%';
    $types .= 's';
}

if ($filterStartDate) {
    $query .= " AND tekliftarihi >= ?";
    $params[] = $filterStartDate . ' 00:00:00';
    $types .= 's';
}

if ($filterEndDate) {
    $query .= " AND tekliftarihi <= ?";
    $params[] = $filterEndDate . ' 23:59:59';
    $types .= 's';
}

$query .= " ORDER BY tekliftarihi DESC";

$stmt = $db->prepare($query);
if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Siparişlerim - GEMAS B2B Portal</title>
    <link rel="shortcut icon" href="../assets/images/favicon.ico">
    <?php include "includes/styles.php"; ?>
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
                                    <i class="mdi mdi-format-list-bulleted me-2"></i>Siparişlerim
                                </h2>
                                <p class="mb-0 opacity-90">
                                    Tüm sipariş bilgilerinizi görüntüleyin ve takip edin
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <a href="products.php" class="btn btn-light btn-lg">
                                    <i class="mdi mdi-cart-plus me-2"></i>Yeni Sipariş
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filtreler -->
                    <div class="filter-card">
                        <h5 class="mb-3"><i class="mdi mdi-filter me-2"></i>Filtreleme</h5>
                        <form method="GET" action="">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Durum</label>
                                        <select class="form-select" name="status">
                                            <option value="">Tümü</option>
                                            <option value="1" <?= $filterStatus === '1' ? 'selected' : '' ?>>Beklemede</option>
                                            <option value="2" <?= $filterStatus === '2' ? 'selected' : '' ?>>Onaylandı</option>
                                            <option value="3" <?= $filterStatus === '3' ? 'selected' : '' ?>>Tamamlandı</option>
                                            <option value="4" <?= $filterStatus === '4' ? 'selected' : '' ?>>İptal</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Başlangıç Tarihi</label>
                                        <input type="date" class="form-control" name="start_date" 
                                               value="<?= htmlspecialchars($filterStartDate) ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Bitiş Tarihi</label>
                                        <input type="date" class="form-control" name="end_date" 
                                               value="<?= htmlspecialchars($filterEndDate) ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">&nbsp;</label>
                                        <div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="mdi mdi-filter-check me-2"></i>Filtrele
                                            </button>
                                            <a href="orders.php" class="btn btn-outline-secondary">
                                                <i class="mdi mdi-refresh"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Siparişler Tablosu -->
                    <div class="table-card">
                        <h5 class="mb-4">
                            <i class="mdi mdi-cart-outline text-primary me-2"></i>Sipariş Listesi
                        </h5>
                        
                        <?php if (count($orders) > 0): ?>
                        <div class="table-responsive">
                            <table id="ordersTable" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Sipariş No</th>
                                        <th>Tarih</th>
                                        <th>Durum</th>
                                        <th>Toplam Tutar</th>
                                        <th>Açıklama</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                    <?php $statusInfo = getStatusInfo($order['durum']); ?>
                                    <tr>
                                        <td>
                                            <strong>#<?= htmlspecialchars($order['id']) ?></strong>
                                        </td>
                                        <td>
                                            <?= !empty($order['tekliftarihi']) ? date('d.m.Y H:i', strtotime($order['tekliftarihi'])) : '-' ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $statusInfo['color'] ?>">
                                                <i class="mdi mdi-<?= $statusInfo['icon'] ?> me-1"></i>
                                                <?= $statusInfo['text'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong class="text-success">
                                                ₺<?= number_format(floatval($order['geneltoplam'] ?? 0), 2, ',', '.') ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars(mb_substr($order['notes1'] ?? '-', 0, 50)) ?>
                                            <?= mb_strlen($order['notes1'] ?? '') > 50 ? '...' : '' ?>
                                        </td>
                                        <td>
                                            <a href="order_detail.php?id=<?= $order['id'] ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="mdi mdi-eye"></i> Detay
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="mdi mdi-cart-off" style="font-size: 64px; color: #ccc;"></i>
                            <p class="text-muted mt-3">
                                <?php if ($filterStatus !== '' || $filterStartDate || $filterEndDate): ?>
                                    Belirtilen kriterlere uygun sipariş bulunamadı.
                                <?php else: ?>
                                    Henüz siparişiniz bulunmamaktadır.
                                <?php endif; ?>
                            </p>
                            <a href="products.php" class="btn btn-primary mt-2">
                                <i class="mdi mdi-cart-plus me-2"></i>İlk Siparişinizi Verin
                            </a>
                        </div>
                        <?php endif; ?>
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
    <script src="../assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="../assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="../assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="../assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>
    <script src="../assets/js/app.js"></script>
    
    <script>
    $(document).ready(function() {
        $('#ordersTable').DataTable({
            language: {
                url: '../assets/libs/datatables.net/i18n/tr.json'
            },
            pageLength: 25,
            order: [[1, 'desc']],
            responsive: true
        });
    });
    </script>
</body>
</html>

