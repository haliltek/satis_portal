<?php include "fonk.php";
oturumkontrol();
$baslangici = $sistemayar["stokalarmlaribaslangic"];
$bitisi = $sistemayar["stokalarmlaribitis"];  ?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title><?php echo $sistemayar["title"]; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $sistemayar["description"]; ?>" name="description" />
    <meta content="<?php echo $sistemayar["keywords"]; ?>" name="keywords" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <!-- Bootstrap Css -->
    <link href="assets/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="assets/css/app.min.css" id="app-style" rel="stylesheet" type="text/css" />
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <!-- Responsive datatable examples -->
    <link href="assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <style type="text/css">
        a {
            text-decoration: none;
        }

        .numa {
            background-color: lightgray;
            font-size: 16px;
            line-height: 40px;
        }

        .numa2 {
            background-color: red;
            font-size: 16px;
            color: white;
        }
    </style>
    <script src="//cdn.ckeditor.com/4.18.0/full/ckeditor.js"></script>
    <script type="text/javascript">
        $('#example').dataTable({
            "pageLength": 200
        });
    </script>
</head>

<body data-layout="horizontal" data-topbar="colored">
    <!-- Begin page -->
    <div id="layout-wrapper">
        <header id="page-topbar">
            <?php include "menuler/ustmenu.php"; ?>
            <?php include "menuler/solmenu.php"; ?>
        </header>
        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-lg-12">
                            <!--    <button type="button" class="btn btn-success waves-effect waves-light float-right" data-bs-toggle="modal" data-bs-target=".yenikategori">Yeni Fihrist Tanımlayınız</button> -->
                            <button type="button" class="btn btn-info btn-sm waves-effect waves-light float-right" data-bs-toggle="modal" data-bs-target=".yardim">Yardım</button>
                            <hr>
                            <div class="card"><br>
                                <div class="card-body">
                                    <h4 class="card-title mb-4">Stok Alarmlarını İnceleyiniz <small style="color:red">(Stoklar <?php echo $baslangici; ?> Adet ile <?php echo $bitisi; ?> Adet Aralığında Stok Miktarlarında Alarm Vermektedir.) Ayarları Genel Ayarlardan Değiştirebilirsiniz</small> </h4>
                                    <table id="example" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <table id="example" class="table table-bordered dt-responsive display " style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                            <thead>
                                                <tr>
                                                    <th>Stok Kodu</th>
                                                    <th>Stok Adı</th>
                                                    <th>Birimi</th>
                                                    <th>Döviz</th>
                                                    <th>Liste Fiyatı</th>
                                                    <th>Marka</th>
                                                    <th>Stok</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php

                                                $sayfada = 10; // sayfada gösterilecek içerik miktarı.
                                                $sorgu = mysqli_query($db, 'SELECT COUNT(*) AS kod FROM urunler where miktar>=' . $baslangici . ' and miktar<=' . $bitisi . ' order by miktar desc');
                                                $sonuc = mysqli_fetch_assoc($sorgu);
                                                $toplam_icerik = $sonuc['kod'];
                                                $toplam_sayfa = ceil($toplam_icerik / $sayfada);
                                                $sayfa = isset($_GET['sayfa']) ? (int) $_GET['sayfa'] : 1;
                                                if ($sayfa < 1) $sayfa = 1;
                                                if ($sayfa > $toplam_sayfa) $sayfa = $toplam_sayfa;
                                                $limit = ($sayfa - 1) * $sayfada;
                                                $markam = $iskonto['marka'];
                                                // $sorgu = mysqli_query($db,'SELECT * FROM urunler where marka="'.$markam.'" group by marka order by miktar desc   LIMIT ' . $limit . ', ' . $sayfada );
                                                $sorgu = mysqli_query($db, 'select * from urunler INNER JOIN iskontolar ON urunler.marka = iskontolar.marka where urunler.miktar>=' . $baslangici . ' and urunler.miktar<=' . $bitisi . '  order by urunler.miktar desc LIMIT ' . $limit . ', ' . $sayfada);
                                                while ($icerik = mysqli_fetch_assoc($sorgu)) {
                                                ?>
                                                    <tr>
                                                        <td><?php echo  $icerik['stokkodu']; ?></td>
                                                        <td><?php echo  $icerik['stokadi']; ?></td>
                                                        <td><?php echo  $icerik['olcubirimi']; ?></td>
                                                        <td><?php echo $icerik['doviz'];  ?></td>
                                                        <td><?php echo $list =  $icerik['fiyat']; ?></td>
                                                        <td><?php echo $icerik['marka']; ?></td>
                                                        <td><?php echo  $mik =  $icerik['miktar']; ?></td>
                                                    </tr>
                                                <?php }  ?>
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th>Stok Kodu</th>
                                                    <th>Stok Adı</th>
                                                    <th>Birimi</th>
                                                    <th>Döviz</th>
                                                    <th>Liste Fiyatı</th>
                                                    <th>Marka</th>
                                                    <th>Stok</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                        <nav aria-label="Page navigation example">
                                            <ul class="pagination">
                                                <?php $sayfa_goster = 11; // gösterilecek sayfa sayısı
                                                $en_az_orta = ceil($sayfa_goster / 2);
                                                $en_fazla_orta = ($toplam_sayfa + 1) - $en_az_orta;
                                                $sayfa_orta = $sayfa;
                                                if ($sayfa_orta < $en_az_orta) $sayfa_orta = $en_az_orta;
                                                if ($sayfa_orta > $en_fazla_orta) $sayfa_orta = $en_fazla_orta;
                                                $sol_sayfalar = round($sayfa_orta - (($sayfa_goster - 1) / 2));
                                                $sag_sayfalar = round((($sayfa_goster - 1) / 2) + $sayfa_orta);
                                                if ($sol_sayfalar < 1) $sol_sayfalar = 1;
                                                if ($sag_sayfalar > $toplam_sayfa) $sag_sayfalar = $toplam_sayfa;
                                                if ($sayfa != 1) echo '<li class="page-item"><a class="page-link" href="stokalarmlari.php?sayfa=1">İlk Sayfa</a></li>   ';
                                                if ($sayfa != 1) echo '  <li class="page-item"><a class="page-link" href="stokalarmlari.php?sayfa=' . ($sayfa - 1) . '">Önceki Sayfa</a></li>   ';
                                                for ($s = $sol_sayfalar; $s <= $sag_sayfalar; $s++) {
                                                    if ($sayfa == $s) {
                                                        echo '<b style="color:red">[' . $s . ']</b> ';
                                                    } else {
                                                        echo '<li class="page-item"><a class="page-link" href="stokalarmlari.php?sayfa=' . $s . '">' . $s . '</a></li>  ';
                                                    }
                                                }
                                                if ($sayfa != $toplam_sayfa) echo '<li class="page-item"> <a class="page-link"  href="stokalarmlari.php?sayfa=' . ($sayfa + 1) . '">Sonraki  </a></li> ';
                                                if ($sayfa != $toplam_sayfa) echo '<li class="page-item"> <a class="page-link" href="stokalarmlari.php?sayfa=' . $toplam_sayfa . '">Son Sayfa</a></li>  '; ?>
                                            </ul>
                                        </nav>
                                </div> <!-- Card-Body Bitişi -->
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
    <!-- apexcharts -->
    <script src="assets/libs/apexcharts/apexcharts.min.js"></script>
    <script src="assets/js/pages/dashboard.init.js"></script>
    <!-- App js -->
    <script src="assets/js/app.js"></script>
    <!-- Responsive examples -->
    <script src="assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>
    <!-- Datatable init js -->
    <script src="assets/js/pages/datatables.init.js"></script>
    <!-- Required datatable js -->
    <script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <!-- Buttons examples -->
    <script src="assets/libs/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
    <script src="assets/libs/parsleyjs/parsley.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $('#example').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": "uruncekdatatable.php",
                language: {
                    "url": "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Turkish.json"
                },
                columnDefs: [{targets:9, visible:false}]
            });
        });
    </script>
</body>

</html>