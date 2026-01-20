<?php include "fonk.php";
firmakontrol2(); ?>
<!doctype html>
<html lang="tr">

<head>
    <meta charset="utf-8" />
    <title><?php include "title.php"; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php $ayar["title"]; ?>" name="description" />
    <meta content="<?php $ayar["title"]; ?>" name="author" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="assets_login/images/favicon.ico">
    <!-- Bootstrap Css -->
    <link href="assets_login/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="assets_login/css/icons.min.css" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="assets_login/css/app.min.css" id="app-style" rel="stylesheet" type="text/css" />
    <style type="text/css">
        body {
            background-image: url('images/bg.jpg');
            -webkit-background-size: cover;
            -moz-background-size: cover;
            -o-background-size: cover;
            background-size: cover;
            opacity: 1;
        }
    </style>
</head>

<body class="authentication-bg">
    <div class="account-pages my-5 pt-sm-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="text-center">
                        <a href="index.php" class="mb-5 d-block auth-logo">
                            <img src="images/logo.png" alt="" height="100" class="logo logo-dark">
                            <img src="images/logo.png" alt="" height="100" class="logo logo-light">
                        </a>
                    </div>
                </div>
            </div>
            <div class="row align-items-center justify-content-center">
                <div class="col-md-8 col-lg-6 col-xl-5">
                    <div class="card " style="border:3px solid lightgray; border-radius: 5%; -webkit-box-shadow: -1px 5px 72px -3px rgba(0,0,0,0.75);
                    -moz-box-shadow: -1px 5px 72px -3px rgba(0,0,0,0.75);
                    box-shadow: -1px 5px 72px -3px rgba(0,0,0,0.75);">
                        <div class="card-body p-4">
                            <div class="text-center mt-2">
                                <?php
                                $ayarbagla = mysqli_query($db, "select * from genelayarlar");
                                $ayar = mysqli_fetch_array($ayarbagla);
                                $bakimmodu =  $ayar["bakimmodu"];
                                if ($bakimmodu == '1') {
                                    echo "<h4 style='color:red'>Şuan sistemde bakım yapılmaktadır. Lütfen kısa bir süre sonra tekrar deneyiniz!</h4>";
                                    die;
                                }
                                ?>
                                <h5 class="text-primary">Gemas Sıfırlama Kodu Girin!</h5>
                                <?php
                                if (isset($_POST['oturumyaptirs'])) {
                                    $kullanici = guvenlik(xss($_POST['eposta']));
                                    $kontrolKullaniciAdi = mysqli_query($db, "SELECT * FROM   personel WHERE p_eposta= '$kullanici' and p_durum='Onaylı'");
                                    if ($dev = mysqli_fetch_array($kontrolKullaniciAdi)) {
                                        $ozelkod =  'YGN' . rand(5435, 472434);
                                        $perid = $dev["personel_id"];
                                        $adsoyad = $dev["p_adi"] . ' ' . $dev["p_soyadi"];
                                        $sifirlamabaglantisor = "UPDATE personel SET sifirlamakodu = '$ozelkod'  WHERE personel_id= '$perid'";
                                        $duzenleme = mysqli_query($db, $sifirlamabaglantisor);
                                        if ($duzenleme) {
                                            echo '<div class="alert alert-success" role="alert">  Sayın ' . $adsoyad . ' Sıfırlama Kodu eposta adresinize gönderilmiştir. <br><br>Gönderilen kodu girebilmeniz için sizi yönlendiriyoruz. <br>Bekleyiniz...</div>  ';
                                            echo '<meta http-equiv="refresh" content="5; url=sifirlamakodgir.php"> ';
                                            exit;
                                        } else {
                                            echo "Kod Gönderilemedi";
                                            exit;
                                        }
                                    } else {
                                        echo '<div class="alert alert-danger" role="alert">Kayıtlı Hesap Bulunamadı! <br> Lütfen mail adresinizi kontrol ediniz</div>';
                                        echo '<meta http-equiv="refresh" content="4; url=sifirlamabilgi.php"> ';
                                        exit;
                                    }
                                } ?>
                            </div>
                            <div class="p-2 mt-4">
                                <form action="sifirlamabilgi.php" method="post">
                                    <div class="mb-3">
                                        <label class="form-label" for="username">Mailinize Gelen YGN başlayan Sıfırlama Kodu</label>
                                        <input required type="text" class="form-control" id="text" name="kod" placeholder="ÖRN: YGNXXXXX">
                                    </div>
                                    <div class="row">
                                        <div class="mt-3 text-end col-md-12">
                                            <button style="width:100%" class="btn btn-success w-sm waves-effect waves-light" type="submit" name="oturumyaptirs">Parolanızı Sıfırlayın!</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 text-center">
                        <p>© <script>
                                document.write(new Date().getFullYear())
                            </script> Tüm Hakları Saklıdır - Gemaş</p>
                    </div>
                </div>
            </div>
            <!-- end row -->
        </div>
        <!-- end container -->
    </div>
    <!-- JAVASCRIPT -->
    <script src="assets_login/libs/jquery/jquery.min.js"></script>
    <script src="assets_login/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets_login/libs/metismenu/metisMenu.min.js"></script>
    <script src="assets_login/libs/simplebar/simplebar.min.js"></script>
    <!--Yazılım: Erkan AK--> <!--E-Posta: erkanak50@gmail.com--> <!--Telefon: 05520838290-->
    <script src="assets_login/libs/node-waves/waves.min.js"></script>
    <script src="assets_login/libs/waypoints/lib/jquery.waypoints.min.js"></script>
    <script src="assets_login/libs/jquery.counterup/jquery.counterup.min.js"></script>
    <!-- App js -->
    <script src="assets_login/js/app.js"></script>
</body>

</html>