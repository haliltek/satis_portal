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
            <?php include "menuler/solmenu.php";
            if ($tanimlar == 'Hayır') {
                echo '<script language="javascript">window.location="anasayfa.php";</script>';
                die();
            } ?>
        </header>
        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-lg-12">
                            <?php $siid = $_GET["id"];
                            $sirketsrogu = mysqli_query($db, "SELECT * FROM  sirket where sirket_id = '$siid'");
                            $sirket = mysqli_fetch_array($sirketsrogu);   ?>
                            <button type="button" class="btn btn-success waves-effect waves-light float-right" data-bs-toggle="modal" data-bs-target=".yenikategori"><?php echo $sirket["s_adi"]; ?> Yeni Fatura / İrsaliye Tanımlayınız</button>


                            <button type="button" class="btn btn-info btn-sm waves-effect waves-light float-right"><?php echo $sirket["s_adi"]; ?> Fatura / İrsaliye İşlemleri</button>
                            <hr>
                            <?php
                            if (isset($_POST['kayit'])) {
                                date_default_timezone_set('Europe/Istanbul');
                                $sirketid = $_POST["sirketid"];
                                $aciklama = $_POST["aciklama"];
                                $turu = $_POST["turu"];
                                $tarih = date("d.m.Y H:i:s");
                                $rand = mt_rand();
                                $uploaddir = 'faturalar/';
                                $resimadi =  basename($rand . '-' . $_FILES['resimdosya']['name']);
                                $uploadfile = $uploaddir . basename($rand . '-' . $_FILES['resimdosya']['name']);
                                if (move_uploaded_file($_FILES['resimdosya']['tmp_name'], $uploadfile)) {
                                    $genelayar_sorgu = "INSERT INTO faturairsaliye(aciklama,sirketid,resim,turu,tarih) VALUES('$aciklama','$sirketid','$resimadi','$turu','$tarih')";
                                    $add = mysqli_query($db, $genelayar_sorgu);
                                } else {
                                    echo ' <div class="alert alert-danger alert-border-left alert-dismissible fade show" role="alert">
                                    <i class="mdi mdi-check-all me-3 align-middle"></i><strong>Hata!</strong> Fatura Yüklemede Sorun Oluştu!!
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>';
                                }
                                if ($add) {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Yeni Fatura / İrsaliye Kayıt','$yonetici_id_sabit','$zaman','Başarılı')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-success" role="alert">  Sayın ' . $adsoyad . ' <br> Fatura / İrsaliye Başarıyla Kaydedilmiştir. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="2; url=faturalar.php?id=' . $siid . '"> ';
                                } else {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Yeni Fatura / İrsaliye Kayıt','$yonetici_id_sabit','$zaman','Başarısız')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-danger" role="alert">  Sayın ' . $adsoyad . ' <br> Fatura / İrsaliye Malesef Kaydedilemedi. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="2; url=faturalar.php?id=' . $siid . '"> ';
                                }
                            } else
                         if (isset($_POST['duzenleme'])) {
                                $sirketid = $_POST["sirketid"];
                                $aciklama = $_POST["aciklama"];
                                $turu = $_POST["turu"];
                                $tarih = date("d.m.Y H:i:s");
                                $icerikid = $_POST["icerikid"];
                                $kategoriduzenleme = "UPDATE faturairsaliye SET sirketid = '$sirketid',aciklama = '$aciklama',turu = '$turu',tarih = '$tarih' WHERE id= '$icerikid'";
                                $duzenleme = mysqli_query($db, $kategoriduzenleme);
                                if ($duzenleme) {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Fatura / İrsaliye Güncelleme','$yonetici_id_sabit','$zaman','Başarılı')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-success" role="alert">  Sayın ' . $adsoyad . ' <br> Fatura / İrsaliye Başarıyla Kaydedilmiştir. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="2; url=faturalar.php?id=' . $siid . '"> ';
                                } else {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Fatura / İrsaliye Güncelleme','$yonetici_id_sabit','$zaman','Başarısız')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-danger" role="alert">  Sayın ' . $adsoyad . ' <br> Fatura / İrsaliye Malesef Kaydedilemedi. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="2; url=faturalar.php?id=' . $siid . '"> ';
                                }
                            } else if (isset($_POST['resim'])) {
                                $icerikid = $_POST["icerikid"];
                                $uploaddir = 'faturalar/';
                                $rand = mt_rand();
                                $resimadi =  basename($rand . '-' . $_FILES['resimdosya']['name']);
                                $uploadfile = $uploaddir . basename($rand . '-' . $_FILES['resimdosya']['name']);
                                if (move_uploaded_file($_FILES['resimdosya']['tmp_name'], $uploadfile)) {
                                    $kategoriduzenleme = "UPDATE faturairsaliye SET resim = '$resimadi' WHERE id= '$icerikid'";
                                    $add = mysqli_query($db, $kategoriduzenleme);
                                } else {
                                    echo ' <div class="alert alert-danger alert-border-left alert-dismissible fade show" role="alert">
                    <i class="mdi mdi-check-all me-3 align-middle"></i><strong>Hata!</strong> Resim Yüklemede Sorun Oluştu!!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
                                }
                                if ($add) {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Fatura / İrsaliye Belge Güncelleme','$yonetici_id_sabit','$zaman','Başarılı')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-success" role="alert">  Sayın ' . $adsoyad . ' <br> Fatura / İrsaliye Başarıyla Güncellendi. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="2; url=faturalar.php?id=' . $siid . '"> ';
                                } else {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Fatura / İrsaliye Belge Güncelleme','$yonetici_id_sabit','$zaman','Başarısız')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-danger" role="alert">  Sayın ' . $adsoyad . ' <br> Fatura / İrsaliye Malesef Güncellendi. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="2; url=faturalar.php?id=' . $siid . '"> ';
                                }
                            }
                            ?>
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-4">Fatura İrsaliye Yönetimi</h4>
                                    <div class="table-responsive">
                                        <table id="datatable" class="table table-bordered table-responsive nsowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                            <thead>
                                                <tr>
                                                    <th>Tarih</th>
                                                    <th>Türü</th>
                                                    <th>Belge</th>
                                                    <th>Açıklama</th>

                                                    <th>İşlemler</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $siid = $_GET["id"];
                                                $Kategori_sorgulama = mysqli_query($db, "SELECT * FROM  faturairsaliye where sirketid='$siid' ");
                                                while ($kategoriler = mysqli_fetch_array($Kategori_sorgulama)) {
                                                ?>
                                                    <tr>
                                                        <td><?php echo $kategoriler["tarih"]; ?></td>
                                                        <td><?php echo $kategoriler["turu"]; ?></td>
                                                        <td><a href="faturalar/<?php echo $kategoriler["resim"]; ?>" class="btn btn-sm btn-info" target="_blank">Belge</a></td>
                                                        <td><small><?php echo $kategoriler["aciklama"]; ?></small></td>


                                                        <td>
                                                            <button type="button" class="btn btn-info waves-effect waves-light btn-sm" data-bs-toggle="modal" data-bs-target=".duzenle<?php echo $kategoriler["id"]; ?>"> Düzenle </button>
                                                            <button type="button" class="btn btn-info waves-effect waves-light btn-sm" data-bs-toggle="modal" data-bs-target=".resim<?php echo $kategoriler["id"]; ?>"> Resim Düzenle </button>
                                                            <a href="faturalarsil.php?id=<?php echo $kategoriler["id"]; ?>&ad=<?php echo $kategoriler["resim"]; ?>&sid=<?php echo $siid; ?>" class="btn btn-danger waves-effect waves-light btn-sm"> Sistemden Sil </a>
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
    <div class="modal fade yenikategori" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myLargeModalLabel">Yeni Belge Tanımlayınız</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form method="post" action="faturalar.php?id=<?php echo $siid; ?>" class="needs-validation" novalidate enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <input type="text" name="sirketid" value="<?php echo $_GET["id"]; ?>" class="form-control" id="validationCustom01" required hidden>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Belge Türü?</label>
                                    <select name="turu" class="form-control" id="validationCustom01" required>
                                        <option value="Fatura">Fatura</option>
                                        <option value="İrsaliye">İrsaliye</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Belge</label>
                                    <input name="resimdosya" type="file" multiple="multiple" name="resimdosya[]" class="form-control" id="recipient-name">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Açıklama</label>
                                    <textarea type="text" name="aciklama" class="form-control" id="validationCustom01" required></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Vazgeçtim, Kapat</button>
                        <button type="submit" name="kayit" class="btn btn-success">Yeni Belge Oluştur!</button>
                    </div>
                </form>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div>
    <div class="modal fade yardim" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
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
    $Kategori_sorgulama = mysqli_query($db, "SELECT * FROM  faturairsaliye  where sirketid='$siid' ");
    while ($markalar = mysqli_fetch_array($Kategori_sorgulama)) {
    ?>
        <div class="modal fade duzenle<?php echo $markalar["id"]; ?>" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="myLargeModalLabel"><b><?php echo $markalar["turu"]; ?></b> Düzenleyin!</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        </button>
                    </div>
                    <form method="post" action="faturalar.php?id=<?php echo $siid; ?>" class="needs-validation" novalidate>
                        <div class="modal-body">
                            <div class="row">

                                <input type="text" name="sirketid" value="<?php echo $_GET["id"]; ?>" class="form-control" id="validationCustom01" required hidden>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Belge Türü?</label>
                                        <select name="turu" class="form-control" id="validationCustom01" required>
                                            <option value="<?php echo $markalar["turu"]; ?>" selected><?php echo $markalar["turu"]; ?></option>
                                            <option value="Fatura">Fatura</option>
                                            <option value="İrsaliye">İrsaliye</option>
                                        </select>

                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Açıklama</label>
                                        <textarea type="text" name="aciklama" class="form-control" id="validationCustom01" required><?php echo $markalar["aciklama"]; ?></textarea>
                                    </div>
                                </div>
                                <input type="text" name="icerikid" value="<?php echo $markalar["id"]; ?>" hidden>
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
    <?php
    $Kategori_sorgulama = mysqli_query($db, "SELECT * FROM  faturairsaliye  where sirketid='$siid' ");
    while ($markalar = mysqli_fetch_array($Kategori_sorgulama)) {
    ?>
        <div class="modal fade resim<?php echo $markalar["id"]; ?>" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="myLargeModalLabel"><b><?php echo $markalar["kategori_adi"]; ?></b> Resimi Düzenleyin!</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        </button>
                    </div>
                    <form method="post" action="faturalar.php?id=<?php echo $siid; ?>" class="needs-validation" novalidate enctype="multipart/form-data">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Marka Resim</label>
                                        <input name="resimdosya" type="file" multiple="multiple" name="resimdosya[]" class="form-control" id="recipient-name">
                                    </div>
                                </div>
                                <input type="text" name="icerikid" value="<?php echo $markalar["id"]; ?>" hidden>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Vazgeçtim, Kapat</button>
                            <button type="submit" name="resim" class="btn btn-success">Düzenleyin!</button>
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