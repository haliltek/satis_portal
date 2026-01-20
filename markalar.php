<?php include "fonk.php";
oturumkontrol(); ?>
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
    <link href="assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css" rel="stylesheet"
        type="text/css" />
    <!-- Responsive datatable examples -->
    <link href="assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet"
        type="text/css" />
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
                            <?php if ($tanimekle == 'Evet') { ?>
                                <button type="button" class="btn btn-success waves-effect waves-light float-right"
                                    data-bs-toggle="modal" data-bs-target=".yenikategori">Yeni Marka Tanımlayınız</button>
                            <?php } ?>

                            <hr>
                            <?php
                            if (isset($_POST['kayit'])) {
                                $markaadi = $_POST["markaadi"];
                                $onecikan = $_POST["onecikan"];
                                $siralamas = $_POST["siralama"];
                                $rand = mt_rand();
                                if ($siralamas) {
                                    $siralama = $siralamas;
                                } else {
                                    $siralama = '0';
                                }
                                $title = $_POST["title"];
                                $seo = seo($title);
                                $uploaddir = 'images/markalar/';
                                $resimadi = seogorsel(basename($rand . '-' . $_FILES['resimdosya']['name']));
                                $uploadfile = $uploaddir . seogorsel(basename($rand . '-' . $_FILES['resimdosya']['name']));
                                if (move_uploaded_file($_FILES['resimdosya']['tmp_name'], $uploadfile)) {
                                    $genelayar_sorgu = "INSERT INTO markalar(kategori_adi,onecikan,resim,url,title,sira) VALUES('$markaadi','$onecikan','$resimadi','$seo','$title','$siralama')";
                                    $add = mysqli_query($db, $genelayar_sorgu);
                                } else {
                                    echo ' <div class="alert alert-danger alert-border-left alert-dismissible fade show" role="alert">
                                    <i class="mdi mdi-check-all me-3 align-middle"></i><strong>Hata!</strong> Resim Yüklemede Sorun Oluştu!!
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>';
                                }
                                if ($add) {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Yeni Marka Kayıt','$yonetici_id_sabit','$zaman','Başarılı')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-success" role="alert">  Sayın ' . $adsoyad . ' <br> Marka Başarıyla Kaydedilmiştir. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="2; url=markalar.php"> ';
                                } else {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Yeni Marka Kayıt','$yonetici_id_sabit','$zaman','Başarısız')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-danger" role="alert">  Sayın ' . $adsoyad . ' <br> Marka Malesef Kaydedilemedi. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="2; url=markalar.php"> ';
                                }
                            } else
                                if (isset($_POST['duzenleme'])) {
                                $markaadi = $_POST["markaadi"];
                                $onecikan = $_POST["onecikan"];
                                $siralamas = $_POST["siralama"];
                                if ($siralamas) {
                                    $siralama = $siralamas;
                                } else {
                                    $siralama = '0';
                                }
                                $title = $_POST["title"];
                                $seo = seo($title);
                                $icerikid = $_POST["icerikid"];
                                $kategoriduzenleme = "UPDATE markalar SET title = '$title',sira = '$siralama',kategori_adi = '$markaadi',onecikan = '$onecikan',url = '$seo' WHERE marka_id= '$icerikid'";
                                $duzenleme = mysqli_query($db, $kategoriduzenleme);
                                if ($duzenleme) {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Marka Güncelleme','$yonetici_id_sabit','$zaman','Başarılı')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-success" role="alert">  Sayın ' . $adsoyad . ' <br> Marka Başarıyla Kaydedilmiştir. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="2; url=markalar.php"> ';
                                } else {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Marka Güncelleme','$yonetici_id_sabit','$zaman','Başarısız')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-danger" role="alert">  Sayın ' . $adsoyad . ' <br> Marka Malesef Kaydedilemedi. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="2; url=markalar.php"> ';
                                }
                            } else if (isset($_POST['resim'])) {
                                $icerikid = $_POST["icerikid"];
                                $uploaddir = 'images/markalar/';
                                $rand = mt_rand();
                                $resimadi = seogorsel(basename($rand . '-' . $_FILES['resimdosya']['name']));
                                $uploadfile = $uploaddir . seogorsel(basename($rand . '-' . $_FILES['resimdosya']['name']));
                                if (move_uploaded_file($_FILES['resimdosya']['tmp_name'], $uploadfile)) {
                                    $kategoriduzenleme = "UPDATE markalar SET resim = '$resimadi' WHERE marka_id= '$icerikid'";
                                    $add = mysqli_query($db, $kategoriduzenleme);
                                } else {
                                    echo ' <div class="alert alert-danger alert-border-left alert-dismissible fade show" role="alert">
                    <i class="mdi mdi-check-all me-3 align-middle"></i><strong>Hata!</strong> Resim Yüklemede Sorun Oluştu!!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
                                }
                                if ($add) {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Marka Resim Güncelleme','$yonetici_id_sabit','$zaman','Başarılı')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-success" role="alert">  Sayın ' . $adsoyad . ' <br> Marka Başarıyla Güncellendi. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="2; url=markalar.php"> ';
                                } else {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Marka Resim Güncelleme','$yonetici_id_sabit','$zaman','Başarısız')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-danger" role="alert">  Sayın ' . $adsoyad . ' <br> Marka Malesef Güncellendi. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="2; url=markalar.php"> ';
                                }
                            }
                            ?>
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-4">Markalar Yönetimi</h4>
                                    <table id="datatable" class="table table-bordered dt-responsive nowrap"
                                        style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>Sıra</th>
                                                <th>Marka İsmi</th>
                                                <th>Resim</th>
                                                <th>Title</th>


                                                <th>Öne Çıkan</th>
                                                <th>İşlemler</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $Kategori_sorgulama = mysqli_query($db, "SELECT * FROM  markalar order by sira asc ");
                                            while ($kategoriler = mysqli_fetch_array($Kategori_sorgulama)) {
                                            ?>
                                                <tr>
                                                    <td><?php echo $kategoriler["sira"]; ?></td>
                                                    <td><?php echo $kategoriler["kategori_adi"]; ?></td>
                                                    <td><img src="images/markalar/<?php echo $kategoriler["resim"]; ?>"
                                                            width="100px"></td>
                                                    <td><small><?php echo $kategoriler["title"]; ?></small></td>

                                                    <td><span class="badge bg-info"><a href="#"
                                                                class="text-white"><?php echo $kategoriler["url"]; ?>/<?php echo $kategoriler["marka_id"]; ?></a></span>
                                                    </td>

                                                    <td>
                                                        <?php if ($tanimduzenle == 'Evet') { ?>
                                                            <button type="button"
                                                                class="btn btn-info waves-effect waves-light btn-sm"
                                                                data-bs-toggle="modal"
                                                                data-bs-target=".duzenle<?php echo $kategoriler["marka_id"]; ?>">
                                                                Düzenle </button>
                                                            <button type="button"
                                                                class="btn btn-info waves-effect waves-light btn-sm"
                                                                data-bs-toggle="modal"
                                                                data-bs-target=".resim<?php echo $kategoriler["marka_id"]; ?>">
                                                                Resim Düzenle </button>
                                                        <?php }
                                                        if ($tanimsil == 'Evet') { ?>
                                                            <a href="markasil.php?id=<?php echo $kategoriler["marka_id"]; ?>&ad=<?php echo $kategoriler["resim"]; ?>"
                                                                class="btn btn-danger waves-effect waves-light btn-sm">
                                                                Sistemden Sil </a>
                                                        <?php } ?>
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
    <div class="modal fade yenikategori" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myLargeModalLabel">Yeni Marka Tanımlayınız</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form method="post" action="markalar.php" class="needs-validation" novalidate
                    enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-5">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Marka Adı</label>
                                    <input type="text" name="markaadi" class="form-control" id="validationCustom01"
                                        placeholder="ÖR. Panasonic - Viko" required>
                                    <div class="valid-feedback"> Başarılı! </div>
                                    <div class="invalid-feedback">Marka Adı Zorunludur </div>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Title</label>
                                    <input type="text" name="title" class="form-control" id="validationCustom01"
                                        maxlength="65" placeholder="ÖR. Aydınlatma Ürünleri" required>
                                    <div class="valid-feedback"> Başarılı! </div>
                                    <div class="invalid-feedback">Title SEO Açısından Gerekli ve Zorunludur </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Sıra</label>
                                    <input type="number" name="siralama" class="form-control" id="validationCustom01"
                                        placeholder="ÖR. 2" required>
                                    <div class="valid-feedback"> Başarılı! </div>
                                    <div class="invalid-feedback">Sayfada Bu Sıralamayla Çıkacağından Zorunludur </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Öne Çıkan Mı?</label>
                                    <select name="onecikan" class="form-control" id="validationCustom01"
                                        placeholder="ÖR. Panasonic - Viko" required>
                                        <option value="Evet">Evet</option>
                                        <option value="Hayır">Hayır</option>
                                    </select>
                                    <div class="valid-feedback"> Başarılı! </div>
                                    <div class="invalid-feedback">Öne Çıkan Belirlemek Zorunludur </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Marka Resim</label>
                                    <input name="resimdosya" type="file" multiple="multiple" name="resimdosya[]"
                                        class="form-control" id="recipient-name">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Vazgeçtim, Kapat</button>
                        <button type="submit" name="kayit" class="btn btn-success">Yeni Marka Oluştur!</button>
                    </div>
                </form>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div>

    <?php
    $Kategori_sorgulama = mysqli_query($db, "SELECT * FROM  markalar ");
    while ($markalar = mysqli_fetch_array($Kategori_sorgulama)) {
    ?>
        <div class="modal fade duzenle<?php echo $markalar["marka_id"]; ?>" tabindex="-1" role="dialog"
            aria-labelledby="myLargeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="myLargeModalLabel"><b><?php echo $markalar["kategori_adi"]; ?></b>
                            Düzenleyin!</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        </button>
                    </div>
                    <form method="post" action="markalar.php" class="needs-validation" novalidate>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Marka Adı</label>
                                        <input type="text" name="markaadi" class="form-control"
                                            value="<?php echo $markalar["kategori_adi"]; ?>" id="validationCustom01"
                                            placeholder="ÖR. Panasonic - Viko" required>
                                        <div class="valid-feedback"> Başarılı! </div>
                                        <div class="invalid-feedback">Marka Adı Zorunludur </div>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Title</label>
                                        <input type="text" name="title" class="form-control"
                                            value="<?php echo $markalar["title"]; ?>" id="validationCustom01" maxlength="65"
                                            placeholder="ÖR. Aydınlatma Ürünleri" required>
                                        <div class="valid-feedback"> Başarılı! </div>
                                        <div class="invalid-feedback">Title SEO Açısından Gerekli ve Zorunludur </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Sıra</label>
                                        <input type="number" name="siralama" value="<?php echo $markalar["siralama"]; ?>"
                                            class="form-control" id="validationCustom01" placeholder="ÖR. 2" required>
                                        <div class="valid-feedback"> Başarılı! </div>
                                        <div class="invalid-feedback">Sayfada Bu Sıralamayla Çıkacağından Zorunludur </div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Öne Çıkan Mı?</label>
                                        <select name="onecikan" class="form-control" id="validationCustom01"
                                            placeholder="ÖR. Panasonic - Viko" required>
                                            <option value="<?php echo $markalar["onecikan"]; ?>" selected>
                                                <?php echo $markalar["onecikan"]; ?> Seçili Durumda</option>
                                            <option value="Evet">Evet</option>
                                            <option value="Hayır">Hayır</option>
                                        </select>
                                        <div class="valid-feedback"> Başarılı! </div>
                                        <div class="invalid-feedback">Öne Çıkan Belirlemek Zorunludur </div>
                                    </div>
                                </div>
                                <input type="text" name="icerikid" value="<?php echo $markalar["marka_id"]; ?>" hidden>
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
    $Kategori_sorgulama = mysqli_query($db, "SELECT * FROM  markalar ");
    while ($markalar = mysqli_fetch_array($Kategori_sorgulama)) {
    ?>
        <div class="modal fade resim<?php echo $markalar["marka_id"]; ?>" tabindex="-1" role="dialog"
            aria-labelledby="myLargeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="myLargeModalLabel"><b><?php echo $markalar["kategori_adi"]; ?></b>
                            Resimi Düzenleyin!</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        </button>
                    </div>
                    <form method="post" action="markalar.php" class="needs-validation" novalidate
                        enctype="multipart/form-data">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Marka Resim</label>
                                        <input name="resimdosya" type="file" multiple="multiple" name="resimdosya[]"
                                            class="form-control" id="recipient-name">
                                    </div>
                                </div>
                                <input type="text" name="icerikid" value="<?php echo $markalar["marka_id"]; ?>" hidden>
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