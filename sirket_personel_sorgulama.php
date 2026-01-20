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
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-4">Şirket Personellerini Sorgulayın</h4>
                                    <form method="post" action="sirket_personel_sorgulama.php">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="mb-3">
                                                    <label class="form-label" for="validationCustom01">Personel Bilgisi Giriniz. (Şirket Girmeyin)</label>
                                                    <input type="text" name="sorgum" class="form-control" id="validationCustom01" placeholder="Arama Kriterleri: E-Posta, Telefon, Adı Soyadı" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="submit" name="sorgulayin" class="btn btn-success">Personel Sorgulayın!</button>
                                            </div>
                                        </div>
                                    </form>
                                    <style>
                                        .blink {
                                            animation: blinker 0.9s linear infinite;
                                            color: white;
                                            font-size: 12px;
                                            font-family: sans-serif;
                                        }

                                        @keyframes blinker {
                                            50% {
                                                opacity: 0;
                                            }
                                        }
                                    </style>
                                    <?php
                                    if (isset($_POST['sorgulayin'])) {  ?>
                                        <table id="datatable" class="table table-bordered table-responsive nsowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                            <thead>
                                                <tr>
                                                    <th>Sıra</th>
                                                    <th>Adı</th>
                                                    <th>E-Posta</th>
                                                    <th>Telefon</th>
                                                    <th>Özel Durum</th>
                                                    <th>Adres</th>
                                                    <th>Kategori</th>
                                                    <th>İşlemler</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $sorgum = $_POST["sorgum"];
                                                $i = 1;
                                                $personel_sorgulama = mysqli_query($db, "SELECT * FROM  personel where p_eposta LIKE '%$sorgum%'");
                                                while ($personel = mysqli_fetch_array($personel_sorgulama)) {
                                                    $yetkilisirketid =  $personel["p_sirket"];
                                                    $sirket_sorgulama = mysqli_query($db, "SELECT * FROM  sirket where sirket_id ='$yetkilisirketid'");
                                                    while ($sirket = mysqli_fetch_array($sirket_sorgulama)) {
                                                ?>
                                                        <tr>
                                                            <td><?php echo $i; ?></td>
                                                            <td><b><?php echo $personel["p_adi"]; ?> <?php echo $personel["p_soyadi"]; ?></b><br>
                                                                <small>(<?php echo $sirket["s_adi"]; ?>)<br>(V.No: <?php echo $sirket["s_vno"]; ?>)</small>
                                                            </td>
                                                            <td><?php echo $personel["p_eposta"]; ?></td>
                                                            <td><?php echo $personel["p_cep"]; ?></td>
                                                            <td>
                                                                <?php $dur =  $personel["p_durum"];
                                                                if ($dur == 'Onaylı') {
                                                                } else 
                                        if ($dur == 'Beklemede') {
                                                                    echo '<b class="blink" style= "color:orange;  font-weight:600"> Üye  Beklemede</b>';
                                                                } else
                                        if ($dur == 'Red') {
                                                                    echo '<b class="blink" style= "color:red;  font-weight:600"> Red Edilmiş Üye </b>';
                                                                }    ?>
                                                                <?php $acik =  $sirket["acikhesap"];
                                                                if ($acik == 'HYR') {
                                                                    echo '<b class="blink" style="background-color:red; color:white; font-weight:600">Açık Hesap Çalışamaz</b>';
                                                                } ?>
                                                            </td>
                                                            <td><?php echo $sirket["s_adresi"]; ?></td>
                                                            <td><?php echo $sirket["kategori"]; ?></td>
                                                            <td>
                                                                <button type="button" class="btn btn-info waves-effect waves-light btn-sm" data-bs-toggle="modal" data-bs-target=".duzenle<?php echo $personel["personel_id"]; ?>"> İnceleyin </button>
                                                                <?php
                                                                $dur =  $personel["p_durum"];
                                                                if ($dur == 'Onaylı') {
                                                                    echo '<a href="bekleyenhesaponayinikaldir.php?id=' . $personel["personel_id"] . '&durum=Beklemede" class="btn btn-warning waves-effect waves-light btn-sm">  Hesabı Askıya Al! </a> 
                                         ';
                                                                }
                                                                ?>
                                                                <a href="sirketpersonelsil.php?id=<?php echo $personel["personel_id"]; ?>" class="btn btn-danger waves-effect waves-light btn-sm"> Sistemden Sil </a>
                                                                <form action="../yon_login_correct.php" method="get" style="float:left; margin-right:5px;" target="_blank">
                                                                    <input required type="email" class="form-control" id="email" name="eposta" value="<?php echo $personel["p_eposta"]; ?>" hidden>
                                                                    <input required type="password" class="form-control" id="userpassword" name="parola" value="<?php echo $personel["p_parola"]; ?>" hidden>

                                                                </form>
                                                            </td>
                                                        </tr>
                                                <?php $i++;
                                                    }
                                                } ?>
                                            </tbody>
                                            <tfoot>
                                                <th>Sıra</th>
                                                <th>Adı</th>
                                                <th>E-Posta</th>
                                                <th>Telefon</th>
                                                <th>Özel Durum</th>
                                                <th>Adres</th>
                                                <th>Kategori</th>
                                                <th>İşlemler</th>
                                            </tfoot>
                                        </table>
                                    <?php } ?>
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
    <?php
    $personel_sorgulama = mysqli_query($db, "SELECT * FROM  personel  ");
    while ($personel = mysqli_fetch_array($personel_sorgulama)) {
        $yetkilisirketid =  $personel["p_sirket"];
        $sirket_sorgulama = mysqli_query($db, "SELECT * FROM  sirket where sirket_id ='$yetkilisirketid'");
        while ($sirket = mysqli_fetch_array($sirket_sorgulama)) {
    ?>
            <div class="modal fade duzenle<?php echo $personel["personel_id"]; ?>" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="myLargeModalLabel"><b><?php echo $personel["p_adi"]; ?> <?php echo $personel["p_soyadi"]; ?></b> İnceleyin!</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                            </button>
                        </div>

                        <div class="modal-body">
                            <div class="p-2 mt-2">
                                <div class="row">
                                    <?php $acik =  $sirket["acikhesap"];
                                    if ($acik == 'HYR') {
                                        echo '<b class="blink" style="background-color:red; font-size:20px; color:white; font-weight:600">Açık Hesap Çalışamaz</b>';
                                    } ?>
                                    <br>
                                    <?php $dur =  $personel["p_durum"];
                                    if ($dur == 'Onaylı') {
                                    } else 
                            if ($dur == 'Beklemede') {
                                        echo '<b class="blink"  style="background-color:orange; font-size:20px; color:white; font-weight:600"> Üye  Beklemede</b>';
                                    } else
                            if ($dur == 'Red') {
                                        echo '<b class="blink" style="background-color:red; font-size:20px; color:white; font-weight:600"  font-weight:600"> Red Edilmiş Üye </b>';
                                    }    ?><br>
                                    <br>
                                    <h2>
                                        <center><b>Personel Bilgisi</b></center>
                                    </h2>
                                    <table class="table dt-responsive table-bordered">
                                        <tbody>
                                            <tr>
                                                <td style="width:33.33%;"> <b>Ad Soyad</b><br> <?php echo $personel["p_adi"]; ?> <?php echo $personel["s_soyadi"]; ?> </td>
                                                <td style="width:33.33%;"><b>E-Posta</b> <br> <?php echo $personel["p_eposta"]; ?></td>
                                                <td style="width:33.33%;"><b>Telefon</b><br> <?php echo $personel["p_cep"]; ?></td>
                                            </tr>
                                            <tr>
                                                <td style="width:33.33%;"><b>Kayıt Tarihi</b><br> <?php echo $personel["p_kayittarihi"]; ?></td>
                                                <td style="width:33.33%;"><b>Son Oturum - Son Çıkış - Fark</b> <br>Açma : <?php echo $basi = $personel["p_sonoturum"]; ?> <br> Çıkış: <?php echo  $soni = $personel["p_soncikis"];
                                                                                                                                                                                        echo "<br> ";
                                                                                                                                                                                        $baslangic     = strtotime($basi);
                                                                                                                                                                                        $bitis         = strtotime($soni);
                                                                                                                                                                                        $fark        = abs($bitis - $baslangic);
                                                                                                                                                                                        $toplantiSure = $fark / 60;
                                                                                                                                                                                        echo "Ort. " . round($toplantiSure) . " Dakika Kaldı."
                                                                                                                                                                                        ?></td>
                                                <td style="width:33.33%;"><b>Durumu</b><br> <?php echo $personel["p_durum"]; ?></td>
                                            </tr>
                                    </table>
                                    <hr>
                                    <br>
                                    <h2>
                                        <center><b>Şirket Bilgisi</b></center>
                                    </h2>
                                    <table class="table dt-responsive table-bordered">
                                        <tbody>
                                            <tr style="width:100%">
                                                <td colspan="3">
                                                    <center><b>Şirket Adı</b><br>
                                                        <love style="font-size:20px; color:red"> <?php echo $sirket["s_adi"]; ?>
                                                    </center>
                                                    </love>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="width:33.33%;"><b>Şirket Türü</b> <br> <?php echo $sirket["s_arp_code"]; ?></td>
                                                <td style="width:33.33%;"><b>Şirket Telefonu</b><br> <?php echo $sirket["s_telefonu"]; ?></td>
                                                <td style="width:33.33%;"><b>Kategori</b><br> <?php echo $sirket["kategori"]; ?></td>
                                            </tr>
                                            <tr>
                                                <td style="width:33.33%;"><b>Vergi Numarası</b><br> <?php echo $sirket["s_vno"]; ?></td>
                                                <td style="width:33.33%;"><b>Vergi Dairesi</b><br> <?php echo $sirket["s_vd"]; ?></td>
                                                <td style="width:33.33%;"><b>Adresi</b><br> <?php echo $sirket["s_adresi"]; ?> / <?php echo $sirket["s_il"]; ?> / <?php echo $sirket["s_ilce"]; ?></td>
                                            </tr>
                                    </table>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">İnceledim, Kapat</button>
                                </div>
                                </form>
                            </div><!-- /.modal-content -->
                        </div><!-- /.modal-dialog -->
                    </div>
                </div>
            </div>
    <?php }
    } ?>
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