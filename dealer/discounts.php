<?php
// Bayi İskontolar
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

// İskonto bilgilerini çek
$result = $db->query("SELECT * FROM iskontolar ORDER BY sira ASC");
$discounts = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$db->close();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İskontolarım - GEMAS B2B Portal</title>
    <link rel="shortcut icon" href="../assets/images/favicon.ico">
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/icons.min.css" rel="stylesheet">
    <link href="../assets/css/app.min.css" rel="stylesheet">
    <link href="../assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <style>
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 30px;
            color: white;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        .discount-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s;
        }
        .discount-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        .discount-value {
            font-size: 32px;
            font-weight: 700;
            color: #667eea;
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
                        <h2 class="mb-2">
                            <i class="mdi mdi-percent me-2"></i>İskontolarım
                        </h2>
                        <p class="mb-0 opacity-90">
                            Size özel tanımlı iskonto oranlarını görüntüleyin
                        </p>
                    </div>
                    
                    <?php if (count($discounts) > 0): ?>
                    <!-- İskonto Tablosu -->
                    <div class="table-card">
                        <h5 class="mb-4">
                            <i class="mdi mdi-tag-multiple text-primary me-2"></i>İskonto Oranları
                        </h5>
                        <div class="table-responsive">
                            <table id="discountTable" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Sıra</th>
                                        <th>Marka</th>
                                        <th>Peşin</th>
                                        <th>Kredi Kartı</th>
                                        <th>60 Gün</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($discounts as $discount): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($discount['sira'] ?? '-') ?></span>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($discount['marka'] ?? '-') ?></strong>
                                        </td>
                                        <td>
                                            <span class="discount-value" style="font-size: 18px;">
                                                <?= htmlspecialchars($discount['pesin'] ?? '0') ?>%
                                            </span>
                                        </td>
                                        <td>
                                            <span class="discount-value" style="font-size: 18px; color: #28a745;">
                                                <?= htmlspecialchars($discount['kredikarti'] ?? '0') ?>%
                                            </span>
                                        </td>
                                        <td>
                                            <span class="discount-value" style="font-size: 18px; color: #17a2b8;">
                                                <?= htmlspecialchars($discount['atmisgun'] ?? '0') ?>%
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Bilgilendirme -->
                    <div class="alert alert-info mt-4">
                        <i class="mdi mdi-information-outline me-2"></i>
                        <strong>Bilgilendirme:</strong> İskonto oranları markalara ve ödeme koşullarına göre değişiklik gösterebilir. 
                        Sipariş oluştururken güncel iskonto oranları otomatik olarak uygulanacaktır.
                    </div>
                    
                    <?php else: ?>
                    <!-- İskonto Yok -->
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="mdi mdi-percent-outline" style="font-size: 80px; color: #ccc;"></i>
                        </div>
                        <h4 class="text-muted">Henüz iskonto tanımlanmamış</h4>
                        <p class="text-muted">
                            Size özel iskonto tanımlamaları için lütfen satış temsilciniz ile iletişime geçin.
                        </p>
                        <div class="mt-4">
                            <a href="support.php" class="btn btn-primary">
                                <i class="mdi mdi-headset me-2"></i>Destek Al
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
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
    <script src="../assets/js/app.js"></script>
    
    <script>
    $(document).ready(function() {
        $('#discountTable').DataTable({
            language: {
                url: '../assets/libs/datatables.net/i18n/tr.json'
            },
            pageLength: 25,
            order: [[0, 'asc']],
            searching: true
        });
    });
    </script>
</body>
</html>

