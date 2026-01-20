<?php include "fonk.php";
oturumkontrol();
$id = $_GET["id"];
$departmansorgula = mysqli_query($db, "SELECT * FROM  departmanlar where id='$id'");
$departman = mysqli_fetch_array($departmansorgula);
$yetkisorgula = mysqli_query($db, "SELECT * FROM  yetkiler where departmanid = '$id' order by yetki_id desc limit 1");
$yetkiler = mysqli_fetch_array($yetkisorgula);
$sorgu1 = $yetkiler["urunler"];
$sorgu2 = $yetkiler["tanimlar"];
$sorgu3 = $yetkiler["degiskenler"];
if ($sorgu1 == null or $sorgu2 == null or $sorgu3 == null) {
    $genelayar_sorgu2 = "INSERT INTO yetkiler(departmanid) VALUES('$id')";
    $add = mysqli_query($db, $genelayar_sorgu2);
}
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

        .mb-3 label {
            color: white;
            margin-left: 5%
        }

        .mb-3 {
            color: white;
            padding: 2%
        }

        .mb-3 input {
            margin-left: 5%;
            width: 20px;
            height: 20px;
            font-size: 18px;
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
                            <?php
                            if (isset($_POST['duzenleme'])) {
                                $urunler = xss(addslashes($_POST["urunler"]));
                                $urunekle = xss(addslashes($_POST["urunekle"]));
                                $urunduzenle = xss(addslashes($_POST["urunduzenle"]));
                                $urunsil = xss(addslashes($_POST["urunsil"]));
                                $tanimlar = xss(addslashes($_POST["tanimlar"]));
                                $tanimduzenle = xss(addslashes($_POST["tanimduzenle"]));
                                $tanimekle = xss(addslashes($_POST["tanimekle"]));
                                $tanimsil = xss(addslashes($_POST["tanimsil"]));
                                $degiskenler = xss(addslashes($_POST["degiskenler"]));
                                $degiskenekle = xss(addslashes($_POST["degiskenekle"]));
                                $degiskenduzenle = xss(addslashes($_POST["degiskenduzenle"]));
                                $degiskensil = xss(addslashes($_POST["degiskensil"]));
                                $topluislemler = xss(addslashes($_POST["topluislemler"]));
                                $siparisler = xss(addslashes($_POST["siparisler"]));
                                $siparisekle = xss(addslashes($_POST["siparisekle"]));
                                $siparisduzenle = xss(addslashes($_POST["siparisduzenle"]));
                                $siparissil = xss(addslashes($_POST["siparissil"]));
                                $kargoyonetimi = xss(addslashes($_POST["kargoyonetimi"]));
                                $kargoyonetimiekle = xss(addslashes($_POST["kargoyonetimiekle"]));
                                $kargoyonetimiduzenle = xss(addslashes($_POST["kargoyonetimiduzenle"]));
                                $kargoyonetimisil = xss(addslashes($_POST["kargoyonetimisil"]));
                                $yapilandirma = xss(addslashes($_POST["yapilandirma"]));
                                $yapilandirmaekle = xss(addslashes($_POST["yapilandirmaekle"]));
                                $yapilandirmaduzenle = xss(addslashes($_POST["yapilandirmaduzenle"]));
                                $yapilandirmasil = xss(addslashes($_POST["yapilandirmasil"]));
                                $kategoriler = xss(addslashes($_POST["kategoriler"]));
                                $kategorilerekle = xss(addslashes($_POST["kategorilerekle"]));
                                $kategorilerduzenle = xss(addslashes($_POST["kategorilerduzenle"]));
                                $kategorilersil = xss(addslashes($_POST["kategorilersil"]));
                                $sirketler = xss(addslashes($_POST["sirketler"]));
                                $sirketlerekle = xss(addslashes($_POST["sirketlerekle"]));
                                $sirketlerduzenle = xss(addslashes($_POST["sirketlerduzenle"]));
                                $sirketlersil = xss(addslashes($_POST["sirketlersil"]));
                                $uyeler = xss(addslashes($_POST["uyeler"]));
                                $uyelerduzenle = xss(addslashes($_POST["uyelerduzenle"]));
                                $uyelerekle = xss(addslashes($_POST["uyelerekle"]));
                                $uyelersil = xss(addslashes($_POST["uyelersil"]));
                                $entegrasyonlar = xss(addslashes($_POST["entegrasyonlar"]));
                                $entegrasyonlarekle = xss(addslashes($_POST["entegrasyonlarekle"]));
                                $entegrasyonlarduzenle = xss(addslashes($_POST["entegrasyonlarduzenle"]));
                                $entegrasyonlarsil = xss(addslashes($_POST["entegrasyonlarsil"]));
                                $departmanlar = xss(addslashes($_POST["departmanlar"]));
                                $departmanlarekle = xss(addslashes($_POST["departmanlarekle"]));
                                $departmanlarduzenle = xss(addslashes($_POST["departmanlarduzenle"]));
                                $departmanlarsil = xss(addslashes($_POST["departmanlarsil"]));
                                $log = xss(addslashes($_POST["log"]));
                                $raporlar = xss(addslashes($_POST["raporlar"]));
                                $ayarlar = xss(addslashes($_POST["ayarlar"]));
                                $ids =  xss(addslashes($_POST["ids"]));
                                $kategoriduzenleme = "UPDATE yetkiler SET urunler = '$urunler', urunekle = '$urunekle', urunduzenle = '$urunduzenle',  urunsil = '$urunsil', tanimlar = '$tanimlar', tanimekle = '$tanimekle', tanimduzenle = '$tanimduzenle', tanimsil = '$tanimsil', degiskenler = '$degiskenler', degiskenekle = '$degiskenekle',  degiskenduzenle = '$degiskenduzenle', degiskensil = '$degiskensil', topluislemler = '$topluislemler', siparisler = '$siparisler', siparisekle = '$siparisekle', siparisduzenle = '$siparisduzenle', siparissil = '$siparissil', kargoyonetimi = '$kargoyonetimi', kargoyonetimiekle = '$kargoyonetimiekle', kargoyonetimiduzenle = '$kargoyonetimiduzenle', kargoyonetimisil = '$kargoyonetimisil', yapilandirma = '$yapilandirma', yapilandirmaekle = '$yapilandirmaekle', yapilandirmaduzenle = '$yapilandirmaduzenle',  yapilandirmasil = '$yapilandirmasil', kategoriler = '$kategoriler', kategorilerekle = '$kategorilerekle', kategorilerduzenle = '$kategorilerduzenle', kategorilersil = '$kategorilersil', sirketler = '$sirketler',  sirketlerekle = '$sirketlerekle', sirketlerduzenle = '$sirketlerduzenle', sirketlersil = '$sirketlersil', uyeler = '$uyeler', uyelerekle = '$uyelerekle', uyelerduzenle = '$uyelerduzenle', uyelersil = '$uyelersil', entegrasyonlar = '$entegrasyonlar', entegrasyonlarekle = '$entegrasyonlarekle', entegrasyonlarduzenle = '$entegrasyonlarduzenle',  entegrasyonlarsil = '$entegrasyonlarsil', departmanlar = '$departmanlar', departmanlarekle = '$departmanlarekle', departmanlarduzenle = '$departmanlarduzenle', departmanlarsil = '$departmanlarsil',  log = '$log', raporlar = '$raporlar',  ayarlar = '$ayarlar' WHERE departmanid= '$ids'";
                                $duzenleme = mysqli_query($db, $kategoriduzenleme);
                                if ($duzenleme) {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Yetki Güncelleme','$yonetici_id_sabit','$zaman','Başarılı')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-success" role="alert">  Sayın ' . $adsoyad . ' <br> Yetki Başarıyla Kaydedilmiştir. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="2; url=yetkilendirme.php?id=' . $id . '"> ';
                                } else {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Yetki Güncelleme','$yonetici_id_sabit','$zaman','Başarısız')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-danger" role="alert">  Sayın ' . $adsoyad . ' <br> Yetki Malesef Kaydedilemedi. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="2; url=yetkilendirme.php?id=' . $id . '"> ';
                                }
                            }
                            $id = $_GET["id"];
                            $departmansor = mysqli_query($db, "SELECT * FROM  departmanlar where id='$id' ");
                            $departmanim = mysqli_fetch_array($departmansor);
                            $departmanid = $departmanim["id"];
                            $yetkisor = mysqli_query($db, "SELECT * FROM  yetkiler where departmanid='$departmanid' ");
                            $yetkim = mysqli_fetch_array($yetkisor);
                            $yetkim["departmanid"];
                            $urunler = $yetkim['urunler'];
                            $urunekle = $yetkim['urunekle'];
                            $urunduzenle = $yetkim['urunduzenle'];
                            $urunsil = $yetkim['urunsil'];
                            $tanimlar = $yetkim['tanimlar'];
                            $tanimekle = $yetkim['tanimekle'];
                            $tanimduzenle = $yetkim['tanimduzenle'];
                            $tanimsil = $yetkim['tanimsil'];
                            $degiskenler = $yetkim['degiskenler'];
                            $degiskenekle = $yetkim['degiskenekle'];
                            $degiskenduzenle = $yetkim['degiskenduzenle'];
                            $degiskensil = $yetkim['degiskensil'];
                            $topluislemler = $yetkim['topluislemler'];
                            $siparisler = $yetkim['siparisler'];
                            $siparisekle = $yetkim['siparisekle'];
                            $siparisduzenle = $yetkim['siparisduzenle'];
                            $siparissil = $yetkim['siparissil'];
                            $kargoyonetimi = $yetkim['kargoyonetimi'];
                            $kargoyonetimiekle = $yetkim['kargoyonetimiekle'];
                            $kargoyonetimiduzenle = $yetkim['kargoyonetimiduzenle'];
                            $kargoyonetimisil = $yetkim['kargoyonetimisil'];
                            $yapilandirma = $yetkim['yapilandirma'];
                            $yapilandirmaekle = $yetkim['yapilandirmaekle'];
                            $yapilandirmaduzenle = $yetkim['yapilandirmaduzenle'];
                            $yapilandirmasil = $yetkim['yapilandirmasil'];
                            $kategoriler = $yetkim['kategoriler'];
                            $kategorilerekle = $yetkim['kategorilerekle'];
                            $kategorilerduzenle = $yetkim['kategorilerduzenle'];
                            $kategorilersil = $yetkim['kategorilersil'];
                            $sirketler = $yetkim['sirketler'];
                            $sirketlerekle = $yetkim['sirketlerekle'];
                            $sirketlerduzenle = $yetkim['sirketlerduzenle'];
                            $sirketlersil = $yetkim['sirketlersil'];
                            $uyeler = $yetkim['uyeler'];
                            $uyelerekle = $yetkim['uyelerekle'];
                            $uyelerduzenle = $yetkim['uyelerduzenle'];
                            $uyelersil = $yetkim['uyelersil'];
                            $entegrasyonlar = $yetkim['entegrasyonlar'];
                            $entegrasyonlarekle = $yetkim['entegrasyonlarekle'];
                            $entegrasyonlarduzenle = $yetkim['entegrasyonlarduzenle'];
                            $entegrasyonlarsil = $yetkim['entegrasyonlarsil'];
                            $departmanlar = $yetkim['departmanlar'];
                            $departmanlarekle = $yetkim['departmanlarekle'];
                            $departmanlarduzenle = $yetkim['departmanlarduzenle'];
                            $departmanlarsil = $yetkim['departmanlarsil'];
                            $log = $yetkim['log'];
                            $raporlar = $yetkim['raporlar'];
                            $ayarlar = $yetkim['ayarlar'];
                            ?>
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-4"><?php echo $departman["departman"]; ?> Yetkilendirme Yönetimi</h4>
                                    <form method="post" action="yetkilendirme.php?id=<?php echo $id; ?>">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #22466E;">
                                                    <label class="form-label" for="validationCustom01">Ürünleri Görebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="urunler" <?php if ($urunler == 'Evet') {
                                                                                            echo 'checked';
                                                                                        } ?> value="Evet" />Evet
                                                    <input type="radio" name="urunler" <?php if ($urunler == 'Hayır'  or $urunekle == '0') {
                                                                                            echo 'checked';
                                                                                        } ?> value="Hayır" />Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #22466E;">
                                                    <label class="form-label" for="validationCustom01">Ürünleri Ekleyebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="urunekle" <?php if ($urunekle == 'Evet') {
                                                                                            echo 'checked';
                                                                                        } ?> value="Evet" />Evet
                                                    <input type="radio" name="urunekle" <?php if ($urunekle == 'Hayır' or $urunekle == '0') {
                                                                                            echo 'checked';
                                                                                        } ?> value="Hayır" />Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #22466E;">
                                                    <label class="form-label" for="validationCustom01">Ürünleri Düzenlesin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="urunduzenle" <?php if ($urunduzenle == 'Evet') {
                                                                                                echo 'checked';
                                                                                            } ?> value="Evet" />Evet
                                                    <input type="radio" name="urunduzenle" <?php if ($urunduzenle == 'Hayır' or $urunduzenle == '0') {
                                                                                                echo 'checked';
                                                                                            } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #22466E;">
                                                    <label class="form-label" for="validationCustom01">Ürünleri Silebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="urunsil" <?php if ($urunsil == 'Evet') {
                                                                                            echo 'checked';
                                                                                        } ?> value="Evet" />Evet
                                                    <input type="radio" name="urunsil" <?php if ($urunsil == 'Hayır' or $urunsil == '0') {
                                                                                            echo 'checked';
                                                                                        } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #22466E;">
                                                    <label class="form-label" for="validationCustom01">Tanımları Görebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="tanimlar" <?php if ($tanimlar == 'Evet') {
                                                                                            echo 'checked';
                                                                                        } ?> value="Evet" />Evet
                                                    <input type="radio" name="tanimlar" <?php if ($tanimlar == 'Hayır' or $tanimlar == '0') {
                                                                                            echo 'checked';
                                                                                        } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #22466E;">
                                                    <label class="form-label" for="validationCustom01">Tanımları Ekleyebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="tanimekle" <?php if ($tanimekle == 'Evet') {
                                                                                                echo 'checked';
                                                                                            } ?> value="Evet" />Evet
                                                    <input type="radio" name="tanimekle" <?php if ($tanimekle == 'Hayır' or $tanimekle == '0') {
                                                                                                echo 'checked';
                                                                                            } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #22466E;">
                                                    <label class="form-label" for="validationCustom01">Tanımları Düzenlesin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="tanimduzenle" <?php if ($tanimduzenle == 'Evet') {
                                                                                                echo 'checked';
                                                                                            } ?> value="Evet" />Evet
                                                    <input type="radio" name="tanimduzenle" <?php if ($tanimduzenle == 'Hayır' or $tanimduzenle == '0') {
                                                                                                echo 'checked';
                                                                                            } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #22466E;">
                                                    <label class="form-label" for="validationCustom01">Tanımları Silebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="tanimsil" <?php if ($tanimsil == 'Evet') {
                                                                                            echo 'checked';
                                                                                        } ?> value="Evet" />Evet
                                                    <input type="radio" name="tanimsil" <?php if ($tanimsil == 'Hayır' or $tanimsil == '0') {
                                                                                            echo 'checked';
                                                                                        } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #22466E;">
                                                    <label class="form-label" for="validationCustom01">Değişkenler Görebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="degiskenler" <?php if ($degiskenler == 'Evet') {
                                                                                                echo 'checked';
                                                                                            } ?> value="Evet" />Evet
                                                    <input type="radio" name="degiskenler" <?php if ($degiskenler == 'Hayır' or $degiskenler == '0') {
                                                                                                echo 'checked';
                                                                                            } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #22466E;">
                                                    <label class="form-label" for="validationCustom01">Değişkenler Ekleyebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="degiskenekle" <?php if ($degiskenekle == 'Evet') {
                                                                                                echo 'checked';
                                                                                            } ?> value="Evet" />Evet
                                                    <input type="radio" name="degiskenekle" <?php if ($degiskenekle == 'Hayır' or $degiskenekle == '0') {
                                                                                                echo 'checked';
                                                                                            } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #22466E;">
                                                    <label class="form-label" for="validationCustom01">Değişkenler Düzenlesin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="degiskenduzenle" <?php if ($degiskenduzenle == 'Evet') {
                                                                                                    echo 'checked';
                                                                                                } ?> value="Evet" />Evet
                                                    <input type="radio" name="degiskenduzenle" <?php if ($degiskenduzenle == 'Hayır' or $degiskenduzenle == '0') {
                                                                                                    echo 'checked';
                                                                                                } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #22466E;">
                                                    <label class="form-label" for="validationCustom01">Değişkenler Silebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="degiskensil" <?php if ($degiskensil == 'Evet') {
                                                                                                echo 'checked';
                                                                                            } ?> value="Evet" />Evet
                                                    <input type="radio" name="degiskensil" <?php if ($degiskensil == 'Hayır' or $degiskensil == '0') {
                                                                                                echo 'checked';
                                                                                            } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #22466E;">
                                                    <label class="form-label" for="validationCustom01">Toplu İşlemleri Görebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="topluislemler" <?php if ($topluislemler == 'Evet') {
                                                                                                    echo 'checked';
                                                                                                } ?> value="Evet" />Evet
                                                    <input type="radio" name="topluislemler" <?php if ($topluislemler == 'Hayır' or $topluislemler == '0') {
                                                                                                    echo 'checked';
                                                                                                } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                            </div>
                                            <div class="col-md-3">
                                            </div>
                                            <div class="col-md-3">
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #5baaff;">
                                                    <label class="form-label" for="validationCustom01">Siparişleri Görebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="siparisler" <?php if ($siparisler == 'Evet') {
                                                                                                echo 'checked';
                                                                                            } ?> value="Evet" />Evet
                                                    <input type="radio" name="siparisler" <?php if ($siparisler == 'Hayır' or $siparisler == '0') {
                                                                                                echo 'checked';
                                                                                            } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #5baaff;">
                                                    <label class="form-label" for="validationCustom01">Siparişleri Ekleyebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="siparisekle" <?php if ($siparisekle == 'Evet') {
                                                                                                echo 'checked';
                                                                                            } ?> value="Evet" />Evet
                                                    <input type="radio" name="siparisekle" <?php if ($siparisekle == 'Hayır' or $siparisekle == '0') {
                                                                                                echo 'checked';
                                                                                            } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #5baaff;">
                                                    <label class="form-label" for="validationCustom01">Siparişleri Düzenlesin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="siparisduzenle" <?php if ($siparisduzenle == 'Evet') {
                                                                                                    echo 'checked';
                                                                                                } ?> value="Evet" />Evet
                                                    <input type="radio" name="siparisduzenle" <?php if ($siparisduzenle == 'Hayır' or $siparisduzenle == '0') {
                                                                                                    echo 'checked';
                                                                                                } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #5baaff;">
                                                    <label class="form-label" for="validationCustom01">Siparişleri Silebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="siparissil" <?php if ($siparissil == 'Evet') {
                                                                                                echo 'checked';
                                                                                            } ?> value="Evet" />Evet
                                                    <input type="radio" name="siparissil" <?php if ($siparissil == 'Hayır' or $siparissil == '0') {
                                                                                                echo 'checked';
                                                                                            } ?> value="Hayır" /> Hayır
                                                    <div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #5baaff;">
                                                    <label class="form-label" for="validationCustom01">Kargo Yönetimi Görebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="kargoyonetimi" <?php if ($kargoyonetimi == 'Evet') {
                                                                                                    echo 'checked';
                                                                                                } ?> value="Evet" />Evet
                                                    <input type="radio" name="kargoyonetimi" <?php if ($urukargoyonetiminkargoyonetimiler == 'Hayır' or $kargoyonetimi == '0') {
                                                                                                    echo 'checked';
                                                                                                } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #5baaff;">
                                                    <label class="form-label" for="validationCustom01">Kargo Yönetimi Ekleyebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="kargoyonetimiekle" <?php if ($kargoyonetimiekle == 'Evet') {
                                                                                                        echo 'checked';
                                                                                                    } ?> value="Evet" />Evet
                                                    <input type="radio" name="kargoyonetimiekle" <?php if ($kargoyonetimiekle == 'Hayır' or $kargoyonetimiekle == '0') {
                                                                                                        echo 'checked';
                                                                                                    } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #5baaff;">
                                                    <label class="form-label" for="validationCustom01">Kargo Yönetimi Düzenlesin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="kargoyonetimiduzenle" <?php if ($kargoyonetimiduzenle == 'Evet') {
                                                                                                        echo 'checked';
                                                                                                    } ?> value="Evet" />Evet
                                                    <input type="radio" name="kargoyonetimiduzenle" <?php if ($kargoyonetimiduzenle == 'Hayır' or $kargoyonetimiduzenle == '0') {
                                                                                                        echo 'checked';
                                                                                                    } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #5baaff;">
                                                    <label class="form-label" for="validationCustom01">Kargo Yönetimi Silebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="kargoyonetimisil" <?php if ($kargoyonetimisil == 'Evet') {
                                                                                                    echo 'checked';
                                                                                                } ?> value="Evet" />Evet
                                                    <input type="radio" name="kargoyonetimisil" <?php if ($kargoyonetimisil == 'Hayır' or $kargoyonetimisil == '0') {
                                                                                                    echo 'checked';
                                                                                                } ?> value="Hayır" /> Hayır
                                                    <div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #5baaff;">
                                                    <label class="form-label" for="validationCustom01">Yapılandırma Görebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="yapilandirma" <?php if ($yapilandirma == 'Evet') {
                                                                                                echo 'checked';
                                                                                            } ?> value="Evet" />Evet
                                                    <input type="radio" name="yapilandirma" <?php if ($yapilandirma == 'Hayır' or $yapilandirma == '0') {
                                                                                                echo 'checked';
                                                                                            } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #5baaff;">
                                                    <label class="form-label" for="validationCustom01">Yapılandırma Ekleyebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="yapilandirmaekle" <?php if ($yapilandirmaekle == 'Evet') {
                                                                                                    echo 'checked';
                                                                                                } ?> value="Evet" />Evet
                                                    <input type="radio" name="yapilandirmaekle" <?php if ($yapilandirmaekle == 'Hayır' or $yapilandirmaekle == '0') {
                                                                                                    echo 'checked';
                                                                                                } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #5baaff;">
                                                    <label class="form-label" for="validationCustom01">Yapılandırma Düzenlesin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="yapilandirmaduzenle" <?php if ($yapilandirmaduzenle == 'Evet') {
                                                                                                        echo 'checked';
                                                                                                    } ?> value="Evet" />Evet
                                                    <input type="radio" name="yapilandirmaduzenle" <?php if ($yapilandirmaduzenle == 'Hayır' or $yapilandirmaduzenle == '0') {
                                                                                                        echo 'checked';
                                                                                                    } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #5baaff;">
                                                    <label class="form-label" for="validationCustom01">Yapılandırma Silebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="yapilandirmasil" <?php if ($yapilandirmasil == 'Evet') {
                                                                                                    echo 'checked';
                                                                                                } ?> value="Evet" />Evet
                                                    <input type="radio" name="yapilandirmasil" <?php if ($yapilandirmasil == 'Hayır' or $yapilandirmasil == '0') {
                                                                                                    echo 'checked';
                                                                                                } ?> value="Hayır" /> Hayır
                                                    <div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #5baaff;">
                                                    <label class="form-label" for="validationCustom01">Kategorileri Görebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="kategoriler" <?php if ($kategoriler == 'Evet') {
                                                                                                echo 'checked';
                                                                                            } ?> value="Evet" />Evet
                                                    <input type="radio" name="kategoriler" <?php if ($kategoriler == 'Hayır' or $kategoriler == '0') {
                                                                                                echo 'checked';
                                                                                            } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #5baaff;">
                                                    <label class="form-label" for="validationCustom01">Kategorileri Ekleyebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="kategorilerekle" <?php if ($kategorilerekle == 'Evet') {
                                                                                                    echo 'checked';
                                                                                                } ?> value="Evet" />Evet
                                                    <input type="radio" name="kategorilerekle" <?php if ($kategorilerekle == 'Hayır' or $kategorilerekle == '0') {
                                                                                                    echo 'checked';
                                                                                                } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #5baaff;">
                                                    <label class="form-label" for="validationCustom01">Kategorileri Düzenlesin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="kategorilerduzenle" <?php if ($kategorilerduzenle == 'Evet') {
                                                                                                        echo 'checked';
                                                                                                    } ?> value="Evet" />Evet
                                                    <input type="radio" name="kategorilerduzenle" <?php if ($kategorilerduzenle == 'Hayır' or $kategorilerduzenle == '0') {
                                                                                                        echo 'checked';
                                                                                                    } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #5baaff;">
                                                    <label class="form-label" for="validationCustom01">Kategorileri Silebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="kategorilersil" <?php if ($kategorilersil == 'Evet') {
                                                                                                    echo 'checked';
                                                                                                } ?> value="Evet" />Evet
                                                    <input type="radio" name="kategorilersil" <?php if ($kategorilersil == 'Hayır' or $kategorilersil == '0') {
                                                                                                    echo 'checked';
                                                                                                } ?> value="Hayır" /> Hayır
                                                    <div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #1a4fef;">
                                                    <label class="form-label" for="validationCustom01">Şirketleri Görebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="sirketler" <?php if ($sirketler == 'Evet') {
                                                                                                echo 'checked';
                                                                                            } ?> value="Evet" />Evet
                                                    <input type="radio" name="sirketler" <?php if ($sirketler == 'Hayır' or $sirketler == '0') {
                                                                                                echo 'checked';
                                                                                            } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #1a4fef;">
                                                    <label class="form-label" for="validationCustom01">Şirketleri Ekleyebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="sirketlerekle" <?php if ($urunler == 'Evet') {
                                                                                                    echo 'checked';
                                                                                                } ?> value="Evet" />Evet
                                                    <input type="radio" name="sirketlerekle" <?php if ($urunler == 'Hayır' or $urunler == '0') {
                                                                                                    echo 'checked';
                                                                                                } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #1a4fef;">
                                                    <label class="form-label" for="validationCustom01">Şirketleri Düzenlesin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="sirketlerduzenle" <?php if ($sirketlerduzenle == 'Evet') {
                                                                                                    echo 'checked';
                                                                                                } ?> value="Evet" />Evet
                                                    <input type="radio" name="sirketlerduzenle" <?php if ($sirketlerduzenle == 'Hayır' or $sirketlerduzenle == '0') {
                                                                                                    echo 'checked';
                                                                                                } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #1a4fef;">
                                                    <label class="form-label" for="validationCustom01">Şirketleri Silebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="sirketlersil" <?php if ($sirketlersil == 'Evet') {
                                                                                                echo 'checked';
                                                                                            } ?> value="Evet" />Evet
                                                    <input type="radio" name="sirketlersil" <?php if ($sirketlersil == 'Hayır' or $sirketlersil == '0') {
                                                                                                echo 'checked';
                                                                                            } ?> value="Hayır" /> Hayır
                                                    <div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #1a4fef;">
                                                    <label class="form-label" for="validationCustom01">Üyeleri Görebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="uyeler" <?php if ($uyeler == 'Evet') {
                                                                                            echo 'checked';
                                                                                        } ?> value="Evet" />Evet
                                                    <input type="radio" name="uyeler" <?php if ($uyeler == 'Hayır' or $uyeler == '0') {
                                                                                            echo 'checked';
                                                                                        } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #1a4fef;">
                                                    <label class="form-label" for="validationCustom01">Üyeleri Ekleyebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="uyelerekle" <?php if ($uyelerekle == 'Evet') {
                                                                                                echo 'checked';
                                                                                            } ?> value="Evet" />Evet
                                                    <input type="radio" name="uyelerekle" <?php if ($uyelerekle == 'Hayır' or $uyelerekle == '0') {
                                                                                                echo 'checked';
                                                                                            } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #1a4fef;">
                                                    <label class="form-label" for="validationCustom01">Üyeleri Düzenlesin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="uyelerduzenle" <?php if ($uyelerduzenle == 'Evet') {
                                                                                                    echo 'checked';
                                                                                                } ?> value="Evet" />Evet
                                                    <input type="radio" name="uyelerduzenle" <?php if ($uyelerduzenle == 'Hayır' or $uyelerduzenle == '0') {
                                                                                                    echo 'checked';
                                                                                                } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #1a4fef;">
                                                    <label class="form-label" for="validationCustom01">Üyeleri Silebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="uyelersil" <?php if ($uyelersil == 'Evet') {
                                                                                                echo 'checked';
                                                                                            } ?> value="Evet" />Evet
                                                    <input type="radio" name="uyelersil" <?php if ($uyelersil == 'Hayır' or $uyelersil == '0') {
                                                                                                echo 'checked';
                                                                                            } ?> value="Hayır" /> Hayır
                                                    <div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #12ba98;">
                                                    <label class="form-label" for="validationCustom01">Entegrasyonları Görebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="entegrasyonlar" <?php if ($entegrasyonlar == 'Evet') {
                                                                                                    echo 'checked';
                                                                                                } ?> value="Evet" />Evet
                                                    <input type="radio" name="entegrasyonlar" <?php if ($entegrasyonlar == 'Hayır' or $entegrasyonlar == '0') {
                                                                                                    echo 'checked';
                                                                                                } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #12ba98;">
                                                    <label class="form-label" for="validationCustom01">Entegrasyonları Ekleyebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="entegrasyonlarekle" <?php if ($entegrasyonlarekle == 'Evet') {
                                                                                                        echo 'checked';
                                                                                                    } ?> value="Evet" />Evet
                                                    <input type="radio" name="entegrasyonlarekle" <?php if ($entegrasyonlarekle == 'Hayır' or $entegrasyonlarekle == '0') {
                                                                                                        echo 'checked';
                                                                                                    } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #12ba98;">
                                                    <label class="form-label" for="validationCustom01">Entegrasyonları Düzenlesin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="entegrasyonlarduzenle" <?php if ($entegrasyonlarduzenle == 'Evet') {
                                                                                                            echo 'checked';
                                                                                                        } ?> value="Evet" />Evet
                                                    <input type="radio" name="entegrasyonlarduzenle" <?php if ($entegrasyonlarduzenle == 'Hayır' or $entegrasyonlarduzenle == '0') {
                                                                                                            echo 'checked';
                                                                                                        } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #12ba98;">
                                                    <label class="form-label" for="validationCustom01">Entegrasyonları Silebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="entegrasyonlarsil" <?php if ($entegrasyonlarsil == 'Evet') {
                                                                                                        echo 'checked';
                                                                                                    } ?> value="Evet" />Evet
                                                    <input type="radio" name="entegrasyonlarsil" <?php if ($entegrasyonlarsil == 'Hayır' or $entegrasyonlarsil == '0') {
                                                                                                        echo 'checked';
                                                                                                    } ?> value="Hayır" /> Hayır
                                                    <div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #12ba98;">
                                                    <label class="form-label" for="validationCustom01">Departmanları Görebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="departmanlar" <?php if ($departmanlar == 'Evet') {
                                                                                                echo 'checked';
                                                                                            } ?> value="Evet" />Evet
                                                    <input type="radio" name="departmanlar" <?php if ($departmanlar == 'Hayır' or $departmanlar == '0') {
                                                                                                echo 'checked';
                                                                                            } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #12ba98;">
                                                    <label class="form-label" for="validationCustom01">Departmanları Ekleyebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="departmanlarekle" <?php if ($departmanlarekle == 'Evet') {
                                                                                                    echo 'checked';
                                                                                                } ?> value="Evet" />Evet
                                                    <input type="radio" name="departmanlarekle" <?php if ($departmanlarekle == 'Hayır' or $departmanlarekle == '0') {
                                                                                                    echo 'checked';
                                                                                                } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #12ba98;">
                                                    <label class="form-label" for="validationCustom01">Departmanları Düzenlesin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="departmanlarduzenle" <?php if ($departmanlarduzenle == 'Evet') {
                                                                                                        echo 'checked';
                                                                                                    } ?> value="Evet" />Evet
                                                    <input type="radio" name="departmanlarduzenle" <?php if ($departmanlarduzenle == 'Hayır' or $departmanlarduzenle == '0') {
                                                                                                        echo 'checked';
                                                                                                    } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #12ba98;">
                                                    <label class="form-label" for="validationCustom01">Departmanları Silebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="departmanlarsil" <?php if ($departmanlarsil == 'Evet') {
                                                                                                    echo 'checked';
                                                                                                } ?> value="Evet" />Evet
                                                    <input type="radio" name="departmanlarsil" <?php if ($departmanlarsil == 'Hayır' or $departmanlarsil == '0') {
                                                                                                    echo 'checked';
                                                                                                } ?> value="Hayır" /> Hayır
                                                    <div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #12ba98;">
                                                    <label class="form-label" for="validationCustom01">Log Kayıtlarını Görebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="log" <?php if ($log == 'Evet') {
                                                                                        echo 'checked';
                                                                                    } ?> value="Evet" />Evet
                                                    <input type="radio" name="log" <?php if ($log == 'Hayır' or $log == '0') {
                                                                                        echo 'checked';
                                                                                    } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                            </div>
                                            <div class="col-md-3">
                                            </div>
                                            <div class="col-md-3">
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #82a00b;">
                                                    <label class="form-label" for="validationCustom01">Raporları Görebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="raporlar" <?php if ($raporlar == 'Evet') {
                                                                                            echo 'checked';
                                                                                        } ?> value="Evet" />Evet
                                                    <input type="radio" name="raporlar" <?php if ($raporlar == 'Hayır' or $raporlar == '0') {
                                                                                            echo 'checked';
                                                                                        } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                            </div>
                                            <div class="col-md-3">
                                            </div>
                                            <div class="col-md-3">
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3" style="background: #e27b14;">
                                                    <label class="form-label" for="validationCustom01">Ayarları Görebilsin Mi?</label>
                                                    <br>
                                                    <input type="radio" name="ayarlar" <?php if ($ayarlar == 'Evet') {
                                                                                            echo 'checked';
                                                                                        } ?> value="Evet" />Evet
                                                    <input type="radio" name="ayarlar" <?php if ($ayarlar == 'Hayır' or $ayarlar == '0') {
                                                                                            echo 'checked';
                                                                                        } ?> value="Hayır" /> Hayır
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                            </div>
                                            <div class="col-md-3">
                                            </div>
                                            <div class="col-md-3">
                                            </div>
                                            <input type="text" name="ids" value="<?php echo $_GET["id"]; ?>" hidden>
                                            <div class="modal-footer">
                                                <button type="submit" name="duzenleme" class="btn btn-success">Yetkilendirmeyi Düzenleyin!</button>
                                            </div>
                                    </form>
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