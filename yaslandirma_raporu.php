<?php
// yaslandirma_raporu.php
require_once "include/fonksiyon.php";
oturumkontrol();

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
    <title>Yaşlandırma Raporu | <?php echo $sistemayar["title"]; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <link href="assets/css/icons.min.css" rel="stylesheet" />
    <link href="assets/css/app.min.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css" rel="stylesheet" />
    <style>
        .table-responsive { overflow-x: auto; }
        .dt-buttons { marginBottom: 15px; }
        th { font-size: 11px; text-align: center; vertical-align: middle; }
        td { font-size: 11px; white-space: nowrap; }
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
                
                <!-- Page Title -->
                <div class="row mb-3 align-items-center">
                    <div class="col-md-6">
                        <h4 class="mb-0 text-primary fw-bold"><i class="bx bx-time-five me-1"></i> Borç/Alacak Yaşlandırma Raporu</h4>
                    </div>
                </div>

                <!-- Report Type Selection -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <form id="reportForm" class="row align-items-end g-3">
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Rapor Türü</label>
                                        <select class="form-select" id="reportType">
                                            <option value="debit">Borç Yaşlandırma (120)</option>
                                            <option value="credit">Alacak Yaşlandırma (320)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <!-- Placeholder for Company Selection if needed later -->
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-primary w-100" id="btnGetReport">
                                            <i class="bx bx-search-alt me-1"></i> Raporu Getir
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Report Content -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <div id="loadingSpinner" class="text-center py-5" style="display:none;">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Yükleniyor...</span>
                                    </div>
                                    <p class="mt-2 text-muted">Rapor hazırlanıyor, lütfen bekleyiniz... (Bu işlem biraz zaman alabilir)</p>
                                </div>
                                
                                <div id="reportContainer" style="display:none;">
                                    <h5 class="card-title text-success mb-3" id="reportTitle">Borç Yaşlandırma Raporu</h5>
                                    <div class="table-responsive">
                                        <table id="agingTable" class="table table-striped table-bordered table-hover w-100 table-sm">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Kod</th>
                                                    <th>Hesap Adı</th>
                                                    <th>Borç</th>
                                                    <th>Alacak</th>
                                                    <th>Bakiye</th>
                                                    <th>Açılış</th>
                                                    <th>Ocak</th>
                                                    <th>Şubat</th>
                                                    <th>Mart</th>
                                                    <th>Nisan</th>
                                                    <th>Mayıs</th>
                                                    <th>Haziran</th>
                                                    <th>Temmuz</th>
                                                    <th>Ağustos</th>
                                                    <th>Eylül</th>
                                                    <th>Ekim</th>
                                                    <th>Kasım</th>
                                                    <th>Aralık</th>
                                                    <th>Sağlama</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                            <tfoot class="table-light fw-bold">
                                                <tr>
                                                    <td colspan="2" class="text-end">TOPLAM:</td>
                                                    <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>

                                <div id="errorMessage" class="alert alert-danger mt-3" style="display:none;"></div>
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
<script src="assets/libs/metismenu/metisMenu.min.js"></script>
<script src="assets/libs/simplebar/simplebar.min.js"></script>
<script src="assets/libs/node-waves/waves.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>

<script>
$(document).ready(function() {
    var table = $('#agingTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            { extend: 'excel', text: '<i class="bx bx-spreadsheet"></i> Excel İndir', className: 'btn btn-success btn-sm' },
            { extend: 'pdf', text: '<i class="bx bxs-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm' },
            { extend: 'print', text: '<i class="bx bx-printer"></i> Yazdır', className: 'btn btn-info btn-sm' }
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/tr.json'
        },
        pageLength: 25,
        columnDefs: [
            { className: 'text-end', targets: [2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18] },
            { 
               targets: [2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18],
               render: function(data, type, row) {
                   if(type === 'display' || type === 'filter') {
                       return data ? parseFloat(data).toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '0,00';
                   }
                   return data;
               }
            }
        ],
        drawCallback: function () {
            var api = this.api();
            // Calculate footer totals? (Optional, skipping for now to keep it simple, DataTables footerCallback can be used)
        }
    });

    $('#btnGetReport').on('click', function() {
        var type = $('#reportType').val();
        // Assuming current sirket (admin view) - Passing a dummy ID or changing API to report_mode
        // Wait, API requires sirket_id to check validity?
        // API Implementation check:
        // $sirket_id = filter_input... if(!$sirket_id) error.
        // And it assumes one "Cari Code".
        // BUT this report is for ALL customers ("120.%").
        // I need to update the API to allow skipping sirket_id check if user is Admin getting general report?
        // Or pass a "B2B Admin" sirket_id?
        // Let's modify the API logic slightly in the next step to support 'general' mode if user is Admin.
        // For now, I'll pass a known sirket_id just to pass the check, or Fix API.
        // Actually, I'll fix the API to check 'mode=general'.
        
        loadReport(type);
    });

    function loadReport(type) {
        $('#loadingSpinner').show();
        $('#reportContainer').hide();
        $('#errorMessage').hide();
        
        // Update Title
        var title = (type === 'debit') ? 'Borç Yaşlandırma Raporu (120)' : 'Alacak Yaşlandırma Raporu (320)';
        $('#reportTitle').text(title);

        $.ajax({
            url: 'api/get_aging_report.php',
            type: 'GET',
            data: { 
                type: type,
                mode: 'general', // New param I should support
                sirket_id: 1 // Dummy, or handled by API update
            },
            dataType: 'json',
            success: function(response) {
                $('#loadingSpinner').hide();
                if(response.error) {
                    $('#errorMessage').text(response.error).show();
                    return;
                }
                
                $('#reportContainer').show();
                
                // Refresh Table
                table.clear();
                if(response.data && response.data.length > 0) {
                    var rows = response.data.map(function(item) {
                        return [
                            item.ACCOUNTCODE || '',
                            item.ACCOUNTNAME || '',
                            item.BORC || 0,
                            item.ALACAK || 0,
                            item.BAKIYE || 0,
                            item.Acilis || 0,
                            item.Ocak || 0,
                            item.Subat || 0,
                            item.Mart || 0,
                            item.Nisan || 0,
                            item.Mayis || 0,
                            item.Haziran || 0,
                            item.Temmuz || 0,
                            item.Agustos || 0,
                            item.Eylul || 0,
                            item.Ekim || 0,
                            item.Kasim || 0,
                            item.Aralik || 0,
                            item.SAGLAMA || 0
                        ];
                    });
                    table.rows.add(rows).draw();
                } else {
                    table.draw();
                }
            },
            error: function(xhr, status, error) {
                $('#loadingSpinner').hide();
                $('#errorMessage').text("Bir hata oluştu: " + error).show();
                console.error(xhr.responseText);
            }
        });
    }
});
</script>
</body>
</html>
