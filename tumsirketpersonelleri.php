<?php include "fonk.php";
oturumkontrol();
$id = $_GET["id"];
$sirket_sorgulama = mysqli_query($db, "SELECT * FROM  sirket where sirket_id='$id' ");
$sirketbilgi = mysqli_fetch_array($sirket_sorgulama);  ?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title><?php echo $ayar["title"]; ?></title>
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
                            <button type="button" class="btn btn-info btn-sm waves-effect waves-light float-right" data-bs-toggle="modal" data-bs-target=".yardim">Yardım</button>
                            <hr>
                            <?php
                            if (isset($_POST['duzenleme'])) {
                                $icerikid = xss(addslashes($_POST["icerikid"]));
                                $p_adi = xss(addslashes($_POST["adi"]));
                                $p_soyadis = xss(addslashes($_POST["soyadi"]));
                                $p_soyadi = mb_strtoupper($p_soyadis, "UTF-8");
                                $p_eposta = xss(addslashes($_POST["eposta"]));
                                $p_cep = xss(addslashes($_POST["cep"]));
                                $parola = xss(addslashes(md5($_POST["parola"])));
                                if ($parola) {
                                    $kategoriduzenleme = "UPDATE personel SET p_adi = '$p_adi',p_soyadi = '$p_soyadi',p_eposta = '$p_eposta',p_cep = '$p_cep',p_parola = '$parola' WHERE personel_id= '$icerikid'";
                                    $duzenleme = mysqli_query($db, $kategoriduzenleme);
                                } else {
                                    $kategoriduzenleme = "UPDATE personel SET p_adi = '$p_adi',p_soyadi = '$p_soyadi',p_eposta = '$p_eposta',p_cep = '$p_cep' WHERE personel_id= '$icerikid'";
                                    $duzenleme = mysqli_query($db, $kategoriduzenleme);
                                }
                                if ($duzenleme) {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Beklemedeki Üye Personelleri Düzenleme','$yonetici_id_sabit','$zaman','Başarılı')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-success" role="alert">  Sayın ' . $adsoyad . ' <br> Şirket Başarıyla Kaydedilmiştir. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="2"; url=tumsirketpersonelleri.php?id=' . $id . '> ';
                                } else {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Beklemedeki Üye Personelleri Düzenleme','$yonetici_id_sabit','$zaman','Başarısız')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-danger" role="alert">  Sayın ' . $adsoyad . ' <br> Şirket Malesef Kaydedilemedi. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="2"; url=tumsirketpersonelleri.php?id=' . $id . '> ';
                                }
                            }
                            ?>
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-4"><b><?php echo $sirketbilgi["s_adi"]; ?></b> Şirket Personelleri</h4>
                                    <div class="table-responsive">
                                        <table id="datatable" class="table table-bordered table-responsive nsowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                            <thead>
                                                <tr>
                                                    <th>Sıra</th>
                                                    <th>Adı</th>
                                                    <th>E-Posta</th>
                                                    <th>Cep Telefon</th>
                                                    <th>Kayıt Tarih</th>
                                                    <th>Son Oturum</th>
                                                    <th>Sözleşme</th>
                                                    <th>Durumu</th>
                                                    <th>Sıfırlama Kodu</th>
                                                    <th>İşlemler</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $i = 1;
                                                $personel_sorgulama = mysqli_query($db, "SELECT * FROM  personel where p_sirket='$id' and p_durum='Onaylı' ");
                                                while ($personel = mysqli_fetch_array($personel_sorgulama)) {
                                                ?>
                                                    <tr>
                                                        <td><?php echo $i; ?></td>
                                                        <td><?php echo $personel["p_adi"]; ?> <?php echo $personel["p_soyadi"]; ?></td>
                                                        <td><?php echo $personel["p_eposta"]; ?>
                                                            <form method="post" action="sirket_personel_sorgulama.php" target="_blank">
                                                                <input hidden type="text" name="sorgum" class="form-control" id="validationCustom01" value="<?php echo $personel["p_eposta"]; ?>">
                                                                <button type="submit" name="sorgulayin" class="btn btn-success btn-sm"> Sorgulayın!</button>
                                                            </form>
                                                        </td>
                                                        <td><a href="tel:+9<?php echo $personel["p_cep"]; ?>" class="btn btn-success btn-sm">ARAMA</a><br><?php echo $personel["p_cep"]; ?></td>
                                                        <td><?php echo $personel["p_kayittarihi"]; ?></td>
                                                        <td><?php echo $personel["p_sonoturum"]; ?></td>
                                                        <?php $soz =  $personel["p_sozlesme"];
                                                        if ($soz == 'Onaylandı') {
                                                            echo '<td style="background-color:green; color:white; font-weight:700"><center>Onaylandı</center></td>';
                                                        } else if ($soz == 'Red') {
                                                            echo '<td style="background-color:red; color:white; font-weight:700"><center>Onaylanmadı</center></td>';
                                                        }    ?>
                                                        <?php $dur =  $personel["p_durum"];
                                                        if ($dur == 'Onaylı') {
                                                            echo '<td style="background-color:green; color:white; font-weight:700"><center>Onaylı</center></td>';
                                                        } else if ($dur == 'Beklemede') {
                                                            echo '<td style="background-color:orange; color:white; font-weight:700"><center>Beklemede</center></td>';
                                                        } else if ($dur == 'Red') {
                                                            echo '<td style="background-color:red; color:white; font-weight:700"><center>Red</center></td>';
                                                        }    ?>
                                                        <td><?php echo $personel["sifirlamakodu"]; ?>
                                                        <td>
                                                            <button type="button" class="btn btn-info waves-effect waves-light btn-sm" data-bs-toggle="modal" data-bs-target=".duzenle<?php echo $personel["personel_id"]; ?>"> Düzenle </button>
                                                            <?php
                                                            $dur =  $personel["p_durum"];
                                                            if ($dur == 'Onaylı') {
                                                                echo '<a href="bekleyenhesaponayinikaldir.php?id=' . $personel["personel_id"] . '&durum=Beklemede" class="btn btn-warning waves-effect waves-light btn-sm">  Hesabı Askıya Al! </a>';
                                                            }
                                                            ?>
                                                            <a href="sirketpersonelsil.php?id=<?php echo $personel["personel_id"]; ?>" class="btn btn-danger waves-effect waves-light btn-sm"> Sistemden Sil </a>
                                                            <br>
                                                        </td>
                                                    </tr>
                                                <?php $i++;
                                                } ?>
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
                        <div class="col-md-12">
                            <b>GENEL TANIM</b>
                            <p>Şirket alanı site üzerinden kayıt gerçekleştiren şirketlere ait verilerdir. Bu alanda onaylı, onaysız veya bekleyen tüm şirket kayıtlarına erişim sağlayabilirsiniz. </p>
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
    $sirket_sorgulama = mysqli_query($db, "SELECT * FROM  personel where p_sirket='$id'");
    while ($sirket = mysqli_fetch_array($sirket_sorgulama)) {
    ?>
        <div class="modal fade duzenle<?php echo $sirket["personel_id"]; ?>" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="myLargeModalLabel"><b><?php echo $sirket["p_adi"]; ?> <?php echo $sirket["p_soyadi"]; ?></b> Düzenleyin!</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        </button>
                    </div>
                    <form method="post" action="tumsirketpersonelleri.php?id=<?php echo $id; ?>" class="needs-validation" novalidate>
                        <div class="modal-body">
                            <div class="p-2 mt-2">
                                <form action="kayitol.php" method="post">
                                    <div class="row">
                                        <div class="mb-3 col-md-6">
                                            <label class="form-label" for="username">Adı </label>
                                            <input required type="text" class="form-control" id="username" name="adi" value="<?php echo $sirket["p_adi"]; ?>" placeholder="ÖRN: Erkan">
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label class="form-label" for="username"> Soyadı</label>
                                            <input required type="text" class="form-control" id="username" value="<?php echo $sirket["p_soyadi"]; ?>" name="soyadi" placeholder="ÖRN: AK">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="username">E-Posta Adresi</label>
                                        <input required type="email" class="form-control" id="email" value="<?php echo $sirket["p_eposta"]; ?>" name="eposta" placeholder="ÖRN: info@gemas.com">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="username">Cep Telefon Numarası</label>
                                        <input required type="number" maxlength="11" value="<?php echo $sirket["p_cep"]; ?>" class="form-control" id="username" name="cep" placeholder="ÖRN: 055555555">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="username">Parola</label>
                                        <input required type="password" class="form-control" id="username" name="parola" placeholder="ÖRN: ***********">
                                    </div>
                                    <input type="text" name="icerikid" value="<?php echo $sirket["personel_id"]; ?>" hidden>
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