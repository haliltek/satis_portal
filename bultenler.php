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
    </style>
    <script src="//cdn.ckeditor.com/4.18.0/full/ckeditor.js"></script>
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
                            <button type="button" class="btn btn-success waves-effect waves-light float-right" data-bs-toggle="modal" data-bs-target=".yenikategori">Yeni Bülten Tanımlayınız</button>
                            <button type="button" class="btn btn-info btn-sm waves-effect waves-light float-right" data-bs-toggle="modal" data-bs-target=".yardim">Yardım</button>
                            <hr>
                            <?php
                            if (isset($_POST['kayit'])) {
                                $marka = xss(addslashes($_POST["marka"]));
                                $pesin = xss(addslashes($_POST["pesin"]));
                                $kredikarti = xss(addslashes($_POST["kredikarti"]));
                                $atmisgun = xss(addslashes($_POST["atmisgun"]));
                                $genelayar_sorgu = "INSERT INTO bultenler(marka,pesin,kredikarti,atmisgun) VALUES('$marka','$pesin','$kredikarti','$atmisgun')";
                                $add = mysqli_query($db, $genelayar_sorgu);
                                if ($add) {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Yeni Bülten Kayıt','$yonetici_id_sabit','$zaman','Başarılı')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-success" role="alert">  Sayın ' . $adsoyad . ' <br> Bülten Başarıyla Kaydedilmiştir. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="2; url=bultenler.php"> ';
                                } else {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Yeni Bülten Kayıt','$yonetici_id_sabit','$zaman','Başarısız')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-danger" role="alert">  Sayın ' . $adsoyad . ' <br> Bülten Malesef Kaydedilemedi. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="2; url=bultenler.php"> ';
                                }
                            } else if (isset($_POST['duzenleme'])) {
                                $marka = xss(addslashes($_POST["marka"]));
                                $pesin = xss(addslashes($_POST["pesin"]));
                                $kredikarti = xss(addslashes($_POST["kredikarti"]));
                                $atmisgun = xss(addslashes($_POST["atmisgun"]));
                                $icerikid = $_POST["icerikid"];
                                $kategoriduzenleme = "UPDATE bultenler SET marka = '$marka',pesin = '$pesin',kredikarti = '$kredikarti',kredikarti = '$kredikarti',atmisgun = '$atmisgun' WHERE bulten_id= '$icerikid'";
                                $duzenleme = mysqli_query($db, $kategoriduzenleme);
                                if ($duzenleme) {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Bülten Güncelleme','$yonetici_id_sabit','$zaman','Başarılı')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-success" role="alert">  Sayın ' . $adsoyad . ' <br> Bülten Başarıyla Kaydedilmiştir. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="0; url=bultenler.php"> ';
                                } else {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Bülten Güncelleme','$yonetici_id_sabit','$zaman','Başarısız')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-danger" role="alert">  Sayın ' . $adsoyad . ' <br> Bülten Malesef Kaydedilemedi. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="2; url=bultenler.php"> ';
                                }
                            }
                            ?>
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-4">Bülten Yönetimi</h4>
                                    <div class="table-responsive">
                                        <table id="datatable" class="table table-bordered table-responsive nsowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                            <thead>
                                                <tr>
                                                    <th>Marka</th>
                                                    <th>Peşin</th>
                                                    <th>Kredi Kartı</th>
                                                    <th>60 Gün Vade</th>
                                                    <th>İşlemler</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $sozlesme_sorgulama = mysqli_query($db, "SELECT * FROM  bultenler");
                                                while ($iskonto = mysqli_fetch_array($sozlesme_sorgulama)) {
                                                ?>
                                                    <tr>
                                                        <td><?php echo $iskonto["marka"]; ?></td>
                                                        <td> <?php echo $iskonto["pesin"]; ?></td>
                                                        <td> <?php echo $iskonto["kredikarti"]; ?></td>
                                                        <td> <?php echo $iskonto["atmisgun"]; ?></td>
                                                        <td>
                                                            <button type="button" class="btn btn-info waves-effect waves-light btn-sm" data-bs-toggle="modal" data-bs-target=".duzenle<?php echo $iskonto["bulten_id"]; ?>"> Düzenle </button>
                                                            <a href="bultensil.php?id=<?php echo $iskonto["bulten_id"]; ?>" class="btn btn-danger waves-effect waves-light btn-sm"> Sistemden Sil </a>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
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
    <div class="modal fade yenikategori" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myLargeModalLabel">Yeni Bülten Tanımlayınız</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form method="post" action="bultenler.php" class="needs-validation" novalidate enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Marka</label>
                                    <input type="text" name="marka" class="form-control" id="validationCustom01" placeholder="ÖR. Işıldar" required>
                                    <div class="valid-feedback"> Başarılı! </div>
                                    <div class="invalid-feedback">Marka Zorunludur </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Peşin %</label>
                                    <input type="text" name="pesin" class="form-control" id="validationCustom01" placeholder="ÖR. 32" required>
                                    <div class="valid-feedback"> Başarılı! </div>
                                    <div class="invalid-feedback">Peşin Zorunludur </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Kredi Kartı %</label>
                                    <input type="text" name="kredikarti" class="form-control" id="validationCustom01" placeholder="ÖR. 32" required>
                                    <div class="valid-feedback"> Başarılı! </div>
                                    <div class="invalid-feedback">Kredi Kartı Zorunludur </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">60 Gün %</label>
                                    <input type="text" name="atmisgun" class="form-control" id="validationCustom01" placeholder="ÖR. 32" required>
                                    <div class="valid-feedback"> Başarılı! </div>
                                    <div class="invalid-feedback">60 Gün Zorunludur </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Vazgeçtim, Kapat</button>
                        <button type="submit" name="kayit" class="btn btn-success">Yeni Bülten Oluştur!</button>
                    </div>
                </form>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div>
    <div class="modal fade yardim" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myLargeModalLabel">Yardım</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8"><img src="images/yardim/kategori.png" width="100%" class="img-responsive"></div>
                        <div class="col-md-4">
                            <b>Kategori Alanı</b>
                            <p>Kategori Alanı E-Ticaret üzerinde bulunan sol kısımdaki listeli menülerdir. Bu kısımdan ürünlere ait kategoriler tanımlanmalıdır. </p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Anladım, Kapat</button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div>
    <?php
    $sozlesme_sorgulama = mysqli_query($db, "SELECT * FROM  bultenler ");
    while ($fihrist = mysqli_fetch_array($sozlesme_sorgulama)) {
    ?>
        <div class="modal fade duzenle<?php echo $fihrist["bulten_id"]; ?>" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="myLargeModalLabel"><?php echo $fihrist["marka"]; ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        </button>
                    </div>
                    <div class="modal-body">
                        <form method="post" action="bultenler.php" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Marka</label>
                                        <input type="text" name="marka" class="form-control" value="<?php echo $fihrist["marka"] ?>" id="validationCustom01" placeholder="ÖR. Işıldar" required>
                                        <div class="valid-feedback"> Başarılı! </div>
                                        <div class="invalid-feedback">Marka Zorunludur </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Peşin %</label>
                                        <input type="text" name="pesin" class="form-control" id="validationCustom01" value="<?php echo $fihrist["pesin"] ?>" placeholder="ÖR. 32" required>
                                        <div class="valid-feedback"> Başarılı! </div>
                                        <div class="invalid-feedback">Peşin Zorunludur </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Kredi Kartı %</label>
                                        <input type="text" name="kredikarti" class="form-control" id="validationCustom01" value="<?php echo $fihrist["kredikarti"] ?>" placeholder="ÖR. 32" required>
                                        <div class="valid-feedback"> Başarılı! </div>
                                        <div class="invalid-feedback">Kredi Kartı Zorunludur </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">60 Gün %</label>
                                        <input type="text" name="atmisgun" class="form-control" id="validationCustom01" value="<?php echo $fihrist["atmisgun"] ?>" placeholder="ÖR. 32" required>
                                        <div class="valid-feedback"> Başarılı! </div>
                                        <div class="invalid-feedback">60 Gün Zorunludur </div>
                                    </div>
                                </div>
                                <input type="text" name="icerikid" value="<?php echo $fihrist["bulten_id"] ?>" hidden>
                            </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Vazgeçtim, Kapat</button>
                        <button type="submit" name="duzenleme" class="btn btn-success">Düzenleyin!</button>
                    </div>
                    </form>
                </div><!-- /.modal-content -->
            </div><!-- /.modal-dialog -->
        </div>
    <?php } ?>
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
</body>

</html>