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
                            <button type="button" class="btn btn-success waves-effect waves-light float-right" data-bs-toggle="modal" data-bs-target=".yenikategori">Yeni Stok Birimi Tanımlayınız</button>
                            <button type="button" class="btn btn-info btn-sm waves-effect waves-light float-right" data-bs-toggle="modal" data-bs-target=".yardim">Yardım</button>
                            <hr>
                            <?php
                            if (isset($_POST['duzenleme'])) {
                                    $title = addslashes($_POST['title']);
                                $stokalarmlaribaslangic = addslashes($_POST['stokalarmlaribaslangic']);
                                $stokalarmlaribitis = addslashes($_POST['stokalarmlaribitis']);
                                $whatsapp_approval_phone = addslashes($_POST['whatsapp_approval_phone']);
                                $uploaddir = 'images/';
                                $resimadi =  basename($_FILES['resimdosya']['name']);
                                $uploadfile = $uploaddir . basename($_FILES['resimdosya']['name']);
                                if (move_uploaded_file($_FILES['resimdosya']['tmp_name'], $uploadfile)) {
                                    $kategoriduzenleme = "UPDATE ayarlar SET stokalarmlaribaslangic = '$stokalarmlaribaslangic',stokalarmlaribitis = '$stokalarmlaribitis',resim = '$resimadi',title = '$title', whatsapp_approval_phone='$whatsapp_approval_phone'";
                                    $add = mysqli_query($db, $kategoriduzenleme);
                                } else {
                                     // Resim yoksa sadece diğer bilgileri güncelle
                                     $kategoriduzenleme = "UPDATE ayarlar SET stokalarmlaribaslangic = '$stokalarmlaribaslangic',stokalarmlaribitis = '$stokalarmlaribitis',title = '$title', whatsapp_approval_phone='$whatsapp_approval_phone'";
                                     $add = mysqli_query($db, $kategoriduzenleme);
                                }
                                if ($add) {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Genel Ayarlar Güncelleme','$yonetici_id_sabit','$zaman','Başarılı')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-success" role="alert">  Sayın ' . $adsoyad . ' <br> Genel Ayarlar Başarıyla Kaydedilmiştir. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="2; url=genelayarlar.php"> ';
                                } else {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Genel Ayarlar Güncelleme','$yonetici_id_sabit','$zaman','Başarısız')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-danger" role="alert">  Sayın ' . $adsoyad . ' <br> Genel Ayarlar Malesef Kaydedilemedi. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="2; url=genelayarlar.php"> ';
                                }
                            }
                            $genelayar_sorgulama = mysqli_query($db, "SELECT * FROM  ayarlar");
                            $ayar = mysqli_fetch_array($genelayar_sorgulama);
                            ?>
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-4">Genel Ayarlarınızı Yönetimi</h4>
                                    <form method="post" action="genelayarlar.php" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="validationCustom01">Logonuzu Yükleyiniz</label>
                                                    <input name="resimdosya" type="file" multiple="multiple" name="resimdosya[]" class="form-control" id="recipient-name">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="validationCustom01">Title Belirtiniz</label>
                                                    <input type="text" name="title" class="form-control" value="<?php echo $ayar["title"]; ?>" id="validationCustom01" placeholder="ÖR. Gemas" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="validationCustom01">Stok Alarmı İçin Başlangıç Sayısı Belirtin</label>
                                                    <input type="text" name="stokalarmlaribaslangic" class="form-control" value="<?php echo $ayar["stokalarmlaribaslangic"]; ?>" id="validationCustom01" placeholder="ÖR. 50" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="validationCustom01">Stok Alarmı İçin Bitiş Sayısı Belirtin</label>
                                                    <input type="text" name="stokalarmlaribitis" class="form-control" value="<?php echo $ayar["stokalarmlaribitis"]; ?>" id="validationCustom01" placeholder="ÖR. 100" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="validationCustom01">Yönetici WhatsApp Onay Numarası</label>
                                                    <input type="text" name="whatsapp_approval_phone" class="form-control" value="<?php echo $ayar["whatsapp_approval_phone"]; ?>" id="validationCustom01" placeholder="905xxxxxxxxx" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" name="duzenleme" class="btn btn-success">Düzenleyin!</button>
                                        </div>
                                    </form>
                                    <img src="images/<?php echo $ayar["resim"]; ?>">
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