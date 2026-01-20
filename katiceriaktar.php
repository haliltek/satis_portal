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
    <link rel="stylesheet" type="text/css" href="progress_style.css">
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery.form/4.3.0/jquery.form.min.js"></script>
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

                            <a type="button" class="btn btn-warning waves-effect waves-light float-right" href="katiceriaktar.php">Excel Kategori Toplu İçe Aktarın</a>

                            <button type="button" class="btn btn-info btn-sm waves-effect waves-light float-right" data-bs-toggle="modal" data-bs-target=".yardim">Yardım</button>
                            <hr>
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-4">Kategori Listesi Excel Toplu Yükleme </h4>

                                    <div class='maindiv'>
                                        <br>
                                        <div id="barDiv"></div>
                                        <form action="katiceriaktarupload.php" id="formID" name="frmupload" method="post" enctype="multipart/form-data">
                                            <input type="hidden" name="MAX_FILE_SIZE" /><!--byte olarak (mutlaka input type="file" den önce olmalıdır)-->
                                            <input type="file" name="file[]" /><br>
                                            <br>
                                            <input type="submit" name="submit_file" value="Yüklemeyi Başlat" onclick="Yukleme_Goster('formID', 'barDiv', 'dairesel', '#22466E', 250, 250);" />
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
    <!-- App js -->
    <script src="assets/js/app.js"></script>
    <!-- Responsive examples -->
    <!-- Datatable init js -->
    <script src="assets/js/pages/datatables.init.js"></script>
    <!-- Required datatable js -->
    <script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <!-- Buttons examples -->
    <script src="assets/libs/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
    <script src="assets/libs/parsleyjs/parsley.min.js"></script>
    <script>
        function Yukleme_Goster(formID, divID, secim, renk, genislik, yukseklik) {
            if (secim == 'dairesel') {
                var tmpHTML = '<div class="c100 p0" id="progress" style="font-size: ' + genislik + 'px;">' +
                    '<span id="yuzde"></span>' +
                    '<div class="slice">' +
                    '<div class="bar"></div><div class="fill"></div>' +
                    '</div></div>' +
                    '<br style="clear: both;"><div id="mesaj"></div>';
                $('#' + divID).html(tmpHTML);
                dairesel_ilerleme(formID, renk);
            }
            if (secim == 'dogrusal') {
                var tmpHTML = '<div class="progress" style="width: ' + genislik + 'px;">' +
                    '<div class="bar_duz" id="bar_duz" style="height: ' + yukseklik + 'px;background-color: ' + renk + '"></div>' +
                    '<div class="yuzde" id="yuzde">0%</div></div>' +
                    '<div id="mesaj"></div>';
                $('#' + divID).html(tmpHTML);
                normal_ilerleme(formID);
            }
        }

        function normal_ilerleme(formID) {
            var bar_duz = $('#bar_duz');
            var yuzde = $('#yuzde');
            var mesaj = $('#mesaj');
            $('#' + formID).ajaxForm({
                beforeSubmit: function() {
                    bar_duz.width('0%')
                    yuzde.text('0%');
                    mesaj.html('Hazırlanıyor......');
                },
                uploadProgress: function(event, position, total, yuzdeComplete) {
                    bar_duz.width(yuzdeComplete + '%')
                    yuzde.text(yuzdeComplete + '%');
                    mesaj.html('<img src="images/loadexcel.gif"><br> <b style="font-size:20px">Yükleniyor......</b>');
                },
                success: function(veri) {
                    if (veri.indexOf('<wbr>') == 0) {
                        bar_duz.css({
                            "background-color": "#ff0000"
                        });
                        bar_duz.width('5%')
                        yuzde.text('5%');
                        mesaj.html(veri);
                    } else {
                        bar_duz.width('100%')
                        yuzde.text('100%');
                        mesaj.html(veri);
                    }
                }
            });
        }

        function dairesel_ilerleme(formID, renk) {
            var yuzde = $('#yuzde');
            var mesaj = $('#mesaj');
            var progress = $('#progress');
            $('.c100 .bar, .c100 .fill').css({
                "border-color": "" + renk + ""
            });
            $('.c100:hover, span').css({
                "color": "" + renk + ""
            });
            $('#' + formID).ajaxForm({
                beforeSubmit: function() {
                    progress.attr('class', 'c100 p0');
                    yuzde.text('0%');
                    mesaj.html('Hazırlanıyor......');
                },
                uploadProgress: function(event, position, total, yuzdeComplete) {
                    progress.attr('class', 'c100 p' + yuzdeComplete);
                    yuzde.text(yuzdeComplete + '%');
                    mesaj.html('<img src="images/loadexcel.gif"><br> <b style="font-size:20px">Yükleniyor......</b>');
                },
                success: function(veri) {
                    if (veri.indexOf('<wbr>') == 0) {
                        $('.c100 .bar, .c100 .fill').css({
                            "border-color": "#ff0000"
                        });
                        $('.c100:hover, span').css({
                            "color": "#ff0000"
                        });
                        progress.attr('class', 'c100 p5');
                        yuzde.text('5%');
                        mesaj.html(veri);
                    } else {
                        progress.attr('class', 'c100 p100');
                        yuzde.text('100%');
                        mesaj.html(veri);
                    }
                }
            });
        }
    </script>
</body>

</html>