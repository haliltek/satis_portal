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
  <style type="text/css">
    .box {
      width: auto;
      height: 150px;
      background-color: #22466E;
      margin: 0 auto;
      float: left;
      font-size: 100px;
      padding: 20px;
      color: #fff;
    }

    /* Step 1: Build the Animation */
    @-webkit-keyframes aniload {
      from {
        -webkit-transform: translate(0px, 1000px)
      }

      to {
        -webkit-transform: translate(0px, 0px)
      }
    }

    @-moz-keyframes aniload {
      from {
        -moz-transform: translate(0px, 1000px)
      }

      to {
        -moz-transform: translate(0px, 0px)
      }
    }

    @-ms-keyframes aniload {
      from {
        -ms-transform: translate(0px, 1000px)
      }

      to {
        -ms-transform: translate(0px, 0px)
      }
    }

    @-o-keyframes aniload {
      from {
        -o-transform: translate(0px, 1000px)
      }

      to {
        -o-transform: translate(0px, 0px)
      }
    }

    @keyframes aniload {
      from {
        transform: translate(0px, 1000px)
      }

      to {
        transform: translate(0px, 0px)
      }
    }

    /* Step 2: Call the Animation */
    #box1 {
      -webkit-animation: aniload 4s;
      -moz-animation: aniload 4s;
      -ms-animation: aniload 4s;
      -o-animation: aniload 4s;
      animation: aniload 4s;
    }

    #box2 {
      -webkit-animation: aniload 1s;
      -moz-animation: aniload 1s;
      -ms-animation: aniload 1s;
      -o-animation: aniload 1s;
      animation: aniload 1s;
    }

    #box3 {
      -webkit-animation: aniload 4s;
      -moz-animation: aniload 4s;
      -ms-animation: aniload 4s;
      -o-animation: aniload 4s;
      animation: aniload 4s;
    }

    #box4 {
      -webkit-animation: aniload 3s;
      -moz-animation: aniload 3s;
      -ms-animation: aniload 3s;
      -o-animation: aniload 3s;
      animation: aniload 3s;
    }

    #box5 {
      -webkit-animation: aniload 2s;
      -moz-animation: aniload 2s;
      -ms-animation: aniload 2s;
      -o-animation: aniload 2s;
      animation: aniload 2s;
    }

    .copyright {
      width: 100%;
      height: auto;
      background-color: #171717;
      color: #aaa;
      position: fixed;
      bottom: 0px;
      font: 12px Arial;
      padding: 5px;
      opacity: 0.4;
      border-top: #a00 2px solid;
      z-index: 99999;
      left: 0px;
      right: 0px;
    }

    .copyright:hover {
      opacity: 1
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
          <!-- start page title -->
          <div class="row">
            <div class="col-12">
              <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-0">ÇOK YAKINDA HİZMETİNİZDE</h4>
                <div class="page-title-right">
                  <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="javascript: void(0);">Anasayfa</a></li>
                    <li class="breadcrumb-item active">Aras Kargo Entegrasonu</li>
                  </ol>
                </div>
              </div>
            </div>
          </div>
          <!-- end page title -->
          <div class="row">
            <div class="sinir">
              <div class="box" id="box1">Ç</div>
              <div class="box" id="box2">O</div>
              <div class="box" id="box1">K</div>
              <div class="box" id="box4"> </div>
              <div class="box" id="box5">Y</div>
              <div class="box" id="box2">A</div>
              <div class="box" id="box5">K</div>
              <div class="box" id="box3">I</div>
              <div class="box" id="box2">N</div>
              <div class="box" id="box5">D</div>
              <div class="box" id="box1">A</div>
            </div>
            <div class="col-md-12 col-xl-12">
              <div class="card">
                <div>
                  <p class="text-center"><img src="images/ok.gif"></p>
                </div>
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
</body>

</html>