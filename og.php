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
                            <button type="button" class="btn btn-success waves-effect waves-light float-right"
                                data-bs-toggle="modal" data-bs-target=".yenikategori">Manuel OG Tanımlayınız</button>
                            <a type="button" class="btn btn-warning waves-effect waves-light float-right"
                                href="ogexceliceriaktar.php">Excel OG Toplu İçe Aktarın</a>
                            <button type="button" class="btn btn-info btn-sm waves-effect waves-light float-right"
                                data-bs-toggle="modal" data-bs-target=".yardim">Yardım</button>
                            <hr>
                            <?php
                            if (isset($_POST['kayit'])) {
                                $kod = xss(addslashes($_POST["kod"]));
                                $adi = xss(addslashes($_POST["adi"]));
                                $liste = xss(addslashes($_POST["liste"]));
                                $birim = xss(addslashes($_POST["birim"]));
                                $doviz = xss(addslashes($_POST["doviz"]));
                                $piskonto = xss(addslashes($_POST["piskonto"]));
                                $ptutar = xss(addslashes($_POST["ptutar"]));
                                $kiskonto = xss(addslashes($_POST["kiskonto"]));
                                $ktutar = xss(addslashes($_POST["ktutar"]));
                                $aiskonto = xss(addslashes($_POST["aiskonto"]));
                                $atutar = xss(addslashes($_POST["atutar"]));
                                $genelayar_sorgu = "INSERT INTO og(kod,adi,liste,birim,doviz,piskonto,ptutar,kiskonto,ktutar,aiskonto,atutar) VALUES('$kod','$adi','$liste','$birim','$doviz','$piskonto','$ptutar','$kiskonto','$ktutar','$aiskonto','$atutar')";
                                $add = mysqli_query($db, $genelayar_sorgu);
                                if ($add) {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Yeni OG Kayıt','$yonetici_id_sabit','$zaman','Başarılı')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-success" role="alert">  Sayın ' . $adsoyad . ' <br> OG Başarıyla Kaydedilmiştir. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="2; url=og.php"> ';
                                } else {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Yeni OG Kayıt','$yonetici_id_sabit','$zaman','Başarısız')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-danger" role="alert">  Sayın ' . $adsoyad . ' <br> OG Malesef Kaydedilemedi. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="2; url=og.php"> ';
                                }
                            } else if (isset($_POST['duzenleme'])) {
                                $kod = xss(addslashes($_POST["kod"]));
                                $adi = xss(addslashes($_POST["adi"]));
                                $liste = xss(addslashes($_POST["liste"]));
                                $birim = xss(addslashes($_POST["birim"]));
                                $doviz = xss(addslashes($_POST["doviz"]));
                                $piskonto = xss(addslashes($_POST["piskonto"]));
                                $ptutar = xss(addslashes($_POST["ptutar"]));
                                $kiskonto = xss(addslashes($_POST["kiskonto"]));
                                $ktutar = xss(addslashes($_POST["ktutar"]));
                                $aiskonto = xss(addslashes($_POST["aiskonto"]));
                                $atutar = xss(addslashes($_POST["atutar"]));
                                $icerikid = $_POST["icerikid"];
                                $kategoriduzenleme = "UPDATE og SET kod = '$kod',adi = '$adi',liste = '$liste',birim = '$birim',doviz = '$doviz',piskonto = '$piskonto',ptutar = '$ptutar',kiskonto = '$kiskonto',ktutar = '$ktutar',aiskonto = '$aiskonto',atutar = '$atutar' WHERE fihrist_id= '$icerikid'";
                                $duzenleme = mysqli_query($db, $kategoriduzenleme);
                                if ($duzenleme) {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('OG Güncelleme','$yonetici_id_sabit','$zaman','Başarılı')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-success" role="alert">  Sayın ' . $adsoyad . ' <br> OG Başarıyla Kaydedilmiştir. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="2; url=og.php"> ';
                                } else {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('OG Güncelleme','$yonetici_id_sabit','$zaman','Başarısız')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-danger" role="alert">  Sayın ' . $adsoyad . ' <br> OG Malesef Kaydedilemedi. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="2; url=og.php"> ';
                                }
                            }
                            ?>
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-4">OG Yönetimi <?php
                                                                            $ustkategori_id = $kategoriler["kategori_id"];
                                                                            $sqlin = "select * from og";
                                                                            $sql_baglan = mysqli_query($db, $sqlin);
                                                                            $toplamuye = mysqli_num_rows($sql_baglan);
                                                                            if ($toplamuye) {
                                                                                echo '/ <b style="color:green;"> ' . $toplamuye . ' Adet </b>Kayıtlı Veri Bulundu';
                                                                            } else {
                                                                                echo '<b style="color:red;">Hiç Kayıtlı Veri Bulunamadı!</b>';
                                                                            }
                                                                            ?></h4>
                                    <div class="table-responsive">
                                        <table id="datatable" class="table table-bordered table-responsive nsowrap"
                                            style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                            <thead>
                                                <tr>
                                                    <th>Stok Kodu</th>
                                                    <th>Stok Adı</th>
                                                    <th>Liste Fiyatı</th>
                                                    <th>Birimi</th>
                                                    <th>Peşin </th>
                                                    <th>Kart</th>
                                                    <th>60 Gün Vade</th>
                                                    <th>İşlemler</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $sozlesme_sorgulama = mysqli_query($db, "SELECT * FROM  og");
                                                while ($fihrist = mysqli_fetch_array($sozlesme_sorgulama)) {
                                                ?>
                                                    <tr>
                                                        <td><small><?php echo $fihrist["kod"]; ?></small></td>
                                                        <td><small><?php echo $fihrist["adi"]; ?></small></td>
                                                        <td><?php echo $fihrist["liste"]; ?>
                                                            <?php echo $fihrist["doviz"]; ?></td>
                                                        <td><small><?php echo $fihrist["birim"]; ?></small></td>
                                                        <td><?php echo $fihrist["ptutar"]; ?>
                                                            <?php echo $fihrist["doviz"]; ?><br>
                                                            İskonto: %<?php echo $fihrist["piskonto"]; ?></td>
                                                        <td><?php echo $fihrist["ktutar"]; ?>
                                                            <?php echo $fihrist["doviz"]; ?><br>
                                                            İskonto: %<?php echo $fihrist["kiskonto"]; ?></td>
                                                        <td><?php echo $fihrist["atutar"]; ?>
                                                            <?php echo $fihrist["doviz"]; ?> <br>
                                                            İskonto: %<?php echo $fihrist["aiskonto"]; ?></td>
                                                        <td>
                                                            <button type="button"
                                                                class="btn btn-info waves-effect waves-light btn-sm"
                                                                data-bs-toggle="modal"
                                                                data-bs-target=".duzenle<?php echo $fihrist["fihrist_id"]; ?>">
                                                                Düzenle </button>
                                                            <a href="ogsil.php?id=<?php echo $fihrist["fihrist_id"]; ?>"
                                                                class="btn btn-danger waves-effect waves-light btn-sm">
                                                                Sistemden Sil </a>
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
                    <h5 class="modal-title" id="myLargeModalLabel">Yeni OG Tanımlayınız</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form method="post" action="og.php" class="needs-validation" novalidate enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Stok Adı</label>
                                    <input type="text" name="adi" class="form-control" id="validationCustom01"
                                        placeholder="ÖR. ABK 2550 2550x2500x3530 mm Monoblok Beton Köşk (MYD/2000-036.C)Astor"
                                        required>
                                    <div class="valid-feedback"> Başarılı! </div>
                                    <div class="invalid-feedback">Stok Adı Zorunludur </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Stok Kodu</label>
                                    <input type="text" name="kod" class="form-control" id="validationCustom01"
                                        placeholder="ÖR. ABK 2550" required>
                                    <div class="valid-feedback"> Başarılı! </div>
                                    <div class="invalid-feedback">Stok Kodu Zorunludur </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Liste Fiyatı</label>
                                    <input type="text" name="liste" class="form-control" id="validationCustom01"
                                        placeholder="ÖR. 8.250" required>
                                    <div class="valid-feedback"> Başarılı! </div>
                                    <div class="invalid-feedback">Liste Fiyatı Zorunludur </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Peşin İskonto</label>
                                    <input type="text" name="piskonto" class="form-control" id="validationCustom01"
                                        placeholder="ÖR. 60" required>
                                    <div class="valid-feedback"> Başarılı! </div>
                                    <div class="invalid-feedback">Peşin İskonto Zorunludur </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Peşin Fiyatı</label>
                                    <input type="text" name="ptutar" class="form-control" id="validationCustom01"
                                        placeholder="ÖR. 3000" required>
                                    <div class="valid-feedback"> Başarılı! </div>
                                    <div class="invalid-feedback">Peşin Fiyatı Zorunludur </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Kart İskonto</label>
                                    <input type="text" name="kiskonto" class="form-control" id="validationCustom01"
                                        placeholder="ÖR. 60" required>
                                    <div class="valid-feedback"> Başarılı! </div>
                                    <div class="invalid-feedback">Kart İskonto Zorunludur </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Kart Fiyatı</label>
                                    <input type="text" name="ktutar" class="form-control" id="validationCustom01"
                                        placeholder="ÖR. 3000" required>
                                    <div class="valid-feedback"> Başarılı! </div>
                                    <div class="invalid-feedback">Kart Fiyatı Zorunludur </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">60 Gün Vadeli İskonto</label>
                                    <input type="text" name="aiskonto" class="form-control" id="validationCustom01"
                                        placeholder="ÖR. 60" required>
                                    <div class="valid-feedback"> Başarılı! </div>
                                    <div class="invalid-feedback">60 Gün Vadeli İskonto Zorunludur </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">60 Gün Vadeli Fiyatı</label>
                                    <input type="text" name="atutar" class="form-control" id="validationCustom01"
                                        placeholder="ÖR. 3000" required>
                                    <div class="valid-feedback"> Başarılı! </div>
                                    <div class="invalid-feedback">60 Gün Vadeli Fiyatı Zorunludur </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Birimi</label>
                                    <input type="text" name="birim" class="form-control" id="validationCustom01"
                                        placeholder="ÖR. Ad" required>
                                    <div class="valid-feedback"> Başarılı! </div>
                                    <div class="invalid-feedback">Birimi Zorunludur </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Döviz Türü</label>
                                    <input type="text" name="doviz" class="form-control" id="validationCustom01"
                                        placeholder="ÖR. USD" required>
                                    <div class="valid-feedback"> Başarılı! </div>
                                    <div class="invalid-feedback">Döviz Türü Zorunludur </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Vazgeçtim, Kapat</button>
                        <button type="submit" name="kayit" class="btn btn-success">Yeni OG Oluştur!</button>
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
                        <div class="col-md-8"><img src="images/yardim/kategori.png" width="100%" class="img-responsive">
                        </div>
                        <div class="col-md-4">
                            <b>Kategori Alanı</b>
                            <p>Kategori Alanı E-Ticaret üzerinde bulunan sol kısımdaki listeli menülerdir. Bu kısımdan
                                ürünlere ait kategoriler tanımlanmalıdır. </p>
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
    $sozlesme_sorgulama = mysqli_query($db, "SELECT * FROM  og ");
    while ($fihrist = mysqli_fetch_array($sozlesme_sorgulama)) {
    ?>
        <div class="modal fade duzenle<?php echo $fihrist["fihrist_id"]; ?>" role="dialog"
            aria-labelledby="myLargeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="myLargeModalLabel"><?php echo $fihrist["kod"]; ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        </button>
                    </div>
                    <div class="modal-body">
                        <form method="post" action="og.php" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Stok Adı</label>
                                        <input type="text" name="adi" class="form-control"
                                            value="<?php echo $fihrist["adi"]; ?>" id="validationCustom01"
                                            placeholder="ÖR. ABK 2550 2550x2500x3530 mm Monoblok Beton Köşk (MYD/2000-036.C)Astor"
                                            required>
                                        <div class="valid-feedback"> Başarılı! </div>
                                        <div class="invalid-feedback">Stok Adı Zorunludur </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Stok Kodu</label>
                                        <input type="text" name="kod" class="form-control"
                                            value="<?php echo $fihrist["kod"]; ?>" id="validationCustom01"
                                            placeholder="ÖR. ABK 2550" required>
                                        <div class="valid-feedback"> Başarılı! </div>
                                        <div class="invalid-feedback">Stok Kodu Zorunludur </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Liste Fiyatı</label>
                                        <input type="text" name="liste" class="form-control"
                                            value="<?php echo $fihrist["liste"]; ?>" id="validationCustom01"
                                            placeholder="ÖR. 8.250" required>
                                        <div class="valid-feedback"> Başarılı! </div>
                                        <div class="invalid-feedback">Liste Fiyatı Zorunludur </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Peşin İskonto</label>
                                        <input type="text" name="piskonto" class="form-control" id="validationCustom01"
                                            value="<?php echo $fihrist["piskonto"]; ?>" placeholder="ÖR. 60" required>
                                        <div class="valid-feedback"> Başarılı! </div>
                                        <div class="invalid-feedback">Peşin İskonto Zorunludur </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Peşin Fiyatı</label>
                                        <input type="text" name="ptutar" class="form-control" id="validationCustom01"
                                            value="<?php echo $fihrist["ptutar"]; ?>" placeholder="ÖR. 3000" required>
                                        <div class="valid-feedback"> Başarılı! </div>
                                        <div class="invalid-feedback">Peşin Fiyatı Zorunludur </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Kart İskonto</label>
                                        <input type="text" name="kiskonto" class="form-control" id="validationCustom01"
                                            value="<?php echo $fihrist["kiskonto"]; ?>" placeholder="ÖR. 60" required>
                                        <div class="valid-feedback"> Başarılı! </div>
                                        <div class="invalid-feedback">Kart İskonto Zorunludur </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Kart Fiyatı</label>
                                        <input type="text" name="ktutar" class="form-control" id="validationCustom01"
                                            value="<?php echo $fihrist["ktutar"]; ?>" placeholder="ÖR. 3000" required>
                                        <div class="valid-feedback"> Başarılı! </div>
                                        <div class="invalid-feedback">Kart Fiyatı Zorunludur </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">60 Gün Vadeli İskonto</label>
                                        <input type="text" name="aiskonto" class="form-control" id="validationCustom01"
                                            value="<?php echo $fihrist["aiskonto"]; ?>" placeholder="ÖR. 60" required>
                                        <div class="valid-feedback"> Başarılı! </div>
                                        <div class="invalid-feedback">60 Gün Vadeli İskonto Zorunludur </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">60 Gün Vadeli Fiyatı</label>
                                        <input type="text" name="atutar" class="form-control" id="validationCustom01"
                                            value="<?php echo $fihrist["atutar"]; ?>" placeholder="ÖR. 3000" required>
                                        <div class="valid-feedback"> Başarılı! </div>
                                        <div class="invalid-feedback">60 Gün Vadeli Fiyatı Zorunludur </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Birimi</label>
                                        <input type="text" name="birim" class="form-control" id="validationCustom01"
                                            value="<?php echo $fihrist["birim"]; ?>" placeholder="ÖR. Ad" required>
                                        <div class="valid-feedback"> Başarılı! </div>
                                        <div class="invalid-feedback">Birimi Zorunludur </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Döviz Türü</label>
                                        <input type="text" name="doviz" class="form-control" id="validationCustom01"
                                            value="<?php echo $fihrist["doviz"]; ?>" placeholder="ÖR. USD" required>
                                        <div class="valid-feedback"> Başarılı! </div>
                                        <div class="invalid-feedback">Döviz Türü Zorunludur </div>
                                    </div>
                                </div>
                                <input type="text" name="icerikid" value="<?php echo $fihrist["fihrist_id"] ?>" hidden>
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