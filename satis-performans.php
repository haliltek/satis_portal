<?php
include "fonk.php";
oturumkontrol();

if ($userType === 'Bayi') {
    header("Location: anasayfa.php");
    exit;
}

// Tarih aralıklarını belirle
$today = date('Y-m-d');
$firstDayOfWeek = date('Y-m-d', strtotime('monday this week'));
$firstDayOfMonth = date('Y-m-01');

// Tüm yöneticileri ve performanslarını çek
$query = "
    SELECT 
        y.yonetici_id, 
        y.adsoyad,
        y.tur,
        
        -- Günlük
        COUNT(CASE WHEN DATE(t.tekliftarihi) = '$today' THEN 1 END) as daily_count,
        SUM(CASE WHEN DATE(t.tekliftarihi) = '$today' THEN t.tltutar ELSE 0 END) as daily_tl,
        SUM(CASE WHEN DATE(t.tekliftarihi) = '$today' THEN t.dolartutar ELSE 0 END) as daily_dolar,
        SUM(CASE WHEN DATE(t.tekliftarihi) = '$today' THEN t.eurotutar ELSE 0 END) as daily_euro,
        
        -- Haftalık
        COUNT(CASE WHEN DATE(t.tekliftarihi) >= '$firstDayOfWeek' THEN 1 END) as weekly_count,
        SUM(CASE WHEN DATE(t.tekliftarihi) >= '$firstDayOfWeek' THEN t.tltutar ELSE 0 END) as weekly_tl,
        SUM(CASE WHEN DATE(t.tekliftarihi) >= '$firstDayOfWeek' THEN t.dolartutar ELSE 0 END) as weekly_dolar,
        SUM(CASE WHEN DATE(t.tekliftarihi) >= '$firstDayOfWeek' THEN t.eurotutar ELSE 0 END) as weekly_euro,
        
        -- Aylık
        COUNT(CASE WHEN DATE(t.tekliftarihi) >= '$firstDayOfMonth' THEN 1 END) as monthly_count,
        SUM(CASE WHEN DATE(t.tekliftarihi) >= '$firstDayOfMonth' THEN t.tltutar ELSE 0 END) as monthly_tl,
        SUM(CASE WHEN DATE(t.tekliftarihi) >= '$firstDayOfMonth' THEN t.dolartutar ELSE 0 END) as monthly_dolar,
        SUM(CASE WHEN DATE(t.tekliftarihi) >= '$firstDayOfMonth' THEN t.eurotutar ELSE 0 END) as monthly_euro,
        
        -- Toplam
        COUNT(t.id) as total_count,
        SUM(t.tltutar) as total_tl,
        SUM(t.dolartutar) as total_dolar,
        SUM(t.eurotutar) as total_euro

    FROM yonetici y
    LEFT JOIN ogteklif2 t ON y.yonetici_id = t.hazirlayanid
    GROUP BY y.yonetici_id
    HAVING total_count > 0 OR y.tur IN ('Satış', 'Yönetici', 'Personel')
    ORDER BY total_count DESC
";

$result = mysqli_query($db, $query);
$performanceData = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $performanceData[] = $row;
    }
}

function formatCurrency($val, $symbol) {
    if (!$val || $val == 0) return '-';
    return number_format((float)$val, 2, ',', '.') . ' ' . $symbol;
}

function renderValue($count, $tl, $usd, $eur) {
    if ($count == 0) return '<span class="text-muted">0</span>';
    
    $output = "<strong>$count</strong><br>";
    $amounts = [];
    if ($tl > 0) $amounts[] = formatCurrency($tl, '₺');
    if ($usd > 0) $amounts[] = formatCurrency($usd, '$');
    if ($eur > 0) $amounts[] = formatCurrency($eur, '€');
    
    if (empty($amounts)) {
        $output .= '<small class="text-muted">0,00</small>';
    } else {
        $output .= '<small>' . implode(' | ', $amounts) . '</small>';
    }
    return $output;
}

?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8" />
    <title>Satış Performansı | <?php echo $sistemayar["title"]; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <link href="assets/css/icons.min.css" rel="stylesheet" />
    <link href="assets/css/app.min.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <style>
        .table-responsive { font-size: 0.85rem; }
        .perf-card { transition: all 0.3s ease; }
        .perf-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .badge-count { font-size: 0.9rem; }
        .currency-block { display: block; border-top: 1px solid #eee; margin-top: 5px; padding-top: 5px; }
        .table thead th { background-color: #f8f9fa; position: sticky; top: 0; z-index: 10; }
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
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                                <h4 class="mb-sm-0">Satış Performans Takibi</h4>
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="anasayfa.php">Anasayfa</a></li>
                                        <li class="breadcrumb-item active">Satış Performansı</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow-sm">
                                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0"><i class="bx bx-bar-chart-alt-2 me-2"></i>Personel Performans Listesi</h5>
                                    <span class="text-muted small">Son Güncelleme: <?php echo date('H:i:s'); ?></span>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover align-middle mb-0" id="perfTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Personel Adı</th>
                                                    <th class="text-center">Günlük (Teklif / Tutar)</th>
                                                    <th class="text-center">Haftalık (Teklif / Tutar)</th>
                                                    <th class="text-center">Aylık (Teklif / Tutar)</th>
                                                    <th class="text-center">Genel Toplam (Teklif / Tutar)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($performanceData as $row): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar-xs me-2">
                                                                <span class="avatar-title rounded-circle bg-soft-primary text-primary">
                                                                    <?php echo substr($row['adsoyad'] ?? 'P', 0, 1); ?>
                                                                </span>
                                                            </div>
                                                            <div>
                                                                <h6 class="mb-0 fs-14"><?php echo htmlspecialchars($row['adsoyad'] ?? ''); ?></h6>
                                                                <small class="text-muted"><?php echo htmlspecialchars($row['tur'] ?? ''); ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php echo renderValue($row['daily_count'], $row['daily_tl'], $row['daily_dolar'], $row['daily_euro']); ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php echo renderValue($row['weekly_count'], $row['weekly_tl'], $row['weekly_dolar'], $row['weekly_euro']); ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php echo renderValue($row['monthly_count'], $row['monthly_tl'], $row['monthly_dolar'], $row['monthly_euro']); ?>
                                                    </td>
                                                    <td class="text-center fw-bold bg-soft-primary">
                                                        <?php echo renderValue($row['total_count'], $row['total_tl'], $row['total_dolar'], $row['total_euro']); ?>
                                                    </td>
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
            </div>
            <?php include "menuler/footer.php"; ?>
        </div>
    </div>

    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
    <script>
        $(document).ready(function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
        });
    </script>
</body>
</html>
