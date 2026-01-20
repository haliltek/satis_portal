<?php
include "fonk.php";
oturumkontrol();
?>
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
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-4">Yönetim Log Kaydı</h4>
                                    <div class="table-responsive">
                                        <table id="datatable" class="table table-bordered table-responsive nsowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                            <thead>
                                                <tr>
                                                    <th>Personel Ad Soyad</th>
                                                    <th>İşlem</th>
                                                    <th>Tarih</th>
                                                    <th>Durum</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $sozlesme_sorgulama = mysqli_query($db, "SELECT * FROM  log_yonetim order by log_id desc ");
                                                while ($iskonto = mysqli_fetch_array($sozlesme_sorgulama)) {
                                                ?>
                                                    <tr>
                                                        <td><?php $per =  $iskonto["personel"];
                                                            $perssor = mysqli_query($db, "SELECT * FROM  yonetici where yonetici_id='$per'");
                                                            $personel = mysqli_fetch_array($perssor);
                                                            echo $personel["adsoyad"];
                                                            ?></td>
                                                        <td> <?php echo $iskonto["islem"]; ?> </td>
                                                        <td> <?php echo $iskonto["tarih"]; ?> </td>
                                                        <td> <?php $dur = $iskonto["durum"];
                                                                if ($dur != 'Başarılı') {
                                                                    echo "<button class='btn btn-danger btn-sm'>Başarısız</button>";
                                                                } else {
                                                                    echo "<button class='btn btn-success btn-sm'>Başarı</button>";
                                                                } ?> </td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th>Personel Ad Soyad</th>
                                                    <th>İşlem</th>
                                                    <th>Tarih</th>
                                                    <th>Durum</th>
                                                </tr>
                                            </tfoot>
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
                    <h5 class="modal-title" id="myLargeModalLabel">Yeni Personel Tanımlayınız</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form method="post" action="personeller.php" class="needs-validation" novalidate enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Ad Soyad</label>
                                    <input type="text" name="adsoyad" class="form-control" id="validationCustom01" placeholder="ÖR. Erkan AK" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">E-Posta</label>
                                    <input type="email" name="eposta" class="form-control" id="validationCustom01" placeholder="ÖR. egemasr@gemas.com" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Parola</label>
                                    <input type="password" name="parola" class="form-control" id="validationCustom01" placeholder="***********" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Tür</label>
                                    <select class="form-control" name="tur">
                                        <option value="Personel">Personel</option>
                                        <option value="Yönetici">Yönetici</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Bölüm</label>
                                    <select class="form-control" name="bolum">
                                        <?php
                                        $departmansorgula = mysqli_query($db, "SELECT * FROM  departmanlar ");
                                        while ($departman = mysqli_fetch_array($departmansorgula)) {
                                        ?>
                                            <option value="<?php echo $departman["departman"]; ?>"><?php echo $departman["departman"]; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Telefon</label>
                                    <input type="number" name="telefon" class="form-control" id="validationCustom01" placeholder="ÖR. 05333333333" required>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Personel Kart No</label>
                                    <input type="text" name="kartno" class="form-control" id="validationCustom01" placeholder="ÖR. 5486145315" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Mail E-Posta</label>
                                    <input type="email" name="mailposta" class="form-control" id="validationCustom01" placeholder="ÖR. egemasr@gemas.com" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Mail Parola</label>
                                    <input type="text" name="mailparola" class="form-control" id="validationCustom01" placeholder="ÖR. egemasr@gemas.com" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Ünvan</label>
                                    <input type="text" name="unvan" class="form-control" id="validationCustom01" placeholder="ÖR. egemasr@gemas.com" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Vazgeçtim, Kapat</button>
                        <button type="submit" name="kayit" class="btn btn-success">Yeni Personel Oluştur!</button>
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
    $sozlesme_sorgulama = mysqli_query($db, "SELECT * FROM  yonetici ");
    while ($fihrist = mysqli_fetch_array($sozlesme_sorgulama)) {
    ?>
        <div class="modal fade duzenle<?php echo $fihrist["yonetici_id"]; ?>" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="myLargeModalLabel"><?php echo $fihrist["adsoyad"]; ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        </button>
                    </div>
                    <div class="modal-body">
                        <form method="post" action="personeller.php" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Ad Soyad</label>
                                        <input type="text" name="adsoyad" value="<?php echo $fihrist["adsoyad"] ?>" class="form-control" id="validationCustom01" placeholder="ÖR. Erkan AK" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">E-Posta</label>
                                        <input type="email" value="<?php echo $fihrist["eposta"] ?>" name="eposta" class="form-control" id="validationCustom01" placeholder="ÖR. egemasr@gemas.com" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Tür</label>
                                        <select class="form-control" name="tur">
                                            <option value="<?php echo $fihrist["tur"] ?>" selected><?php echo $fihrist["tur"] ?></option>
                                            <option value="Personel">Personel</option>
                                            <option value="Yönetici">Yönetici</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Bölüm</label>
                                        <select class="form-control" name="bolum">
                                            <option value="<?php echo $fihrist["bolum"] ?>" selected><?php echo $fihrist["bolum"] ?></option>
                                            <?php
                                            $departmansorgula = mysqli_query($db, "SELECT * FROM  departmanlar ");
                                            while ($departman = mysqli_fetch_array($departmansorgula)) {
                                            ?>
                                                <option value="<?php echo $departman["departman"]; ?>"><?php echo $departman["departman"]; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Telefon</label>
                                        <input type="number" name="telefon" class="form-control" value="<?php echo $fihrist["telefon"] ?>" id="validationCustom01" placeholder="ÖR. 05333333333" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Parola</label>
                                        <input type="text" name="parola" class="form-control" id="validationCustom01" placeholder="ÖR. 1234abcABC" required>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Personel Kart No</label>
                                        <input type="text" name="kartno" class="form-control" value="<?php echo $fihrist["kartno"] ?>" id="validationCustom01" placeholder="ÖR. 5486145315" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Mail E-Posta</label>
                                        <input type="email" name="mailposta" value="<?php echo $fihrist["mailposta"] ?>" class="form-control" id="validationCustom01" placeholder="ÖR. egemasr@gemas.com" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Mail Parola</label>
                                        <input type="text" name="mailparola" class="form-control" value="<?php echo $fihrist["mailparola"] ?>" id="validationCustom01" placeholder="ÖR. egemasr@gemas.com" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Ünvan</label>
                                        <input type="text" name="unvan" class="form-control" id="validationCustom01" value="<?php echo $fihrist["unvan"] ?>" placeholder="ÖR. egemasr@gemas.com" required>
                                    </div>
                                </div>
                                <input type="text" name="icerikid" value="<?php echo $fihrist["yonetici_id"] ?>" hidden>
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