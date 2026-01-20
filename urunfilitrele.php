<?php include "fonk.php";
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

        .numa {
            background-color: lightgray;
            font-size: 16px;
            line-height: 40px;
        }

        .numa2 {
            background-color: red;
            font-size: 16px;
            color: white;
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
                            <!--    <button type="button" class="btn btn-success waves-effect waves-light float-right" data-bs-toggle="modal" data-bs-target=".yenikategori">Yeni Fihrist Tanımlayınız</button> -->
                            <button type="button" class="btn btn-info btn-sm waves-effect waves-light float-right" data-bs-toggle="modal" data-bs-target=".yardim">Yardım</button>
                            <hr>
                            <div class="card"><br>
                                <div class="row">
                                    <div class="col-md-4">
                                        <a href="urunler.php" class="btn btn-info"> Yeni Arama Yapın </a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h4 class="card-title mb-4">Ürünleri İnceleyiniz</h4>
                                    <table id="datatsable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>Stok Kodu</th>
                                                <th>Stok Adı</th>
                                                <th>Ölçü Birimi</th>
                                                <th>Liste Fiyatı</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $sayfada = 1000; // sayfada gösterilecek içerik miktarını belirtiyoruz.
                                            $toplam_icerik = '119800';
                                            $toplam_sayfa = ceil($toplam_icerik / $sayfada);
                                            $sayfa = isset($_GET['sayfa']) ? (int) $_GET['sayfa'] : 1;
                                            if ($sayfa < 1) $sayfa = 1;
                                            if ($sayfa > $toplam_sayfa) $sayfa = $toplam_sayfa;
                                            $limit = ($sayfa - 1) * $sayfada;
                                            $sayfada;
                                            // error_reporting(0);
                                            $kacsayfam = $_GET["sayfa"];
                                            if ($kacsayfam) {
                                                $kacsayfa = $_GET["sayfa"];
                                            } else {
                                                $kacsayfa =  "0";
                                            }
                                            $entegbag = mysqli_query($db, "SELECT * FROM  netsisentegrasyon ");
                                            $enteg = mysqli_fetch_array($entegbag);
                                            $saniyem = $enteg["saniye"];
                                            $javasaniyem = $enteg["javasaniye"];
                                            $saniye1 = $saniyem; //Token ve yenileme süresi 30 Dakikalık 30*60saniye
                                            $saniye2 = $javasaniyem; //Token ve yenileme süresi 30 Dakikalık (1000*1800) -1
                                            $sayfa = $sayfa;
                                            $limit = $limit;
                                            $tokenurl = "http://192.168.1.240:7070/api/v2/token";
                                            $kodarama = $_POST["urunkodu"];
                                            $adarama = $_POST["urunadi"];
                                            if ($kodarama) {
                                                $url2 = "http://192.168.1.240:7070/api/v2/items?q=STOK_KODU='" . $kodarama . "'";
                                            } else if ($adarama) {
                                                $url2 = "http://192.168.1.240:7070/api/v2/items?q=STOK_ADI='" . $kodarama . "'";
                                            } else if (empty($adarama) or empty($kodarama)) {
                                                echo '<meta http-equiv="refresh" content="0; url=urunler.php"> ';
                                            }
                                            $curl = curl_init($tokenurl);
                                            curl_setopt($curl, CURLOPT_URL, $tokenurl);
                                            curl_setopt($curl, CURLOPT_POST, true);
                                            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                                            $headers = array(
                                                "Content-Type: application/json",
                                            );
                                            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                                            $data = "grant_type=password&branchcode=0&password=" . $enteg["password"] . "&username=" . $enteg["username"] . "&dbname=" . $enteg["dbname"] . "&dbuser=" . $enteg["dbuser"] . "&dbpassword={{" . $enteg["dbpassword"] . "}}&dbtype=0";
                                            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                                            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                                            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                                            $resp = curl_exec($curl);
                                            curl_close($curl);
                                            $dizi = json_decode($resp);
                                            // print_r($resp);
                                            $token =  $dizi->access_token;
                                            $zamancookie = setcookie("tokensuresi", 'tokensuresi', time() + $saniye1);
                                            if (isset($_COOKIE['tokensuresi'])) {
                                                $_COOKIE['tokensuresi'];
                                            } else {
                                                $zamancookie = setcookie("tokensuresi", 'a' . $dakikasi, time() + $saniye1);
                                                echo '<meta http-equiv="refresh" content="0">';
                                                echo "Cookie bulunamadı";
                                            }
                                            $curl2 = curl_init($url2);
                                            curl_setopt($curl2, CURLOPT_URL, $url2);
                                            curl_setopt($curl2, CURLOPT_RETURNTRANSFER, true);
                                            $headers = array(
                                                "Accept: application/json",
                                                "Authorization: Bearer " . $token,
                                            );
                                            curl_setopt($curl2, CURLOPT_HTTPHEADER, $headers);
                                            //for debug only!
                                            curl_setopt($curl2, CURLOPT_SSL_VERIFYHOST, false);
                                            curl_setopt($curl2, CURLOPT_SSL_VERIFYPEER, false);
                                            $resp2 = curl_exec($curl2);
                                            curl_close($curl2);
                                            $dizi2 = json_decode($resp2);
                                            //print_r($resp2);
                                            foreach ($dizi2->Data as $data) {
                                                $stokkodu =  $urunkodu = $data->StokTemelBilgi->Stok_Kodu;
                                                $stokadi =  $urunkodu = $data->StokTemelBilgi->Stok_Adi;
                                                $olcubirimi =  $urunkodu = $data->StokTemelBilgi->Olcu_Br1;
                                                $satisfiyati =  $urunkodu = $data->StokTemelBilgi->Satis_Fiat1;
                                                $dovizcinsi =  $urunkodu = $data->StokTemelBilgi->Sat_Dov_Tip;
                                                if ($dovizcinsi == '0') {
                                                    $dov = 'TL';
                                                } else  if ($dovizcinsi == '1') {
                                                    $dov = 'USD';
                                                } else   if ($dovizcinsi == '2') {
                                                    $dov = 'EUR';
                                                }
                                            ?>
                                                <tr>
                                                    <td><?php echo $stokkodu; ?></td>
                                                    <td><?php echo $stokadi; ?></td>
                                                    <td><?php echo $olcubirimi; ?></td>
                                                    <td><?php echo $satisfiyati;
                                                        echo ' ' . $dov; ?></td>
                                                </tr>
                                            <?php
                                            }
                                            // StokTemelBilgi  Stok_Kodu 
                                            // $urunkodu = $dizi2['Data'][0]['StokTemelBilgi']['Stok_Adi'];
                                            if (isset($_COOKIE['tokensuresi'])) {
                                                // $_COOKIE['tokensuresi'];
                                                // "Token Var";
                                            } else {
                                                echo '<p style="color:white; background-color:red; padding:2%">Token Bulunamadı!</p>';
                                            }
                                            echo '
                                                <script type="text/javascript">
                                                setTimeout(function() {
                                                    window.location.reload(1);
                                                }, ' . $saniye2 . ');
                                                </script>';
                                            ?>
                                        </tbody>
                                    </table>
                                    <hr>
                                    <div class="container" style="overflow-x: scroll; width:100%">
                                        <nav aria-label="...">
                                            <ul class="pagination">
                                                <?php for ($s = 1; $s <= $toplam_sayfa; $s++) {
                                                    if ($sayfa == $s) { // eğer bulunduğumuz sayfa ise link yapma. 
                                                ?>
                                                        <li class="page-item active">
                                                            <a class="page-link" href="#"><?php echo $s; ?> <span class="sr-only">(current)</span></a>
                                                        </li>
                                                    <?php
                                                    } else { ?>
                                                        <li class="page-item"><a class="page-link" href="?sayfa=<?php echo $s; ?>"><?php echo $s; ?></a></li>
                                                <?php   }
                                                } ?>
                                            </ul>
                                        </nav>
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