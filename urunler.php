<?php include "fonk.php";
oturumkontrol();  ?>
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
                    <?php if (isset($_POST['kayit'])) {
                        $stokadi = xss(addslashes($_POST["stokadi"]));
                        $stokkodu = xss(addslashes($_POST["kod"]));
                        $fiyat = xss(addslashes($_POST["fiyat"]));
                        $olcubirimi = xss(addslashes($_POST["olcubirimi"]));
                        $doviz = xss(addslashes($_POST["doviz"]));
                        $miktar = xss(addslashes($_POST["miktar"]));
                        $marka = xss(addslashes($_POST["marka"]));
                        $genelayar_sorgu = "INSERT INTO urunler(stokadi,stokkodu,fiyat,olcubirimi,doviz,miktar ,marka) VALUES('$stokadi','$stokkodu','$fiyat','$olcubirimi','$doviz','$miktar','$marka')";
                        $add = mysqli_query($db, $genelayar_sorgu);
                        if ($add) {
                            syncPortalProductImmediate($stokkodu);
                            $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Yeni Personel Kayıt','$yonetici_id_sabit','$zaman','Başarılı')";
                            $logislem = mysqli_query($db, $logbaglanti);
                            echo '<div class="alert alert-success" role="alert">  Sayın ' . $adsoyad . ' <br> Ürün Başarıyla Kaydedilmiştir. Lütfen Bekleyiniz...</div>  ';
                            echo '<meta http-equiv="refresh" content="2; url=urunler.php"> ';
                        } else {
                            $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Yeni Ürün Kayıt','$yonetici_id_sabit','$zaman','Başarısız')";
                            $logislem = mysqli_query($db, $logbaglanti);
                            echo '<div class="alert alert-danger" role="alert">  Sayın ' . $adsoyad . ' <br> Ürün Malesef Kaydedilemedi. Lütfen Bekleyiniz...</div>  ';
                            echo '<meta http-equiv="refresh" content="2; url=urunler.php"> ';
                        }
                    }  ?>
                    <div class="row">
                        <div class="col-lg-12">
                            <button type="button" class="btn btn-success waves-effect waves-light float-right" data-bs-toggle="modal" data-bs-target=".yenikategori">Yeni Ürün Tanımlayınız</button>
                            <button type="button" class="btn btn-info btn-sm waves-effect waves-light float-right" data-bs-toggle="modal" data-bs-target=".yardim">Yardım</button>
                            <hr>
                            <div class="card"><br>
                                <div class="card-body">
                                    <h4 class="card-title mb-4">Ürünleri İnceleyiniz</h4>
                                    <div class="table-responsive">
                                        <table id="examples" class="table table-striped table-bordered dt-ressponsive " style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                            <thead>
                                                <tr>
                                                    <th>İşlem</th>
                                                    <th>Stok Kodu</th>
                                                    <th>Stok Adı</th>
                                                    <th>Ölçü Birimi</th>
                                                    <th>Liste Fiyatı</th>

                                                    <th>Döviz</th>
                                                    <th>Stok</th>
                                                    <th>Marka</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php

                                                $sozlesme_sorgulama = mysqli_query($db, "SELECT * FROM  urunler");
                                                while ($urunler = mysqli_fetch_array($sozlesme_sorgulama)) {
                                                ?>
                                                    <tr>
                                                        <td><a href='siparis-olustur.php?ekle=<?php echo $urunler["urun_id"]; ?>' class='btn btn-success btn-sm btn-xs'>Seç </a></td>
                                                        <td><?php echo $urunler["stokkodu"]; ?></td>
                                                        <td><?php echo $urunler["stokadi"]; ?></td>
                                                        <td><?php echo $urunler["olcubirimi"]; ?></td>
                                                        <td><?php echo $urunler["fiyat"]; ?></td>
                                                        <td><?php echo $urunler["doviz"]; ?></td>
                                                        <td><?php echo $urunler["miktar"]; ?></td>
                                                        <td><?php echo $urunler["marka"]; ?></td>
                                                        <td><a href='urunsil.php?id=<?php echo $urunler["urun_id"]; ?>' class='btn btn-danger btn-sm btn-xs'>Sil </a>
                                                        </td>


                                                    </tr>
                                                <?php  } ?>
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th>İşlem</th>
                                                    <th>Stok Kodu</th>
                                                    <th>Stok Adı</th>
                                                    <th>Ölçü Birimi</th>
                                                    <th>Liste Fiyatı</th>

                                                    <th>Döviz</th>
                                                    <th>Stok</th>
                                                    <th>Marka</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    <hr>
                                    <div class="container" style="overflow-x: scroll; width:100%">
                                        <nav aria-label="...">
                                            <ul class="pagination">
                                                <?php for ($s = 1; $s <= $toplam_sayfa; $s++) {
                                                    if ($sayfa == $s) { // eğer bulunduğumuz sayfa ise link yapma. 
                                                ?>
                                                        <li class="page-item active">
                                                            <a class="page-link" href="#"><?php echo $s; ?> <span class="sr-only">(current)</span></a>
                                                        </li>
                                                    <?php
                                                    } else { ?>
                                                        <li class="page-item"><a class="page-link" href="?sayfa=<?php echo $s; ?>"><?php echo $s; ?></a></li>
                                                <?php   }
                                                } ?>
                                            </ul>
                                        </nav>
                                    </div>
                                </div> <!-- Card-Body Bitişi -->
                            </div>
                        </div>
                    </div>
                </div> <!-- container-fluid -->
            </div>
            <!-- End Page-content -->
            <div class="modal fade yenikategori" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="myLargeModalLabel">Yeni Ürün Tanımlayınız</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                            </button>
                        </div>
                        <form method="post" action="urunler.php" class="needs-validation" novalidate enctype="multipart/form-data">
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label" for="validationCustom01">Ürün Adı</label>
                                            <input type="text" name="stokadi" class="form-control" id="validationCustom01" placeholder="ÖR. ABK 2550 2550x2500x3530 mm Monoblok Beton Köşk (MYD/2000-036.C)Astor" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="validationCustom01">Stok Kodu</label>
                                            <input type="text" name="kod" class="form-control" id="validationCustom01" placeholder="ÖR. ABK 2550" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="validationCustom01">Liste Fiyatı</label>
                                            <input type="text" name="fiyat" class="form-control" id="validationCustom01" placeholder="ÖR. 8.250" required>
                                            <div class="valid-feedback"> Başarılı! </div>
                                            <div class="invalid-feedback">Liste Fiyatı Zorunludur </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="validationCustom01">Birimi</label>
                                            <select class="form-control" name="olcubirimi">
                                                <?php
                                                $stokbirimisor = mysqli_query($db, "SELECT * FROM  stokbirimi ");
                                                while ($birim = mysqli_fetch_array($stokbirimisor)) {
                                                ?>
                                                    <option value="<?php echo $birim["birim"]; ?>"><?php echo $birim["adi"]; ?> (<?php echo $birim["birim"]; ?>)</option>
                                                <?php }  ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="validationCustom01">Döviz Türü</label>
                                            <select class="form-control" name="doviz">
                                                <option value="TL">TL</option>
                                                <option value="EUR">EUR</option>
                                                <option value="USDs">USD</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="validationCustom01">Stok Miktarı</label>
                                            <input type="number" name="miktar" class="form-control" id="validationCustom01" placeholder="ÖR. 500" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="validationCustom01">Marka</label>
                                            <select class="form-control" name="marka">
                                                <?php
                                                $markasor = mysqli_query($db, "SELECT * FROM markalar  ");
                                                while ($markam = mysqli_fetch_array($markasor)) {
                                                ?>
                                                    <option value="<?php echo $markam["kategori_adi"]; ?>"><?php echo $markam["kategori_adi"]; ?></option>
                                                <?php }  ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Vazgeçtim, Kapat</button>
                                <button type="submit" name="kayit" class="btn btn-success">Yeni Ürün Oluştur!</button>
                            </div>
                        </form>
                    </div><!-- /.modal-content -->
                </div><!-- /.modal-dialog -->
            </div>
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
            // Setup - add a text input to each footer cell
            $('#example thead th').each(function() {
                var title = $(this).text();
                $(this).html('<input type="text" placeholder="Search ' + title + '" />');
            });
            $('#example').DataTable({
                initComplete: function() {
                    // Apply the search
                    this.api()
                        .columns()
                        .every(function() {
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
                "ajax": "uruncekdatatable.php",
                language: {
                    "url": "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Turkish.json"
                },
                columnDefs: [{targets: 9, visible:false}]
            });
        });
    </script>
</body>

</html>