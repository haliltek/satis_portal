<?php include "fonk.php";
oturumkontrol();
$id = $_GET["id"]; //id olan grup idsi Kat olan ise kategori idsi
$kat = $_GET["kat"];
$kategori_sorgulamam = mysqli_query($db, "SELECT * FROM  kategoriler where kategori_id='$kat' ");
$ustkategorim = mysqli_fetch_array($kategori_sorgulamam);  ?>
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
                            <button type="button" class="btn btn-success waves-effect waves-light float-right" data-bs-toggle="modal" data-bs-target=".yenikategori">Yeni Alt Kategori Tanımlayınız</button>
                            <a href="kategori_grup.php?id=<?php echo $kat; ?>" class="btn btn-primary btn-sm waves-effect waves-light float-right">Kategori Gruplarına Dönün</a>
                            <button type="button" class="btn btn-info btn-sm waves-effect waves-light float-right" data-bs-toggle="modal" data-bs-target=".yardim">Yardım</button>
                            <hr>
                            <?php
                            if (isset($_POST['kayit'])) {
                                $kategoriadi = $_POST["kategoriadi"];
                                $siralamas = $_POST["siralama"];
                                if ($siralamas) {
                                    $siralama = $siralamas;
                                } else {
                                    $siralama = '0';
                                }
                                $title = $_POST["title"];
                                $seo = seo($title);
                                $genelayar_sorgu = "INSERT INTO altkategoriler(altkategori_adi,url,title,sira,ustkategori_id,grupid) VALUES('$kategoriadi','$seo','$title','$siralama','$kat','$id')";
                                $add = mysqli_query($db, $genelayar_sorgu);
                                if ($add) {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Yeni Alt Kategori Eklendi','$yonetici_id_sabit','$zaman','Başarılı')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-success" role="alert">  Sayın ' . $adsoyad . ' <br> Kategori Başarıyla Kaydedilmiştir. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="1; url=kategori_altkategoriler.php?id=' . $id . '&kat=' . $kat . '"> ';
                                } else {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Yeni  Alt Kategori Eklendi','$yonetici_id_sabit','$zaman','Başarısız')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-danger" role="alert">  Sayın ' . $adsoyad . ' <br> Kategori Malesef Kaydedilemedi. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="1; url=kategori_altkategoriler.php?id=' . $id . '&kat=' . $kat . '"> ';
                                }
                            } else
                       if (isset($_POST['duzenleme'])) {
                                $kategoriadi = $_POST["kategoriadi"];
                                $siralamas = $_POST["siralama"];
                                if ($siralamas) {
                                    $siralama = $siralamas;
                                } else {
                                    $siralama = '0';
                                }
                                $title = $_POST["title"];
                                $icerikid = $_POST["icerikid"];
                                $seo = seo($title);
                                $kategoriduzenleme = "UPDATE altkategoriler SET title = '$title',sira = '$siralama',altkategori_adi = '$kategoriadi' WHERE kategori_id= '$icerikid'";
                                $duzenleme = mysqli_query($db, $kategoriduzenleme);
                                if ($duzenleme) {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Kategori  Alt Kategori Güncelleme','$yonetici_id_sabit','$zaman','Başarılı')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-success" role="alert">  Sayın ' . $adsoyad . ' <br> Kategori Başarıyla Kaydedilmiştir. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="1; url=kategori_altkategoriler.php?id=' . $id . '&kat=' . $kat . '"> ';
                                } else {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Kategori  Alt Kategori Güncelleme','$yonetici_id_sabit','$zaman','Başarısız')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-danger" role="alert">  Sayın ' . $adsoyad . ' <br> Kategori Malesef Kaydedilemedi. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="1; url=kategori_altkategoriler.php?id=' . $id . '&kat=' . $kat . '"> ';
                                }
                            }
                            ?>
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-4"><?php echo $ustkategorim["kategori_adi"]; ?> Ait Alt Kategorileri Yönetiniz</h4>
                                    <table id="datatable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>Sıra</th>
                                                <th>Alt Kategori İsmi</th>
                                                <th>Title</th>
                                                <th>URL</th>
                                                <th>İşlemler</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $Kategori_sorgulama = mysqli_query($db, "SELECT * FROM  altkategoriler where grupid='$id' and ustkategori_id='$kat'");
                                            while ($kategoriler = mysqli_fetch_array($Kategori_sorgulama)) {
                                            ?>
                                                <tr>
                                                    <td><?php echo $kategoriler["sira"]; ?></td>
                                                    <td><?php echo $kategoriler["altkategori_adi"]; ?></td>
                                                    <td><small><?php echo $kategoriler["title"]; ?></small></td>
                                                    <td><span class="badge bg-info"><a href="#" class="text-white"><?php echo $kategoriler["url"]; ?>/<?php echo $kategoriler["altkategori_id"]; ?></a></span></td>
                                                    <td>
                                                        <button type="button" class="btn btn-info waves-effect waves-light btn-sm" data-bs-toggle="modal" data-bs-target=".duzenle<?php echo $kategoriler["altkategori_id"]; ?>"> Düzenle </button>

                                                        <a href="altkategorisil.php?id=<?php echo $kategoriler["altkategori_id"]; ?>&gr=<?php echo $id; ?>&kt=<?php echo $kat; ?>" class="btn btn-danger waves-effect waves-light btn-sm"> Kaldır </a>
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
                        <div class="col-md-8"><img src="images/yardim/altkategori.png" width="100%" class="img-responsive"></div>
                        <div class="col-md-4">
                            <b>Alt Kategori Alanı</b>
                            <p>Alt Kategori Alanı E-Ticaret üzerinde bulunan Sağa açılır menüdeki grupların altında bulunan yönlendirme menülerdir. Üst Kategoriye bağlıdır. Bu alandan Alt Kategorileri oluşturabilirsiniz. </p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Anladım, Kapat</button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div>
    <div class="modal fade yenikategori" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myLargeModalLabel">Yeni Alt Kategori Tanımlayınız</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form method="post" action="kategori_altkategoriler.php?id=<?php echo $id; ?>&kat=<?php echo $kat; ?>" class="needs-validation" novalidate>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-5">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Kategori Adı</label>
                                    <input type="text" name="kategoriadi" class="form-control" id="validationCustom01" placeholder="ÖR. Aydınlatma" required>
                                    <div class="valid-feedback"> Başarılı! </div>
                                    <div class="invalid-feedback">Kategori Adı Zorunludur </div>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Title</label>
                                    <input type="text" name="title" class="form-control" id="validationCustom01" maxlength="65" placeholder="ÖR. Aydınlatma Ürünleri" required>
                                    <div class="valid-feedback"> Başarılı! </div>
                                    <div class="invalid-feedback">Title SEO Açısından Gerekli ve Zorunludur </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Sıra</label>
                                    <input type="number" name="siralama" class="form-control" id="validationCustom01" placeholder="ÖR. 2" required>
                                    <div class="valid-feedback"> Başarılı! </div>
                                    <div class="invalid-feedback">Sayfada Bu Sıralamayla Çıkacağından Zorunludur </div>
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
    $Kategori_sorgulama = mysqli_query($db, "SELECT * FROM  kategoriler order by sira asc ");
    while ($kategoriler = mysqli_fetch_array($Kategori_sorgulama)) {
    ?>
        <div class="modal fade duzenle<?php echo $kategoriler["altkategori_id"]; ?>" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="myLargeModalLabel"><b><?php echo $kategoriler["altkategori_adi"]; ?></b> Düzenleyin!</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        </button>
                    </div>
                    <form method="post" action="kategori_altkategoriler.php?id=<?php echo $id; ?>&kat=<?php echo $kat; ?>" class="needs-validation" novalidate>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Kategori Adı</label>
                                        <input type="text" name="kategoriadi" class="form-control" value="<?php echo $kategoriler["altkategori_adi"]; ?>" id="validationCustom01" placeholder="ÖR. Aydınlatma" required>
                                        <div class="valid-feedback"> Başarılı! </div>
                                        <div class="invalid-feedback">Kategori Adı Zorunludur </div>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Title</label>
                                        <input type="text" name="title" class="form-control" value="<?php echo $kategoriler["title"]; ?>" id="validationCustom01" maxlength="65" placeholder="ÖR. Aydınlatma Ürünleri" required>
                                        <div class="valid-feedback"> Başarılı! </div>
                                        <div class="invalid-feedback">Title SEO Açısından Gerekli ve Zorunludur </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Sıra</label>
                                        <input type="number" name="siralama" class="form-control" value="<?php echo $kategoriler["sira"]; ?>" id="validationCustom01" placeholder="ÖR. 2" required>
                                        <div class="valid-feedback"> Başarılı! </div>
                                        <div class="invalid-feedback">Sayfada Bu Sıralamayla Çıkacağından Zorunludur </div>
                                    </div>
                                </div>
                                <input type="text" name="icerikid" value="<?php echo $kategoriler["altkategori_id"]; ?>" hidden>
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