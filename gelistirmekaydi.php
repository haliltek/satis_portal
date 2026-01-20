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
                            <button type="button" class="btn btn-success waves-effect waves-light float-right" data-bs-toggle="modal" data-bs-target=".yenikategori">Kayıt Tanımlayınz</button>
                            <a href="personelduyurulariincele.php" class="btn btn-info waves-effect waves-light float-right">Personel Duyurularını İnceleyiniz</a>
                            <hr>
                            <?php
                            if (isset($_POST['kayit'])) {
                                $adi = addslashes($_POST["adi"]);
                                $aciklama = addslashes($_POST["aciklama"]);
                                $durumu = addslashes($_POST["durumu"]);
                                $tarih  = date("Y.m.d"); //    31.12.2022 13:17
                                $genelayar_sorgu = "INSERT INTO guncellemeler(adi,aciklama,tarih,durumu) VALUES('$adi','$aciklama','$tarih','$durumu')";
                                $add = mysqli_query($db, $genelayar_sorgu);
                                if ($add) {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Yeni Güncelleme Tanımaldı','$yonetici_id_sabit','$zaman','Başarılı')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-success" role="alert">  Sayın ' . $adsoyad . ' <br> Güncelleme Kaydı Başarıyla Kaydedilmiştir. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="2; url=gelistirmekaydi.php"> ';
                                } else {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Yeni Güncelleme Kaydı','$yonetici_id_sabit','$zaman','Başarısız')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-danger" role="alert">  Sayın ' . $adsoyad . ' <br> Güncelleme Kaydı Malesef Kaydedilemedi. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="2; url=gelistirmekaydi.php"> ';
                                }
                            } else
                         if (isset($_POST['duzenleme'])) {
                                $adi = addslashes($_POST["adi"]);
                                $aciklama = addslashes($_POST["aciklama"]);
                                $tarih  = addslashes($_POST["tarih"]); //    31.12.2022 13:17
                                $durumu = addslashes($_POST["durumu"]);
                                $icerikid = $_POST["icerikid"];
                                $kategoriduzenleme = "UPDATE guncellemeler SET adi = '$adi',aciklama = '$aciklama',tarih = '$tarih',durumu = '$durumu' WHERE id= '$icerikid'";
                                $duzenleme = mysqli_query($db, $kategoriduzenleme);
                                if ($duzenleme) {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Güncelleme Kaydı Güncelleme','$yonetici_id_sabit','$zaman','Başarılı')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-success" role="alert">  Sayın ' . $adsoyad . ' <br> Güncelleme Kaydı Başarıyla Kaydedilmiştir. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="2; url=gelistirmekaydi.php"> ';
                                } else {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Güncelleme Kaydı Güncelleme','$yonetici_id_sabit','$zaman','Başarısız')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-danger" role="alert">  Sayın ' . $adsoyad . ' <br> Güncelleme Kaydı Malesef Kaydedilemedi. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="2; url=gelistirmekaydi.php"> ';
                                }
                            }
                            ?>
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-4">Güncelleme Kaydı Yönetimi</h4>
                                    <table id="datatable" class="table table-bordered dt-resposnsive noswrap" style="border-collapse: collapsse; border-spacing: 0; width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>Güncelleme Adı</th>
                                                <th>Güncelleme Detayları</th>
                                                <th>Güncelleme Tarihi</th>
                                                <th>İşlem</th>
                                                <th>Kime?</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $Kategori_sorgulama = mysqli_query($db, "SELECT * FROM  guncellemeler where durumu='Müşteri' order by tarih desc ");
                                            while ($stokbirimi = mysqli_fetch_array($Kategori_sorgulama)) {
                                            ?>
                                                <tr>
                                                    <td><?php echo $stokbirimi["adi"]; ?></td>
                                                    <td><small><?php echo $stokbirimi["aciklama"]; ?></small></td>
                                                    <td><?php echo $stokbirimi["tarih"]; ?></td>
                                                    <?php $durumus = $stokbirimi["durumu"];
                                                    if ($durumus == 'Müşteri') {
                                                        echo "
                                             <td><button class='btn btn-success btn-sm'>Geliştirme</button></td>
                                             <td><button class='btn btn-success btn-sm'>Müşteri</button></td>
                                              ";
                                                    } else {
                                                        echo "
                                            <td><button class='btn btn-primary btn-sm'>Duyuru</button></td>
                                             <td><button class='btn btn-primary btn-sm'>Personel</button></td>
                                              ";
                                                    } ?>

                                                    <td>
                                                        <button type="button" class="btn btn-info waves-effect waves-light btn-sm" data-bs-toggle="modal" data-bs-target=".duzenle<?php echo $stokbirimi["id"]; ?>"> Düzenle </button>
                                                        <a href="guncellemekaydisil.php?id=<?php echo $stokbirimi["id"]; ?>" class="btn btn-danger waves-effect waves-light btn-sm"> Sistemden Sil </a>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
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
    <div class="modal fade yenikategori" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myLargeModalLabel">Yeni Güncelleme Kaydı Tanımlayınız</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form method="post" action="gelistirmekaydi.php" class="needs-validation" novalidate>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Güncelleme Adı</label>
                                    <input type="text" name="adi" class="form-control" id="validationCustom01" placeholder="ÖR. Yazılım Geliştirme " required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Kime?</label>
                                    <select class="form-control" name="durumu">
                                        <option value="Müşteri">Müşteri</option>

                                    </select>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Güncelleme Açıklaması</label>
                                    <textarea class="form-control" name="aciklama" placeholder="Güncellemeyi kısaca açıklayınız"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Vazgeçtim, Kapat</button>
                        <button type="submit" name="kayit" class="btn btn-success">Yeni Oluştur!</button>
                    </div>
                </form>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div>
    <?php
    $Kategori_sorgulama = mysqli_query($db, "SELECT * FROM  guncellemeler order by id asc ");
    while ($stokbirimi = mysqli_fetch_array($Kategori_sorgulama)) {
    ?>
        <div class="modal fade duzenle<?php echo $stokbirimi["id"]; ?>" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="myLargeModalLabel"><b><?php echo $stokbirimi["adi"]; ?></b> Düzenleyin!</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        </button>
                    </div>
                    <form method="post" action="gelistirmekaydi.php" class="needs-validation" novalidate>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Güncelleme Adı</label>
                                        <input type="text" name="adi" class="form-control" value="<?php echo $stokbirimi["adi"]; ?>" id="validationCustom01" placeholder="ÖR. Yazılım Geliştirme " required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Tarih</label>
                                        <input type="date" name="tarih" class="form-control" id="validationCustom01" required>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Kime?</label>
                                        <select class="form-control" name="durumu">
                                            <option value="<?php echo $stokbirimi["durumu"]; ?>" selected><?php echo $stokbirimi["durumu"]; ?></option>
                                            <option value="Müşteri">Müşteri</option>
                                            <option value="Personel">Personel</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Güncelleme Açıklaması</label>
                                        <textarea class="form-control" name="aciklama" placeholder="Güncellemeyi kısaca açıklayınız"><?php echo $stokbirimi["aciklama"]; ?></textarea>
                                    </div>
                                </div>
                            </div> <input type="text" name="icerikid" value="<?php echo $stokbirimi["id"]; ?>" hidden>
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