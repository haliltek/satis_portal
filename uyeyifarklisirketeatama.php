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
                            <button type="button" class="btn btn-info btn-sm waves-effect waves-light float-right" data-bs-toggle="modal" data-bs-target=".yardim">Yardım</button>
                            <hr>
                            <?php
                            if (isset($_POST['duzenleme'])) {
                                $uye = xss(addslashes($_POST["uye"]));
                                $sirket = xss(addslashes($_POST["sirket"]));
                                $yetki = xss(addslashes($_POST["yetki"]));
                                if ($yetki == 'Yönetici') {
                                    $kategoriduzenleme = "UPDATE personel SET p_sirket = '$sirket' WHERE personel_id= '$uye'";
                                    $duzenleme = mysqli_query($db, $kategoriduzenleme);
                                    $kategoriduzenleme2 = "UPDATE sirket SET yetkili = '$uye' WHERE sirket_id= '$sirket'";
                                    $duzenleme2 = mysqli_query($db, $kategoriduzenleme2);
                                } else if ($yetki == 'Personel') {
                                    $kategoriduzenleme = "UPDATE personel SET p_sirket = '$sirket' WHERE personel_id= '$uye'";
                                    $duzenleme = mysqli_query($db, $kategoriduzenleme);
                                }

                                if ($duzenleme) {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Personel Farklı Şirkete Atama','$yonetici_id_sabit','$zaman','Başarılı')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-success" role="alert">  Sayın ' . $adsoyad . ' <br> Personel Ataması Başarıyla Kaydedilmiştir. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="2; url=uyeyifarklisirketeatama.php"> ';
                                } else {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Personel Farklı Şirkete Atama','$yonetici_id_sabit','$zaman','Başarısız')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-danger" role="alert">  Sayın ' . $adsoyad . ' <br> Personel Ataması Malesef Kaydedilemedi. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="2; url=uyeyifarklisirketeatama.php"> ';
                                }
                            }
                            ?>
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-4"><b>Üyeyi Farklı Şirkete Atayın!</b> </h4>
                                    <table id="datatable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>Sıra</th>
                                                <th>Adı</th>
                                                <th>Şirket</th>
                                                <th>E-Posta</th>
                                                <th>Cep Telefon</th>
                                                <th>Kayıt Tarih</th>
                                                <th>Son Oturum</th>
                                                <th>Sözleşme</th>
                                                <th>Durumu</th>
                                                <th>İşlemler</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $i = 1;
                                            $personel_sorgulama = mysqli_query($db, "SELECT * FROM  personel");
                                            while ($personel = mysqli_fetch_array($personel_sorgulama)) {
                                            ?>
                                                <tr>
                                                    <td><?php echo $i; ?></td>
                                                    <td><?php echo $personel["p_adi"]; ?> <?php echo $personel["p_soyadi"]; ?></td>
                                                    <td>
                                                        <?php
                                                        $sirid = $personel["p_sirket"];
                                                        $sirket_sorgulama = mysqli_query($db, "SELECT * FROM  sirket where sirket_id='$sirid' ");
                                                        $sirketbilgi = mysqli_fetch_array($sirket_sorgulama);
                                                        echo $sirketbilgi["s_adi"];
                                                        ?>
                                                    </td>
                                                    <td><?php echo $personel["p_eposta"]; ?></td>
                                                    <td><?php echo $personel["p_cep"]; ?></td>
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
                                                    <td>
                                                        <button type="button" class="btn btn-info waves-effect waves-light btn-sm" data-bs-toggle="modal" data-bs-target=".duzenle<?php echo $personel["personel_id"]; ?>"> Farklı Şirkete Atayın! </button>
                                                    </td>
                                                </tr>
                                            <?php $i++;
                                            } ?>
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
    $sirket_sorgulama = mysqli_query($db, "SELECT * FROM  personel");
    while ($sirket = mysqli_fetch_array($sirket_sorgulama)) {
        $sirid = $sirket["p_sirket"];
        $sirket_sorgulama2 = mysqli_query($db, "SELECT * FROM  sirket where sirket_id='$sirid' ");
        $sirketbilgi = mysqli_fetch_array($sirket_sorgulama2);
    ?>
        <div class="modal fade duzenle<?php echo $sirket["personel_id"]; ?>" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="modal-title" id="myLargeModalLabel"><b><?php echo $sirketbilgi["s_adi"]; ?> Şirketindeki <?php echo $sirket["p_adi"]; ?> <?php echo $sirket["p_soyadi"]; ?></b> Üyeyi Değiştirin !</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        </button>
                    </div>
                    <form method="post" action="uyeyifarklisirketeatama.php" class="needs-validation" novalidate>
                        <div class="modal-body">
                            <div class="p-2 mt-2">
                                <form action="uyeyifarklisirketeatama.php" method="post">
                                    <div class="row">
                                        <div class="mb-3 col-md-6">
                                            <label class="form-label" for="username">Personel </label>
                                            <select name="uye" class="form-control">
                                                <option value="<?php echo $sirket["personel_id"]; ?>"><?php echo $sirket["p_adi"]; ?> <?php echo $sirket["p_soyadi"]; ?></option>
                                            </select>
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label class="form-label" for="username"> Şirketler</label>
                                            <select name="sirket" class="form-control">
                                                <?php $sirket_sorgulama2 = mysqli_query($db, "SELECT * FROM  sirket");
                                                while ($sirketbilgi2 = mysqli_fetch_array($sirket_sorgulama2)) { ?>
                                                    <option value="<?php echo $sirketbilgi2["sirket_id"]; ?>"><?php echo $sirketbilgi2["s_adi"]; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="mb-3 col-md-12">
                                            <label class="form-label" for="username">Yetkisi Nedir? </label>
                                            <select name="yetki" class="form-control">
                                                <option value="Yönetici">Yönetici</option>
                                                <option value="Personel">Personel</option>
                                            </select>
                                        </div>
                                    </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Vazgeçtim, Kapat</button>
                            <button type="submit" name="duzenleme" class="btn btn-success">Atamayı Tamamlayın!</button>
                        </div>
                    </form>
                </div><!-- /.modal-content -->
            </div><!-- /.modal-dialog -->
        </div>
        </div>
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