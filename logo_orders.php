<?php
include "fonk.php";
oturumkontrol();

if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Bayi') {
    header('Location: anasayfa.php');
    exit;
}

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date   = $_GET['end_date'] ?? date('Y-m-d');
$limit      = 200;

gempa_logo_veritabani(); 
global $gempa_logo_db;

if (!$gempa_logo_db) {
    die("GEMPA2026 veritabanına bağlanılamadı.");
}

// Logo SQL Query Matches Screenshot Columns:
// TARİH, FİŞ NO, BELGE NO, CARİ HESAP UNVANI, İŞ AKIŞ KODU, DÖVİZLİ TUTAR, DOK. İZLEME, E-FATURA, TUTAR, AMBAR, SATIŞ ELEMANI, BÖLÜM, FABRIKA, TİP
$sql = "
    SELECT TOP $limit
        O.DATE_,
        O.FICHENO,
        O.DOCODE,
        C.DEFINITION_ AS CLIENT_NAME,
        '' AS WORK_FLOW_CODE, -- Genelde boş veya özel alan
        O.REPORTNET AS FX_AMOUNT, -- Raporlama dövizi tutarı (Dövizli Tutar)
        O.TRCURR, -- 1=USD, 20=EUR, 160=TL
        '' AS DOC_TRACKING,
        (CASE WHEN C.ACCEPT_EINV = 1 THEN 'e-Fatura' WHEN C.ACCEPT_EARC = 1 THEN 'e-Arşiv' ELSE '' END) AS EFATURA_TYPE,
        O.NETTOTAL, -- Tutar
        O.SOURCEINDEX AS AMBAR,
        S.DEFINITION_ AS SALESMAN,
        O.BRANCH AS BOLUM,
        O.FACTORYNR AS FABRIKA,
        (CASE O.TRCODE 
            WHEN 1 THEN 'Satış Siparişi' 
            ELSE 'Tip: ' + CAST(O.TRCODE AS VARCHAR) 
         END) AS TIP,
         O.STATUS
    FROM LG_566_01_ORFICHE O
    LEFT JOIN LG_566_CLCARD C ON O.CLIENTREF = C.LOGICALREF
    LEFT JOIN LG_SLSMAN S ON O.SALESMANREF = S.LOGICALREF
    WHERE O.TRCODE = 1
    AND O.DATE_ BETWEEN :start_date AND :end_date
    ORDER BY O.DATE_ DESC
";

try {
    $stmt = $gempa_logo_db->prepare($sql);
    $stmt->bindValue(':start_date', $start_date);
    $stmt->bindValue(':end_date', $end_date);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Sorgu Hatası: " . $e->getMessage());
}

function getCurrencySymbol($code) {
    if ($code == 1) return '$';
    if ($code == 20) return '€';
    if ($code == 160) return '₺';
    return '';
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8" />
    <title>Satış Siparişleri (ERP Görünümü)</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <link href="assets/css/icons.min.css" rel="stylesheet" />
    <link href="assets/css/app.min.css" rel="stylesheet" />
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" />
    <style>
        /* ERP Style Customization */
        body { background-color: #f0f2f5; font-size: 11px; }
        .card { border-radius: 0; box-shadow: none; border: 1px solid #dcdcdc; }
        .page-title-box { padding-bottom: 10px; }
        
        /* Table Styling */
        table.erp-table {
            width: 100%;
            border-collapse: collapse;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #fff;
        }
        table.erp-table thead th {
            background: linear-gradient(to bottom, #e1e0ee 0%, #d1d0e8 100%);
            color: #333;
            font-weight: 600;
            padding: 4px 8px;
            border: 1px solid #a0a0a0;
            text-transform: uppercase;
            font-size: 10px;
            white-space: nowrap;
        }
        table.erp-table tbody td {
            padding: 2px 8px;
            border: 1px solid #d0d0d0;
            color: #000;
            white-space: nowrap;
            height: 22px;
        }
        table.erp-table tbody tr:nth-child(even) { background-color: #fbfbfb; }
        table.erp-table tbody tr:hover { background-color: #fff3cd; cursor: pointer; }
        
        /* Specific Column Colors from Screenshot */
        .col-green { color: #008000; font-weight: 500; }
        .col-red { color: #d00000; }
        .col-blue { color: #0000ff; }
        
        .form-control-sm { border-radius: 0; font-size: 11px; }
        .btn-sm { border-radius: 0; font-size: 11px; }
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
                <div class="container-fluid p-2">

                    <!-- Filter Bar -->
                    <div class="card mb-2">
                        <div class="card-body p-2">
                            <form method="GET" class="row g-2 align-items-center">
                                <div class="col-auto"><label class="fw-bold">Tarih Aralığı:</label></div>
                                <div class="col-auto">
                                    <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($start_date) ?>">
                                </div>
                                <div class="col-auto">-</div>
                                <div class="col-auto">
                                    <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($end_date) ?>">
                                </div>
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-primary btn-sm px-3">Listele</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- ERP Table -->
                    <div class="card">
                        <div class="card-body p-0 overflow-auto">
                            <table id="erpTable" class="erp-table table-hover">
                                <thead>
                                    <tr>
                                        <th>TARİH</th>
                                        <th>FİŞ NO</th>
                                        <th>BELGE NO</th>
                                        <th>CARİ HESAP UNVANI</th>
                                        <th>İŞ AKIŞ KODU</th>
                                        <th>DÖVİZLİ TUTAR</th>
                                        <th>DOK. İZLEM...</th>
                                        <th>E-FATURA</th>
                                        <th>TUTAR</th>
                                        <th>AMBAR</th>
                                        <th>SATIŞ ELEMANI...</th>
                                        <th>BÖLÜM</th>
                                        <th>FABRIKA</th>
                                        <th>TİP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $o): 
                                        $currencySym = getCurrencySymbol($o['TRCURR']);
                                        // Row coloring logic (example: e-archive green, others black)
                                        $rowClass = '';
                                        $invoiceTypeClass = '';
                                        if ($o['EFATURA_TYPE'] === 'e-Arşiv') {
                                            $rowClass = 'col-green'; // Example from screenshot where some rows are green
                                            $invoiceTypeClass = 'col-green';
                                        }
                                    ?>
                                        <tr class="<?= $rowClass ?>">
                                            <td><?= date('d.m.Y', strtotime($o['DATE_'])) ?></td>
                                            <td><?= htmlspecialchars($o['FICHENO']) ?></td>
                                            <td><?= htmlspecialchars($o['DOCODE']) ?></td>
                                            <td><?= htmlspecialchars($o['CLIENT_NAME']) ?></td>
                                            <td><?= htmlspecialchars($o['WORK_FLOW_CODE']) ?></td>
                                            <td class="text-end fw-bold">
                                                <?= number_format($o['FX_AMOUNT'], 2, ',', '.') ?> <?= $currencySym ?>
                                            </td>
                                            <td><?= htmlspecialchars($o['DOC_TRACKING']) ?></td>
                                            <td class="<?= $invoiceTypeClass ?>"><?= htmlspecialchars($o['EFATURA_TYPE']) ?></td>
                                            <td class="text-end fw-bold"><?= number_format($o['NETTOTAL'], 2, ',', '.') ?></td>
                                            <td class="text-center"><?= htmlspecialchars($o['AMBAR']) ?></td>
                                            <td><?= htmlspecialchars($o['SALESMAN']) ?></td>
                                            <td class="text-center"><?= htmlspecialchars($o['BOLUM']) ?></td>
                                            <td class="text-center"><?= htmlspecialchars($o['FABRIKA']) ?></td>
                                            <td><?= htmlspecialchars($o['TIP']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            // Using standard datatable but with minimal styling options if needed, 
            // strictly following the CSS defined above for the look.
            // Converting to DataTables for sorting features but keeping style
             $('#erpTable').DataTable({
                "scrollY": "70vh",
                "scrollCollapse": true,
                "paging": false,
                "info": false,
                "filter": true,
                "order": [[ 0, "desc" ]],
                "language": {
                     "search": "Ara:",
                     "zeroRecords": "Kayıt bulunamadı",
                }
             });
        });
    </script>
</body>
</html>
