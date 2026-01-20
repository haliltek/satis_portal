<?php
include "fonk.php";
oturumkontrol();

use App\Models\ArpMap;

global $dbManager, $logoService;

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title><?php echo $sistemayar["title"]; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $sistemayar["description"]; ?>" name="description" />
    <meta content="<?php echo $sistemayar["keywords"]; ?>" name="keywords" />
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <!-- Bootstrap Css -->
    <link href="assets/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="assets/css/app.min.css" id="app-style" rel="stylesheet" type="text/css" />
    <!-- DataTables Css -->
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <style type="text/css">
        a {
            text-decoration: none;
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
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="d-flex justify-content-end flex-wrap gap-2 mb-3 top-buttons">
                                <a href="yenisirketkaydi.php" class="btn btn-primary btn-sm d-flex align-items-center">
                                    <i class="fa fa-plus me-1"></i> Yeni Şirket Tanımlayınız
                                </a>
                                <a href="sirket_cek.php" class="btn btn-info btn-sm d-flex align-items-center">
                                    <i class="fa fa-sync me-1"></i> Logo Şirket Senkronizasyonu
                                </a>
                                <button type="button" class="btn btn-secondary btn-sm d-flex align-items-center" data-bs-toggle="modal" data-bs-target=".yardim">
                                    <i class="fa fa-question-circle me-1"></i> Yardım
                                </button>
                            </div>
                            <hr>
                            <?php
                            // Güncelleme işlemi
                            if (isset($_POST['duzenleme'])) {
                                $data = [
                                    's_adi'        => $_POST['adi'],
                                    'kategori'     => $_POST['kategori'],
                                    's_arp_code'   => $_POST['turu'],
                                    'acikhesap'    => $_POST['acikhesap'],
                                    's_adresi'     => $_POST['adresi'],
                                    's_il'         => $_POST['il'],
                                    's_ilce'       => $_POST['ilce'],
                                    's_telefonu'   => $_POST['telefonu'],
                                    's_vno'        => $_POST['vno'],
                                    's_vd'         => $_POST['vd'],
                                    'payplan_code' => $_POST['payplan_code'] ?? '',
                                    'payplan_def'  => $_POST['payplan_def'] ?? '',
                                    's_fax'        => $_POST['s_fax'] ?? '',
                                    's_gl_code'    => $_POST['s_gl_code'] ?? '',
                                    'credit_limit' => $_POST['credit_limit'] ?? '',
                                    'risk_limit'   => $_POST['risk_limit'] ?? '',
                                ];

                                $logoResp = $logoService->updateArpFromDb($data);
                                $logoError = $logoResp['error'] ?? $logoResp['Message'] ?? '';

                                if ($logoError === '') {
                                    $ok = $dbManager->updateCompany($data['s_arp_code'], $data);
                                    if ($ok) {
                                        echo '<div class="alert alert-success" role="alert">Şirket güncellendi.</div>';
                                    } else {
                                        echo '<div class="alert alert-danger" role="alert">DB güncelleme başarısız.</div>';
                                    }
                                    echo '<meta http-equiv="refresh" content="2; url=tumsirketler.php">';
                                } else {
                                    echo '<div class="alert alert-danger">Logo API Hatası: ' . htmlspecialchars($logoError) . '</div>';
                                }
                            }
                            ?>
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-4">Tüm Kayıtlı Şirketler</h4>
                                    <div class="mb-3">
                                        <select id="companyTypeFilter" class="form-select form-select-sm" style="width:auto; display:inline-block;">
                                            <option value="">Tümü</option>
                                            <option value="yurtici">Yurt İçi</option>
                                            <option value="yurtdisi">Yurt Dışı</option>
                                        </select>
                                    </div>
                                    <div class="table-responsive">
                                        <!-- DataTables server-side ile çalışacak -->
                                        <table id="example" class="table table-bordered dt-responsive company-table nowrap" style="width: 100%;">
                                            <thead>
                                                <tr>
                                                    <th>İşlemler</th>
                                                    <th class="company-title">Ünvanı</th>
                                                    <th>Türü</th>
                                                    <th>Ülke Kodu</th>
                                                    <th>Ülke</th>
                                                    <th>Telefon</th>
                                                    <th>Açık Hesap</th>
                                                    <th>Risk Limiti</th>
                                                    <th class="payplan-code">Ödeme Planı Kodu</th>
                                                    <th class="payplan-def">Ödeme Planı</th>
                                                    <th>Ticari Grup</th>
                                                    <th>Ref</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                            <tfoot>
                                                <tr>
                                                    <th>İşlemler</th>
                                                    <th class="company-title">Ünvanı</th>
                                                    <th>Türü</th>
                                                    <th>Ülke Kodu</th>
                                                    <th>Ülke</th>
                                                    <th>Telefon</th>
                                                    <th>Açık Hesap</th>
                                                    <th>Risk Limiti</th>
                                                    <th class="payplan-code">Ödeme Planı Kodu</th>
                                                    <th class="payplan-def">Ödeme Planı</th>
                                                    <th>Ticari Grup</th>
                                                    <th>Ref</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> <!-- container-fluid -->
            </div>
            <!-- End Page-content -->
            <?php include "menuler/footer.php"; ?>
        </div>
        <!-- end main content-->
    </div>
    <!-- END layout-wrapper -->

    <!-- Yardım Modal -->
    <div class="modal fade yardim" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myLargeModalLabel">Yardım</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <b>GENEL TANIM</b>
                            <p>Şirket alanı, site üzerinden kayıt gerçekleştiren şirketlere ait verilerdir. Bu alanda onaylı, onaysız veya bekleyen tüm şirket kayıtlarına erişim sağlayabilirsiniz.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Anladım, Kapat</button>
                </div>
            </div>
        </div>
    </div>


    <!-- Right bar overlay-->
    <div class="rightbar-overlay"></div>

    <!-- JAVASCRIPT -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/metismenu/metisMenu.min.js"></script>
    <script src="assets/libs/simplebar/simplebar.min.js"></script>
    <script src="assets/libs/node-waves/waves.min.js"></script>
    <script src="assets/libs/waypoints/lib/jquery.waypoints.min.js"></script>
    <script src="assets/libs/jquery.counterup/jquery.counterup.min.js"></script>
    <script src="assets/libs/apexcharts/apexcharts.min.js"></script>
    <script src="assets/js/pages/dashboard.init.js"></script>
    <script src="assets/js/app.js"></script>
    <script src="assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>
    <script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="assets/libs/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
    <script src="assets/libs/parsleyjs/parsley.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            // Her sütun için arama kutusu
            $('#example thead th').each(function() {
                var title = $(this).text();
                $(this).html('<input type="text" placeholder="Search ' + title + '" />');
            });

            // DataTable serverSide processing
            var table = $('#example').DataTable({
                initComplete: function() {
                    this.api().columns().every(function() {
                        var that = this;
                        $('input', this.header()).on('keyup change clear', function() {
                            if (that.search() !== this.value) {
                                that.search(this.value).draw();
                            }
                        });
                    });
                },
                "processing": true,
                "serverSide": true,
                "pageLength": 50,
                "ajax": {
                    "url": "sirketcekdatatable.php",
                    "data": function(d){
                        d.trading_filter = $('#companyTypeFilter').val();
                    }
                },
                "columns": [
                    { "data": 0, "orderable": false, "className": "text-center" },
                    { "data": 1, "className": "company-title" },
                    { "data": 2 },
                    { "data": 3 },
                    { "data": 4 },
                    { "data": 5 },
                    {
                        "data": 6,
                        "render": function(data, type, row) {
                            if (type === 'display' || type === 'filter') {
                                if (isNaN(data)) {
                                    return '0,00 TL';
                                }
                                return Number(data).toLocaleString('tr-TR', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                }) + ' TL';
                            }
                            return data;
                        },
                        "className": "text-end"
                    },
                    {
                        "data": 11,
                        "render": function(data, type, row) {
                            if (type === 'display' || type === 'filter') {
                                if (isNaN(data) || data === null) {
                                    return '0,00 TL';
                                }
                                return Number(data).toLocaleString('tr-TR', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                }) + ' TL';
                            }
                            return data;
                        },
                        "className": "text-end"
                    },
                    { "data": 7, "className": "payplan-code" },
                    { "data": 8, "className": "payplan-def" },
                    { "data": 9 },
                    { "data": 10 }
                ],
                "language": {
                    "url": "assets/libs/datatables.net/i18n/tr.json"
                },
                "responsive": true,
                "columnDefs": [
                    { "targets": 0, "orderable": false, "className": "text-center", "responsivePriority": 1 },
                    { "targets": 1, "className": "company-title", "responsivePriority": 2 },
                    { "targets": 6, "type": "num", "className": "text-end", "responsivePriority": 3 },
                    { "targets": 7, "type": "num", "className": "text-end", "responsivePriority": 3 },
                    { "targets": [8, 9], "responsivePriority": 4 },
                    { "targets": 11, "responsivePriority": 5 }
                ]
            }).on('error.dt', function(e, settings, techNote, message){
                console.error('DataTables error: ' + message);
            });

            $('#companyTypeFilter').on('change', function(){
                table.ajax.reload();
            });

            // Bootstrap tooltip initialization for action icons
            $('[data-bs-toggle="tooltip"]').tooltip();

            $('#example tbody').on('click', 'td.company-title', function(){
                var link = $(this).closest('tr').find('a[href*="edit_company.php"]');
                if (link.length) {
                    var href = link.attr('href');
                    var id = new URL(href, window.location.href).searchParams.get('id');
                    if (id) {
                        window.open('company_details.php?id=' + id, '_blank');
                    }
                }
            });
        });
    </script>
</body>

</html>