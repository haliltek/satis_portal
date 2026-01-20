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
                                <?php
                                error_reporting(E_ALL ^ E_NOTICE);
                                //ini_set('max_execution_time',300);
                                //ini_set('max_input_time',300);
                                odbc_close_all();

                                // Cari, plasiyer, fiyat bilgisi vs. bilgileri sorgulamak için aktif şirketin veritabanına bağlanır.
                                $baglanti = odbc_connect('DRIVER={SQL Server};SERVER=192.168.1.240;DATABASE=gemasR2022', 'b2b', 'mEHF72gnvh3G!');
                                try {
                                    $kernel = new COM("NETOPENX50.Kernel") or die("Unable to instantiate Netopenx50.Kernel");
                                    $Sirket = $kernel->yenisirket(0, "SIRKETADI", "TEMELSET", "", "KULLANICIADI", "KULLANICISIFRE", 0);
                                    $Fatura = $kernel->yeniFatura($Sirket, 7);
                                    //Sipariş numarası post edilmezse yeni numara oluştur.
                                    $skod = ($_POST['skod']);
                                    if ($skod != '') {
                                        $Fatura->Ust->FATIRS_NO = "$skod";
                                    } else {
                                        $Fatura->Ust->FATIRS_NO = $Fatura->YeniNumara("S");
                                    }
                                    /*
Manuel numara girişi yapılmak istenirse
//$Fatura->Ust->FATIRS_NO ="S00000000037997"; */
                                    $CariKod = "$_POST[carikodu]";
                                    $PLA_KODU = $_POST['PLA_KODU'];
                                    // Cari aktif fiyatgrubu veritabanından sorgulanıyor
                                    $sorgu = odbc_exec($baglanti, "select FIYATGRUBU from TBLCASABIT WITH(NOLOCK) where CARI_KOD = '$CariKod'");
                                    $FIYATGRUBU = odbc_fetch_object($sorgu);
                                    $FIYATGRUBU = $FIYATGRUBU->FIYATGRUBU;
                                    // Hazırlanacak bir frontend arayüz ile aşağıdaki veriler post edilebilir.
                                    $stoklar = $_POST['stoklar'];
                                    $adetler = $_POST['adetler'];
                                    $eklan1 = $_POST['eklan1'];
                                    $Fatura->Ust->CariKod = "$_POST[carikodu]";
                                    $Fatura->Ust->Tarih = date('d-m-Y');
                                    $Fatura->Ust->FiiliTarih = date('d-m-Y');
                                    // Ek açıklamalar bölümünde Türkçe karakter sorunları yaşamamak için
                                    $Fatura->Ust->Aciklama = mb_convert_encoding("$_POST[aciklama]", "windows-1254", "utf-8");
                                    $Fatura->Ust->EKACK1 = mb_convert_encoding("$_POST[EKACK1]", "windows-1254", "utf-8");
                                    $Fatura->Ust->EKACK2 = mb_convert_encoding("$_POST[EKACK2]", "windows-1254", "utf-8");
                                    $Fatura->Ust->EKACK3 = mb_convert_encoding("$_POST[EKACK3]", "windows-1254", "utf-8");
                                    $Fatura->Ust->EKACK4 = mb_convert_encoding("$_POST[EKACK4]", "windows-1254", "utf-8");
                                    $Fatura->Ust->EKACK5 = mb_convert_encoding("$_POST[EKACK5]", "windows-1254", "utf-8");
                                    $Fatura->Ust->EKACK6 = mb_convert_encoding("$_POST[EKACK6]", "windows-1254", "utf-8");
                                    $Fatura->Ust->EKACK7 = mb_convert_encoding("$_POST[EKACK7]", "windows-1254", "utf-8");
                                    $Fatura->Ust->EKACK8 = mb_convert_encoding("$_POST[EKACK8]", "windows-1254", "utf-8");
                                    $Fatura->Ust->EKACK9 = mb_convert_encoding("$_POST[EKACK9]", "windows-1254", "utf-8");
                                    $Fatura->Ust->EKACK10 = mb_convert_encoding("$_POST[EKACK10]", "windows-1254", "utf-8");
                                    $Fatura->Ust->EKACK11 = mb_convert_encoding("$_POST[EKACK11]", "windows-1254", "utf-8");
                                    $Fatura->Ust->EKACK12 = mb_convert_encoding("$_POST[EKACK12]", "windows-1254", "utf-8");
                                    $Fatura->Ust->EKACK13 = mb_convert_encoding("$_POST[EKACK13]", "windows-1254", "utf-8");
                                    $Fatura->Ust->EKACK14 = mb_convert_encoding("$_POST[EKACK14]", "windows-1254", "utf-8");
                                    $Fatura->Ust->EKACK15 = mb_convert_encoding("$_POST[EKACK15]", "windows-1254", "utf-8");
                                    $Fatura->Ust->EKACK16 = mb_convert_encoding("$_POST[EKACK16]", "windows-1254", "utf-8");
                                    $Fatura->Ust->ENTEGRE_TRH = date('d-m-Y');
                                    $Fatura->Ust->KOD1 = "1";
                                    $Fatura->Ust->FIYATTARIHI = date('d-m-Y');
                                    $Fatura->Ust->SIPARIS_TEST = date('d-m-Y');
                                    $Fatura->Ust->KOSULTARIHI = date('d-m-Y');
                                    $Fatura->Ust->TIPI = 2;
                                    $Fatura->Ust->PLA_KODU = "$_POST[plakodu]";
                                    $Fatura->Ust->KDV_DAHILMI = false;
                                    //Sipariş kalem bilgileri oluşturma 
                                    for ($i = 0; $i < count($stoklar); $i++) {
                                        $FatKalem = $Fatura->kalemYeni("$stoklar[$i]");
                                        $FatKalem->DEPO_KODU = "0";
                                        $FatKalem->STra_GCMIK = "$adetler[$i]"; // adet
                                        $FatKalem->Listeno = "1";
                                        $FatKalem->Olcubr =  "1";
                                        if ($ekalan1[$i]) $FatKalem->Ekalan1 = mb_convert_encoding("$renkler[$i]", "windows-1254", "utf-8");
                                        $sorgu = odbc_exec($baglanti, "SELECT FIYAT1 FROM TBLSTOKFIAT WITH(NOLOCK) WHERE FIYATGRUBU='$FIYATGRUBU' and STOKKODU='$stoklar[$i]' and A_S = 'S'");
                                        $fiyat = odbc_fetch_object($sorgu);
                                        $FatKalem->STra_NF = number_format($fiyat->FIYAT1, 5, ',', '.');
                                        $FatKalem->STra_BF = number_format($fiyat->FIYAT1, 5, ',', '.');
                                    }
                                    $Fatura->kayitYeni();
                                    $Sirket->LogOff();
                                    $kernel->FreeNetsisLibrary();
                                    //Oluşturulan sipariş kaydının nosunu getirir.
                                    echo $Fatura->Ust->FATIRS_NO;
                                    odbc_close_all();
                                } catch (Exception $e) {
                                    // var_dump($e);
                                    echo '<br><br><br>';
                                    echo $e->getMessage();
                                    echo '<br><br><br>';
                                    echo $kernel->SonNetsisHata->Hata;
                                    echo '<br><br><br>';
                                    echo $kernel->SonNetsisHata->Detay;
                                }
                                ?>
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