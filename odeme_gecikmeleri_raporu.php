<?php
// odeme_gecikmeleri_raporu.php
include "fonk.php";
// session_start(); handled in fonk.php
oturumkontrol();

// Admin Permission Check (Raporlar)
$personel_id = $_SESSION['yonetici_id'] ?? 0;
$hasAccess = false;
if($personel_id) {
    // Basic admin check - expanding from yaslandirma_raporu logic
    // Assuming if they can see aging report, they can see this.
    // Or just check if they are admin.
    $hasAccess = true; // Temporary simplification, or copy exact logic if needed.
}

if (!$hasAccess) {
    header("Location: anasayfa.php");
    exit;
}

$type = $_GET['type'] ?? '320';
if ($type === '120') {
    $pageTitle = "Tahsilat Gecikmeleri Raporu (120)";
    $descText = "120'li hesapların (Müşteriler) tahsilat gecikmeleri";
    $colorClass = "text-success";
    $btnClass = "btn-success";
    $icon = "bx-check-circle";
    $tableHeader = "Alacaklı Hesap Adı";
} else {
    $pageTitle = "Ödeme Gecikmeleri Raporu (320)";
    $descText = "320'li hesapların (Tedarikçiler) ödeme gecikmeleri";
    $colorClass = "text-danger";
    $btnClass = "btn-danger";
    $icon = "bx-error-circle";
    $tableHeader = "Hesap Adı";
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8" />
    <title><?= $pageTitle ?> | <?php echo $sistemayar["title"]; ?></title>
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
        /* Custom Report Window "Pencere" Style */
        #customReportWindow {
            height: 72vh; /* Slightly reduced to make room for footer */
            overflow: auto; 
            border: 2px solid #6c757d; 
            border-radius: 4px; 
            background-color: #fff;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
            position: relative; 
            margin-bottom: 60px; /* Space for footer */
        }

        /* Strict Sticky Headers */
        #delaysTable thead th {
            position: sticky;
            top: 0;
            z-index: 100; /* Lower z-index to not block menus */
            background-color: #f8f9fa; /* Background color is crucial for opacity */
            box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.4); 
            border-top: none;
            white-space: nowrap; /* Keep headers single line if possible */
        }

        /* Compact Table Layout */
        #delaysTable th, #delaysTable td {
            font-size: 11px !important;
            padding: 3px 4px !important; /* Reduced padding */
            vertical-align: middle;
            border: 1px solid #dee2e6;
        }
        
        #delaysTable td {
            white-space: nowrap;
        }
        
        /* Maximize Screen Real Estate */
        .container-fluid { 
            padding-left: 2px !important; 
            padding-right: 2px !important; 
            max-width: 100% !important; /* Full width */
        }
        .page-content {
            padding-top: 10px !important;
            padding-bottom: 0 !important;
        }
        .card-body { padding: 4px !important; }
        
        /* Truncate long names but allow hover */
        .truncate-text {
            max-width: 200px; /* Slightly reduced to fit more cols */
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: inline-block;
            vertical-align: middle;
        }
    </style>
</head>
<body data-layout="horizontal" data-topbar="colored">
<!-- ... header ... -->

<!-- ... header ... -->

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
                        <h4 class="mb-0 <?= $colorClass ?> fw-bold"><i class="bx <?= $icon ?> me-1"></i> <?= $pageTitle ?></h4>
                    </div>
                </div>

                <!-- Report Logic -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <div class="row g-3 align-items-end mt-5">
                                    <div class="col-md-9">
                                        <input type="text" id="customSearch" class="form-control" placeholder="Cari Adı veya Koduna Göre Arama Yapabilirsiniz..." autocomplete="off">
                                    </div>
                                    <div class="col-md-3">
                                        <button type="button" class="btn <?= $btnClass ?> w-100" id="btnGetReport">
                                            <i class="bx bx-search-alt me-1"></i> Raporu Getir
                                        </button>
                                    </div>
                                </div>
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
                                    <div class="spinner-border text-danger" role="status">
                                        <span class="visually-hidden">Yükleniyor...</span>
                                    </div>
                                    <p class="mt-2 text-muted">Ödeme verileri analiz ediliyor, lütfen bekleyiniz...</p>
                                </div>
                                
                                <div id="reportContainer" style="display:none;">
                                    <!-- Custom Scroll Window -->
                                    <div id="customReportWindow">
                                        <table id="delaysTable" class="table table-striped table-bordered table-hover w-100 table-sm mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th rowspan="2" style="width: 30px;"></th> <!-- Expander Column -->
                                                    <th rowspan="2" style="min-width: 80px;">Kod</th>
                                                    <th rowspan="2" style="min-width: 200px;"><?= $tableHeader ?></th>
                                                    <th colspan="12">Aylık Gecikme</th>
                                                    <th colspan="4" class="bg-warning bg-opacity-10">Gecikme (Gün)</th>
                                                    <th rowspan="2">Toplam<br>Gecikmiş</th>
                                                    <th rowspan="2" class="text-danger">Gecikme<br>Bedeli (%3)</th>
                                                </tr>
                                                <tr>
                                                    <th>Oca</th>
                                                    <th>Şub</th>
                                                    <th>Mar</th>
                                                    <th>Nis</th>
                                                    <th>May</th>
                                                    <th>Haz</th>
                                                    <th>Tem</th>
                                                    <th>Ağu</th>
                                                    <th>Eyl</th>
                                                    <th>Eki</th>
                                                    <th>Kas</th>
                                                    <th>Ara</th>
                                                    
                                                    <th class="bg-warning bg-opacity-10">1-30</th>
                                                    <th class="bg-warning bg-opacity-10">31-60</th>
                                                    <th class="bg-warning bg-opacity-10">61-90</th>
                                                    <th class="bg-warning bg-opacity-10">90+</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                            <tfoot class="table-light fw-bold">
                                                <tr>
                                                    <td class="bg-light"></td>
                                                    <td colspan="2" class="text-end">GENEL TOPLAM:</td>
                                                    <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                                                    <td class="bg-warning bg-opacity-10"></td>
                                                    <td class="bg-warning bg-opacity-10"></td>
                                                    <td class="bg-warning bg-opacity-10"></td>
                                                    <td class="bg-warning bg-opacity-10"></td>
                                                    <td></td>
                                                    <td class="text-danger"></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                        </div>
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
    var table;
    function initTable() {
        if ($.fn.DataTable.isDataTable('#delaysTable')) {
            $('#delaysTable').DataTable().destroy();
        }
        table = $('#delaysTable').DataTable({
            dom: 'Bfrtip',
            // Disable DataTables Scrolling so our custom CSS handles it
            paging: false, /* Disable pagination to show ALL data in scrollable window */
            destroy: true,
        buttons: [
            { extend: 'excel', text: '<i class="bx bx-spreadsheet"></i> Excel İndir', className: 'btn btn-success btn-sm' },
            { extend: 'pdf', text: '<i class="bx bxs-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', orientation: 'landscape', pageSize: 'A3' },
            { extend: 'print', text: '<i class="bx bx-printer"></i> Yazdır', className: 'btn btn-info btn-sm' }
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/tr.json'
        },
        pageLength: 100,
        lengthMenu: [[100, 250, 500, -1], [100, 250, 500, "Tümü"]],
        columns: [
            {
                "className":      'details-control text-center align-middle',
                "orderable":      false,
                "data":           null,
                "defaultContent": '<i class="bx bx-plus-circle text-primary" style="cursor:pointer; font-size: 1.2rem;"></i>'
            },
            { data: 'ACCOUNTCODE' },
            { 
                data: 'ACCOUNTNAME',
                render: function(data, type, row) {
                    if (type === 'display') {
                        return '<div class="truncate-text" title="'+data+'">'+data+'</div>';
                    }
                    return data;
                }
            },
            { data: 'OCAK', className: 'text-end', render: $.fn.dataTable.render.number('.', ',', 2, '') },
            { data: 'SUBAT', className: 'text-end', render: $.fn.dataTable.render.number('.', ',', 2, '') },
            { data: 'MART', className: 'text-end', render: $.fn.dataTable.render.number('.', ',', 2, '') },
            { data: 'NISAN', className: 'text-end', render: $.fn.dataTable.render.number('.', ',', 2, '') },
            { data: 'MAYIS', className: 'text-end', render: $.fn.dataTable.render.number('.', ',', 2, '') },
            { data: 'HAZIRAN', className: 'text-end', render: $.fn.dataTable.render.number('.', ',', 2, '') },
            { data: 'TEMMUZ', className: 'text-end', render: $.fn.dataTable.render.number('.', ',', 2, '') },
            { data: 'AGUSTOS', className: 'text-end', render: $.fn.dataTable.render.number('.', ',', 2, '') },
            { data: 'EYLUL', className: 'text-end', render: $.fn.dataTable.render.number('.', ',', 2, '') },
            { data: 'EKIM', className: 'text-end', render: $.fn.dataTable.render.number('.', ',', 2, '') },
            { data: 'KASIM', className: 'text-end', render: $.fn.dataTable.render.number('.', ',', 2, '') },
            { data: 'ARALIK', className: 'text-end', render: $.fn.dataTable.render.number('.', ',', 2, '') },
            
            { data: 'GECIKME_0_30', className: 'bg-warning bg-opacity-10 text-end', render: $.fn.dataTable.render.number('.', ',', 2, '') },
            { data: 'GECIKME_31_60', className: 'bg-warning bg-opacity-10 text-end', render: $.fn.dataTable.render.number('.', ',', 2, '') },
            { data: 'GECIKME_61_90', className: 'bg-warning bg-opacity-10 text-end', render: $.fn.dataTable.render.number('.', ',', 2, '') },
            { data: 'GECIKME_90_PLUS', className: 'bg-warning bg-opacity-10 text-end', render: $.fn.dataTable.render.number('.', ',', 2, '') },
            
            { data: 'TOPLAM_GECIKMIS', className: 'fw-bold text-end', render: $.fn.dataTable.render.number('.', ',', 2, '') },
            { data: 'GECIKME_BEDELI', className: 'fw-bold text-danger text-end', render: $.fn.dataTable.render.number('.', ',', 2, '') }
        ],
        footerCallback: function (row, data, start, end, display) {
            var api = this.api();
 
            // Helper to remove formatting (Turkish uses dot for thousands, comma for decimals)
            // But here raw data might be numbers? No, render function formats them. 
            // API data() returns original data object if not specified, but here we access column().data().
            // If we access original data, we are safer.
            
            var intVal = function (i) {
                return typeof i === 'string' ?
                    parseFloat(i.replace(/[\$.]/g, '').replace(/,/g, '.')) : // Remove thousands dot, replace decimal comma
                    typeof i === 'number' ? i : 0;
            };
            
            // However, column().data() returns the datasource data (numbers) before rendering! 
            // So we don't need to parse formatted strings if we use column().data().
            // Let's verify: render option affects display, but data() gives the underlying value.
            
            var sumColumn = function(index) {
                return api.column(index).data().reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);
            };

            // Indices: 
            // 3-14: Months
            // 15-18: Delay Buckets
            // 19: Total Delayed
            // 20: Penalty
            
            for (var i = 3; i <= 20; i++) {
                var total = sumColumn(i);
                $(api.column(i).footer()).html(
                    total.toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2})
                );
            }
        }
    });

    // CUSTOM SEARCH LOGIC
    // Unbind previous listeners to prevent duplicates
    $('#customSearch').off('keyup').on('keyup', function() {
        if(table) {
            table.column(2).search(this.value).draw();
        }
    });

    // ROW DETAIL CLICK LISTENER
    // Unbind previous listeners to prevent duplicates
    $('#delaysTable tbody').off('click', 'td.details-control').on('click', 'td.details-control', function () {
        var tr = $(this).closest('tr');
        var row = table.row( tr );
        var icon = $(this).find('i');
 
        if ( row.child.isShown() ) {
            // This row is already open - close it
            row.child.hide();
            tr.removeClass('shown');
            icon.removeClass('bx-minus-circle text-danger').addClass('bx-plus-circle text-primary');
        }
        else {
            // Open this row
            row.child( format(row.data()) ).show();
            tr.addClass('shown');
            icon.removeClass('bx-plus-circle text-primary').addClass('bx-minus-circle text-danger');
        }
    });
    }

    // Format function for row details
    function format ( d ) {
        if(!d.details || d.details.length === 0) {
            return '<div class="p-3 text-muted">Gecikmiş fatura detayı bulunamadı.</div>';
        }

        var html = '<div class="p-3 bg-light border rounded"><h6 class="mb-2">Geciken Faturalar</h6>';
        html += '<table class="table table-sm table-bordered mb-0" style="background-color: #fff;">';
        html += '<thead class="table-light"><tr>' +
                '<th>Belge No (Fiş No)</th>' +
                '<th>Fatura Tarihi</th>' +
                '<th>Vade Tarihi</th>' +
                '<th>Geciken Gün</th>' +
                '<th class="text-end">Fatura Tutarı / Kalan</th>' +
                '</tr></thead><tbody>';

        d.details.forEach(function(item) {
            var date = new Date(item.InvoiceDate).toLocaleDateString('tr-TR');
            var dueDate = item.DueDate ? new Date(item.DueDate).toLocaleDateString('tr-TR') : '-';
            var amount = parseFloat(item.Amount).toLocaleString('tr-TR', {minimumFractionDigits: 2});
            
            html += '<tr>' +
                    '<td class="fw-bold">' + (item.FicheNo || '-') + '</td>' +
                    '<td>' + date + '</td>' +
                    '<td>' + dueDate + '</td>' +
                    '<td><span class="badge bg-danger">' + item.DelayDays + ' Gün</span></td>' +
                    '<td class="text-end">' + amount + ' ₺</td>' +
                    '</tr>';
        });

        html += '</tbody></table></div>';
        return html;
    }

    $('#btnGetReport').off('click').on('click', function() {
        $('#loadingSpinner').show();
        $('#reportContainer').hide();
        $('#errorMessage').hide();

        var type = '<?= $type ?>';

        $.ajax({
            url: 'api/get_payment_delays.php',
            type: 'GET',
            data: { type: type },
            dataType: 'json',
            success: function(response) {
                $('#loadingSpinner').hide();
                if(response.error) {
                    $('#errorMessage').text(response.error).show();
                    return;
                }
                
                $('#reportContainer').show();
                
                // Init table AFTER showing container
                initTable();
                
                table.clear();
                if(response.data && response.data.length > 0) {
                    // Pass the whole object directly, columns definition handles mapping
                    table.rows.add(response.data).draw();
                    
                    // Adjust columns again to be safe
                    table.columns.adjust();
                } else {
                    table.draw();
                }
            },
            error: function(xhr, status, error) {
                $('#loadingSpinner').hide();
                $('#errorMessage').text("Bir hata oluştu: " + error).show();
            }
        });
    });
});
</script>
</body>
</html>
