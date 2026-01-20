<?php include "fonk.php";
oturumkontrol();
$sirid = $_GET["id"];
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
</head>

<body data-layout="horizontal" data-topbar="colored">
    <!-- Begin page -->
    <div id="layout-wrapper">
        <header id="page-topbar">
            <?php include "menuler/ustmenu.php"; ?>
            <?php include "menuler/solmenu.php";
            if ($tanimlar == 'Hayır') {
                echo '<script language="javascript">window.location="anasayfa.php";</script>';
                die();
            } ?>
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
                                    <h4 class="card-title mb-4">Şirkete Ait Siparişler</h4>
                                    <div class="table-responsive">
                                        <table id="datatable-buttons" class="table table-striped table-bordered dt-responsive " style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                            <!-- <table id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;"> -->
                                            <thead>
                                                <tr>
                                                    <th scope="row" class="tablobaslik">Teklif No</th>
                                                    <th scope="row" class="tablobaslik">Hazırlayan</th>
                                                    <th scope="row" class="tablobaslik">Teklif Verilen</th>
                                                    <th scope="row" class="tablobaslik"> Proje Adı</th>
                                                    <th scope="row" class="tablobaslik"> Teklif Tarihi</th>
                                                    <th scope="row" class="tablobaslik"> Genel Toplam</th>
                                                    <th scope="row" class="tablobaslik"> Durum</th>
                                                    <th scope="row" class="tablobaslik"> İşlem</th>
                                                </tr>
                                            </thead>
                                            <tbody class="yazilar">
                                                <?php
                                                // where durum!='Sipariş' and durum!='Sipariş Ödemesi Bekleniyor'

                                                $kontrolKullaniciAdi3 = mysqli_query($db, "SELECT * FROM  ogteklif2 where durum!='Beklemede' and  durum!='Tamamlandı' and sirketid='$sirid'");
                                                while ($dev2 = mysqli_fetch_array($kontrolKullaniciAdi3)) {
                                                ?>
                                                    <tr>
                                                        <td><?php echo $dev2["teklifkodu"]; ?></td>
    80                                                          <td><?php $prepInfo = $dbManager->resolvePreparer($dev2["hazirlayanid"] ?? ""); echo htmlspecialchars($prepInfo["name"])."<br><small>(".$prepInfo["source"].")</small>"; ?></td>
                                                        <td><?php $hazx2 =  $dev2["musteriid"];
                                                            if ($hazx2 == 'kendim') {
                                                                $personelcekereksorgulama3 = mysqli_query($db, "SELECT * FROM  personel WHERE personel_id='$personelid'");
                                                                $benkim = mysqli_fetch_array($personelcekereksorgulama3);
                                                                echo $benkim["p_adi"] . ' ' . $benkim["p_soyadi"] . ' <small>(Kendisi)</small>';
                                                            } else {
                                                                $personelcekereksorgulama3 = mysqli_query($db, "SELECT * FROM  sirketmusteriler WHERE id='$hazx2'");
                                                                $musterim = mysqli_fetch_array($personelcekereksorgulama3);
                                                                echo $musterim["adsoyad"] . '<br>' . $musterim["telefon"];
                                                            }
                                                            ?></td>
                                                        <td><?php echo $dev2["projeadi"]; ?></td>
                                                        <td><?php echo $dev2["tekliftarihi"]; ?></td>
                                                        <td><?php echo number_format($dev2["geneltoplam"], 2, ',', '.'); ?> ₺</td>
                                                        <td><?php echo $durumu =  $dev2["durum"]; ?></td>
                                                        <td>
                                                            <?php

                                                            if ($durumu == 'Sipariş') { ?>

                                                                <a target="_blank" href="sip-dep1-show.php?te=<?php echo $dev2["id"]; ?>&sta=Sipariş" class="btn btn-success btn-xs">Siparişi İncele</a>
                                                            <?php } else
             if ($durumu == 'Teklif İptal Edildi') { ?>

                                                                <a target="_blank" href="sip-dep1-show.php?te=<?php echo $dev2["id"]; ?>&sta=Teklif" class="btn btn-success btn-xs">Teklifi İncele</a>
                                                            <?php } else 
            if ($durumu == 'Sipariş Ödemesi Bekleniyor') { ?>

                                                                <a target="_blank" href="sip-dep1-show.php?te=<?php echo $dev2["id"]; ?>&sta=Sipariş" class="btn btn-success btn-xs">Siparişi İncele</a>
                                                            <?php } else  if ($durumu == 'Kontrol Aşamasında') { ?>

                                                                <a target="_blank" href="sip-dep1-show.php?te=<?php echo $dev2["id"]; ?>&sta=Sipariş" class="btn btn-success btn-xs">Siparişi İncele</a>
                                                            <?php } else { ?>

                                                                <a target="_blank" href="sip-dep1-show.php?te=<?php echo $dev2["id"]; ?>&sta=Sipariş" class="btn btn-success btn-xs">Siparişi İncele</a>
                                                            <?php }  ?>
                                                    </tr>
                                                <?php  } ?>
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
    <div class="modal fade yenikategori" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myLargeModalLabel">Yeni Marka Tanımlayınız</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form method="post" action="markalar.php" class="needs-validation" novalidate enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-5">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Marka Adı</label>
                                    <input type="text" name="markaadi" class="form-control" id="validationCustom01" placeholder="ÖR. Panasonic - Viko" required>
                                    <div class="valid-feedback"> Başarılı! </div>
                                    <div class="invalid-feedback">Marka Adı Zorunludur </div>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Title</label>
                                    <input type="text" name="title" class="form-control" id="validationCustom01" maxlength="65" placeholder="ÖR. Aydınlatma Ürünleri" required>
                                    <div class="valid-feedback"> Başarılı! </div>
                                    <div class="invalid-feedback">Title SEO Açısından Gerekli ve Zorunludur </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Sıra</label>
                                    <input type="number" name="siralama" class="form-control" id="validationCustom01" placeholder="ÖR. 2" required>
                                    <div class="valid-feedback"> Başarılı! </div>
                                    <div class="invalid-feedback">Sayfada Bu Sıralamayla Çıkacağından Zorunludur </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Öne Çıkan Mı?</label>
                                    <select name="onecikan" class="form-control" id="validationCustom01" placeholder="ÖR. Panasonic - Viko" required>
                                        <option value="Evet">Evet</option>
                                        <option value="Hayır">Hayır</option>
                                    </select>
                                    <div class="valid-feedback"> Başarılı! </div>
                                    <div class="invalid-feedback">Öne Çıkan Belirlemek Zorunludur </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label" for="validationCustom01">Marka Resim</label>
                                    <input name="resimdosya" type="file" multiple="multiple" name="resimdosya[]" class="form-control" id="recipient-name">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Vazgeçtim, Kapat</button>
                        <button type="submit" name="kayit" class="btn btn-success">Yeni Marka Oluştur!</button>
                    </div>
                </form>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div>
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
    $Kategori_sorgulama = mysqli_query($db, "SELECT * FROM  markalar ");
    while ($markalar = mysqli_fetch_array($Kategori_sorgulama)) {
    ?>
        <div class="modal fade duzenle<?php echo $markalar["marka_id"]; ?>" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="myLargeModalLabel"><b><?php echo $markalar["kategori_adi"]; ?></b> Düzenleyin!</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        </button>
                    </div>
                    <form method="post" action="markalar.php" class="needs-validation" novalidate>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Marka Adı</label>
                                        <input type="text" name="markaadi" class="form-control" value="<?php echo $markalar["kategori_adi"]; ?>" id="validationCustom01" placeholder="ÖR. Panasonic - Viko" required>
                                        <div class="valid-feedback"> Başarılı! </div>
                                        <div class="invalid-feedback">Marka Adı Zorunludur </div>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Title</label>
                                        <input type="text" name="title" class="form-control" value="<?php echo $markalar["title"]; ?>" id="validationCustom01" maxlength="65" placeholder="ÖR. Aydınlatma Ürünleri" required>
                                        <div class="valid-feedback"> Başarılı! </div>
                                        <div class="invalid-feedback">Title SEO Açısından Gerekli ve Zorunludur </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Sıra</label>
                                        <input type="number" name="siralama" value="<?php echo $markalar["siralama"]; ?>" class="form-control" id="validationCustom01" placeholder="ÖR. 2" required>
                                        <div class="valid-feedback"> Başarılı! </div>
                                        <div class="invalid-feedback">Sayfada Bu Sıralamayla Çıkacağından Zorunludur </div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Öne Çıkan Mı?</label>
                                        <select name="onecikan" class="form-control" id="validationCustom01" placeholder="ÖR. Panasonic - Viko" required>
                                            <option value="<?php echo $markalar["onecikan"]; ?>" selected><?php echo $markalar["onecikan"]; ?> Seçili Durumda</option>
                                            <option value="Evet">Evet</option>
                                            <option value="Hayır">Hayır</option>
                                        </select>
                                        <div class="valid-feedback"> Başarılı! </div>
                                        <div class="invalid-feedback">Öne Çıkan Belirlemek Zorunludur </div>
                                    </div>
                                </div>
                                <input type="text" name="icerikid" value="<?php echo $markalar["marka_id"]; ?>" hidden>
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
    <?php
    $Kategori_sorgulama = mysqli_query($db, "SELECT * FROM  markalar ");
    while ($markalar = mysqli_fetch_array($Kategori_sorgulama)) {
    ?>
        <div class="modal fade resim<?php echo $markalar["marka_id"]; ?>" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="myLargeModalLabel"><b><?php echo $markalar["kategori_adi"]; ?></b> Resimi Düzenleyin!</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        </button>
                    </div>
                    <form method="post" action="markalar.php" class="needs-validation" novalidate enctype="multipart/form-data">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Marka Resim</label>
                                        <input name="resimdosya" type="file" multiple="multiple" name="resimdosya[]" class="form-control" id="recipient-name">
                                    </div>
                                </div>
                                <input type="text" name="icerikid" value="<?php echo $markalar["marka_id"]; ?>" hidden>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Vazgeçtim, Kapat</button>
                            <button type="submit" name="resim" class="btn btn-success">Düzenleyin!</button>
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